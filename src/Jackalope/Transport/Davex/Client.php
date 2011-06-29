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

use PHPCR\PropertyType;
use Jackalope\Transport\curl;
use Jackalope\TransportInterface;
use Jackalope\NotImplementedException;
use DOMDocument;
use Jackalope\NodeType\NodeTypeManager;

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
     * The factory to instantiate objects
     * @var Factory
     */
    protected $factory;

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
     *  A list of additional HTTP headers to be sent on each request
     *  @var array[]string
     */

    protected $defaultHeaders = array();

    /**
     *  @var bool Send Expect: 100-continue header
     */

    protected $sendExpect = false;

    /**
     * @var \Jackalope\NodeType\NodeTypeXmlConverter
     */
    protected $typeXmlConverter;

    /**
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * Check if an initial PROPFIND should be send to check if repository exists
     * This is according to the JCR specifications and set to true by default
     * @see setCheckLoginOnServer
     * @var bool
     */
    protected $checkLoginOnServer = true;

    /**
     * Create a transport pointing to a server url.
     *
     * @param object $factory  an object factory implementing "get" as described in \Jackalope\Factory.
     * @param serverUri location of the server
     */
    public function __construct($factory, $serverUri)
    {
        $this->factory = $factory;
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
     * Add a HTTP header which is sent on each Request
     */
    public function addDefaultHeader($header)
    {
        $this->defaultHeaders[] = $header;
    }

    /**
     * If you want to send the "Expect: 100-continue" header on larger
     * PUT and POST requests, set this to true
     * Disabled by default
     *
     * @param bool $send
     */
    public function sendExpect($send = true)
    {
        $this->sendExpect = $send;
    }

    /**
     * Opens a cURL session if not yet one open.
     *
     * @return null|false False in case there is already an open connection, else null;
     */
    protected function getRequest($method, $uri)
    {
        if (!is_array($uri)) {
            $uri = array($uri => $uri);
        }

        if (is_null($this->curl)) {
            // lazy init curl
            $this->curl = new curl();
        } else if ($this->curl === false) {
            // but do not re-connect, rather report the error if trying to access a closed connection
            throw new \LogicException("Tried to start a request on a closed transport ($method for ".var_export($uri,true).")");
        }

        foreach ($uri as $key => $row) {
            $uri[$key] = $this->addWorkspacePathToUri($row);
        }


        $request = $this->factory->get('Transport\Davex\Request', array($this->curl, $method, $uri));
        $request->setCredentials($this->credentials);
        foreach ($this->defaultHeaders as $header) {
            $request->addHeader($header);
        }

        if (!$this->sendExpect) {
            $request->addHeader("Expect:");
        }

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
     * @throws \PHPCR\NoSuchWorkspaceException if the specified workspaceName is not recognized
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

        if (!$this->checkLoginOnServer ) {
            return true;
        }

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
     * Releases all resources associated with this Session.
     *
     * This method should be called when a Session is no longer needed.
     *
     * @return void
     */
    public function logout()
    {
        $this->curl->close();
        $this->curl = false;
    }

    /**
     * Change the way Jackalope works when getting a session
     * By default, it sends a PROPFIND to the server, to see if the repository exists
     * You can disable that with setting it to false, then an error only occurs later
     * if the repository doesn't exits
     *
     * @return void
     */
    public function setCheckLoginOnServer($bool)
    {
        $this->checkLoginOnServer = $bool;
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

        if ($dom->firstChild->localName != 'repositorydescriptors-report'
            || $dom->firstChild->namespaceURI != self::NS_DCR
        ) {
            throw new \PHPCR\RepositoryException('Error talking to the backend. '.$dom->saveXML());
        }

        $descs = $dom->getElementsByTagNameNS(self::NS_DCR, 'descriptor');
        $descriptors = array();
        foreach ($descs as $desc) {
            $values = array();
            foreach ($desc->getElementsByTagNameNS(self::NS_DCR, 'descriptorvalue') as $value) {
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
     * Get the node from an absolute path
     *
     * @param string $path Absolute path to the node.
     * @return array associative array for the node (decoded from json with associative = true)
     *
     * @throws \PHPCR\ItemNotFoundException If the item at path was not found
     * @throws \PHPCR\RepositoryException if not logged in
     */
    public function getNode($path)
    {
        $path = $this->encodePathForDavex($path);
        $path .= '.0.json';

        $request = $this->getRequest(Request::GET, $path);
        try {
            return $request->executeJson();
        } catch (\PHPCR\PathNotFoundException $e) {
            throw new \PHPCR\ItemNotFoundException($e->getMessage(), $e->getCode(), $e);
        }
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
        $url = array_shift($paths);

        if (count($paths) == 0) {
            try {
                return array($url => $this->getNode($url));
            } catch (\PHPCR\ItemNotFoundException $e) {
                return array();
            }
        }
        $body = array();
        
        $url = $this->encodePathForDavex($url).".0.json";
        foreach ($paths as $path) {
            $body[] = http_build_query(array(":get"=>$path));
        }
        $body = implode("&",$body);
        $request = $this->getRequest(Request::POST, $url);
        $request->setBody($body);
        $request->setContentType('application/x-www-form-urlencoded');
        try {
            $data = $request->executeJson();
            return $data->nodes;
        } catch (\PHPCR\PathNotFoundException $e) {
            throw new \PHPCR\ItemNotFoundException($e->getMessage(), $e->getCode(), $e);
        } catch (\PHPCR\RepositoryException $e) {
            if ($e->getMessage() == 'HTTP 403: Prefix must not be empty (org.apache.jackrabbit.spi.commons.conversion.IllegalNameException)') {
                throw new \PHPCR\UnsupportedRepositoryOperationException("Jackalope currently needs a patched jackrabbit for Session->getNodes() to work. Until our patches make it into the official distribution, see https://github.com/jackalope/jackrabbit/blob/2.2-jackalope/README.jackalope.patches.md for details and downloads."); 
            } 
            throw $e;
        }
    }

    /**
     * Get the property stored at an absolute path.
     *
     * Same format as getNode with just one property.
     *
     * @return array associative array with the property value.
     */
    public function getProperty($path)
    {
        throw new NotImplementedException();
        /*
         * TODO: implement
         * jackrabbit: instead of fetching the node, we could make Transport provide it with a
         * GET /server/tests/jcr%3aroot/tests_level1_access_base/multiValueProperty/jcr%3auuid
         * (davex getItem uses json, which is not applicable to properties)
         */
    }

    /**
     * Retrieves a binary value resp. multiple values
     *
     * @param $path the path to the binary property
     *
     * @return mixed decoded stream or array of streams
     */
    public function getBinaryStream($path)
    {
        $path = $this->encodePathForDavex($path);
        $request = $this->getRequest(Request::GET, $path);
        $curl = $request->execute(true);
        switch($curl->getHeader('Content-Type')) {
            case 'text/xml; charset=utf-8':
                return $this->decodeBinaryDom($curl->getResponse());
            case 'jcr-value/binary; charset=utf-8':
                // TODO: OPTIMIZE stream handling!
                $stream = fopen('php://memory', 'rwb+');
                fwrite($stream, $curl->getResponse());
                rewind($stream);
                return $stream;
        }

        throw new \PHPCR\RepositoryException('Unknown encoding of binary data: '.$curl->getHeader('Content-Type'));
    }

    /**
     * parse the multivalue binary response (a list of base64 encoded values)
     *
     * <dcr:values xmlns:dcr="http://www.day.com/jcr/webdav/1.0">
     *   <dcr:value dcr:type="Binary">aDEuIENoYXB0ZXIgMSBUaXRsZQoKKiBmb28KKiBiYXIKKiogZm9vMgoqKiBmb28zCiogZm9vMAoKfHwgaGVhZGVyIHx8IGJhciB8fAp8IGggfCBqIHwKCntjb2RlfQpoZWxsbyB3b3JsZAp7Y29kZX0KCiMgZm9vCg==</dcr:value>
     *   <dcr:value dcr:type="Binary">aDEuIENoYXB0ZXIgMSBUaXRsZQoKKiBmb28KKiBiYXIKKiogZm9vMgoqKiBmb28zCiogZm9vMAoKfHwgaGVhZGVyIHx8IGJhciB8fAp8IGggfCBqIHwKCntjb2RlfQpoZWxsbyB3b3JsZAp7Y29kZX0KCiMgZm9vCg==</dcr:value>
     * </dcr:values>
     *
     * @param string $xml the xml as returned by jackrabbit
     *
     * @return array of stream resources
     *
     * @throws \PHPCR\RepositoryException if the xml is invalid or any value is not of type binary
     */
    private function decodeBinaryDom($xml)
    {
        $dom = new \DOMDocument();
        if (! $dom->loadXML($xml)) {
            throw new \PHPCR\RepositoryException("Failed to load xml data:\n\n$xml");
        }
        $ret = array();
        foreach($dom->getElementsByTagNameNS(self::NS_DCR, 'values') as $node) {
            foreach($node->getElementsByTagNameNS(self::NS_DCR, 'value') as $value) {
                if ($value->getAttributeNS(self::NS_DCR, 'type') != \PHPCR\PropertyType::TYPENAME_BINARY) {
                    throw new \PHPCR\RepositoryException('Expected binary value but got '.$value->getAttributeNS(self::NS_DCR, 'type'));
                }
                // TODO: OPTIMIZE stream handling!
                $stream = fopen('php://memory', 'rwb+');
                fwrite($stream, base64_decode($value->textContent));
                rewind($stream);
                $ret[] = $stream;
            }
        }
        return $ret;
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
        return $this->getNodeReferences($path, $name);
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
        $path = $this->encodePathForDavex($path);
        $identifier = $weak_reference ? 'weakreferences' : 'references';
        $request = $this->getRequest(Request::PROPFIND, $path);
        $request->setBody($this->buildPropfindRequest(array('dcr:'.$identifier)));
        $request->setDepth(0);
        $dom = $request->executeDom();

        $references = array();

        foreach($dom->getElementsByTagNameNS(self::NS_DCR, $identifier) as $node) {
            foreach($node->getElementsByTagNameNS(self::NS_DAV, 'href') as $ref) {
                $refpath = str_replace($this->workspaceUriRoot, '',  urldecode($ref->textContent));
                if ($name === null || basename($refpath) === $name) {
                    $references[] = str_replace($this->workspaceUriRoot, '',  urldecode($ref->textContent));
                }
            }
        }

        return $references;
    }

    /**
     * Check-in item at path.
     *
     * @param string $path
     * @return string path to the new version
     *
     * @throws PHPCR\UnsupportedRepositoryOperationException
     * @throws PHPCR\RepositoryException
     */
    public function checkinItem($path)
    {
        $path = $this->encodePathForDavex($path);
        try {
            $request = $this->getRequest(Request::CHECKIN, $path);
            $curl = $request->execute(true);
            if ($curl->getHeader("Location")) {
                return $this->stripServerRootFromUri(urldecode($curl->getHeader("Location")));
            }
        } catch (\Jackalope\Transport\Davex\HTTPErrorException $e) {
            if ($e->getCode() == 405) {
                throw new \PHPCR\UnsupportedRepositoryOperationException();
            }
            throw new \PHPCR\RepositoryException($e->getMessage());
        }

        // TODO: not sure what this means
        throw new \PHPCR\RepositoryException();
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
        $path = $this->encodePathForDavex($path);
        try {
            $request = $this->getRequest(Request::CHECKOUT, $path);
            $request->execute();
        } catch (\Jackalope\Transport\Davex\HTTPErrorException $e) {
            if ($e->getCode() == 405) {
                throw new \PHPCR\UnsupportedRepositoryOperationException();
            }
            throw new \PHPCR\RepositoryException();
        }
        return;
    }

    public function restoreItem($removeExisting, $versionPath, $path)
    {
        $path = $this->encodePathForDavex($path);

        $body ='<D:update xmlns:D="DAV:">
	<D:version>
		<D:href>'.$this->addWorkspacePathToUri($versionPath).'</D:href>
	</D:version>';
        if ($removeExisting) {
            $body .= '<dcr:removeexisting xmlns:dcr="http://www.day.com/jcr/webdav/1.0" />';
        }
        $body .= '</D:update>';

        $request = $this->getRequest(Request::UPDATE, $path);
        $request->setBody($body);
        $request->execute(); // errors are checked in request
    }

    public function getVersionHistory($path)
    {
        $path = $this->encodePathForDavex($path);
        $request = $this->getRequest(Request::GET, $path."/jcr:versionHistory");
        $resp = $request->execute();
        return $resp;
    }

    public function query(\PHPCR\Query\QueryInterface $query)
    {
        $querystring = $query->getStatementSql2();
        $limit = $query->getLimit();
        $offset = $query->getOffset();

        $body ='<D:searchrequest xmlns:D="DAV:"><JCR-SQL2><![CDATA['.$querystring.']]></JCR-SQL2>';

        if (null !== $limit || null !== $limit) {
            $body .= '<D:limit>';
            if (null !== $limit) {
                $body .= '<D:nresults>'.(int)$limit.'</D:nresults>';
            }
            if (null !== $offset) {
                $body .= '<offset>'.(int)$offset.'</offset>';
            }
            $body .= '</D:limit>';
        }

        $body .= '</D:searchrequest>';

        $path = $this->addWorkspacePathToUri('/');
        $request = $this->getRequest(Request::SEARCH, $path);
        $request->setBody($body);

        $rawData = $request->execute();

        $dom = new \DOMDocument();
        $dom->loadXML($rawData);

        $rows = array();
        foreach ($dom->getElementsByTagName('response') as $row) {
            $columns = array();
            foreach ($row->getElementsByTagName('column') as $column) {
                $sets = array();
                foreach ($column->childNodes as $childNode) {
                    $sets[$childNode->tagName] = $childNode->nodeValue;
                }
                // TODO if this bug is fixed, spaces may be urlencoded instead of the escape sequence: https://issues.apache.org/jira/browse/JCR-2997
                // the following line fails for nodes with "_x0020 " in their name, changing that part to " x0020_"
                // other characters like < and > are urlencoded, which seems to be handled by dom already.
                $sets['dcr:value'] = str_replace('_x0020_', ' ', $sets['dcr:value']);

                $columns[] = $sets;
            }

            $rows[] = $columns;
        }

        return $rows;
    }

    /**
     * Deletes a node and the whole subtree under it
     *
     * @param string $path Absolute path to the node
     * @return bool true on success
     *
     * @throws \PHPCR\RepositoryException if not logged in
     */
    public function deleteNode($path)
    {
        $path = $this->encodePathForDavex($path);

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
     * @param   string  $srcWorkspace   The source workspace where the node can be found or null for current
     * @return void
     *
     * @link http://www.ietf.org/rfc/rfc2518.txt
     * @see \Jackalope\Workspace::copy
     */
    public function copyNode($srcAbsPath, $dstAbsPath, $srcWorkspace = null)
    {
        $srcAbsPath = $this->encodePathForDavex($srcAbsPath);
        $dstAbsPath = $this->encodePathForDavex($dstAbsPath);

        if ($srcWorkspace) {
            $srcAbsPath = $this->server . $srcAbsPath;
        }

        $request = $this->getRequest(Request::COPY, $srcAbsPath);
        $request->setDepth(Request::INFINITY);
        $request->addHeader('Destination: '.$this->addWorkspacePathToUri($dstAbsPath));
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
        $srcAbsPath = $this->encodePathForDavex($srcAbsPath);
        $dstAbsPath = $this->encodePathForDavex($dstAbsPath);

        $request = $this->getRequest(Request::MOVE, $srcAbsPath);
        $request->setDepth(Request::INFINITY);
        $request->addHeader('Destination: '.$this->addWorkspacePathToUri($dstAbsPath));
        $request->execute();
    }

    public function cloneFrom($srcWorkspace, $srcAbsPath, $destAbsPath, $removeExisting)
    {
        throw new NotImplementedException();
    }

    /**
     * Recursively store a node and its children to the given absolute path.
     *
     * The basename of the path is the name of the node
     *
     * @param NodeInterface $node
     *
     * @return bool true on success
     *
     * @throws \PHPCR\RepositoryException if not logged in
     */
    public function storeNode(\PHPCR\NodeInterface $node)
    {
        $path = $node->getPath();
        $path = $this->encodePathForDavex($path);

        $buffer = array();
        $body = '<?xml version="1.0" encoding="UTF-8"?>';
        $body .= $this->createNodeMarkup($path, $node->getProperties(), $node->getNodes(), $buffer);

        $request = $this->getRequest(Request::MKCOL, $path);
        $request->setBody($body);
        $request->execute();

        // store single-valued multivalue properties separately
        foreach ($buffer as $path => $body) {
            $request = $this->getRequest(Request::PUT, $path);
            $request->setBody($body);
            $request->execute();
        }

        return true;
    }

    /**
     * create the node markup and a list of value dispatches for multivalue properties
     *
     * this is a recursive function.
     *
     * @param string $path path to the current node, basename is the name of the node
     * @param array $properties of this node
     * @param array $children nodes of this node
     * @param array $buffer list of xml strings to set multivalue properties
     */
    protected function createNodeMarkup($path, $properties, $children, array &$buffer)
    {
        $body = '<sv:node xmlns:sv="http://www.jcp.org/jcr/sv/1.0" xmlns:nt="http://www.jcp.org/jcr/nt/1.0" sv:name="'.basename($path).'">';

        foreach ($properties as $name => $property) {
            $type = \PHPCR\PropertyType::nameFromValue($property->getType());
            $nativeValue = $property->getValueForStorage();
            $valueBody = '';
            // handle multivalue properties
            if (is_array($nativeValue)) {
                // multivalue properties with many rows can be inlined
                if (count($nativeValue) > 1 || $name === 'jcr:mixinTypes') {
                    foreach ($nativeValue as $value) {
                        $valueBody .= '<sv:value>'.$this->propertyToXmlString($value, $type).'</sv:value>';
                    }
                } else {
                    // multivalue properties with just one value have to be saved separately to transmit the multivalue info
                    $buffer[$path.'/'.$name] = '<?xml version="1.0" encoding="UTF-8"?><dcr:values xmlns:dcr="http://www.day.com/jcr/webdav/1.0">'.
                        '<dcr:value dcr:type="'.$type.'">'.$this->propertyToXmlString(reset($nativeValue), $type).'</dcr:value>'.
                    '</dcr:values>';
                    continue;
                }
            } else {
                // handle single value properties
                $valueBody = '<sv:value>'.$this->propertyToXmlString($nativeValue, $type).'</sv:value>';
            }
            $body .= '<sv:property sv:name="'.$name.'" sv:type="'.$type.'">'.$valueBody.'</sv:property>';
        }

        foreach ($children as $name => $node) {
            $body .= $this->createNodeMarkup($path.'/'.$name, $node->getProperties(), $node->getNodes(), $buffer);
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
    public function storeProperty(\PHPCR\PropertyInterface $property)
    {
        $path = $property->getPath();
        $path = $this->encodePathForDavex($path);

        $typeid = $property->getType();
        $type = PropertyType::nameFromValue($typeid);
        $nativeValue = $property->getValueForStorage();

        $request = $this->getRequest(Request::PUT, $path);
        if ($property->getName() === 'jcr:mixinTypes') {
            $uri = $this->addWorkspacePathToUri(dirname($path) === '\\' ? '/' : dirname($path));
            $request->setUri($uri);
            $request->setMethod(Request::PROPPATCH);
            $body = '<?xml version="1.0" encoding="UTF-8"?>'.
                '<D:propertyupdate xmlns:D="DAV:">'.
                '<D:set>'.
                '<D:prop>'.
                '<dcr:mixinnodetypes xmlns:dcr="http://www.day.com/jcr/webdav/1.0">';
            foreach ($nativeValue as $value) {
                $body .= '<dcr:nodetype><dcr:nodetypename>'.$value.'</dcr:nodetypename></dcr:nodetype>';
            }
            $body .= '</dcr:mixinnodetypes>'.
                '</D:prop>'.
                '</D:set>'.
                '</D:propertyupdate>';
        } elseif (is_array($nativeValue)) {
            $body = '<?xml version="1.0" encoding="UTF-8"?>'.
                '<jcr:values xmlns:jcr="http://www.day.com/jcr/webdav/1.0">';
            foreach ($nativeValue as $value) {
                $body .= '<jcr:value jcr:type="'.$type.'">'.$this->propertyToXmlString($value, $type).'</jcr:value>';
            }
            $body .= '</jcr:values>';
        } else {
            $body = $this->propertyToRawString($nativeValue, $type);
            $request->setContentType('jcr-value/'.strtolower($type));
        }
        $request->setBody($body);
        $request->execute();

        return true;
    }

    /**
     * This method is used when building an XML of the properties
     *
     * @param  $value
     * @param  $type
     * @return mixed|string
     */
    protected function propertyToXmlString($value, $type)
    {
        switch ($type) {
            case \PHPCR\PropertyType::TYPENAME_BOOLEAN:
                return $value ? 'true' : 'false';
            case \PHPCR\PropertyType::TYPENAME_DATE:
                return PropertyType::convertType($value, PropertyType::STRING);
            case \PHPCR\PropertyType::TYPENAME_BINARY:
                return base64_encode(stream_get_contents($value));
            case \PHPCR\PropertyType::TYPENAME_UNDEFINED:
            case \PHPCR\PropertyType::TYPENAME_STRING:
            case \PHPCR\PropertyType::TYPENAME_URI:
                $value = str_replace(']]>',']]]]><![CDATA[>',$value);
                return '<![CDATA['.$value.']]>';
        }
        return $value;
    }

    /**
     * This method is used to directly set a property
     *
     * @param  $value
     * @param  $type
     * @return mixed|string
     */
    protected function propertyToRawString($value, $type)
    {
        switch ($type) {
            case \PHPCR\PropertyType::TYPENAME_BINARY:
                $ret = stream_get_contents($value);
                fclose($value);
                return $ret;
            case \PHPCR\PropertyType::TYPENAME_UNDEFINED:
            case \PHPCR\PropertyType::TYPENAME_STRING:
            case \PHPCR\PropertyType::TYPENAME_URI:
                return $value;
        }
        return $this->propertyToXmlString($value, $type);
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
        return $this->stripServerRootFromUri(substr(\urldecode($fullPath),0,-1));
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

        if ($dom->firstChild->localName != 'registerednamespaces-report'
            || $dom->firstChild->namespaceURI != self::NS_DCR
        ) {
            throw new \PHPCR\RepositoryException('Error talking to the backend. '.$dom->saveXML());
        }

        $mappings = array();
        $namespaces = $dom->getElementsByTagNameNS(self::NS_DCR, 'namespace');
        foreach ($namespaces as $elem) {
            $mappings[$elem->firstChild->textContent] = $elem->lastChild->textContent;
        }
        return $mappings;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \PHPCR\UnsupportedRepositoryOperationException if trying to overwrite existing prefix to new uri, as jackrabbit can not do this
     */
    public function registerNamespace($prefix, $uri)
    {
        // seems jackrabbit always expects full list of namespaces
        $namespaces = $this->getNamespaces();

        // check if prefix is already mapped
        if (isset($namespaces[$prefix])) {
            if ($namespaces[$prefix] == $uri) {
                // nothing to do, we already have the mapping
                return;
            }
            // unregister old mapping
            throw new \PHPCR\UnsupportedRepositoryOperationException("Trying to set existing prefix $prefix from ".$namespaces[$prefix]." to different uri $uri, but unregistering namespace is not supported by jackrabbit backend. You can move the old namespace to a different prefix before adding this prefix to work around this issue.");
        }

        // if target uri already exists elsewhere, do not re-send or result is random
        /* weird: we can not unset this or we get the unregister not
         * supported exception. but we can send two mappings and
         * jackrabbit does the right guess what we want and moves the
         * namespace to the new prefix

        if (false !== $expref = array_search($uri, $namespaces)) {
            unset($namespaces[$expref]);
        }
        */

        $request = $this->getRequest(Request::PROPPATCH, $this->workspaceUri);
        $namespaces[$prefix] = $uri;
        $request->setBody($this->buildRegisterNamespaceRequest($namespaces));
        $request->execute();
        return true;
    }

    /**
     * Unregister an existing namespace.
     *
     * Validation based on what was returned from getNamespaces has already
     * happened in the NamespaceRegistry.
     *
     * @param string $prefix The prefix to unregister.
     */
    public function unregisterNamespace($prefix)
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException('Unregistering namespace not supported by jackrabbit backend');

        /*
         * TODO: could look a bit like the following if the backend would support it
        $request = $this->getRequest(Request::PROPPATCH, $this->workspaceUri);
        // seems jackrabbit always expects full list of namespaces
        $namespaces = $this->getNamespaces();
        unset($namespaces[$prefix]);
        $request->setBody($this->buildRegisterNamespaceRequest($namespaces));
        $request->execute();
        return true;
        */
    }

    /**
     * Returns node types as array structure
     *
     * @param array nodetypes to request
     *
     * @return array a list of nodetype definitions
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

        if ($this->typeXmlConverter === null) {
            $this->typeXmlConverter = new \Jackalope\NodeType\NodeTypeXmlConverter();
        }

        return $this->typeXmlConverter->getNodeTypesFromXml($dom);
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
     *
     * @author david at liip.ch
     */
    public function registerNodeTypesCnd($cnd, $allowUpdate)
    {
        $request = $this->getRequest(Request::PROPPATCH, $this->workspaceUri);
        $request->setBody($this->buildRegisterNodeTypeRequest($cnd, $allowUpdate));
        $request->execute();
        return true;
    }

    /**
     * @param array $types a list of \PHPCR\NodeType\NodeTypeDefinitionInterface objects
     * @param boolean $allowUpdate whether to fail if node already exists or to update it
     * @return bool true on success
     */
    public function registerNodeTypes($types, $allowUpdate)
    {
        throw new NotImplementedException('TODO: convert node type definition to cnd format and call registerNodeTypesCnd');
        //see http://jackrabbit.apache.org/node-type-notation.html
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
        // TODO: OPTIMIZE - once we have ACL this might be done without any server request
        $body = '<?xml version="1.0" encoding="UTF-8"?>' .
                '<dcr:privileges xmlns:dcr="http://www.day.com/jcr/webdav/1.0">' .
                '<D:href xmlns:D="DAV:">'.$this->addWorkspacePathToUri($path).'</D:href>' .
                '</dcr:privileges>';

        $valid_permissions = array(
            \PHPCR\SessionInterface::ACTION_ADD_NODE,
            \PHPCR\SessionInterface::ACTION_READ,
            \PHPCR\SessionInterface::ACTION_REMOVE,
            \PHPCR\SessionInterface::ACTION_SET_PROPERTY);

        $result = array();

        $request = $this->getRequest(Request::REPORT, $this->workspaceUri);
        $request->setBody($body);
        $dom = $request->executeDom();

        foreach($dom->getElementsByTagNameNS(self::NS_DAV, 'current-user-privilege-set') as $node) {
            foreach($node->getElementsByTagNameNS(self::NS_DAV, 'privilege') as $privilege) {
                foreach($privilege->childNodes as $child) {
                    $permission = str_replace('dcr:', '', $child->tagName);
                    if (! in_array($permission, $valid_permissions)) {
                        throw new \PHPCR\RepositoryException("Invalid permission '$permission'");
                    }
                    $result[] = $permission;
                }
            }
        }

        return $result;
    }

    /**
     * Build the xml required to register node types
     *
     * @param string $cnd the node type definition
     * @return string XML with register request
     *
     * @author david at liip.ch
     */
    protected function buildRegisterNodeTypeRequest($cnd, $allowUpdate)
    {
        $cnd = '<dcr:cnd>'.str_replace(array('<','>'), array('&lt;','&gt;'), $cnd).'</dcr:cnd>';
        $cnd .= '<dcr:allowupdate>'.($allowUpdate ? 'true' : 'false').'</dcr:allowupdate>';
        return '<?xml version="1.0" encoding="UTF-8" standalone="no"?><D:propertyupdate xmlns:D="DAV:"><D:set><D:prop><dcr:nodetypes-cnd xmlns:dcr="http://www.day.com/jcr/webdav/1.0">'.$cnd.'</dcr:nodetypes-cnd></D:prop></D:set></D:propertyupdate>';
    }

    /**
     * Build the xml to update the namespaces
     *
     * You need to repeat all existing node type plus add your new ones
     *
     * @param array $mappings hashmap of prefix => uri for all existing and new namespaces
     */
    protected function buildRegisterNamespaceRequest($mappings) {
        $ns = '';
        foreach ($mappings as $prefix => $uri) {
            $ns .= "<dcr:namespace><dcr:prefix>$prefix</dcr:prefix><dcr:uri>$uri</dcr:uri></dcr:namespace>";
        }

        return '<?xml version="1.0" encoding="UTF-8"?><D:propertyupdate xmlns:D="DAV:"><D:set><D:prop><dcr:namespaces xmlns:dcr="http://www.day.com/jcr/webdav/1.0">'.
                $ns .
                '</dcr:namespaces></D:prop></D:set></D:propertyupdate>';
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
        foreach ($properties as $property) {
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

    //TODO: this seems unused - and its never set anyways
    public function setNodeTypeManager($nodeTypeManager)
    {
        $this->nodeTypeManager = $nodeTypeManager;
    }

    /**
     * Whether the path conforms to the specification and is supported by this implementation
     *
     * @see http://www.day.com/specs/jcr/2.0/3_Repository_Model.html#3.2.2%20Local%20Names
     *
     * TODO: the spec is extremly open and recommends to restrict further. We
     * currently have rather random restrictions
     *
     * @param   string  $path   THe path to validate
     *
     * @return boolean always true, exception if this is not a valid path
     *
     * @throws RepositoryException if the path contains invalid characters
     */
    protected function ensureValidPath($path)
    {
        if (! (strpos($path, '//') === false
              && strpos($path, '/../') === false
              && preg_match('/^[\w{}\/#:^+~*\[\]\. <>"\'-]*$/i', $path))
        ) {
            throw new \PHPCR\RepositoryException('Path is not well-formed or contains invalid characters: ' . $path);
        }
        // if we allow MORE stuff, we might have to adapt encodePathForDavex for escaping
    }

    /**
     * Checks if the path is absolute and valid, and properly urlencodes special characters
     *
     * This is to be used in the Davex headers. The XML requests can cope with unencoded stuff
     *
     * @param string $path to check
     *
     * @return string the cleaned path
     *
     * @throws \PHPCR\RepositoryException If path is not absolute or invalid
     */
    protected function encodePathForDavex($path)
    {
        if ('/' != substr($path, 0, 1)) {
            //sanity check
            throw new \PHPCR\RepositoryException("Implementation error: '$path' is not an absolute path");
        }
        $this->ensureValidPath($path);
        return str_replace(' ', '%20', $path); // TODO: does ensureValidPath allow other characters that should be encoded?
    }

    /**
     * remove the server and workspace part from an uri, leaving the absolute
     * path inside the current workspace
     *
     * @param string $uri a full uri including the server path, workspace and jcr%3aroot
     *
     * @return string absolute path in the current work space
     */
    protected function stripServerRootFromUri($uri)
    {
        return substr($uri,strlen($this->workspaceUriRoot));
    }

    /**
     * Prepends the workspace root to the uris that contain an absolute path
     *
     * @param   string  $uri The absolute path in the current workspace or server uri
     * @return  string The server uri with this path
     * @throws \PHPCR\RepositoryException   If workspaceUri is missing (not logged in)
     */
    protected function addWorkspacePathToUri($uri)
    {
        if (substr($uri, 0, 1) === '/') {
            if (empty($this->workspaceUri)) {
                throw new \PHPCR\RepositoryException("Implementation error: Please login before accessing content");
            }
            $uri = $this->workspaceUriRoot . $uri;
        }
        return $uri;
    }
}
