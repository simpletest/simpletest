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
        
        /**
         *    Checks that a class has been declared.
         *	  @param string $interface   Name of interface or class
         *								 to test for.
         *    @return boolean            True if defined.
         *    @access public
         *    @static
         */
        function classExists($interface) {
            return class_exists($interface);
        }
        
        /**
         *    Needed to kill the autoload feature in PHP5
         *    for classes created dynamically.
         *    @return boolean        True if defined.
         *    @access public
         *    @static
         */
        function classExistsSansAutoload($interface) {
            return class_exists($interface, false);
        }
        
        /**
         *    Checks that a class or interface has been
         *    declared.
         *	  @param string $interface   Name of interface or class
         *								 to test for.
         *    @return boolean            True if defined.
         *    @access public
         *    @static
         */
        function classOrInterfaceExists($interface) {
            if (interface_exists($interface)) {
            	return true;
            }
            return class_exists($interface);
        }
        
        /**
         *    Needed to kill the autoload feature in PHP5
         *    for classes created dynamically.
         *    @return boolean        True if defined.
         *    @access public
         *    @static
         */
        function classOrInterfaceExistsSansAutoload($interface) {
            if (interface_exists($interface, false)) {
            	return true;
            }
            return class_exists($interface, false);
        }
        
        /**
         *    Gets the list of methods on a class or
         *    interface.
         *    @param string $interface    Class or interface.
         *    @returns array              List of method names.
         *    @access public
         *    @static
         */
        function getMethods($interface) {
            return get_class_methods($interface);
        }
        
        /**
         *    Gets the list of interfaces from a class. If the
         *	  class name is actually an interface then just that
         *	  interface is returned.
         *    @param string $class    Class to examine.
         *    @returns array          List of interfaces.
         *    @access public
         *    @static
         */
        function getInterfaces($class) {
            $reflection = new ReflectionClass($class);
            if ($reflection->isInterface()) {
            	return array($class);
            }
        }
        
        /**
         *	  Gets the source code matching the declaration
         *	  of a method.
         *    @param string $interface    Class or interface.
         * 	  @param string $method		  Method name.
         *    @access public
         *    @static
         */
        function getSignature($interface, $method) {
        	if ($method == '__get') {
        		return 'function __get($key)';
        	}
        	if ($method == '__set') {
        		return 'function __set($key, $value)';
        	}
        	$code = "function $method(";
        	if (is_callable(array($interface, $method))) {
	            $reflection = new ReflectionClass($interface);
	            $as_code = array();
	            foreach ($reflection->getMethod($method)->getParameters() as $parameter) {
	            	$as_code[] = '$' . $parameter->getName() .
	            			($parameter->isOptional() ? ' = false' : '');
	            }
	            $code .= implode(', ', $as_code);
	        }
        	$code .= ")";
        	return $code;
        }
    }
?>