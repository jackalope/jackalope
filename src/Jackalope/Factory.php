<?php
namespace Jackalope;

/**
 * Jackalope implementation factory - injected into every class.
 *
 * This factory is used to centralize the jackalope instantiations and make
 * them easily replaceable with dummies for the unit and functional testing.
 * It is injected in the constructor of every class of the framework.
 *
 * The Jackalope namespace is automatically prepended in order to allow for
 * other factories to instantiate objects in a different namspace.
 * If no class with the requested name exists in the Jackalope namespace, the
 * factory tries to use a class in global namespace.
 *
 * It should be used in the code like this:
 * <pre>
 * $this->factory->get('Node', array(...));
 * $this->factory->get('NodeType\PropertyDefinition', array(...));
 * //note the \ for sub namespaces. the name is relative to the jackalope namespace
 * </pre>
 *
 * The result will be an object for jackalope with the factory and given paramaters.
 *
 * Note that the factory passes itself to every object it creates as the first
 * argument, to give them a reference to itselves.
 */
class Factory
{
    /**
     * Get the object with the class name created with the factory plus any
     * passed parameters.
     *
     * @param $name string class name with sub-namespace inside the Jackalope
     *      namespace
     * @param $params array Parameters in order of their appearance in the
     *      constructor. The factory will prepend itself to this list.
     *
     * @return object
     */
    public function get($name, array $params = array())
    {
        if (class_exists('Jackalope\\' . $name)) {
            $name = 'Jackalope\\' . $name;
        } elseif (! class_exists($name)) {
            throw new \InvalidArgumentException("Neither class Jackalope\\$name nor class $name found. Please check your autoloader and the spelling of $name");
        }

        $class = new \ReflectionClass($name);
        array_unshift($params, $this);
        return $class->newInstanceArgs($params);
    }
}
