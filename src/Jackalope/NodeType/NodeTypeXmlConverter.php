<?php

namespace Jackalope\NodeType;

use Jackalope\FactoryInterface;
use Jackalope\Helper;
use PHPCR\PropertyType;
use PHPCR\RepositoryException;
use PHPCR\Version\OnParentVersionAction;

/**
 * Converter to generate NodeType elements array from storage XML (jackrabbit
 * format).
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 */
class NodeTypeXmlConverter
{
    private const DEFAULT_PRIMARY_NODE = 'nt:base';

    /**
     * Empty constructor.
     *
     * Everything inside jackalope has to accept the factory in the
     * constructor. We define the constructor but do nothing at all.
     *
     * @param FactoryInterface $factory the object factory
     */
    public function __construct(FactoryInterface $factory)
    {
    }

    /**
     * @return array
     */
    public function getItemDefinitionFromXml(\DOMElement $node)
    {
        $data = [];
        $data['declaringNodeType'] = $node->getAttribute('declaringNodeType');
        $data['name'] = $node->getAttribute('name');
        $data['isAutoCreated'] = Helper::getBoolAttribute($node, 'autoCreated');
        $data['isMandatory'] = Helper::getBoolAttribute($node, 'mandatory');
        $data['isProtected'] = Helper::getBoolAttribute($node, 'protected');
        $data['onParentVersion'] = OnParentVersionAction::valueFromName($node->getAttribute('onParentVersion'));

        return $data;
    }

    /**
     * Convert property definition xml into array.
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    public function getPropertyDefinitionFromXml(\DOMElement $node)
    {
        $data = $this->getItemDefinitionFromXml($node);

        $data['requiredType'] = PropertyType::valueFromName($node->getAttribute('requiredType'));
        $data['multiple'] = Helper::getBoolAttribute($node, 'multiple');
        $data['fullTextSearchable'] = Helper::getBoolAttribute($node, 'fullTextSearchable');
        $data['queryOrderable'] = Helper::getBoolAttribute($node, 'queryOrderable');

        $xp = new \DOMXPath($node->ownerDocument);
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
     * @return array
     */
    public function getNodeDefinitionFromXml(\DOMElement $node)
    {
        $data = $this->getItemDefinitionFromXml($node);

        // node
        $data['allowsSameNameSiblings'] = Helper::getBoolAttribute($node, 'sameNameSiblings');
        $data['defaultPrimaryTypeName'] = $node->getAttribute('defaultPrimaryType') ?: null;

        $xp = new \DOMXPath($node->ownerDocument);
        $requiredPrimaryTypes = $xp->query('requiredPrimaryTypes/requiredPrimaryType', $node);
        if (0 < $requiredPrimaryTypes->length) {
            foreach ($requiredPrimaryTypes as $requiredPrimaryType) {
                $data['requiredPrimaryTypeNames'][] = $requiredPrimaryType->nodeValue;
            }
        } else {
            $data['requiredPrimaryTypeNames'] = [self::DEFAULT_PRIMARY_NODE];
        }

        return $data;
    }

    /**
     * Convert NodeTypeDefinition XML into array.
     *
     * @return array
     *
     * @throws RepositoryException
     * @throws \InvalidArgumentException
     */
    public function getNodeTypeDefinitionFromXml(\DOMElement $node)
    {
        $data = [];
        // nodetype
        $data['name'] = $node->getAttribute('name');
        $data['isAbstract'] = Helper::getBoolAttribute($node, 'isAbstract');
        $data['isMixin'] = Helper::getBoolAttribute($node, 'isMixin');
        $data['isQueryable'] = Helper::getBoolAttribute($node, 'isQueryable');
        $data['hasOrderableChildNodes'] = Helper::getBoolAttribute($node, 'hasOrderableChildNodes');

        $data['primaryItemName'] = $node->getAttribute('primaryItemName') ?: null;

        $data['declaredSuperTypeNames'] = [];
        $xp = new \DOMXPath($node->ownerDocument);
        $supertypes = $xp->query('supertypes/supertype', $node);
        foreach ($supertypes as $supertype) {
            $data['declaredSuperTypeNames'][] = $supertype->nodeValue;
        }

        $data['declaredPropertyDefinitions'] = [];
        $properties = $xp->query('propertyDefinition', $node);
        foreach ($properties as $propertyDefinition) {
            $data['declaredPropertyDefinitions'][] = $this->getPropertyDefinitionFromXml($propertyDefinition);
        }

        $data['declaredNodeDefinitions'] = [];
        $declaredNodeDefinitions = $xp->query('childNodeDefinition', $node);
        foreach ($declaredNodeDefinitions as $nodeDefinition) {
            $data['declaredNodeDefinitions'][] = $this->getNodeDefinitionFromXml($nodeDefinition);
        }

        return $data;
    }

    public function getNodeTypesFromXml(\DOMDocument $dom)
    {
        $xp = new \DOMXPath($dom);
        $nodeTypesElements = $xp->query('/nodeTypes/nodeType');
        $nodeTypes = [];
        foreach ($nodeTypesElements as $nodeTypeElement) {
            $nodeTypes[] = $this->getNodeTypeDefinitionFromXml($nodeTypeElement);
        }

        return $nodeTypes;
    }
}
