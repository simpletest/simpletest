<?php
    // $Id$
    
    if (!defined('SIMPLE_TEST')) {
        define("SIMPLE_TEST", "simpletest/");
    }
    
    /**
     *    Static global directives and options.
     */
    class SimpleTestOptions {
        
        /**
         *    Does nothing.
         */
        function SimpleTestOptions() {
        }
        
        /**
         *    Sets the name of a test case to ignore, usually
         *    because the class is an abstract case that should
         *    not be run.
         *    @param $class        Add a class to ignore.
         *    @static
         *    @public
         */
        function ignore($class) {
            $registry = &SimpleTestOptions::_getRegistry();
            $registry['IgnoreList'][] = strtolower($class);
        }
        
        /**
         *    Test to see iif a test case is in the ignore
         *    list.
         *    @param $class        Class name to test.
         *    @return              True if should not be run.
         *    @public
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
         *    @param $stub_base        Server stub class to use.
         *    @static
         *    @public
         */
        function setStubBaseClass($stub_base) {
            $registry = &SimpleTestOptions::_getRegistry();
            $registry['StubBaseClass'] = $stub_base;
        }
        
        /**
         *    Accessor for the currently set stub base class.
         *    @return            Class name to inherit from.
         *    @static
         *    @public
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
         *    @param $mock_base        Mock base class to use.
         *    @static
         *    @public
         */
        function setMockBaseClass($mock_base) {
            $registry = &SimpleTestOptions::_getRegistry();
            $registry['MockBaseClass'] = $mock_base;
        }
        
        /**
         *    Accessor for the currently set mock base class.
         *    @return            Class name to inherit from.
         *    @static
         *    @public
         */
        function getMockBaseClass() {
            $registry = &SimpleTestOptions::_getRegistry();
            return $registry['MockBaseClass'];
        }
        
        /**
         *    Adds additional mock code.
         *    @param $code    Extra code that can be added
         *                    to the partial mocks for
         *                    extra functionality. Useful
         *                    when a test tool has overridden
         *                    the mock base classes.
         *    @public
         */
        function addPartialMockCode($code = '') {
            $registry = &SimpleTestOptions::_getRegistry();
            $registry['AdditionalPartialMockCode'] = $code;
        }
        
        /**
         *    Accessor for additional partial mock code.
         *    @return        Code as a string.
         *    @public
         */
        function getPartialMockCode() {
            $registry = &SimpleTestOptions::_getRegistry();
            return $registry['AdditionalPartialMockCode'];
        }
        
        /**
         *    Accessor for global registry of options.
         *    @return            Hash of stored values.
         *    @private
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
         *    @return        Hash of registry defaults.
         *    @public
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
?>