<?php

namespace Jackalope;

use Exception;
use LogicException;
use ArrayIterator;
use IteratorAggregate;
use InvalidArgumentException;

use PHPCR\NodeType\ConstraintViolationException;
use PHPCR\PropertyInterface;
use PHPCR\PropertyType;
use PHPCR\RepositoryException;
use PHPCR\ValueFormatException;
use PHPCR\InvalidItemStateException;
use PHPCR\ItemNotFoundException;

use PHPCR\NodeType\PropertyDefinitionInterface;

use PHPCR\Util\PathHelper;
use PHPCR\Util\UUIDHelper;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
class Property extends Item implements IteratorAggregate, PropertyInterface
{
    /**
     * flag to know if binary streams should be wrapped or retrieved
     * immediately. this is a per session setting.
     *
     * @var boolean
     * @see Property::__construct()
     */
    protected $wrapBinaryStreams;

    /**
     * All binary stream wrapper instances
     * @var array
     */
    protected $streams = array();

    /**
     * The property value in suitable native format or object
     * @var mixed
     */
    protected $value;

    /**
     * length is only used for binary property, because binary loading is
     * delayed until explicitly requested.
     * @var int
     */
    protected $length;

    /**
     * whether this is a multivalue property
     * @var boolean
     */
    protected $isMultiple = false;
    /**
     * the type constant from PropertyType
     * @var int
     */
    protected $type;

    /**
     * cached instance of the property definition that defines this property
     * @var PropertyDefinitionInterface
     * @see Property::getDefinition()
     */
    protected $definition;

    /**
     * Create a property, either from server data or locally
     *
     * To indicate a property has newly been created locally, make sure to pass
     * true for the $new parameter. In that case, you should pass an empty array
     * for $data and use setValue afterwards to let the type magic be handled.
     * Then multivalue is determined on setValue
     *
     * For binary properties, the value is the length of the data(s), not the
     * data itself.
     *
     * @param FactoryInterface $factory the object factory
     * @param array            $data    array with fields <tt>type</tt>
     *      (integer or string from PropertyType) and <tt>value</tt> (data for
     *      creating the property value - array for multivalue property)
     * @param string        $path          the absolute path of this item
     * @param Session       $session       the session instance
     * @param ObjectManager $objectManager the objectManager instance - the
     *      caller has to take care of registering this item with the object
     *      manager
     * @param boolean $new optional: set to true to make this property aware
     *      its not yet existing on the server. defaults to false
     */
    public function __construct(FactoryInterface $factory, array $data, $path, Session $session, ObjectManager $objectManager, $new = false)
    {
        parent::__construct($factory, $path, $session, $objectManager, $new);

        $this->wrapBinaryStreams = $session->getRepository()->getDescriptor(Repository::JACKALOPE_OPTION_STREAM_WRAPPER);

        if (empty($data) && $new) {
            return;
        }

        if (! isset($data['value'])) {
            throw new InvalidArgumentException("Can't create property at $path without any data");
        }

        if (isset($data['type']) && PropertyType::UNDEFINED !== $data['type']) {
            $type = $data['type'];
            if (is_string($type)) {
                $type = PropertyType::valueFromName($type);
            } elseif (!is_numeric($type)) {
                // @codeCoverageIgnoreStart
                throw new RepositoryException("INTERNAL ERROR -- No valid type specified ($type)");
                // @codeCoverageIgnoreEnd
            } else {
                //sanity check. this will throw InvalidArgumentException if $type is not a valid type
                PropertyType::nameFromValue($type);
            }
        } else {
            // we are creating a node
            $type = PropertyType::determineType(is_array($data['value']) ? reset($data['value']) : $data['value']);
        }
        $this->type = $type;

        if ($type == PropertyType::BINARY && ! $new) {
            // reading a binary property from backend, we do not get the stream immediately but just the size
            if (is_array($data['value'])) {
                $this->isMultiple = true;
            }
            $this->length = $data['value'];
            $this->value = null;

            return;
        }

        if (is_array($data['value'])) {
            $this->isMultiple = true;
            $this->value = array();
            foreach ($data['value'] as $value) {
                $this->value[] = $this->valueConverter->convertType($value, $type);
            }
        } elseif (null !== $data['value']) {
            $this->value = $this->valueConverter->convertType($data['value'], $type);
        } else {
            // @codeCoverageIgnoreStart
            throw new RepositoryException('INTERNAL ERROR -- data[value] may not be null');
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setValue($value, $type = PropertyType::UNDEFINED)
    {
        $this->checkState();

        // need to determine type to avoid unnecessary modification
        // don't try to determine if the value changed anyway (i.e. null to delete)
        if (PropertyType::UNDEFINED === $type && $this->value === $value) {
            $type = $this->valueConverter->determineType(is_array($value) ? reset($value) : $value);
        }

        // Need to check both value and type, as native php type string is used for a number of phpcr types
        if ($this->value !== $value || $this->type !== $type) {
            if ($this->getDefinition()->isProtected()) {
                $violation = true;
                if ('jcr:mixinTypes' === $this->getDefinition()->getName()) {
                    // check that the jcr:mixinTypes are in sync with the mixin node types
                    $mixins = array();
                    foreach ($this->getParent()->getMixinNodeTypes() as $mixin) {
                        $mixins[] = $mixin->getName();
                    }
                    $violation = (bool) array_diff($mixins, $this->value);
                }
                if ($violation) {
                    $msg = sprintf("Property '%s' of node type '%s' is protected and cannot be modified", $this->name, $this->getDefinition()->getDeclaringNodeType()->getName());
                    throw new ConstraintViolationException($msg);
                }
            }

            $this->setModified();
        }

        // The _setValue call MUST BE after the check to see if the value or type changed
        $this->_setValue($value, $type);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function addValue($value)
    {
        $this->checkState();

        if (!$this->isMultiple()) {
            throw new ValueFormatException('You can not add values to non-multiple properties');
        }
        $this->value[] = $this->valueConverter->convertType($value, $this->type);
        $this->setModified();
    }

    /**
     * Tell this item that it has been modified.
     *
     * Used to make the parent node aware that this property has changed
     *
     * @private
     */
    public function setModified()
    {
        parent::setModified();
        $parent = $this->getParent();
        if (!is_null($parent)) {
            $parent->setModified();
        }
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getValue()
    {
        $this->checkState();

        if ($this->type == PropertyType::REFERENCE
            || $this->type == PropertyType::WEAKREFERENCE
        ) {
            return $this->getNode();
        }

        if ($this->type == PropertyType::BINARY) {
            return $this->getBinary();
        }

        return $this->value;
    }

    /**
     * Get the value of this property to store in the storage backend.
     *
     * Path and reference properties are not resolved to the node objects.
     * If this is a binary property, from the moment this method has been
     * called the stream will be read from the transport layer again.
     *
     * @private
     */
    public function getValueForStorage()
    {
        $this->checkState();

        $value = $this->value;
        if (PropertyType::BINARY == $this->type) {
            //from now on,
            $this->value = null;
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getString()
    {
        $this->checkState();

        if ($this->type == PropertyType::BINARY && empty($this->value)) {
            return $this->valueConverter->convertType($this->getBinary(), PropertyType::STRING, $this->type);
        }
        if ($this->type != PropertyType::STRING) {
            return $this->valueConverter->convertType($this->value, PropertyType::STRING, $this->type);
        }

        return $this->value;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getBinary()
    {
        $this->checkState();

        if ($this->type != PropertyType::BINARY) {
            return $this->valueConverter->convertType($this->value, PropertyType::BINARY, $this->type);
        }
        if (! $this->wrapBinaryStreams && null == $this->value) {
            // no caching the stream. we need to fetch the stream and then copy
            // it into a memory stream so it can be accessed more than once.
            $this->value = $this->objectManager->getBinaryStream($this->path);
        }
        if ($this->value != null) {
            // we have the stream locally: no wrapping or new or updated property
            // copy the stream so the original stream stays usable for storing, fetching again...
            $val = is_array($this->value) ? $this->value : array($this->value);
            $ret = array();
            foreach ($val as $s) {
                $stream = fopen('php://memory', 'rwb+');
                $pos = ftell($s);
                stream_copy_to_stream($s, $stream);
                rewind($stream);
                fseek($s, $pos); //go back to previous position
                $ret[] = $stream;
            }

            return is_array($this->value) ? $ret : $ret[0];
        }

        if (! $this->wrapBinaryStreams) {
            throw new LogicException("Attempting to create 'jackalope' stream instances but stream wrapper is not activated");
        }

        // return wrapped stream
        if ($this->isMultiple()) {
            $results = array();
            // identifies all streams loaded by one backend call
            $token = md5(uniqid(mt_rand(), true));
            // start with part = 1 since 0 will not be parsed properly by parse_url
            for ($i = 1; $i <= count($this->length); $i++) {
                $this->streams[] = $results[] = fopen('jackalope://' . $token. '@' . $this->session->getRegistryKey() . ':' . $i . $this->path , 'rwb+');
            }

            return $results;
        }

        // single property case
        $result = fopen('jackalope://' . $this->session->getRegistryKey() . $this->path , 'rwb+');
        $this->streams[] = $result;

        return $result;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getLong()
    {
        $this->checkState();

        if ($this->type != PropertyType::LONG) {
            return $this->valueConverter->convertType($this->value, PropertyType::LONG, $this->type);
        }

        return $this->value;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getDouble()
    {
        $this->checkState();

        if ($this->type != PropertyType::DOUBLE) {
            return $this->valueConverter->convertType($this->value, PropertyType::DOUBLE, $this->type);
        }

        return $this->value;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getDecimal()
    {
        $this->checkState();

        if ($this->type != PropertyType::DECIMAL) {
            return $this->valueConverter->convertType($this->value, PropertyType::DECIMAL, $this->type);
        }

        return $this->value;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getDate()
    {
        $this->checkState();

        if ($this->type != PropertyType::DATE) {
            return $this->valueConverter->convertType($this->value, PropertyType::DATE, $this->type);
        }

        return $this->value;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getBoolean()
    {
        $this->checkState();

        if ($this->type != PropertyType::BOOLEAN) {
            return $this->valueConverter->convertType($this->value, PropertyType::BOOLEAN, $this->type);
        }

        return $this->value;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getNode()
    {
        $this->checkState();

        $values = $this->isMultiple() ? $this->value : array($this->value);

        $results = array();
        switch ($this->type) {
            case PropertyType::REFERENCE:
                $results = $this->objectManager->getNodesByIdentifier($values);
                $results = $results->getArrayCopy();
                if (array_keys($results) != $values) {
                    // @codeCoverageIgnoreStart
                    throw new RepositoryException('Internal Error: Could not find a referenced node. If the referencing node is a frozen version, this can happen, otherwise it would be a bug.');
                    // @codeCoverageIgnoreEnd
                }
                break;
            case PropertyType::WEAKREFERENCE:
                $results = $this->objectManager->getNodesByIdentifier($values);
                $results = $results->getArrayCopy();
                if (array_keys($results) != $values) {
                    throw new ItemNotFoundException('One or more weak reference targets have not been found: ' . implode(',', array_diff(array_keys($results), $values)));
                }
                break;
            case PropertyType::STRING:
                foreach ($values as $value) {
                    if (UUIDHelper::isUUID($value)) {
                        $results[] = $this->objectManager->getNodeByIdentifier($value);
                    } else {
                        $results[] = $this->objectManager->getNode($value, $this->parentPath);
                    }
                }
                break;
            case PropertyType::PATH:    
            case PropertyType::NAME:
                foreach ($values as $value) {
                    // OPTIMIZE: use objectManager->getNodes instead of looping (but paths need to be absolute then)
                    $results[] = $this->objectManager->getNode($value, $this->parentPath);
                }
                break;
            default:
                throw new ValueFormatException('Property is not a REFERENCE, WEAKREFERENCE or PATH (or convertible to PATH)');
        }

        return $this->isMultiple() ? $results : reset($results);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getProperty()
    {
        $this->checkState();

        $values = $this->isMultiple() ? $this->value : array($this->value);

        $results = array();
        switch ($this->type) {
            case PropertyType::PATH:
            case PropertyType::STRING:
            case PropertyType::NAME:
                foreach ($values as $value) {
                    $results[] = $this->objectManager->getPropertyByPath(PathHelper::absolutizePath($value, $this->parentPath));
                }
                break;
            default:
                throw new ValueFormatException('Property is not a PATH (or convertible to PATH)');
        }

        return $this->isMultiple() ? $results : $results[0];

    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getLength()
    {
        $this->checkState();

        if (PropertyType::BINARY === $this->type) {
            return $this->length;
        }

        $vals = $this->isMultiple ? $this->value : array($this->value);
        $ret = array();

        foreach ($vals as $value) {
            try {
                $ret[] = strlen($this->valueConverter->convertType($value, PropertyType::STRING, $this->type));
            } catch (Exception $e) {
                // @codeCoverageIgnoreStart
                $ret[] = -1;
                // @codeCoverageIgnoreEnd
            }
        }

        return $this->isMultiple ? $ret : $ret[0];
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getDefinition()
    {
        $this->checkState();

        if (empty($this->definition)) {
            $types = $this->getParent()->getMixinNodeTypes();
            array_push($types, $this->getParent()->getPrimaryNodeType());
            /** @var $nt \Jackalope\NodeType\NodeType */
            foreach ($types as $nt) {
                /** @var $candidate PropertyDefinitionInterface */
                foreach ($nt->getPropertyDefinitions() as $candidate) {
                    if ($candidate->getName() == $this->name) {
                        $this->definition = $candidate;
                        break 2;
                    } elseif ('*' == $candidate->getName()) {
                        // i guess if we have multiple wildcard property
                        // definitions, they should be equivalent
                        $this->definition = $candidate;
                        // do not abort loop, in case we hit an exactly
                        // matching definition
                    }
                }
            }
        }
        // sanity check. theoretically, the property should not be able to
        // exist if there is no definition for it
        if (empty($this->definition)) {
            throw new \RuntimeException('Found no property definition, this should not be possible');
        }

        return $this->definition;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getType()
    {
        $this->checkState();

        return $this->type;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isMultiple()
    {
        $this->checkState();

        return $this->isMultiple;
    }

    /**
     * Also unsets internal reference in containing node
     *
     * {@inheritDoc}
     *
     * @uses Node::unsetProperty()
     *
     * @api
     */
    public function remove()
    {
        $this->checkState();

        $parentNodeType = $this->getParent()->getPrimaryNodeType();
        //will throw a ConstraintViolationException if this property can't be removed
        $parentNodeType->canRemoveProperty($this->getName(), true);

        $this->getParent()->unsetProperty($this->name);

        parent::remove();
    }

    /**
     * Provide Traversable interface: redirect to getNodes with no filter
     *
     * @return \Iterator over all child nodes
     */
    public function getIterator()
    {
        $this->checkState();

        $value = $this->getValue();
        if (!is_array($value)) {
            $value = array($value);
        }

        return new ArrayIterator($value);
    }

    /**
     * Refresh this property
     *
     * {@inheritDoc}
     *
     * In Jackalope, this is also called internally to refresh when the node
     * is accessed in state DIRTY.
     *
     * Triggers a reload of the containing node, as a property can only ever be
     * loaded attached to a node.
     *
     * TODO: refactor this if we implement loading single properties
     *
     * @see Item::checkState
     */
    protected function refresh($keepChanges, $internal = false)
    {
        if ($this->isDeleted()) {
            if ($internal) {
                // @codeCoverageIgnoreStart
                // FIXME: this should not be possible
                return;
                // @codeCoverageIgnoreEnd
            }
            throw new InvalidItemStateException('This property is deleted');
        }
        // Let the node refresh us
        try {
            // do not use getParent to avoid checkState - could lead to an endless loop
            $this->objectManager->getNodeByPath($this->parentPath)->refresh($keepChanges);
        } catch (ItemNotFoundException $e) {
            $this->setDeleted();
        }
    }

    /**
     * Internally used to set the value of the property without any notification
     * of changes nor state change.
     *
     * @param mixed  $value
     * @param string $type
     *
     * @see Property::setValue()
     *
     * @private
     */
    public function _setValue($value, $type = PropertyType::UNDEFINED)
    {
        if (is_null($value)) {
            $this->remove();

            return;
        }
        if (! is_integer($type)) {
            // @codeCoverageIgnoreStart
            throw new InvalidArgumentException("The type has to be one of the numeric constants defined in PHPCR\\PropertyType. $type");
            // @codeCoverageIgnoreEnd
        }
        if ($this->isNew()) {
            $this->isMultiple = is_array($value);
        }

        if (is_array($value) && !$this->isMultiple) {
            throw new ValueFormatException('Can not set a single value property ('.$this->name.') with an array of values');
        }

        //TODO: check if changing type allowed.
        /*
         * if ($type !== null && ! canHaveType($type)) {
         *   throw new ConstraintViolationException("Can not set this property to type ".PropertyType::nameFromValue($type));
         * }
         */

        if (PropertyType::UNDEFINED === $type) {
            $type = $this->valueConverter->determineType(is_array($value) ? reset($value) : $value);
        }

        $targettype = $this->type;
        if ($this->type !== $type) {
            /* TODO: find out with node type definition if the new type is allowed
              if (canHaveType($type)) {
            */
            $targettype = $type;
            /*
              } else {
                  //convert to an allowed type. if the current type is defined $targettype = $this->type;
              }
            */
        }

        $value = $this->valueConverter->convertType($value, $targettype, $type);

        if (PropertyType::BINARY === $targettype) {
            if (is_array($value)) {
                $this->length = array();
                foreach($value as $v) {
                    $stat = is_resource($v) ? fstat($v) : array( 'size' => -1 );
                    $this->length[] = $stat['size'];
                }
            } elseif(is_resource($value)) {
                $stat = fstat($value);
                $this->length = $stat['size'];
            } else {
                $this->length = -1;
            }
        }

        $this->type = $targettype;
        $this->value = $value;
    }

    /**
     * Internally used after refresh from backend to set new length
     *
     * @param int $length the new length of this binary
     *
     * @private
     */
    public function _setLength($length)
    {
        $this->length = $length;
        $this->value = null;
    }

    /**
     * Close all open binary stream wrapper instances on shutdown.
     */
    public function __destruct()
    {
        foreach ($this->streams as $k => $v) {
            // if this is not a resource, it means the stream has already been
            // closed by client code
            if (is_resource($v)) {
                fclose($v);
                unset($this->streams[$k]);
            }
        }
    }
}
