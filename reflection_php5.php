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
    }
?>
