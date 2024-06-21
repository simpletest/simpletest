<?php declare(strict_types=1);

// NOTE:
// Some of these tests are designed to fail! Do not be alarmed.
//                         ----------------

//
// The proper tests start in all_tests.php
//
require_once __DIR__ . '/../src/unit_tester.php';

require_once __DIR__ . '/../src/shell_tester.php';

require_once __DIR__ . '/../src/mock_objects.php';

require_once __DIR__ . '/../src/reporter.php';

require_once __DIR__ . '/../src/xml.php';

class TestDisplayClass
{
    private $a;

    public function __construct($a)
    {
        $this->a = $a;
    }
}

class PassingUnitTestCaseOutput extends UnitTestCase
{
    public function testOfResults(): void
    {
        $this->pass('Pass');
    }

    public function testTrue(): void
    {
        $this->assertTrue(true);
    }

    public function testFalse(): void
    {
        $this->assertFalse(false);
    }

    public function testExpectation(): void
    {
        $expectation = new EqualExpectation(25, 'My expectation message: %s');
        $this->assert($expectation, 25, 'My assert message : %s');
    }

    public function testNull(): void
    {
        $this->assertNull(null, '%s -> Pass');
        $this->assertNotNull(false, '%s -> Pass');
    }

    public function testType(): void
    {
        $this->assertIsA('hello', 'string', '%s -> Pass');
        $this->assertIsA($this, 'PassingUnitTestCaseOutput', '%s -> Pass');
        $this->assertIsA($this, 'UnitTestCase', '%s -> Pass');
    }

    public function testTypeEquality(): void
    {
        $this->assertEqual('0', 0, '%s -> Pass');
    }

    public function testNullEquality(): void
    {
        $this->assertNotEqual(null, 1, '%s -> Pass');
        $this->assertNotEqual(1, null, '%s -> Pass');
    }

    public function testIntegerEquality(): void
    {
        $this->assertNotEqual(1, 2, '%s -> Pass');
    }

    public function testStringEquality(): void
    {
        $this->assertEqual('a', 'a', '%s -> Pass');
        $this->assertNotEqual('aa', 'ab', '%s -> Pass');
    }

    public function testHashEquality(): void
    {
        $this->assertEqual(['a' => 'A', 'b' => 'B'], ['b' => 'B', 'a' => 'A'], '%s -> Pass');
    }

    public function testWithin(): void
    {
        $this->assertWithinMargin(5, 5.4, 0.5, '%s -> Pass');
    }

    public function testOutside(): void
    {
        $this->assertOutsideMargin(5, 5.6, 0.5, '%s -> Pass');
    }

    public function testStringIdentity(): void
    {
        $a = 'fred';
        $b = $a;
        $this->assertIdentical($a, $b, '%s -> Pass');
    }

    public function testTypeIdentity(): void
    {
        $a = '0';
        $b = 0;
        $this->assertNotIdentical($a, $b, '%s -> Pass');
    }

    public function testNullIdentity(): void
    {
        $this->assertNotIdentical(null, 1, '%s -> Pass');
        $this->assertNotIdentical(1, null, '%s -> Pass');
    }

    public function testHashIdentity(): void
    {
    }

    public function testObjectEquality(): void
    {
        $this->assertEqual(new TestDisplayClass(4), new TestDisplayClass(4), '%s -> Pass');
        $this->assertNotEqual(new TestDisplayClass(4), new TestDisplayClass(5), '%s -> Pass');
    }

    public function testObjectIndentity(): void
    {
        $this->assertIdentical(new TestDisplayClass(false), new TestDisplayClass(false), '%s -> Pass');
        $this->assertNotIdentical(new TestDisplayClass(false), new TestDisplayClass(0), '%s -> Pass');
    }

    public function testReference(): void
    {
        $a = 'fred';
        $b = &$a;
        $this->assertReference($a, $b, '%s -> Pass');
    }

    /*public function testCloneOnDifferentObjects()
    {
        // test for copy object; both objects represent the same memory address
        $object1 = new stdClass;
        $object1->name = 'Object 1';
        $object2 = $object1; // copy the object

        $this->assertSame($object1, $object2);
        $this->assertSame($object1->name, 'Object 1');
        $this->assertSame($object2->name, 'Object 1');

        // test for clone object; both objects are independent
        $object3 = clone $object1;
        // after clone they still have equal values
        $this->assertSame($object1->name, 'Object 1');
        $this->assertSame($object3->name, 'Object 1');
        // modify values
        $object1->name = 'Still Object 1';
        $object3->name = 'Object 3';
        // test for values
        $this->assertSame($object1->name, 'Still Object 1');
        $this->assertSame($object3->name, 'Object 3');
        // finally, test difference of cloned objects
        $this->assertClone($object1, $object2, '%s -> Pass');
    }*/

    public function testPatterns(): void
    {
        $this->assertPattern('/hello/i', 'Hello there', '%s -> Pass');
        $this->assertNoPattern('/hello/', 'Hello there', '%s -> Pass');
    }

    public function testLongStrings(): void
    {
        $text = '';

        for ($i = 0; $i < 10; $i++) {
            $text .= '0123456789';
        }
        $this->assertEqual($text, $text);
    }
}

class FailingUnitTestCaseOutput extends UnitTestCase
{
    public function testOfResults(): void
    {
        $this->fail('Fail');        // Fail.
    }

    public function testTrue(): void
    {
        $this->assertTrue(false);        // Fail.
    }

    public function testFalse(): void
    {
        $this->assertFalse(true);        // Fail.
    }

    public function testExpectation(): void
    {
        $expectation = new EqualExpectation(25, 'My expectation message: %s');
        $this->assert($expectation, 24, 'My assert message : %s');        // Fail.
    }

    public function testNull(): void
    {
        $this->assertNull(false, '%s -> Fail');        // Fail.
        $this->assertNotNull(null, '%s -> Fail');        // Fail.
    }

    public function testType(): void
    {
        $this->assertIsA(14, 'string', '%s -> Fail');        // Fail.
        $this->assertIsA(14, 'TestOfUnitTestCaseOutput', '%s -> Fail');        // Fail.
        $this->assertIsA($this, 'TestReporter', '%s -> Fail');        // Fail.
    }

    public function testTypeEquality(): void
    {
        $this->assertNotEqual('0', 0, '%s -> Fail');        // Fail.
    }

    public function testNullEquality(): void
    {
        $this->assertEqual(null, 1, '%s -> Fail');        // Fail.
        $this->assertEqual(1, null, '%s -> Fail');        // Fail.
    }

    public function testIntegerEquality(): void
    {
        $this->assertEqual(1, 2, '%s -> Fail');        // Fail.
    }

    public function testStringEquality(): void
    {
        $this->assertNotEqual('a', 'a', '%s -> Fail');    // Fail.
        $this->assertEqual('aa', 'ab', '%s -> Fail');        // Fail.
    }

    public function testHashEquality(): void
    {
        $this->assertEqual(['a' => 'A', 'b' => 'B'], ['b' => 'B', 'a' => 'Z'], '%s -> Fail');
    }

    public function testWithin(): void
    {
        $this->assertWithinMargin(5, 5.6, 0.5, '%s -> Fail');   // Fail.
    }

    public function testOutside(): void
    {
        $this->assertOutsideMargin(5, 5.4, 0.5, '%s -> Fail');   // Fail.
    }

    public function testStringIdentity(): void
    {
        $a = 'fred';
        $b = $a;
        $this->assertNotIdentical($a, $b, '%s -> Fail');       // Fail.
    }

    public function testTypeIdentity(): void
    {
        $a = '0';
        $b = 0;
        $this->assertIdentical($a, $b, '%s -> Fail');        // Fail.
    }

    public function testNullIdentity(): void
    {
        $this->assertIdentical(null, 1, '%s -> Fail');        // Fail.
        $this->assertIdentical(1, null, '%s -> Fail');        // Fail.
    }

    public function testHashIdentity(): void
    {
        $this->assertIdentical(['a' => 'A', 'b' => 'B'], ['b' => 'B', 'a' => 'A'], '%s -> fail');        // Fail.
    }

    public function testObjectEquality(): void
    {
        $this->assertNotEqual(new TestDisplayClass(4), new TestDisplayClass(4), '%s -> Fail');    // Fail.
        $this->assertEqual(new TestDisplayClass(4), new TestDisplayClass(5), '%s -> Fail');        // Fail.
    }

    public function testObjectIndentity(): void
    {
        $this->assertNotIdentical(new TestDisplayClass(false), new TestDisplayClass(false), '%s -> Fail');    // Fail.
        $this->assertIdentical(new TestDisplayClass(false), new TestDisplayClass(0), '%s -> Fail');        // Fail.
    }

    public function testReference(): void
    {
        $a = 'fred';
        $b = &$a;
        $this->assertClone($a, $b, '%s -> Fail');        // Fail.
    }

    public function testCloneOnDifferentObjects(): void
    {
        $a = 'fred';
        $b = $a;
        $c = 'Hello';
        $this->assertClone($a, $c, '%s -> Fail');        // Fail.
    }

    public function testPatterns(): void
    {
        $this->assertPattern('/hello/', 'Hello there', '%s -> Fail');            // Fail.
        $this->assertNoPattern('/hello/i', 'Hello there', '%s -> Fail');      // Fail.
    }

    public function testLongStrings(): void
    {
        $text = '';

        for ($i = 0; $i < 10; $i++) {
            $text .= '0123456789';
        }
        $this->assertEqual($text . $text, $text . 'a' . $text);        // Fail.
    }
}

class Dummy
{
    public function __construct()
    {
    }

    public function a(): void
    {
    }
}
Mock::generate('Dummy');

class TestOfMockObjectsOutput extends UnitTestCase
{
    public function testCallCounts(): void
    {
        $dummy = new MockDummy;
        $dummy->expectCallCount('a', 1, 'My message: %s');
        $dummy->a();
        $dummy->a();
    }

    public function testMinimumCallCounts(): void
    {
        $dummy = new MockDummy;
        $dummy->expectMinimumCallCount('a', 2, 'My message: %s');
        $dummy->a();
        $dummy->a();
    }

    public function testEmptyMatching(): void
    {
        $dummy = new MockDummy;
        $dummy->expect('a', []);
        $dummy->a();
        $dummy->a(null);        // Fail.
    }

    public function testEmptyMatchingWithCustomMessage(): void
    {
        $dummy = new MockDummy;
        $dummy->expect('a', [], 'My expectation message: %s');
        $dummy->a();
        $dummy->a(null);        // Fail.
    }

    public function testNullMatching(): void
    {
        $dummy = new MockDummy;
        $dummy->expect('a', [null]);
        $dummy->a(null);
        $dummy->a();        // Fail.
    }

    public function testBooleanMatching(): void
    {
        $dummy = new MockDummy;
        $dummy->expect('a', [true, false]);
        $dummy->a(true, false);
        $dummy->a(true, true);        // Fail.
    }

    public function testIntegerMatching(): void
    {
        $dummy = new MockDummy;
        $dummy->expect('a', [32, 33]);
        $dummy->a(32, 33);
        $dummy->a(32, 34);        // Fail.
    }

    public function testFloatMatching(): void
    {
        $dummy = new MockDummy;
        $dummy->expect('a', [3.2, 3.3]);
        $dummy->a(3.2, 3.3);
        $dummy->a(3.2, 3.4);        // Fail.
    }

    public function testStringMatching(): void
    {
        $dummy = new MockDummy;
        $dummy->expect('a', ['32', '33']);
        $dummy->a('32', '33');
        $dummy->a('32', '34');        // Fail.
    }

    public function testEmptyMatchingWithCustomExpectationMessage(): void
    {
        $dummy = new MockDummy;
        $dummy->expect(
            'a',
            [new EqualExpectation('A', 'My part expectation message: %s')],
            'My expectation message: %s',
        );
        $dummy->a('A');
        $dummy->a('B');        // Fail.
    }

    public function testArrayMatching(): void
    {
        $dummy = new MockDummy;
        $dummy->expect('a', [[32], [33]]);
        $dummy->a([32], [33]);
        $dummy->a([32], ['33']);        // Fail.
    }

    public function testObjectMatching(): void
    {
        $a     = new Dummy;
        $a->a  = 'a';
        $b     = new Dummy;
        $b->b  = 'b';
        $dummy = new MockDummy;
        $dummy->expect('a', [$a, $b]);
        $dummy->a($a, $b);
        $dummy->a($a, $a);        // Fail.
    }

    public function testBigList(): void
    {
        $dummy = new MockDummy;
        $dummy->expect('a', [false, 0, 1, 1.0]);
        $dummy->a(false, 0, 1, 1.0);
        $dummy->a(true, false, 2, 2.0);        // Fail.
    }
}

class TestOfPastBugs extends UnitTestCase
{
    public function testMixedTypes(): void
    {
        $this->assertEqual([], null, '%s -> Pass');
        $this->assertIdentical([], null, '%s -> Fail');    // Fail.
    }

    public function testMockWildcards(): void
    {
        $dummy = new MockDummy;
        $dummy->expect('a', ['*', [33]]);
        $dummy->a([32], [33]);
        $dummy->a([32], ['33']);        // Fail.
    }
}

class TestOfVisualShell extends ShellTestCase
{
    public function testDump(): void
    {
        $this->execute('ls');
        $this->dumpOutput();
        $this->execute('dir');
        $this->dumpOutput();
    }

    public function testDumpOfList(): void
    {
        $this->execute('ls');
        $this->dump($this->getOutputAsList());
    }
}

class PassesAsWellReporter extends HtmlReporter
{
    public function paintPass($message): void
    {
        parent::paintPass($message);
        print '<span class="pass">Pass</span>: ';
        $breadcrumb = $this->getTestList();
        \array_shift($breadcrumb);
        print \implode(' -&gt; ', $breadcrumb);
        print ' -&gt; ' . \htmlentities($message) . "<br />\n";
    }

    public function paintSignal($type, $payload): void
    {
        print "<span class=\"fail\">{$type}</span>: ";
        $breadcrumb = $this->getTestList();
        \array_shift($breadcrumb);
        print \implode(' -&gt; ', $breadcrumb);
        print ' -&gt; ' . \htmlentities(\serialize($payload)) . "<br />\n";
    }

    protected function getCss()
    {
        return parent::getCss() . ' .pass { color: darkgreen; }';
    }
}

class TestOfSkippingNoMatterWhat extends UnitTestCase
{
    public function skip(): void
    {
        $this->skipIf(true, 'Always skipped -> %s');
    }

    public function testFail(): void
    {
        $this->fail('This really shouldn\'t have happened');
    }
}

class TestOfSkippingOrElse extends UnitTestCase
{
    public function skip(): void
    {
        $this->skipUnless(false, 'Always skipped -> %s');
    }

    public function testFail(): void
    {
        $this->fail('This really shouldn\'t have happened');
    }
}

class TestOfSkippingTwiceOver extends UnitTestCase
{
    public function skip(): void
    {
        $this->skipIf(true, 'First reason -> %s');
        $this->skipIf(true, 'Second reason -> %s');
    }

    public function testFail(): void
    {
        $this->fail('This really shouldn\'t have happened');
    }
}

class TestThatShouldNotBeSkipped extends UnitTestCase
{
    public function skip(): void
    {
        $this->skipIf(false);
        $this->skipUnless(true);
    }

    public function testFail(): void
    {
        $this->fail('We should see this message');
    }

    public function testPass(): void
    {
        $this->pass('We should see this message');
    }
}

$test = new TestSuite('Visual test with 46 passes, 47 fails and 0 exceptions');
$test->add(new PassingUnitTestCaseOutput);
$test->add(new FailingUnitTestCaseOutput);
$test->add(new TestOfMockObjectsOutput);
$test->add(new TestOfPastBugs);
$test->add(new TestOfVisualShell);
$test->add(new TestOfSkippingNoMatterWhat);
$test->add(new TestOfSkippingOrElse);
$test->add(new TestOfSkippingTwiceOver);
$test->add(new TestThatShouldNotBeSkipped);

if (isset($_GET['xml']) || \in_array('xml', ($argv ?? []), true)) {
    $reporter = new XmlReporter;
} elseif (TextReporter::inCli()) {
    $reporter = new TextReporter;
} else {
    $reporter = new PassesAsWellReporter;
}

if (isset($_GET['dry']) || \in_array('dry', ($argv ?? []), true)) {
    $reporter->makeDry();
}

exit($test->run($reporter) ? 0 : 1);
