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

namespace Jackalope\Transport;

use PHPCR\PropertyType;
use Jackalope\TransportInterface;
use PHPCR\RepositoryException;
use Doctrine\DBAL\Connection;
use Jackalope\Helper;
use Jackalope\NodeType\NodeTypeManager;

/**
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class DoctrineDBAL implements TransportInterface
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
     * @var int|string
     */
    private $workspaceId;

    /**
     * @var array
     */
    private $nodeTypes = array(
        "nt:file" => array(
            "is_abstract" => false,
            "properties" => array(
                "jcr:primaryType" => array('multi_valued' => false),
                "jcr:mixinTypes" => array('multi_valued' => true),
            ),
        ),
        "nt:folder" => array(
            "is_abstract" => false,
            "properties" => array(
                "jcr:primaryType" => array('multi_valued' => false),
                "jcr:mixinTypes" => array('multi_valued' => true),
            ),
        ),
    );

    /**
     * @var array
     */
    private $nodeIdentifiers = array();

    private $nodeTypeManager = null;

    public function __construct(Connection $conn)
    {
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
     * Create a new workspace.
     *
     * @param string $workspaceName
     * @return void
     */
    public function createWorkspace($workspaceName)
    {
        $workspaceId = $this->getWorkspaceId($workspaceName);
        if ($workspaceId !== false) {
            throw new \PHPCR\RepositoryException("Workspace '" . $workspaceName . "' already exists");
        }
        $this->conn->insert('jcrworkspaces', array('name' => $workspaceName));
        $workspaceId = $this->conn->lastInsertId();

        $this->conn->insert("jcrnodes", array(
            'path' => '',
            'parent' => '-1',
            'workspace_id' => $workspaceId,
            'identifier' => Helper::generateUUID(),
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
        $this->workspaceId = $this->getWorkspaceId($workspaceName);
        if (!$this->workspaceId) {
            throw new \PHPCR\NoSuchWorkspaceException;
        }

        $this->loggedIn = true;
        return true;
    }

    private function getWorkspaceId($workspaceName)
    {
        $sql = "SELECT id FROM jcrworkspaces WHERE name = ?";
        return $this->conn->fetchColumn($sql, array($workspaceName));
    }

    private function assertLoggedIn()
    {
        if (!$this->loggedIn) {
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
        $this->assertLoggedIn();

        return array();
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
        return array();
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
        
        $srcAbsPath = ltrim($srcAbsPath, '/');
        $dstAbsPath = ltrim($dstAbsPath, '/');

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

            $sql = "SELECT * FROM jcrnodes WHERE path LIKE ? AND workspace_id = ?";
            $stmt = $this->conn->executeQuery($sql, array($srcAbsPath . "%", $workspaceId));

            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $newPath = str_replace($srcAbsPath, $dstAbsPath, $row['path']);
                $uuid = Helper::generateUUID();
                $this->conn->insert("jcrnodes", array(
                    'identifier' => $uuid,
                    'type' => $row['type'],
                    'path' => $newPath,
                    'parent' => $this->getParentPath($newPath),
                    'workspace_id' => $this->workspaceId,
                ));

                $sql = "SELECT * FROM jcrprops WHERE node_identifier = ?";
                $propStmt = $this->conn->executeQuery($sql, array($row['identifier']));

                while ($propRow = $propStmt->fetch(\PDO::FETCH_ASSOC)) {
                    $propRow['node_identifier'] = $uuid;
                    $propRow['path'] = str_replace($srcAbsPath, $dstAbsPath, $propRow['path']);
                    $this->conn->insert('jcrprops', $propRow);
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
        foreach ($this->conn->fetchAll("SELECT name FROM jcrworkspaces") AS $row) {
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
        $path = ltrim($path, "/");

        $sql = "SELECT * FROM jcrnodes WHERE path = ? AND workspace_id = ?";
        $row = $this->conn->fetchAssoc($sql, array($path, $this->workspaceId));
        if (!$row) {
            throw new \PHPCR\ItemNotFoundException("Item /".$path." not found.");
        }

        $data = new \stdClass();
        $data->{'jcr:uuid'} = $row['identifier'];
        $data->{'jcr:primaryType'} = $row['type'];
        $this->nodeIdentifiers[$path] = $row['identifier'];

        $sql = "SELECT path FROM jcrnodes WHERE parent = ? AND workspace_id = ?";
        $children = $this->conn->fetchAll($sql, array($path, $this->workspaceId));

        foreach ($children AS $child) {
            $childName = explode("/", $child['path']);
            $childName = end($childName);
            $data->{$childName} = new \stdClass();
        }

        $sql = "SELECT * FROM jcrprops WHERE node_identifier = ?";
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
                $data->{":" . $prop['name']} = $value;
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
        $query = "SELECT identifier FROM jcrnodes WHERE path = ? AND workspace_id = ?";
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
        $path = ltrim($path, '/');
        $this->assertLoggedIn();

        $match = $path."%";
        $query = "SELECT node_identifier FROM jcrprops WHERE type = ? AND string_data LIKE ? AND workspace_id = ?";
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
            $query = "DELETE FROM jcrprops WHERE path LIKE ? AND workspace_id = ?";
            $this->conn->executeUpdate($query, array($match, $this->workspaceId));

            $query = "DELETE FROM jcrnodes WHERE path LIKE ? AND workspace_id = ?";
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
        $path = ltrim($path, '/');
        $this->assertLoggedIn();

        $query = "DELETE FROM jcrprops WHERE path = ? AND workspace_id = ?";
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
        $path = ltrim($path, "/");
        $this->assertLoggedIn();

        $properties = $node->getProperties();

        $nodeIdentifier = (isset($properties['jcr:uuid'])) ? $properties['jcr:uuid']->getNativeValue() : Helper::generateUUID();
        if (!$this->pathExists($path)) {
            $this->conn->insert("jcrnodes", array(
                'identifier' => $nodeIdentifier,
                'type' => isset($properties['jcr:primaryType']) ? $properties['jcr:primaryType']->getValue() : "nt:unstructured",
                'path' => $path,
                'parent' => $this->getParentPath($path),
                'workspace_id' => $this->workspaceId,
            ));
        }
        $this->nodeIdentifiers[$path] = $nodeIdentifier;

        foreach ($properties AS $property) {
            $this->storeProperty($property);
        }
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
        $path = $property->getPath();
        $path = ltrim($path, '/');
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

        $this->conn->delete('jcrprops', array(
            'path' => $path,
            'workspace_id' => $this->workspaceId,
        ));
        
        $data = array(
            'path' => $path,
            'workspace_id' => $this->workspaceId,
            'name' => $name,
            'idx' => 0,
            'multi_valued' => $property->isMultiple() ? 1 : 0,
            'node_identifier' => $this->nodeIdentifiers[ltrim($property->getParent()->getPath(), '/')]
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
                $binaryData = stream_get_contents($property->getBinary());
                $values = strlen($binaryData);
                break;
            case \PHPCR\PropertyType::DATE:
                $dataFieldName = 'datetime_data';
                $values = $property->getDate()->format($this->conn->getDatabasePlatform()->getDateTimeFormatString());
                break;
            case \PHPCR\PropertyType::DOUBLE:
                $dataFieldName = 'float_data';
                $values = $property->getDouble();
                break;
        }

        if ($property->isMultiple()) {
            foreach ($values AS $value) {
                $data[$dataFieldName] = $value;
                $data['idx'] = $idx++;
                $this->conn->insert('jcrprops', $data);
            }
        } else {
            $data[$dataFieldName] = $values;
            $this->conn->insert('jcrprops', $data);
        }

        if ($binaryData) {
            $this->conn->insert('jcrbinarydata', array(
                'path' => $path,
                'workspace_id' => $this->workspaceId,
                'data' => $binaryData,
            ));
        }
    }

    /**
     * Get the node path from a JCR uuid
     *
     * @param string $uuid the id in JCR format
     * @return string Absolute path to the node
     *
     * @throws \PHPCR\ItemNotFoundException if the backend does not know the uuid
     * @throws \PHPCR\RepositoryException if not logged in
     */
    public function getNodePathForIdentifier($uuid)
    {
        $this->assertLoggedIn();

        $path = $this->conn->fetchColumn("SELECT path FROM jcrnodes WHERE identifier = ? AND workspace_id = ?", array($uuid, $this->workspaceId));
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
        return array(
            0 =>
            array(
                'name' => 'nt:base',
                'isAbstract' => true,
                'isMixin' => false,
                'isQueryable' => true,
                'hasOrderableChildNodes' => true,
                'primaryItemName' => NULL,
                'declaredSuperTypeNames' =>
                array(
                ),
                'declaredPropertyDefinitions' =>
                array(
                    0 =>
                    array(
                        'declaringNodeType' => '',
                        'name' => 'jcr:primaryType',
                        'isAutoCreated' => true,
                        'isMandatory' => true,
                        'isProtected' => true,
                        'onParentVersion' => 4,
                        'requiredType' => 7,
                        'multiple' => true,
                        'fullTextSearchable' => true,
                        'queryOrderable' => true,
                    ),
                    1 =>
                    array(
                        'declaringNodeType' => '',
                        'name' => 'jcr:mixinTypes',
                        'isAutoCreated' => true,
                        'isMandatory' => true,
                        'isProtected' => true,
                        'onParentVersion' => 4,
                        'requiredType' => 7,
                        'multiple' => true,
                        'fullTextSearchable' => true,
                        'queryOrderable' => true,
                    ),
                ),
                'declaredNodeDefinitions' =>
                array(
                ),
            ),
            1 =>
            array(
                'name' => 'nt:unstructured',
                'isAbstract' => false,
                'isMixin' => false,
                'isQueryable' => true,
                'hasOrderableChildNodes' => true,
                'primaryItemName' => NULL,
                'declaredSuperTypeNames' =>
                array(
                    0 => 'nt:base',
                ),
                'declaredPropertyDefinitions' =>
                array(
                    0 =>
                    array(
                        'declaringNodeType' => 'nt:unstructured',
                        'name' => '*',
                        'isAutoCreated' => true,
                        'isMandatory' => false,
                        'isProtected' => true,
                        'onParentVersion' => 1,
                        'requiredType' => 0,
                        'multiple' => true,
                        'fullTextSearchable' => true,
                        'queryOrderable' => true,
                    ),
                ),
                'declaredNodeDefinitions' =>
                array(
                    0 =>
                    array(
                        'declaringNodeType' => 'nt:unstructured',
                        'name' => '*',
                        'isAutoCreated' => true,
                        'isMandatory' => false,
                        'isProtected' => true,
                        'onParentVersion' => 2,
                        'allowsSameNameSiblings' => false,
                        'defaultPrimaryTypeName' => 'nt:unstructured',
                        'requiredPrimaryTypeNames' =>
                        array(
                            0 => 'nt:base',
                        ),
                    ),
                ),
            ),
            2 =>
            array(
                'name' => 'mix:etag',
                'isAbstract' => true,
                'isMixin' => true,
                'isQueryable' => true,
                'hasOrderableChildNodes' => true,
                'primaryItemName' => NULL,
                'declaredSuperTypeNames' =>
                array(
                ),
                'declaredPropertyDefinitions' =>
                array(
                    0 =>
                    array(
                        'declaringNodeType' => '',
                        'name' => 'jcr:etag',
                        'isAutoCreated' => true,
                        'isMandatory' => true,
                        'isProtected' => true,
                        'onParentVersion' => 4,
                        'requiredType' => 1,
                        'multiple' => true,
                        'fullTextSearchable' => true,
                        'queryOrderable' => true,
                    ),
                ),
                'declaredNodeDefinitions' =>
                array(
                ),
            ),
            3 =>
            array(
                'name' => 'nt:hierachy',
                'isAbstract' => true,
                'isMixin' => true,
                'isQueryable' => true,
                'hasOrderableChildNodes' => true,
                'primaryItemName' => NULL,
                'declaredSuperTypeNames' =>
                array(
                    0 => 'mix:created',
                ),
                'declaredPropertyDefinitions' =>
                array(
                ),
                'declaredNodeDefinitions' =>
                array(
                ),
            ),
            4 =>
            array(
                'name' => 'nt:file',
                'isAbstract' => false,
                'isMixin' => false,
                'isQueryable' => true,
                'hasOrderableChildNodes' => true,
                'primaryItemName' => NULL,
                'declaredSuperTypeNames' =>
                array(
                    0 => 'nt:hierachy',
                ),
                'declaredPropertyDefinitions' =>
                array(
                ),
                'declaredNodeDefinitions' =>
                array(
                ),
            ),
            5 =>
            array(
                'name' => 'nt:folder',
                'isAbstract' => false,
                'isMixin' => false,
                'isQueryable' => true,
                'hasOrderableChildNodes' => true,
                'primaryItemName' => NULL,
                'declaredSuperTypeNames' =>
                array(
                    0 => 'nt:hierachy',
                ),
                'declaredPropertyDefinitions' =>
                array(
                ),
                'declaredNodeDefinitions' =>
                array(
                ),
            ),
            6 =>
            array(
                'name' => 'nt:resource',
                'isAbstract' => false,
                'isMixin' => false,
                'isQueryable' => true,
                'hasOrderableChildNodes' => true,
                'primaryItemName' => 'jcr:data',
                'declaredSuperTypeNames' =>
                array(
                    0 => 'mix:mimeType',
                    1 => 'mix:modified',
                ),
                'declaredPropertyDefinitions' =>
                array(
                    0 =>
                    array(
                        'declaringNodeType' => '',
                        'name' => 'jcr:created',
                        'isAutoCreated' => true,
                        'isMandatory' => true,
                        'isProtected' => true,
                        'onParentVersion' => 1,
                        'requiredType' => 2,
                        'multiple' => true,
                        'fullTextSearchable' => true,
                        'queryOrderable' => true,
                    ),
                ),
                'declaredNodeDefinitions' =>
                array(
                ),
            ),
            7 =>
            array(
                'name' => 'mix:created',
                'isAbstract' => true,
                'isMixin' => true,
                'isQueryable' => true,
                'hasOrderableChildNodes' => true,
                'primaryItemName' => NULL,
                'declaredSuperTypeNames' =>
                array(
                ),
                'declaredPropertyDefinitions' =>
                array(
                    0 =>
                    array(
                        'declaringNodeType' => '',
                        'name' => 'jcr:created',
                        'isAutoCreated' => true,
                        'isMandatory' => true,
                        'isProtected' => true,
                        'onParentVersion' => 4,
                        'requiredType' => 5,
                        'multiple' => true,
                        'fullTextSearchable' => true,
                        'queryOrderable' => true,
                    ),
                    1 =>
                    array(
                        'declaringNodeType' => '',
                        'name' => 'jcr:createdBy',
                        'isAutoCreated' => true,
                        'isMandatory' => true,
                        'isProtected' => true,
                        'onParentVersion' => 4,
                        'requiredType' => 1,
                        'multiple' => true,
                        'fullTextSearchable' => true,
                        'queryOrderable' => true,
                    ),
                ),
                'declaredNodeDefinitions' =>
                array(
                ),
            ),
            8 =>
            array(
                'name' => 'mix:mimeType',
                'isAbstract' => true,
                'isMixin' => true,
                'isQueryable' => true,
                'hasOrderableChildNodes' => true,
                'primaryItemName' => NULL,
                'declaredSuperTypeNames' =>
                array(
                ),
                'declaredPropertyDefinitions' =>
                array(
                    0 =>
                    array(
                        'declaringNodeType' => '',
                        'name' => 'jcr:mimeType',
                        'isAutoCreated' => true,
                        'isMandatory' => true,
                        'isProtected' => true,
                        'onParentVersion' => 1,
                        'requiredType' => 5,
                        'multiple' => true,
                        'fullTextSearchable' => true,
                        'queryOrderable' => true,
                    ),
                    1 => array(
                        'declaringNodeType' => '',
                        'name' => 'jcr:encoding',
                        'isAutoCreated' => true,
                        'isMandatory' => true,
                        'isProtected' => true,
                        'onParentVersion' => 1,
                        'requiredType' => 1,
                        'multiple' => true,
                        'fullTextSearchable' => true,
                        'queryOrderable' => true,
                    ),
                ),
                'declaredNodeDefinitions' =>
                array(
                ),
            ),
            9 =>
            array(
                'name' => 'mix:lastModified',
                'isAbstract' => true,
                'isMixin' => true,
                'isQueryable' => true,
                'hasOrderableChildNodes' => true,
                'primaryItemName' => NULL,
                'declaredSuperTypeNames' =>
                array(
                ),
                'declaredPropertyDefinitions' =>
                array(
                    0 =>
                    array(
                        'declaringNodeType' => '',
                        'name' => 'jcr:lastModified',
                        'isAutoCreated' => true,
                        'isMandatory' => true,
                        'isProtected' => true,
                        'onParentVersion' => 4,
                        'requiredType' => 5,
                        'multiple' => true,
                        'fullTextSearchable' => true,
                        'queryOrderable' => true,
                    ),
                    1 =>
                    array(
                        'declaringNodeType' => '',
                        'name' => 'jcr:lastModifiedBy',
                        'isAutoCreated' => true,
                        'isMandatory' => true,
                        'isProtected' => true,
                        'onParentVersion' => 4,
                        'requiredType' => 1,
                        'multiple' => true,
                        'fullTextSearchable' => true,
                        'queryOrderable' => true,
                    ),
                ),
                'declaredNodeDefinitions' =>
                array(
                ),
            ),
        );
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

    }

    /**
     * @param array $types a list of \PHPCR\NodeType\NodeTypeDefinitionInterface objects
     * @param boolean $allowUpdate whether to fail if node already exists or to update it
     * @return bool true on success
     */
    public function registerNodeTypes($types, $allowUpdate)
    {
        
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

        $data = $this->conn->fetchColumn('SELECT data FROM jcrbinarydata WHERE path = ?', array($path));
        // TODO: Error Handling
        return fopen("data://text/plain,".$data, "r");
    }

    public function getProperty($path)
    {
        throw new \Jackalope\NotImplementedException("Not implemented yet");
    }

    public function query(\PHPCR\Query\QueryInterface $query)
    {
            throw new \Jackalope\NotImplementedException("Not implemented yet");
    }

    public function registerNamespace($prefix, $uri)
    {
        throw new \Jackalope\NotImplementedException("Not implemented yet");
    }

    public function unregisterNamespace($prefix)
    {

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
        throw new \Jackalope\NotImplementedException("Not implemented yet");
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
        throw new \Jackalope\NotImplementedException("Not implemented yet");
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
        throw new \Jackalope\NotImplementedException("Not implemented yet");
    }
}