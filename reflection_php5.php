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
            if (interface_exists($this->_interface)) {
            	return true;
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
            if (interface_exists($this->_interface, false)) {
            	return true;
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
            	return array($class);
            }
            $interfaces = array();
            foreach ($reflection->getInterfaces() as $interface) {
            	$interfaces[] = $interface->getName();
            }
            return $interfaces;
        }
        
        /**
         *	  Gets the source code matching the declaration
         *	  of a method.
         * 	  @param string $method		  Method name.
         *    @access public
         */
        function getSignature($method) {
        	if ($method == '__get') {
        		return 'function __get($key)';
        	}
        	if ($method == '__set') {
        		return 'function __set($key, $value)';
        	}
	        $reflection = new ReflectionClass($this->_interface);
        	$code = "function $method(";
        	if (is_callable(array($interface, $method))) {
		        if ($reflection->getMethod($method)->returnsReference()) {
	        		$code = "function &$method(";
	        	}
	            $as_code = array();
	            foreach ($reflection->getMethod($method)->getParameters() as $parameter) {
	            	$as_code[] =
	            			($parameter->isPassedByReference() ? '&' : '') .
	            			'$' . $parameter->getName() .
	            			($parameter->isOptional() ? ' = false' : '');
	            }
	            $code .= implode(', ', $as_code);
	        }
        	$code .= ")";
        	return $code;
        }
    }
?>