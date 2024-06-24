<?php declare(strict_types=1);

require_once __DIR__ . '/../../src/autorun.php';

require_once __DIR__ . '/../testdox.php';

// uncomment to see test dox in action
// SimpleTest::prefer(new TestDoxReporter());

class TestOfTestDoxReporter extends UnitTestCase
{
    public function testIsAnInstanceOfSimpleScorerAndReporter(): void
    {
        $dox = new TestDoxReporter;
        $this->assertIsA($dox, 'SimpleScorer');
        $this->assertIsA($dox, 'SimpleReporter');
    }

    public function testOutputsNameOfTestCase(): void
    {
        $dox = new TestDoxReporter;
        \ob_start();
        $dox->paintCaseStart('TestOfTestDoxReporter');
        $buffer = \ob_get_clean();
        $this->assertPattern('/^TestDoxReporter/', $buffer);
    }

    public function testOutputOfTestCaseNameFilteredByConstructParameter(): void
    {
        $dox = new TestDoxReporter('/^(.*)Test$/');
        \ob_start();
        $dox->paintCaseStart('SomeGreatWidgetTest');
        $buffer = \ob_get_clean();
        $this->assertPattern('/^SomeGreatWidget/', $buffer);
    }

    public function testIfTest_case_patternIsEmptyAssumeEverythingMatches(): void
    {
        $dox = new TestDoxReporter('');
        \ob_start();
        $dox->paintCaseStart('TestOfTestDoxReporter');
        $buffer = \ob_get_clean();
        $this->assertPattern('/^TestOfTestDoxReporter/', $buffer);
    }

    public function testEmptyLineInsertedWhenCaseEnds(): void
    {
        $dox = new TestDoxReporter;
        \ob_start();
        $dox->paintCaseEnd('TestOfTestDoxReporter');
        $buffer = \ob_get_clean();
        $this->assertEqual("\n", $buffer);
    }

    public function testPaintsTestMethodInTestDoxFormat(): void
    {
        $dox = new TestDoxReporter;
        \ob_start();
        $dox->paintMethodStart('testSomeGreatTestCase');
        $buffer = \ob_get_clean();
        $this->assertEqual('- some great test case', $buffer);
        unset($buffer);

        $random = \random_int(100, 200);
        \ob_start();
        $dox->paintMethodStart("testRandomNumberIs{$random}");
        $buffer = \ob_get_clean();
        $this->assertEqual("- random number is {$random}", $buffer);
    }

    public function testDoesNotOutputAnythingOnNoneTestMethods(): void
    {
        $dox = new TestDoxReporter;
        \ob_start();
        $dox->paintMethodStart('nonMatchingMethod');
        $buffer = \ob_get_clean();
        $this->assertEqual('', $buffer);
    }

    public function testPaintMethodAddLineBreak(): void
    {
        $dox = new TestDoxReporter;
        \ob_start();
        $dox->paintMethodEnd('someMethod');
        $buffer = \ob_get_clean();
        $this->assertEqual("\n", $buffer);
    }

    public function testProperlySpacesSingleLettersInMethodName(): void
    {
        $dox = new TestDoxReporter;
        \ob_start();
        $dox->paintMethodStart('testAVerySimpleAgainAVerySimpleMethod');
        $buffer = \ob_get_clean();
        $this->assertEqual('- a very simple again a very simple method', $buffer);
    }

    public function testOnFailureThisPrintsFailureNotice(): void
    {
        $dox = new TestDoxReporter;
        \ob_start();
        $dox->paintFail('');
        $buffer = \ob_get_clean();
        $this->assertEqual(' [FAILED]', $buffer);
    }

    public function testWhenMatchingMethodNamesTestPrefixIsCaseInsensitive(): void
    {
        $dox = new TestDoxReporter;
        \ob_start();
        $dox->paintMethodStart('TESTSupportsAllUppercaseTestPrefixEvenThoughIDoNotKnowWhyYouWouldDoThat');
        $buffer = \ob_get_clean();
        $this->assertEqual(
            '- supports all uppercase test prefix even though i do not know why you would do that',
            $buffer,
        );
    }
}
