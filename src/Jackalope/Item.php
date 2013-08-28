<?php

namespace Jackalope;

use LogicException;
use PHPCR\Util\PathHelper;

use PHPCR\PropertyInterface;
use PHPCR\ItemInterface;
use PHPCR\ItemVisitorInterface;
use PHPCR\RepositoryInterface;
use PHPCR\RepositoryException;
use PHPCR\ItemNotFoundException;
use PHPCR\InvalidItemStateException;
use PHPCR\Util\ValueConverter;

/**
 * {@inheritDoc}
 *
 * <b>Jackalope Implementation:</b> The item has a state machine to track in
 * what state it currently is. All API exposed methods must call
 * Item::checkState() before doing anything.
 *
 * Most important is that everything that is in state deleted can not be used
 * anymore (will detect logic errors in client code) and that if the item needs
 * to be refreshed from the backend, this can be postponed until the item is
 * actually accessed again.
 *
 * <img src="https://fosswiki.liip.ch/download/attachments/11501816/Jackalope-Node-State.png" />
 *
 * <em>Figure: workflow state transitions</em>
 *
 * For the special case of Item state after a failed transaction, see Item::rollbackTransaction()
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 */
abstract class Item implements ItemInterface
{
    /**
     * The item needs to be created in the backend on Session::save()
     *
     * Item::isNew() returns true.
     */
    const STATE_NEW = 0;

    /**
     * The item needs to be refreshed before using it the next time.
     * Item::checkState will refresh it and set the state to clean.
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

    /** @var boolean To know whether to keep changes or not when reloading in dirty state */
    protected $keepChanges = false;

    /**
     * @var int    The state of the item saved when a transaction is started
     *
     * @see Item::rollbackTransaction()
     */
    protected $savedState;

    /**
     * @var int The state to take after this dirty node has been refreshed. One of the STATE_ constants
     */
    protected $postDirtyState = -1;

    /** @var array  The states an Item can take */
    protected $available_states = array(
        self::STATE_NEW,
        self::STATE_DIRTY,
        self::STATE_CLEAN,
        self::STATE_MODIFIED,
        self::STATE_DELETED,
    );

    /** @var FactoryInterface   The jackalope object factory for this object */
    protected $factory;

    /** @var Session    The session this item belongs to */
    protected $session;

    /** @var ObjectManager  The object manager to get nodes and properties from */
    protected $objectManager;

    /** @var ValueConverter */
    protected $valueConverter;

    /** @var bool   false if item is read from backend, true if created locally in this session */
    protected $new;

    /** @var string     the node or property name*/
    protected $name;

    /** @var string     Normalized and absolute path to this item. */
    protected $path;

    /** @var string     While this item is moved but unsaved, stores the old path for refresh. */
    protected $oldPath = null;

    /** @var string     Normalized and absolute path to the parent item for convenience. */
    protected $parentPath;

    /** @var int    Depth in the workspace graph */
    protected $depth;

    /** @var bool   Whether this item is a node (otherwise it is a property) */
    protected $isNode = false;

    /**
     * Initialize basic information common to nodes and properties
     *
     * @param FactoryInterface $factory       the object factory
     * @param string           $path          The normalized and absolute path to this item
     * @param Session          $session
     * @param ObjectManager    $objectManager
     * @param boolean          $new           can be set to true to tell the object that it has
     *      been created locally
     */
    protected function __construct(FactoryInterface $factory, $path, Session $session, ObjectManager $objectManager, $new = false)
    {
        $this->factory = $factory;
        $this->valueConverter = $this->factory->get('PHPCR\Util\ValueConverter');
        $this->session = $session;
        $this->objectManager = $objectManager;
        $this->setState($new ? self::STATE_NEW : self::STATE_CLEAN);
        if (! $new
            && $session->getRepository()->getDescriptor(RepositoryInterface::OPTION_TRANSACTIONS_SUPPORTED)
        ) {
            if ($session->getWorkspace()->getTransactionManager()->inTransaction()) {
                // properly set previous state in case we get into a rollback
                $this->savedState = self::STATE_CLEAN;
            }
        }

        $this->setPath($path);
    }

    /**
     * Set or update the path, depth, name and parent reference
     *
     * @param string  $path the new path this item lives at
     * @param boolean $move whether this item is being moved in session context
     *      and should store the current path until the next save operation.
     *
     * @private
     */
    public function setPath($path, $move = false)
    {
        if ($move && is_null($this->oldPath)) {
            try {
                $this->checkState();
            } catch (InvalidItemStateException $e) {
                // do not break if object manager tells the move to a child that was removed in backend
                return;
            }
            $this->oldPath = $this->path;
        }
        $this->path = $path;
        $this->depth = ('/' === $path) ? 0 : substr_count($path, '/');
        $this->name = PathHelper::getNodeName($path);
        $this->parentPath = (0 === $this->depth) ? null : PathHelper::getParentPath($path);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getPath()
    {
        $this->checkState();

        return $this->path;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getName()
    {
        $this->checkState();

        return $this->name;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getAncestor($depth)
    {
        $this->checkState();

        if ($depth < 0 || $depth > $this->depth) {
            throw new ItemNotFoundException('Depth must be between 0 and '.$this->depth.' for this Item');
        }
        if ($depth == $this->depth) {
            return $this;
        }
        // we do not use the PathHelper as this is a special case
        $ancestorPath = '/'.implode('/', array_slice(explode('/', $this->path), 1, $depth));

        return $this->objectManager->getNodeByPath($ancestorPath);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getParent()
    {
        $this->checkState();
        if (is_null($this->parentPath)) {
            throw new ItemNotFoundException('The root node has no parent');
        }

        return $this->objectManager->getNodeByPath($this->parentPath);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getDepth()
    {
        $this->checkState();

        return $this->depth;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getSession()
    {
        $this->checkState();

        return $this->session;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isNode()
    {
        $this->checkState();

        return $this->isNode;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isNew()
    {
        return self::STATE_NEW === $this->state;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isModified()
    {
        return self::STATE_MODIFIED === $this->state
            || self::STATE_DIRTY === $this->state && self::STATE_MODIFIED === $this->postDirtyState;
    }

    /**
     * {@inheritDoc}
     *
     * @private
     */
    public function isMoved()
    {
        return isset($this->oldPath);
    }

    /**
     * Whether this item is in state dirty.
     *
     * Returns true if this Item has been marked dirty (i.e. being saved) and
     * has not been refreshed since.
     *
     * The in-memory representation of the item in memory might not reflect the
     * current state in the backend (for instance if mix:referenceable mixin
     * type has been added to the item the backend creates a UUID on save).
     *
     * @return boolean
     *
     * @private
     */
    public function isDirty()
    {
        return self::STATE_DIRTY === $this->state;
    }

    /**
     * Whether this item has been deleted and can not be used anymore.
     *
     * @return boolean
     * @private
     */
    public function isDeleted()
    {
        return self::STATE_DELETED === $this->state;
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
        return self::STATE_CLEAN === $this->state;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isSame(ItemInterface $otherItem)
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
                // assert($this instanceof Property)
            } elseif ($this->name == $otherItem->getName()
                && $this->getParent()->isSame($otherItem->getParent())
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function accept(ItemVisitorInterface $visitor)
    {
        $this->checkState();

        $visitor->visit($this);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function remove()
    {
        $this->checkState(); // To avoid the possibility to delete an already deleted node

        // sanity checks
        if ($this->getDepth() == 0) {
            throw new RepositoryException('Cannot remove root node');
        }

        if ($this instanceof PropertyInterface) {
            $this->objectManager->removeItem($this->parentPath, $this);
        } else {
            $this->objectManager->removeItem($this->path);
        }

        // TODO same-name siblings reindexing

        $this->setDeleted();
    }

    /**
     * Tell this item that it has been modified.
     *
     * This will do nothing if the node is new, to avoid duplicating store commands.
     *
     * @private
     */
    public function setModified()
    {
        if (! $this->isNew()) {
            $this->setState(self::STATE_MODIFIED);
        }
    }

    /**
     * Tell this item that it is dirty and needs to be refreshed
     *
     * @param boolean $keepChanges whether to keep changes when reloading or not
     *
     * @private
     */
    public function setDirty($keepChanges = false, $targetState = false)
    {
        if (false === $targetState) {
            $targetState = $keepChanges ? $this->getState() : self::STATE_CLEAN;
        }
        switch ($targetState) {
            case self::STATE_DIRTY:
                break;
            case self::STATE_CLEAN:
            case self::STATE_MODIFIED:
                $this->postDirtyState = $targetState;
                break;
            default:
                throw new RepositoryException('Setting item ' . $this->path . ' dirty in state ' . $this->getState() . ' is not expected');
        }

        $this->keepChanges = $keepChanges;
        $this->setState(self::STATE_DIRTY);
    }

    /**
     * Tell this item it has been deleted and cannot be used anymore
     *
     * @private
     */
    public function setDeleted()
    {
        $this->setState(self::STATE_DELETED);
    }

    /**
     * Tell this item it is clean (i.e. it has been refreshed after a modification)
     *
     * @private
     */
    public function setClean()
    {
        $this->setState(self::STATE_CLEAN);
    }

    /**
     * notify this item that it has been saved into the backend.
     * allowing it to clear the modified / new flags
     *
     * @private
     */
    public function confirmSaved()
    {
        $this->oldPath = null; // in case this item has been moved
        $this->setDirty(false, self::STATE_CLEAN);
    }

    /**
     * Get the state of the item
     *
     * @return int one of the state constants
     *
     * @private
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function revert()
    {
        $this->refresh(false);
    }

    /**
     * Updates the state of the current item.
     *
     * In JSR-283 this was part of the interface. While JSR-333 deprecated
     * the refresh() method and replaces it with revert(), the functionality
     * is still needed as Session::refresh() has been kept.
     *
     * If keepChanges is false, this method discards all pending changes
     * currently recorded in this Session that apply to this Item or any
     * of its descendants (that is, the subgraph rooted at this Item) and
     * returns all items to reflect the current saved state. Outside a
     * transaction this state is simply the current state of persistent
     * storage. Within a transaction, this state will reflect persistent
     * storage as modified by changes that have been saved but not yet
     * committed.
     *
     * If keepChanges is true then pending change are not discarded but
     * items that do not have changes pending have their state refreshed
     * to reflect the current saved state, thus revealing changes made by
     * other sessions.
     *
     * @param boolean $keepChanges a boolean
     *
     * @throws InvalidItemStateException if this Item object represents
     *      a workspace item that has been removed (either by this session or
     *      another).
     * @throws RepositoryException if another error occurs.
     */
    abstract protected function refresh($keepChanges);

    /**
     * Change the state of the item
     *
     * @param int $state The new item state, one of the state constants
     *
     * @throws RepositoryException
     *
     * @private
     */
    private function setState($state)
    {
        if (! in_array($state, $this->available_states)) {
            throw new RepositoryException("Invalid state [$state]");
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
        if (self::STATE_MODIFIED  === $state && self::STATE_CLEAN === $this->savedState) {
            $this->savedState = self::STATE_MODIFIED;
        }
    }

    /**
     * This function will modify the state of the item as well as refresh it if necessary (i.e.
     * if it is DIRTY).
     *
     * @throws InvalidItemStateException When an operation is attempted on a deleted item
     *
     * @private
     */
    protected function checkState()
    {
        if ($this->isDirty()) {
            $this->refresh($this->keepChanges);

            // check whether node|property updated state
            if ($this->isDirty()) {
                $this->setState($this->postDirtyState);
            }
            $this->keepChanges = false;
            $this->postDirtyState = -1;
        }

        if ($this->state === self::STATE_DELETED) {
            throw new InvalidItemStateException('Item '.$this->path.' is deleted');
        }

        // For all the other cases, the state does not change
    }

    /**
     * Manage item state when transaction starts. This method is called on
     * every cached item by the ObjectManager.
     *
     * Saves the current item state in case a rollback occurs.
     *
     * @private
     *
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
     * @private
     *
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
     *      modified after transaction start and successfully saved, ending up with clean state again. it has to be
     *      modified as its different from the backend value.
     * </td></tr>
     * <tr><td>8</td><td>CLEAN      </td><td>DIRTY   </td><td>   DIRTY</td></tr>
     * <tr><td>9</td><td>DIRTY      </td><td>*       </td><td>   DIRTY</td></tr>
     * </table>
     *
     * @throws LogicException if an unexpected state transition is encountered
     *
     * @private
     *
     * @see ObjectManager::rollbackTransaction()
     */
    public function rollbackTransaction()
    {
        if (is_null($this->savedState)) {
            $this->savedState = self::STATE_NEW;
        }

        if (self::STATE_DELETED === $this->state || self::STATE_DELETED === $this->savedState) {

            // Case 1) and 2)
            $this->state = self::STATE_DELETED;

        } elseif (self::STATE_NEW === $this->savedState) {

            // Case 3)
            $this->state = self::STATE_NEW;

        } elseif (self::STATE_MODIFIED  === $this->state || self::STATE_MODIFIED === $this->savedState) {

            // Case 4) and 5)
            $this->state = self::STATE_MODIFIED;

        } elseif (self::STATE_CLEAN === $this->savedState) {

            if (self::STATE_CLEAN === $this->state) {

                // Case 6) and 7), see the comment in the function setState()
                $this->state = $this->savedState;

            } elseif (self::STATE_DIRTY === $this->state) {

                // Case 8)
                $this->state = self::STATE_DIRTY;
            }

        } elseif (self::STATE_DIRTY === $this->savedState) {

            // Case 9)
            $this->state = self::STATE_DIRTY;

        } else {

            // There might be some special case we do not handle. for the moment throw an exception
            throw new LogicException("There was an unexpected state transition during the transaction: " .
                                      "old state = {$this->savedState}, new state = {$this->state}");
        }

        // Reset the saved state
        $this->savedState = null;
    }
}
