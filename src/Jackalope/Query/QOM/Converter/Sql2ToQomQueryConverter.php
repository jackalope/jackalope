<?php

namespace Jackalope\Query\QOM\Converter;

use Jackalope\Query\QOM;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as Constants;

/**
 * Parse SQL2 statements and output a corresponding QOM objects tree
 * 
 * TODO: finish implementation
 */
class Sql2ToQomQueryConverter
{
    /**
     * @var \Jackalope\Query\QOM\QueryObjectModelFactory();
     */
    protected $factory;

    /**
     * @var \Jackalope\Query\QOM\Sql2Converter\Scanner;
     */
    protected $scanner;

    public function __construct()
    {
        $this->factory = new QOM\QueryObjectModelFactory();
    }

    /**
     * Parse an SQL2 query and return the corresponding Qom QueryObjectModel
     *
     * @param string $sql2
     * @return \Jackalope\Query\QOM\QueryObjectModel;
     */
    public function parse($sql2)
    {
        $this->scanner = new Sql2Scanner($sql2);
        $source = null;
        $columns = array();
        $constraint = null;
        $orderings = array();

        while($this->scanner->lookupNextToken() !== '') {
            
            switch(strtoupper($this->scanner->lookupNextToken())) {
                case 'FROM':
                    $source = $this->parseSouce();
                    break;
                case 'SELECT':
                    $columns = $this->parseColumns();
                    break;
                case 'ORDER':
                    // Ordering, check there is a BY
                    break;
                case 'ŴHERE':
                    // Constraint
                    break;
                default:
                    // Exit loop for debugging
                    break(2);
            }
        }

        $query = $this->factory->createQuery($source, $constraint, $orderings, $columns);;

        return $query;
    }

    /**
     * Parse an SQL2 source definition and return the corresponding QOM Source
     *
     * @return \PHPCR\Query\QOM\SourceInterface
     */
    protected function parseSouce()
    {
        $this->assertNextTokenIs('FROM');

        $selector = $this->parseSelector();
        
        $next = $this->scanner->lookupNextToken();
        if (in_array(strtoupper($next), array('JOIN', 'INNER', 'RIGHT', 'LEFT'))) {
            // TODO: JOIN...
        }
        
        return $selector;
    }

    /**
     * Parse an SQL2 selector and return a QOM\Selector
     *
     * @return \Jackalope\Query\QOM\Selector
     */
    protected function parseSelector()
    {
        $token = $this->scanner->fetchNextToken();
        if ($this->scanner->lookupNextToken() === 'AS') {
            $this->scanner->fetchNextToken();
            $nodeTypeName = $this->scanner->fetchNextToken();
            return $this->factory->selector($nodeTypeName, $token);
        }
        
        return $this->factory->selector($token);
    }

    /**
     * Parse an SQL2 join source and return a QOM\Join
     * 
     * @param string $leftSelector the left selector as it has been read by parseSource
     * return \PHPCR\Query\QOM\JoinInterface
     */
    protected function parseJoin($leftSelector)
    {
        $joinType = Constants::JCR_JOIN_TYPE_INNER;
        $token = $this->scanner->fetchNextToken();
        
        switch ($token) {
            case 'JOIN':
                // Token already fetched, nothing to do
                break;
            case 'INNER':
                $this->scanner->fetchNextToken();
                break;
            case 'LEFT':
                $this->scanner->fetchNextToken();
                if ($this->scanner->fetchNextToken() !== 'OUTER') {
                    throw new \Exception('Syntax error: LEFT OUTER expected');
                }
                $joinType = Constants::JCR_JOIN_TYPE_LEFT_OUTER;
                break;
            case 'RIGHT':
                $this->scanner->fetchNextToken();
                if ($this->scanner->fetchNextToken() !== 'OUTER') {
                    throw new \Exception('Syntax error: RIGHT OUTER expected');
                }
                $joinType = Constants::JCR_JOIN_TYPE_RIGHT_OUTER;
                break;
            default:
                throw new \Exception('Syntax error: Expected JOIN, INNER JOIN, RIGHT JOIN or LEFT JOIN');
        }

        if ($this->scanner->lookupNextToken() !== 'ON') {
            throw new \Exception('Syntax error: Expected ON');
        }

        $left = $this->factory->selector($leftSelector);
        $right = $this->factory->selector($this->scanner->fetchNextToken());
        $joinCondition = $this->parseJoinCondition();

        return $this->factory->join($left, $right, $joinType, $joinCondition);
    }

    /**
     * Parse an SQL2 join condition and return a QOM\Joincondition
     *
     * @return \PHPCR\Query\QOM\JoinConditionInterface
     */
    protected function parseJoinCondition()
    {
        $this->assertNextTokenIs('ON');

        $token = $this->scanner->lookupNextToken();
        if ($token === 'ISSAMENODE') {

            return $this->parseSameNodeJoinCondition();

        } elseif ($token === 'ISCHILDNODE') {

            return $this->parseChildNodeJoinCondition();

        } elseif ($token === 'ISDESCENDANTNODE') {

            return $this->parseDescendantNodeJoinCondition();
        }

        return $this->parseEquiJoin();
    }

    /**
     * Parse an SQL2 equijoin condition and return a QOM\EquiJoinCondition
     *
     * @return \Jackalope\Query\QOM\EquiJoinCondition
     */
    protected function parseEquiJoin()
    {
        throw new \Jackalope\NotImplementedException();
    }

    /**
     * Parse an SQL2 same node join condition and return a QOM\SameNodeJoinCondition
     *
     * @return \Jackalope\Query\QOM\SameNodeJoinCondition
     */
    protected function parseSameNodeJoinCondition()
    {
        $this->assertNextTokenIs('ISSAMENODE');

        throw new \Jackalope\NotImplementedException();
    }

    /**
     * Parse an SQL2 child node join condition and return a QOM\ChildNodeJoinCondition
     *
     * @return \Jackalope\Query\QOM\ChildNodeJoinCondition
     */
    protected function parseChildNodeJoinCondition()
    {
        throw new \Jackalope\NotImplementedException();
    }

    /**
     * Parse an SQL2 descendant node join condition and return a QOM\DescendantNodeJoinCondition
     *
     * @return \Jackalope\Query\QOM\DescendantNodeJoinCondition
     */
    protected function parseDescendantNodeJoinCondition()
    {
        throw new \Jackalope\NotImplementedException();
    }

    /**
     * Parse an SQL2 columns definition and return an array of QOM\Column
     *
     * @return array of \Jackalope\Query\QOM\Column
     */
    protected function parseColumns()
    {
        $this->assertNextTokenIs('SELECT');

        // Wildcard
        if ($this->scanner->lookupNextToken() === '*') {
            $this->scanner->fetchNextToken();
            return array();
        }
        
        $columns = array();
        $hasNext = true;
        
        // Column list
        while ($hasNext) {

            $columns[] = $this->parseColumn();
            
            // Are there more columns?
            if ($this->scanner->lookupNextToken() !== ',') {
                $hasNext = false;
            } else {
                $this->scanner->fetchNextToken();
            }

        }

        return $columns;
    }

    /**
     * Parse a single SQL2 column definition and return a QOM\Column
     *
     * @return \Jackalope\Query\QOM\Column
     */
    protected function parseColumn()
    {
        $propertyName = '';
        $columnName = null;
        $selectorName = null;

        $token = $this->scanner->fetchNextToken();

        // selector.property
        if ($this->scanner->lookupNextToken() !== '.') {
            $propertyName = $token;
        } else {
            $selectorName = $token;
            $this->scanner->fetchNextToken(); // Consume the '.'
            $propertyName = $this->scanner->fetchNextToken();
        }

        // AS name
        if (strtoupper($this->scanner->lookupNextToken()) === 'AS') {
            $this->scanner->fetchNextToken();
            $columnName = $this->scanner->fetchNextToken();
        }

        return $this->factory->column($propertyName, $columnName, $selectorName);
    }

    protected function assertNextTokenIs($expected_token)
    {
        $token = $this->scanner->fetchNextToken();
        if (strtoupper($token) !== strtoupper($expected_token)) {
            throw new \Exception("Syntax error: Expected " . $expected_token);
        }
    }
}
