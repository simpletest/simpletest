<?php
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'simple_test.php');
    
    // Temprorary test function until we build a test tool.
    //
    function assertion($result, $message) {
        $result_string = ($result ? "pass" : "fail");
        print "<span class=\"$result_string\">$result_string</span>: $message<br />\n";
    }
    
    // Testing version of a test display.
    //
    class TestOfTestReporter extends TestReporter {
        var $_count;
        
        function TestOfTestReporter($expected_names, $expected_results, $expected_messages) {
            $this->TestReporter();
            $this->_expected_names = $expected_names;
            $this->_expected_results = $expected_results;
            $this->_expected_messages = $expected_messages;
            $this->_count = 0;
        }
        function paintStart($test_name) {
            $this->_testExpected($test_name);
        }
        function paintEnd($test_name) {
            $this->_testExpected($test_name);
        }
        function paintPass($message) {
            assertion($this->_expected_results[$this->_count], "Expecting a pass");
            assertion(
                    $message == $this->_expected_messages[$this->_count],
                    $this->_count . ": Expecting [" . $this->_expected_messages[$this->_count] . "] got [$message]");
            $this->_count++;
        }
        function paintFail($message) {
            assertion(!$this->_expected_results[$this->_count], "Expecting a fail");
            assertion(
                    $message == $this->_expected_messages[$this->_count],
                    $this->_count . ": Expecting [" . $this->_expected_messages[$this->_count] . "] got [$message]");
            $this->_count++;
        }
        function _testExpected($test_name) {
            assertion(
                    strtolower($test_name) == strtolower($this->_expected_names[$this->_count]),
                    $this->_count . ": Expected [" . $this->_expected_names[$this->_count] . "] got [$test_name]");
            $this->_count++;
        }
    }
?>
<html>
    <head>
        <title>Test of basic test classes used in SimpleTest</title>
        <style type="text/css">
            .pass { color: green; }
            .fail { color: red; }
            body { padding: 1em; }
        </style>
    </head>
    <body>
        <h1>Simple test script for simple test</h1>
        <ol>
            <?php
                // Abstract class test.
                //
                $runnable = new RunnableTest("Me");
                assertion(
                        "Me" == $runnable->getLabel(),
                        "Expected [Me] got [" . $runnable->getLabel() . "]");
                
                $test_case = new TestCase();
                $test_case->attachObserver(new TestOfTestReporter(
                        array(null),
                        array(true),
                        array("stuff")));
                $test_case->assertTrue(true, "stuff");
                
                $test_case = new TestCase();
                $test_case->attachObserver(new TestOfTestReporter(
                        array(null),
                        array(false),
                        array("more stuff")));
                $test_case->assertTrue(false, "more stuff");
                
                // Testing group test with reporter.
                //
                $test = new GroupTest("Me again");
                assertion("Me again" == $test->getLabel(), "Expected [Me again] got [" . $test->getLabel() . "]");
                $test->attachObserver(new TestOfTestReporter(
                        array("Me again", "Me again"),
                        array(null, null),
                        array(null, null)));
                $test->run();
                
                // Build a test case.
                //
                class MyTestCase extends TestCase {
                    var $test_variable;
                    
                    function MyTestCase() {
                        $this->TestCase();
                    }
                    function test() {
                        $this->assertTrue(true, "True");
                    }
                    function test2() {
                        $this->assertTrue($this->test_variable == 13, "True");
                    }
                    function setUp() {
                        $this->test_variable = 13;
                    }
                    function tearDown() {
                        $this->test_variable = 0;
                    }
                }
                $test = new GroupTest("Me");
                $test_case = new MyTestCase();
                $test->addTestCase($test_case);
                $test->attachObserver(new TestOfTestReporter(
                        array(
                                "Me",
                                "mytestcase",
                                "test",
                                null,
                                "test",
                                "test2",
                                null,
                                "test2",
                                "mytestcase",
                                "Me"),
                        array(null, null, null, true, null, null, true, null, null, null),
                        array(null, null, null, "True", null, null, "True", null, null, null)));
                $test->run();
                assertion(0 == $test_case->test_variable, "Expected [0] got [" . $test_case->test_variable . "]");
                
                // Collect test cases from a script.
                //
                $test = new GroupTest("Script");
                $test->addTestFile("support/dummy_test_1.php");
                $test->addTestFile("support/dummy_test_2.php");
                $test->attachObserver(new TestOfTestReporter(
                        array(
                                "Script",
                                "support/dummy_test_1.php",
                                "DummyTestOneA",
                                "testOneA",
                                null,
                                "testOneA",
                                "DummyTestOneA",
                                "DummyTestOneB",
                                "testOneB",
                                null,
                                "testOneB",
                                "DummyTestOneB",
                                "support/dummy_test_1.php",
                                "support/dummy_test_2.php",
                                "DummyTestTwo",
                                "testTwo",
                                null,
                                "testTwo",
                                "DummyTestTwo",
                                "support/dummy_test_2.php",
                                "Script"),
                        array(null, null, null, null, true,
                                null, null, null, null, true,
                                null, null, null, null, null, null, true,
                                null, null, null, null),
                        array(null, null, null, null,"True",
                                null, null, null, null,"True",
                                null, null, null, null, null, null,"True",
                                null, null, null, null)));
                $test->run();
            ?>
        </ol>
        <div><em>Should have all passed.</em></div>
    </body>
</html>
