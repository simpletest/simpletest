<?php
    /**
     *	base include file for SimpleTest
     *	@package	SimpleTest
     *	@subpackage	UnitTester
     *	@version	$Id$
     */

    /**
     *    Version specific reflection API.
	 *	  @package SimpleTest
	 *	  @subpackage UnitTester
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
         *    Checks that a class has been declared.
         *    @return boolean            True if defined.
         *    @access public
         */
        function classExists() {
            return class_exists($this->_interface);
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
            if (function_exists('interface_exists')) {
                if (interface_exists($this->_interface)) {
                    return true;
                }
            }
            return class_exists($this->_interface);
        }

        /**
         *    Needed to kill the autoload feature in PHP5
         *    for classes created dynamically.
         *    @return boolean        True if defined.
         *    @access public
         */
        function classOrInterfaceExistsSansAutoload() {
            if (function_exists('interface_exists')) {
                if (interface_exists($this->_interface, false)) {
                    return true;
                }
            }
            return class_exists($this->_interface, false);
        }

        /**
         *    Gets the list of methods on a class or
         *    interface.
         *    @returns array              List of method names.
         *    @access public
         */
        function getMethods() {
            return get_class_methods($this->_interface);
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
            $interfaces = array();
            foreach ($reflection->getInterfaces() as $interface) {
                if (! $interface->isInternal()) {
                    $interfaces[] = $interface->getName();
                }
            }
            return $interfaces;
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
         *	  Gets the source code for each parameter.
         * 	  @param ReflectionMethod $method   Method object from
         *										reflection API
         *    @return array                     List of strings, each
         *                                      a snippet of code.
         *    @access private
         */
        function _getParameterSignatures($method) {
        	$signatures = array();
            foreach ($method->getParameters() as $parameter) {
            	$signatures[] =
            			($parameter->isPassedByReference() ? '&' : '') .
            			'$' . $parameter->getName() .
            			($parameter->isOptional() ? ' = false' : '');
            }
            return $signatures;
        }
    }
?>