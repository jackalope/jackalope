<?php

namespace Jackalope\Transport;

use Jackalope\Property;

/**
 * Representing a property remove operation.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 */
final class RemovePropertyOperation extends Operation
{
    /**
     * The property to remove.
     */
    public Property $property;

    public function __construct(string $srcPath, Property $property)
    {
        parent::__construct($srcPath, self::REMOVE_PROPERTY);
        $this->property = $property;
    }
}
