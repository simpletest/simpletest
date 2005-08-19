<?php
    // $Id$
    require_once(dirname(__FILE__) . '/../unit_tester.php');
    require_once(dirname(__FILE__) . '/../shell_tester.php');
    require_once(dirname(__FILE__) . '/../mock_objects.php');
    require_once(dirname(__FILE__) . '/../web_tester.php');
    require_once(dirname(__FILE__) . '/../extensions/pear_test_case.php');
    require_once(dirname(__FILE__) . '/../extensions/phpunit_test_case.php');
    
    class UnitTests extends GroupTest {
        function UnitTests() {
            $this->GroupTest('Unit tests');
            $test_path = dirname(__FILE__);
            $this->addTestFile($test_path . '/errors_test.php');
            $this->addTestFile($test_path . '/compatibility_test.php');
            $this->addTestFile($test_path . '/simpletest_test.php');
            $this->addTestFile($test_path . '/dumper_test.php');
            $this->addTestFile($test_path . '/expectation_test.php');
            $this->addTestFile($test_path . '/unit_tester_test.php');
            $this->addTestFile($test_path . '/collector_test.php');
            if (version_compare(phpversion(), '5') >= 0) {
                $this->addTestFile($test_path . '/reflection_php5_test.php');
            } else {
                $this->addTestFile($test_path . '/reflection_php4_test.php');
            }
            $this->addTestFile($test_path . '/mock_objects_test.php');
            $this->addTestFile($test_path . '/adapter_test.php');
            $this->addTestFile($test_path . '/socket_test.php');
            $this->addTestFile($test_path . '/encoding_test.php');
            $this->addTestFile($test_path . '/url_test.php');
            $this->addTestFile($test_path . '/http_test.php');
            $this->addTestFile($test_path . '/authentication_test.php');
            $this->addTestFile($test_path . '/user_agent_test.php');
            $this->addTestFile($test_path . '/parser_test.php');
            $this->addTestFile($test_path . '/tag_test.php');
            $this->addTestFile($test_path . '/form_test.php');
            $this->addTestFile($test_path . '/page_test.php');
            $this->addTestFile($test_path . '/frames_test.php');
            $this->addTestFile($test_path . '/browser_test.php');
            $this->addTestFile($test_path . '/web_tester_test.php');
            $this->addTestFile($test_path . '/shell_tester_test.php');
            $this->addTestFile($test_path . '/xml_test.php');
        }
    }
    
    // Uncomment and modify the following line if you are accessing
    // the net via a proxy server.
    //
    // SimpleTest::useProxy('http://my-proxy', 'optional username', 'optional password');
        
    class AllTests extends GroupTest {
        function AllTests() {
            $this->GroupTest('All tests for SimpleTest ' . SimpleTest::getVersion());
            $this->addTestCase(new UnitTests());
            $this->addTestFile(dirname(__FILE__) . '/shell_test.php');
            $this->addTestFile(dirname(__FILE__) . '/live_test.php');
            $this->addTestFile(dirname(__FILE__) . '/acceptance_test.php');
            $this->addTestFile(dirname(__FILE__) . '/real_sites_test.php');
        }
    }
?>