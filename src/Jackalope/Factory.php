<?php

namespace Jackalope;

use InvalidArgumentException;
use ReflectionClass;

/**
 * Jackalope implementation factory.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 */
class Factory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function get($name, array $params = array())
    {
        if (class_exists('Jackalope\\' . $name)) {
            $name = 'Jackalope\\' . $name;
        } elseif (! class_exists($name)) {
            throw new InvalidArgumentException("Neither class Jackalope\\$name nor class $name found. Please check your autoloader and the spelling of $name");
        }

        if (0 === strpos($name, 'Jackalope\\')) {
            array_unshift($params, $this);
        }

        if (! count($params)) {
            return new $name;
        }

        $class = new ReflectionClass($name);

        return $class->newInstanceArgs($params);
    }
}
