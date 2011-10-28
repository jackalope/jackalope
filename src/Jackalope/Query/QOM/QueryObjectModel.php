<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\QueryObjectModelInterface;
use PHPCR\Query\QOM\SourceInterface;
use PHPCR\Query\QOM\ConstraintInterface;
use PHPCR\Query\QOM\OrderingInterface;
use PHPCR\Query\QOM\ColumnInterface;

use Jackalope\ObjectManager;
use Jackalope\Query\SqlQuery;

// inherit all doc
/**
 * {@inheritDoc}
 *
 * We extend SqlQuery to have features like limit and offset
 *
 * @api
 */
class QueryObjectModel extends SqlQuery implements QueryObjectModelInterface
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
     * @param object $factory an object factory implementing "get" as
     *      described in \Jackalope\Factory
     * @param ObjectManager $objectManager (can be omitted if you do not want
     *      to execute the query but just use it with a parser)
     * @param SourceInterface $source
     * @param ConstraintInterface $constraint
     * @param array $orderings
     * @param array $columns
     */
    public function __construct($factory, ObjectManager $objectManager = null,
                                SourceInterface $source, ConstraintInterface $constraint = null,
                                array $orderings, array $columns)
    {
        foreach ($orderings as $o) {
            if (! $o instanceof OrderingInterface) {
                throw new \InvalidArgumentException('Not a valid ordering: '.$o);
            }
        }
        foreach ($columns as $c) {
            if (! $c instanceof ColumnInterface) {
                throw new \InvalidArgumentException('Not a valid column: '.$o);
            }
        }
        parent::__construct($factory, '', $objectManager);
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
    function getBindVariableNames()
    {
        // TODO: can we inherit from SqlQuery?
        throw new \Jackalope\NotImplementedException();
    }

    // inherit all doc
    /**
     * @api
     */
    function getStatement()
    {
        $converter = new \PHPCR\Util\QOM\QomToSql2QueryConverter(new \PHPCR\Util\QOM\Sql2Generator());
        return $converter->convert($this);
    }

    // inherit all doc
    /**
     * @api
     */
    function getLanguage()
    {
        return self::JCR_JQOM;
    }
}
