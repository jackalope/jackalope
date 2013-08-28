<?php
namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\DescendantNodeJoinConditionInterface;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
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
        $this->ancestorSelectorNode = (string) $ancestorSelectorName;
        $this->descendantSelectorName = (string) $descendantSelectorName;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getDescendantSelectorName()
    {
        return $this->descendantSelectorName;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getAncestorSelectorName()
    {
        return $this->ancestorSelectorNode;
    }
}
