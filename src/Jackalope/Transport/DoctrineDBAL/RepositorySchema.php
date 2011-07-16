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

namespace Jackalope\Transport\DoctrineDBAL;

use Doctrine\DBAL\Schema\Schema;

class RepositorySchema
{
    static public function create()
    {
        $schema = new Schema();
        $namespace = $schema->createTable('phpcr_namespaces');
        $namespace->addColumn('prefix', 'string');
        $namespace->addColumn('uri', 'string');
        $namespace->setPrimaryKey(array('prefix'));

        $workspace = $schema->createTable('phpcr_workspaces');
        $workspace->addColumn('id', 'integer', array('autoincrement' => true));
        $workspace->addColumn('name', 'string');
        $workspace->setPrimaryKey(array('id'));

        $nodes = $schema->createTable('phpcr_nodes');
        $nodes->addColumn('id', 'integer', array('autoincrement' => true));
        $nodes->addColumn('path', 'string', array('length' => 500));
        $nodes->addColumn('parent', 'string', array('length' => 500));
        $nodes->addColumn('workspace_id', 'integer');
        $nodes->addColumn('identifier', 'string');
        $nodes->addColumn('type', 'string');
        $nodes->addColumn('props', 'text');
        $nodes->setPrimaryKey(array('id'));
        $nodes->addUniqueIndex(array('path', 'workspace_id'));
        $nodes->addUniqueIndex(array('identifier'));
        $nodes->addIndex(array('parent'));
        $nodes->addIndex(array('type'));
        
        $indexJcrTypes = $schema->createTable('phpcr_internal_index_types');
        $indexJcrTypes->addColumn('type', 'string');
        $indexJcrTypes->addColumn('node_id', 'integer');
        $indexJcrTypes->setPrimaryKey(array('type', 'node_id'));

        $binary = $schema->createTable('phpcr_binarydata');
        $binary->addColumn('id', 'integer', array('autoincrement' => true));
        $binary->addColumn('node_id', 'integer');
        $binary->addColumn('property_name', 'string');
        $binary->addColumn('workspace_id', 'integer');
        $binary->addColumn('idx', 'integer', array('default' => 0));
        $binary->addColumn('data', 'text'); // TODO BLOB!
        $binary->setPrimaryKey(array('id'));
        $binary->addUniqueIndex(array('node_id', 'property_name', 'workspace_id', 'idx'));
        
        $foreignKeys = $schema->createTable('phpcr_nodes_foreignkeys');
        $foreignKeys->addColumn('source_id', 'integer');
        $foreignKeys->addColumn('source_property_name', 'string');
        $foreignKeys->addColumn('target_id', 'integer');
        $foreignKeys->addColumn('type', 'smallint');
        $foreignKeys->setPrimaryKey(array('source_id', 'source_property_name', 'target_id'));
        $foreignKeys->addIndex(array('target_id'));
        // TODO: Add Foreign Keys to phpcr_nodes table

        $types = $schema->createTable('phpcr_type_nodes');
        $types->addColumn('node_type_id', 'integer', array('autoincrement' => true));
        $types->addColumn('name', 'string');
        $types->addColumn('supertypes', 'string');
        $types->addColumn('is_abstract', 'boolean');
        $types->addColumn('protected', 'boolean');
        $types->addColumn('is_mixin', 'boolean');
        $types->addColumn('queryable', 'boolean');
        $types->addColumn('primary_item', 'string');
        $types->setPrimaryKey(array('node_type_id'));

        $propTypes = $schema->createTable('phpcr_type_props');
        $propTypes->addColumn('node_type_id', 'integer');
        $propTypes->addColumn('name', 'string');
        $propTypes->addColumn('protected', 'boolean');
        $propTypes->addColumn('auto_created', 'boolean');
        $propTypes->addColumn('mandatory', 'boolean');
        $propTypes->addColumn('property_type', 'integer');
        $propTypes->setPrimaryKey(array('node_type_id', 'name'));

        #$propContraints = $schema->createTable('phpcr_type_props_contraints');

        $childTypes = $schema->createTable('phpcr_type_childs');
        $childTypes->addColumn('node_type_id', 'integer');
        $childTypes->addColumn('name', 'string');
        $childTypes->addColumn('primary_types', 'string');
        $childTypes->addColumn('default_type', 'string');

        return $schema;
    }
}
