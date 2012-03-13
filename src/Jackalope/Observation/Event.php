<?php

namespace Jackalope\Observation;

use PHPCR\Observation\EventInterface;

/**
 * {@inheritDoc}
 *
 * @api
 *
 * @author D. Barsotti <daniel.barsotti@liip.ch>
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
    protected $info;

    /** @var string */
    protected $userData;

    /** @var int */
    protected $date;

    /**
     * Internaly used to store the nodeType returned by the backend for further filtering of the event journal
     * @var string
     */
    protected $nodeType;

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
     * @return void
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * {@inheritDoc}
     * @api
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     * @return void
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * {@inheritDoc}
     * @api
     */
    public function getUserID()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     * @return void
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * {@inheritDoc}
     * @api
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     * @return void
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * {@inheritDoc}
     * @api
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * @param mixed $info
     * @return void
     */
    public function addInfo($info)
    {
        $this->info[] = $info;
    }

    /**
     * {@inheritDoc}
     * @api
     */
    public function getUserData()
    {
        return $this->userData;
    }

    /**
     * @param string $data
     * @return void
     */
    public function setUserData($data)
    {
        $this->userData = $data;
    }

    /**
     * {@inheritDoc}
     * @api
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param int $date
     * @return void
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @param string $nodeType
     */
    public function setNodeType($nodeType)
    {
        $this->nodeType = $nodeType;
    }

    /**
     * @return string
     */
    public function getNodeType()
    {
        return $this->nodeType;
    }
}
