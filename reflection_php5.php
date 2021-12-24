<?php

/**
 * Version specific reflection API.
 */
class SimpleReflection
{
    private $interface;

    /**
     * Stashes the class/interface.
     *
     * @param string $interface    Class or interface to inspect.
     */
    public function __construct($interface)
    {
        $this->interface = $interface;
    }

    /**
     * Checks that a class has been declared.
     *
     * @return bool            True if defined.
     */
    public function classExists()
    {
        $reflection = new ReflectionClass($this->interface);

        return ! $reflection->isInterface();
    }

    /**
     * Needed to kill the autoload feature in PHP5 for classes created dynamically.
     *
     * @return bool        True if defined.
     */
    public function classExistsSansAutoload()
    {
        return class_exists($this->interface, false);
    }

    /**
     * Checks that a class or interface has been declared.
     *
     * @return bool            True if defined.
     */
    public function classOrInterfaceExists()
    {
        return $this->classOrInterfaceExistsWithAutoload($this->interface, true);
    }

    /**
     * Needed to kill the autoload feature in PHP5 for classes created dynamically.
     *
     * @return bool        True if defined.
     */
    public function classOrInterfaceExistsSansAutoload()
    {
        return $this->classOrInterfaceExistsWithAutoload($this->interface, false);
    }

    /**
     * Needed to select the autoload feature in PHP5 for classes created dynamically.
     *
     * @param string $interface       Class or interface name.
     * @param bool $autoload       True totriggerautoload.
     *
     * @return bool                True if interface defined.
     */
    protected function classOrInterfaceExistsWithAutoload($interface, $autoload)
    {
        if (function_exists('interface_exists')) {
            if (interface_exists($interface, $autoload)) {
                return true;
            }
        }

        return class_exists($interface, $autoload);
    }

    /**
     * Gets the list of methods on a class or interface.
     *
     * @returns array              List of method names.
     */
    public function getMethods()
    {
        return array_unique(get_class_methods($this->interface));
    }

    /**
     * Gets the list of interfaces from a class.
     * If the class name is actually an interface then just that interface is returned.
     *
     * @returns array          List of interfaces.
     */
    public function getInterfaces()
    {
        $reflection = new ReflectionClass($this->interface);
        if ($reflection->isInterface()) {
            return array($this->interface);
        }

        return $this->onlyParents($reflection->getInterfaces());
    }

    /**
     * Gets the list of methods for the implemented interfaces only.
     *
     * @returns array      List of enforced method signatures.
     */
    public function getInterfaceMethods()
    {
        $methods = array();
        foreach ($this->getInterfaces() as $interface) {
            $methods = array_merge($methods, get_class_methods($interface));
        }

        return array_unique($methods);
    }

    /**
     * Checks to see if the method signature has to be tightly specified.
     *
     * @param string $method        Method name.
     *
     * @returns boolean             True if enforced.
     */
    protected function isInterfaceMethod($method)
    {
        return in_array($method, $this->getInterfaceMethods());
    }

    /**
     * Finds the parent class name.
     *
     * @returns string      Parent class name.
     */
    public function getParent()
    {
        $reflection = new ReflectionClass($this->interface);
        $parent     = $reflection->getParentClass();
        if ($parent) {
            return $parent->getName();
        }

        return false;
    }

    /**
     * Trivially determines if the class is abstract.
     *
     * @returns boolean      True if abstract.
     */
    public function isAbstract()
    {
        $reflection = new ReflectionClass($this->interface);

        return $reflection->isAbstract();
    }

    /**
     * Trivially determines if the class is an interface.
     *
     * @returns boolean      True if interface.
     */
    public function isInterface()
    {
        $reflection = new ReflectionClass($this->interface);

        return $reflection->isInterface();
    }

    /**
     * Scans for final methods, as they screw up inherited mocks
     * by not allowing you to override them.
     *
     * @returns boolean   True if the class has a final method.
     */
    public function hasFinal()
    {
        $reflection = new ReflectionClass($this->interface);
        foreach ($reflection->getMethods() as $method) {
            if ($method->isFinal()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whittles a list of interfaces down to only the necessary top level parents.
     *
     * @param array $interfaces     Reflection API interfaces to reduce.
     *
     * @return array               List of parent interface names.
     */
    protected function onlyParents($interfaces)
    {
        $parents   = array();
        $blacklist = array();
        foreach ($interfaces as $interface) {
            foreach ($interfaces as $possible_parent) {
                if ($interface->getName() == $possible_parent->getName()) {
                    continue;
                }
                if ($interface->isSubClassOf($possible_parent)) {
                    $blacklist[$possible_parent->getName()] = true;
                }
            }
            if (!isset($blacklist[$interface->getName()])) {
                $parents[] = $interface->getName();
            }
        }

        return $parents;
    }

    /**
     * Checks whether a method is abstract or not.
     *
     * @param string $name Method name.
     *
     * @return bool true if method is abstract, else false
     */
    protected function isAbstractMethod($name)
    {
        $interface = new ReflectionClass($this->interface);
        if (! $interface->hasMethod($name)) {
            return false;
        }

        return $interface->getMethod($name)->isAbstract();
    }

    /**
     * Checks whether a method is the constructor.
     *
     * @param string $name Method name.
     *
     * @return bool true if method is the constructor
     */
    protected function isConstructor($name)
    {
        return ($name === '__construct') || ($name == $this->interface);
    }

    /**
     * Checks whether a method is abstract in all parents or not.
     *
     * @param string $name Method name.
     *
     * @return bool true if method is abstract in parent, else false
     */
    protected function isAbstractMethodInParents($name)
    {
        $interface = new ReflectionClass($this->interface);
        $parent    = $interface->getParentClass();
        while ($parent) {
            if (! $parent->hasMethod($name)) {
                return false;
            }
            if ($parent->getMethod($name)->isAbstract()) {
                return true;
            }
            $parent = $parent->getParentClass();
        }

        return false;
    }

    /**
     * Checks whether a method is static or not.
     *
     * @param string $name Method name
     *
     * @return bool true if method is static, else false
     */
    protected function isStaticMethod($name)
    {
        $interface = new ReflectionClass($this->interface);
        if (! $interface->hasMethod($name)) {
            return false;
        }

        return $interface->getMethod($name)->isStatic();
    }

    /**
     * Writes the source code matching the declaration of a method.
     *
     * @param string $name    Method name.
     *
     * @return string         Method signature up to last bracket.
     */
    public function getSignature($name)
    {
        if ($name === '__set') {
            return 'function __set($key, $value)';
        }
        if ($name === '__call') {
            return 'function __call($method, $arguments)';
        }
        if (in_array($name, array('__get', '__isset', $name === '__unset'))) {
            return "function {$name}(\$key)";
        }
        if ($name === '__toString') {
            return "function $name()";
        }


        /**
         * @todo
         * 1) This wonky try-catch is a work around for a faulty method_exists()
         *    in early versions of PHP 5 which would return false for static methods.
         * 2) The Reflection classes work fine, but hasMethod() doesn't exist prior to PHP 5.1.0,
         *    so we need to use a more crude detection method.
         */
        try {
            $interface = new ReflectionClass($this->interface);
            $interface->getMethod($name);
        } catch (ReflectionException $e) {
            return "function $name()";
        }

        return $this->getFullSignature($name);
    }

    /**
     * For a signature specified in an interface,
     * full details must be replicated to be a valid implementation.
     *
     * @param string $name    Method name.
     *
     * @return string         Method signature up to last bracket.
     */
    protected function getFullSignature($name)
    {
        $interface = new ReflectionClass($this->interface);
        $method    = $interface->getMethod($name);
        $reference = $method->returnsReference() ? '&' : '';
        $static    = $method->isStatic() ? 'static ' : '';

        return "{$static}function $reference$name(" .
                implode(', ', $this->getParameterSignatures($method)) .
                ')';
    }

    /**
     * Gets the source code for each parameter.
     *
     * @param ReflectionMethod $method   Method object from reflection API
     *
     * @return array                     List of strings, each a snippet of code.
     */
    protected function getParameterSignatures($method)
    {
        $signatures = array();
        foreach ($method->getParameters() as $parameter) {
            $signature = '';
            if (version_compare(phpversion(), '8.0.0', '>=')) {
                $type = $parameter->getType() && ! $parameter->getType()->isBuiltin()
                    ? new ReflectionClass($parameter->getType()->getName())
                    : null;

                if ($type && $type->getName() === 'array') {
                    $signature .= 'array ';
                } elseif ($type) {
                    $signature .= $type->getName() . ' ';
                }
            } else {
                $type = $parameter->getClass();
                if (is_null($type) && version_compare(phpversion(), '5.1.0', '>=') && $parameter->isArray()) {
                    $signature .= 'array ';
                } elseif (!is_null($type)) {
                    $signature .= $type->getName() . ' ';
                }
            }
            if ($parameter->isPassedByReference()) {
                $signature .= '&';
            }
            // Variadic methods only supported in PHP 5.6+, so guard the call
            $isVariadic = version_compare(phpversion(), '5.6.0', '>=') && $parameter->isVariadic();
            if ($isVariadic) {
                $signature .= '...';
            }
            $signature .= '$' . $this->suppressSpurious($parameter->getName());
            if (!$isVariadic && $this->isOptional($parameter)) {
                $signature .= ' = null';
            }
            $signatures[] = $signature;
        }

        return $signatures;
    }

    /**
     * The SPL library has problems with the Reflection library.
     * In particular, you can get extra characters in parameter names :(.
     *
     * @todo check compatibility
     *
     * @param string $name    Parameter name.
     *
     * @return string         Cleaner name.
     */
    protected function suppressSpurious($name)
    {
        return str_replace(array('[', ']', ' '), '', $name);
    }

    /**
     * Test of a reflection parameter being optional.
     * That works with early versions of PHP5.
     *
     * @param reflectionParameter $parameter    Is this optional.
     *
     * @return bool                          True if optional.
     */
    protected function isOptional($parameter)
    {
        if (method_exists($parameter, 'isOptional')) {
            return $parameter->isOptional();
        }

        return false;
    }
}
