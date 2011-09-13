<?php

namespace Jackalope;

use ArrayIterator;
use PHPCR\PropertyType;

/**
 * The Jackalope in-memory representation of a property.
 *
 * This is an Item and follows the property interface - see the base class and
 * interface for more documentation.
 */
class Property extends Item implements \IteratorAggregate, \PHPCR\PropertyInterface
{
    /**
     * flag to know if binary streams should be wrapped or retrieved
     * immediately.
     *
     * limit the call to stream_get_wrappers to one per session. can not be
     * static as this is a per session setting.
     *
     * @var boolean
     * @see Property::__construct()
     */
    protected $binaryStreamWrapperRegistered;

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
     * the type constant from \PHPCR\PropertyType
     * @var int
     */
    protected $type;

    /**
     * cached instance of the property definition that defines this property
     * @var \PHPCR\NodeType\PropertyDefinitionInterface
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
     * @param object $factory an object factory implementing "get" as
     *      described in \Jackalope\Factory
     * @param array $data array with fields <tt>type</tt> (integer or string
     *      from \PHPCR\PropertyType) and <tt>value</tt> (data for creating the
     *      property value - array for multivalue property)
     * @param string $path the absolute path of this item
     * @param Session the session instance
     * @param ObjectManager the objectmanager instance - the caller has to take
     *      care of registering this item with the object manager
     * @param boolean $new optional: set to true to make this property aware
     *      its not yet existing on the server. defaults to false
     */
    public function __construct($factory, array $data, $path, Session $session,
                                ObjectManager $objectManager, $new = false)
    {
        parent::__construct($factory, $path, $session, $objectManager, $new);

        if (null === $this->binaryStreamWrapperRegistered) {
            $this->binaryStreamWrapperRegistered = in_array('jackalope', stream_get_wrappers());
        }

        if (empty($data) && $new) {
            return;
        } else if (! isset($data['value'])) {
            throw new \InvalidArgumentException("Can't create property at $path without any data");
        }

        if (isset($data['type']) && \PHPCR\PropertyType::UNDEFINED !== $data['type']) {
            $type = $data['type'];
            if (is_string($type)) {
                $type = PropertyType::valueFromName($type);
            } elseif (!is_numeric($type)) {
                throw new \PHPCR\RepositoryException("INTERNAL ERROR -- No valid type specified ($type)");
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
                $this->value[] = PropertyType::convertType($value, $type);
            }
        } elseif (null !== $data['value']) {
            $this->value = PropertyType::convertType($data['value'], $type);
        } else {
            throw new \PHPCR\RepositoryException('INTERNAL ERROR -- data[value] may not be null');
        }
    }

    // inherit all doc
    /**
     * @api
     */
    public function setValue($value, $type = PropertyType::UNDEFINED)
    {
        $this->checkState();

        // Need to check both value and type, as native php type string is used for a number of phpcr types
        if ($this->value !== $value || $this->type !== $type) {
            $this->setModified();
        }

        // The _setValue call MUST BE after the check to see if the value or type changed
        $this->_setValue($value, $type);
    }

    // inherit all doc
    /**
     * @api
     */
    public function addValue($value)
    {
        $this->checkState();

        if (!$this->isMultiple()) {
            throw new \PHPCR\ValueFormatException('You can not add values to non-multiple properties');
        }
        $this->value[] = PropertyType::convertType($value, $this->type);
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

    // inherit all doc
    /**
     * @api
     */
    public function getValue()
    {
        $this->checkState();

        if ($this->type == PropertyType::REFERENCE
            || $this->type == PropertyType::WEAKREFERENCE
        ) {
            return $this->getNode();
        } elseif ($this->type == PropertyType::BINARY) {
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

    // inherit all doc
    /**
     * @api
     */
    public function getString()
    {
        $this->checkState();

        if ($this->type == PropertyType::BINARY && empty($this->value)) {
            return PropertyType::convertType($this->getBinary(), PropertyType::STRING, $this->type);
        }
        if ($this->type != PropertyType::STRING) {
            return PropertyType::convertType($this->value, PropertyType::STRING, $this->type);
        }
        return $this->value;
    }

    // inherit all doc
    /**
     * @api
     */
    public function getBinary()
    {
        $this->checkState();

        if ($this->type != PropertyType::BINARY) {
            return PropertyType::convertType($this->value, PropertyType::BINARY, $this->type);
        }
        if (! $this->binaryStreamWrapperRegistered && null == $this->value) {
            $this->value = $this->objectManager->getBinaryStream($this->path);
        }
        if ($this->value != null) {
            // new or updated property
            $val = is_array($this->value) ? $this->value : array($this->value);
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
        if (! $this->binaryStreamWrapperRegistered) {
            throw new \LogicException("Attempting to create 'jackalope' stream instances but stream wrapper is not registered");
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

    // inherit all doc
    /**
     * @api
     */
    public function getLong()
    {
        $this->checkState();

        if ($this->type != PropertyType::LONG) {
            return PropertyType::convertType($this->value, PropertyType::LONG, $this->type);
        }
        return $this->value;
    }

    // inherit all doc
    /**
     * @api
     */
    public function getDouble()
    {
        $this->checkState();

        if ($this->type != PropertyType::DOUBLE) {
            return PropertyType::convertType($this->value, PropertyType::DOUBLE, $this->type);
        }
        return $this->value;
    }

    // inherit all doc
    /**
     * @api
     */
    public function getDecimal()
    {
        $this->checkState();

        if ($this->type != PropertyType::DECIMAL) {
            return PropertyType::convertType($this->value, PropertyType::DECIMAL, $this->type);
        }
        return $this->value;
    }

    // inherit all doc
    /**
     * @api
     */
    public function getDate()
    {
        $this->checkState();

        if ($this->type != PropertyType::DATE) {
            return PropertyType::convertType($this->value, PropertyType::DATE, $this->type);
        }
        return $this->value;
    }

    // inherit all doc
    /**
     * @api
     */
    public function getBoolean()
    {
        $this->checkState();

        if ($this->type != PropertyType::BOOLEAN) {
            return PropertyType::convertType($this->value, PropertyType::BOOLEAN, $this->type);
        }
        return $this->value;
    }

    // inherit all doc
    /**
     * @api
     */
    public function getNode()
    {
        $this->checkState();

        $values = $this->isMultiple() ? $this->value : array($this->value);

        $results = array();
        switch($this->type) {
            case PropertyType::REFERENCE:
                try {
                    foreach ($values as $value) {
                        // OPTIMIZE: use objectManager->getNodes instead of looping
                        $results[] = $this->objectManager->getNode($value);
                    }
                } catch(\PHPCR\ItemNotFoundException $e) {
                    throw new \PHPCR\RepositoryException('Internal Error: Could not find a referenced node. This should be impossible.');
                }
                break;
            case PropertyType::WEAKREFERENCE:
                foreach ($values as $value) {
                    // OPTIMIZE: use objectManager->getNodes instead of looping
                    $results[] = $this->objectManager->getNode($value);
                }
                break;
            case PropertyType::PATH:
            case PropertyType::STRING:
            case PropertyType::NAME:
                foreach ($values as $value) {
                    // OPTIMIZE: use objectManager->getNodes instead of looping (but paths need to be absolute then)
                    $results[] = $this->objectManager->getNode($value, $this->parentPath);
                }
                break;
            default:
                throw new \PHPCR\ValueFormatException('Property is not a REFERENCE, WEAKREFERENCE or PATH (or convertible to PATH)');
        }

        return $this->isMultiple() ? $results : $results[0];
    }

    // inherit all doc
    /**
     * @api
     */
    public function getProperty()
    {
        $this->checkState();

        $values = $this->isMultiple() ? $this->value : array($this->value);

        $results = array();
        switch($this->type) {
            case PropertyType::PATH:
            case PropertyType::STRING:
            case PropertyType::NAME:
                foreach ($values as $value) {
                    $results[] = $this->objectManager->getPropertyByPath($this->objectManager->absolutePath($this->parentPath, $value));
                }
                break;
            default:
                throw new \PHPCR\ValueFormatException('Property is not a PATH (or convertible to PATH)');
        }

        return $this->isMultiple() ? $results : $results[0];

    }

    // inherit all doc
    /**
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
                $ret[] = strlen(PropertyType::convertType($value, PropertyType::STRING, $this->type));
            } catch (\Exception $e) {
                $ret[] = -1;
            }
        }

        return $this->isMultiple ? $ret : $ret[0];
    }

    // inherit all doc
    /**
     * @api
     */
    public function getDefinition()
    {
        $this->checkState();

        if (empty($this->definition)) {
            //FIXME: acquire definition
        }
        return $this->definition;
    }

    // inherit all doc
    /**
     * @api
     */
    public function getType()
    {
        $this->checkState();

        return $this->type;
    }

    // inherit all doc
    /**
     * @api
     */
    public function isMultiple()
    {
        $this->checkState();

        return $this->isMultiple;
    }

    /**
     * Throws an exception if the property is multivalued
     * @throws \PHPCR\ValueFormatException
     */
    protected function checkMultiple($isMultiple = true)
    {
        $this->checkState();

        if ($isMultiple === $this->isMultiple) {
            throw new \PHPCR\ValueFormatException();
        }
    }

    /**
     * Also unsets internal reference in containing node
     *
     * {@inheritDoc}
     *
     * @return void
     *
     * @uses Node::unsetProperty()
     *
     * @api
     */
    public function remove()
    {
        $this->checkState();

        $this->getParent()->unsetProperty($this->name);

        parent::remove();
    }

    /**
     * Provide Traversable interface: redirect to getNodes with no filter
     *
     * @return Iterator over all child nodes
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
     *
     * @api
     */
    public function refresh($keepChanges, $internal = false)
    {
        if ($this->isDeleted()) {
            if ($internal) {
                // FIXME: this should not be possible
                return;
            }
            throw new \PHPCR\InvalidItemStateException('This item has been removed at the backend');
        }
        // Let the node refresh us
        try {
            // do not use getParent to avoid checkState - could lead to an endless loop
            $this->objectManager->getNodeByPath($this->parentPath)->refresh($keepChanges);
        } catch (\PHPCR\ItemNotFoundException $e) {
            $this->setDeleted();
        }
    }

    /**
     * Internally used to get the raw value of the property without any state
     * checks.
     *
     * @return mixed
     *
     * @see Property::getValue()
     *
     * @private
     */
    public function _getRawValue()
    {
        return $this->value;
    }

    /**
     * Internally used to set the value of the property without any notification
     * of changes nor state change.
     *
     * @param mixed $value
     * @param string $type
     *
     * @return void
     *
     * @see Property::setValue()
     *
     * @private
     */
    public function _setValue($value, $type = PropertyType::UNDEFINED)
    {
        if (is_null($value)) {
            $this->remove();
        }
        if (! is_integer($type)) {
            throw new \InvalidArgumentException("The type has to be one of the numeric constants defined in PHPCR\PropertyType. $type");
        }
        if ($this->isNew()) {
            $this->isMultiple = is_array($value);
        }

        if (is_array($value) && !$this->isMultiple) {
            throw new \PHPCR\ValueFormatException('Can not set a single value property ('.$this->name.') with an array of values');
        }

        //TODO: check if changing type allowed.
        /*
         * if ($type !== null && ! canHaveType($type)) {
         *   throw new ConstraintViolationException("Can not set this property to type ".PropertyType::nameFromValue($type));
         * }
         */

        if (PropertyType::UNDEFINED === $type) {
            $type = PropertyType::determineType(is_array($value) ? reset($value) : $value);
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

        $value = PropertyType::convertType($value, $targettype);

        if (PropertyType::BINARY === $targettype) {
            $stat = fstat($value); //TODO: read file into local context? fstat not available on all streams
            $this->length = $stat['size'];
        }

        $this->type = $targettype;
        $this->value = $value;
    }

    /**
     * Internally used after refresh from backend to set new length
     *
     * @param int $length the new length of this binary
     *
     * @return void
     *
     * @private
     */
    public function _setLength($length)
    {
        $this->length = $length;
        $this->value = null;
    }

    /**
     * Close all open binary stream wrapper instances on shutdown
     *
     * @return void
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
