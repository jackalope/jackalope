<?php

/**
 * Class to handle the communication between Jackalope and Jackrabbit via Davex.
 *
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache License Version 2.0, January 2004
 *   Licensed under the Apache License, Version 2.0 (the "License");
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

namespace Jackalope\Transport\Davex;
use Jackalope\Transport\curl;
use Jackalope\TransportInterface;
use DOMDocument;

/**
 * Connection to one Jackrabbit server.
 *
 * Once the login method has been called, the workspace is set and can not be changed anymore.
 *
 * @package jackalope
 * @subpackage transport
 */
class Client implements TransportInterface
{
    /**
     * Description of the namspace to be used for communication with the server.
     * @var string
     */
    const NS_DCR = 'http://www.day.com/jcr/webdav/1.0';

    /**
     * Identifier of the used namespace.
     * @var string
     */
    const NS_DAV = 'DAV:';

    /**
     * Representation of a XML string header.
     *
     * @todo TODO: seems not to be used anymore.
     *
     * @var string
     */
    const REGISTERED_NAMESPACES =
        '<?xml version="1.0" encoding="UTF-8"?>< xmlns:dcr="http://www.day.com/jcr/webdav/1.0"/>';

    /**
     * Server url including protocol.
     *
     * i.e http://localhost:8080/server/
     * constructor ensures the trailing slash /
     *
     * @var string
     */
    protected $server;

    /**
     * Workspace name the transport is bound to
     * @var string
     */
    protected $workspace;

    /**
     * Identifier of the workspace including the used protocol and server name.
     *
     * "$server/$workspace" without trailing slash
     *
     *  @var string
     */
    protected $workspaceUri;

    /**
     * Root node path with server domain without trailing slash.
     *
     * "$server/$workspace/jcr%3aroot
     * (make sure you never hardcode the jcr%3aroot, its ugly)
     * @todo TODO: apparently, jackrabbit handles the root node by name - it is invisible everywhere for the api,
     *             but needed when talking to the backend... could that name change?
     *
     * @var string
     */
    protected $workspaceUriRoot;

    /**
     * Set of credentials necessary to connect to the server or else.
     * @var \PHPCR\CredentialsInterface
     */
    protected $credentials;

    /**
     * The cURL resource handle
     * @var curl
     */
    protected $curl = null;

    /**
     * Create a transport pointing to a server url.
     *
     * @param serverUri location of the server
     */
    public function __construct($serverUri)
    {
        // append a slash if not there
        if ('/' !== substr($serverUri, -1)) {
            $serverUri .= '/';
        }
        $this->server = $serverUri;
    }

    /**
     * Tidies up the current cUrl connection.
     */
    public function __destruct()
    {
        if ($this->curl) {
            $this->curl->close();
        }
    }

    /**
     * Opens a cURL session if not yet one open.
     *
     * @return null|false False in case there is already an open connection, else null;
     */
    protected function getRequest($method, $uri)
    {
        // init curl
        if (is_null($this->curl)) {
            $this->curl = new curl();
        }

        $uri = $this->normalizeUri($uri);

        $request = new Request($this->curl, $method, $uri);
        $request->setCredentials($this->credentials);

        return $request;
    }

    /**
     * Set this transport to a specific credential and a workspace.
     *
     * This can only be called once. To connect to another workspace or with
     * another credential, use a fresh instance of transport.
     *
     * @param \PHPCR\CredentialsInterface $credentials A set of attributes to be used to login per example.
     * @param string $workspaceName The workspace name for this transport.
     * @return boolean True on success, exceptions on failure.
     *
     * @throws \PHPCR\LoginException if authentication or authorization (for the specified workspace) fails
     * @throws \PHPCR\NoSuchWorkspacexception if the specified workspaceName is not recognized
     * @throws \PHPCR\RepositoryException if another error occurs
     */
    public function login(\PHPCR\CredentialsInterface $credentials, $workspaceName)
    {
        if ($this->credentials) {
            throw new \PHPCR\RepositoryException(
                'Do not call login twice. Rather instantiate a new Transport object '.
                'to log in as different user or for a different workspace.'
            );
        }
        if (!$credentials instanceof \PHPCR\SimpleCredentials) {
            throw new \PHPCR\LoginException('Unkown Credentials Type: '.get_class($credentials));
        }

        $this->credentials = $credentials;
        $this->workspace = $workspaceName;
        $this->workspaceUri = $this->server . $workspaceName;
        $this->workspaceUriRoot = $this->workspaceUri . "/jcr:root";

        $request = $this->getRequest(Request::PROPFIND, $this->workspaceUri);
        $request->setBody($this->buildPropfindRequest(array('D:workspace', 'dcr:workspaceName')));
        $dom = $request->executeDom();

        $set = $dom->getElementsByTagNameNS(self::NS_DCR, 'workspaceName');
        if ($set->length != 1) {
            throw new \PHPCR\RepositoryException('Unexpected answer from server: '.$dom->saveXML());
        }

        if ($set->item(0)->textContent != $this->workspace) {
            throw new \PHPCR\RepositoryException('Wrong workspace in answer from server: '.$dom->saveXML());
        }
        return true;
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
        $request = $this->getRequest(Request::REPORT, $this->server);
        $request->setBody($this->buildReportRequest('dcr:repositorydescriptors'));
        $dom = $request->executeDom();

        if ($dom->firstChild->localName != 'repositorydescriptors-report' ||
            $dom->firstChild->namespaceURI != self::NS_DCR) {
            throw new \PHPCR\RepositoryException('Error talking to the backend. '.$dom->saveXML());
        }

        $descs = $dom->getElementsByTagNameNS(self::NS_DCR, 'descriptor');
        $descriptors = array();
        foreach($descs as $desc) {
            $values = array();
            foreach($desc->getElementsByTagNameNS(self::NS_DCR, 'descriptorvalue') as $value) {
                $values[] = $value->textContent;
            }
            if ($desc->childNodes->length == 2) {
                //there was one type and one value => this is a single value property
                //TODO: is this the correct assumption? or should the backend tell us specifically?
                $descriptors[$desc->firstChild->textContent] = $values[0];
            } else {
                $descriptors[$desc->firstChild->textContent] = $values;
            }
        }
        return $descriptors;
    }

    /**
     * Returns the accessible workspace names
     *
     * @return array Set of workspaces to work on.
     */
    public function getAccessibleWorkspaceNames()
    {
        $request = $this->getRequest(Request::PROPFIND, $this->server);
        $request->setBody($this->buildPropfindRequest(array('D:workspace')));
        $request->setDepth(1);
        $dom = $request->executeDom();

        $workspaces = array();
        foreach ($dom->getElementsByTagNameNS(self::NS_DAV, 'workspace') as $value) {
            if (!empty($value->nodeValue)) {
                $workspaces[] = substr(trim($value->nodeValue), strlen($this->server), -1);
            }
        }
        return array_unique($workspaces);
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
        $this->ensureAbsolutePath($path);

        $path .= '.0.json';

        $request = $this->getRequest(Request::GET, $path);
        return $request->executeJson();
    }

    /**
     * checks if the path is absolute, throws an exception if it is not
     *
     * @param path to check
     * @throws \PHPCR\RepositoryException If path is not absolute
     */
    protected function ensureAbsolutePath($path)
    {
        if ('/' != substr($path, 0, 1)) {
            //sanity check
            throw new \PHPCR\RepositoryException("Implementation error: '$path' is not an absolute path");
        }
    }

    /**
     * Prepends the workspace root to the uris that contain an absolute path
     *
     * @param   string  $uri    The URI to normalize
     * @return  string  The normalized URI
     * @throws \PHPCR\RepositoryException   If workspaceUri is missing
     */
    protected function normalizeUri($uri)
    {
        if (substr($uri, 0, 1) === '/') {
            if (empty($this->workspaceUri)) {
                throw new \PHPCR\RepositoryException("Implementation error: Please login before accessing content");
            }
            $uri = $this->workspaceUriRoot . $uri;
        }
        return $uri;
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
        $this->ensureAbsolutePath($path);

        $request = $this->getRequest(Request::DELETE, $path);
        $request->execute();
        return true;
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
        return $this->deleteItem($path);
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
        $this->ensureAbsolutePath($srcAbsPath);
        $this->ensureAbsolutePath($dstAbsPath);

        if ($srcWorkspace) {
            $srcAbsPath = $this->server . $srcAbsPath;
        }

        $request = $this->getRequest(Request::COPY, $srcAbsPath);
        $request->setDepth(Request::INFINITY);
        $request->addHeader('Destination: '.$this->normalizeUri($dstAbsPath));
        $request->execute();
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
        $this->ensureAbsolutePath($srcAbsPath);
        $this->ensureAbsolutePath($dstAbsPath);

        $request = $this->getRequest(Request::MOVE, $srcAbsPath);
        $request->setDepth(Request::INFINITY);
        $request->addHeader('Destination: '.$this->normalizeUri($dstAbsPath));
        $request->execute();
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
        $this->ensureAbsolutePath($path);

        $body = '<?xml version="1.0" encoding="UTF-8"?>';
        $body .= $this->createNodeMarkup($path, $properties, $children);

        $request = $this->getRequest(Request::MKCOL, $path);
        $request->setBody($body);
        $request->execute();

        return true;
    }

    protected function createNodeMarkup($path, $properties, $children)
    {
        $body = '<sv:node xmlns:sv="http://www.jcp.org/jcr/sv/1.0" xmlns:nt="http://www.jcp.org/jcr/nt/1.0" sv:name="'.basename($path).'">';

        foreach ($properties as $name => $property) {
            $type = \PHPCR\PropertyType::nameFromValue($property->getType());
            $body .= '<sv:property sv:name="'.$name.'" sv:type="'.$type.'">'.
                    '<sv:value>'.$this->propertyToXmlString($property, $type).'</sv:value>'.
                '</sv:property>';
        }

        foreach ($children as $name => $node) {
            $body .= $this->createNodeMarkup($path.'/'.$name, $node->getProperties(), $node->getNodes());
        }

        return $body . '</sv:node>';
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
        $this->ensureAbsolutePath($path);

        $type = \PHPCR\PropertyType::nameFromValue($property->getType());

        $request = $this->getRequest(Request::PUT, $path);
        $request->setBody($this->propertyToRawString($property, $type));
        $request->setContentType('jcr-value/'.strtolower($type));
        $request->execute();

        return true;
    }

    protected function propertyToXmlString($property, $type)
    {
        $value = $property->getNativeValue();
        switch ($type) {
        case \PHPCR\PropertyType::TYPENAME_DATE:
            return $value->format(\Jackalope\Helper::DATETIME_FORMAT);
        case \PHPCR\PropertyType::TYPENAME_BINARY:
            return base64_encode($value);
        case \PHPCR\PropertyType::UNDEFINED:
        case \PHPCR\PropertyType::STRING:
        case \PHPCR\PropertyType::URI:
            $value = str_replace(']]>',']]]]><![CDATA[>',$value);
            return '<![CDATA['.$value.']]>';
        }
        return $value;
    }

    protected function propertyToRawString($property, $type)
    {
        // skip binary encoding for raw strings
        switch ($type) {
        case \PHPCR\PropertyType::TYPENAME_BINARY:
            return $property->getNativeValue();
        }
        return $this->propertyToXmlString($property, $type);
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
        $request = $this->getRequest(Request::REPORT, $this->workspaceUri);
        $request->setBody($this->buildLocateRequest($uuid));
        $dom = $request->executeDom();

        /* answer looks like
           <D:multistatus xmlns:D="DAV:">
             <D:response>
                 <D:href>http://localhost:8080/server/tests/jcr%3aroot/tests_level1_access_base/idExample/</D:href>
             </D:response>
         </D:multistatus>
        */
        $set = $dom->getElementsByTagNameNS(self::NS_DAV, 'href');
        if ($set->length != 1) {
            throw new \PHPCR\RepositoryException('Unexpected answer from server: '.$dom->saveXML());
        }
        $fullPath = $set->item(0)->textContent;
        if (strncmp($this->workspaceUriRoot, $fullPath, strlen($this->workspaceUri))) {
            throw new \PHPCR\RepositoryException(
                "Server answered a path that is not in the current workspace: uuid=$uuid, path=$fullPath, workspace=".
                $this->workspaceUriRoot
            );
        }
        return substr(
            substr($fullPath, 0, -1), //cut trailing slash /
            strlen($this->workspaceUriRoot) //remove uri, workspace and root node
        );
    }

    /**
     * Get the registered namespaces mappings from the backend.
     *
     * @return array Associative array of prefix => uri
     *
     * @throws \PHPCR\RepositoryException if not logged in
     */
    public function getNamespaces()
    {
        $request = $this->getRequest(Request::REPORT, $this->workspaceUri);
        $request->setBody($this->buildReportRequest('dcr:registerednamespaces'));
        $dom = $request->executeDom();

        if ($dom->firstChild->localName != 'registerednamespaces-report' ||
            $dom->firstChild->namespaceURI != self::NS_DCR) {
            throw new \PHPCR\RepositoryException('Error talking to the backend. '.$dom->saveXML());
        }

        $mappings = array();
        $namespaces = $dom->getElementsByTagNameNS(self::NS_DCR, 'namespace');
        foreach($namespaces as $elem) {
            $mappings[$elem->firstChild->textContent] = $elem->lastChild->textContent;
        }
        return $mappings;
    }

    /**
     * Returns node types
     * @param array nodetypes to request
     * @return dom with the definitions
     * @throws \PHPCR\RepositoryException if not logged in
     */
    public function getNodeTypes($nodeTypes = array())
    {
        $request = $this->getRequest(Request::REPORT, $this->workspaceUriRoot);
        $request->setBody($this->buildNodeTypesRequest($nodeTypes));
        $dom = $request->executeDom();

        if ($dom->firstChild->localName != 'nodeTypes') {
            throw new \PHPCR\RepositoryException('Error talking to the backend. '.$dom->saveXML());
        }
        return $dom;
    }

    /**
     * Returns the XML required to request nodetypes
     *
     * @param array $nodesType The list of nodetypes you want to request for.
     * @return string XML with the request information.
     */
    protected function buildNodeTypesRequest(array $nodeTypes)
    {
        $xml = '<?xml version="1.0" encoding="utf-8" ?>'.
            '<jcr:nodetypes xmlns:jcr="http://www.day.com/jcr/webdav/1.0">';
        if (empty($nodeTypes)) {
            $xml .= '<jcr:all-nodetypes/>';
        } else {
            foreach ($nodeTypes as $nodetype) {
                $xml .= '<jcr:nodetype><jcr:nodetypename>'.$nodetype.'</jcr:nodetypename></jcr:nodetype>';
            }
        }
        $xml .='</jcr:nodetypes>';
        return $xml;
    }

    /**
     * Build PROPFIND request XML for the specified property names
     *
     * @param array $properties names of the properties to search for
     * @return string XML to post in the body
     */
    protected function buildPropfindRequest($properties)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.
            '<D:propfind xmlns:D="DAV:" xmlns:dcr="http://www.day.com/jcr/webdav/1.0"><D:prop>';
        if (!is_array($properties)) {
            $properties = array($properties);
        }
        foreach($properties as $property) {
            $xml .= '<'. $property . '/>';
        }
        $xml .= '</D:prop></D:propfind>';
        return $xml;
    }

    /**
     * Build a REPORT XML request string
     *
     * @param string $name Name of the resource to be requested.
     * @return string XML string representing the head of the request.
     */
    protected function buildReportRequest($name)
    {
        return '<?xml version="1.0" encoding="UTF-8"?><' .
                $name .
               ' xmlns:dcr="http://www.day.com/jcr/webdav/1.0"/>';
    }

    /**
     * Build REPORT XML request for locating a node path by uuid
     *
     * @param string $uuid Unique identifier of the node to be asked for.
     * @return string XML sring representing the content of the request.
     */
    protected function buildLocateRequest($uuid)
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'.
               '<dcr:locate-by-uuid xmlns:dcr="http://www.day.com/jcr/webdav/1.0">'.
               '<D:href xmlns:D="DAV:">' .
                $uuid .
               '</D:href></dcr:locate-by-uuid>';
    }


}
