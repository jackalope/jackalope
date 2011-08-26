<?php

namespace Jackalope;

use ArrayIterator;
use PHPCR\PropertyType;

// inherit all doc
/**
 * @api
 */
class Session implements \PHPCR\SessionInterface
{
    /**
     * A registry for all created sessions to be able to reference them by id in
     * the stream wrapper for lazy loading binary properties.
     *
     * Keys are spl_object_hash'es for the sessions which are the values
     * @var array
     */
    protected static $sessionRegistry = array();

    /**
     * The factory to instantiate objects
     * @var object
     */
    protected $factory;

    /**
     * @var Repository
     */
    protected $repository;
    /**
     * @var Workspace
     */
    protected $workspace;
    /**
     * @var ObjectManager
     */
    protected $objectManager;
    /**
     * @var \PHPCR\Transaction\UserTransactionInterface
     */
    protected $utx = null;
    /**
     * @var \PHPCR\SimpleCredentials
     */
    protected $credentials;
    /**
     * Whether this session is in logged out state and can not be used anymore
     * @var bool
     */
    protected $logout = false;
    /**
     * The namespace registry.
     *
     * It is only used to check prefixes and at setup. Session namespace
     * remapping must be handled locally.
     *
     * @var NamespaceRegistry
     */
    protected $namespaceRegistry;

    /**
     * List of local namespaces
     *
     * TODO: implement local namespace rewriting
     * see jackrabbit-spi-commons/src/main/java/org/apache/jackrabbit/spi/commons/conversion/PathParser.java and friends
     * for how this is done in jackrabbit
     */
    //protected $localNamespaces;

    /** Creates a session
     *
     * Builds the corresponding workspace instance
     *
     * @param object $factory an object factory implementing "get" as
     *      described in \Jackalope\Factory
     * @param Repository $repository
     * @param string $workspaceName the workspace name that is used
     * @param \PHPCR\SimpleCredentials $credentials the credentials that where
     *      used to log in, in order to implement Session::getUserID()
     * @param TransportInterface $transport the transport implementation
     */
    public function __construct($factory, Repository $repository, $workspaceName, \PHPCR\SimpleCredentials $credentials, TransportInterface $transport)
    {
        $this->factory = $factory;
        $this->repository = $repository;
        $this->objectManager = $this->factory->get('ObjectManager', array($transport, $this));
        $this->workspace = $this->factory->get('Workspace', array($this, $this->objectManager, $workspaceName));
        $this->utx = $this->workspace->getTransactionManager();
        $this->credentials = $credentials;
        $this->namespaceRegistry = $this->workspace->getNamespaceRegistry();
        self::registerSession($this);

        $transport->setNodeTypeManager($this->workspace->getNodeTypeManager());
    }

    // inherit all doc
    /**
     * @api
     */
    public function getRepository()
    {
        return $this->repository;
    }

    // inherit all doc
    /**
     * @api
     */
    public function getUserID()
    {
        return $this->credentials->getUserID(); //TODO: what if its not simple credentials? what about anonymous login?
    }

    // inherit all doc
    /**
     * @api
     */
    public function getAttributeNames()
    {
        return $this->credentials->getAttributeNames();
    }

    // inherit all doc
    /**
     * @api
     */
    public function getAttribute($name)
    {
        return $this->credentials->getAttribute($name);
    }

    // inherit all doc
    /**
     * @api
     */
    public function getWorkspace()
    {
        return $this->workspace;
    }

    // inherit all doc
    /**
     * @api
     */
    public function getRootNode()
    {
        return $this->getNode('/');
    }

    // inherit all doc
    /**
     * {@inheritDoc}
     *
     * TODO: Implement this for jackalope
     */
    public function impersonate(\PHPCR\CredentialsInterface $credentials)
    {
        throw new \PHPCR\LoginException('Not supported');
    }

    // inherit all doc
    /**
     * @api
     */
    public function getNodeByIdentifier($id)
    {
        return $this->objectManager->getNode($id);
    }

    // inherit all doc
    /**
     * @api
     */
    public function getNodesByIdentifier($ids)
    {
        $nodesByPath = $this->objectManager->getNodes($ids);
        $nodesByUUID = array();
        foreach ($nodesByPath as $node) {
            $nodesByUUID[$node->getIdentifier()] = $node;
        }
        return new ArrayIterator($nodesByUUID);
    }

    // inherit all doc
    /**
     * @api
     */
    public function getItem($absPath)
    {
        if (strpos($absPath,'/') !== 0) {
            throw new \PHPCR\PathNotFoundException('It is forbidden to call getItem on session with a relative path');
        }

        if ($this->nodeExists($absPath)) {
            return $this->getNode($absPath);
        }
        return $this->getProperty($absPath);
    }

    // inherit all doc
    /**
     * @api
     */
    public function getNode($absPath)
    {
        try {
            return $this->objectManager->getNodeByPath($absPath);
        } catch (\PHPCR\ItemNotFoundException $e) {
            throw new \PHPCR\PathNotFoundException($e->getMessage(), $e->getCode(), $e);
        }
    }

    // inherit all doc
    /**
     * @api
     */
    public function getNodes($absPaths)
    {
        return $this->objectManager->getNodesByPath($absPaths);
    }

    // inherit all doc
    /**
     * @api
     */
    public function getProperty($absPath)
    {
        try {
            return $this->objectManager->getPropertyByPath($absPath);
        } catch (\PHPCR\ItemNotFoundException $e) {
            throw new \PHPCR\PathNotFoundException($e->getMessage(), $e->getCode(), $e);
        }
    }

    // inherit all doc
    /**
     * @api
     */
    public function itemExists($absPath)
    {
        if ($absPath == '/') {
            return true;
        }
        return $this->nodeExists($absPath) || $this->propertyExists($absPath);
    }

    // inherit all doc
    /**
     * @api
     */
    public function nodeExists($absPath)
    {
        if ($absPath == '/') {
            return true;
        }

        try {
            //OPTIMIZE: avoid throwing and catching errors would improve performance if many node exists calls are made
            //would need to communicate to the lower layer that we do not want exceptions
            $this->objectManager->getNodeByPath($absPath);
        } catch(\PHPCR\ItemNotFoundException $e) {
            return false;
        }
        return true;
    }

    // inherit all doc
    /**
     * @api
     */
    public function propertyExists($absPath)
    {
        try {
            //OPTIMIZE: avoid throwing and catching errors would improve performance if many node exists calls are made
            //would need to communicate to the lower layer that we do not want exceptions
            $this->getProperty($absPath);
        } catch(\PHPCR\PathNotFoundException $e) {
            return false;
        }
        return true;

    }

    // inherit all doc
    /**
     * @api
     */
    public function move($srcAbsPath, $destAbsPath)
    {
        try {
            $parent = $this->objectManager->getNodeByPath(dirname($destAbsPath));
        } catch(\PHPCR\ItemNotFoundException $e) {
            throw new \PHPCR\PathNotFoundException("Target path can not be found: $destAbsPath", $e->getCode(), $e);
        }

        if ($parent->hasNode(basename($destAbsPath))) {
            // TODO same-name siblings
            throw new \PHPCR\ItemExistsException('Target node already exists at '.$destAbsPath);
        }
        if ($parent->hasProperty(basename($destAbsPath))) {
            throw new \PHPCR\ItemExistsException('Target property already exists at '.$destAbsPath);
        }
        $this->objectManager->moveNode($srcAbsPath, $destAbsPath);
    }

    // inherit all doc
    /**
     * @api
     */
    public function removeItem($absPath)
    {
        $item = $this->getItem($absPath);
        $item->remove();
    }

    // inherit all doc
    /**
     * @api
     */
    public function save()
    {
        if ($this->utx && !$this->utx->inTransaction()) {
            // do the operation in a short transaction
            $this->utx->begin();
            try {
                $this->objectManager->save();
                $this->utx->commit();
            } catch(\Exception $e) {
                // if anything goes wrong, rollback this mess
                $this->utx->rollback();
                // but do not eat the exception
                throw $e;
            }
        } else {
            $this->objectManager->save();
        }
    }

    // inherit all doc
    /**
     * @api
     */
    public function refresh($keepChanges)
    {
        $this->objectManager->refresh($keepChanges);
    }

    /**
     * Jackalope specific hack to drop the state of the current session
     *
     * Removes all cached objects, planned changes etc without making the
     * objects aware of it. Was done as a cheap replacement for refresh
     * in testing.
     *
     * @deprecated: this will screw up major, as the user of the api can still have references to nodes. USE refresh instead!
     */
    public function clear()
    {
        trigger_error('Use Session::refresh instead, this method is extremely unsafe', E_USER_DEPRECATED);
        $this->objectManager->clear();
    }

    // inherit all doc
    /**
     * @api
     */
    public function hasPendingChanges()
    {
        return $this->objectManager->hasPendingChanges();
    }

    // inherit all doc
    /**
     * @api
     */
    public function hasPermission($absPath, $actions)
    {
        $actualPermissions = $this->objectManager->getPermissions($absPath);
        $requestedPermissions = explode(',', $actions);

        foreach ($requestedPermissions as $perm) {
            if (! in_array(strtolower(trim($perm)), $actualPermissions)) {
                return false;
            }
        }

        return true;
    }

    // inherit all doc
    /**
     * @api
     */
    public function checkPermission($absPath, $actions)
    {
        if (! $this->hasPermission($absPath, $actions)) {
            throw new \PHPCR\Security\AccessControlException($absPath);
        }
    }

    // inherit all doc
    /**
     * {@inheritDoc}
     *
     * Jackalope does currently not check anything and always return true.
     *
     * @api
     */
    public function hasCapability($methodName, $target, array $arguments)
    {
        //we never determine wether operation can be performed as it is optional ;-)
        //TODO: could implement some
        return true;
    }

    // inherit all doc
    /**
     * @api
     */
    public function importXML($parentAbsPath, $in, $uuidBehavior)
    {
        throw new NotImplementedException('Write');
    }

    // inherit all doc
    /**
     * @api
     */
    public function exportSystemView($absPath, $stream, $skipBinary, $noRecurse)
    {
        $node = $this->getNode($absPath);

        fwrite($stream, '<?xml version="1.0" encoding="UTF-8"?>'."\n");
        $this->exportSystemViewRecursive($node, $stream, $skipBinary, $noRecurse, true);
    }

    /**
     * Recursively output node and all its children into the file in the system
     * view format
     *
     * @param NodeInterface $node the node to output
     * @param resource $stream The stream resource (i.e. aquired with fopen) to
     *      which the XML serialization of the subgraph will be output. Must
     *      support the fwrite method.
     * @param boolean $skipBinary A boolean governing whether binary properties
     *      are to be serialized.
     * @param boolean $noRecurse A boolean governing whether the subgraph at
     *      absPath is to be recursed.
     * @param boolean $root Whether this is the root node of the resulting
     *      document, meaning the namespace declarations have to be included in
     *      it.
     *
     * @return void
     */
    private function exportSystemViewRecursive($node, $stream, $skipBinary, $noRecurse, $root=false)
    {
        fwrite($stream, '<sv:node');
        if ($root) {
            $this->exportNamespaceDeclarations($stream);
        }
        fwrite($stream, ' sv:name="'.($node->getPath() == '/' ? 'jcr:root' : htmlspecialchars($node->getName())).'">');

        // the order MUST be primary type, then mixins, if any, then jcr:uuid if its a referenceable node
        fwrite($stream, '<sv:property sv:name="jcr:primaryType" sv:type="Name"><sv:value>'.htmlspecialchars($node->getPropertyValue('jcr:primaryType')).'</sv:value></sv:property>');
        if ($node->hasProperty('jcr:mixinTypes')) {
            fwrite($stream, '<sv:property sv:name="jcr:mixinTypes" sv:type="Name">');
            foreach ($node->getPropertyValue('jcr:mixinTypes') as $type) {
                fwrite($stream, '<sv:value>'.htmlspecialchars($type).'</sv:value>');
            }
            fwrite($stream, '</sv:property>');
        }
        if ($node->isNodeType('mix:referenceable')) {
            fwrite($stream, '<sv:property sv:name="jcr:uuid" sv:type="String"><sv:value>'.$node->getIdentifier().'</sv:value></sv:property>');
        }

        foreach ($node->getProperties() as $name => $property) {
            if ($name == 'jcr:primaryType' || $name == 'jcr:mixinTypes' || $name == 'jcr:uuid') {
                // explicitly handled before
                continue;
            }
            if (PropertyType::BINARY == $property->getType() && $skipBinary) {
                // do not output binary data in the xml
                continue;
            }
            fwrite($stream, '<sv:property sv:name="'.htmlentities($name).'" sv:type="'
                                . PropertyType::nameFromValue($property->getType()).'"'
                                . ($property->isMultiple() ? ' sv:multiple="true"' : '')
                                . '>');
            $values = $property->isMultiple() ? $property->getString() : array($property->getString());

            foreach ($values as $value) {
                if (PropertyType::BINARY == $property->getType()) {
                    $val = base64_encode($value);
                } else {
                    $val = htmlspecialchars($value);
                    //TODO: can we still have invalid characters after this? if so base64 and property, xsi:type="xsd:base64Binary"
                }
                fwrite($stream, "<sv:value>$val</sv:value>");
            }
            fwrite($stream, "</sv:property>");
        }
        if (! $noRecurse) {
            foreach ($node as $child) {
                $this->exportSystemViewRecursive($child, $stream, $skipBinary, $noRecurse);
            }
        }
        fwrite($stream, '</sv:node>');
    }

    // inherit all doc
    /**
     * @api
     */
    public function exportDocumentView($absPath, $stream, $skipBinary, $noRecurse)
    {
        $node = $this->getNode($absPath);

        fwrite($stream, '<?xml version="1.0" encoding="UTF-8"?>'."\n");
        $this->exportDocumentViewRecursive($node, $stream, $skipBinary, $noRecurse, true);
    }

    /**
     * Recursively output node and all its children into the file in the
     * document view format
     *
     * @param NodeInterface $node the node to output
     * @param resource $stream the resource to write data out to
     * @param boolean $skipBinary A boolean governing whether binary properties
     *      are to be serialized.
     * @param boolean $noRecurse A boolean governing whether the subgraph at
     *      absPath is to be recursed.
     * @param boolean $root Whether this is the root node of the resulting
     *      document, meaning the namespace declarations have to be included in
     *      it.
     *
     * @return void
     */
    private function exportDocumentViewRecursive($node, $stream, $skipBinary, $noRecurse, $root=false)
    {
        //TODO: encode name according to spec
        $nodename = $this->escapeXmlName($node->getName());
        fwrite($stream, "<$nodename");
        if ($root) {
            $this->exportNamespaceDeclarations($stream);
        }
        foreach ($node->getProperties() as $name => $property) {
            if ($property->isMultiple()) {
                // skip multiple properties. jackrabbit does this too. cheap but whatever. use system view for a complete export
                continue;
            }
            if (PropertyType::BINARY == $property->getType()) {
                if ($skipBinary) {
                    continue;
                }
                $value = base64_encode($property->getString());
            } else {
                $value = htmlspecialchars($property->getString());
            }
            fwrite($stream, ' '.$this->escapeXmlName($name).'="'.$value.'"');
        }
        if ($noRecurse || ! $node->hasNodes()) {
            fwrite($stream, '/>');
        } else {
            fwrite($stream, '>');
            foreach ($node as $child) {
                $this->exportDocumentViewRecursive($child, $stream, $skipBinary, $noRecurse);
            }
            fwrite($stream, "</$nodename>");
        }
    }
    /**
     * Helper method for escaping node names into valid xml according to
     * the specification.
     *
     * @param string $name A node name possibly containing characters illegal
     *      in an XML document.
     *
     * @return string The name encoded to be valid xml
     */
    private function escapeXmlName($name)
    {
        $name = preg_replace('/_(x[0-9a-fA-F]{4})/', '_x005f_\\1', $name);
        return str_replace(array(' ',       '<',       '>',       '"',       "'"),
                           array('_x0020_', '_x003c_', '_x003e_', '_x0022_', '_x0027_'),
                           $name); // TODO: more invalid characters?
    }
    /**
     * Helper method to produce the xmlns:... attributes of the root node from
     * the built-in namespace registry.
     *
     * @param stream $stream the ouptut stream to write the namespaces to
     *
     * @return void
     */
    private function exportNamespaceDeclarations($stream)
    {
        foreach ($this->workspace->getNamespaceRegistry() as $key => $uri) {
            if (! empty($key)) { // no ns declaration for empty namespace
                fwrite($stream, " xmlns:$key=\"$uri\"");
            }
        }
    }

    // inherit all doc
    /**
     * @api
     */
    public function setNamespacePrefix($prefix, $uri)
    {
        $this->namespaceRegistry->checkPrefix($prefix);
        throw new NotImplementedException('TODO: implement session scope remapping of namespaces');
        //this will lead to rewrite all names and paths in requests and replies. part of this can be done in ObjectManager::normalizePath
    }

    // inherit all doc
    /**
     * @api
     */
    public function getNamespacePrefixes()
    {
        //TODO: once setNamespacePrefix is implemented, must take session remaps into account
        return $this->namespaceRegistry->getPrefixes();
    }

    // inherit all doc
    /**
     * @api
     */
    public function getNamespaceURI($prefix)
    {
        //TODO: once setNamespacePrefix is implemented, must take session remaps into account
        return $this->namespaceRegistry->getURI($prefix);
    }

    // inherit all doc
    /**
     * @api
     */
    public function getNamespacePrefix($uri)
    {
        //TODO: once setNamespacePrefix is implemented, must take session remaps into account
        return $this->namespaceRegistry->getPrefix($uri);
    }

    // inherit all doc
    /**
     * @api
     */
    public function logout()
    {
        //OPTIMIZATION: flush object manager to help garbage collector
        $this->logout = true;
        self::unregisterSession($this);
        $this->getTransport()->logout();
    }

    // inherit all doc
    /**
     * @api
     */
    public function isLive()
    {
        return ! $this->logout;
    }

    // inherit all doc
    /**
     * @api
     */
    public function getAccessControlManager()
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException();
    }

    // inherit all doc
    /**
     * @api
     */
    public function getRetentionManager()
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException();
    }

    /**
     * Implementation specific: The object manager is also used by other
     * components, i.e. the QueryManager.
     *
     * @return ObjectManager the object manager associated with this session
     *
     * @private
     */
    public function getObjectManager()
    {
        return $this->objectManager;
    }

    /**
     * Implementation specific: The transport implementation is also used by
     * other components, i.e. the NamespaceRegistry
     *
     * @return TransportInterface the transport implementation associated with
     *      this session.
     *
     * @private
     */
    public function getTransport()
    {
        return $this->objectManager->getTransport();
    }

    /**
     * Implementation specific: register session in session registry for the
     * stream wrapper.
     *
     * @private
     */
    protected static function registerSession(Session $session)
    {
        $key = $session->getRegistryKey();
        self::$sessionRegistry[$key] = $session;
    }

    /**
     * Implementation specific: unregister session in session registry on
     * logout.
     *
     * @private
     */
    protected static function unregisterSession(Session $session)
    {
        $key = $session->getRegistryKey();
        unset(self::$sessionRegistry[$key]);
    }

    /**
     * Implementation specific: create an id for the session registry so that
     * the stream wrapper can identify it.
     *
     * @private
     *
     * @return an id for this session
     */
    public function getRegistryKey()
    {
        return spl_object_hash($this);
    }

    /**
     * Implementation specific: get a session from the session registry for the
     * stream wrapper.
     *
     * @param $key key for the session
     *
     * @return the session or null if none is registered with the given key
     *
     * @private
     */
    public static function getSessionFromRegistry($key)
    {
        if (isset(self::$sessionRegistry[$key])) {
            return self::$sessionRegistry[$key];
        }
    }
}
