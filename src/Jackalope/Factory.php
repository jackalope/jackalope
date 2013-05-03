<?php

namespace Jackalope;

use InvalidArgumentException;
use ReflectionClass;

/**
 * Jackalope implementation factory
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

        $class = new ReflectionClass($name);
        if (0 === strpos($name, 'Jackalope\\')) {
            array_unshift($params, $this);
        }

        return $class->newInstanceArgs($params);
    }
}
