<?php

namespace Jackalope;

/**
 * Jackalope implementation factory.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 */
class Factory implements FactoryInterface
{
    private array $classCache = [];

    private array $reflectionCache = [];

    /**
     * {@inheritDoc}
     *
     * @throws \InvalidArgumentException
     */
    public function get(string $name, array $params = [])
    {
        if (isset($this->classCache[$name])) {
            $name = $this->classCache[$name];
        } else {
            $originalName = $name;

            if (class_exists('Jackalope\\'.$name)) {
                $name = 'Jackalope\\'.$name;
            } elseif (!class_exists($name)) {
                throw new \InvalidArgumentException("Neither class Jackalope\\$name nor class $name found. Please check your autoloader and the spelling of $name");
            }

            $this->classCache[$originalName] = $name;
        }

        if (0 === strpos($name, 'Jackalope\\')) {
            array_unshift($params, $this);
        }

        if (!count($params)) {
            return new $name();
        }

        if (isset($this->reflectionCache[$name])) {
            $class = $this->reflectionCache[$name];
        } else {
            $class = new \ReflectionClass($name);
            $this->reflectionCache[$name] = $class;
        }

        return $class->newInstanceArgs($params);
    }
}
