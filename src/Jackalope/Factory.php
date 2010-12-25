<?php
namespace Jackalope;

/**
 * Jackalope implementation factory - injected into every class
 *
 * This factory is used to centralize the jackalope instantiations and make
 * them easily replaceable with dummies for the unit and functional testing.
 * It is injected in the constructor of every class of the framework.
 *
 * It should be used in the code like that:
 * $this->factory->get('Node', array(...));
 * $this->factory->get('NodeType\PropertyDefinition', array(...));
 * //note the \ for sub namespaces. the name is relative to the jackalope namespace
 *
 * The result will be an object from jackalope with the given named params.
 *
 * Note that the factory passes itself to every object it creates, to give them
 * a reference to itselves.
 */
class Factory
{
    /**
     * Factory
     *
     * @param $name string: Model name.
     * @param $params array: Parameters in order of their appearance in the constructor.
     * @return object
     */
    public function get($name, $params = array())
    {
        if (class_exists('Jackalope\\' . $name)) {
            $name = 'Jackalope\\' . $name;
        }
        if (count($params) == 0) {
            return new $name;
        } else {
            $class = new \ReflectionClass($name);
            array_unshift($params, $this);
            return $class->newInstanceArgs($params);
        }
    }
}
