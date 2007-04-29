<?php
// $Id$
    
require_once(dirname(__FILE__) . '/../../autorun.php');

class SampleTestForRecorder extends UnitTestCase {
    function testTrueIsTrue() {
        $this->assertTrue(true);
    }

    function testFalseIsTrue() {
        $this->assertFalse(true);
    }
}
?>