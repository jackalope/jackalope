<?php

/**
 * Class to handle the communication between Jackalope and Jackrabbit via Davex.
 *
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache License Version 2.0, January 2004
 *   Licensed under the Apache License, Version 2.0 (the "License") {}
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

namespace Jackalope\Transport\Doctrine;

use Doctrine\DBAL\Schema\Schema;

class RepositorySchema
{
    static public function create()
    {
        $schema = new Schema();
        $workspace = $schema->createTable('jcrworkspaces');
        $workspace->addColumn('id', 'integer', array('autoincrement' => true));
        $workspace->addColumn('name', 'string');
        $workspace->setPrimaryKey(array('id'));

        $nodes = $schema->createTable('jcrnodes');
        $nodes->addColumn('path', 'string');
        $nodes->addColumn('workspace_id', 'integer');
        $nodes->addColumn('identifier', 'string');
        $nodes->addColumn('type', 'string');
        $nodes->setPrimaryKey(array('path', 'workspace_id'));
        $nodes->addUniqueIndex(array('identifier'));
        
        $properties = $schema->createTable('jcrprops');
        $properties->addColumn('path', 'string');
        $properties->addColumn('workspace_id', 'integer');
        $properties->addColumn('name', 'string');
        $properties->addColumn('node_identifier', 'string');
        $properties->addColumn('type', 'integer');
        $properties->addColumn('string_data', 'string', array('notnull' => false, 'length' => 4000));
        $properties->addColumn('int_data', 'integer', array('notnull' => false));
        $properties->addColumn('float_data', 'float', array('notnull' => false));
        $properties->addColumn('clob_data', 'text', array('notnull' => false));
        $properties->addColumn('datetime_data', 'datetime', array('notnull' => false));
        $properties->setPrimaryKey(array('path', 'workspace_id'));
        $properties->addIndex(array('node_identifier'));
        $properties->addIndex(array('string_data'));

        $binary = $schema->createTable('jcrbinarydata');
        $binary->addColumn('path', 'string');
        $binary->addColumn('workspace_id', 'integer');
        $binary->addColumn('data', 'text'); // TODO BLOB!
        $binary->setPrimaryKey(array('path', 'workspace_id'));

        return $schema;
    }
}