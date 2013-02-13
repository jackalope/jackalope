<?php

namespace Jackalope\Transport;
use PHPCR\PropertyInterface;

/**
 * Representing a property remove operation
 */
class RemovePropertyOperation extends Operation
{
    /**
     * The item to remove
     *
     * @var PropertyInterface
     */
    public $property;

    /**
     * Whether this remove operations was later determined to be skipped
     * (i.e. a parent node is removed as well.)
     *
     * @var bool
     */
    public $skip = false;

    public function __construct($srcPath, PropertyInterface $property)
    {
        parent::__construct($srcPath, self::REMOVE_PROPERTY);
        $this->property = $property;
    }
}