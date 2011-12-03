<?php

namespace Jackalope\Transport\DoctrineDBAL;

use PHPCR\PropertyType;
use PHPCR\RepositoryException;
use PHPCR\Query\QueryInterface;
use PHPCR\NamespaceRegistryInterface;
use PHPCR\RepositoryInterface;
use PHPCR\Util\UUIDHelper;
use PHPCR\Util\QOM\Sql2ToQomQueryConverter;
use PHPCR\NoSuchWorkspaceException;
use PHPCR\ItemExistsException;
use PHPCR\ItemNotFoundException;
use PHPCR\ReferentialIntegrityException;
use PHPCR\ValueFormatException;
use PHPCR\PathNotFoundException;
use PHPCR\Query\InvalidQueryException;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\ArrayCache;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;

use Jackalope\Transport\QueryInterface as QueryTransport;
use Jackalope\Transport\WritingInterface;
use Jackalope\Transport\WorkspaceManagementInterface;
use Jackalope\Transport\NodeTypeManagementInterface;
use Jackalope\NodeType\NodeTypeManager;
use Jackalope\NodeType\PHPCR2StandardNodeTypes;
use Jackalope\NotImplementedException;

/**
 * Class to handle the communication between Jackalope and RDBMS via Doctrine DBAL.
 *
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache License Version 2.0, January 2004
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @author Luis Cordova <cordoval@gmail.com>
 */
class Client implements QueryTransport, WritingInterface, WorkspaceManagementInterface, NodeTypeManagementInterface
{
    /**
     * @var Doctrine\DBAL\Connection
     */
    private $conn;

    /**
     * @var bool
     */
    private $loggedIn = false;

    /**
     * @var \PHPCR\SimpleCredentials
     */
    private $credentials;

    /**
     * @var int|string
     */
    private $workspaceId;

    /**
     * @var string
     */
    private $workspaceName;

    /**
     * @var array
     */
    private $nodeIdentifiers = array();

    /**
     * @var PHPCR\NodeType\NodeTypeManagerInterface
     */
    private $nodeTypeManager = null;

    /**
     * @var array
     */
    private $fetchedUserNamespaces = false;

    /**
     * Check if an initial request on login should be send to check if repository exists
     * This is according to the JCR specifications and set to true by default
     * @see setCheckLoginOnServer
     * @var bool
     */
    private $checkLoginOnServer = true;

    /**
     * @var array
     */
    private $namespaces = array(
        NamespaceRegistryInterface::PREFIX_EMPTY => NamespaceRegistryInterface::NAMESPACE_EMPTY,
        NamespaceRegistryInterface::PREFIX_JCR => NamespaceRegistryInterface::NAMESPACE_JCR,
        NamespaceRegistryInterface::PREFIX_NT => NamespaceRegistryInterface::NAMESPACE_NT,
        NamespaceRegistryInterface::PREFIX_MIX => NamespaceRegistryInterface::NAMESPACE_MIX,
        NamespaceRegistryInterface::PREFIX_XML => NamespaceRegistryInterface::NAMESPACE_XML,
        'phpcr' => 'http://github.com/jackalope/jackalope', // TODO: Namespace?
    );

    /**
     * Indexes
     *
     * @var array
     */
    private $indexes;

    /**
     * @var string|null
     */
    private $sequenceWorkspaceName;
    /**
     * @var string|null
     */
    private $sequenceNodeName;
    /**
     * @var string|null
     */
    private $sequenceTypeName;

    /**
     * @var Doctrine\Common\Cache\Cache
     */
    private $cache;

    public function __construct($factory, Connection $conn, array $indexes = array(), Cache $cache = null)
    {
        $this->factory = $factory;
        $this->conn = $conn;
        $this->indexes = $indexes;
        $this->sequenceWorkspaceName = ($conn->getDatabasePlatform() instanceof PostgreSqlPlatform) ? 'phpcr_workspaces_id_seq' : null;
        $this->sequenceNodeName = ($conn->getDatabasePlatform() instanceof PostgreSqlPlatform) ? 'phpcr_nodes_id_seq' : null;
        $this->sequenceTypeName = ($conn->getDatabasePlatform() instanceof PostgreSqlPlatform) ? 'phpcr_type_nodes_id_seq' : null;
        $this->cache = $cache ?: new ArrayCache();
    }

    /**
     * @return Doctrine\DBAL\Connection
     */
    public function getConnection()
    {
        return $this->conn;
    }

    // inherit all doc
    public function createWorkspace($name, $srcWorkspace = null)
    {
        if (null !== $srcWorkspace) {
            throw new NotImplementedException();
        }

        $workspaceId = $this->getWorkspaceId($name);
        if ($workspaceId !== false) {
            throw new RepositoryException("Workspace '$name' already exists");
        }
        $this->conn->insert('phpcr_workspaces', array('name' => $name));
        $workspaceId = $this->conn->lastInsertId($this->sequenceWorkspaceName);
        if (!$workspaceId) {
            throw new RepositoryException("Workspace creation fails.");
        }

        $this->conn->insert("phpcr_nodes", array(
            'path'          => '/',
            'parent'        => '',
            'workspace_id'  => $workspaceId,
            'identifier'    => UUIDHelper::generateUUID(),
            'type'          => 'nt:unstructured',
            'local_name'    => '',
            'namespace'     => '',
            'props' => '<?xml version="1.0" encoding="UTF-8"?>
<sv:node xmlns:mix="http://www.jcp.org/jcr/mix/1.0" xmlns:nt="http://www.jcp.org/jcr/nt/1.0" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:jcr="http://www.jcp.org/jcr/1.0" xmlns:sv="http://www.jcp.org/jcr/sv/1.0" xmlns:rep="internal" />'
        ));
    }

    // inherit all doc
    public function login(\PHPCR\CredentialsInterface $credentials = null, $workspaceName = 'default')
    {
        $this->credentials = $credentials;
        $this->workspaceName = $workspaceName;

        if (!$this->checkLoginOnServer) {
            return true;
        }

        $this->workspaceId = $this->getWorkspaceId($workspaceName);
        // create default workspace if it not exists
        if (!$this->workspaceId && $workspaceName === "default") {
            $this->createWorkspace($workspaceName);
            $this->workspaceId = $this->getWorkspaceId($workspaceName);
        }

        if (!$this->workspaceId) {
            throw new NoSuchWorkspaceException("Requested workspace: $workspaceName");
        }

        $this->loggedIn = true;
        return true;
    }

    // inherit all doc
    public function logout()
    {
        $this->loggedIn = false;
        $this->conn = null;
    }

    // inherit all doc
    public function setCheckLoginOnServer($bool)
    {
        $this->checkLoginOnServer = $bool;
    }

    private function getWorkspaceId($workspaceName)
    {
        try {
            $sql = "SELECT id FROM phpcr_workspaces WHERE name = ?";
            return $this->conn->fetchColumn($sql, array($workspaceName));
        } catch(\PDOException $e) {
            if (1045 == $e->getCode()) {
                throw new \PHPCR\LoginException('Access denied with your credentials: '.$e->getMessage());
            }
            if ("42S02" == $e->getCode()) {
                throw new \PHPCR\RepositoryException('You did not properly set up the database for the repository. See README file for more information. Message from backend: '.$e->getMessage());
            }
            throw new \PHPCR\RepositoryException('Unexpected error talking to the backend: '.$e->getMessage());
        }
    }

    private function assertLoggedIn()
    {
        if (!$this->loggedIn) {
            if (!$this->checkLoginOnServer && $this->workspaceName) {
                $credentials = $this->credentials;
                $workspaceName = $this->workspaceName;
                $this->credentials = $this->workspaceName = null;
                $this->checkLoginOnServer = true;
                if ($this->login($credentials, $workspaceName)) {
                    return;
                }
            }

            throw new RepositoryException();
        }
    }

    // inherit all doc
    public function getRepositoryDescriptors()
    {
        return array(
          'identifier.stability' => RepositoryInterface::IDENTIFIER_STABILITY_INDEFINITE_DURATION,
          'jcr.repository.name'  => 'jackalope_doctrine_dbal',
          'jcr.repository.vendor' => 'Jackalope Community',
          'jcr.repository.vendor.url' => 'http://github.com/jackalope',
          'jcr.repository.version' => '1.0.0-DEV',
          'jcr.specification.name' => 'Content Repository for PHP',
          'jcr.specification.version' => false,
          'level.1.supported' => false,
          'level.2.supported' => false,
          'node.type.management.autocreated.definitions.supported' => true,
          'node.type.management.inheritance' => true,
          'node.type.management.multiple.binary.properties.supported' => true,
          'node.type.management.multivalued.properties.supported' => true,
          'node.type.management.orderable.child.nodes.supported' => false,
          'node.type.management.overrides.supported' => false,
          'node.type.management.primary.item.name.supported' => true,
          'node.type.management.property.types' => true,
          'node.type.management.residual.definitions.supported' => false,
          'node.type.management.same.name.siblings.supported' => false,
          'node.type.management.update.in.use.suported' => false,
          'node.type.management.value.constraints.supported' => false,
          'option.access.control.supported' => false,
          'option.activities.supported' => false,
          'option.baselines.supported' => false,
          'option.journaled.observation.supported' => false,
          'option.lifecycle.supported' => false,
          'option.locking.supported' => false,
          'option.node.and.property.with.same.name.supported' => false,
          'option.node.type.management.supported' => true,
          'option.observation.supported' => false,
          'option.query.sql.supported' => false,
          'option.retention.supported' => false,
          'option.shareable.nodes.supported' => false,
          'option.simple.versioning.supported' => false,
          'option.transactions.supported' => false, // TODO: this claimed true but we need to implement TransactionInterface to really support transactions
          'option.unfiled.content.supported' => true,
          'option.update.mixin.node.types.supported' => true,
          'option.update.primary.node.type.supported' => true,
          'option.versioning.supported' => false,
          'option.workspace.management.supported' => true,
          'option.xml.export.supported' => false,
          'option.xml.import.supported' => true,
          'query.full.text.search.supported' => false,
          'query.joins' => false,
          'query.languages' => '',
          'query.stored.queries.supported' => false,
          'query.xpath.doc.order' => false,
          'query.xpath.pos.index' => false,
          'write.supported' => true,
        );
    }

    // inherit all doc
    public function getNamespaces()
    {
        if ($this->fetchedUserNamespaces === false) {
            $data = $this->conn->fetchAll('SELECT * FROM phpcr_namespaces');
            $this->fetchedUserNamespaces = true;

            foreach ($data as $row) {
                $this->namespaces[$row['prefix']] = $row['uri'];
            }
        }
        return $this->namespaces;
    }

    // inherit all doc
    public function copyNode($srcAbsPath, $dstAbsPath, $srcWorkspace = null)
    {
        $this->assertLoggedIn();

        $workspaceId = $this->workspaceId;
        if (null !== $srcWorkspace) {
            $workspaceId = $this->getWorkspaceId($srcWorkspace);
            if ($workspaceId === false) {
                throw new NoSuchWorkspaceException("Source workspace '" . $srcWorkspace . "' does not exist.");
            }
        }

        if (substr($dstAbsPath, -1, 1) == "]") {
            // TODO: Understand assumptions of CopyMethodsTest::testCopyInvalidDstPath more
            throw new RepositoryException("Invalid destination path");
        }

        $srcNodeId = $this->pathExists($srcAbsPath);
        if (!$srcNodeId) {
            throw new PathNotFoundException("Source path '".$srcAbsPath."' not found");
        }

        if ($this->pathExists($dstAbsPath)) {
            throw new ItemExistsException("Cannot copy to destination path '" . $dstAbsPath . "' that already exists.");
        }

        if (!$this->pathExists($this->getParentPath($dstAbsPath))) {
            throw new PathNotFoundException("Parent of the destination path '" . $this->getParentPath($dstAbsPath) . "' has to exist.");
        }

        // Algorithm:
        // 1. Select all nodes with path $srcAbsPath."%" and iterate them
        // 2. create a new node with path $dstAbsPath + leftovers, with a new uuid. Save old => new uuid
        // 3. copy all properties from old node to new node
        // 4. if a reference is in the properties, either update the uuid based on the map if its inside the copied graph or keep it.
        // 5. "May drop mixin types"

        try {
            $this->conn->beginTransaction();

            $sql = "SELECT * FROM phpcr_nodes WHERE path LIKE ? AND workspace_id = ?";
            $stmt = $this->conn->executeQuery($sql, array($srcAbsPath . "%", $workspaceId));

            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $newPath = str_replace($srcAbsPath, $dstAbsPath, $row['path']);

                $dom = new \DOMDocument('1.0', 'UTF-8');
                $dom->loadXML($row['props']);

                $newNodeId = $this->syncNode(null, $newPath, $this->getParentPath($newPath), $row['type'], array(), array('dom' => $dom, 'binaryData' => array()));

                $query = "INSERT INTO phpcr_binarydata (node_id, property_name, workspace_id, idx, data) " .
                         "SELECT ?, b.property_name, ?, b.idx, b.data " .
                         "FROM phpcr_binarydata b WHERE b.node_id = ?";
                $this->conn->executeUpdate($query, array($newNodeId, $this->workspaceId, $srcNodeId));
            }
            $this->conn->commit();
        } catch (\Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /**
     * @param string $path
     * @return array
     */
    private function getJcrName($path)
    {
        $name = implode("", array_slice(explode("/", $path), -1, 1));
        if (strpos($name, ":") === false) {
            $alias = "";
        } else {
            list($alias, $name) = explode(":", $name);
        }

        $namespaces = $this->getNamespaces();

        return array($namespaces[$alias], $name);
    }

    private function syncNode($uuid, $path, $parent, $type, $props = array(), $propsData = array())
    {
        // TODO: Not sure if there are always ALL props in $props, should be grab the online data here?
        // TODO: Binary data is handled very inefficiently here, UPSERT will really be necessary here as well as lazy handling

        $this->conn->beginTransaction();

        try {
            if (!$propsData) {
                $propsData = $this->propsToXML($props);
            }

            if ($uuid === null) {
                $uuid = UUIDHelper::generateUUID();
            }

            $nodeId = $this->pathExists($path);
            if (!$nodeId) {
                list($namespace, $localName) = $this->getJcrName($path);
                $this->conn->insert("phpcr_nodes", array(
                    'identifier'    => $uuid,
                    'type'          => $type,
                    'path'          => $path,
                    'local_name'    => $localName,
                    'namespace'     => $namespace,
                    'parent'        => $parent,
                    'workspace_id'  => $this->workspaceId,
                    'props'         => $propsData['dom']->saveXML(),
                ));

                $nodeId = $this->conn->lastInsertId($this->sequenceNodeName);
            } else {
                $this->conn->update('phpcr_nodes', array('props' => $propsData['dom']->saveXML()), array('id' => $nodeId));
            }
            $this->nodeIdentifiers[$path] = $uuid;

            if (isset($propsData['binaryData'])) {
                $this->syncBinaryData($nodeId, $propsData['binaryData']);
            }

            // update foreign keys (references)
            $this->syncForeignKeys($nodeId, $path, $props);

            // Update internal indexes
            $this->syncInternalIndexes();
            // Update user indexes
            $this->syncUserIndexes();

            $this->conn->commit();
        } catch(\Exception $e) {
            $this->conn->rollback();
            throw $e;
        }

        return $nodeId;
    }

    private function syncInternalIndexes()
    {
        // TODO:
    }

    private function syncUserIndexes()
    {

    }

    private function syncBinaryData($nodeId, $binaryData)
    {
        foreach ($binaryData as $propertyName => $binaryValues) {
            foreach ($binaryValues as $idx => $data) {
                $this->conn->delete('phpcr_binarydata', array(
                    'node_id'       => $nodeId,
                    'property_name' => $propertyName,
                    'workspace_id'  => $this->workspaceId,
                ));
                $this->conn->insert('phpcr_binarydata', array(
                    'node_id'       => $nodeId,
                    'property_name' => $propertyName,
                    'workspace_id'  => $this->workspaceId,
                    'idx'           => $idx,
                    'data'          => $data,
                ));
            }
        }
    }

    private function syncForeignKeys($nodeId, $path, $props)
    {
        $this->conn->delete('phpcr_nodes_foreignkeys', array('source_id' => $nodeId));

        foreach ($props as $property) {
            $type = $property->getType();
            if ($type === PropertyType::REFERENCE || $type === PropertyType::WEAKREFERENCE) {
                $values = array_unique( $property->isMultiple() ? $property->getString() : array($property->getString()) );

                foreach ($values as $value) {
                    try {
                        $targetId = $this->pathExists($this->getNodePathForIdentifier($value));

                        $this->conn->insert('phpcr_nodes_foreignkeys', array(
                            'source_id' => $nodeId,
                            'source_property_name' => $property->getName(),
                            'target_id' => $targetId,
                            'type' => $type
                        ));
                    } catch (ItemNotFoundException $e) {
                        if ($type == PropertyType::REFERENCE) {
                            throw new ReferentialIntegrityException(
                                "Trying to store reference to non-existant node with path '" . $value . "' in " .
                                "node " . $path . " property " . $property->getName()
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * Seperate properties array into an xml and binary data.
     *
     * @param array $properties
     * @param bool $inlineBinaries
     * @return array ('dom' => $dom, 'binary' => streams)
     */
    static public function propsToXML($properties, $inlineBinaries = false)
    {
        $namespaces = array(
            'mix' => "http://www.jcp.org/jcr/mix/1.0",
            'nt' => "http://www.jcp.org/jcr/nt/1.0",
            'xs' => "http://www.w3.org/2001/XMLSchema",
            'jcr' => "http://www.jcp.org/jcr/1.0",
            'sv' => "http://www.jcp.org/jcr/sv/1.0",
            'rep' => "internal"
        );

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $rootNode = $dom->createElement('sv:node');
        foreach ($namespaces as $namespace => $uri) {
            $rootNode->setAttribute('xmlns:' . $namespace, $uri);
        }
        $dom->appendChild($rootNode);

        $binaryData = null;
        foreach ($properties as $property) {
            /* @var $prop \PHPCR\PropertyInterface */
            $propertyNode = $dom->createElement('sv:property');
            $propertyNode->setAttribute('sv:name', $property->getName());
            $propertyNode->setAttribute('sv:type', $property->getType()); // TODO: Name! not int
            $propertyNode->setAttribute('sv:multi-valued', $property->isMultiple() ? "1" : "0");

            switch ($property->getType()) {
                case PropertyType::NAME:
                case PropertyType::URI:
                case PropertyType::WEAKREFERENCE:
                case PropertyType::REFERENCE:
                case PropertyType::PATH:
                case PropertyType::STRING:
                    $values = $property->getString();
                    break;
                case PropertyType::DECIMAL:
                    $values = $property->getDecimal();
                    break;
                case PropertyType::BOOLEAN:;
                    $values = $property->getBoolean() ? "1" : "0";
                    break;
                case PropertyType::LONG:
                    $values = $property->getLong();
                    break;
                case PropertyType::BINARY:
                    if ($property->isMultiple()) {
                        $values = $values = array();
                        foreach ((array)$property->getBinary() as $binary) {
                            $binary = stream_get_contents($binary);
                            $binaryData[$property->getName()][] = $binary;
                            $values[] = strlen($binary);
                        }
                    } else {
                        $binary = stream_get_contents($property->getBinary());
                        $binaryData[$property->getName()][] = $binary;
                        $values = strlen($binary);
                    }
                    break;
                case PropertyType::DATE:
                    $date = $property->getDate();
                    if (!$date instanceof \DateTime) {
                        $date = new \DateTime("now");
                    }
                    $values = $date->format('r');
                    break;
                case PropertyType::DOUBLE:
                    $values = $property->getDouble();
                    break;
            }

            foreach ((array)$values as $value) {
                $propertyNode->appendChild($dom->createElement('sv:value', $value));
            }

            $rootNode->appendChild($propertyNode);
        }

        return array('dom' => $dom, 'binaryData' => $binaryData);
    }

    // inherit all doc
    public function getAccessibleWorkspaceNames()
    {
        $workspaceNames = array();
        foreach ($this->conn->fetchAll("SELECT name FROM phpcr_workspaces") as $row) {
            $workspaceNames[] = $row['name'];
        }
        return $workspaceNames;
    }

    // inherit all doc
    public function getNode($path)
    {
        $path = $this->encodeSanitizePath($path);
        $this->assertLoggedIn();

        $sql = "SELECT * FROM phpcr_nodes WHERE path = ? AND workspace_id = ?";
        $row = $this->conn->fetchAssoc($sql, array($path, $this->workspaceId));
        if (!$row) {
            throw new ItemNotFoundException("Item /".$path." not found.");
        }

        $data = new \stdClass();
        // TODO: only return jcr:uuid when this node implements mix:referencable
        $data->{'jcr:uuid'} = $row['identifier'];
        $data->{'jcr:primaryType'} = $row['type'];
        $this->nodeIdentifiers[$path] = $row['identifier'];

        $sql = "SELECT path FROM phpcr_nodes WHERE parent = ? AND workspace_id = ?";
        $children = $this->conn->fetchAll($sql, array($path, $this->workspaceId));

        foreach ($children as $child) {
            $childName = explode("/", $child['path']);
            $childName = end($childName);
            $data->{$childName} = new \stdClass();
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadXML($row['props']);

        foreach ($dom->getElementsByTagNameNS('http://www.jcp.org/jcr/sv/1.0', 'property') as $propertyNode) {
            $name = $propertyNode->getAttribute('sv:name');
            $values = array();
            $type = (int)$propertyNode->getAttribute('sv:type');
            foreach ($propertyNode->childNodes as $valueNode) {
                switch ($type) {
                    case PropertyType::NAME:
                    case PropertyType::URI:
                    case PropertyType::WEAKREFERENCE:
                    case PropertyType::REFERENCE:
                    case PropertyType::PATH:
                    case PropertyType::DECIMAL:
                    case PropertyType::STRING:
                        $values[] = $valueNode->nodeValue;
                        break;
                    case PropertyType::BOOLEAN:
                        $values[] = (bool)$valueNode->nodeValue;
                        break;
                    case PropertyType::LONG:
                        $values[] = (int)$valueNode->nodeValue;
                        break;
                    case PropertyType::BINARY:
                        $values[] = (int)$valueNode->nodeValue;
                        break;
                    case PropertyType::DATE:
                        $values[] = $valueNode->nodeValue;
                        break;
                    case PropertyType::DOUBLE:
                        $values[] = (double)$valueNode->nodeValue;
                        break;
                    default:
                        throw new \InvalidArgumentException("Type with constant " . $type . " not found.");
                }
            }

            if ($type == PropertyType::BINARY) {
                if ($propertyNode->getAttribute('sv:multi-valued') == 1) {
                    $data->{":" . $name} = $values;
                } else {
                    $data->{":" . $name} = $values[0];
                }
            } else {
                if ($propertyNode->getAttribute('sv:multi-valued') == 1) {
                    $data->{$name} = $values;
                } else {
                    $data->{$name} = $values[0];
                }
                $data->{":" . $name} = $type;
            }
        }

        return $data;
    }

    // inherit all doc
    public function getNodes($paths)
    {
        $nodes = array();
        foreach ($paths as $key => $path) {
            try {
                $nodes[$key] = $this->getNode($path);
            } catch (\PHPCR\ItemNotFoundException $e) {
                // ignore
            }
        }

        return $nodes;
    }

    private function pathExists($path)
    {
        $query = "SELECT id FROM phpcr_nodes WHERE path = ? AND workspace_id = ?";
        if ($nodeId = $this->conn->fetchColumn($query, array($path, $this->workspaceId))) {
            return $nodeId;
        }
        return false;
    }

    // inherit all doc
    public function deleteNode($path)
    {
        $this->assertLoggedIn();

        $nodeId = $this->pathExists($path);

        if (!$nodeId) {
            // This might still be a property
            $nodePath = $this->getParentPath($path);
            $nodeId = $this->pathExists($nodePath);
            if (!$nodeId) {
                // no we really don't know that path
                throw new ItemNotFoundException("No item found at ".$path);
            }
            $propertyName = str_replace($nodePath, "", $path);

            $query = "SELECT props FROM phpcr_nodes WHERE id = ?";
            $xml = $this->conn->fetchColumn($query, array($nodeId));

            $dom = new \DOMDocument('1.0', 'UTF-8');
            $dom->loadXml($xml);

            foreach ($dom->getElementsByTagNameNS('http://www.jcp.org/jcr/sv/1.0', 'property') as $propertyNode) {
                if ($propertyName == $propertyNode->getAttribute('sv:name')) {
                    $propertyNode->parentNode->removeChild($propertyNode);
                    break;
                }
            }
            $xml = $dom->saveXML();

            $query = "UPDATE phpcr_nodes SET props = ? WHERE id = ?";
            $params = array($xml, $nodeId);

        } else {
            $params = array($path."%", $this->workspaceId);

            $query = "SELECT count(*) FROM phpcr_nodes_foreignkeys fk INNER JOIN phpcr_nodes n ON n.id = fk.target_id " .
                     "WHERE n.path LIKE ? AND workspace_id = ? AND fk.type = " . PropertyType::REFERENCE;
            $fkReferences = $this->conn->fetchColumn($query, $params);
            if ($fkReferences > 0) {
                throw new ReferentialIntegrityException("Cannot delete " . $path . ": A reference points to this node or a subnode.");
            }

            $query = "DELETE FROM phpcr_nodes WHERE path LIKE ? AND workspace_id = ?";
        }

        $this->conn->beginTransaction();

        try {
            $this->conn->executeUpdate($query, $params);
            $this->conn->commit();

            return true;
        } catch(\Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    // inherit all doc
    public function deleteProperty($path)
    {
        // TODO:
    }

    // inherit all doc
    public function moveNode($srcAbsPath, $dstAbsPath)
    {
        $this->assertLoggedIn();

        throw new NotImplementedException("Moving nodes is not yet implemented");
    }

    /**
     * Get parent path of a path.
     *
     * @param string $path
     * @return string
     */
    private function getParentPath($path)
    {
        $parent = implode("/", array_slice(explode("/", $path), 0, -1));
        if (!$parent) {
            return "/";
        }
        return $parent;
    }

    /**
     * @param \PHPCR\NodeInterface $node
     * @param \PHPCR\NodeType\NodeTypeDefinitionInterface $def
     */
    private function validateNode($node, $def)
    {
        foreach ($def->getDeclaredChildNodeDefinitions() as $childDef) {
            /* @var $childDef \PHPCR\NodeType\NodeDefinitionInterface */
            if (!$node->hasNode($childDef->getName())) {
                if ($childDef->getName() === '*') {
                    continue;
                }

                if ($childDef->isMandatory() && !$childDef->isAutoCreated()) {
                    throw new RepositoryException(
                        "Child " . $child->getName() . " is mandatory, but is not present while ".
                        "saving " . $def->getName() . " at " . $node->getPath()
                    );
                } elseif ($childDef->isAutoCreated()) {
                    throw new NotImplementedException("Auto-creation of child node '".$def->getName()."#".$childDef->getName()."' is not yet supported in DoctrineDBAL transport.");
                }

                if ($node->hasProperty($childDef->getName())) {
                    throw new RepositoryException(
                        "Node " . $node->getPath() . " has property with name ".
                        $childDef->getName() . " but its node type '". $def->getName() . "' defines a ".
                        "child with this name."
                    );
                }
            }
        }

        foreach ($def->getDeclaredPropertyDefinitions() as $propertyDef) {
            /* @var $propertyDef \PHPCR\NodeType\PropertyDefinitionInterface */
            if ($propertyDef->getName() == '*') {
                continue;
            }

            if (!$node->hasProperty($propertyDef->getName())) {
                if ($node->hasNode($propertyDef->getName())) {
                    throw new RepositoryException(
                        "Node " . $node->getPath() . " has child with name ".
                        $propertyDef->getName() . " but its node type '". $def->getName() . "' defines a ".
                        "property with this name."
                    );
                }

                if ($propertyDef->isMandatory() && !$propertyDef->isAutoCreated()) {
                    throw new RepositoryException(
                        "Property " . $propertyDef->getName() . " is mandatory, but is not present while ".
                        "saving " . $def->getName() . " at " . $node->getPath()
                    );
                } elseif ($propertyDef->isAutoCreated()) {
                    $defaultValues = $propertyDef->getDefaultValues();
                    $node->setProperty(
                        $propertyDef->getName(),
                        $propertyDef->isMultiple() ? $defaultValues : (isset($defaultValues[0]) ? $defaultValues[0] : null),
                        $propertyDef->getRequiredType()
                    );
                }
            }
        }
    }

    private function getResponsibleNodeTypes($node)
    {
        // This is very slow i believe :-(
        $nodeDef = $node->getPrimaryNodeType();
        $nodeTypes = $node->getMixinNodeTypes();
        array_unshift($nodeTypes, $nodeDef);
        foreach ($nodeTypes as $nodeType) {
            /* @var $nodeType \PHPCR\NodeType\NodeTypeDefinitionInterface */
            foreach ($nodeType->getDeclaredSupertypes() as $superType) {
                $nodeTypes[] = $superType;
            }
        }
        return $nodeTypes;
    }

    // inherit all doc
    public function storeNode(\PHPCR\NodeInterface $node)
    {
        $path = $node->getPath();
        $this->assertLoggedIn();

        $nodeTypes = $this->getResponsibleNodeTypes($node);
        $popertyDefs = array();
        foreach ($nodeTypes as $nodeType) {
            /* @var $nodeType \PHPCR\NodeType\NodeTypeDefinitionInterface */
            $this->validateNode($node, $nodeType);
        }

        $properties = $node->getProperties();

        $nodeIdentifier = (isset($properties['jcr:uuid'])) ? $properties['jcr:uuid']->getValue() : UUIDHelper::generateUUID();
        $type = isset($properties['jcr:primaryType']) ? $properties['jcr:primaryType']->getValue() : "nt:unstructured";
        $this->syncNode($nodeIdentifier, $path, $this->getParentPath($path), $type, $properties);

        return true;
    }

    // inherit all doc
    public function storeProperty(\PHPCR\PropertyInterface $property)
    {
        $this->assertLoggedIn();

        $node = $property->getParent();
        $this->storeNode($node);
        return true;
    }

    /**
     * Validation if all the data is correct before writing it into the database.
     *
     * @param int $type
     * @param mixed $value
     * @param string $path
     * @throws \PHPCR\ValueFormatException
     * @return void
     */
    private function assertValidPropertyValue($type, $value, $path)
    {
        if ($type === PropertyType::NAME) {
            if (strpos($value, ":") !== false) {
                list($prefix, $localName) = explode(":", $value);

                $this->getNamespaces();
                if (!isset($this->namespaces[$prefix])) {
                    throw new ValueFormatException("Invalid PHPCR NAME at " . $path . ": The namespace prefix " . $prefix . " does not exist.");
                }
            }
        } elseif ($type === PropertyType::PATH) {
            if (!preg_match('((/[a-zA-Z0-9:_-]+)+)', $value)) {
                throw new ValueFormatException("Invalid PATH at " . $path .": Segments are seperated by / and allowed chars are a-zA-Z0-9:_-");
            }
        } elseif ($type === PropertyType::URI) {
            if (!preg_match(self::VALIDATE_URI_RFC3986, $value)) {
                throw new ValueFormatException("Invalid URI at " . $path .": Has to follow RFC 3986.");
            }
        }
    }

    const VALIDATE_URI_RFC3986 = "
/^
([a-z][a-z0-9\*\-\.]*):\/\/
(?:
  (?:(?:[\w\.\-\+!$&'\(\)*\+,;=]|%[0-9a-f]{2})+:)*
  (?:[\w\.\-\+%!$&'\(\)*\+,;=]|%[0-9a-f]{2})+@
)?
(?:
  (?:[a-z0-9\-\.]|%[0-9a-f]{2})+
  |(?:\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\])
)
(?::[0-9]+)?
(?:[\/|\?]
  (?:[\w#!:\.\?\+=&@!$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})
*)?
$/xi";

    // inherit all doc
    public function getNodePathForIdentifier($uuid)
    {
        $this->assertLoggedIn();

        $path = $this->conn->fetchColumn("SELECT path FROM phpcr_nodes WHERE identifier = ? AND workspace_id = ?", array($uuid, $this->workspaceId));
        if (!$path) {
            throw new ItemNotFoundException("no item found with uuid ".$uuid);
        }
        return $path;
    }

    // inherit all doc
    public function getNodeTypes($nodeTypes = array())
    {
        $nodeTypes = array_flip($nodeTypes);

        $data = PHPCR2StandardNodeTypes::getNodeTypeData();
        $filteredData = array();
        foreach ($data as $nodeTypeData) {
            if (isset($nodeTypes[$nodeTypeData['name']])) {
                $filteredData[$nodeTypeData['name']] = $nodeTypeData;
            }
        }

        foreach ($nodeTypes as $type => $val) {
            if (!isset($filteredData[$type]) && $result = $this->fetchUserNodeType($type)) {
                $filteredData[$type] = $result;
            }
        }

        return array_values($filteredData);
    }

    /**
     * Fetch a user-defined node-type definition.
     *
     * @param string $name
     * @return array
     */
    private function fetchUserNodeType($name)
    {
        if ($result = $this->cache->fetch('phpcr_nodetype_' . $name)) {
            return $result;
        }

        $query = "SELECT * FROM phpcr_type_nodes WHERE name = ?";
        $data = $this->conn->fetchAssoc($query, array($name));

        if (!$data) {
            $this->cache->save('phpcr_nodetype_' . $name, false);
            return false;
        }

        $result = array(
            'name' => $data['name'],
            'isAbstract' => (bool)$data['is_abstract'],
            'isMixin' => (bool)($data['is_mixin']),
            'isQueryable' => (bool)$data['is_queryable'],
            'hasOrderableChildNodes' => (bool)$data['orderable_child_nodes'],
            'primaryItemName' => $data['primary_item'],
            'declaredSuperTypeNames' => array_filter(explode(' ', $data['supertypes'])),
            'declaredPropertyDefinitions' => array(),
            'declaredNodeDefinitions' => array(),
        );

        $query = "SELECT * FROM phpcr_type_props WHERE node_type_id = ?";
        $props = $this->conn->fetchAll($query, array($data['node_type_id']));

        foreach ($props as $propertyData) {
            $result['declaredPropertyDefinitions'][] = array(
                'declaringNodeType' => $data['name'],
                'name' => $propertyData['name'],
                'isAutoCreated' => (bool)$propertyData['auto_created'],
                'isMandatory' => (bool)$propertyData['mandatory'],
                'isProtected' => (bool)$propertyData['protected'],
                'onParentVersion' => $propertyData['on_parent_version'],
                'requiredType' => $propertyData['required_type'],
                'multiple' => (bool)$propertyData['multiple'],
                'isFulltextSearchable' => (bool)$propertyData['fulltext_searchable'],
                'isQueryOrderable' => (bool)$propertyData['query_orderable'],
                'queryOperators' => array (
                  0 => 'jcr.operator.equal.to',
                  1 => 'jcr.operator.not.equal.to',
                  2 => 'jcr.operator.greater.than',
                  3 => 'jcr.operator.greater.than.or.equal.to',
                  4 => 'jcr.operator.less.than',
                  5 => 'jcr.operator.less.than.or.equal.to',
                  6 => 'jcr.operator.like',
                ),
                'defaultValue' => array($propertyData['default_value']),
            );
        }

        $query = "SELECT * FROM phpcr_type_childs WHERE node_type_id = ?";
        $childs = $this->conn->fetchAll($query, array($data['node_type_id']));

        foreach ($childs as $childData) {
            $result['declaredNodeDefinitions'][] = array(
                'declaringNodeType' => $data['name'],
                'name' => $childData['name'],
                'isAutoCreated' => (bool)$childData['auto_created'],
                'isMandatory' => (bool)$childData['mandatory'],
                'isProtected' => (bool)$childData['protected'],
                'onParentVersion' => $childData['on_parent_version'],
                'allowsSameNameSiblings' => false,
                'defaultPrimaryTypeName' => $childData['default_type'],
                'requiredPrimaryTypeNames' => array_filter(explode(" ", $childData['primary_types'])),
            );
        }

        $this->cache->save('phpcr_nodetype_' . $name, $result);

        return $result;
    }

    // inherit all doc
    public function registerNodeTypesCnd($cnd, $allowUpdate)
    {
        throw new NotImplementedException("Not implemented yet");
    }

    // inherit all doc
    public function registerNodeTypes($types, $allowUpdate)
    {
        foreach ($types as $type) {
            /* @var $type \Jackalope\NodeType\NodeTypeDefinition */
            $this->conn->insert('phpcr_type_nodes', array(
                'name' => $type->getName(),
                'supertypes' => implode(' ', $type->getDeclaredSuperTypeNames()),
                'is_abstract' => $type->isAbstract() ? 1 : 0,
                'is_mixin' => $type->isMixin() ? 1 : 0,
                'queryable' => $type->isQueryable() ? 1 : 0,
                'orderable_child_nodes' => $type->hasOrderableChildNodes() ? 1 : 0,
                'primary_item' => $type->getPrimaryItemName(),
            ));
            $nodeTypeId = $this->conn->lastInsertId($this->sequenceTypeName);

            if ($propDefs = $type->getDeclaredPropertyDefinitions()) {
                foreach ($propDefs as $propertyDef) {
                    /* @var $propertyDef \Jackalope\NodeType\PropertyDefinition */
                    $this->conn->insert('phpcr_type_props', array(
                        'node_type_id' => $nodeTypeId,
                        'name' => $propertyDef->getName(),
                        'protected' => $propertyDef->isProtected(),
                        'mandatory' => $propertyDef->isMandatory(),
                        'auto_created' => $propertyDef->isAutoCreated(),
                        'on_parent_version' => $propertyDef->getOnParentVersion(),
                        'multiple' => $propertyDef->isMultiple(),
                        'fulltext_searchable' => $propertyDef->isFullTextSearchable(),
                        'query_orderable' => $propertyDef->isQueryOrderable(),
                        'required_type' => $propertyDef->getRequiredType(),
                        'query_operators' => 0, // transform to bitmask
                        'default_value' => $propertyDef->getDefaultValues() ? current($propertyDef->getDefaultValues()) : null,
                    ));
                }
            }

            if ($childDefs = $type->getDeclaredChildNodeDefinitions()) {
                foreach ($childDefs as $childDef) {
                    /* @var $propertyDef \PHPCR\NodeType\NodeDefinitionInterface */
                    $this->conn->insert('phpcr_type_childs', array(
                        'node_type_id' => $nodeTypeId,
                        'name' => $childDef->getName(),
                        'protected' => $childDef->isProtected(),
                        'mandatory' => $childDef->isMandatory(),
                        'auto_created' => $childDef->isAutoCreated(),
                        'on_parent_version' => $childDef->getOnParentVersion(),
                        'primary_types' => implode(' ', $childDef->getRequiredPrimaryTypeNames() ?: array()),
                        'default_type' => $childDef->getDefaultPrimaryTypeName(),
                    ));
                }
            }
        }
    }

    // inherit all doc
    public function setNodeTypeManager($nodeTypeManager)
    {
        $this->nodeTypeManager = $nodeTypeManager;
    }

    // inherit all doc
    public function cloneFrom($srcWorkspace, $srcAbsPath, $destAbsPath, $removeExisting)
    {
        throw new NotImplementedException("Not implemented yet");
    }

    // inherit all doc
    public function getBinaryStream($path)
    {
        $this->assertLoggedIn();

        $nodePath = $this->getParentPath($path);
        $propertyName = ltrim(str_replace($nodePath, "", $path), "/"); // i dont know why trim here :/
        $nodeId = $this->pathExists($nodePath);

        $data = $this->conn->fetchAll(
            'SELECT data, idx FROM phpcr_binarydata WHERE node_id = ? AND property_name = ? AND workspace_id = ?',
            array($nodeId, $propertyName, $this->workspaceId)
        );

        // TODO: Error Handling on the stream?
        if (count($data) == 1) {
            return fopen("data://text/plain,".$data[0]['data'], "r");
        } else {
            $streams = array();
            foreach ($data as $row) {
                $streams[$row['idx']] = fopen("data://text/plain,".$row['data'], "r");
            }
            return $streams;
        }
    }

    // inherit all doc
    public function getProperty($path)
    {
        throw new NotImplementedException("Not implemented yet");
    }

    // inherit all doc
    public function query(\PHPCR\Query\QueryInterface $query)
    {
        $this->assertLoggedIn();

        $limit = $query->getLimit();
        $offset = $query->getOffset();

        $language = $query->getLanguage();
        if ($language === QueryInterface::JCR_SQL2) {
            $parser = new Sql2ToQomQueryConverter($this->factory->get('Jackalope\Query\QOM\QueryObjectModelFactory'));
            try {
                $query = $parser->parse($query->getStatement());
            } catch (\Exception $e) {
                throw new InvalidQueryException('Invalid query: '.$query->getStatement());
            }
            $language = QueryInterface::JCR_JQOM;
        }

        if ($language === QueryInterface::JCR_JQOM) {
            $qomWalker = new Query\QOMWalker($this->nodeTypeManager, $this->conn->getDatabasePlatform(), $this->getNamespaces());
            $sql = $qomWalker->walkQOMQuery($query);

            $sql = $this->conn->getDatabasePlatform()->modifyLimitQuery($sql, $limit, $offset);
            $data = $this->conn->fetchAll($sql, array($this->workspaceId));

            $result = array();
            foreach ($data as $row) {
                $result[] = array(
                    array('dcr:name' => 'jcr:primaryType', 'dcr:value' => $row['type']),
                    array('dcr:name' => 'jcr:path', 'dcr:value' => $row['path'], 'dcr:selectorName' => $row['type']),
                    array('dcr:name' => 'jcr:score', 'dcr:value' => 0)
                );
            }

            return $result;
        }

        throw new NotImplementedException("JCQ-JQOM not yet implemented.");
    }

    // inherit all doc
    public function registerNamespace($prefix, $uri)
    {
        $this->conn->insert('phpcr_namespaces', array(
            'prefix' => $prefix,
            'uri' => $uri,
        ));
    }

    // inherit all doc
    public function unregisterNamespace($prefix)
    {
        $this->conn->delete('phpcr_namespaces', array('prefix' => $prefix));
    }

    // inherit all doc
    public function getReferences($path, $name = null)
    {
        return $this->getNodeReferences($path, $name, false);
    }

    // inherit all doc
    public function getWeakReferences($path, $name = null)
    {
        return $this->getNodeReferences($path, $name, true);
    }

    // inherit all doc
    protected function getNodeReferences($path, $name = null, $weakReference = false)
    {
        $targetId = $this->pathExists($path);

        $type = $weakReference ? PropertyType::WEAKREFERENCE : PropertyType::REFERENCE;

        $sql = "SELECT CONCAT(n.path, '/', fk.source_property_name) as path, fk.source_property_name FROM phpcr_nodes n " .
               "INNER JOIN phpcr_nodes_foreignkeys fk ON n.id = fk.source_id ".
               "WHERE fk.target_id = ? AND fk.type = ?";
        $properties = $this->conn->fetchAll($sql, array($targetId, $type));

        $references = array();
        foreach ($properties as $property) {
            if ($name === null || $property['source_property_name'] == $name) {
                $references[] = $property['path'];
            }
        }
        return $references;
    }

    private function assertValidPath($path)
    {
        if (! (strpos($path, '//') === false
              && strpos($path, '/../') === false
              && preg_match('/^[\w{}\/#:^+~*\[\]\. -]*$/i', $path))
        ) {
            throw new RepositoryException('Path is not well-formed or contains invalid characters: ' . $path);
        }
    }

    /**
     * Checks if the path is absolute and valid, and properly urlencodes special characters
     *
     * This is to be used in the Davex headers. The XML requests can cope with unencoded stuff
     * Not sure if dbal has something to do with Davex but in any case this is a clean utility
     * run at the very beginning of reading node paths
     *
     * @param string $path to check
     *
     * @return string the cleaned path
     *
     * @throws RepositoryException If path is not absolute or invalid
     */
    protected function encodeSanitizePath($path)
    {
        if ('/' != substr($path, 0, 1)) {
            //sanity check
            throw new RepositoryException("Implementation error: '$path' is not an absolute path");
        }
        $this->assertValidPath($path);
        return str_replace(' ', '%20', $path); // TODO: does assertValidPath allow other characters that should be encoded?
    }

    // TODO: remove once transport is split
    public function checkinItem($path)
    {
        throw new NotImplementedException();
    }
    public function checkoutItem($path)
    {
        throw new NotImplementedException();
    }
    public function restoreItem($removeExisting, $versionPath, $path)
    {
        throw new NotImplementedException();
    }
    public function getVersionHistory($path)
    {
        throw new NotImplementedException();
    }


}
