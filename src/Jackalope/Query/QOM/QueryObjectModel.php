<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\QueryObjectModelInterface;
use PHPCR\Query\QOM\SourceInterface;
use PHPCR\Query\QOM\ConstraintInterface;
use PHPCR\Query\QOM\OrderingInterface;
use PHPCR\Query\QOM\ColumnInterface;

use PHPCR\Util\QOM\Sql2Generator;
use PHPCR\Util\QOM\QomToSql2QueryConverter;

use Jackalope\ObjectManager;
use Jackalope\Query\SqlQuery;
use Jackalope\FactoryInterface;
use Jackalope\NotImplementedException;

/**
 * {@inheritDoc}
 *
 * We extend SqlQuery to have features like limit and offset.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
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
     * @param FactoryInterface $factory       the object factory
     * @param ObjectManager    $objectManager (can be omitted if you do not want
     *      to execute the query but just use it with a parser)
     * @param SourceInterface     $source
     * @param ConstraintInterface $constraint
     * @param array               $orderings
     * @param array               $columns
     */
    public function __construct(FactoryInterface $factory, ObjectManager $objectManager = null,
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
                throw new \InvalidArgumentException('Not a valid column: '.$c);
            }
        }
        parent::__construct($factory, '', $objectManager);
        $this->source = $source;
        $this->constraint = $constraint;
        $this->orderings = $orderings;
        $this->columns = $columns;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getConstraint()
    {
        return $this->constraint;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getOrderings()
    {
        return $this->orderings;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getBindVariableNames()
    {
        // TODO: can we inherit from SqlQuery?
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getStatement()
    {
        $valueConverter = $this->factory->get('PHPCR\Util\ValueConverter');
        $converter = new QomToSql2QueryConverter(new Sql2Generator($valueConverter));

        return $converter->convert($this);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getLanguage()
    {
        return self::JCR_SQL2;
    }
}
