<?php
    // $Id$
    require_once(dirname(__FILE__) . '/../simpletest.php');
        
    SimpleTest::ignore('ShouldNeverBeRun');
    class ShouldNeverBeRun extends UnitTestCase {
        function testWithNoChanceOfSuccess() {
            $this->fail('Should be ignored');
        }
    }
?>