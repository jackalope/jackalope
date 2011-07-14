<?php

/**
 * Lexer for parsing SQL2 statements into an ANSI-SQL statement for use with the Doctrine DBAL Transport
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

class SQL2Lexer extends \Doctrine\Common\Lexer
{
    // All tokens that are not valid identifiers must be < 100
    const T_NONE                = 1;
    const T_INTEGER             = 2;
    const T_STRING              = 3;
    const T_INPUT_PARAMETER     = 4;
    const T_FLOAT               = 5;
    const T_CLOSE_PARENTHESIS   = 6;
    const T_OPEN_PARENTHESIS    = 7;
    const T_COMMA               = 8;
    const T_DIVIDE              = 9;
    const T_DOT                 = 10;
    const T_EQUALS              = 11;
    const T_GREATER_THAN        = 12;
    const T_LOWER_THAN          = 13;
    const T_MINUS               = 14;
    const T_MULTIPLY            = 15;
    const T_NEGATE              = 16;
    const T_PLUS                = 17;
    const T_OPEN_CURLY_BRACE    = 18;
    const T_CLOSE_CURLY_BRACE   = 19;
    const T_OPEN_BRACKET        = 20;
    const T_CLOSE_BRACKET       = 21;
    
    const T_IDENTIFIER          = 100;
    const T_SELECT              = 101;
    const T_FROM                = 102;
    const T_WHERE               = 103;
    const T_ORDER               = 104;
    const T_BY                  = 105;
    const T_AS                  = 106;
    const T_OR                  = 107;
    const T_AND                 = 108;
    const T_NOT                 = 109;
    const T_LITERAL             = 110;
    const T_LIKE                = 110;

    /**
     * Creates a new query scanner object.
     *
     * @param string $input a query string
     */
    public function __construct($input)
    {
        $this->setInput($input);
    }

    /**
     * @inheritdoc
     */
    protected function getCatchablePatterns()
    {
        return array(
            '[a-z_\\\][a-z0-9_\:\\\]*[a-z0-9_]{1}',
            '(?:[0-9]+(?:[\.][0-9]+)*)(?:e[+-]?[0-9]+)?',
            "'(?:[^']|'')*'",
            '\?[0-9]*|:[a-z]{1}[a-z0-9_]{0,}'
        );
    }
    
    /**
     * @inheritdoc
     */
    protected function getNonCatchablePatterns()
    {
        return array('\s+', '(.)');
    }
    
    /**
     * @inheritdoc
     */
    protected function getType(&$value)
    {
        $type = self::T_NONE;
        
        // Recognizing numeric values
        if (is_numeric($value)) {
            return (strpos($value, '.') !== false || stripos($value, 'e') !== false) 
                    ? self::T_FLOAT : self::T_INTEGER;
        }
        
        // Differentiate between quoted names, identifiers, input parameters and symbols
        if ($value[0] === "'") {
            $value = str_replace("''", "'", substr($value, 1, strlen($value) - 2));
            return self::T_STRING;
        } else if (ctype_alpha($value[0]) || $value[0] === '_') {
            $name = 'Jackalope\Transport\DoctrineDBAL\Query\SQL2Lexer::T_' . strtoupper($value);

            if (defined($name)) {
                $type = constant($name);
                
                if ($type > 100) {
                    return $type;
                }
            }

            return self::T_IDENTIFIER;
        } else if ($value[0] === '?' || $value[0] === ':') {
            return self::T_INPUT_PARAMETER;
        } else {
            switch ($value) {
                case '.': return self::T_DOT;
                case ',': return self::T_COMMA;
                case '(': return self::T_OPEN_PARENTHESIS;
                case ')': return self::T_CLOSE_PARENTHESIS;
                case '=': return self::T_EQUALS;
                case '>': return self::T_GREATER_THAN;
                case '<': return self::T_LOWER_THAN;
                case '+': return self::T_PLUS;
                case '-': return self::T_MINUS;
                case '*': return self::T_MULTIPLY;
                case '/': return self::T_DIVIDE;
                case '!': return self::T_NEGATE;
                case '{': return self::T_OPEN_CURLY_BRACE;
                case '}': return self::T_CLOSE_CURLY_BRACE;
                case '[': return self::T_OPEN_BRACKET;
                case ']': return self::T_CLOSE_BRACKET;
                default:
                    // Do nothing
                    break;
            }
        }

        return $type;
    }
}