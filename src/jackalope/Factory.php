<?php

namespace jackalope;

/**
 * This factory is used to centralize the jackalope instantiations and make
 * them easily replaceable with dummies for the unit and functional testing.
 *
 * It should be used in the commands like that:
 * Factory::get('Node', array(...));
 * The result will be an object from jackalope with the given named params.
 */
class Factory {
    /**
     * Factory
     *
     * @param $name string: Model name.
     * @param $params array: Parameters in order of their appearance in the constructor.
     * @return jackalope
     */
    public static function get($name, $params = array()) {
        if (class_exists('jackalope\\' . $name)) {
            $name = 'jackalope\\' . $name;
        }
        if (count($params) == 0) {
            return new $name;
        } else {
            $class = new \ReflectionClass($name);
            return $class->newInstanceArgs($params);
        }
    }
}
