<?php

namespace Jackalope\NodeType;

use DOMElement, DOMXPath, ArrayObject;
use Jackalope\Helper;
use DOMDocument;

/**
 * Converter to generate NodeType elements array from storage XML (jackrabbit format).
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
 * @subpackage nodetype
 */
class NodeTypeXmlConverter
{
    const DEFAULT_PRIMARY_NODE = 'nt:base';

    /**
     * @param \DOMElement $node
     * @return array
     */
    public function getItemDefinitionFromXml(DOMElement $node)
    {
        $data = array();
        $data['declaringNodeType'] = $node->getAttribute('declaringNodeType');
        $data['name'] = $node->getAttribute('name');
        $data['isAutoCreated'] = Helper::getBoolAttribute($node, 'isAutoCreated');
        $data['isMandatory'] = Helper::getBoolAttribute($node, 'mandatory');
        $data['isProtected'] = Helper::getBoolAttribute($node, 'isProtected');
        $data['onParentVersion'] = \PHPCR\Version\OnParentVersionAction::valueFromName($node->getAttribute('onParentVersion'));

        return $data;
    }

    /**
     * Convert property definition xml into array.
     *
     * @param  \DOMElement $node
     * @return array
     */
    public function getPropertyDefinitionFromXml(DOMElement $node)
    {
        $data = $this->getItemDefinitionFromXml($node);

        $data['requiredType'] = \PHPCR\PropertyType::valueFromName($node->getAttribute('requiredType'));
        $data['multiple'] = Helper::getBoolAttribute($node, 'multiple');
        $data['fullTextSearchable'] = Helper::getBoolAttribute($node, 'fullTextSearchable');
        $data['queryOrderable'] = Helper::getBoolAttribute($node, 'queryOrderable');

        $xp = new DOMXPath($node->ownerDocument);
        $valueConstraints = $xp->query('valueConstraints/valueConstraint', $node);
        foreach ($valueConstraints as $valueConstraint) {
            $data['valueConstraints'][] = $valueConstraint->nodeValue;
        }

        $availableQueryOperators = $xp->query('availableQueryOperators/availableQueryOperator', $node);
        foreach ($availableQueryOperators as $availableQueryOperator) {
            $data['availableQueryOperators'][] = $availableQueryOperator->nodeValue;
        }

        $defaultValues = $xp->query('defaultValues/defaultValue', $node);
        foreach ($defaultValues as $defaultValue) {
            $data['defaultValues'][] = $defaultValue->nodeValue;
        }
        return $data;
    }

    /**
     * Convert Node Definition XML into array.
     *
     * @param  \DOMElement $node
     * @return array
     */
    public function getNodeDefinitionFromXml(DOMElement $node)
    {
        $data = $this->getItemDefinitionFromXml($node);
        
        // node
        $data['allowsSameNameSiblings'] = Helper::getBoolAttribute($node, 'sameNameSiblings');
        $data['defaultPrimaryTypeName'] = $node->getAttribute('defaultPrimaryType') ?: null;

        $xp = new DOMXPath($node->ownerDocument);
        $requiredPrimaryTypes = $xp->query('requiredPrimaryTypes/requiredPrimaryType', $node);
        if (0 < $requiredPrimaryTypes->length) {
            foreach ($requiredPrimaryTypes as $requiredPrimaryType) {
                $data['requiredPrimaryTypeNames'][] = $requiredPrimaryType->nodeValue;
            }
        } else {
            $data['requiredPrimaryTypeNames'] = array(self::DEFAULT_PRIMARY_NODE);
        }

        return $data;
    }

    /**
     * Convert NodeTypeDefintion XML into array.
     *
     * @param \DOMElement $node
     * @return array
     */
    public function getNodeTypeDefinitionFromXml(DOMElement $node)
    {
        $data = array();
        // nodetype
        $data['name'] = $node->getAttribute('name');
        $data['isAbstract'] = Helper::getBoolAttribute($node, 'isAbstract');
        $data['isMixin'] = Helper::getBoolAttribute($node, 'isMixin');
        $data['isQueryable'] = Helper::getBoolAttribute($node, 'isQueryable');
        $data['hasOrderableChildNodes'] = Helper::getBoolAttribute($node, 'hasOrderableChildNodes');

        $data['primaryItemName'] = $node->getAttribute('primaryItemName') ?: null;

        $data['declaredSuperTypeNames'] = array();
        $xp = new DOMXPath($node->ownerDocument);
        $supertypes = $xp->query('supertypes/supertype', $node);
        foreach ($supertypes as $supertype) {
            $data['declaredSuperTypeNames'][] = $supertype->nodeValue;
        }

        $data['declaredPropertyDefinitions'] = array();
        $properties = $xp->query('propertyDefinition', $node);
        foreach ($properties as $propertyDefinition) {
            $data['declaredPropertyDefinitions'][] = $this->getPropertyDefinitionFromXml($propertyDefinition);
        }

        $data['declaredNodeDefinitions'] = array();
        $declaredNodeDefinitions = $xp->query('childNodeDefinition', $node);
        foreach ($declaredNodeDefinitions as $nodeDefinition) {
            $data['declaredNodeDefinitions'][] = $this->getNodeDefinitionFromXml($nodeDefinition);
        }

        return $data;
    }

    public function getNodeTypesFromXml(DOMDocument $dom)
    {
        $xp = new \DOMXpath($dom);
        $nodeTypesElements = $xp->query('/nodeTypes/nodeType');
        $nodeTypes = array();
        foreach ($nodeTypesElements as $nodeTypeElement) {
            $nodeTypes[] = $this->getNodeTypeDefinitionFromXml($nodeTypeElement);
        }
        return $nodeTypes;
    }
}
