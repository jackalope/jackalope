<?php

namespace Jackalope\Observation;

use Jackalope\FactoryInterface;
use Jackalope\NotImplementedException;
use PHPCR\NodeType\NodeTypeInterface;
use PHPCR\NodeType\NodeTypeManagerInterface;
use PHPCR\Observation\EventInterface;
use PHPCR\RepositoryException;

/**
 * {@inheritDoc}
 *
 * @api
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 * @author D. Barsotti <daniel.barsotti@liip.ch>
 * @author David Buchmann <mail@davidbu.ch>
 */
final class Event implements EventInterface
{
    private int $type;

    private ?string $path = null;

    private string $userId;

    private ?string $identifier = null;

    private array $info = [];

    private ?string $userData = null;

    private int $date;

    private string $primaryNodeTypeName;

    private array $mixinNodeTypeNames = [];

    private NodeTypeManagerInterface $ntm;

    /**
     * Events that support getting the primary or mixin node types.
     *
     * @var array
     */
    private static $NODE_TYPE_EVENT = [
        self::NODE_ADDED,
        self::NODE_REMOVED,
        self::NODE_MOVED,
        self::PROPERTY_ADDED,
        self::PROPERTY_REMOVED,
        self::PROPERTY_CHANGED,
    ];

    /**
     * Events that support getting the property type.
     *
     * @var array
     */
    private static $PROPERTY_TYPE_EVENT = [
        self::PROPERTY_ADDED,
        self::PROPERTY_REMOVED,
        self::PROPERTY_CHANGED,
    ];

    /**
     * @param FactoryInterface         $factory ignored but need by the factory
     * @param NodeTypeManagerInterface $ntm     to have primary and mixin types
     */
    public function __construct(
        FactoryInterface $factory,
        NodeTypeManagerInterface $ntm
    ) {
        $this->ntm = $ntm;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getType(): int
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getUserID(): string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): void
    {
        $this->userId = $userId;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(?string $identifier): void
    {
        $this->identifier = $identifier;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getInfo(): array
    {
        return $this->info;
    }

    public function addInfo(string $key, string $value): void
    {
        $this->info[$key] = $value;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getUserData(): ?string
    {
        return $this->userData;
    }

    /**
     * @param string|null $data url-encoded string or null to remove the data
     */
    public function setUserData(?string $data): void
    {
        if (null === $data) {
            $this->userData = null;
        } else {
            $this->userData = urldecode($data);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getDate(): int
    {
        return $this->date;
    }

    public function setDate(int $date): void
    {
        $this->date = $date;
    }

    public function setPrimaryNodeTypeName(string $primaryNodeTypeName): void
    {
        $this->primaryNodeTypeName = $primaryNodeTypeName;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getPrimaryNodeType(): NodeTypeInterface
    {
        $this->checkNodeTypeEvent();

        return $this->ntm->getNodeType($this->primaryNodeTypeName);
    }

    public function setMixinNodeTypeNames(array $mixinNodeTypeNames): void
    {
        $this->mixinNodeTypeNames = $mixinNodeTypeNames;
    }

    public function addMixinNodeTypeName(string $mixinNodeTypeName): void
    {
        $this->mixinNodeTypeNames[] = $mixinNodeTypeName;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getMixinNodeTypes(): array
    {
        $this->checkNodeTypeEvent();
        $nt = [];
        foreach ($this->mixinNodeTypeNames as $name) {
            $nt[$name] = $this->ntm->getNodeType($name);
        }

        return $nt;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getPropertyType()
    {
        if (!in_array($this->type, self::$PROPERTY_TYPE_EVENT)) {
            throw new RepositoryException('Event of type '.$this->type.' does not have property type information');
        }

        /*
         * For add and change events, we could try to fetch the property in
         * question and ask it for its type. But if the property was removed
         * since then, this does not work.
         */
        throw new NotImplementedException('TODO: implement once jackrabbit provides the information.');
    }

    /**
     * Check if this event is a node type event. Throw exception otherwise.
     *
     * @throws RepositoryException if this event is not of a type that has node
     *                             type information
     */
    private function checkNodeTypeEvent(): void
    {
        if (!in_array($this->type, self::$NODE_TYPE_EVENT)) {
            throw new RepositoryException('Event of type '.$this->type.' does not have node type information');
        }
    }
}
