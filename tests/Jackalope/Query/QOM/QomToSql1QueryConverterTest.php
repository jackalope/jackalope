<?php

namespace Jackalope\Query\QOM;

use Jackalope\Query\QOM\QueryObjectModelFactory;
use Jackalope\Factory;

use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use PHPCR\Util\QOM\QueryBuilder;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as Constants;
use PHPCR\Query\QOM\ConstraintInterface;
use PHPCR\Query\QOM\SourceInterface;

class QomToSql1QueryConverterTest extends \PHPUnit_Framework_TestCase
{
    /** @var QueryObjectModelFactoryInterface */
    protected $qf;
    protected $qb;

    public function setUp()
    {
        $this->qf = new QueryObjectModelFactorySql1(new Factory());
        $this->qb = new QueryBuilder($this->qf );
    }

    public function doQuery($constraint)
    {
        $this->qb->andWhere($constraint);

        $this->qb->from($this->qf->selector('base', "nt:base"));
        return $this->qb->getQuery()->getStatement();
    }

    public function testFullText()
    {
        $statement = $this->doQuery($this->qf->fullTextSearch('base', "foo","bar"));
        $this->assertSame("SELECT s FROM nt:base WHERE CONTAINS(foo, 'bar')", $statement);
    }

    public function testDescendantNode()
    {
        $statement = $this->doQuery($this->qf->descendantNode('base', "/foo/bar"));
        $this->assertSame("SELECT s FROM nt:base WHERE jcr:path LIKE '/foo[%]/bar[%]/%'", $statement);
    }

    public function testPpropertyExistence()
    {
        $statement = $this->doQuery($this->qf->propertyExistence('base', "foo"));
        $this->assertSame("SELECT s FROM nt:base WHERE foo IS NOT NULL", $statement);
    }

    public function testChildNode()
    {
        $statement = $this->doQuery($this->qf->childNode('base', "/foo/bar"));
        $this->assertSame("SELECT s FROM nt:base WHERE jcr:path LIKE '/foo[%]/bar[%]/%' AND NOT jcr:path LIKE '/foo[%]/bar[%]/%/%'", $statement);
    }

    public function testAndConstraint()
    {
        $this->qb->andWhere($this->qf->comparison($this->qf->propertyValue('base', 'foo'), Constants::JCR_OPERATOR_EQUAL_TO, $this->qf->literal('bar')));
        $statement = $this->doQuery($this->qf->propertyExistence('base', "foo"));
        $variations = array(
            "SELECT s FROM nt:base WHERE foo = 'bar' AND foo IS NOT NULL",
            "SELECT s FROM nt:base WHERE (foo = 'bar' AND foo IS NOT NULL)",
        );
        $this->assertTrue(in_array($statement, $variations), "The statement '$statement' does not match an expected variation");
    }

    public function testOrConstraint()
    {
        $this->qb->where($this->qf->comparison($this->qf->propertyValue('base', 'foo'), Constants::JCR_OPERATOR_EQUAL_TO, $this->qf->literal('bar')));
        $this->qb->orWhere($this->qf->comparison($this->qf->propertyValue('base', 'bar'), Constants::JCR_OPERATOR_EQUAL_TO, $this->qf->literal('foo')));
        $this->qb->from($this->qf->selector('base', "nt:base"));
        $statement = $this->qb->getQuery()->getStatement();
        $variations = array(
            "SELECT s FROM nt:base WHERE foo = 'bar' OR bar = 'foo'",
            "SELECT s FROM nt:base WHERE (foo = 'bar' OR bar = 'foo')",
        );
        $this->assertTrue(in_array($statement, $variations), "The statement '$statement' does not match an expected variation");
    }

    public function testNotConstraint()
    {
        $this->qb->where(
            $this->qf->notConstraint(
                $this->qf->comparison(
                    $this->qf->propertyValue('base', 'bar'), Constants::JCR_OPERATOR_EQUAL_TO, $this->qf->literal('foo')
                )
            )
        );
        $this->qb->from($this->qf->selector('base', "nt:base"));
        $statement = $this->qb->getQuery()->getStatement();
        $this->assertSame("SELECT s FROM nt:base WHERE NOT bar = 'foo'", $statement);
    }
}

class QueryObjectModelFactorySql1 extends QueryObjectModelFactory
{
    /**
    * {@inheritDoc}
    *
    * @api
    */
    public function createQuery(SourceInterface $source,
                        ConstraintInterface $constraint = null,
                        array $orderings = array(),
                        array $columns = array(),
                        $simpleQuery = false
        )
    {
        return $this->factory->get('Query\QOM\QueryObjectModelSql1',
        array($this->objectManager, $source, $constraint, $orderings, $columns));
    }
}
