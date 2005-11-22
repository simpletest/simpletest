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
?>