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
        $nodes->addColumn('parent', 'string');
        $nodes->addColumn('workspace_id', 'integer');
        $nodes->addColumn('identifier', 'string');
        $nodes->addColumn('type', 'string');
        $nodes->setPrimaryKey(array('path', 'workspace_id'));
        $nodes->addUniqueIndex(array('identifier'));
        $nodes->addIndex(array('parent'));
        
        $properties = $schema->createTable('jcrprops');
        $properties->addColumn('path', 'string');
        $properties->addColumn('workspace_id', 'integer');
        $properties->addColumn('idx', 'integer', array('default' => 0));
        $properties->addColumn('name', 'string');
        $properties->addColumn('node_identifier', 'string');
        $properties->addColumn('type', 'integer');
        $properties->addColumn('multi_valued', 'integer', array('default' => 0));
        $properties->addColumn('string_data', 'string', array('notnull' => false, 'length' => 4000));
        $properties->addColumn('int_data', 'integer', array('notnull' => false));
        $properties->addColumn('float_data', 'float', array('notnull' => false));
        $properties->addColumn('clob_data', 'text', array('notnull' => false));
        $properties->addColumn('datetime_data', 'datetime', array('notnull' => false));
        $properties->setPrimaryKey(array('path', 'workspace_id', 'idx'));
        $properties->addIndex(array('node_identifier'));
        $properties->addIndex(array('string_data'));

        $binary = $schema->createTable('jcrbinarydata');
        $binary->addColumn('path', 'string');
        $binary->addColumn('workspace_id', 'integer');
        $binary->addColumn('data', 'text'); // TODO BLOB!
        $binary->setPrimaryKey(array('path', 'workspace_id'));

        $types = $schema->createTable('jcrtype_nodes');
        $types->addColumn('node_type_id', 'integer', array('autoincrement' => true));
        $types->addColumn('name', 'string');
        $types->addColumn('supertypes', 'string');
        $types->addColumn('is_abstract', 'boolean');
        $types->addColumn('protected', 'boolean');
        $types->addColumn('is_mixin', 'boolean');
        $types->addColumn('queryable', 'boolean');
        $types->addColumn('primary_item', 'string');
        $types->setPrimaryKey(array('node_type_id'));

        $propTypes = $schema->createTable('jcrtype_props');
        $propTypes->addColumn('node_type_id', 'integer');
        $propTypes->addColumn('name', 'string');
        $propTypes->addColumn('protected', 'boolean');
        $propTypes->addColumn('auto_created', 'boolean');
        $propTypes->addColumn('mandatory', 'boolean');
        $propTypes->addColumn('property_type', 'integer');
        $propTypes->setPrimaryKey(array('node_type_id', 'name'));

        #$propContraints = $schema->createTable('jcrtype_props_contraints');

        $childTypes = $schema->createTable('jcrtype_childs');
        $childTypes->addColumn('node_type_id', 'integer');
        $childTypes->addColumn('name', 'string');
        $childTypes->addColumn('primary_types', 'string');
        $childTypes->addColumn('default_type', 'string');

        return $schema;
    }
}