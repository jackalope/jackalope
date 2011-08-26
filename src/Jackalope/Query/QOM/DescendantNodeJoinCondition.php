<?php
namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\DescendantNodeJoinConditionInterface;

// inherit all doc
/**
 * @api
 */
class DescendantNodeJoinCondition implements DescendantNodeJoinConditionInterface
{
    /**
     * @var string
     */
    protected $descendantSelectorName;

    /**
     * @var string
     */
    protected $ancestorSelectorNode;

    /**
     * Constructor
     *
     * @param string $descendantSelectorName
     * @param string $ancestorSelectorName
     */
    public function __construct($descendantSelectorName, $ancestorSelectorName)
    {
        $this->ancestorSelectorNode = $ancestorSelectorName;
        $this->descendantSelectorName = $descendantSelectorName;
    }

    // inherit all doc
    /**
     * @api
     */
    function getDescendantSelectorName()
    {
        return $this->descendantSelectorName;
    }

    // inherit all doc
    /**
     * @api
     */
    function getAncestorSelectorName()
    {
        return $this->ancestorSelectorNode;
    }
}
