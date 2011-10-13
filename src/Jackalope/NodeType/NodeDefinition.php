<?php
namespace Jackalope\NodeType;

use Jackalope\Helper;
use \DOMElement, \DOMXPath;

// inherit all doc
/**
 * @api
 */
class NodeDefinition extends ItemDefinition implements \PHPCR\NodeType\NodeDefinitionInterface
{
    const DEFAULT_PRIMARY_NODE = 'nt:base';

    /**
     * Cached list of NodeType instances populated in first call to getRequiredPrimaryTypes
     * @var array
     */
    protected $requiredPrimaryTypes = array();
    /**
     * List of required primary type names as string.
     * @var array
     */
    protected $requiredPrimaryTypeNames = array();
    /**
     * @var string
     */
    protected $defaultPrimaryTypeName;
    /**
     * @var boolean
     */
    protected $allowsSameNameSiblings;

    /**
     * Create a new node definition instance.
     *
     * @param object $factory an object factory implementing "get" as
     *      described in \Jackalope\Factory
     * @param mixed $node The node data either as DOMElement or as array
     * @param NodeTypeManager $nodeTypeManager
     */
    public function __construct($factory, $node, NodeTypeManager $nodeTypeManager)
    {
        parent::__construct($factory, $node, $nodeTypeManager);

        if ($node instanceof DOMElement) {
            $this->fromXML($node);
        } elseif (is_array($node)) {
            $this->fromArray($node);
        } else {
            throw new \InvalidArgumentException(get_type($node).' is not valid to create a NodeDefinition from');
        }
    }

    /**
     * Read more information in addition to ItemDefinition::fromXML()
     */
    protected function fromXML(DOMElement $node)
    {
        parent::fromXML($node);
        $this->allowsSameNameSiblings = Helper::getBoolAttribute($node, 'sameNameSiblings');
        $this->defaultPrimaryTypeName = $node->getAttribute('defaultPrimaryType');
        if (empty($this->defaultPrimaryTypeName)) {
            $this->defaultPrimaryTypeName = null;
        }

        $xp = new DOMXPath($node->ownerDocument);
        $requiredPrimaryTypes = $xp->query('requiredPrimaryTypes/requiredPrimaryType', $node);
        if (0 < $requiredPrimaryTypes->length) {
            foreach ($requiredPrimaryTypes as $requiredPrimaryType) {
                $this->requiredPrimaryTypeNames[] = $requiredPrimaryType->nodeValue;
            }
        } else {
            $this->requiredPrimaryTypeNames[] = self::DEFAULT_PRIMARY_NODE;
        }
    }

    /**
     * Read more information in addition to ItemDefinition::fromArray()
     */
    protected function fromArray(array $data)
    {
        parent::fromArray($data);
        $this->allowsSameNameSiblings = $data['allowsSameNameSiblings'];
        $this->defaultPrimaryTypeName = isset($data['defaultPrimaryTypeName']) ? $data['defaultPrimaryTypeName'] : null;
        $this->requiredPrimaryTypeNames = (isset($data['requiredPrimaryTypeNames']) && count($data['requiredPrimaryTypeNames']))
                ? $data['requiredPrimaryTypeNames'] : array(self::DEFAULT_PRIMARY_NODE);
    }

    // inherit all doc
    /**
     * @api
     */
    public function getRequiredPrimaryTypes()
    {
        // TODO if this is not attached to a live NodeType, return null
        if (empty($this->requiredPrimaryTypes)) {
            foreach ($this->requiredPrimaryTypeNames as $primaryTypeName) {
                $this->requiredPrimaryTypes[] = $this->nodeTypeManager->getNodeType($primaryTypeName);
            }
        }
        return $this->requiredPrimaryTypes;
    }

    // inherit all doc
    /**
     * @api
     */
    public function getRequiredPrimaryTypeNames()
    {
        return $this->requiredPrimaryTypeNames;
    }

    // inherit all doc
    /**
     * @api
     */
    public function getDefaultPrimaryType()
    {
        if (null === $this->defaultPrimaryTypeName) {
            return null;
        }
        return $this->nodeTypeManager->getNodeType($this->defaultPrimaryTypeName);
    }

    // inherit all doc
    /**
     * @api
     */
    public function getDefaultPrimaryTypeName()
    {
        return $this->defaultPrimaryTypeName;
    }

    // inherit all doc
    /**
     * @api
     */
    public function allowsSameNameSiblings()
    {
        return $this->allowsSameNameSiblings;
    }
}
