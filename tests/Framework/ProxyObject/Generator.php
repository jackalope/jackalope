<?php
/**
 * Class used by the ProxyObject to generate the actual proxy pbject.
 *
 * @package Jackalope
 * @subpackage Unittests
 */

namespace Tests\Framework;

/**
 * Path to this module.
 * @var string
 */
$modulePath = dirname(__FILE__);

if (version_compare(\PHPUnit_Runner_Version::id(), '3.5', '<')) {
    include_once('PHPUnit/Util/Class.php');
    include_once('PHPUnit/Util/Filter.php');
    include_once('PHPUnit/Framework/Exception.php');
}

/**
 * Class used by the ProxyObject to generate the actual proxy pbject.
 *
 * @package Jackalope
 * @subpackage Unittests
 *
 */
class ProxyObjectGenerator
{
    /**
     * Contain elements worth caching.
     * @var array
     */
    protected static $cache = array();

    /**
     * List of methods not to be reflected.
     * @var array
     */
    protected static $blacklistedMethodNames = array(
    '__clone' => TRUE,
    '__destruct' => TRUE,
    'abstract' => TRUE,
    'and' => TRUE,
    'array' => TRUE,
    'as' => TRUE,
    'break' => TRUE,
    'case' => TRUE,
    'catch' => TRUE,
    'class' => TRUE,
    'clone' => TRUE,
    'const' => TRUE,
    'continue' => TRUE,
    'declare' => TRUE,
    'default' => TRUE,
    'do' => TRUE,
    'else' => TRUE,
    'elseif' => TRUE,
    'enddeclare' => TRUE,
    'endfor' => TRUE,
    'endforeach' => TRUE,
    'endif' => TRUE,
    'endswitch' => TRUE,
    'endwhile' => TRUE,
    'extends' => TRUE,
    'final' => TRUE,
    'for' => TRUE,
    'foreach' => TRUE,
    'function' => TRUE,
    'global' => TRUE,
    'goto' => TRUE,
    'if' => TRUE,
    'implements' => TRUE,
    'interface' => TRUE,
    'instanceof' => TRUE,
    'namespace' => TRUE,
    'new' => TRUE,
    'or' => TRUE,
    'private' => TRUE,
    'protected' => TRUE,
    'public' => TRUE,
    'static' => TRUE,
    'switch' => TRUE,
    'throw' => TRUE,
    'try' => TRUE,
    'use' => TRUE,
    'var' => TRUE,
    'while' => TRUE,
    'xor' => TRUE
    );

    /**
     * Gets the data to be used for the actual reflection.
     *
     * If the class has already been reflected in the same configuration
     * it will be fetched from the local cache.
     *
     * @param  string  $originalClassName Name of the class to be reflected.
     * @param  array   $methods List of methods to be exposed.
     * @param  string  $proxyClassName Name to be used for the reflected class.
     * @param boolean $callAutoload Switch to run the autoloader.
     * @return array The data to be used for the actual reflection.
     */
    public static function generate($originalClassName, array $methods = NULL, $proxyClassName = '',
                                    $callAutoload = false) {

        if ($proxyClassName == '') {
            $key = md5(
            $originalClassName.
            serialize($methods)
            );

            if (isset(self::$cache[$key])) {
                return self::$cache[$key];
            }
        }

        $proxy = self::generateProxy(
            $originalClassName,
            $methods,
            $proxyClassName,
            $callAutoload
        );

        if (isset($key)) {
            self::$cache[$key] = $proxy;
        }

        return $proxy;
    }

    /**
     * Generates the data to be used for the actual reflection.
     *
     * @param  string  $originalClassName Name of the class to be reflected.
     * @param  array   $methods List of methods to be exposed.
     * @param  string  $proxyClassName Name to be used for the reflected class.
     * @param boolean $callAutoload Switch to run the autoloader.
     * @return array The data to be used for the actual reflection.
     */
    protected static function generateProxy($originalClassName, array $methods = null,
                                            $proxyClassName = '', $callAutoload = false) {
        $templateDir = dirname(__FILE__).DIRECTORY_SEPARATOR.'Generator'.DIRECTORY_SEPARATOR;
        $classTemplate = self::createTemplateObject(
            $templateDir.'proxied_class.tpl'
        );

        $proxyClassName = self::generateProxyClassName(
            $originalClassName, $proxyClassName
        );

        if (interface_exists($proxyClassName['fullClassName'], $callAutoload)) {
            throw new \PHPUnit_Framework_Exception(
                sprintf(
                    '"%s" is an interface.',
                    $proxyClassName['fullClassName']
                )
            );
        }

        if (!class_exists($proxyClassName['fullClassName'], $callAutoload)) {
            throw new \PHPUnit_Framework_Exception(
                sprintf(
                    'Class "%s" does not exists.',
                    $proxyClassName['fullClassName']
                )
            );
        }

        $class = new \ReflectionClass($proxyClassName['fullClassName']);

        if ($class->isFinal()) {
            throw new \PHPUnit_Framework_Exception(
                sprintf(
                    'Class "%s" is declared "final". Can not create proxy.',
                    $proxyClassName['fullClassName']
                )
            );
        }

        $proxyMethods = array();
        if (is_array($methods) && count($methods) > 0) {
            foreach ($methods as $methodName) {
                if ($class->hasMethod($methodName)) {
                    $method = $class->getMethod($methodName);
                    if (self::canProxyMethod($method)) {
                        $proxyMethods[] = $method;
                    } else {
                        throw new \PHPUnit_Framework_Exception(
                            sprintf(
                                'Can not proxy method "%s" of class "%s".',
                                $methodName,
                                $proxyClassName['fullClassName']
                            )
                        );
                    }
                } else {
                    throw new \PHPUnit_Framework_Exception(
                        sprintf(
                            'Class "%s" has no protected method "%s".',
                            $proxyClassName['fullClassName'],
                            $methodName
                        )
                    );
                }
            }
        } else {
            $proxyMethods = $class->getMethods(\ReflectionMethod::IS_PROTECTED);
            if (!(is_array($proxyMethods) && count($proxyMethods) > 0)) {
                throw new \PHPUnit_Framework_Exception(
                    sprintf(
                        'Class "%s" has no protected methods.',
                         $proxyClassName['fullClassName']
                    )
                );
            }
        }

        $proxiedMethods = '';
        foreach ($proxyMethods as $method) {
            $proxiedMethods .= self::generateProxiedMethodDefinition(
                $templateDir, $method
            );
        }

        if (!empty($proxyClassName['namespaceName'])) {
            $prologue = 'namespace '.$proxyClassName['namespaceName'].";\n\n";
        }

        $classTemplate->setVar(
            array(
                'prologue' => isset($prologue) ? $prologue : '',
                'class_declaration' => $proxyClassName['proxyClassName'].' extends '.$originalClassName,
                'methods' => $proxiedMethods
            )
        );

        return array(
            'code' => $classTemplate->render(),
            'proxyClassName' => $proxyClassName['proxyClassName'],
            'namespaceName' => $proxyClassName['namespaceName']
        );
    }

    /**
     * Generates a unique name to be used to identify the created proxyclass.
     *
     * @param  string $originalClassName Name of the class to be reflected.
     * @param  string $proxyClassName Name to be used for the reflected class.
     * @return array Information of the class to be reflected.
     */
    protected static function generateProxyClassName($originalClassName, $proxyClassName)
    {
        $classNameParts = explode('\\', $originalClassName);

        if (count($classNameParts) > 1) {
            $originalClassName = array_pop($classNameParts);
            $namespaceName = implode('\\', $classNameParts);
            $fullClassName = $namespaceName.'\\'.$originalClassName;

            // eval does identifies namespaces with leading backslash as constant.
            $namespaceName = (0 === stripos($namespaceName, '\\') ? substr($namespaceName, 1) : $namespaceName);

        } else {
            $namespaceName = '';
            $fullClassName = $originalClassName;
        }

        if ($proxyClassName == '') {
            do {
                $proxyClassName = 'Proxy_'.$originalClassName.'_'.substr(md5(microtime()), 0, 8);
            } while (class_exists($proxyClassName, false));
        }

        return array(
            'proxyClassName' => $proxyClassName,
            'className' => $originalClassName,
            'fullClassName' => $fullClassName,
            'namespaceName' => $namespaceName
        );
    }

    /**
     * Generates the definition of a method to be proxied.
     *
     * @param string $templateDir Location of the templates to be used to create the proxy.
     * @param \ReflectionMethod $method Name of the method to be reflected.
     * @return array Information about the method to be proxied.
     */
    protected static function generateProxiedMethodDefinition($templateDir, \ReflectionMethod $method)
    {
        if ($method->returnsReference()) {
            $reference = '&';
        } else {
            $reference = '';
        }

        $template = self::createTemplateObject(
            $templateDir . 'proxied_method.tpl'
        );

        $template->setVar(
            array(
                'arguments_declaration' => \PHPUnit_Util_Class::getMethodParameters($method),
                'arguments' => self::getMethodCallParameters($method),
                'method_name' => $method->getName(),
                'reference'   => $reference
            )
        );
        return $template->render();
    }

    /**
     * Gets the arguments the proxied method expectes.
     *
     * @param string $method
     * @return array List of parameters to be passed to the proxied method.
     */
    public static function getMethodCallParameters($method)
    {
        $parameters = array();
        foreach ($method->getParameters() as $i => $parameter) {
            $parameters[] = '$'.$parameter->getName();
        }
        return join(', ', $parameters);
    }

    /**
     * Determine if the given method may be proxied.
     *
     * Since it is not possible to reflect a
     *  - constructor
     *  - final method
     *  - static method
     * those will cause this method to return false.
     * Also methods registered in the blacklist will cause this
     * method to return false.
     *
     * @param \ReflectionMethod $method Name of the method to be reflected.
     * @return boolean True, if the given method may be reflected, else false.
     */
    protected static function canProxyMethod(\ReflectionMethod $method)
    {
        if ($method->isConstructor() ||
        $method->isFinal() ||
        $method->isStatic() ||
        isset(self::$blacklistedMethodNames[$method->getName()])) {
            return false;
        } elseif ($method->isProtected()) {
            return true;
        }
        return false;
    }

    /**
     * Generates the template to be used to create a proxy object.
     *
     * The return value depends on the used version of PHPUnit.
     * If a version is 3.5 of higher Text_Template, else PHPUnit_Util_Template
     * is used.
     *
     * @param string $file The location of the template file to be used.
     * @return Text_Template|PHPUnit_Util_Template The template object to create the proxy class.
     */
    protected static function createTemplateObject($file)
    {
        if (version_compare(\PHPUnit_Runner_Version::id(), '3.5', '>=')) {
            include_once('Text/Template.php');
            return new \Text_Template($file);
        } else {
            include_once('PHPUnit/Util/Template.php');
            return new \PHPUnit_Util_Template($file);
        }
    }
}