<?php
    /**
     *	base include file for SimpleTest
     *	@package	SimpleTest
     *	@subpackage	UnitTester
     *	@version	$Id$
     */

    /**
     *    Version specific reflection API.
	 *    @package SimpleTest
	 *    @subpackage UnitTester
     */
    class SimpleReflection {
        var $_interface;

        /**
         *    Stashes the class/interface.
         *    @param string $interface    Class or interface
         *                                to inspect.
         */
        function SimpleReflection($interface) {
            $this->_interface = $interface;
        }

        /**
         *    Checks that a class has been declared. Versions
         *    before PHP5.0.2 need a check that it's not really
         *    an interface.
         *    @return boolean            True if defined.
         *    @access public
         */
        function classExists() {
            if (! class_exists($this->_interface)) {
                return false;
            }
            $reflection = new ReflectionClass($this->_interface);
            return ! $reflection->isInterface();
        }

        /**
         *    Needed to kill the autoload feature in PHP5
         *    for classes created dynamically.
         *    @return boolean        True if defined.
         *    @access public
         */
        function classExistsSansAutoload() {
            return class_exists($this->_interface, false);
        }

        /**
         *    Checks that a class or interface has been
         *    declared.
         *    @return boolean            True if defined.
         *    @access public
         */
        function classOrInterfaceExists() {
            return $this->_classOrInterfaceExistsWithAutoload($this->_interface, true);
        }

        /**
         *    Needed to kill the autoload feature in PHP5
         *    for classes created dynamically.
         *    @return boolean        True if defined.
         *    @access public
         */
        function classOrInterfaceExistsSansAutoload() {
            return $this->_classOrInterfaceExistsWithAutoload($this->_interface, false);
        }

        /**
         *    Needed to select the autoload feature in PHP5
         *    for classes created dynamically.
         *    @param string $interface       Class or interface name.
         *    @param boolean $autoload       True totriggerautoload.
         *    @return boolean                True if interface defined.
         *    @access private
         */
        function _classOrInterfaceExistsWithAutoload($interface, $autoload) {
            if (function_exists('interface_exists')) {
                if (interface_exists($this->_interface, $autoload)) {
                    return true;
                }
            }
            return class_exists($this->_interface, $autoload);
        }

        /**
         *    Gets the list of methods on a class or
         *    interface.
         *    @returns array              List of method names.
         *    @access public
         */
        function getMethods() {
            return array_unique(get_class_methods($this->_interface));
        }

        /**
         *    Gets the list of interfaces from a class. If the
         *	  class name is actually an interface then just that
         *	  interface is returned.
         *    @returns array          List of interfaces.
         *    @access public
         */
        function getInterfaces() {
            $reflection = new ReflectionClass($this->_interface);
            if ($reflection->isInterface()) {
            	return array($this->_interface);
            }
            return $this->_onlyParents($reflection->getInterfaces());
        }

        /**
         *    Wittles a list of interfaces down to only the top
         *    level parents.
         *    @param array $interfaces     Reflection API interfaces
         *                                 to reduce.
         *    @returns array               List of parent interface names.
         *    @access private
         */
        function _onlyParents($interfaces) {
            $parents = array();
            foreach ($interfaces as $interface) {
                foreach($interfaces as $possible_parent) {
                    if ($interface->getName() == $possible_parent->getName()) {
                        continue;
                    }
                    if ($interface->isSubClassOf($possible_parent)) {
                        break;
                    }
                }
                $parents[] = $interface->getName();
            }
            return $parents;
        }

        /**
         *	  Gets the source code matching the declaration
         *	  of a method.
         * 	  @param string $name	Method name.
         *    @return string        Method signature up to last
         *                          bracket.
         *    @access public
         */
        function getSignature($name) {
        	if ($name == '__get') {
        		return 'function __get($key)';
        	}
        	if ($name == '__set') {
        		return 'function __set($key, $value)';
        	}
        	if (! is_callable(array($this->_interface, $name))) {
        		return "function $name()";
        	}
	        $interface = new ReflectionClass($this->_interface);
	        $method = $interface->getMethod($name);
	        $reference = $method->returnsReference() ? '&' : '';
        	return "function $reference$name(" .
            		implode(', ', $this->_getParameterSignatures($method)) .
            		")";
        }

        /**
         *    Gets the source code for each parameter.
         *    @param ReflectionMethod $method   Method object from
         *										reflection API
         *    @return array                     List of strings, each
         *                                      a snippet of code.
         *    @access private
         */
        function _getParameterSignatures($method) {
        	$signatures = array();
            foreach ($method->getParameters() as $parameter) {
            	$signatures[] =
					(! is_null($parameter->getClass()) ? $parameter->getClass()->getName() . ' ' : '') .
            			($parameter->isPassedByReference() ? '&' : '') .
            			'$' . $parameter->getName() .
            			($this->_isOptional($parameter) ? ' = false' : '');
            }
            return $signatures;
        }

        /**
         *    Test of a reflection parameter being optional
         *    that works with early versions of PHP5.
         *    @param reflectionParameter $parameter    Is this optional.
         *    @return boolean                          True if optional.
         *    @access private
         */
        function _isOptional($parameter) {
            if (method_exists($parameter, 'isOptional')) {
                return $parameter->isOptional();
            }
            return false;
        }
    }
?>