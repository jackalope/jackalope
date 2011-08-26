<?php
namespace Jackalope\NodeType;

use Jackalope\Helper;
use \DOMElement, \DOMXPath;

// inherit all doc
/**
 * @api
 */
class PropertyDefinition extends ItemDefinition implements \PHPCR\NodeType\PropertyDefinitionInterface
{
    /**
     * One of the PropertyType type constants
     * @var int
     */
    protected $requiredType;
    /**
     * The constraint information array (array of strings)
     * @var array
     */
    protected $valueConstraints = array();
    /**
     * @var mixed
     */
    protected $defaultValues = array();
    /**
     * @var boolean
     */
    protected $isMultiple;
    /**
     * List of constants from \PHPCR\Query\QueryObjectModelConstants
     * @var array
     */
    protected $availableQueryOperators = array();
    /**
     * @var boolean
     */
    protected $isFullTextSearchable;
    /**
     * @var boolean
     */
    protected $isQueryOrderable;

    /**
     * Create a new property definition instance.
     *
     * @param object $factory an object factory implementing "get" as
     *      described in \Jackalope\Factory
     * @param mixed $node The property data either as DOMElement or as array
     * @param NodeTypeManager $nodeTypeManager
     */
    public function __construct($factory, $node, NodeTypeManager $nodeTypeManager)
    {
        parent::__construct($factory, $node, $nodeTypeManager);

        if ($node instanceof DOMElement) {
            $this->fromXML($node);
        } elseif (is_array($node)) {
            $this->fromArray($node);
        }
    }

    /**
     * Read more information in addition to ItemDefinition::fromXML()
     */
    protected function fromXML(DOMElement $node)
    {
        parent::fromXML($node);
        $this->requiredType = \PHPCR\PropertyType::valueFromName($node->getAttribute('requiredType'));
        $this->isMultiple = Helper::getBoolAttribute($node, 'multiple');
        $this->isFullTextSearchable = Helper::getBoolAttribute($node, 'fullTextSearchable');
        $this->isQueryOrderable = Helper::getBoolAttribute($node, 'queryOrderable');

        $xp = new DOMXPath($node->ownerDocument);
        $valueConstraints = $xp->query('valueConstraints/valueConstraint', $node);
        foreach ($valueConstraints as $valueConstraint) {
            $this->valueConstraints[] = $valueConstraint->nodeValue;
        }

        $availableQueryOperators = $xp->query('availableQueryOperators/availableQueryOperator', $node);
        foreach ($availableQueryOperators as $availableQueryOperator) {
            $this->availableQueryOperators[] = $availableQueryOperator->nodeValue;
        }

        $defaultValues = $xp->query('defaultValues/defaultValue', $node);
        foreach ($defaultValues as $defaultValue) {
            $this->defaultValues[] = $defaultValue->nodeValue;
        }
    }

    /**
     * Read more information in addition to ItemDefinition::fromArray()
     */
    protected function fromArray(array $data)
    {
        parent::fromArray($data);
        $this->requiredType = $data['requiredType'];
        $this->isMultiple = isset($data['multiple']) ? $data['multiple'] : false;
        $this->isFullTextSearchable = isset($data['fullTextSearchable']) ? $data['fullTextSearchable'] : false;
        $this->isQueryOrderable = isset($data['queryOrderable']) ? $data['queryOrderable'] : false;
        $this->valueConstraints = isset($data['valueConstraints']) ? $data['valueConstraints'] : array();
        $this->availableQueryOperators = isset($data['availableQueryOperators']) ? $data['availableQueryOperators'] : array();
        $this->defaultValues = isset($data['defaultValues']) ? $data['defaultValues'] : array();
    }

    // inherit all doc
    /**
     * @api
     */
    public function getRequiredType()
    {
        return $this->requiredType;
    }

    // inherit all doc
    /**
     * @api
     */
    public function getValueConstraints()
    {
        return $this->valueConstraints;
    }

    // inherit all doc
    /**
     * @api
     */
    public function getDefaultValues()
    {
        return $this->defaultValues;
    }

    // inherit all doc
    /**
     * @api
     */
    public function isMultiple()
    {
        return $this->isMultiple;
    }

    // inherit all doc
    /**
     * @api
     */
    public function getAvailableQueryOperators()
    {
        return $this->availableQueryOperators;
    }

    // inherit all doc
    /**
     * @api
     */
    public function isFullTextSearchable()
    {
        return $this->isFullTextSearchable;
    }

    // inherit all doc
    /**
     * @api
     */
    public function isQueryOrderable()
    {
        return $this->isQueryOrderable;
    }
}
