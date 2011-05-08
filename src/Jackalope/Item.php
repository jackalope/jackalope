<?php
namespace Jackalope;

/**
 * The Item is the base interface of Node and Property.
 * This class implements methods for both types.
 * It should not be instantiated directly.
 *
 * @api
 */
class Item implements \PHPCR\ItemInterface
{
    /** @var Factory   The jackalope object factory for this object */
    protected $factory;

    /** @var Session    The session this item belongs to */
    protected $session;

    /** @var ObjectManager  The object manager to get nodes and properties from */
    protected $objectManager;

    /** @var bool   false if item is read from backend, true if created locally in this session */
    protected $new;

    /** @var bool   True if modified otherwise false */
    protected $modified = false;

    /** @var string */
    protected $name;

    /** @var string     Normalized and absolute path to this item. */
    protected $path;

    /** @var string     Normalized and absolute path to the parent item. */
    protected $parentPath;

    /** @var int    Depth in the workspace graph */
    protected $depth;

    /** @var bool   Whether this item is a node  */
    protected $isNode = false;

    /**
     * @param object $factory  an object factory implementing "get" as described in \jackalope\Factory
     * @param string    $path   The normalized and absolute path to this item
     * @param Session $session
     * @param ObjectManager $objectManager
     * @param boolean $new can be set to true to tell the object that it has been created locally
     */
    public function __construct($factory, $path,  Session $session,
                                ObjectManager $objectManager, $new = false)
    {
        $this->factory = $factory;
        $this->session = $session;
        $this->objectManager = $objectManager;
        $this->new = $new;

        $this->setPath($path);
    }

    protected function setPath($path) {
        $this->path = $path;
        $this->depth = $path === '/' ? 0 : substr_count($path, '/');
        $this->name = basename($path);
        $this->parentPath = strtr(dirname($path), '\\', '/');
    }

    /**
     * Returns the normalized absolute path to this item.
     *
     * @return string the normalized absolute path of this Item.
     * @throws \PHPCR\RepositoryException if an error occurs.
     * @api
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Returns the name of this Item in qualified form. If this Item is the root
     * node of the workspace, an empty string is returned.
     *
     * @return string the name of this Item in qualified form or an empty string if this Item is the root node of a workspace.
     * @throws \PHPCR\RepositoryException if an error occurs.
     * @api
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the ancestor of this Item at the specified depth. An ancestor of
     * depth x is the Item that is x levels down along the path from the root
     * node to this Item.
     *
     * * depth = 0 returns the root node of a workspace.
     * * depth = 1 returns the child of the root node along the path to this Item.
     * * depth = 2 returns the grandchild of the root node along the path to this Item.
     * * And so on to depth = n, where n is the depth of this Item, which returns this Item itself.
     *
     * If this node has more than one path (i.e., if it is a descendant of a
     * shared node) then the path used to define the ancestor is implementaion-
     * dependent.
     *
     * @param integer $depth An integer, 0 <= depth <= n where n is the depth of this Item.
     * @return \PHPCR\ItemInterface The ancestor of this Item at the specified depth.
     * @throws \PHPCR\ItemNotFoundException if depth &lt; 0 or depth &gt; n where n is the depth of this item.
     * @throws \PHPCR\AccessDeniedException if the current session does not have sufficient access to retrieve the specified node.
     * @throws \PHPCR\RepositoryException if another error occurs.
     * @api
     */
    public function getAncestor($depth)
    {
        if ($depth < 0 || $depth > $this->depth) {
            throw new \PHPCR\ItemNotFoundException('Depth must be between 0 and '.$this->depth.' for this Item');
        }
        if ($depth == $this->depth) {
            return $this;
        }
        $ancestorPath = '/'.implode('/', array_slice(explode('/', $this->path), 1, $depth));
        return $this->objectManager->getNodeByPath($ancestorPath);
    }

    /**
     * Returns the parent of this Item.
     *
     * @return \PHPCR\NodeInterface The parent of this Item.
     * @throws \PHPCR\ItemNotFoundException if this Item< is the root node of a workspace.
     * @throws \PHPCR\AccessDeniedException if the current session does not have sufficent access to retrieve the parent of this item.
     * @throws \PHPCR\RepositoryException if another error occurs.
     * @api
     */
    public function getParent()
    {
        return $this->objectManager->getNodeByPath($this->parentPath);
    }

    /**
     * Returns the depth of this Item in the workspace item graph.
     *
     * * The root node returns 0.
     * * A property or child node of the root node returns 1.
     * * A property or child node of a child node of the root returns 2.
     * * And so on to this Item.
     *
     * @return integer The depth of this Item in the workspace item graph.
     * @throws \PHPCR\RepositoryException if an error occurs.
     * @api
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     * Returns the Session through which this Item was acquired.
     *
     * @return \PHPCR\SessionInterface the Session through which this Item was acquired.
     * @throws \PHPCR\RepositoryException if an error occurs.
     * @api
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Indicates whether this Item is a Node or a Property. Returns true if
     * this Item is a Node; Returns false if this Item is a Property.
     *
     * @return boolean true if this Item is a Node, false if it is a Property.
     * @api
     */
    public function isNode()
    {
        return $this->isNode;
    }

    /**
     * Returns true if this is a new item, meaning that it exists only in
     * transient storage on the Session and has not yet been saved. Within a
     * transaction, isNew on an Item may return false (because the item has
     * been saved) even if that Item is not in persistent storage (because the
     * transaction has not yet been committed).
     *
     * Note that if an item returns true on isNew, then by definition is parent
     * will return true on isModified.
     *
     * Note that in read-only implementations, this method will always return
     * false.
     *
     * @return boolean true if this item is new; false otherwise.
     * @api
     */
    public function isNew()
    {
        return $this->new;
    }

    /**
     * Returns true if this Item has been saved but has subsequently been
     * modified through the current session and therefore the state of this
     * item as recorded in the session differs from the state of this item as
     * saved. Within a transaction, isModified on an Item may return false
     * (because the Item has been saved since the modification) even if the
     * modification in question is not in persistent storage (because the
     * transaction has not yet been committed).
     *
     * Note that in read-only implementations, this method will always return
     * false.
     *
     * @return boolean true if this item is modified; false otherwise.
     * @api
     */
    public function isModified()
    {
        return $this->modified;
    }

    /**
     * Returns true if this Item object represents the same actual workspace
     * item as the object otherItem.
     *
     * Two Item objects represent the same workspace item if all the following
     * are true:
     *
     * * Both objects were acquired through Session objects that were created
     *   by the same Repository object.
     * * Both objects were acquired through Session objects bound to the same
     *   repository workspace.
     * * The objects are either both Node objects or both Property
     *   objects.
     * * If they are Node objects, they have the same identifier.
     * * If they are Property objects they have identical names and
     *   isSame() is true of their parent nodes.
     *
     * This method does not compare the states of the two items. For example, if
     * two Item objects representing the same actual workspace item have been
     * retrieved through two different sessions and one has been modified, then
     * this method will still return true when comparing these two objects.
     * Note that if two Item objects representing the same workspace item are
     * retrieved through the same session they will always reflect the same
     * state.
     *
     * @param \PHPCR\ItemInterface $otherItem the Item object to be tested for identity with this Item.
     * @return boolean true if this Item object and otherItem represent the same actual repository item; false otherwise.
     * @throws \PHPCR\RepositoryException if an error occurs.
     * @api
     */
    public function isSame(\PHPCR\ItemInterface $otherItem)
    {
        if ($this === $otherItem) { // trivial case
            return true;
        }
        if ($this->session->getRepository() === $otherItem->getSession()->getRepository() &&
            $this->session->getWorkspace() === $otherItem->getSession()->getWorkspace() &&
            get_class($this) == get_class($otherItem)) {

            if ($this instanceof Node) {
                if ($this->uuid == $otherItem->getIdentifier()) {
                    return true;
                }
            } else { // assert($this instanceof Property)
                if ($this->name == $otherItem->getName() &&
                    $this->getParent()->isSame($otherItem->getParent())) {
                        return true;
                }
            }
        }
        return false;
    }

    /**
     * Accepts an ItemVisitor, calls visit on it
     *
     * @param \PHPCR\ItemVisitorInterface $visitor The ItemVisitor to be accepted.
     * @throws \PHPCR\RepositoryException if an error occurs.
     * @api
     */
    public function accept(\PHPCR\ItemVisitorInterface $visitor)
    {
        $visitor->visit($this);
    }

    /**
     * If keepChanges is false, this method discards all pending changes
     * currently recorded in this Session that apply to this Item or any
     * of its descendants (that is, the subgraph rooted at this Item) and
     * returns all items to reflect the current saved state. Outside a
     * transaction this state is simple the current state of persistent
     * storage. Within a transaction, this state will reflect persistent
     * storage as modified by changes that have been saved but not yet
     * committed.
     * If keepChanges is true then pending change are not discarded but
     * items that do not have changes pending have their state refreshed
     * to reflect the current saved state, thus revealing changes made by
     * other sessions.
     *
     * @param boolean $keepChanges a boolean
     * @return void
     * @throws \PHPCR\InvalidItemStateException if this Item object represents a workspace item that has been removed (either by this session or another).
     * @throws \PHPCR\RepositoryException if another error occurs.
     * @api
     */
    public function refresh($keepChanges)
    {
        throw new NotImplementedException('Write');
    }

    /**
     * Removes this item (and its subgraph).
     *
     * To persist a removal, a save must be performed that includes the (former)
     * parent of the removed item within its scope.
     *
     * If a node with same-name siblings is removed, this decrements by one the
     * indices of all the siblings with indices greater than that of the removed
     * node. In other words, a removal compacts the array of same-name siblings
     * and causes the minimal re-numbering required to maintain the original
     * order but leave no gaps in the numbering.
     *
     * @return void
     * @throws \PHPCR\Version\VersionException if the parent node of this item is versionable and checked-in or is non-versionable but its nearest versionable ancestor is checked-in and this implementation performs this validation immediately instead of waiting until save.
     * @throws \PHPCR\Lock\LockException if a lock prevents the removal of this item and this implementation performs this validation immediately instead of waiting until save.
     * @throws \PHPCR\ConstraintViolationException if removing the specified item would violate a node type or implementation-specific constraint and this implementation performs this validation immediately instead of waiting until save.
     * @throws \PHPCR\AccessDeniedException if this item or an item in its subgraph is currently the target of a REFERENCE property located in this workspace but outside this item's subgraph and the current Session does not have read access to that REFERENCE property or if the current Session does not have sufficent privileges to remove the item.
     * @throws \PHPCR\RepositoryException if another error occurs.
     * @see SessionInterface::removeItem(String)
     * @api
     */
    public function remove()
    {
        // sanity checks
        if ($this->getDepth() == 0) {
            throw new \PHPCR\RepositoryException('Cannot remove root node');
        }

        // TODO: add sanity checks to all other write methods to avoid modification after deleting?
        // TODO same-name siblings reindexing
        if ($this instanceof \PHPCR\PropertyInterface) {
            $this->objectManager->removeItem($this->parentPath, $this->name);
        } else {
            $this->objectManager->removeItem($this->path);
        }
        $this->getParent()->setModified();
    }

    /**
     * Tell this item that it has been modified.
     * Used when deleting a node to tell the parent node about modification.
     */
    public function setModified()
    {
        $this->modified = true;
    }

    /**
     * notify this item that it has been saved into the backend.
     * allowing it to clear the modified / new flags
     */
    public function confirmSaved()
    {
        $this->new = false;
        $this->modified = false;
    }
}
