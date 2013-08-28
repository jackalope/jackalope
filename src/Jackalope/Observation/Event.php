<?php

namespace Jackalope\Observation;

use Jackalope\FactoryInterface;
use Jackalope\NotImplementedException;
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
 *
 * @author D. Barsotti <daniel.barsotti@liip.ch>
 * @author David Buchmann <mail@davidbu.ch>
 */
class Event implements EventInterface
{
    /** @var int */
    protected $type;

    /** @var string */
    protected $path;

    /** @var string */
    protected $userId;

    /** @var string */
    protected $identifier;

    /** @var array */
    protected $info = array();

    /** @var string */
    protected $userData;

    /** @var int */
    protected $date;

    /** @var string */
    protected $primaryNodeTypeName;

    /** @var array */
    protected $mixinNodeTypeNames = array();

    /** @var \PHPCR\NodeType\NodeTypeManagerInterface */
    protected $ntm;

    /**
     * Events that support getting the primary or mixin node types.
     * @var array
     */
    protected static $NODE_TYPE_EVENT = array(
        self::NODE_ADDED,
        self::NODE_REMOVED,
        self::NODE_MOVED,
        self::PROPERTY_ADDED,
        self::PROPERTY_REMOVED,
        self::PROPERTY_CHANGED,
    );
    /**
     * Events that support getting the property type.
     * @var array
     */
    protected static $PROPERTY_TYPE_EVENT = array(
        self::PROPERTY_ADDED,
        self::PROPERTY_REMOVED,
        self::PROPERTY_CHANGED,
    );

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
     * @api
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getUserID()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * @param string $key
     * @param string $value
     */
    public function addInfo($key, $value)
    {
        $this->info[$key] = $value;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getUserData()
    {
        return $this->userData;
    }

    /**
     * @param string $data url-encoded string
     */
    public function setUserData($data)
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
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param int $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @param string $primaryNodeTypeName
     */
    public function setPrimaryNodeTypeName($primaryNodeTypeName)
    {
        $this->primaryNodeTypeName = $primaryNodeTypeName;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getPrimaryNodeType()
    {
        $this->checkNodeTypeEvent();

        return $this->ntm->getNodeType($this->primaryNodeTypeName);
    }

    /**
     * @param array $mixinNodeTypeNames
     */
    public function setMixinNodeTypeNames(array $mixinNodeTypeNames)
    {
        $this->mixinNodeTypeNames = $mixinNodeTypeNames;
    }

    public function addMixinNodeTypeName($mixinNodeTypeName)
    {
        $this->mixinNodeTypeNames[] = $mixinNodeTypeName;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getMixinNodeTypes()
    {
        $this->checkNodeTypeEvent();
        $nt = array();
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
        if (! in_array($this->type, self::$PROPERTY_TYPE_EVENT)) {
            throw new RepositoryException('Event of type ' . $this->type . ' does not have property type information');
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
     *      type information.
     */
    private function checkNodeTypeEvent()
    {
        if (! in_array($this->type, self::$NODE_TYPE_EVENT)) {
            throw new RepositoryException('Event of type ' . $this->type . ' does not have node type information');
        }
    }
}
