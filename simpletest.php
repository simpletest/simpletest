<?php
    /**
     *	Global state for SimpleTest and kicker script in future versions.
     *	@package	SimpleTest
     *	@subpackage	UnitTester
     *	@version	$Id$
     */
    
    /**
     *    Static global directives and options.
     *	  @package	SimpleTest
     */
    class SimpleTest {
        
        /**
         *    Reads the SimpleTest version from the release file.
         *    @return string        Version string.
         *    @static
         *    @access public
         */
        function getVersion() {
            $content = file(dirname(__FILE__) . '/VERSION');
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
            $registry = &SimpleTest::_getRegistry();
            $registry['IgnoreList'][] = strtolower($class);
        }
        
        /**
         *    Test to see if a test case is in the ignore
         *    list.
         *    @param string $class        Class name to test.
         *    @return boolean             True if should not be run.
         *    @access public
         *    @static
         */
        function isIgnored($class) {
            $registry = &SimpleTest::_getRegistry();
            return in_array(strtolower($class), $registry['IgnoreList']);
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
            $registry = &SimpleTest::_getRegistry();
            $registry['MockBaseClass'] = $mock_base;
        }
        
        /**
         *    @deprecated
         */
        function getMockBaseClass() {
            $registry = &SimpleTest::_getRegistry();
            return $registry['MockBaseClass'];
        }
        
        /**
         *    Sets proxy to use on all requests for when
         *    testing from behind a firewall. Set host
         *    to false to disable. This will take effect
         *    if there are no other proxy settings.
         *    @param string $proxy     Proxy host as URL.
         *    @param string $username  Proxy username for authentication.
         *    @param string $password  Proxy password for authentication.
         *    @access public
         */
        function useProxy($proxy, $username = false, $password = false) {
            $registry = &SimpleTest::_getRegistry();
            $registry['DefaultProxy'] = $proxy;
            $registry['DefaultProxyUsername'] = $username;
            $registry['DefaultProxyPassword'] = $password;
        }
        
        /**
         *    Accessor for default proxy host.
         *    @return string       Proxy URL.
         *    @access public
         */
        function getDefaultProxy() {
            $registry = &SimpleTest::_getRegistry();
            return $registry['DefaultProxy'];
        }
        
        /**
         *    Accessor for default proxy username.
         *    @return string    Proxy username for authentication.
         *    @access public
         */
        function getDefaultProxyUsername() {
            $registry = &SimpleTest::_getRegistry();
            return $registry['DefaultProxyUsername'];
        }
        
        /**
         *    Accessor for default proxy password.
         *    @return string    Proxy password for authentication.
         *    @access public
         */
        function getDefaultProxyPassword() {
            $registry = &SimpleTest::_getRegistry();
            return $registry['DefaultProxyPassword'];
        }
        
        /**
         *    Sets the current test case instance. This
         *    global instance can be used by the mock objects
         *    to send message to the test cases.
         *    @param SimpleTestCase $test        Test case to register.
         *    @access public
         *    @static
         */
        function setCurrent(&$test) {
            $registry = &SimpleTest::_getRegistry();
            $registry['CurrentTestCase'] = &$test;
        }
        
        /**
         *    Accessor for current test instance.
         *    @return SimpleTEstCase        Currently running test.
         *    @access public
         *    @static
         */
        function &getCurrent() {
            $registry = &SimpleTest::_getRegistry();
            return $registry['CurrentTestCase'];
        }
        
        /**
         *    Accessor for global registry of options.
         *    @return hash           All stored values.
         *    @access private
         *    @static
         */
        function &_getRegistry() {
            static $registry = false;
            if (! $registry) {
                $registry = SimpleTest::_getDefaults();
            }
            return $registry;
        }
        
        /**
         *    Constant default values.
         *    @return hash       All registry defaults.
         *    @access private
         *    @static
         */
        function _getDefaults() {
            return array(
                    'StubBaseClass' => 'SimpleStub',
                    'MockBaseClass' => 'SimpleMock',
                    'IgnoreList' => array(),
                    'DefaultProxy' => false,
                    'DefaultProxyUsername' => false,
                    'DefaultProxyPassword' => false);
        }
    }
    
    /**
     *    @deprecated
     */
    class SimpleTestOptions extends SimpleTest {
        
        /**
         *    @deprecated
         */
        function getVersion() {
            return Simpletest::getVersion();
        }
        
        /**
         *    @deprecated
         */
        function ignore($class) {
            return Simpletest::ignore($class);
        }
        
        /**
         *    @deprecated
         */
        function isIgnored($class) {
            return Simpletest::isIgnored($class);
        }
        
        /**
         *    @deprecated
         */
        function setMockBaseClass($mock_base) {
            return Simpletest::setMockBaseClass($mock_base);
        }
        
        /**
         *    @deprecated
         */
        function getMockBaseClass() {
            return Simpletest::getMockBaseClass();
        }
        
        /**
         *    @deprecated
         */
        function useProxy($proxy, $username = false, $password = false) {
            return Simpletest::useProxy($proxy, $username, $password);
        }
        
        /**
         *    @deprecated
         */
        function getDefaultProxy() {
            return Simpletest::getDefaultProxy();
        }
        
        /**
         *    @deprecated
         */
        function getDefaultProxyUsername() {
            return Simpletest::getDefaultProxyUsername();
        }
        
        /**
         *    @deprecated
         */
        function getDefaultProxyPassword() {
            return Simpletest::getDefaultProxyPassword();
        }
    }
?>