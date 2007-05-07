<?php
    // $Id$
    require_once(dirname(__FILE__) . '/../unit_tester.php');
    require_once(dirname(__FILE__) . '/../shell_tester.php');
    require_once(dirname(__FILE__) . '/../mock_objects.php');
    require_once(dirname(__FILE__) . '/../web_tester.php');
    require_once(dirname(__FILE__) . '/../extensions/pear_test_case.php');
    require_once(dirname(__FILE__) . '/../extensions/phpunit_test_case.php');

    class UnitTests extends TestSuite {
        function UnitTests() {
            $this->TestSuite('Unit tests');
            $path = dirname(__FILE__);
            $this->load($path . '/errors_test.php');
            if (version_compare(phpversion(), '5') >= 0) {
                $this->load($path . '/exceptions_test.php');
            }
            $this->load($path . '/compatibility_test.php');
            $this->load($path . '/simpletest_test.php');
            $this->load($path . '/dumper_test.php');
            $this->load($path . '/expectation_test.php');
            $this->load($path . '/unit_tester_test.php');
            if (version_compare(phpversion(), '5', '>=')) {
                $this->load($path . '/reflection_php5_test.php');
            } else {
                $this->load($path . '/reflection_php4_test.php');
            }
            $this->load($path . '/mock_objects_test.php');
            if (version_compare(phpversion(), '5', '>=')) {
                $this->load($path . '/interfaces_test.php');
            }
            $this->load($path . '/collector_test.php');
            $this->load($path . '/adapter_test.php');
            $this->load($path . '/socket_test.php');
            $this->load($path . '/encoding_test.php');
            $this->load($path . '/url_test.php');
            $this->load($path . '/cookies_test.php');
            $this->load($path . '/http_test.php');
            $this->load($path . '/authentication_test.php');
            $this->load($path . '/user_agent_test.php');
            $this->load($path . '/parser_test.php');
            $this->load($path . '/tag_test.php');
            $this->load($path . '/form_test.php');
            $this->load($path . '/page_test.php');
            $this->load($path . '/frames_test.php');
            $this->load($path . '/browser_test.php');
            $this->load($path . '/web_tester_test.php');
            $this->load($path . '/shell_tester_test.php');
            $this->load($path . '/xml_test.php');
        }
    }

    // Uncomment and modify the following line if you are accessing
    // the net via a proxy server.
    //
    // SimpleTest::useProxy('http://my-proxy', 'optional username', 'optional password');

    class AllTests extends TestSuite {
        function AllTests() {
            $this->TestSuite('All tests for SimpleTest ' . SimpleTest::getVersion());
            $this->add(new UnitTests());
            $this->load(dirname(__FILE__) . '/shell_test.php');
            $this->load(dirname(__FILE__) . '/live_test.php');
            $this->load(dirname(__FILE__) . '/acceptance_test.php');
        }
    }
?>