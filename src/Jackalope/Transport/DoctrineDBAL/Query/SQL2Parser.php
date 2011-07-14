<?php

/**
 * Class to parse SQL2 statements into an ANSI-SQL statement for use with the Doctrine DBAL Transport
 *
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache License Version 2.0, January 2004
 *   Licensed under the Apache License, Version 2.0 (the "License");
 *   you may not use this file except in compliance with the License.
 *   You may obtain a copy of the License at
 *
 *       http://www.apache.org/licenses/LICENSE-2.0
 *
 *   Unless required by applicable law or agreed to in writing, software
 *   distributed under the License is distributed on an "AS IS" BASIS,
 *   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *   See the License for the specific language governing permissions and
 *   limitations under the License.
 *
 * @package jackalope
 * @subpackage transport
 */

namespace Jackalope\Transport\DoctrineDBAL\Query;

class SQL2Parser
{
    /**
     * @var \PHPCR\Query\QueryInterface 
     */
    private $query;
    /**
     *
     * @var SQL2Lexer
     */
    private $lexer;
    
    public function __construct(\PHPCR\Query\QueryInterface $query)
    {
        if ($query->getLanguage() != \PHPCR\Query\QueryInterface::JCR_SQL2) {
            throw new \InvalidArgumentException("Invalid Query passed to SQL2 Parser");
        }
        
        $this->query = $query;
        $this->lexer = new SQL2Lexer($query->getStatement());
    }
    
    public function parse()
    {
        $this->getAST();
    }
    
    public function getAST()
    {
        $AST = $this->QueryLanguage();
        return $AST;
    }
    
    public function QueryLanguage()
    {
        $this->lexer->moveNext();

        switch ($this->lexer->lookahead['type']) {
            case SQL2Lexer::T_SELECT:
                $statement = $this->SelectStatement();
                break;
            default:
                $this->syntaxError('SELECT');
                break;
        }
        
        return $statement;
    }
    
    public function SelectStatement()
    {
        $select = array('select' => $this->SelectClause(), 'from' => $this->FromClause());
        
        $select['where'] = $this->lexer->isNextToken(SQL2Lexer::T_WHERE) ? $this->WhereClause() : null;
        
        return $select;
    }
    
    public function SelectClause()
    {
        $this->match(SQL2Lexer::T_SELECT);
        
        // Process SelectExpressions (1..N)
        $selectExpressions = array();
        $selectExpressions[] = $this->SelectExpression();

        while ($this->lexer->isNextToken(SQL2Lexer::T_COMMA)) {
            $this->match(SQL2Lexer::T_COMMA);
            $selectExpressions[] = $this->SelectExpression();
        }
        
        return $selectExpressions;
    }
    
    public function SelectExpression()
    {
        if ($this->lexer->isNextToken(SQL2Lexer::T_MULTIPLY)) {
            $this->match(SQL2Lexer::T_MULTIPLY);
        } else if ($this->lexer->isNextToken(SQL2Lexer::T_IDENTIFIER)) {
            $this->match(SQL2Lexer::T_IDENTIFIER);
        } else {
            $this->syntaxError('* or identifier');
        }
        
        return $this->lexer->token['value'];
    }
    
    public function FromClause()
    {
        $this->match(SQL2Lexer::T_FROM);
       
        return array($this->Source());
    }
    
    public function Source()
    {
        $source = array('type' => null, 'as' => null);
        
        if ($this->lexer->isNextToken(SQL2Lexer::T_OPEN_BRACKET)) {
            $this->match(SQL2Lexer::T_OPEN_BRACKET);
            $this->match(SQL2Lexer::T_IDENTIFIER);
            
            $source['type'] = $this->lexer->token['value'];
            
            $this->match(SQL2Lexer::T_CLOSE_BRACKET);
        } else if ($this->lexer->isNextToken(SQL2Lexer::T_IDENTIFIER)) {
            $this->match(SQL2Lexer::T_IDENTIFIER);
            
            $source['type'] = $this->lexer->token['value'];
        } else  {
            $this->syntaxError('[ or jcr-name');
        }
        
        if ($this->lexer->isNextToken(SQL2Lexer::T_AS)) {
            $this->lexer->moveNext();
            $this->match(SQL2Lexer::T_IDENTIFIER);
            
            $source['as'] = $this->lexer->token['value'];
        }
        
        return $source;
    }
    
    public function WhereClause()
    {
        $this->match(SQL2Lexer::T_WHERE);
        
        
        return $this->Constraint();
    }
    
    public function Constraint()
    {
        $contraints = array();
        $contraints[] = $this->ConstraintItem();
        
        while ($this->lexer->isNextToken(SQL2Lexer::T_OR) || $this->lexer->isNextToken(SQL2Lexer::T_AND)) {
            $this->lexer->moveNext();
            $contraints[] = $this->ConstraintItem();
        }
        
        return $contraints;
    }
    
    public function ConstraintItem()
    {
        $item = array('type' => 0, 'terms' => array(), 'children' => array(), 'operator' => false, 'not' => false);
        
        if ($this->lexer->isNextToken(SQL2Lexer::T_OPEN_PARENTHESIS)) {
            $this->lexer->moveNext();
            
            $item['children'] = $this->Constraint();
            
            $this->match(SQL2Lexer::T_CLOSE_PARENTHESIS);
        } else if($this->lexer->isNextToken(SQL2Lexer::T_NOT)) {
            $item['type'] = SQL2Lexer::T_NOT;
            $this->lexer->moveNext();
            $item['children'] = $this->Constraint();
        } else {
            $this->lexer->moveNext();
            if ($this->lexer->token['type'] == SQL2Lexer::T_IDENTIFIER) {
                $item['terms'][] = array('type' => SQL2Lexer::T_IDENTIFIER, 'value' => $this->lexer->token['value']);
            } else {
                $item['terms'][] = array('type' => SQL2Lexer::T_LITERAL, 'value' => $this->lexer->token['value']);
            }
            
            $item['operator'] = $this->ComparisonOperator();
            
            $this->lexer->moveNext();
            if ($this->lexer->token['type'] == SQL2Lexer::T_IDENTIFIER) {
                $item['terms'][] = array('type' => SQL2Lexer::T_IDENTIFIER, 'value' => $this->lexer->token['value']);
            } else {
                $item['terms'][] = array('type' => SQL2Lexer::T_LITERAL, 'value' => $this->lexer->token['value']);
            }
        }
        
        return $item;
    }
    
    /**
     * ComparisonOperator ::= "=" | "<" | "<=" | "<>" | ">" | ">=" | "!=" | "LIKE"
     *
     * @return string
     */
    public function ComparisonOperator()
    {
        switch ($this->lexer->lookahead['value']) {
            case '=':
                $this->match(SQL2Lexer::T_EQUALS);

                return '=';

            case '<':
                $this->match(SQL2Lexer::T_LOWER_THAN);
                $operator = '<';

                if ($this->lexer->isNextToken(SQL2Lexer::T_EQUALS)) {
                    $this->match(SQL2Lexer::T_EQUALS);
                    $operator .= '=';
                } else if ($this->lexer->isNextToken(SQL2Lexer::T_GREATER_THAN)) {
                    $this->match(SQL2Lexer::T_GREATER_THAN);
                    $operator .= '>';
                }

                return $operator;

            case '>':
                $this->match(SQL2Lexer::T_GREATER_THAN);
                $operator = '>';

                if ($this->lexer->isNextToken(SQL2Lexer::T_EQUALS)) {
                    $this->match(SQL2Lexer::T_EQUALS);
                    $operator .= '=';
                }

                return $operator;

            case '!':
                $this->match(SQL2Lexer::T_NEGATE);
                $this->match(SQL2Lexer::T_EQUALS);

                return '<>';
            case 'LIKE':
                return 'LIKE';

            default:
                $this->syntaxError('=, <, <=, <>, >, >=, !=, LIKE');
        }
    }
    
    /**
     * Generates a new syntax error.
     *
     * @param string $expected Expected string.
     * @param array $token Got token.
     *
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function syntaxError($expected = '', $token = null)
    {
        if ($token === null) {
            $token = $this->lexer->lookahead;
        }

        $tokenPos = (isset($token['position'])) ? $token['position'] : '-1';
        $message  = "line 0, col {$tokenPos}: Error: ";

        if ($expected !== '') {
            $message .= "Expected {$expected}, got ";
        } else {
            $message .= 'Unexpected ';
        }

        if ($this->lexer->lookahead === null) {
            $message .= 'end of string.';
        } else {
            $message .= "'{$token['value']}'";
        }

        throw new \PHPCR\Query\InvalidQueryException('[Syntax Error] ' . $message);
    }

    /**
     * Generates a new semantical error.
     *
     * @param string $message Optional message.
     * @param array $token Optional token.
     *
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function semanticalError($message = '', $token = null)
    {
        if ($token === null) {
            $token = $this->lexer->lookahead;
        }

        // Minimum exposed chars ahead of token
        $distance = 12;

        // Find a position of a final word to display in error string
        $dql = $this->_query->getDql();
        $length = strlen($dql);
        $pos = $token['position'] + $distance;
        $pos = strpos($dql, ' ', ($length > $pos) ? $pos : $length);
        $length = ($pos !== false) ? $pos - $token['position'] : $distance;

        // Building informative message
        $message = 'line 0, col ' . (
            (isset($token['position']) && $token['position'] > 0) ? $token['position'] : '-1'
        ) . " near '" . substr($dql, $token['position'], $length) . "': Error: " . $message;

        throw new \PHPCR\Query\InvalidQueryException('[Semantical Error] ' . $message);
    }
    
    /**
     * Attempts to match the given token with the current lookahead token.
     *
     * If they match, updates the lookahead token; otherwise raises a syntax
     * error.
     *
     * @param int token type
     * @return void
     * @throws QueryException If the tokens dont match.
     */
    public function match($token)
    {
        // short-circuit on first condition, usually types match
        if ($this->lexer->lookahead['type'] !== $token &&
                $token !== SQL2Lexer::T_IDENTIFIER &&
                $this->lexer->lookahead['type'] <= SQL2Lexer::T_IDENTIFIER
         ) {
            $this->syntaxError($this->lexer->getLiteral($token));
        }

        $this->lexer->moveNext();
    }
}