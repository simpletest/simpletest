<?php
    // $Id$
    
    class SampleTestForArrayReporter extends UnitTestCase {
        
        function testTrueIsTrue() {
            $this->assertTrue(true);
        }

        function testFalseIsTrue() {
            $this->assertFalse(true);
        }

    }
?>