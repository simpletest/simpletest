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
         *    @return boolean        True if defined.
         *    @access public
         *    @static
         */
        function classExists($class) {
            return class_exists($class);
        }
        
        /**
         *    Needed to kill the autoload feature in PHP5
         *    for classes created dynamically.
         *    @return boolean        True if defined.
         *    @access public
         *    @static
         */
        function classExistsSansAutoload($class) {
            return class_exists($class);
        }
        
        /**
         *    Checks that a class or interface has been
         *    declared.
         *    @return boolean        True if defined.
         *    @access public
         *    @static
         */
        function classOrInterfaceExists($class) {
            return class_exists($class);
        }
        
        /**
         *    Needed to kill the autoload feature in PHP5
         *    for classes created dynamically.
         *    @return boolean        True if defined.
         *    @access public
         *    @static
         */
        function classOrInterfaceExistsSansAutoload($class) {
            return class_exists($class);
        }
        
        /**
         *    Gets the list of methods on a class or
         *    interface.
         *    @param string $class    Class or interface.
         *    @returns array          List of method names.
         *    @access public
         *    @static
         */
        function getMethods($class) {
            return get_class_methods($class);
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
            return array();
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
        	return "function &$method()";
        }
    }
?>