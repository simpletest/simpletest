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
    
    class TestOfHeaderExpectations extends UnitTestCase {
        function TestOfHeaderExpectations() {
            $this->UnitTestCase();
        }
        
        function testExpectingOnlyTheHeaderName() {
            $expectation = new HttpHeaderExpectation('a');
            $this->assertIdentical($expectation->test(false), false);
            $this->assertIdentical($expectation->test('a: A'), true);
            $this->assertIdentical($expectation->test('A: A'), true);
            $this->assertIdentical($expectation->test('a: B'), true);
            $this->assertIdentical($expectation->test(' a : A '), true);
        }
        
        function testHeaderValueAsWell() {
            $expectation = new HttpHeaderExpectation('a', 'A');
            $this->assertIdentical($expectation->test(false), false);
            $this->assertIdentical($expectation->test('a: A'), true);
            $this->assertIdentical($expectation->test('A: A'), true);
            $this->assertIdentical($expectation->test('A: a'), false);
            $this->assertIdentical($expectation->test('a: B'), false);
            $this->assertIdentical($expectation->test(' a : A '), true);
            $this->assertIdentical($expectation->test(' a : AB '), false);
        }
        
        function testMultilineSearch() {
            $expectation = new HttpHeaderExpectation('a', 'A');
            $this->assertIdentical($expectation->test("aa: AA\r\nb: B\r\nc: C"), false);
            $this->assertIdentical($expectation->test("aa: AA\r\na: A\r\nb: B"), true);
        }
    }
?>