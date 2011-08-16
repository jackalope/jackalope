<?php
namespace Jackalope;

/**
 * Item base class with common functionality
 *
 * Implementation: The item has a state machine to track in what state it
 * currently is. All API exposed methods must call Item::checkState() before
 * doing anything.
 * Most important is that everything that is in state deleted can not be used
 * anymore (will detect logic errors in client code) and that if the item needs
 * to be reloaded from the backend, this can be postponed until the item is
 * actually accessed again.
 *
 * <img src="https://fosswiki.liip.ch/download/attachments/11501816/Jackalope-Node-State.png" />
 * <em>Figure: workflow state transitions</em>
 *
 * For the special case of Item state after a failed transaction, see Item::rollbackTransaction()
 */
abstract class Item implements \PHPCR\ItemInterface
{
    /**
     * The item needs to be created in the backend on Session::save()
     *
     * Item::isNew() returns true.
     */
    const STATE_NEW = 0;

    /**
     * The item needs to be reloaded before using it the next time.
     * Item::checkState will reload it and set the state to clean.
     */
    const STATE_DIRTY = 1;

    /**
     * The item is fully synchronized with the backend and usable.
     */
    const STATE_CLEAN = 2;

    /**
     * The item has been modified locally and needs to be saved to the backend
     * on Session::save()
     */
    const STATE_MODIFIED = 3;

    /**
     * The item has been deleted and may not be accessed in any way anymore
     */
    const STATE_DELETED = 4;

    /** @var int    The state of the item, one of the STATE_ constants */
    protected $state;

    /**
     * @var int    The state of the item saved when a transaction is started
     *
     * @see Item::rollbackTransaction()
     */
    protected $savedState;

    /** @var array  The states an Item can take */
    protected $available_states = array(
        self::STATE_NEW,
        self::STATE_DIRTY,
        self::STATE_CLEAN,
        self::STATE_MODIFIED,
        self::STATE_DELETED,
    );

    /** @var Factory   The jackalope object factory for this object */
    protected $factory;

    /** @var Session    The session this item belongs to */
    protected $session;

    /** @var ObjectManager  The object manager to get nodes and properties from */
    protected $objectManager;

    /** @var bool   false if item is read from backend, true if created locally in this session */
    protected $new;

    /** @var string     the node or property name*/
    protected $name;

    /** @var string     Normalized and absolute path to this item. */
    protected $path;

    /** @var string     Normalized and absolute path to the parent item for convenience. */
    protected $parentPath;

    /** @var int    Depth in the workspace graph */
    protected $depth;

    /** @var bool   Whether this item is a node (otherwise it is a property) */
    protected $isNode = false;

    /**
     * Initialize basic information common to nodes and properties
     *
     * @param object $factory  an object factory implementing "get" as described in \Jackalope\Factory
     * @param string    $path   The normalized and absolute path to this item
     * @param Session $session
     * @param ObjectManager $objectManager
     * @param boolean $new can be set to true to tell the object that it has been created locally
     */
    protected function __construct($factory, $path,  Session $session,
                                ObjectManager $objectManager, $new = false)
    {
        $this->factory = $factory;
        $this->session = $session;
        $this->objectManager = $objectManager;

        $this->setState($new ? self::STATE_NEW : self::STATE_CLEAN);

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
        $this->checkState();
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
        $this->checkState();
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
        $this->checkState();

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
        $this->checkState();
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
        $this->checkState();
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
        $this->checkState();
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
        $this->checkState();
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
        return $this->state === self::STATE_NEW;
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
        return $this->state === self::STATE_MODIFIED;
    }

    /**
     * Whether this item is in state dirty.
     *
     * Returns true if this Item has been marked dirty (i.e. being saved) and
     * has not been reloaded since.
     *
     * The in-memory representation of the item in memory might not reflect the
     * current state in the backend (for instance if mix:referenceable mixin
     * type has been added to the item the backend creates a UUID on save).
     *
     * @return boolean
     * @private
     */
    public function isDirty()
    {
        return $this->state === self::STATE_DIRTY;
    }

    /**
     * Whether this item has been deleted and can not be used anymore.
     *
     * @return boolean
     * @private
     */
    public function isDeleted()
    {
        return $this->state === self::STATE_DELETED;
    }

    /**
     * Whether this item is in STATE_CLEAN (meaning its data is fully
     * synchronized with the backend)
     *
     * @return boolean
     * @private
     */
    public function isClean()
    {
        return $this->state === self::STATE_CLEAN;
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
        $this->checkState();

        if ($this === $otherItem) { // trivial case
            return true;
        }
        if ($this->session->getRepository() === $otherItem->getSession()->getRepository()
            && $this->session->getWorkspace() === $otherItem->getSession()->getWorkspace()
            && get_class($this) == get_class($otherItem)
        ) {
            if ($this instanceof Node) {
                if ($this->uuid == $otherItem->getIdentifier()) {
                    return true;
                }
            } else { // assert($this instanceof Property)
                if ($this->name == $otherItem->getName()
                    && $this->getParent()->isSame($otherItem->getParent())
                ) {
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
        $this->checkState();

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
        $this->checkState(); // To avoid the possibility to delete an already deleted node

        // sanity checks
        if ($this->getDepth() == 0) {
            throw new \PHPCR\RepositoryException('Cannot remove root node');
        }

        // TODO add sanity checks to all other write methods to avoid modification after deleting
        // TODO same-name siblings reindexing
        if ($this instanceof \PHPCR\PropertyInterface) {
            $this->objectManager->removeItem($this->parentPath, $this->name);
        } else {
            $this->objectManager->removeItem($this->path);
        }
        $this->setDeleted();
    }

    /**
     * Tell this item that it has been modified.
     *
     * This will do nothing if the node is new, to avoid duplicating store commands.
     */
    public function setModified()
    {
        if (! $this->isNew()) {
            $this->setState(self::STATE_MODIFIED);
        }
    }

    /**
     * Tell this item that it is dirty and needs to be reloaded
     * @private
     */
    public function setDirty()
    {
        $this->setState(self::STATE_DIRTY);
    }

    /**
     * Tell this item it has been deleted and cannot be used anymore
     * @private
     */
    public function setDeleted()
    {
        $this->setState(self::STATE_DELETED);
    }

    /**
     * Tell this item it is clean (i.e. it has been reloaded after a modification)
     * @private
     */
    public function setClean()
    {
        $this->setState(self::STATE_CLEAN);
    }

    /**
     * notify this item that it has been saved into the backend.
     * allowing it to clear the modified / new flags
     */
    public function confirmSaved()
    {
        $this->setState(self::STATE_DIRTY);
    }

    /**
     * Get the state of the item
     *
     * @return int
     * @private
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Change the state of the item
     *
     * @param int $state The new item state
     * @throws \PHPCR\InvalidItemStateException
     * @private
     */
    private function setState($state)
    {
        if (! in_array($state, $this->available_states)) {
            throw new \PHPCR\RepositoryException("Invalid state [$state]");
        }
        $this->state = $state;

        // -------------------------------------------------------------------------------------
        // see the phpdoc of rollbackTransaction()
        //
        // In the cases 6 and 7, when the state is CLEAN before the TRX and CLEAN at the end of
        // the TRX, the final state will be different if the item has been modified during
        // the TRX or not.
        //
        // The following test covers that special case, if the item is modified during the TRX,
        // we set the saved state to MODIFIED so that it can be restored as it is when the TRX
        // is rolled back.
        if ($state === self::STATE_MODIFIED && $this->savedState === self::STATE_CLEAN) {
            $this->savedState = self::STATE_MODIFIED;
        }
    }

    /**
     * This function will modify the state of the item as well as reload it if necessary (i.e.
     * if it is DIRTY).
     *
     * @return void
     * @throws \PHPCR\InvalidItemStateException When an operation is attempted on a deleted item
     * @private
     */
    protected function checkState()
    {
        if ($this->state === self::STATE_DELETED) {
            throw new \PHPCR\InvalidItemStateException("The item was deleted");
        }

        // Dirty items need to be reloaded
        if ($this->isDirty()) {

            $this->reload();
            $this->setClean();
        }

        // For all the other cases, the state does not change
    }

    /**
     * Manage item state when transaction starts. This method is called on
     * every cached item by the ObjectManager.
     *
     * Saves the current item state in case a rollback occurs.
     *
     * @return void
     * @private
     * @see Item::rollbackTransaction
     */
    public function beginTransaction()
    {
        // Save the item state
        $this->savedState = $this->state;
    }

    /**
     * Clean up state after a transaction. This method is called on every
     * cached item by the ObjectManager.
     *
     * @return void
     * @private
     * @see Item::rollbackTransaction
     */
    public function commitTransaction()
    {
        // Unset the stored state
        $this->savedState = null;
    }

    /**
     * Adjust the correct item state after a transaction rollback. This method
     * is called on every cached item by the ObjectManager.
     *
     * Item state represents the state of an in-memory item. This has nothing
     * to do with the state of the item in the backend.
     *
     * Referring to the JCR spec (21.3 Save vs. Commit) a transaction rollback
     * or commit will not change the in-memory state of items, but only the
     * backend.
     *
     * When a transaction is rolled back, we try to correct the state of
     * in-memory items so that the session could be correctly saved if no more
     * constraint violations remain. Note that this does not fully work yet.
     *
     *
     * On Item::beginTransaction() we save the current state into savedState.
     * On a rollback, we basically go back to the saved state, with a couple of
     * exceptions. The following table shows an ordered list of rules - the
     * first match is used. The * denotes any state.
     *
     * <table>
     * <tr><th>#</th><th>$savedState</th><th>$state  </th><th>Resulting $state</th></tr>
     * <tr><td>1</td><td>DELETED    </td><td>*       </td><td>DELETED</td></tr>
     * <tr><td>2</td><td>*          </td><td>DELETED </td><td>DELETED</td></tr>
     * <tr><td>3</td><td>NEW        </td><td>*       </td><td>NEW</td></tr>
     * <tr><td>4</td><td>*          </td><td>MODIFIED</td><td>MODIFIED</td></tr>
     * <tr><td>5</td><td>MODIFIED   </td><td>*       </td><td>MODIFIED</td></tr>
     * <tr><td>6</td><td>CLEAN      </td><td>CLEAN   </td><td>CLEAN    (if the item was not modified in the TRX)</td></tr>
     * <tr><td>7</td><td>CLEAN      </td><td>CLEAN   </td><td>MODIFIED (if the item was modified in the TRX)</td></tr>
     * <tr><td colspan="4">
     *      note: case 7 is handled in Item::setState() by changing $savedState to MODIFIED if $savedState is CLEAN and
     *      current state changes to MODIFIED Without this special case, we would miss the situation where a clean node is
     *      modified after transaction start and successfully saved, endeding up with clean state again. it has to be
     *      modified as its different from the backend value.
     * </td></tr>
     * <tr><td>8</td><td>CLEAN      </td><td>DIRTY   </td><td>   DIRTY</td></tr>
     * <tr><td>9</td><td>DIRTY      </td><td>*       </td><td>   DIRTY</td></tr>
     * </table>
     *
     * @return void
     * @throws \LogicException if an unexpected state transition is encountered
     * @private
     * @see ObjectManager::rollbackTransaction()
     */
    public function rollbackTransaction()
    {
        if (is_null($this->savedState)) {
            $this->savedState = self::STATE_NEW;
        }

        if ($this->state === self::STATE_DELETED || $this->savedState === self::STATE_DELETED) {

            // Case 1) and 2)
            $this->state = self::STATE_DELETED;

        } elseif ($this->savedState === self::STATE_NEW) {

            // Case 3)
            $this->state = self::STATE_NEW;

        } elseif ($this->state === self::STATE_MODIFIED || $this->savedState === self::STATE_MODIFIED) {

            // Case 4) and 5)
            $this->state = self::STATE_MODIFIED;

        } elseif ($this->savedState === self::STATE_CLEAN) {

            if ($this->state === self::STATE_CLEAN) {

                // Case 6) and 7), see the comment in the function setState()
                $this->state = $this->savedState;

            } elseif ($this->state === self::STATE_DIRTY) {

                // Case 8)
                $this->state = self::STATE_DIRTY;
            }

        } elseif ($this->savedState === self::STATE_DIRTY) {

            // Case 9)
            $this->state = self::STATE_DIRTY;

        } else {

            // There is some special case we didn't think of, for the moment throw an exception
            // TODO: figure out if this might happen or not
            throw new \LogicException("There was an unexpected state transition during the transaction: " .
                                      "old state = {$this->savedState}, new state = {$this->state}");
        }

        // Reset the saved state
        $this->savedState = null;
    }

    /**
     * Reload the current item from the backend.
     *
     * @private
     */
    protected abstract function reload();
}
