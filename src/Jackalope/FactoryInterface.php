<?php

namespace Jackalope;

/**
 * Jackalope factory interface - injected into every class.
 *
 * This factory is used to centralize the Jackalope instantiations and make
 * them easily replaceable with dummies for the unit and functional testing.
 * It is injected in the constructor of every class of the framework.
 *
 * The Jackalope namespace is automatically prepended in order to allow for
 * other factories to instantiate objects in a different namespace.
 * If no class with the requested name exists in the Jackalope namespace, the
 * factory tries to use a class in global namespace.
 *
 * It should be used in the code like this:
 * <pre>
 * $this->factory->get('Node', array(...));
 * $this->factory->get('NodeType\PropertyDefinition', array(...));
 * //note the \ for sub namespaces. the name is relative to the Jackalope namespace
 * </pre>
 *
 * The result will be an object for Jackalope with the factory and given parameters.
 *
 * Note that the factory passes itself to every object it creates as the first
 * argument, to give them a reference to itself.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 */
interface FactoryInterface
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
    public function get($name, array $params = array());
}
