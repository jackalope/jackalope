<?php
/**
 * This factory is used to centralize the jackalope instantiations and make
 * them easily replaceable with dummies for the unit and functional testing.
 *
 * It should be used in the commands like that:
 * jackalope_Factory::get('Node', array(...));
 * The result will be an object from jackalope with the given named params.
 */
class jackalope_Factory {
    /**
     * Factory
     *
     * @param $name string: Model name.
     * @param $params array: Parameters in order of their appearance in the constructor.
     * @return jackalope
     */
    public static function get($name, $params = array()) {
        if (class_exists('jackalope_' . $name)) {
            $name = 'jackalope_' . $name;
        }
        if (count($params) == 0) {
            return new $name;
        } else {
            $class = new ReflectionClass($name);
            return $class->newInstanceArgs($params);
        }
    }
}
