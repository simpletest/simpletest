<?php
    // $Id$
    
    require_once(dirname(__FILE__) . '/../web_tester.php');
    
    class TestOfFieldExpectation extends UnitTestCase {
        function TestOfFieldExpectation() {
            $this->UnitTestCase();
        }
        function testString() {
            $expectation = new FieldExpectation('a');
            $this->assertTrue($expectation->test('a'));
            $this->assertTrue($expectation->test(array('a')));
            $this->assertFalse($expectation->test('A'));
        }
        function testNonString() {
            $expectation = new FieldExpectation('a');
            $this->assertFalse($expectation->test(null));
        }
        function testUnsetField() {
            $expectation = new FieldExpectation(false);
            $this->assertTrue($expectation->test(false));
        }
        function testMultipleValues() {
            $expectation = new FieldExpectation(array('a', 'b'));
            $this->assertTrue($expectation->test(array('a', 'b')));
            $this->assertTrue($expectation->test(array('b', 'a')));
            $this->assertFalse($expectation->test(array('a', 'a')));            
            $this->assertFalse($expectation->test('a'));            
        }
        function testSingleItem() {
            $expectation = new FieldExpectation(array('a'));
            $this->assertTrue($expectation->test(array('a')));
            $this->assertTrue($expectation->test('a'));
        }
    }
?>