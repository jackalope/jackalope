<?php

namespace Jackalope\NodeType;

/**
 * Returns all the default nodes provided by the JCR 2.0 specification in the array
 * data format that is required for every TransportInterface::getNodeTypes() to return
 * back to the ObjectManager.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class JCR2StandardNodeTypes
{
    /**
     * @return array
     */
    static public function getNodeTypeData()
    {
        return array(
            0 =>
            array(
                'name' => 'nt:base',
                'isAbstract' => true,
                'isMixin' => false,
                'isQueryable' => true,
                'hasOrderableChildNodes' => true,
                'primaryItemName' => NULL,
                'declaredSuperTypeNames' =>
                array(
                ),
                'declaredPropertyDefinitions' =>
                array(
                    0 =>
                    array(
                        'declaringNodeType' => '',
                        'name' => 'jcr:primaryType',
                        'isAutoCreated' => true,
                        'isMandatory' => true,
                        'isProtected' => true,
                        'onParentVersion' => 4,
                        'requiredType' => 7,
                        'multiple' => true,
                        'fullTextSearchable' => true,
                        'queryOrderable' => true,
                    ),
                    1 =>
                    array(
                        'declaringNodeType' => '',
                        'name' => 'jcr:mixinTypes',
                        'isAutoCreated' => true,
                        'isMandatory' => true,
                        'isProtected' => true,
                        'onParentVersion' => 4,
                        'requiredType' => 7,
                        'multiple' => true,
                        'fullTextSearchable' => true,
                        'queryOrderable' => true,
                    ),
                ),
                'declaredNodeDefinitions' =>
                array(
                ),
            ),
            1 =>
            array(
                'name' => 'nt:unstructured',
                'isAbstract' => false,
                'isMixin' => false,
                'isQueryable' => true,
                'hasOrderableChildNodes' => true,
                'primaryItemName' => NULL,
                'declaredSuperTypeNames' =>
                array(
                    0 => 'nt:base',
                ),
                'declaredPropertyDefinitions' =>
                array(
                    0 =>
                    array(
                        'declaringNodeType' => 'nt:unstructured',
                        'name' => '*',
                        'isAutoCreated' => true,
                        'isMandatory'   => false,
                        'isProtected'   => true,
                        'onParentVersion' => 1,
                        'requiredType' => 0,
                        'multiple' => true,
                        'fullTextSearchable' => true,
                        'queryOrderable' => true,
                    ),
                ),
                'declaredNodeDefinitions' =>
                array(
                    0 =>
                    array(
                        'declaringNodeType' => 'nt:unstructured',
                        'name' => '*',
                        'isAutoCreated' => true,
                        'isMandatory' => false,
                        'isProtected' => true,
                        'onParentVersion' => 2,
                        'allowsSameNameSiblings' => false,
                        'defaultPrimaryTypeName' => 'nt:unstructured',
                        'requiredPrimaryTypeNames' =>
                        array(
                            0 => 'nt:base',
                        ),
                    ),
                ),
            ),
            2 =>
            array(
                'name' => 'mix:etag',
                'isAbstract' => true,
                'isMixin' => true,
                'isQueryable' => true,
                'hasOrderableChildNodes' => true,
                'primaryItemName' => NULL,
                'declaredSuperTypeNames' =>
                array(
                ),
                'declaredPropertyDefinitions' =>
                array(
                    0 =>
                    array(
                        'declaringNodeType' => '',
                        'name' => 'jcr:etag',
                        'isAutoCreated' => true,
                        'isMandatory' => true,
                        'isProtected' => true,
                        'onParentVersion' => 4,
                        'requiredType' => 1,
                        'multiple' => true,
                        'fullTextSearchable' => true,
                        'queryOrderable' => true,
                    ),
                ),
                'declaredNodeDefinitions' =>
                array(
                ),
            ),
            3 =>
            array(
                'name' => 'nt:hierachy',
                'isAbstract' => true,
                'isMixin' => true,
                'isQueryable' => true,
                'hasOrderableChildNodes' => true,
                'primaryItemName' => NULL,
                'declaredSuperTypeNames' =>
                array(
                    0 => 'mix:created',
                ),
                'declaredPropertyDefinitions' =>
                array(
                ),
                'declaredNodeDefinitions' =>
                array(
                ),
            ),
            4 =>
            array(
                'name' => 'nt:file',
                'isAbstract' => false,
                'isMixin' => false,
                'isQueryable' => true,
                'hasOrderableChildNodes' => true,
                'primaryItemName' => 'jcr:content',
                'declaredSuperTypeNames' =>
                array(
                    0 => 'nt:hierachy',
                ),
                'declaredPropertyDefinitions' =>
                array(
                ),
                'declaredNodeDefinitions' =>
                array(
                ),
            ),
            5 =>
            array(
                'name' => 'nt:folder',
                'isAbstract' => false,
                'isMixin' => false,
                'isQueryable' => true,
                'hasOrderableChildNodes' => true,
                'primaryItemName' => NULL,
                'declaredSuperTypeNames' =>
                array(
                    0 => 'nt:hierachy',
                ),
                'declaredPropertyDefinitions' =>
                array(
                ),
                'declaredNodeDefinitions' =>
                array(
                ),
            ),
            6 =>
            array(
                'name' => 'nt:resource',
                'isAbstract' => false,
                'isMixin' => false,
                'isQueryable' => true,
                'hasOrderableChildNodes' => true,
                'primaryItemName' => 'jcr:data',
                'declaredSuperTypeNames' =>
                array(
                    0 => 'mix:mimeType',
                    1 => 'mix:modified',
                ),
                'declaredPropertyDefinitions' =>
                array(
                    0 =>
                    array(
                        'declaringNodeType' => '',
                        'name' => 'jcr:created',
                        'isAutoCreated' => true,
                        'isMandatory' => true,
                        'isProtected' => true,
                        'onParentVersion' => 1,
                        'requiredType' => 2,
                        'multiple' => true,
                        'fullTextSearchable' => true,
                        'queryOrderable' => true,
                    ),
                ),
                'declaredNodeDefinitions' =>
                array(
                ),
            ),
            7 =>
            array(
                'name' => 'mix:created',
                'isAbstract' => true,
                'isMixin' => true,
                'isQueryable' => true,
                'hasOrderableChildNodes' => true,
                'primaryItemName' => NULL,
                'declaredSuperTypeNames' =>
                array(
                ),
                'declaredPropertyDefinitions' =>
                array(
                    0 =>
                    array(
                        'declaringNodeType' => '',
                        'name' => 'jcr:created',
                        'isAutoCreated' => true,
                        'isMandatory' => true,
                        'isProtected' => true,
                        'onParentVersion' => 4,
                        'requiredType' => 5,
                        'multiple' => true,
                        'fullTextSearchable' => true,
                        'queryOrderable' => true,
                    ),
                    1 =>
                    array(
                        'declaringNodeType' => '',
                        'name' => 'jcr:createdBy',
                        'isAutoCreated' => true,
                        'isMandatory' => true,
                        'isProtected' => true,
                        'onParentVersion' => 4,
                        'requiredType' => 1,
                        'multiple' => true,
                        'fullTextSearchable' => true,
                        'queryOrderable' => true,
                    ),
                ),
                'declaredNodeDefinitions' =>
                array(
                ),
            ),
            8 =>
            array(
                'name' => 'mix:mimeType',
                'isAbstract' => true,
                'isMixin' => true,
                'isQueryable' => true,
                'hasOrderableChildNodes' => true,
                'primaryItemName' => NULL,
                'declaredSuperTypeNames' =>
                array(
                ),
                'declaredPropertyDefinitions' =>
                array(
                    0 =>
                    array(
                        'declaringNodeType' => '',
                        'name' => 'jcr:mimeType',
                        'isAutoCreated' => true,
                        'isMandatory' => true,
                        'isProtected' => true,
                        'onParentVersion' => 1,
                        'requiredType' => 5,
                        'multiple' => true,
                        'fullTextSearchable' => true,
                        'queryOrderable' => true,
                    ),
                    1 => array(
                        'declaringNodeType' => '',
                        'name' => 'jcr:encoding',
                        'isAutoCreated' => true,
                        'isMandatory' => true,
                        'isProtected' => true,
                        'onParentVersion' => 1,
                        'requiredType' => 1,
                        'multiple' => true,
                        'fullTextSearchable' => true,
                        'queryOrderable' => true,
                    ),
                ),
                'declaredNodeDefinitions' =>
                array(
                ),
            ),
            9 =>
            array(
                'name' => 'mix:lastModified',
                'isAbstract' => true,
                'isMixin' => true,
                'isQueryable' => true,
                'hasOrderableChildNodes' => true,
                'primaryItemName' => NULL,
                'declaredSuperTypeNames' =>
                array(
                ),
                'declaredPropertyDefinitions' =>
                array(
                    0 =>
                    array(
                        'declaringNodeType' => '',
                        'name' => 'jcr:lastModified',
                        'isAutoCreated' => true,
                        'isMandatory' => true,
                        'isProtected' => true,
                        'onParentVersion' => 4,
                        'requiredType' => 5,
                        'multiple' => true,
                        'fullTextSearchable' => true,
                        'queryOrderable' => true,
                    ),
                    1 =>
                    array(
                        'declaringNodeType' => '',
                        'name' => 'jcr:lastModifiedBy',
                        'isAutoCreated' => true,
                        'isMandatory' => true,
                        'isProtected' => true,
                        'onParentVersion' => 4,
                        'requiredType' => 1,
                        'multiple' => true,
                        'fullTextSearchable' => true,
                        'queryOrderable' => true,
                    ),
                ),
                'declaredNodeDefinitions' =>
                array(
                ),
            ),
            10 => array(
                'name' => 'mix:referenceable',
                'isAbstract' => false,
                'isMixin' => true,
                'isQueryable' => true,
                'hasOrderableChildNodes' => true,
                'primaryItemName' => NULL,
                'declaredSuperTypeNames' =>
                array(
                ),
                'declaredPropertyDefinitions' =>
                array(
                    0 =>
                    array(
                        'declaringNodeType' => '',
                        'name' => 'jcr:uuid',
                        'isAutoCreated' => true,
                        'isMandatory' => true,
                        'isProtected' => true,
                        'onParentVersion' => 4,
                        'requiredType' => 1,
                        'multiple' => false,
                        'fullTextSearchable' => true,
                        'queryOrderable' => true,
                    ),
                ),
                'declaredNodeDefinitions' =>
                array(
                ),
            ),
            11 => array(
                'name' => 'mix:language',
                'isAbstract' => false,
                'isMixin' => true,
                'isQueryable' => true,
                'hasOrderableChildNodes' => true,
                'primaryItemName' => NULL,
                'declaredSuperTypeNames' =>
                array(
                ),
                'declaredPropertyDefinitions' =>
                array(
                    0 =>
                    array(
                        'declaringNodeType' => '',
                        'name' => 'jcr:language',
                        'isAutoCreated' => true,
                        'isMandatory' => true,
                        'isProtected' => true,
                        'onParentVersion' => 4,
                        'requiredType' => 1,
                        'multiple' => false,
                        'fullTextSearchable' => true,
                        'queryOrderable' => true,
                    ),
                ),
                'declaredNodeDefinitions' =>
                array(
                ),
            ),
            12 => array(
                'name' => 'mix:shareable',
                'isAbstract' => false,
                'isMixin' => true,
                'isQueryable' => true,
                'hasOrderableChildNodes' => true,
                'primaryItemName' => NULL,
                'declaredSuperTypeNames' => array('mix:referenceable'),
                'declaredPropertyDefinitions' => array(),
                'declaredNodeDefinitions' => array(),
            ),
            13 => array(
                'name' => 'mix:title',
                'isAbstract' => false,
                'isMixin' => true,
                'isQueryable' => true,
                'hasOrderableChildNodes' => true,
                'primaryItemName' => NULL,
                'declaredSuperTypeNames' =>
                array(
                ),
                'declaredPropertyDefinitions' =>
                array(
                    0 => array(
                        'declaringNodeType' => '',
                        'name' => 'jcr:title',
                        'isAutoCreated' => false,
                        'isMandatory' => false,
                        'isProtected' => false,
                        'onParentVersion' => 4,
                        'requiredType' => 1,
                        'multiple' => false,
                        'fullTextSearchable' => false,
                        'queryOrderable' => false,
                    ),
                    1 => array(
                        'declaringNodeType' => '',
                        'name' => 'jcr:description',
                        'isAutoCreated' => false,
                        'isMandatory' => false,
                        'isProtected' => false,
                        'onParentVersion' => 4,
                        'requiredType' => 1,
                        'multiple' => false,
                        'fullTextSearchable' => false,
                        'queryOrderable' => false,
                    ),
                ),
                'declaredNodeDefinitions' =>
                array(
                ),
            ),
            14 =>
            array(
                'name' => 'nt:linkedFile',
                'isAbstract' => false,
                'isMixin' => false,
                'isQueryable' => true,
                'hasOrderableChildNodes' => true,
                'primaryItemName' => 'jcr:content',
                'declaredSuperTypeNames' =>
                array(
                    0 => 'nt:hierachy',
                ),
                'declaredPropertyDefinitions' => array(
                    array(
                        'declaringNodeType' => '',
                        'name' => 'jcr:content',
                        'isAutoCreated' => false,
                        'isMandatory' => true,
                        'isProtected' => false,
                        'onParentVersion' => 4,
                        'requiredType' => \PHPCR\PropertyType::REFERENCE,
                        'multiple' => false,
                        'fullTextSearchable' => false,
                        'queryOrderable' => false,
                    ),
                ),
                'declaredNodeDefinitions' =>
                array(
                ),
            ),
        );
    }
}