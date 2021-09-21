<?php

namespace Jackalope\Transport;

use Jackalope\Property;

/**
 * Representing a property remove operation.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 */
class RemovePropertyOperation extends Operation
{
    /**
     * The property to remove.
     *
     * @var Property
     */
    public $property;

    /**
     * @param string   $srcPath  absolute path of the property to remove
     * @param Property $property property object to be removed
     */
    public function __construct($srcPath, Property $property)
    {
        parent::__construct($srcPath, self::REMOVE_PROPERTY);
        $this->property = $property;
    }
}
