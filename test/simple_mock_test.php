<?php
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'simple_mock.php');
    require_once(SIMPLE_TEST . 'simple_unit.php');
    require_once(SIMPLE_TEST . 'simple_html_test.php');

    class TestOfParameterList extends UnitTestCase {
        function TestOfParameterList() {
            $this->UnitTestCase();
        }
        function testEmptyMatch() {
            $list = new ParameterList(array());
            $this->assertTrue($list->isMatch(array()));
            $this->assertFalse($list->isMatch(array(33)));
        }
        function testSingleMatch() {
            $list = new ParameterList(array(0));
            $this->assertFalse($list->isMatch(array(1)));
            $this->assertTrue($list->isMatch(array(0)));
        }
        function testAnyMatch() {
            $list = new ParameterList("");
            $this->assertTrue($list->isMatch(array()));
            $this->assertTrue($list->isMatch(array(1, 2)));
        }
        function testMissingParameter() {
            $list = new ParameterList(array(0));
            $this->assertFalse($list->isMatch(array()));
        }
        function testNullParameter() {
            $list = new ParameterList(array(null));
            $this->assertTrue($list->isMatch(array(null)));
            $this->assertFalse($list->isMatch(array()));
        }
        function testWildcardParameter() {
            $list = new ParameterList(array("wild"), "wild");
            $this->assertFalse($list->isMatch(array()), "Empty");
            $this->assertTrue($list->isMatch(array(null)), "Null");
            $this->assertTrue($list->isMatch(array(13)), "Integer");
        }
        function testIdentityOnly() {
            $list = new ParameterList(array("0"));
            $this->assertFalse($list->isMatch(array(0)));
            $this->assertTrue($list->isMatch(array("0")));
        }
        function testLongList() {
            $list = new ParameterList(array("0", 0, "wild", false), "wild");
            $this->assertTrue($list->isMatch(array("0", 0, 37, false)));
            $this->assertFalse($list->isMatch(array("0", 0, 37, true)));
            $this->assertFalse($list->isMatch(array("0", 0, 37)));
        }
    }
    
    class TestOfCallMap extends UnitTestCase {
        function TestOfCallMap() {
            $this->UnitTestCase();
        }
        function testEmpty() {
            $map = new CallMap("wild");
            $this->assertNull($map->findFirstMatch("any", array()));
        }
        function testExactValue() {
            $map = new CallMap("wild");
            $map->addValue("aMethod", array(0), "Fred");
            $this->assertEqual($map->findFirstMatch("aMethod", array(0)), "Fred");
        }
        function testExactReference() {
            $map = new CallMap("wild");
            $ref = "Fred";
            $map->addReference("aMethod", array(0), &$ref);
            $this->assertEqual($map->findFirstMatch("aMethod", array(0)), "Fred");
            $this->assertReference($map->findFirstMatch("aMethod", array(0)), $ref);
        }
        function testWildcard() {
            $map = new CallMap("wild");
            $map->addValue("aMethod", array("wild", 1, 3), "Fred");
            $this->assertEqual($map->findFirstMatch("aMethod", array(2, 1, 3)), "Fred");
        }
        function testAllWildcard() {
            $map = new CallMap("wild");
            $map->addValue("aMethod", "", "Fred");
            $this->assertEqual($map->findFirstMatch("aMethod", array(2, 1, 3)), "Fred");
        }
        function testOrdering() {
            $map = new CallMap("wild");
            $map->addValue("aMethod", array(1, 2), "1, 2");
            $map->addValue("aMethod", array(1, 3), "1, 3");
            $map->addValue("aMethod", array(1), "1");
            $map->addValue("aMethod", array(1, 4), "1, 4");
            $map->addValue("aMethod", array("wild"), "Any");
            $map->addValue("aMethod", array(2), "2");
            $map->addValue("aMethod", "", "Default");
            $map->addValue("aMethod", array(), "None");
            $this->assertEqual($map->findFirstMatch("aMethod", array(1, 2)), "1, 2");
            $this->assertEqual($map->findFirstMatch("aMethod", array(1, 3)), "1, 3");
            $this->assertEqual($map->findFirstMatch("aMethod", array(1, 4)), "1, 4");
            $this->assertEqual($map->findFirstMatch("aMethod", array(1)), "1");
            $this->assertEqual($map->findFirstMatch("aMethod", array(2)), "Any");
            $this->assertEqual($map->findFirstMatch("aMethod", array(3)), "Any");
            $this->assertEqual($map->findFirstMatch("aMethod", array()), "Default");
        }
    }
    
    class Dummy {
        function Dummy() {
        }
        function aMethod() {
        }
    }
    Mock::generate("Dummy");
    Mock::generate("Dummy", "AnotherMockDummy");
    
    class SpecialSimpleMock extends SimpleMock {
        function SpecialSimpleMock(&$test, $wildcard) {
            $this->SimpleMock($test, $wildcard);
        }
    }
    Mock::setMockBaseClass("SpecialSimpleMock");
    Mock::generate("Dummy", "SpecialMockDummy");
    Mock::setMockBaseClass("SimpleMock");
    
    class TestOfCodeGeneration extends UnitTestCase {
        function TestOfCodeGeneration() {
            $this->UnitTestCase();
        }
        function testCloning() {
            $mock = &new MockDummy($this);
            $this->assertTrue(method_exists($mock, "aMethod"));
            $this->assertNull($mock->aMethod());
        }
        function testCloningWithChosenClassName() {
            $mock = &new AnotherMockDummy($this);
            $this->assertTrue(method_exists($mock, "aMethod"));
        }
        function testCloningWithDifferentBaseClass() {
            $mock = &new SpecialMockDummy($this);
            $this->assertTrue(is_a($mock, "SpecialSimpleMock"));
            $this->assertTrue(method_exists($mock, "aMethod"));
        }
    }
    
    class TestOfMockReturns extends UnitTestCase {
        function TestOfMockReturns() {
            $this->UnitTestCase();
        }
        function testDefaultReturn() {
            $mock = &new MockDummy($this);
            $mock->setReturnValue("aMethod", "aaa");
            $this->assertIdentical($mock->aMethod(), "aaa");
            $this->assertIdentical($mock->aMethod(), "aaa");
        }
        function testParameteredReturn() {
            $mock = &new MockDummy($this);
            $mock->setReturnValue("aMethod", "aaa", array(1, 2, 3));
            $this->assertNull($mock->aMethod());
            $this->assertIdentical($mock->aMethod(1, 2, 3), "aaa");
        }
        function testReferenceReturned() {
            $mock = &new MockDummy($this);
            $object = new Dummy();
            $mock->setReturnReference("aMethod", $object, array(1, 2, 3));
            $this->assertReference($mock->aMethod(1, 2, 3), $object);
        }
        function testWildcardReturn() {
            $mock = &new MockDummy($this, "wild");
            $mock->setReturnValue("aMethod", "aaa", array(1, "wild", 3));
            $this->assertIdentical($mock->aMethod(1, "something", 3), "aaa");
            $this->assertIdentical($mock->aMethod(1, "anything", 3), "aaa");
        }
        function testAllWildcardReturn() {
            $mock = &new MockDummy($this, "wild");
            $mock->setReturnValue("aMethod", "aaa");
            $this->assertIdentical($mock->aMethod(1, 2, 3), "aaa");
            $this->assertIdentical($mock->aMethod(), "aaa");
        }
        function testCallCount() {
            $mock = &new MockDummy($this);
            $this->assertEqual($mock->getCallCount("aMethod"), 0);
            $mock->aMethod();
            $this->assertEqual($mock->getCallCount("aMethod"), 1);
            $mock->aMethod();
            $this->assertEqual($mock->getCallCount("aMethod"), 2);
        }
        function testReturnSequence() {
            $mock = &new MockDummy($this);
            $mock->setReturnValueSequence(0, "aMethod", "aaa");
            $mock->setReturnValueSequence(1, "aMethod", "bbb");
            $mock->setReturnValueSequence(3, "aMethod", "ddd");
            $this->assertIdentical($mock->aMethod(), "aaa");
            $this->assertIdentical($mock->aMethod(), "bbb");
            $this->assertNull($mock->aMethod());
            $this->assertIdentical($mock->aMethod(), "ddd");
        }
        function testReturnReferenceSequence() {
            $mock = &new MockDummy($this);
            $object = new Dummy();
            $mock->setReturnReferenceSequence(1, "aMethod", $object);
            $this->assertNull($mock->aMethod());
            $this->assertReference($mock->aMethod(), $object);
            $this->assertNull($mock->aMethod());
        }
        function testComplicatedReturnSequence() {
            $mock = &new MockDummy($this, "wild");
            $object = new Dummy();
            $mock->setReturnValueSequence(1, "aMethod", "aaa", array("a"));
            $mock->setReturnValueSequence(1, "aMethod", "bbb");
            $mock->setReturnReferenceSequence(2, "aMethod", $object, array("wild", 2));
            $mock->setReturnValueSequence(2, "aMethod", "value", array("wild", 3));
            $mock->setReturnValue("aMethod", 3, array(3));
            $this->assertNull($mock->aMethod());
            $this->assertEqual($mock->aMethod("a"), "aaa");
            $this->assertReference($mock->aMethod(1, 2), $object);
            $this->assertEqual($mock->aMethod(3), 3);
            $this->assertNull($mock->aMethod());
        }
    }
    
    Mock::generate("TestCase");
    
    class TestOfMockTally extends UnitTestCase {
        function TestOfMockTally() {
            $this->UnitTestCase();
        }
        function testZeroCallCount() {
            $mock = &new MockDummy($this);
            $mock->setExpectedCallCount("aMethod", 0);
            $this->assertTrue($mock->tally(), "Tally");
        }
        function testClearHistory() {
            $mock = &new MockDummy($this);
            $mock->setExpectedCallCount("aMethod", 0);
            $mock->aMethod();
            $this->assertEqual($mock->getCallCount("aMethod"), 1);
            $mock->clearHistory();
            $this->assertTrue($mock->tally(), "Tally");
        }
        function testExpectedCallCount() {
            $mock = &new MockDummy($this);
            $mock->setExpectedCallCount("aMethod", 2);
            $mock->aMethod();
            $mock->aMethod();
            $this->assertTrue($mock->tally(), "Tally");
        }
        function testFailedCallCount() {
            $mock = &new MockDummy(new MockTestCase($this));
            $mock->setExpectedCallCount("aMethod", 2);
            $this->assertFalse($mock->tally());
            $mock->aMethod();
            $this->assertFalse($mock->tally(), "Tally");
        }
    }
    
    class TestOfMockExpectations extends UnitTestCase {
        function TestOfMockExpectations() {
            $this->UnitTestCase();
        }
        function testMaxCalls() {
            $test = &new MockTestCase($this);
            $test->setExpectedCallCount("assertTrue", 1);
            $mock = &new MockDummy($test);
            $mock->setMaximumCallCount("aMethod", 2);
            $mock->aMethod();
            $mock->aMethod();
            $mock->aMethod();
            $test->tally();
        }
        function testZeroArguments() {
            $mock = &new MockDummy($this);
            $mock->setExpectedArguments("aMethod", array());
            $mock->aMethod();
        }
        function testExpectedArguments() {
            $mock = &new MockDummy($this);
            $mock->setExpectedArguments("aMethod", array(1, 2, 3));
            $mock->aMethod(1, 2, 3);
        }
        function testFailedArguments() {
            $test = &new MockTestCase($this, "*");
            $test->setExpectedArguments("assertTrue", array(false, "*"));
            $mock = &new MockDummy($test);
            $mock->setExpectedArguments("aMethod", array("this"));
            $mock->aMethod("that");
        }
        function testWildcardArguments() {
            $mock = &new MockDummy($this, "wild");
            $mock->setExpectedArguments("aMethod", array("wild", 123, "wild"));
            $mock->aMethod(100, 123, 101);
        }
        function testSpecificSequence() {
            $mock = &new MockDummy($this);
            $mock->setExpectedArgumentsSequence(1, "aMethod", array(1, 2, 3));
            $mock->setExpectedArgumentsSequence(2, "aMethod", array("Hello"));
            $mock->aMethod();
            $mock->aMethod(1, 2, 3);
            $mock->aMethod("Hello");
            $mock->aMethod();
        }
        function testFailedSequence() {
            $test = &new MockTestCase($this);
            $test->setExpectedArguments("assertTrue", array(false, "*"));
            $mock = &new MockDummy($test);
            $mock->setExpectedArgumentsSequence(0, "aMethod", array(1, 2, 3));
            $mock->setExpectedArgumentsSequence(1, "aMethod", array("Hello"));
            $mock->aMethod(1, 2);
            $mock->aMethod("Goodbye");
        }
    }
    
    $test = new SimpleTest("Mock components test");
    $test->addTestCase(new TestOfParameterList());
    $test->addTestCase(new TestOfCallMap());
    $test->addTestCase(new TestOfCodeGeneration());
    $test->addTestCase(new TestOfMockReturns());
    $test->addTestCase(new TestOfMockTally());
    $test->addTestCase(new TestOfMockExpectations());
    $test->attachObserver(new TestHtmlDisplay());
    $test->run();
?>