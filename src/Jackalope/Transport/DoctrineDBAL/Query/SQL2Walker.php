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

use Jackalope\NodeType\NodeTypeManager;

class SQL2Walker
{
    /**
     * @var array
     */
    private $parameters = array();
    
    private $sqls = array();
    
    /**
     * @var NodeTypeManager
     */
    private $nodeTypeManager;
    
    public function __construct(NodeTypeManager $nodeManager)
    {
        $this->nodeTypeManager = $nodeTypeManager;
    }
    
    public function walkQueryLanguage($AST)
    {
        // Build the Fetch paths statement
        $sql = "SELECT DISTINCT n.path FROM phpcr_nodes n INNER JOIN phpcr_props p ON n.path = p.path AND n.workspace_id = p.workspace_id ".
               "WHERE n.workspace_id = ? AND n.type IN ('" . $AST['from'][0]['type']."'";
        
        $subTypes = $this->nodeTypeManager->getSubtypes($AST['from'][0]['type']);
        foreach ($subTypes as $subType) {
            /* @var $subType PHPCR\NodeType\NodeTypeInterface */
            $sql .= ", '" . $subType . "'";
        }
        $sql .= ') ';
        
        $querySQLs = array();
        if (isset($AST['where'])) {
            if (count($AST['where']) > 0) {
                throw new \InvalidArgumentException("Queries with more than one condition are not supported yet.");
            }
            
            foreach ($AST['where'] AS $contraint) {
                if (count($contraint['children'])) {
                    throw new \InvalidArgumentException("Queries with sub-conditions are not supported yet.");
                }

                $sql .= ' AND ';
                if ($contraint['not']) {
                    $sql .= 'NOT ';
                }
                $sql .= '(';
                
                // TODO: INSECURE AND SIMPLE FOR NOW, WORKING EXAMPLE
                // Assumes that term 1 is a property name and term 2 is a value/literal
                
                $op = $contraint['operator'];
                $value = $constraint['terms'][1]['value'];
                
                $sql .= '(';
                $sql .= "p.name = '".$contraint['terms'][0]['value']."' AND (";
                $sql .= " (p.type = 1 AND p.clob_data IS NOT NULL AND p.clob_data $op $value) OR " .
                $sql .= " (p.type IN (3,6) AND p.int_data IS NOT NULL AND p.int_data $op $value) OR " .
                $sql .= " (p.type = 4 AND p.float_data IS NOT NULL AND p.float_data $op $value) OR " .
                $sql .= " (p.type = 5 AND p.datetime_data IS NOT NULL AND p.datetime_data $op $value)" .
                $sql .= ')';
                
                $querySQLs[] = $sql;
            }
        } else {
            $querySQLs[] = $sql;
        }
        
        return $querySQLs;
    }
}