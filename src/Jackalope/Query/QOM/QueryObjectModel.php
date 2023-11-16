<?php

namespace Jackalope\Query\QOM;

use InvalidArgumentException;
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
use PHPCR\Util\ValueConverter;

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
     * @var SourceInterface
     */
    protected $source;

    /**
     * @var ConstraintInterface
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
     * @param FactoryInterface $factory the object factory
     * @param ObjectManager $objectManager (can be omitted if you do not want
     *      to execute the query but just use it with a parser)
     * @param SourceInterface $source
     * @param ConstraintInterface $constraint
     * @param array $orderings
     * @param array $columns
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        FactoryInterface $factory,
        ObjectManager $objectManager = null,
        SourceInterface $source,
        ConstraintInterface $constraint = null,
        array $orderings,
        array $columns
    ) {
        foreach ($orderings as $o) {
            if (! $o instanceof OrderingInterface) {
                throw new InvalidArgumentException("Not a valid ordering: $o");
            }
        }

        foreach ($columns as $c) {
            if (! $c instanceof ColumnInterface) {
                throw new InvalidArgumentException("Not a valid column: $c");
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
     * @return SourceInterface the node-tuple source
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
     * @return ConstraintInterface|null the constraint, or null if there is no
     *                                   constraint
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
     * @return OrderingInterface[] an array of the orderings. If no orderings
     *                              defined an empty array is returned.
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
     * @return ColumnInterface[] an array of the columns to get. If none
     *                            specified an empty array is returned.
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
     * @return string[]
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
     * @return string the query statement
     *
     * @api
     */
    public function getStatement()
    {
        $valueConverter = $this->factory->get(ValueConverter::class);
        $converter = new QomToSql2QueryConverter(new Sql2Generator($valueConverter));

        return $converter->convert($this);
    }

    /**
     * {@inheritDoc}
     *
     * @return string the query language
     *
     * @api
     */
    public function getLanguage()
    {
        return self::JCR_SQL2;
    }
}
