<?php

namespace Jackalope;

use PHPCR\Util\PathHelper;
use Exception;

use PHPCR\RepositoryInterface;
use PHPCR\SessionInterface;
use PHPCR\SimpleCredentials;
use PHPCR\CredentialsInterface;
use PHPCR\PathNotFoundException;
use PHPCR\ItemNotFoundException;
use PHPCR\ItemExistsException;
use PHPCR\RepositoryException;
use PHPCR\UnsupportedRepositoryOperationException;
use InvalidArgumentException;

use PHPCR\Security\AccessControlException;

use Jackalope\ImportExport\ImportExport;

use Jackalope\Transport\TransportInterface;
use Jackalope\Transport\TransactionInterface;

/**
 * {@inheritDoc}
 *
 * Jackalope adds the SessionOption concept to handle session specific tweaking
 * and optimization. We distinguish between options that are purely
 * optimization but do not affect the behaviour and those that are change the
 * behaviour.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
class Session implements SessionInterface
{
    /**
     * constant for setSessionOption to manage the fetch depth.
     *
     * This option is used to set the depth with which nodes should be fetched
     * from the backend to optimize performance when you know you will need
     * the child nodes.
     */
    const OPTION_FETCH_DEPTH = 'jackalope.fetch_depth';

    /**
     * constant for setSessionOption to manage whether nodes having
     * mix:lastModified should automatically be updated.
     *
     * Disable if you want to manually control this information, e.g. in a
     * PHPCR-ODM listener.
     */
    const OPTION_AUTO_LASTMODIFIED = 'jackalope.auto_lastmodified';

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
     * @var FactoryInterface
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
     * @var SimpleCredentials
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
     * @param FactoryInterface  $factory       the object factory
     * @param Repository        $repository
     * @param string            $workspaceName the workspace name that is used
     * @param SimpleCredentials $credentials   the credentials that where
     *      used to log in, in order to implement Session::getUserID()
     *      if they are null, getUserID returns null
     * @param TransportInterface $transport the transport implementation
     */
    public function __construct(FactoryInterface $factory, Repository $repository, $workspaceName, SimpleCredentials $credentials = null, TransportInterface $transport)
    {
        $this->factory = $factory;
        $this->repository = $repository;
        $this->objectManager = $this->factory->get('ObjectManager', array($transport, $this));
        $this->workspace = $this->factory->get('Workspace', array($this, $this->objectManager, $workspaceName));
        $this->credentials = $credentials;
        $this->namespaceRegistry = $this->workspace->getNamespaceRegistry();
        self::registerSession($this);

        $transport->setNodeTypeManager($this->workspace->getNodeTypeManager());
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getUserID()
    {
        if (null == $this->credentials) {
            return null;
        }

        return $this->credentials->getUserID(); //TODO: what if its not simple credentials? what about anonymous login?
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getAttributeNames()
    {
        if (null == $this->credentials) {
            return array();
        }

        return $this->credentials->getAttributeNames();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getAttribute($name)
    {
        if (null == $this->credentials) {
            return null;
        }

        return $this->credentials->getAttribute($name);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getWorkspace()
    {
        return $this->workspace;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getRootNode()
    {
        return $this->getNode('/');
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function impersonate(CredentialsInterface $credentials)
    {
        throw new UnsupportedRepositoryOperationException('Not supported');
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getNodeByIdentifier($id)
    {
        return $this->objectManager->getNodeByIdentifier($id);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getNodesByIdentifier($ids)
    {
        if (! is_array($ids) && ! $ids instanceof \Traversable) {
            $hint = is_object($ids) ? get_class($ids) : gettype($ids);
            throw new InvalidArgumentException("Not a valid array or Traversable: $hint");
        }

        return $this->objectManager->getNodesByIdentifier($ids);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getItem($absPath)
    {
        if (! is_string($absPath) || strlen($absPath) === 0 || '/' !== $absPath[0]) {
            throw new PathNotFoundException('It is forbidden to call getItem on session with a relative path');
        }

        if ($this->nodeExists($absPath)) {
            return $this->getNode($absPath);
        }

        return $this->getProperty($absPath);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getNode($absPath, $depthHint = -1)
    {
        if (-1 !== $depthHint) {
            $depth = $this->getSessionOption(self::OPTION_FETCH_DEPTH);
            $this->setSessionOption(self::OPTION_FETCH_DEPTH, $depthHint);
        }

        try {
            $node = $this->objectManager->getNodeByPath($absPath);
            if (isset($depth)) {
                $this->setSessionOption(self::OPTION_FETCH_DEPTH, $depth);
            }

            return $node;
        } catch (ItemNotFoundException $e) {
            if (isset($depth)) {
                $this->setSessionOption(self::OPTION_FETCH_DEPTH, $depth);
            }
            throw new PathNotFoundException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getNodes($absPaths)
    {
        if (! is_array($absPaths) && ! $absPaths instanceof \Traversable) {
            $hint = is_object($absPaths) ? get_class($absPaths) : gettype($absPaths);
            throw new InvalidArgumentException("Not a valid array or Traversable: $hint");
        }

        return $this->objectManager->getNodesByPath($absPaths);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getProperty($absPath)
    {
        try {
            return $this->objectManager->getPropertyByPath($absPath);
        } catch (ItemNotFoundException $e) {
            throw new PathNotFoundException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getProperties($absPaths)
    {
        if (! is_array($absPaths) && ! $absPaths instanceof \Traversable) {
            $hint = is_object($absPaths) ? get_class($absPaths) : gettype($absPaths);
            throw new InvalidArgumentException("Not a valid array or Traversable: $hint");
        }

        return $this->objectManager->getPropertiesByPath($absPaths);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function itemExists($absPath)
    {
        if ($absPath == '/') {
            return true;
        }

        return $this->nodeExists($absPath) || $this->propertyExists($absPath);
    }

    /**
     * {@inheritDoc}
     *
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
        } catch (ItemNotFoundException $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function propertyExists($absPath)
    {
        try {
            //OPTIMIZE: avoid throwing and catching errors would improve performance if many node exists calls are made
            //would need to communicate to the lower layer that we do not want exceptions
            $this->getProperty($absPath);
        } catch (PathNotFoundException $e) {
            return false;
        }

        return true;

    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function move($srcAbsPath, $destAbsPath)
    {
        try {
            $parent = $this->objectManager->getNodeByPath(PathHelper::getParentPath($destAbsPath));
        } catch (ItemNotFoundException $e) {
            throw new PathNotFoundException("Target path can not be found: $destAbsPath", $e->getCode(), $e);
        }

        if ($parent->hasNode(PathHelper::getNodeName($destAbsPath))) {
            // TODO same-name siblings
            throw new ItemExistsException('Target node already exists at '.$destAbsPath);
        }
        if ($parent->hasProperty(PathHelper::getNodeName($destAbsPath))) {
            throw new ItemExistsException('Target property already exists at '.$destAbsPath);
        }
        $this->objectManager->moveNode($srcAbsPath, $destAbsPath);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function removeItem($absPath)
    {
        $item = $this->getItem($absPath);
        $item->remove();
    }

    /**
     * {@inheritDoc}
     *
     * Wraps the save operation into a transaction if transactions are enabled
     * but we are not currently inside a transaction and rolls back on error.
     *
     * If transactions are disabled, errors on save can lead to partial saves
     * and inconsistent data.
     *
     * @api
     */
    public function save()
    {
        if ($this->getTransport() instanceof TransactionInterface) {
            try {
                $utx = $this->workspace->getTransactionManager();
            } catch (UnsupportedRepositoryOperationException $e) {
                // user transactions where disabled for this session, do no automatic transaction.
            }
        }

        if (isset($utx) && !$utx->inTransaction()) {
            // do the operation in a short transaction
            $utx->begin();
            try {
                $this->objectManager->save();
                $utx->commit();
            } catch (Exception $e) {
                // if anything goes wrong, rollback this mess
                try {
                    $utx->rollback();
                } catch (Exception $rollbackException) {
                    // ignore this exception
                }
                // but do not eat this exception
                throw $e;
            }
        } else {
            $this->objectManager->save();
        }
    }

    /**
     * {@inheritDoc}
     *
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

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function hasPendingChanges()
    {
        return $this->objectManager->hasPendingChanges();
    }

    /**
     * {@inheritDoc}
     *
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

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function checkPermission($absPath, $actions)
    {
        if (! $this->hasPermission($absPath, $actions)) {
            throw new AccessControlException($absPath);
        }
    }

    /**
     * {@inheritDoc}
     *
     * Jackalope does currently not check anything and always return true.
     *
     * @api
     */
    public function hasCapability($methodName, $target, array $arguments)
    {
        //we never determine whether operation can be performed as it is optional ;-)
        //TODO: could implement some
        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function importXML($parentAbsPath, $uri, $uuidBehavior)
    {
        ImportExport::importXML(
            $this->getNode($parentAbsPath),
            $this->workspace->getNamespaceRegistry(),
            $uri,
            $uuidBehavior);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function exportSystemView($absPath, $stream, $skipBinary, $noRecurse)
    {
        ImportExport::exportSystemView(
            $this->getNode($absPath),
            $this->workspace->getNamespaceRegistry(),
            $stream,
            $skipBinary,
            $noRecurse);
    }


    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function exportDocumentView($absPath, $stream, $skipBinary, $noRecurse)
    {
        ImportExport::exportDocumentView(
            $this->getNode($absPath),
            $this->workspace->getNamespaceRegistry(),
            $stream,
            $skipBinary,
            $noRecurse);
    }



    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setNamespacePrefix($prefix, $uri)
    {
        $this->namespaceRegistry->checkPrefix($prefix);
        throw new NotImplementedException('TODO: implement session scope remapping of namespaces');
        //this will lead to rewrite all names and paths in requests and replies. part of this can be done in ObjectManager::normalizePath
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getNamespacePrefixes()
    {
        //TODO: once setNamespacePrefix is implemented, must take session remaps into account
        return $this->namespaceRegistry->getPrefixes();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getNamespaceURI($prefix)
    {
        //TODO: once setNamespacePrefix is implemented, must take session remaps into account
        return $this->namespaceRegistry->getURI($prefix);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getNamespacePrefix($uri)
    {
        //TODO: once setNamespacePrefix is implemented, must take session remaps into account
        return $this->namespaceRegistry->getPrefix($uri);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function logout()
    {
        //OPTIMIZATION: flush object manager to help garbage collector
        $this->logout = true;
        if ($this->getRepository()->getDescriptor(RepositoryInterface::OPTION_LOCKING_SUPPORTED)) {
            $this->getWorkspace()->getLockManager()->logout();
        }
        self::unregisterSession($this);
        $this->getTransport()->logout();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isLive()
    {
        return ! $this->logout;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getAccessControlManager()
    {
        throw new UnsupportedRepositoryOperationException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getRetentionManager()
    {
        throw new UnsupportedRepositoryOperationException();
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
     * @param Session $session the session to register
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
     * @param Session $session the session to unregister
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
     * @return string an id for this session
     */
    public function getRegistryKey()
    {
        return spl_object_hash($this);
    }

    /**
     * Implementation specific: get a session from the session registry for the
     * stream wrapper.
     *
     * @param string $key key for the session
     *
     * @return Session|null the session or null if none is registered with the given key
     *
     * @private
     */
    public static function getSessionFromRegistry($key)
    {
        if (isset(self::$sessionRegistry[$key])) {
            return self::$sessionRegistry[$key];
        }

        return null;
    }

    /**
     * Sets a session specific option.
     *
     * @param string $key   the key to be set
     * @param mixed  $value the value to be set
     *
     * @throws InvalidArgumentException if the option is unknown
     * @throws RepositoryException      if this option is not supported and is
     *      a behaviour relevant option
     *
     * @see Jackalope\Transport\BaseTransport::setFetchDepth($value);
     */

    public function setSessionOption($key, $value)
    {
        switch ($key) {
            case self::OPTION_FETCH_DEPTH:
                $this->getTransport()->setFetchDepth($value);
                break;
            case self::OPTION_AUTO_LASTMODIFIED:
                $this->getTransport()->setAutoLastModified($value);
                break;
            default:
                throw new InvalidArgumentException("Unknown option: $key");
        }
    }

    /**
     * Gets a session specific option.
     *
     * @param string $key the key to be gotten
     *
     * @throws InvalidArgumentException if the option is unknown
     *
     * @see setSessionOption($key, $value);
     */
    public function getSessionOption($key)
    {
        switch ($key) {
            case self::OPTION_FETCH_DEPTH:
                return $this->getTransport()->getFetchDepth();
            case self::OPTION_AUTO_LASTMODIFIED:
                return $this->getTransport()->getAutoLastModified();
        }

        throw new InvalidArgumentException("Unknown option: $key");
    }
}
