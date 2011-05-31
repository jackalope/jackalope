<?php
namespace Jackalope\NodeType;

use Jackalope\Helper;
use \DOMElement, \DOMXPath;

class NodeDefinition extends ItemDefinition implements \PHPCR\NodeType\NodeDefinitionInterface
{
    const DEFAULT_PRIMARY_NODE = 'nt:base';

    protected $requiredPrimaryTypes = array();
    protected $requiredPrimaryTypeNames = array();
    protected $defaultPrimaryTypeName;
    protected $allowsSameNameSiblings;

    public function __construct($factory, $node, NodeTypeManager $nodeTypeManager)
    {
        parent::__construct($factory, $node, $nodeTypeManager);

        if ($node instanceof DOMElement) {
            $this->fromXML($node);
        } elseif (is_array($node)) {
            $this->fromArray($node);
        }
    }

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

    protected function fromArray(array $data)
    {
        parent::fromArray($data);
        $this->allowsSameNameSiblings = $data['allowsSameNameSiblings'];
        $this->defaultPrimaryTypeName = isset($data['defaultPrimaryTypeName']) ? $data['defaultPrimaryTypeName'] : null;
        $this->requiredPrimaryTypeNames = (isset($data['requiredPrimaryTypeNames']) && count($data['requiredPrimaryTypeNames']))
                ? $data['requiredPrimaryTypeNames'] : array(self::DEFAULT_PRIMARY_NODE);
    }

    /**
     * Gets the minimum set of primary node types that the child node must have.
     * Returns an array to support those implementations with multiple inheritance.
     * This method never returns an empty array. If this node definition places
     * no requirements on the primary node type, then this method will return an
     * array containing only the NodeType object representing nt:base, which is
     * the base of all primary node types and therefore constitutes the least
     * restrictive node type requirement. Note that any particular node instance
     * still has only one assigned primary node type, but in multiple-inheritance-
     * supporting implementations the RequiredPrimaryTypes attribute can be used
     * to restrict that assigned node type to be a subtype of all of a specified
     * set of node types.
     * In implementations that support node type registration an NodeDefinition
     * object may be acquired (in the form of a NodeDefinitionTemplate) that is
     * not attached to a live NodeType. In such cases this method returns null.
     *
     * @return \PHPCR\NodeType\NodeTypeInterface an array of NodeType objects.
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

    /**
     * Returns the names of the required primary node types.
     * If this NodeDefinition is acquired from a live NodeType this list will
     * reflect the node types returned by getRequiredPrimaryTypes, above.
     *
     * If this NodeDefinition is actually a NodeDefinitionTemplate that is not
     * part of a registered node type, then this method will return the required
     * primary types as set in that template. If that template is a newly-created
     * empty one, then this method will return null.
     *
     * @return array a String array
     */
    public function getRequiredPrimaryTypeNames()
    {
        return $this->requiredPrimaryTypeNames;
    }

    /**
     * Gets the default primary node type that will be assigned to the child node
     * if it is created without an explicitly specified primary node type. This
     * node type must be a subtype of (or the same type as) the node types returned
     * by getRequiredPrimaryTypes.
     * If null is returned this indicates that no default primary type is
     * specified and that therefore an attempt to create this node without
     * specifying a node type will throw a ConstraintViolationException. In
     * implementations that support node type registration an NodeDefinition
     * object may be acquired (in the form of a NodeDefinitionTemplate) that is
     * not attached to a live NodeType. In such cases this method returns null.
     *
     * @return \PHPCR\NodeType\NodeTypeInterface a NodeType.
     */
    public function getDefaultPrimaryType()
    {
        if (null === $this->defaultPrimaryTypeName) {
            return null;
        }
        return $this->nodeTypeManager->getNodeType($this->defaultPrimaryTypeName);
    }

    /**
     * Returns the name of the default primary node type.
     * If this NodeDefinition is acquired from a live NodeType this list will
     * reflect the NodeType returned by getDefaultPrimaryType, above.
     *
     * If this NodeDefinition is actually a NodeDefinitionTemplate that is not
     * part of a registered node type, then this method will return the required
     * primary types as set in that template. If that template is a newly-created
     * empty one, then this method will return null.
     *
     * @return string a String
     */
    public function getDefaultPrimaryTypeName()
    {
        return $this->defaultPrimaryTypeName;
    }

    /**
     * Reports whether this child node can have same-name siblings. In other
     * words, whether the parent node can have more than one child node of this
     * name. If this NodeDefinition is actually a NodeDefinitionTemplate that is
     * not part of a registered node type, then this method will return the same
     * name siblings status as set in that template. If that template is a
     * newly-created empty one, then this method will return false.
     *
     * @return boolean a boolean.
     */
    public function allowsSameNameSiblings()
    {
        return $this->allowsSameNameSiblings;
    }
}
