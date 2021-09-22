<?php

namespace Jackalope\Query\QOM;

use Jackalope\FactoryInterface;
use Jackalope\NotImplementedException;
use Jackalope\ObjectManager;
use Jackalope\Query\SqlQuery;
use PHPCR\Query\QOM\ColumnInterface;
use PHPCR\Query\QOM\ConstraintInterface;
use PHPCR\Query\QOM\OrderingInterface;
use PHPCR\Query\QOM\QueryObjectModelInterface;
use PHPCR\Query\QOM\SourceInterface;
use PHPCR\Util\QOM\QomToSql2QueryConverter;
use PHPCR\Util\QOM\Sql2Generator;
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
    private SourceInterface $source;
    private ?ConstraintInterface $constraint;
    private array $orderings;
    private array $columns;

    public function __construct(
        FactoryInterface $factory,
        ?ObjectManager $objectManager,
        SourceInterface $source,
        ?ConstraintInterface $constraint,
        array $orderings,
        array $columns
    ) {
        foreach ($orderings as $o) {
            if (!$o instanceof OrderingInterface) {
                throw new \InvalidArgumentException("Not a valid ordering: $o");
            }
        }

        foreach ($columns as $c) {
            if (!$c instanceof ColumnInterface) {
                throw new \InvalidArgumentException("Not a valid column: $c");
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
    public function getSource(): SourceInterface
    {
        return $this->source;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getConstraint(): ?ConstraintInterface
    {
        return $this->constraint;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getOrderings(): array
    {
        return $this->orderings;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getColumns(): array
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
    public function getStatement(): string
    {
        $valueConverter = $this->factory->get(ValueConverter::class);
        $converter = new QomToSql2QueryConverter(new Sql2Generator($valueConverter));

        return $converter->convert($this);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getLanguage(): string
    {
        return self::JCR_SQL2;
    }
}
