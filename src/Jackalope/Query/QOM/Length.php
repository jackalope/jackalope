<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\LengthInterface;
use PHPCR\Query\QOM\PropertyValueInterface;

/**
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
final class Length implements LengthInterface
{
    private PropertyValueInterface $propertyValue;

    public function __construct(PropertyValueInterface $propertyValue)
    {
        $this->propertyValue = $propertyValue;
    }

    /**
     * @api
     */
    public function getPropertyValue(): PropertyValueInterface
    {
        return $this->propertyValue;
    }
}
