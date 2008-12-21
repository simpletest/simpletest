<?php
// $Id: test.php 1500 2007-04-29 14:33:31Z pp11 $
require_once dirname(__FILE__) . '/../../../autorun.php';
require_once(dirname(__FILE__) . '/../../recorder.php');

class TestOfRecorder extends UnitTestCase {
    
    function testContentOfRecorderWithOnePassAndOneFailure() {
        $test = new TestSuite();
        $test->addFile(dirname(__FILE__) . '/sample.php');
        $recorder = new Recorder();
        $test->run($recorder);
        $this->assertEqual(count($recorder->results), 2);
        
        $d = '[\\\\\\/]'; // backslash or slash
        
        $this->assertEqual(count($recorder->results[0]), 4);
        $this->assertPattern("/".substr(time(), 9)."/", $recorder->results[0]['time']);
        $this->assertEqual($recorder->results[0]['status'], "Passed");
        $this->assertPattern("/sample\.php->SampleTestForRecorder->testTrueIsTrue/i", $recorder->results[0]['test']);
        $this->assertPattern("/ at \[.*recorder{$d}test{$d}sample\.php line 7\]/", $recorder->results[0]['message']);

        $this->assertEqual(count($recorder->results[1]), 4);
        $this->assertPattern("/".substr(time(), 9)."/", $recorder->results[1]['time']);
        $this->assertEqual($recorder->results[1]['status'], "Failed");
        $this->assertPattern("/sample\.php->SampleTestForRecorder->testFalseIsTrue/i", $recorder->results[1]['test']);
        $this->assertPattern("/Expected false, got \[Boolean: true\] at \[.*recorder{$d}test{$d}sample\.php line 11\]/", $recorder->results[1]['message']);
    }
}
?>