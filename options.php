<?php
    /**
     *	base include file for SimpleTest
     *	@package	SimpleTest
     *	@version	$Id$
     */
    
	/**
	 * @ignore	originally defined in simple_test.php
	 */
    if (! defined('SIMPLE_TEST')) {
        define('SIMPLE_TEST', 'simpletest/');
    }
    
    /**
     *    Static global directives and options.
     *	  @package	SimpleTest
     */
    class SimpleTestOptions {
        
        /**
         *    Reads the SimpleTest version from the release file.
         *    @return string        Version string.
         *    @static
         *    @access public
         */
        function getVersion() {
            $content = file(SIMPLE_TEST . 'VERSION');
            return trim($content[0]);
        }
        
        /**
         *    Sets the name of a test case to ignore, usually
         *    because the class is an abstract case that should
         *    not be run.
         *    @param string $class        Add a class to ignore.
         *    @static
         *    @access public
         */
        function ignore($class) {
            $registry = &SimpleTestOptions::_getRegistry();
            $registry['IgnoreList'][] = strtolower($class);
        }
        
        /**
         *    Test to see iif a test case is in the ignore
         *    list.
         *    @param string $class        Class name to test.
         *    @return boolean             True if should not be run.
         *    @access public
         *    @static
         */
        function isIgnored($class) {
            $registry = &SimpleTestOptions::_getRegistry();
            return in_array(strtolower($class), $registry['IgnoreList']);
        }
        
        /**
         *    The base class name is settable here. This is the
         *    class that a new stub will inherited from.
         *    To modify the generated stubs simply extend the
         *    SimpleStub class and set it's name
         *    with this method before any stubs are generated.
         *    @param string $stub_base     Server stub class to use.
         *    @static
         *    @access public
         */
        function setStubBaseClass($stub_base) {
            $registry = &SimpleTestOptions::_getRegistry();
            $registry['StubBaseClass'] = $stub_base;
        }
        
        /**
         *    Accessor for the currently set stub base class.
         *    @return string        Class name to inherit from.
         *    @static
         *    @access public
         */
        function getStubBaseClass() {
            $registry = &SimpleTestOptions::_getRegistry();
            return $registry['StubBaseClass'];
        }
        
        /**
         *    The base class name is settable here. This is the
         *    class that a new mock will inherited from.
         *    To modify the generated mocks simply extend the
         *    SimpleMock class and set it's name
         *    with this method before any mocks are generated.
         *    @param string $mock_base        Mock base class to use.
         *    @static
         *    @access public
         */
        function setMockBaseClass($mock_base) {
            $registry = &SimpleTestOptions::_getRegistry();
            $registry['MockBaseClass'] = $mock_base;
        }
        
        /**
         *    Accessor for the currently set mock base class.
         *    @return string           Class name to inherit from.
         *    @static
         *    @access public
         */
        function getMockBaseClass() {
            $registry = &SimpleTestOptions::_getRegistry();
            return $registry['MockBaseClass'];
        }
        
        /**
         *    Adds additional mock code.
         *    @param string $code    Extra code that can be added
         *                           to the partial mocks for
         *                           extra functionality. Useful
         *                           when a test tool has overridden
         *                           the mock base classes.
         *    @access public
         */
        function addPartialMockCode($code = '') {
            $registry = &SimpleTestOptions::_getRegistry();
            $registry['AdditionalPartialMockCode'] = $code;
        }
        
        /**
         *    Accessor for additional partial mock code.
         *    @return string       Extra code.
         *    @access public
         */
        function getPartialMockCode() {
            $registry = &SimpleTestOptions::_getRegistry();
            return $registry['AdditionalPartialMockCode'];
        }
        
        /**
         *    Accessor for global registry of options.
         *    @return hash           All stored values.
         *    @access private
         *    @static
         */
        function &_getRegistry() {
            static $registry = false;
            if (!$registry) {
                $registry = SimpletestOptions::getDefaults();
            }
            return $registry;
        }
        
        /**
         *    Constant default values.
         *    @return hash       All registry defaults.
         *    @access public
         *    @static
         */
        function getDefaults() {
            return array(
                    'StubBaseClass' => 'SimpleStub',
                    'MockBaseClass' => 'SimpleMock',
                    'IgnoreList' => array(),
                    'AdditionalPartialMockCode' => '');
        }
    }
    
    /**
     *  Static methods for compatibility between different
     *  PHP versions.
     *	@package	SimpleTest
     */
    class SimpleTestCompatibility {
        
        /**
         *    Test to see if an object is a memebr of a
         *    class hiearchy.
         *    @param object $object    Object to test.
         *    @param string $class     Root name of hiearchy.
         *    @access public
         *    @static
         */
        function isA($object, $class) {
            if (version_compare(phpversion(), '5') >= 0) {
                if (! class_exists($class)) {
                    return false;
                }
                eval("\$is_a = \$object instanceof $class;");
                return $is_a;
            }
            if (function_exists('is_a')) {
                return is_a($object, $class);
            }
            return ((strtolower($class) == get_class($object))
                    or (is_subclass_of($object, $class)));
        }
        
        /**
         *    Sets a socket timeout for each chunk.
         *    @param resource $handle    Socket handle.
         *    @param integer $timeout    Limit in seconds.
         *    @access public
         *    @static
         */
        function setTimeout($handle, $timeout) {
            stream_set_timeout($handle, $timeout, 0);
        }
    }
?>
