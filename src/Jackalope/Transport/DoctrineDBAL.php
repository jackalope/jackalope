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

use Jackalope\TransportInterface;
use PHPCR\RepositoryException;
use Doctrine\DBAL\Connection;

class DoctrineDBAL implements TransportInterface
{
    private $conn;
    private $loggedIn = false;
    private $workspaceId;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
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
        $sql = "SELECT id FROM jcrworkspaces WHERE name = ?";
        $this->workspaceId = $this->conn->fetchColumn($sql, array($workspaceName));
        if (!$this->workspaceId) {
            throw new \PHPCR\NoSuchWorkspaceException;
        }

        $this->loggedIn = true;
        return true;
    }

    private function assertLoggedIn()
    {
        if (!$this->loggedIn) {
            throw RepositoryException();
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

    }

    /**
     * Returns the accessible workspace names
     *
     * @return array Set of workspaces to work on.
     */
    public function getAccessibleWorkspaceNames()
    {

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
    public function getItem($path)
    {
        $this->assertLoggedIn();
        $path = ltrim($path, "/");

        $sql = "SELECT * FROM jcrnodes WHERE path = ? AND workspace_id = ?";
        $row = $this->conn->fetchAssoc($sql, array($path, $this->workspaceId));
        if (!$row) {
            throw new \PHPCR\ItemNotFoundException("Item ".$path." not found.");
        }

        $data = array(
            'jcr:uuid' => $row['identifier'],
            'jcr:primaryType' => $row['type'],
        );

        $sql = "SELECT * FROM jcrprops WHERE node_identifier = ?";
        $props = $this->conn->fetchAll($sql, array($data['jcr:uuid']));

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
            $data[$prop['name']] = $value;
            $data[":" . $prop['name']] = $type;
        }
        var_dump($data);
        return $data;
    }

    /**
     * Retrieves a binary value
     *
     * @param $path
     * @return string
     */
    public function getBinaryProperty($path)
    {
        $this->assertLoggedIn();

        return $this->conn->fetchColumn('SELECT data FROM jcrbinarydata WHERE path = ?', array($path));
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

    }

    public function restoreItem($removeExisting, $versionPath, $path)
    {

    }

    public function getVersionHistory($path)
    {

    }

    public function querySQL($query, $limit = null, $offset = null)
    {

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
    public function deleteItem($path)
    {
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

            #throw new \PHPCR\RepositoryException("Could not delete item at path ".$path);
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

        $nodeName = end(explode("/", $srcAbsPath));
    }

    /**
     * Stores an item to the given absolute path
     *
     * @param string $path Absolute path to identify a special item.
     * @param \PHPCR\NodeType\NodeTypeInterface $primaryType
     * @param \Traversable $properties array of \PHPCR\PropertyInterface objects
     * @param \Traversable $children array of \PHPCR\NodeInterface objects
     * @return bool true on success
     *
     * @throws \PHPCR\RepositoryException if not logged in
     */
    public function storeItem($path, $properties, $children)
    {
        $this->assertLoggedIn();

        $nodeIdentifier = (isset($properties['jcr:uuid'])) ? $properties['jcr:uuid'] : $this->generateUUID();
        if (!$this->pathExists($path)) {
            $this->conn->insert("jcrnodes", array(
                'identifier' => $nodeIdentifier,
                'type' => isset($properties['jcr:primaryType']) ? $properties['jcr:primaryType'] : "nt:unstructured",
                'path' => $path,
                'workspace_id' => $this->workspaceId,
            ));
        }

        unset($properties['jcr:uuid'], $properties['jcr:primaryType']);
        foreach ($properties AS $property) {
            $this->storeProperty($path, $property);
        }
    }

    private function generateUUID()
    {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,

            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
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
    public function storeProperty($path, \PHPCR\PropertyInterface $property)
    {
        // TODO: Upsert
        /* @var $property \PHPCR\PropertyInterface */
        $data = array('path' => $property->getPath(), 'workspace_id' => $this->workspaceId);
        $this->conn->delete('jcrprops', array('path' => $property->getPath(), 'workspace_id' => $this->workspaceId));

        $data['type'] = $property->getType();
        $isBinary = false;
        switch ($data['type']) {
            case \PHPCR\PropertyType::NAME:
            case \PHPCR\PropertyType::URI:
            case \PHPCR\PropertyType::WEAKREFERENCE:
            case \PHPCR\PropertyType::REFERENCE:
            case \PHPCR\PropertyType::PATH:
                $data['string_data'] = $property->getString();
                break;
            case \PHPCR\PropertyType::DECIMAL:
                $data['string_data'] = $property->getDecimal();
                break;
            case \PHPCR\PropertyType::STRING:
                $data['clob_data'] = $property->getString();
                break;
            case \PHPCR\PropertyType::BOOLEAN:
                $data['int_data'] = $property->getBoolean() ? 1 : 0;
                break;
            case \PHPCR\PropertyType::LONG:
                $data['int_data'] = $property->getLong();
                break;
            case \PHPCR\PropertyType::BINARY:
                $isBinary = true;
                $data['int_data'] = $property->getBinary()->getSize();
                break;
            case \PHPCR\PropertyType::DATE:
                $data['datetime_data'] = $property->getDate()->format($this->conn->getDatabasePlatform()->getDateTimeFormatString());
                break;
            case \PHPCR\PropertyType::DOUBLE:
                $data['float_data'] = $property->getDouble();
                break;
        }

        $this->conn->insert('jcrprops', $data);
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
    }

    /**
     * Returns node types
     * @param array nodetypes to request
     * @return dom with the definitions
     * @throws \PHPCR\RepositoryException if not logged in
     */
    public function getNodeTypes($nodeTypes = array())
    {
        return array();
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
}