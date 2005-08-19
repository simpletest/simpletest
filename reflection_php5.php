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
            return class_exists($class, false);
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
    }
?>
