<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\ChildNodeJoinConditionInterface;

/**
 * {@inheritDoc}
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
        $this->childNodeSelectorName = $childSelectorName;
        $this->parentSelectorName = $parentSelectorName;
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
