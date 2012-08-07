<?php

namespace Jackalope\Query\QOM;

use Jackalope\Query\QOM\QueryObjectModelFactory;
use Jackalope\Factory;

use PHPCR\Util\QOM\QueryBuilder;
use PHPCR\Util\QOM\Sql1Generator;
use PHPCR\Util\QOM\QomToSql1QueryConverter;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as Constants;

class QomToSql2QueryConverterTest extends \PHPUnit_Framework_TestCase
{
    protected $qf;
    protected $qb;

    public function setUp()
    {
        $this->qf = new QueryObjectModelFactory(new Factory());
        $this->qb = new QueryBuilder($this->qf );
    }

    public function doQuery($constraint)
    {
        $this->qb->andWhere($constraint);
        $this->qb->from($this->qf->selector("nt:base"));
        return $this->qb->getQuery()->getStatement();
    }

    public function testFullText()
    {
        $statement = $this->doQuery($this->qf->fullTextSearch("foo", "bar", "data"));
        $this->assertSame("SELECT * FROM [nt:base] WHERE CONTAINS(data.foo, 'bar')", $statement);
    }

    public function testDescendantNode()
    {
        $statement = $this->doQuery($this->qf->descendantNode("/foo/bar"));
        $this->assertSame("SELECT * FROM [nt:base] WHERE ISDESCENDANTNODE([/foo/bar])", $statement);
    }

    public function testPpropertyExistence()
    {
        $statement = $this->doQuery($this->qf->propertyExistence("foo"));
        $this->assertSame("SELECT * FROM [nt:base] WHERE foo IS NOT NULL", $statement);
    }

    public function testChildNode()
    {
        $statement = $this->doQuery($this->qf->childNode("/foo/bar"));
        $this->assertSame("SELECT * FROM [nt:base] WHERE ISCHILDNODE([/foo/bar])", $statement);
    }

    public function testAndConstraint()
    {
        $this->qb->andWhere($this->qf->comparison($this->qf->propertyValue('foo'), Constants::JCR_OPERATOR_EQUAL_TO, $this->qf->literal('bar')));
        $statement = $this->doQuery($this->qf->propertyExistence("foo"));
        $this->assertSame("SELECT * FROM [nt:base] WHERE (foo = 'bar' AND foo IS NOT NULL)", $statement);
    }

    public function testOrConstraint()
    {
        $this->qb->where($this->qf->comparison($this->qf->propertyValue('foo'), Constants::JCR_OPERATOR_EQUAL_TO, $this->qf->literal('bar')));
        $this->qb->orWhere($this->qf->comparison($this->qf->propertyValue('bar'), Constants::JCR_OPERATOR_EQUAL_TO, $this->qf->literal('foo')));
        $this->qb->from($this->qf->selector("nt:base"));
        $statement = $this->qb->getQuery()->getStatement();
        $this->assertSame("SELECT * FROM [nt:base] WHERE (foo = 'bar' OR bar = 'foo')", $statement);
    }

    public function testNotConstraint()
    {
        $this->qb->where(
            $this->qf->notConstraint(
                $this->qf->comparison(
                    $this->qf->propertyValue('bar'), Constants::JCR_OPERATOR_EQUAL_TO, $this->qf->literal('foo')
                )
            )
        );
        $this->qb->from($this->qf->selector("nt:base"));
        $statement = $this->qb->getQuery()->getStatement();
        $this->assertSame("SELECT * FROM [nt:base] WHERE NOT bar = 'foo'", $statement);
    }
}

