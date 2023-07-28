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
     * @param string $interface class or interface to inspect
     */
    public function __construct($interface)
    {
        $this->interface = $interface;
    }

    /**
     * Checks that a class has been declared.
     *
     * @return bool true if defined
     */
    public function classExists()
    {
        $reflection = new ReflectionClass($this->interface);

        return !$reflection->isInterface();
    }

    /**
     * Needed to kill the autoload feature in PHP5 for classes created dynamically.
     *
     * @return bool true if defined
     */
    public function classExistsWithoutAutoload()
    {
        return class_exists($this->interface, false);
    }

    /**
     * Checks that a class or interface has been declared.
     *
     * @return bool true if defined
     */
    public function classOrInterfaceExists()
    {
        return $this->classOrInterfaceExistsWithAutoload($this->interface, true);
    }

    /**
     * Needed to kill the autoload feature in PHP5 for classes created dynamically.
     *
     * @return bool true if defined
     */
    public function classOrInterfaceExistsWithoutAutoload()
    {
        return $this->classOrInterfaceExistsWithAutoload($this->interface, false);
    }

    /**
     * Needed to select the autoload feature in PHP5 for classes created dynamically.
     *
     * @param string $interface class or interface name
     * @param bool   $autoload  True, to trigger autoloading. Default: true.
     *
     * @return bool true if interface defined
     */
    protected function classOrInterfaceExistsWithAutoload($interface, $autoload = true)
    {
        if (interface_exists($interface, $autoload)) {
            return true;
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
     * Gets the list of all methods in a class or interface, including
     * non-visible.
     *
     * @returns array              List of method names.
     */
    public function getAllMethods()
    {
        $reflection = new ReflectionClass($this->interface);

        return array_map(function($method) {
            return $method->getName();
        }, $reflection->getMethods());
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
            return [$this->interface];
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
        $methods = [];
        $interfaces = $this->getInterfaces();
        foreach ($interfaces as $interface) {
            $methods = array_merge($methods, get_class_methods($interface));
        }

        return array_unique($methods);
    }

    /**
     * Checks to see if the method signature has to be tightly specified.
     *
     * @param string $method method name
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
        $parent = $reflection->getParentClass();
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
        $methods = $reflection->getMethods();
        foreach ($methods as $method) {
            if ($method->isFinal()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whittles a list of interfaces down to only the necessary top level parents.
     *
     * @param array $interfaces reflection API interfaces to reduce
     *
     * @return array list of parent interface names
     */
    protected function onlyParents($interfaces)
    {
        $parents = [];
        $blacklist = [];
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
     * @param string $name method name
     *
     * @return bool true if method is abstract, else false
     */
    protected function isAbstractMethod($name)
    {
        $interface = new ReflectionClass($this->interface);
        if (!$interface->hasMethod($name)) {
            return false;
        }

        return $interface->getMethod($name)->isAbstract();
    }

    /**
     * Checks whether a method is the constructor.
     *
     * @param string $name method name
     *
     * @return bool true if method is the constructor
     */
    protected function isConstructor($name)
    {
        return ('__construct' === $name) || ($name == $this->interface);
    }

    /**
     * Checks whether a method is abstract in all parents or not.
     *
     * @param string $name method name
     *
     * @return bool true if method is abstract in parent, else false
     */
    public function isAbstractMethodInParents($name)
    {
        $interface = new ReflectionClass($this->interface);
        $parent = $interface->getParentClass();
        while ($parent) {
            if (!$parent->hasMethod($name)) {
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
        if (!$interface->hasMethod($name)) {
            return false;
        }

        return $interface->getMethod($name)->isStatic();
    }

    /**
     * Returns the source code matching the declaration of a method.
     *
     * @param string $name method name
     *
     * @return string method signature up to last bracket
     */
    public function getSignature($name)
    {
        $interface = new ReflectionClass($this->interface);
        $method = $interface->getMethod($name);

        $abstract = ($method->isAbstract() && !$interface->isInterface() && !$this->isAbstractMethodInParents($name)) ? 'abstract ' : '';

        if ($method->isPublic()) {
            $visibility = 'public';
        } elseif ($method->isProtected()) {
            $visibility = 'protected';
        } else {
            $visibility = 'private';
        }

        $static = $method->isStatic() ? 'static ' : '';
        $reference = $method->returnsReference() ? '&' : '';
        $params = $this->getParameterSignatures($method);
        $returnType = $this->getReturnType($method);

        return "{$abstract}$visibility {$static}function $reference$name($params){$returnType}";
    }

    /**
     * Get the source code for the parameters of a method.
     *
     * @param ReflectionMethod $method Method object from reflection API
     *
     * @return string the Parameters string for a method
     */
    protected function getParameterSignatures($method)
    {
        $signatures = [];
        $parameters = $method->getParameters();
        foreach ($parameters as $parameter) {
            $signature = $this->getParameterTypeHint($parameter);
            if ($parameter->isPassedByReference()) {
                $signature .= '&';
            }
            // Guard: Variadic methods only supported by PHP 5.6+
            $isVariadic = (PHP_VERSION_ID >= 50600) && $parameter->isVariadic();
            if ($isVariadic) {
                $signature .= '...';
            }
            $signature .= '$'.$parameter->getName();
            if (!$isVariadic) {
                if ($parameter->isDefaultValueAvailable()) {
                    $signature .= ' = '.var_export($parameter->getDefaultValue(), true);
                } elseif ($parameter->isOptional()) {
                    $signature .= ' = null';
                }
            }

            $signatures[] = $signature;
        }

        return implode(', ', $signatures);
    }

    /**
     * getReturnType.
     *
     * @param ReflectionMethod $method Method object from reflection API
     *
     * @return string the Parameters string for a method
     */
    protected function getReturnType($method)
    {
        // the return type feature doesn't exist below PHP7, return empty string by default
        $returnTypeString = '';
        // Guard: method getReturnType() is only supported by PHP7.0+
        if (PHP_VERSION_ID >= 70000) {
            $returnType = $method->getReturnType();
            $returnTypeString = (string) $returnType;

            if ('self' === $returnTypeString) {
                $returnTypeString = '\\'.$this->method->getDeclaringClass()->getName();
            }

            if ('' != $returnTypeString) {
                // Guard: method getReturnType()->allowsNull() is only supported by PHP7.1+
                if (PHP_VERSION_ID >= 70100
                    &&
                    $returnType->allowsNull()
                    &&
                    // getReturnType->__toString() for Throwable
                    // already return question mark ("?Throwable"), so check it.
                    // Using strpos() instead of str_starts_with() for backward compatibility
                    strpos($returnTypeString, "?") !== 0) {
                    $returnTypeString = '?'.$returnTypeString;
                }
                $returnTypeString = ': ' . $returnTypeString;
            }
        }

        return $returnTypeString;
    }

    protected function getParameterTypeHint(ReflectionParameter $parameter)
    {
        // Guard: parameter types only supported by PHP7.0+
        if ((PHP_VERSION_ID >= 70000) && $parameter->hasType()) {
            $typeHint = $parameter->getType();
            if ($typeHint && PHP_VERSION_ID >= 70100) {
                if (get_class($typeHint) === 'ReflectionNamedType') {
                    $typeHint = $typeHint->getName();
                }
                elseif (get_class($typeHint) === 'ReflectionUnionType') {
                    $typeHint = implode("|", $typeHint->getTypes());
                }
                elseif (get_class($typeHint) === 'ReflectionIntersectionType') {
                    $typeHint = implode("&", $typeHint->getTypes());
                }
            } else {
                $typeHint = (string) $typeHint;
            }
        }
        // Guard: parameter is array only supported by <PHP8
        elseif((PHP_VERSION_ID < 80000) && $parameter->isArray()) {
            $typeHint = 'array';
        }
        else {
            $typeHint = '';
        }

        if (empty($typeHint)) {
            return '';
        }

        $typesThatDontRequirePrefixSlash = [
            'self', 'array', 'callable',
            // PHP 7
            'bool', 'float', 'int', 'string', 'object',
            // PHP 8
            'mixed',
        ];

        // prefix a slash, on "class" or "interface" typehints
        if (!in_array($typeHint, $typesThatDontRequirePrefixSlash)
            &&
            // Union or intersection type don't need to prefix a slash
            // using strpos() instead of str_contains() for backward compatibility
            strpos($typeHint, "|") === false && strpos($typeHint, "&") === false) {
            $typeHint = '\\'.$typeHint;
        }

        return $typeHint .= ' ';
    }
}
