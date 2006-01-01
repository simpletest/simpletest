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
?>