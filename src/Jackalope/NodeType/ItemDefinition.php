<?php
namespace Jackalope\NodeType;

use Jackalope\Helper;
use \DOMElement;

// inherit all doc
/**
 * @api
 */
class ItemDefinition implements \PHPCR\NodeType\ItemDefinitionInterface
{
    /**
     * The factory to instantiate objects
     * @var \Jackalope\Factory
     */
    protected $factory;

    /**
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * Name of the declaring node type.
     * @var string
     */
    protected $declaringNodeType;
    /**
     * Name of this node type.
     * @var string
     */
    protected $name;
    /**
     * Whether this item is autocreated.
     * @var boolean
     */
    protected $isAutoCreated;
    /**
     * Whether this item is mandatory.
     * @var boolean
     */
    protected $isMandatory;
    /**
     * Whether this item is protected.
     * @var boolean
     */
    protected $isProtected;
    /**
     * On parent version constant
     * @var int
     */
    protected $onParentVersion;

    /**
     * Create a new item definition.
     *
     * @param object $factory Ignored for now, as this class does not create objects
     */
    public function __construct($factory, $node, NodeTypeManager $nodeTypeManager)
    {
        $this->factory = $factory;
        $this->nodeTypeManager = $nodeTypeManager;
    }

    /**
     * Load item definition from xml fragment.
     *
     * @param \DOMElement $node The node containing the information for this
     *      item definition
     *
     * @return void
     */
    protected function fromXML(DOMElement $node)
    {
        $this->declaringNodeType = $node->getAttribute('declaringNodeType');
        $this->name = $node->getAttribute('name');
        $this->isAutoCreated = Helper::getBoolAttribute($node, 'isAutoCreated');
        $this->isMandatory = Helper::getBoolAttribute($node, 'mandatory');
        $this->isProtected = Helper::getBoolAttribute($node, 'isProtected');
        $this->onParentVersion = \PHPCR\Version\OnParentVersionAction::valueFromName($node->getAttribute('onParentVersion'));
    }

    /**
     * Load item definition from an array.
     *
     * @param array $data An array with the fields required by ItemDefition
     *
     * @return void
     */
    protected function fromArray(array $data)
    {
        $this->declaringNodeType = $data['declaringNodeType'];
        $this->name = $data['name'];
        $this->isAutoCreated = $data['isAutoCreated'];
        $this->isMandatory = isset($data['mandatory']) ? $data['mandatory'] : false;
        $this->isProtected = $data['isProtected'];
        $this->onParentVersion = $data['onParentVersion'];
    }

    // inherit all doc
    /**
     * @api
     */
    public function getDeclaringNodeType()
    {
        return $this->nodeTypeManager->getNodeType($this->declaringNodeType);
    }

    // inherit all doc
    /**
     * @api
     */
    public function getName()
    {
        return $this->name;
    }

    // inherit all doc
    /**
     * @api
     */
    public function isAutoCreated()
    {
        return $this->isAutoCreated;
    }

    // inherit all doc
    /**
     * @api
     */
    public function isMandatory()
    {
        return $this->isMandatory;
    }

    // inherit all doc
    /**
     * @api
     */
    public function getOnParentVersion()
    {
        return $this->onParentVersion;
    }

    // inherit all doc
    /**
     * @api
     */
    public function isProtected()
    {
        return $this->isProtected;
    }
}
