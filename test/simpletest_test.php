<?php
    // $Id$
    require_once(dirname(__FILE__) . '/../simpletest.php');

    SimpleTest::ignore('ShouldNeverBeRunEither');

    class ShouldNeverBeRun extends UnitTestCase {
        function testWithNoChanceOfSuccess() {
            $this->fail('Should be ignored');
        }
    }

    class ShouldNeverBeRunEither extends ShouldNeverBeRun { }
    
    class TestOfStackTrace extends UnitTestCase {
        
        function testCanFindAssertInTrace() {
            $trace = new SimpleStackTrace(array('assert'));
            $this->assertEqual(
                    $trace->traceMethod(array(array(
                            'file' => '/my_test.php',
                            'line' => 24,
                            'function' => 'assertSomething'))),
                    ' at [/my_test.php line 24]');
        }
    }
?>