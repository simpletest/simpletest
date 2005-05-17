<?php
    // $Id$
    require_once(dirname(__FILE__) . '/../unit_tester.php');
    require_once(dirname(__FILE__) . '/../shell_tester.php');
    require_once(dirname(__FILE__) . '/../mock_objects.php');
    require_once(dirname(__FILE__) . '/../web_tester.php');
    require_once('../extensions/pear_test_case.php');
    require_once('../extensions/phpunit_test_case.php');
    
    class UnitTests extends GroupTest {
        function UnitTests() {
            $this->GroupTest('Unit tests');
            $this->addTestFile('errors_test.php');
            $this->addTestFile('options_test.php');
            $this->addTestFile('dumper_test.php');
            $this->addTestFile('expectation_test.php');
            $this->addTestFile('unit_tester_test.php');
            $this->addTestFile('collector_test.php');
            $this->addTestFile('simple_mock_test.php');
            $this->addTestFile('adapter_test.php');
            $this->addTestFile('socket_test.php');
            $this->addTestFile('encoding_test.php');
            $this->addTestFile('url_test.php');
            $this->addTestFile('http_test.php');
            $this->addTestFile('authentication_test.php');
            $this->addTestFile('user_agent_test.php');
            $this->addTestFile('parser_test.php');
            $this->addTestFile('tag_test.php');
            $this->addTestFile('form_test.php');
            $this->addTestFile('page_test.php');
            $this->addTestFile('frames_test.php');
            $this->addTestFile('browser_test.php');
            $this->addTestFile('web_tester_test.php');
            $this->addTestFile('shell_tester_test.php');
            $this->addTestFile('xml_test.php');
        }
    }
    
    // Uncomment and modify the following line if you are accessing
    // the net via a proxy server.
    //
    // SimpleTestOptions::useProxy('http://my-proxy', 'optional username', 'optional password');
        
    class AllTests extends GroupTest {
        function AllTests() {
            $this->GroupTest('All tests for SimpleTest ' . SimpleTestOptions::getVersion());
            $this->addTestCase(new UnitTests());
            $this->addTestFile(dirname(__FILE__) . '/shell_test.php');
            $this->addTestFile(dirname(__FILE__) . '/live_test.php');
            $this->addTestFile(dirname(__FILE__) . '/acceptance_test.php');
        }
    }
?>