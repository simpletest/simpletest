<?php
    // $Id$
    
    require_once(dirname(__FILE__) . '/../web_tester.php');
    
    class TestOfFieldExpectation extends UnitTestCase {
        function TestOfFieldExpectation() {
            $this->UnitTestCase();
        }
        
        function testStringMatchingIsCaseSensitive() {
            $expectation = new FieldExpectation('a');
            $this->assertTrue($expectation->test('a'));
            $this->assertTrue($expectation->test(array('a')));
            $this->assertFalse($expectation->test('A'));
        }
        
        function testMatchesInteger() {
            $expectation = new FieldExpectation('1');
            $this->assertTrue($expectation->test('1'));
            $this->assertTrue($expectation->test(1));
            $this->assertTrue($expectation->test(array('1')));
            $this->assertTrue($expectation->test(array(1)));
        }
        
        function testNonStringFailsExpectation() {
            $expectation = new FieldExpectation('a');
            $this->assertFalse($expectation->test(null));
        }
        
        function testUnsetFieldCanBeTestedFor() {
            $expectation = new FieldExpectation(false);
            $this->assertTrue($expectation->test(false));
        }
        
        function testMultipleValuesCanBeInAnyOrder() {
            $expectation = new FieldExpectation(array('a', 'b'));
            $this->assertTrue($expectation->test(array('a', 'b')));
            $this->assertTrue($expectation->test(array('b', 'a')));
            $this->assertFalse($expectation->test(array('a', 'a')));            
            $this->assertFalse($expectation->test('a'));            
        }
        
        function testSingleItemCanBeArrayOrString() {
            $expectation = new FieldExpectation(array('a'));
            $this->assertTrue($expectation->test(array('a')));
            $this->assertTrue($expectation->test('a'));
        }
    }
    
    class TestOfHeaderAssertions extends UnitTestCase {
        function TestOfHeaderAssertions() {
            $this->UnitTestCase();
        }
        
        function testNoHeaderFoundInEmptyHeaderSet() {
            $expectation = new HttpHeaderExpectation('a');
            $this->assertIdentical($expectation->test(false), false);
        }
    }
?>