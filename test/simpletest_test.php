<?php
    // $Id$
    require_once(dirname(__FILE__) . '/../simpletest.php');


    class ShouldNeverBeRun extends UnitTestCase {
        function testWithNoChanceOfSuccess() {
            $this->fail('Should be ignored');
        }
    }

    class ShouldNeverBeRunEither extends ShouldNeverBeRun { }
    SimpleTest::ignore('ShouldNeverBeRunEither');

    if (version_compare(phpversion(), '5') >= 0) {
        abstract class ShouldNeverRunAnAbstract extends UnitTestCase {
            function testWithNoChanceOfSuccess() {
                $this->fail('Should be ignored');
            }
        }
    }
?>