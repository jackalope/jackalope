<?php

/**
 * Class to handle the communication between Jackalope and Jackrabbit via Davex.
 *
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache License Version 2.0, January 2004
 *   Licensed under the Apache License, Version 2.0 (the "License") {}
 *   you may not use this file except in compliance with the License.
 *   You may obtain a copy of the License at
 *
 *       http://www.apache.org/licenses/LICENSE-2.0
 *
 *   Unless required by applicable law or agreed to in writing, software
 *   distributed under the License is distributed on an "AS IS" BASIS,
 *   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *   See the License for the specific language governing permissions and
 *   limitations under the License.
 *
 * @package jackalope
 * @subpackage transport
 */

namespace Jackalope\Transport\DoctrineDBAL;

use PHPCR\PropertyType;
use Jackalope\TransportInterface;
use PHPCR\RepositoryException;
use Doctrine\DBAL\Connection;
use PHPCR\Util\UUIDHelper;
use Jackalope\NodeType\NodeTypeManager;
use Jackalope\NodeType\PHPCR2StandardNodeTypes;
/**
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class Client implements TransportInterface
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
     *
     * @var PHPCR\NodeType\NodeTypeManagerInterface
     */
    private $nodeTypeManager = null;

    /**
     * @var array
     */
    private $userNamespaces = null;

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
    private $validNamespacePrefixes = array(
        \PHPCR\NamespaceRegistryInterface::PREFIX_EMPTY => true,
        \PHPCR\NamespaceRegistryInterface::PREFIX_JCR => true,
        \PHPCR\NamespaceRegistryInterface::PREFIX_NT => true,
        \PHPCR\NamespaceRegistryInterface::PREFIX_MIX => true,
        \PHPCR\NamespaceRegistryInterface::PREFIX_XML => true,
        'phpcr' => true,
    );

    public function __construct($factory, Connection $conn)
    {
        $this->factory = $factory;
        $this->conn = $conn;
    }

    /**
     * @return Doctrine\DBAL\Connection
     */
    public function getConnection()
    {
        return $this->conn;
    }

    /**
     * Creates a new Workspace with the specified name. The new workspace is
     * empty, meaning it contains only root node.
     *
     * If srcWorkspace is given:
     * Creates a new Workspace with the specified name initialized with a
     * clone of the content of the workspace srcWorkspace. Semantically,
     * this method is equivalent to creating a new workspace and manually
     * cloning srcWorkspace to it; however, this method may assist some
     * implementations in optimizing subsequent Node.update and Node.merge
     * calls between the new workspace and its source.
     *
     * The new workspace can be accessed through a login specifying its name.
     *
     * @param string $name A String, the name of the new workspace.
     * @param string $srcWorkspace The name of the workspace from which the new workspace is to be cloned.
     * @return void
     * @throws \PHPCR\AccessDeniedException if the session through which this Workspace object was acquired does not have sufficient access to create the new workspace.
     * @throws \PHPCR\UnsupportedRepositoryOperationException if the repository does not support the creation of workspaces.
     * @throws \PHPCR\NoSuchWorkspaceException if $srcWorkspace does not exist.
     * @throws \PHPCR\RepositoryException if another error occurs.
     * @api
     */
    public function createWorkspace($name, $srcWorkspace = null)
    {
        if (null !== $srcWorkspace) {
            throw new \Jackalope\NotImplementedException();
        }

        $workspaceId = $this->getWorkspaceId($name);
        if ($workspaceId !== false) {
            throw new \PHPCR\RepositoryException("Workspace '" . $name . "' already exists");
        }
        $this->conn->insert('phpcr_workspaces', array('name' => $name));
        $workspaceId = $this->conn->lastInsertId();

        $this->conn->insert("phpcr_nodes", array(
            'path' => '',
            'parent' => '-1',
            'workspace_id' => $workspaceId,
            'identifier' => UUIDHelper::generateUUID(),
            'type' => 'nt:unstructured',
        ));
    }

    /**
     * Set this transport to a specific credential and a workspace.
     *
     * This can only be called once. To connect to another workspace or with
     * another credential, use a fresh instance of transport.
     *
     * @param credentials A \PHPCR\SimpleCredentials instance (this is the only type currently understood)
     * @param workspaceName The workspace name for this transport.
     * @return true on success (exceptions on failure)
     *
     * @throws \PHPCR\LoginException if authentication or authorization (for the specified workspace) fails
     * @throws \PHPCR\NoSuchWorkspacexception if the specified workspaceName is not recognized
     * @throws \PHPCR\RepositoryException if another error occurs
     */
    public function login(\PHPCR\CredentialsInterface $credentials, $workspaceName)
    {
        $this->credentials = $credentials;
        $this->workspaceName = $workspaceName;

        if (!$this->checkLoginOnServer) {
            return true;
        }

        $this->workspaceId = $this->getWorkspaceId($workspaceName);
        if (!$this->workspaceId) {
            // create default workspace if it not exists
            if ($workspaceName === "default") {
                $this->createWorkspace($workspaceName);
                $this->workspaceId = $this->getWorkspaceId($workspaceName);
            }
        }
        
        if (!$this->workspaceId) {
            throw new \PHPCR\NoSuchWorkspaceException;
        }

        $this->loggedIn = true;
        return true;
    }

    /**
     * Releases all resources associated with this Session.
     *
     * This method is called on $session->logout
     * Implementations can use it to close database connections and similar.
     *
     * @return void
     */
    public function logout()
    {
        $this->loggedIn = false;
        $this->conn = null;
    }

    /**
     * Change the way Jackalope works when getting a session
     *
     * @return void
     */
    public function setCheckLoginOnServer($bool)
    {
        $this->checkLoginOnServer = $bool;
    }

    private function getWorkspaceId($workspaceName)
    {
        $sql = "SELECT id FROM phpcr_workspaces WHERE name = ?";
        return $this->conn->fetchColumn($sql, array($workspaceName));
    }

    private function assertLoggedIn()
    {
        if (!$this->loggedIn) {
            if (!$this->checkLoginOnServer && $this->credentials && $this->workspaceName) {
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

    /**
     * Get the repository descriptors from the jackrabbit server
     * This happens without login or accessing a specific workspace.
     *
     * @return Array with name => Value for the descriptors
     * @throws \PHPCR\RepositoryException if error occurs
     */
    public function getRepositoryDescriptors()
    {
        return array(
          'identifier.stability' => \PHPCR\RepositoryInterface::IDENTIFIER_STABILITY_INDEFINITE_DURATION,
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
          'option.transactions.supported' => true,
          'option.unfiled.content.supported' => true,
          'option.update.mixin.node.types.supported' => true,
          'option.update.primary.node.type.supported' => true,
          'option.versioning.supported' => false,
          'option.workspace.management.supported' => true,
          'option.xml.export.supported' => false,
          'option.xml.import.supported' => false,
          'query.full.text.search.supported' => false,
          'query.joins' => false,
          'query.languages' => '',
          'query.stored.queries.supported' => false,
          'query.xpath.doc.order' => false,
          'query.xpath.pos.index' => false,
          'write.supported' => true,
        );
    }

    /**
     * Get the registered namespaces mappings from the backend.
     *
     * @return array Associative array of prefix => uri
     *
     * @throws \PHPCR\RepositoryException if now logged in
     */
    public function getNamespaces()
    {
        if ($this->userNamespaces === null) {
            $data = $this->conn->fetchAll('SELECT * FROM phpcr_namespaces');
            $this->userNamespaces = array();

            foreach ($data AS $row) {
                $this->validNamespacePrefixes[$row['prefix']] = true;
                $this->userNamespaces[$row['prefix']] = $row['uri'];
            }
        }
        return $this->userNamespaces;
    }

    /**
     * Copies a Node from src to dst
     *
     * @param   string  $srcAbsPath     Absolute source path to the node
     * @param   string  $dstAbsPath     Absolute destination path (must include the new node name)
     * @param   string  $srcWorkspace   The source workspace where the node can be found or NULL for current
     * @return void
     *
     * @link http://www.ietf.org/rfc/rfc2518.txt
     * @see \Jackalope\Workspace::copy
     */
    public function copyNode($srcAbsPath, $dstAbsPath, $srcWorkspace = null)
    {
        $this->assertLoggedIn();

        $srcAbsPath = $this->trimPath($srcAbsPath);
        $dstAbsPath = $this->trimPath($dstAbsPath);

        $workspaceId = $this->workspaceId;
        if (null !== $srcWorkspace) {
            $workspaceId = $this->getWorkspaceId($srcWorkspace);
            if ($workspaceId === false) {
                throw new \PHPCR\NoSuchWorkspaceException("Source workspace '" . $srcWorkspace . "' does not exist.");
            }
        }

        if (substr($dstAbsPath, -1, 1) == "]") {
            // TODO: Understand assumptions of CopyMethodsTest::testCopyInvalidDstPath more
            throw new \PHPCR\RepositoryException("Invalid destination path");
        }

        if (!$this->pathExists($srcAbsPath)) {
            throw new \PHPCR\PathNotFoundException("Source path '".$srcAbsPath."' not found");
        }

        if ($this->pathExists($dstAbsPath)) {
            throw new \PHPCR\ItemExistsException("Cannot copy to destination path '" . $dstAbsPath . "' that already exists.");
        }

        if (!$this->pathExists($this->getParentPath($dstAbsPath))) {
            throw new \PHPCR\PathNotFoundException("Parent of the destination path '" . $this->getParentPath($dstAbsPath) . "' has to exist.");
        }

        // Algorithm:
        // 1. Select all nodes with path $srcAbsPath."%" and iterate them
        // 2. create a new node with path $dstAbsPath + leftovers, with a new uuid. Save old => new uuid
        // 3. copy all properties from old node to new node
        // 4. if a reference is in the properties, either update the uuid based on the map if its inside the copied graph or keep it.
        // 5. "May drop mixin types"

        $this->conn->beginTransaction();

        try {

            $sql = "SELECT * FROM phpcr_nodes WHERE path LIKE ? AND workspace_id = ?";
            $stmt = $this->conn->executeQuery($sql, array($srcAbsPath . "%", $workspaceId));

            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $newPath = str_replace($srcAbsPath, $dstAbsPath, $row['path']);
                $uuid = UUIDHelper::generateUUID();
                $this->conn->insert("phpcr_nodes", array(
                    'identifier' => $uuid,
                    'type' => $row['type'],
                    'path' => $newPath,
                    'parent' => $this->getParentPath($newPath),
                    'workspace_id' => $this->workspaceId,
                ));

                $sql = "SELECT * FROM phpcr_props WHERE node_identifier = ?";
                $propStmt = $this->conn->executeQuery($sql, array($row['identifier']));

                while ($propRow = $propStmt->fetch(\PDO::FETCH_ASSOC)) {
                    $propRow['node_identifier'] = $uuid;
                    $propRow['path'] = str_replace($srcAbsPath, $dstAbsPath, $propRow['path']);
                    $this->conn->insert('phpcr_props', $propRow);
                }
            }
            $this->conn->commit();
        } catch (\Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /**
     * Returns the accessible workspace names
     *
     * @return array Set of workspaces to work on.
     */
    public function getAccessibleWorkspaceNames()
    {
        $workspaceNames = array();
        foreach ($this->conn->fetchAll("SELECT name FROM phpcr_workspaces") AS $row) {
            $workspaceNames[] = $row['name'];
        }
        return $workspaceNames;
    }

    /**
     * Get the item from an absolute path
     *
     * TODO: should we call this getNode? does not work for property. (see ObjectManager::getPropertyByPath for more on properties)
     *
     * @param string $path Absolute path to identify a special item.
     * @return array for the node (decoded from json)
     *
     * @throws \PHPCR\RepositoryException if not logged in
     */
    public function getNode($path)
    {
        $this->assertLoggedIn();
        $path = $this->trimPath($path);

        $sql = "SELECT * FROM phpcr_nodes WHERE path = ? AND workspace_id = ?";
        $row = $this->conn->fetchAssoc($sql, array($path, $this->workspaceId));
        if (!$row) {
            throw new \PHPCR\ItemNotFoundException("Item /".$path." not found.");
        }

        $data = new \stdClass();
        // TODO: only return jcr:uuid when this node implements mix:referencable
        $data->{'jcr:uuid'} = $row['identifier'];
        $data->{'jcr:primaryType'} = $row['type'];
        $this->nodeIdentifiers[$path] = $row['identifier'];

        $sql = "SELECT path FROM phpcr_nodes WHERE parent = ? AND workspace_id = ?";
        $children = $this->conn->fetchAll($sql, array($path, $this->workspaceId));

        foreach ($children AS $child) {
            $childName = explode("/", $child['path']);
            $childName = end($childName);
            $data->{$childName} = new \stdClass();
        }

        $sql = "SELECT * FROM phpcr_props WHERE node_identifier = ?";
        $props = $this->conn->fetchAll($sql, array($data->{'jcr:uuid'}));

        foreach ($props AS $prop) {
            $value = null;
            $type = (int)$prop['type'];
            switch ($type) {
                case \PHPCR\PropertyType::NAME:
                case \PHPCR\PropertyType::URI:
                case \PHPCR\PropertyType::WEAKREFERENCE:
                case \PHPCR\PropertyType::REFERENCE:
                case \PHPCR\PropertyType::PATH:
                case \PHPCR\PropertyType::DECIMAL:
                    $value = $prop['string_data'];
                    break;
                case \PHPCR\PropertyType::STRING:
                    $value = $prop['clob_data']; // yah, go figure!
                    break;
                case \PHPCR\PropertyType::BOOLEAN:
                    $value = (bool)$prop['int_data'];
                    break;
                case \PHPCR\PropertyType::LONG:
                    $value = (int)$prop['int_data'];
                    break;
                case \PHPCR\PropertyType::BINARY:
                    $value = (int)$prop['int_data'];
                    break;
                case \PHPCR\PropertyType::DATE:
                    $value = $prop['datetime_data'];
                    break;
                case \PHPCR\PropertyType::DOUBLE:
                    $value = (double)$prop['float_data'];
                    break;
            }

            if ($type == \PHPCR\PropertyType::BINARY) {
                if ($prop['multi_valued'] == 1) {
                    $data->{":" . $prop['name']}[$prop['idx']] = $value;
                } else {
                    $data->{":" . $prop['name']} = $value;
                }
            } else {
                if ($prop['multi_valued'] == 1) {
                    $data->{$prop['name']}[$prop['idx']] = $value;
                } else {
                    $data->{$prop['name']} = $value;
                }
                $data->{":" . $prop['name']} = $type;
            }
        }

        return $data;
    }

    /**
     * Get the nodes from an array of absolute paths
     *
     * @param array $path Absolute paths to the nodes.
     * @return array associative array for the node (decoded from json with associative = true)
     *
     * @throws \PHPCR\RepositoryException if not logged in
     */
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

    /**
     * Check-in item at path.
     *
     * @param string $path
     * @return string
     *
     * @throws PHPCR\UnsupportedRepositoryOperationException
     * @throws PHPCR\RepositoryException
     */
    public function checkinItem($path)
    {
        throw new \Jackalope\NotImplementedException();
    }

    /**
     * Check-out item at path.
     *
     * @param string $path
     * @return void
     *
     * @throws PHPCR\UnsupportedRepositoryOperationException
     * @throws PHPCR\RepositoryException
     */
    public function checkoutItem($path)
    {
        throw new \Jackalope\NotImplementedException();
    }

    public function restoreItem($removeExisting, $versionPath, $path)
    {
        throw new \Jackalope\NotImplementedException();
    }

    public function getVersionHistory($path)
    {
        throw new \Jackalope\NotImplementedException();
    }

    public function querySQL($query, $limit = null, $offset = null)
    {
        throw new \Jackalope\NotImplementedException();
    }

    private function pathExists($path)
    {
        $query = "SELECT identifier FROM phpcr_nodes WHERE path = ? AND workspace_id = ?";
        if (!$this->conn->fetchColumn($query, array($path, $this->workspaceId))) {
            return false;
        }
        return true;
    }

    /**
     * Deletes a node and its subnodes
     *
     * @param string $path Absolute path to identify a special item.
     * @return bool true on success
     *
     * @throws \PHPCR\RepositoryException if not logged in
     */
    public function deleteNode($path)
    {
        $path = $this->trimPath($path);
        $this->assertLoggedIn();

        $match = $path."%";
        $query = "SELECT node_identifier FROM phpcr_props WHERE type = ? AND string_data LIKE ? AND workspace_id = ?";
        if ($ident = $this->conn->fetchColumn($query, array(\PHPCR\PropertyType::REFERENCE, $match, $this->workspaceId))) {
            throw new \PHPCR\ReferentialIntegrityException(
                "Cannot delete item at path '".$path."', there is at least one item (ident ".$ident.") with ".
                "a reference to this or a subnode of the path."
            );
        }

        if (!$this->pathExists($path)) {
            throw new \PHPCR\ItemNotFoundException("No item found at ".$path);
        }

        $this->conn->beginTransaction();

        try {
            $query = "DELETE FROM phpcr_props WHERE path LIKE ? AND workspace_id = ?";
            $this->conn->executeUpdate($query, array($match, $this->workspaceId));

            $query = "DELETE FROM phpcr_nodes WHERE path LIKE ? AND workspace_id = ?";
            $this->conn->executeUpdate($query, array($match, $this->workspaceId));

            $this->conn->commit();

            return true;
        } catch(\Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    /**
     * Deletes a property
     *
     * @param string $path Absolute path to identify a special item.
     * @return bool true on success
     *
     * @throws \PHPCR\RepositoryException if not logged in
     */
    public function deleteProperty($path)
    {
        $path = $this->trimPath($path);
        $this->assertLoggedIn();

        $query = "DELETE FROM phpcr_props WHERE path = ? AND workspace_id = ?";
        $this->conn->executeUpdate($query, array($path, $this->workspaceId));

        return true;
    }

    /**
     * Moves a node from src to dst
     *
     * @param   string  $srcAbsPath     Absolute source path to the node
     * @param   string  $dstAbsPath     Absolute destination path (must NOT include the new node name)
     * @return void
     *
     * @link http://www.ietf.org/rfc/rfc2518.txt
     */
    public function moveNode($srcAbsPath, $dstAbsPath)
    {
        $this->assertLoggedIn();

        throw new \Jackalope\NotImplementedException("Moving nodes is not yet implemented");
    }

    /**
     * Get parent path of a path.
     *
     * @param  string $path
     * @return string
     */
    private function getParentPath($path)
    {
        return implode("/", array_slice(explode("/", $path), 0, -1));
    }

    private function validateNode(\PHPCR\NodeInterface $node, \PHPCR\NodeType\NodeTypeDefinitionInterface $def)
    {
        foreach ($def->getDeclaredChildNodeDefinitions() AS $childDef) {
            /* @var $childDef \PHPCR\NodeType\NodeDefinitionInterface */
            if (!$node->hasNode($childDef->getName())) {
                if ($childDef->isMandatory() && !$childDef->isAutoCreated()) {
                    throw new \PHPCR\RepositoryException(
                        "Child " . $child->getName() . " is mandatory, but is not present while ".
                        "saving " . $def->getName() . " at " . $node->getPath()
                    );
                } else if ($childDef->isAutoCreated()) {

                }

                if ($node->hasProperty($childDef->getName())) {
                    throw new \PHPCR\RepositoryException(
                        "Node " . $node->getPath() . " has property with name ".
                        $childDef->getName() . " but its node type '". $def->getName() . "' defines a ".
                        "child with this name."
                    );
                }
            }
        }

        foreach ($def->getDeclaredPropertyDefinitions() AS $propertyDef) {
            /* @var $propertyDef \PHPCR\NodeType\PropertyDefinitionInterface */
            if ($propertyDef->getName() == '*') {
                continue;
            }

            if (!$node->hasProperty($propertyDef->getName())) {
                if ($propertyDef->isMandatory() && !$propertyDef->isAutoCreated()) {
                    throw new \PHPCR\RepositoryException(
                        "Property " . $propertyDef->getName() . " is mandatory, but is not present while ".
                        "saving " . $def->getName() . " at " . $node->getPath()
                    );
                } else if ($propertyDef->isAutoCreated()) {
                    $defaultValues = $propertyDef->getDefaultValues();
                    $node->setProperty(
                        $propertyDef->getName(),
                        $propertyDef->isMultiple() ? $defaultValues : (isset($defaultValues[0]) ? $defaultValues[0] : null),
                        $propertyDef->getRequiredType()
                    );
                }

                if ($node->hasNode($propertyDef->getName())) {
                    throw new \PHPCR\RepositoryException(
                        "Node " . $node->getPath() . " has child with name ".
                        $propertyDef->getName() . " but its node type '". $def->getName() . "' defines a ".
                        "property with this name."
                    );
                }
            }
        }
    }

    /**
     * Stores a node to the given absolute path
     *
     * @param string $path Absolute path to identify a special item.
     * @param \PHPCR\NodeType\NodeTypeInterface $primaryType
     * @param \Traversable $properties array of \PHPCR\PropertyInterface objects
     * @param \Traversable $children array of \PHPCR\NodeInterface objects
     * @return bool true on success
     *
     * @throws \PHPCR\RepositoryException if not logged in
     */
    public function storeNode(\PHPCR\NodeInterface $node)
    {
        $path = $node->getPath();
        $path = $this->trimPath($path);
        $this->assertLoggedIn();

        // This is very slow i believe :-(
        $nodeDef = $node->getPrimaryNodeType();
        $nodeTypes = $node->getMixinNodeTypes();
        array_unshift($nodeTypes, $nodeDef);
        foreach ($nodeTypes as $nodeType) {
            /* @var $nodeType \PHPCR\NodeType\NodeTypeDefinitionInterface */
            foreach ($nodeType->getDeclaredSupertypes() AS $superType) {
                $nodeTypes[] = $superType;
            }
        }

        $popertyDefs = array();
        foreach ($nodeTypes AS $nodeType) {
            /* @var $nodeType \PHPCR\NodeType\NodeTypeDefinitionInterface */
            foreach ($nodeType->getDeclaredPropertyDefinitions() AS $itemDef) {
                /* @var $itemDef \PHPCR\NodeType\ItemDefinitionInterface */
                if ($itemDef->getName() == '*') {
                    continue;
                }

                if (isset($popertyDefs[$itemDef->getName()])) {
                    throw new \PHPCR\RepositoryException("DoctrineTransport does not support child/property definitions for the same subpath.");
                }
                $popertyDefs[$itemDef->getName()] = $itemDef;
            }
            $this->validateNode($node, $nodeType);
        }

        $properties = $node->getProperties();

        $this->conn->beginTransaction();

        try {
            $nodeIdentifier = (isset($properties['jcr:uuid'])) ? $properties['jcr:uuid']->getNativeValue() : UUIDHelper::generateUUID();
            if (!$this->pathExists($path)) {
                $this->conn->insert("phpcr_nodes", array(
                    'identifier' => $nodeIdentifier,
                    'type' => isset($properties['jcr:primaryType']) ? $properties['jcr:primaryType']->getValue() : "nt:unstructured",
                    'path' => $path,
                    'parent' => $this->getParentPath($path),
                    'workspace_id' => $this->workspaceId,
                ));
            }
            $this->nodeIdentifiers[$path] = $nodeIdentifier;

            foreach ($properties AS $property) {
                $this->doStoreProperty($property, $popertyDefs);
            }
            $this->conn->commit();
        } catch(\Exception $e) {
            $this->conn->rollBack();
            throw new \PHPCR\RepositoryException("Storing node " . $node->getPath() . " failed: " . $e->getMessage(), null, $e);
        }

        return true;
    }

    /**
     * Stores a property to the given absolute path
     *
     * @param string $path Absolute path to identify a specific property.
     * @param \PHPCR\PropertyInterface
     * @return bool true on success
     *
     * @throws \PHPCR\RepositoryException if not logged in
     */
    public function storeProperty(\PHPCR\PropertyInterface $property)
    {
        return $this->doStoreProperty($property, array());
    }

    /**
     * @param \PHPCR\PropertyInterface $property
     * @param array $propDefinitions
     * @return bool
     */
    private function doStoreProperty(\PHPCR\PropertyInterface $property, $propDefinitions = array())
    {
        $path = $property->getPath();
        $path = $this->trimPath($path);
        $name = explode("/", $path);
        $name = end($name);
        // TODO: Upsert
        /* @var $property \PHPCR\PropertyInterface */
        $idx = 0;

        if ($name == "jcr:uuid" || $name == "jcr:primaryType") {
            return;
        }

        if (!$property->isModified() && !$property->isNew()) {
            return;
        }

        $this->assertLoggedIn();

        if (($property->getType() == PropertyType::REFERENCE || $property->getType() == PropertyType::WEAKREFERENCE) &&
            !$property->getNode()->isNodeType('mix:referenceable')) {
            throw new \PHPCR\ValueFormatException('Node ' . $property->getNode()->getPath() . ' is not referencable');
        }

        $this->conn->delete('phpcr_props', array(
            'path' => $path,
            'workspace_id' => $this->workspaceId,
        ));

        $isMultiple = $property->isMultiple();
        if (isset($propDefinitions[$name])) {
            /* @var $propertyDef \PHPCR\NodeType\PropertyDefinitionInterface */
            $propertyDef = $propDefinitions[$name];
            if ($propertyDef->isMultiple() && !$isMultiple) {
                $isMultiple = true;
            } else if (!$propertyDef->isMultiple() && $isMultiple) {
                throw new \PHPCR\ValueFormatException(
                    'Cannot store property ' . $property->getPath() . ' as array, '.
                    'property definition of nodetype ' . $propertyDef->getDeclaringNodeType()->getName() .
                    ' requests a single value.'
                );
            }

            if ($propertyDef !== \PHPCR\PropertyType::UNDEFINED) {
                // TODO: Is this the correct way? No side effects while initializtion?
                $property->setValue($property->getValue(), $propertyDef->getRequiredType());
            }

            foreach ($propertyDef->getValueConstraints() AS $valueConstraint) {
                // TODO: Validate constraints
            }
        }

        $data = array(
            'path'              => $path,
            'workspace_id'      => $this->workspaceId,
            'name'              => $name,
            'idx'               => 0,
            'multi_valued'      => $isMultiple ? 1 : 0,
            'node_identifier'   => $this->nodeIdentifiers[$this->trimPath($property->getParent()->getPath(), '/')]
        );
        $data['type'] = $property->getType();

        $binaryData = null;
        switch ($data['type']) {
            case \PHPCR\PropertyType::NAME:
            case \PHPCR\PropertyType::URI:
            case \PHPCR\PropertyType::WEAKREFERENCE:
            case \PHPCR\PropertyType::REFERENCE:
            case \PHPCR\PropertyType::PATH:
                $dataFieldName = 'string_data';
                $values = $property->getString();
                break;
            case \PHPCR\PropertyType::DECIMAL:
                $dataFieldName = 'string_data';
                $values = $property->getDecimal();
                break;
            case \PHPCR\PropertyType::STRING:
                $dataFieldName = 'clob_data';
                $values = $property->getString();
                break;
            case \PHPCR\PropertyType::BOOLEAN:
                $dataFieldName = 'int_data';
                $values = $property->getBoolean() ? 1 : 0;
                break;
            case \PHPCR\PropertyType::LONG:
                $dataFieldName = 'int_data';
                $values = $property->getLong();
                break;
            case \PHPCR\PropertyType::BINARY:
                $dataFieldName = 'int_data';
                if ($property->isMultiple()) {
                    foreach ((array)$property->getBinary() AS $binary) {
                        $binary = stream_get_contents($binary);
                        $binaryData[] = $binary;
                        $values[] = strlen($binary);
                    }
                } else {
                    $binary = stream_get_contents($property->getBinary());
                    $binaryData[] = $binary;
                    $values = strlen($binary);
                }
                break;
            case \PHPCR\PropertyType::DATE:
                $dataFieldName = 'datetime_data';
                $date = $property->getDate() ?: new \DateTime("now");
                $values = $date->format($this->conn->getDatabasePlatform()->getDateTimeFormatString());
                break;
            case \PHPCR\PropertyType::DOUBLE:
                $dataFieldName = 'float_data';
                $values = $property->getDouble();
                break;
        }

        if ($isMultiple) {
            foreach ((array)$values AS $value) {
                $this->assertValidPropertyValue($data['type'], $value, $path);

                $data[$dataFieldName] = $value;
                $data['idx'] = $idx++;
                $this->conn->insert('phpcr_props', $data);
            }
        } else {
            $this->assertValidPropertyValue($data['type'], $values, $path);

            $data[$dataFieldName] = $values;
            $this->conn->insert('phpcr_props', $data);
        }

        if ($binaryData) {
            foreach ($binaryData AS $idx => $data)
            $this->conn->insert('phpcr_binarydata', array(
                'path'          => $path,
                'workspace_id'  => $this->workspaceId,
                'idx'           => $idx,
                'data'          => $data,
            ));
        }
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
        if ($type === \PHPCR\PropertyType::NAME) {
            if (strpos($value, ":") !== false) {
                list($prefix, $localName) = explode(":", $value);

                $this->getNamespaces();
                if (!isset($this->validNamespacePrefixes[$prefix])) {
                    throw new \PHPCR\ValueFormatException("Invalid PHPCR NAME at " . $path . ": The namespace prefix " . $prefix . " does not exist.");
                }
            }
        } else if ($type === \PHPCR\PropertyType::PATH) {
            if (!preg_match('((/[a-zA-Z0-9:_-]+)+)', $value)) {
                throw new \PHPCR\ValueFormatException("Invalid PATH at " . $path .": Segments are seperated by / and allowed chars are a-zA-Z0-9:_-");
            }
        } else if ($type === \PHPCR\PropertyType::URI) {
            if (!preg_match(self::VALIDATE_URI_RFC3986, $value)) {
                throw new \PHPCR\ValueFormatException("Invalid URI at " . $path .": Has to follow RFC 3986.");
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

    /**
     * Get the node path from a PHPCR uuid
     *
     * @param string $uuid the id in PHPCR format
     * @return string Absolute path to the node
     *
     * @throws \PHPCR\ItemNotFoundException if the backend does not know the uuid
     * @throws \PHPCR\RepositoryException if not logged in
     */
    public function getNodePathForIdentifier($uuid)
    {
        $this->assertLoggedIn();

        $path = $this->conn->fetchColumn("SELECT path FROM phpcr_nodes WHERE identifier = ? AND workspace_id = ?", array($uuid, $this->workspaceId));
        if (!$path) {
            throw new \PHPCR\ItemNotFoundException("no item found with uuid ".$uuid);
        }
        return "/" . $path;
    }

    /**
     * Returns node types
     * @param array nodetypes to request
     * @return dom with the definitions
     * @throws \PHPCR\RepositoryException if not logged in
     */
    public function getNodeTypes($nodeTypes = array())
    {
        // TODO: Filter for the passed nodetypes
        // TODO: Check database for user node-types.

        return PHPCR2StandardNodeTypes::getNodeTypeData();
    }

    /**
     * Register namespaces and new node types or update node types based on a
     * jackrabbit cnd string
     *
     * @see \Jackalope\NodeTypeManager::registerNodeTypesCnd
     *
     * @param $cnd The cnd string
     * @param boolean $allowUpdate whether to fail if node already exists or to update it
     * @return bool true on success
     */
    public function registerNodeTypesCnd($cnd, $allowUpdate)
    {
        throw new \Jackalope\NotImplementedException("Not implemented yet");
    }

    /**
     * @param array $types a list of \PHPCR\NodeType\NodeTypeDefinitionInterface objects
     * @param boolean $allowUpdate whether to fail if node already exists or to update it
     * @return bool true on success
     */
    public function registerNodeTypes($types, $allowUpdate)
    {
        throw new \Jackalope\NotImplementedException("Not implemented yet");
    }

    public function setNodeTypeManager($nodeTypeManager)
    {
        $this->nodeTypeManager = $nodeTypeManager;
    }

    public function cloneFrom($srcWorkspace, $srcAbsPath, $destAbsPath, $removeExisting)
    {
        throw new \Jackalope\NotImplementedException("Not implemented yet");
    }

    public function getBinaryStream($path)
    {
        $this->assertLoggedIn();

        $data = $this->conn->fetchAll(
            'SELECT data, idx FROM phpcr_binarydata WHERE path = ? AND workspace_id = ?',
            array($this->trimPath($path, "/"), $this->workspaceId)
        );

        // TODO: Error Handling on the stream?
        if (count($data) == 1) {
            return fopen("data://text/plain,".$data[0]['data'], "r");
        } else {
            $streams = array();
            foreach ($data AS $row) {
                $streams[$row['idx']] = fopen("data://text/plain,".$row['data'], "r");
            }
            return $streams;
        }
    }

    public function getProperty($path)
    {
        throw new \Jackalope\NotImplementedException("Not implemented yet");
    }

    public function query(\PHPCR\Query\QueryInterface $query)
    {
        switch ($query->getLanguage()) {
            case \PHPCR\Query\QueryInterface::JCR_SQL2:
                $parser = new Query\SQL2Parser($query);
                $parser->parse();
                
                throw new \Jackalope\NotImplementedException("JCQ-SQL cannot hydrate yet.");
                break;
            case \PHPCR\Query\QueryInterface::JCR_JQOM:
                throw new \Jackalope\NotImplementedException("JCQ-JQOM not yet implemented.");
                break;
        }
    }

    public function registerNamespace($prefix, $uri)
    {
        $this->conn->insert('phpcr_namespaces', array(
            'prefix' => $prefix,
            'uri' => $uri,
        ));
    }

    public function unregisterNamespace($prefix)
    {
        $this->conn->delete('phpcr_namespaces', array('prefix' => $prefix));
    }

    /**
     * Returns the path of all accessible REFERENCE properties in the workspace that point to the node
     *
     * @param string $path
     * @param string $name name of referring REFERENCE properties to be returned; if null then all referring REFERENCEs are returned
     * @return array
     */
    public function getReferences($path, $name = null)
    {
        return $this->getNodeReferences($path, $name, false);
    }

    /**
     * Returns the path of all accessible WEAKREFERENCE properties in the workspace that point to the node
     *
     * @param string $path
     * @param string $name name of referring WEAKREFERENCE properties to be returned; if null then all referring WEAKREFERENCEs are returned
     * @return array
     */
    public function getWeakReferences($path, $name = null)
    {
        return $this->getNodeReferences($path, $name, true);
    }

    /**
     * Returns the path of all accessible reference properties in the workspace that point to the node.
     * If $weak_reference is false (default) only the REFERENCE properties are returned, if it is true, only WEAKREFERENCEs.
     * @param string $path
     * @param string $name name of referring WEAKREFERENCE properties to be returned; if null then all referring WEAKREFERENCEs are returned
     * @param boolean $weak_reference If true return only WEAKREFERENCEs, otherwise only REFERENCEs
     * @return array
     */
    protected function getNodeReferences($path, $name = null, $weak_reference = false)
    {
        $path = $this->trimPath($path);
        $type = $weak_reference ? \PHPCR\PropertyType::WEAKREFERENCE : \PHPCR\PropertyType::REFERENCE;

        $sql = "SELECT p.path, p.name FROM phpcr_props p " .
               "INNER JOIN phpcr_nodes r ON r.identifier = p.string_data AND p.workspace_id = ? AND r.workspace_id = ?" .
               "WHERE r.path = ? AND p.type = ?";
        $properties = $this->conn->fetchAll($sql, array($this->workspaceId, $this->workspaceId, $path, $type));

        $references = array();
        foreach ($properties AS $property) {
            if ($name === null || $property['name'] == $name) {
                $references[] = "/" . $property['path'];
            }
        }
        return $references;
    }


    /**
     * Return the permissions of the current session on the node given by path.
     * The result of this function is an array of zero, one or more strings from add_node, read, remove, set_property.
     *
     * @param string $path the path to the node we want to check
     * @return array of string
     */
    public function getPermissions($path)
    {
        return array(
            \PHPCR\SessionInterface::ACTION_ADD_NODE,
            \PHPCR\SessionInterface::ACTION_READ,
            \PHPCR\SessionInterface::ACTION_REMOVE,
            \PHPCR\SessionInterface::ACTION_SET_PROPERTY);
    }

    protected function trimPath($path)
    {
        $this->ensureValidPath($path);

        return ltrim($path, "/");
    }

    protected function ensureValidPath($path)
    {
        if (! (strpos($path, '//') === false
              && strpos($path, '/../') === false
              && preg_match('/^[\w{}\/#:^+~*\[\]\. -]*$/i', $path))
        ) {
            throw new \PHPCR\RepositoryException('Path is not well-formed or contains invalid characters: ' . $path);
        }
    }
}
