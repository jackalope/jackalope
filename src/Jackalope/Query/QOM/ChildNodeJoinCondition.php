<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\ChildNodeJoinConditionInterface;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
class ChildNodeJoinCondition implements ChildNodeJoinConditionInterface
{
    /**
     * @var string
     */
    protected $childNodeSelectorName;

    /**
     * @var string
     */
    protected $parentSelectorName;

    /**
     * Constructor
     *
     * @param string $childSelectorName
     * @param string $parentSelectorName
     */
    public function __construct($childSelectorName, $parentSelectorName)
    {
        $this->childNodeSelectorName = (string) $childSelectorName;
        $this->parentSelectorName = (string) $parentSelectorName;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getChildSelectorName()
    {
        return $this->childNodeSelectorName;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getParentSelectorName()
    {
        return $this->parentSelectorName;
    }
}
