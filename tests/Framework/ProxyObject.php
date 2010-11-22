<?php
/**
 * Class to create a proxy object used in unittests.
 *
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache License Version 2.0, January 2004
 *   Licensed under the Apache License, Version 2.0 (the "License");
 *   you may not use this file except in compliance with the License.
 *   You may obtain a copy of the License at
 *
 *       http://www.apache.org/licenses/LICENSE-2.0
 *
 *   Unless required by applicable law or agreed to in writing, software
 *   distributed under the License is distributed on an "AS IS" BASIS,
 *   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *   See the License for the specific language governing permissions and
 *   limitations under the License.
 *
 * @package Jackalope
 * @subpackage Unittests
 */
namespace Tests\Framework;

/**
 * The proxy class wrapps the original class to expose protected or private methods or attributes.
 *
 * @package Jackalope
 * @subpackage Unittests
 *
 */
class ProxyObject
{
    /**
     * Wraps the class identified by its classname to expose invisible methods and attributes.
     *
     * @param string $originalClassName Name of the class to be proxied.
     * @param array $methods List of methodnames to be exposed.
     * @param array $arguments List of arguments to be passed to the constructor of the wrapped class.
     * @param string $proxyClassName Name to be used for the reflected class.
     * @param boolean $callAutoload Switch to run the autoloader.
     *
     * @return object Instance of the proxied class exposing the configured attributes and methods.
     */
    public function getProxy($originalClassName, array $methods = null, array $arguments = array(),
                             $proxyClassName = '', $callAutoload = false) {
        include_once(dirname(__FILE__).'/ProxyObject/Generator.php');
        $proxyClass = ProxyObjectGenerator::generate(
            $originalClassName, $methods, $proxyClassName, $callAutoload
        );

        if (!empty($proxyClass['namespaceName'])) {
            $classname = '\\'.$proxyClass['namespaceName'].'\\'.$proxyClass['proxyClassName'];
        } else {
            $classname = $proxyClass['proxyClassName'];
        }

        if (!class_exists($classname, false)) {
            eval($proxyClass['code']);
        }

        if (empty($arguments)) {
            return new $classname();
        } else {
            $proxy = new \ReflectionClass($classname);
            return $proxy->newInstanceArgs($arguments);
        }
    }
}