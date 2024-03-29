<?php

namespace Jackalope;

use PHPCR\InvalidItemStateException;
use PHPCR\ItemInterface;
use PHPCR\ItemNotFoundException;
use PHPCR\ItemVisitorInterface;
use PHPCR\NodeInterface;
use PHPCR\NodeType\ItemDefinitionInterface;
use PHPCR\PropertyInterface;
use PHPCR\RepositoryException;
use PHPCR\RepositoryInterface;
use PHPCR\SessionInterface;
use PHPCR\Util\PathHelper;
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
 * <img src="http://jackalope.github.io/doc/Jackalope-Node-State.png" />
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
     * The item needs to be created in the backend on Session::save().
     *
     * Item::isNew() returns true.
     */
    public const STATE_NEW = 0;

    /**
     * The item needs to be refreshed before using it the next time.
     * Item::checkState will refresh it and set the state to clean.
     */
    public const STATE_DIRTY = 1;

    /**
     * The item is fully synchronized with the backend and usable.
     */
    public const STATE_CLEAN = 2;

    /**
     * The item has been modified locally and needs to be saved to the backend on Session::save().
     */
    public const STATE_MODIFIED = 3;

    /**
     * The item has been deleted and may not be accessed in any way anymore.
     */
    public const STATE_DELETED = 4;

    private const STATES = [
        self::STATE_NEW,
        self::STATE_DIRTY,
        self::STATE_CLEAN,
        self::STATE_MODIFIED,
        self::STATE_DELETED,
    ];

    /**
     * @var int The state of the item, one of the STATE_ constants
     */
    private int $state;

    /**
     * @var int The state to take after this dirty node has been refreshed. One of the STATE_ constants
     */
    private int $postDirtyState = -1;

    /**
     * @var int|null The state of the item saved when a transaction is started
     *
     * @see Item::rollbackTransaction()
     */
    private ?int $savedState = null;

    protected FactoryInterface $factory;
    protected Session $session;
    protected ObjectManager $objectManager;
    protected ValueConverter $valueConverter;

    /**
     * @var bool To know whether to keep changes or not when reloading in dirty state
     */
    private bool $keepChanges = false;

    /**
     * @var string the node or property name
     */
    protected string $name;

    /**
     * @var string normalized and absolute path to this item
     */
    protected string $path;

    /**
     * @var string|null while this item is moved but unsaved, stores the old path for refresh
     */
    protected ?string $oldPath = null;

    /**
     * @var string|null normalized and absolute path to the parent item, or null if item already is the root node
     */
    protected ?string $parentPath;

    /**
     * @var int Depth in the workspace graph
     */
    private int $depth;

    /**
     * @var bool Whether this item is a node (otherwise it is a property)
     */
    protected bool $isNode = false;

    /**
     * Initialize basic information common to nodes and properties.
     *
     * @param bool $new can be set to true to tell the object that it has
     *                  been created locally
     *
     * @throws RepositoryException
     */
    protected function __construct(
        FactoryInterface $factory,
        string $path,
        Session $session,
        ObjectManager $objectManager,
        bool $new
    ) {
        $this->factory = $factory;
        $this->valueConverter = $this->factory->get(ValueConverter::class);
        $this->session = $session;
        $this->objectManager = $objectManager;
        $this->setState($new ? self::STATE_NEW : self::STATE_CLEAN);
        if (!$new
            && $session->getRepository()->getDescriptor(RepositoryInterface::OPTION_TRANSACTIONS_SUPPORTED)
            && $session->getWorkspace()->getTransactionManager()->inTransaction()
        ) {
            // properly set previous state in case we get into a rollback
            $this->savedState = self::STATE_CLEAN;
        }

        $this->setPath($path);
    }

    /**
     * Set or update the path, depth, name and parent reference.
     *
     * @param string $path the new path this item lives at
     * @param bool   $move whether this item is being moved in session context
     *                     and should store the current path until the next save operation
     *
     * @private
     */
    public function setPath(string $path, bool $move = false): void
    {
        if ($move && null === $this->oldPath) {
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
        $this->parentPath = 0 === $this->depth ? null : PathHelper::getParentPath($path);
    }

    /**
     * @api
     */
    public function getPath(): string
    {
        $this->checkState();

        return $this->path;
    }

    /**
     * @api
     */
    public function getName(): string
    {
        $this->checkState();

        return $this->name;
    }

    /**
     * @api
     */
    public function getAncestor($depth): ItemInterface
    {
        $this->checkState();

        if ($depth < 0 || $depth > $this->depth) {
            throw new ItemNotFoundException('Depth must be between 0 and '.$this->depth.' for this Item');
        }
        if ($depth === $this->depth) {
            return $this;
        }
        // we do not use the PathHelper as this is a special case
        $ancestorPath = '/'.implode('/', array_slice(explode('/', $this->path), 1, $depth));

        return $this->objectManager->getNodeByPath($ancestorPath);
    }

    /**
     * @api
     */
    public function getParent(): NodeInterface
    {
        $this->checkState();

        if (is_null($this->parentPath)) {
            throw new ItemNotFoundException('The root node has no parent');
        }

        return $this->objectManager->getNodeByPath($this->parentPath);
    }

    /**
     * @api
     */
    public function getDepth(): int
    {
        $this->checkState();

        return $this->depth;
    }

    /**
     * @api
     */
    public function getSession(): SessionInterface
    {
        $this->checkState();

        return $this->session;
    }

    /**
     * @api
     */
    public function isNode(): bool
    {
        $this->checkState();

        return $this->isNode;
    }

    /**
     * @api
     */
    public function isNew(): bool
    {
        return self::STATE_NEW === $this->state;
    }

    /**
     * @api
     */
    public function isModified(): bool
    {
        return self::STATE_MODIFIED === $this->state
            || self::STATE_DIRTY === $this->state && self::STATE_MODIFIED === $this->postDirtyState;
    }

    /**
     * @private
     */
    public function isMoved(): bool
    {
        return null !== $this->oldPath;
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
     * @private
     */
    public function isDirty(): bool
    {
        return self::STATE_DIRTY === $this->state;
    }

    /**
     * Whether this item has been deleted and can not be used anymore.
     *
     * @private
     */
    public function isDeleted(): bool
    {
        return self::STATE_DELETED === $this->state;
    }

    /**
     * Whether this item is in STATE_CLEAN (meaning its data is fully
     * synchronized with the backend).
     *
     * @private
     */
    public function isClean(): bool
    {
        return self::STATE_CLEAN === $this->state;
    }

    /**
     * @api
     */
    public function isSame(ItemInterface $otherItem): bool
    {
        $this->checkState();

        if ($this === $otherItem) { // trivial case
            return true;
        }
        if (get_class($this) !== get_class($otherItem)
            || $this->session->getRepository() !== $otherItem->getSession()->getRepository()
            || $this->session->getWorkspace() !== $otherItem->getSession()->getWorkspace()
        ) {
            return false;
        }
        switch ($this) {
            case $this instanceof Node:
                return $this->getIdentifier() === $otherItem->getIdentifier();
            case $this instanceof Property:
                return $this->name === $otherItem->getName()
                    && $this->getParent()->isSame($otherItem->getParent())
                ;
            default:
                throw new NotImplementedException('Item::isSame for Item of class '.get_class($this).' is not implemented');
        }
    }

    /**
     * @api
     */
    public function accept(ItemVisitorInterface $visitor): void
    {
        $this->checkState();

        $visitor->visit($this);
    }

    /**
     * @throws InvalidItemStateException
     *
     * @api
     */
    public function remove(): void
    {
        $this->checkState(); // To avoid the possibility to delete an already deleted node

        // sanity checks
        if (0 === $this->getDepth()) {
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
     * @throws RepositoryException
     *
     * @private
     */
    public function setModified(): void
    {
        if (!$this->isNew()) {
            $this->setState(self::STATE_MODIFIED);
        }
    }

    /**
     * Tell this item that it is dirty and needs to be refreshed.
     *
     * @param bool     $keepChanges whether to keep changes when reloading or not
     * @param bool|int $targetState
     *
     * @throws RepositoryException
     *
     * @private
     */
    public function setDirty(bool $keepChanges = false, $targetState = false): void
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
                throw new RepositoryException('Setting item '.$this->path.' dirty in state '.$this->getState().' is not expected');
        }

        $this->keepChanges = $keepChanges;
        $this->setState(self::STATE_DIRTY);
    }

    /**
     * Tell this item it has been deleted and cannot be used anymore.
     *
     * @throws RepositoryException
     *
     * @private
     */
    public function setDeleted(): void
    {
        $this->setState(self::STATE_DELETED);
    }

    /**
     * Tell this item it is clean (i.e. it has been refreshed after a modification).
     *
     * @throws RepositoryException
     *
     * @private
     */
    public function setClean(): void
    {
        $this->setState(self::STATE_CLEAN);
    }

    /**
     * notify this item that it has been saved into the backend.
     * allowing it to clear the modified / new flags.
     *
     * @throws RepositoryException
     *
     * @private
     */
    public function confirmSaved(): void
    {
        $this->oldPath = null; // in case this item has been moved
        $this->setDirty(false, self::STATE_CLEAN);
    }

    /**
     * Get the state of the item.
     *
     * @return int one of the state constants
     *
     * @private
     */
    public function getState(): int
    {
        return $this->state;
    }

    /**
     * @api
     */
    public function revert(): void
    {
        $this->refresh(false);
    }

    /**
     * Find the matching item definition for this item.
     *
     * @param callable $definitions Function that extracts the ItemDefinitions from a NodeType
     *
     * @return ItemDefinitionInterface the definition for this item
     *
     * @throws RepositoryException if no definition can be found
     */
    protected function findItemDefinition(callable $definitions): ItemDefinitionInterface
    {
        $fallbackDefinition = null;
        $types = $this->getParent()->getMixinNodeTypes();
        $types[] = $this->getParent()->getPrimaryNodeType();
        foreach ($types as $nt) {
            /** @var $candidate ItemDefinitionInterface */
            foreach ($definitions($nt) as $candidate) {
                if ($candidate->getName() === $this->name) {
                    return $candidate;
                }
                if (!$fallbackDefinition && '*' === $candidate->getName()) {
                    // if we have multiple wildcard definitions, they are hopefully equivalent
                    $fallbackDefinition = $candidate;
                    // do not abort loop, in case we hit an exactly matching definition
                }
            }
        }

        // sanity check. theoretically, the item should not be able to
        // exist if there is no definition for it
        if (!$fallbackDefinition) {
            throw new RepositoryException(sprintf('Found no definition for item %s, this should not be possible', $this->path));
        }

        return $fallbackDefinition;
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
     * @param bool $keepChanges a boolean
     *
     * @throws InvalidItemStateException if this Item object represents
     *                                   a workspace item that has been removed (either by this session or
     *                                   another)
     * @throws RepositoryException       if another error occurs
     */
    abstract protected function refresh(bool $keepChanges, bool $internal = false): void;

    /**
     * Change the state of the item.
     *
     * @param int $state The new item state, one of the state constants
     *
     * @throws RepositoryException
     *
     * @private
     */
    private function setState(int $state): void
    {
        if (!in_array($state, self::STATES)) {
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
        if (self::STATE_MODIFIED === $state && self::STATE_CLEAN === $this->savedState) {
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
    protected function checkState(): void
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

        if (self::STATE_DELETED === $this->state) {
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
    public function beginTransaction(): void
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
    public function commitTransaction(): void
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
     * @throws \LogicException if an unexpected state transition is encountered
     *
     * @private
     *
     * @see ObjectManager::rollbackTransaction()
     */
    public function rollbackTransaction(): void
    {
        if (null === $this->savedState) {
            $this->savedState = self::STATE_NEW;
        }

        if (self::STATE_DELETED === $this->state || self::STATE_DELETED === $this->savedState) {
            // Case 1) and 2)
            $this->state = self::STATE_DELETED;
        } elseif (self::STATE_NEW === $this->savedState) {
            // Case 3)
            $this->state = self::STATE_NEW;
        } elseif (self::STATE_MODIFIED === $this->state || self::STATE_MODIFIED === $this->savedState) {
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
            throw new \LogicException('There was an unexpected state transition during the transaction: '.
                                      "old state = {$this->savedState}, new state = {$this->state}");
        }

        // Reset the saved state
        $this->savedState = null;
    }
}
