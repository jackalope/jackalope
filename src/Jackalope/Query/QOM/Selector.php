<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\SelectorInterface;

/**
 * Selects a subset of the nodes in the repository based on node type.
 *
 * A selector selects every node in the repository, subject to access control
 * constraints, that satisfies at least one of the following conditions:
 *
 * - the node's primary node type is nodeType
 * - the node's primary node type is a subtype of nodeType
 * - the node has a mixin node type that is nodeType
 * - the node has a mixin node type that is a subtype of nodeType
 *
 * @api
 */
class Selector implements SelectorInterface
{
    /**
     * @var string
     */
    protected $nodeTypeName;

    /**
     * @var string
     */
    protected $selectorName;

    /**
     * Constructor
     *
     * @param string $nodeTypeName
     * @param string $selectorName 
     */
    public function __construct($nodeTypeName, $selectorName = null)
    {
        $this->nodeTypeName = $nodeTypeName;
        $this->selectorName = $selectorName;
    }

    /**
     * {@inheritdoc}
     */
    function getNodeTypeName()
    {
        return $this->nodeTypeName;
    }

    /**
     * {@inheritdoc}
     */
    function getSelectorName()
    {
        return $this->selectorName;
    }
}
