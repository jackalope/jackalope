<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\QueryObjectModelInterface;
use PHPCR\Query\QOM\SourceInterface;

// inherit all doc
/**
 * @api
 */
class QueryObjectModel implements QueryObjectModelInterface
{
    /**
     * @var \PHPCR\Query\QOM\SourceInterface
     */
    protected $source;

    /**
     * @var \PHPCR\Query\QOM\ConstraintInterface
     */
    protected $constraint;

    /**
     * @var array
     */
    protected $orderings;

    /**
     * @var array
     */
    protected $columns;

    /**
     * Constructor
     *
     * @param SourceInterface $source
     * @param ConstraintInterface $constraint
     * @param array $orderings
     * @param array $columns
     */
    public function __construct(SourceInterface $source, $constraint, array $orderings, array $columns)
    {
        $this->source = $source;
        $this->constraint = $constraint;
        $this->orderings = $orderings;
        $this->columns = $columns;
    }

    // inherit all doc
    /**
     * @api
     */
    public function getSource()
    {
        return $this->source;
    }

    // inherit all doc
    /**
     * @api
     */
    function getConstraint()
    {
        return $this->constraint;
    }

    // inherit all doc
    /**
     * @api
     */
    function getOrderings()
    {
        return $this->orderings;
    }

    // inherit all doc
    /**
     * @api
     */
    function getColumns()
    {
        return $this->columns;
    }

    // inherit all doc
    /**
     * @api
     */
    function bindValue($varName, $value)
    {
        throw new \Jackalope\NotImplementedException();
    }

    // inherit all doc
    /**
     * @api
     */
    function execute()
    {
        throw new \Jackalope\NotImplementedException();
    }

    // inherit all doc
    /**
     * @api
     */
    function getBindVariableNames()
    {
        throw new \Jackalope\NotImplementedException();
    }

    // inherit all doc
    /**
     * @api
     */
    function setLimit($limit)
    {
        throw new \Jackalope\NotImplementedException();
    }

    // inherit all doc
    /**
     * @api
     */
    function setOffset($offset)
    {
        throw new \Jackalope\NotImplementedException();
    }

    // inherit all doc
    /**
     * @api
     */
    function getStatement()
    {
        throw new \Jackalope\NotImplementedException();
    }

    // inherit all doc
    /**
     * @api
     */
    function getLanguage()
    {
        throw new \Jackalope\NotImplementedException();
    }

    // inherit all doc
    /**
     * @api
     */
    function getStoredQueryPath()
    {
        throw new \Jackalope\NotImplementedException();
    }

    // inherit all doc
    /**
     * @api
     */
    function storeAsNode($absPath)
    {
        throw new \Jackalope\NotImplementedException();
    }
}