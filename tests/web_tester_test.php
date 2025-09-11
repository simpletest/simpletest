<?php declare(strict_types=1);

require_once __DIR__ . '/../src/autorun.php';

require_once __DIR__ . '/../src/web_tester.php';

class TestOfFieldExpectation extends UnitTestCase
{
    public function testStringMatchingIsCaseSensitive(): void
    {
        $expectation = new FieldExpectation('a');
        $this->assertTrue($expectation->test('a'));
        $this->assertTrue($expectation->test(['a']));
        $this->assertFalse($expectation->test('A'));
    }

    public function testMatchesInteger(): void
    {
        $expectation = new FieldExpectation('1');
        $this->assertTrue($expectation->test('1'));
        $this->assertTrue($expectation->test(1));
        $this->assertTrue($expectation->test(['1']));
        $this->assertTrue($expectation->test([1]));
    }

    public function testNonStringFailsExpectation(): void
    {
        $expectation = new FieldExpectation('a');
        $this->assertFalse($expectation->test(null));
    }

    public function testUnsetFieldCanBeTestedFor(): void
    {
        $expectation = new FieldExpectation(false);
        $this->assertTrue($expectation->test(false));
    }

    public function testMultipleValuesCanBeInAnyOrder(): void
    {
        $expectation = new FieldExpectation(['a', 'b']);
        $this->assertTrue($expectation->test(['a', 'b']));
        $this->assertTrue($expectation->test(['b', 'a']));
        $this->assertFalse($expectation->test(['a', 'a']));
        $this->assertFalse($expectation->test('a'));
    }

    public function testSingleItemCanBeArrayOrString(): void
    {
        $expectation = new FieldExpectation(['a']);
        $this->assertTrue($expectation->test(['a']));
        $this->assertTrue($expectation->test('a'));
    }
}

class TestOfHeaderExpectations extends UnitTestCase
{
    public function testExpectingOnlyTheHeaderName(): void
    {
        $expectation = new HttpHeaderExpectation('a');
        $this->assertIdentical($expectation->test(false), false);
        $this->assertIdentical($expectation->test('a: A'), true);
        $this->assertIdentical($expectation->test('A: A'), true);
        $this->assertIdentical($expectation->test('a: B'), true);
        $this->assertIdentical($expectation->test(' a : A '), true);
    }

    public function testHeaderValueAsWell(): void
    {
        $expectation = new HttpHeaderExpectation('a', 'A');
        $this->assertIdentical($expectation->test(false), false);
        $this->assertIdentical($expectation->test('a: A'), true);
        $this->assertIdentical($expectation->test('A: A'), true);
        $this->assertIdentical($expectation->test('A: a'), false);
        $this->assertIdentical($expectation->test('a: B'), false);
        $this->assertIdentical($expectation->test(' a : A '), true);
        $this->assertIdentical($expectation->test(' a : AB '), false);
    }

    public function testHeaderValueWithColons(): void
    {
        $expectation = new HttpHeaderExpectation('a', 'A:B:C');
        $this->assertIdentical($expectation->test('a: A'), false);
        $this->assertIdentical($expectation->test('a: A:B'), false);
        $this->assertIdentical($expectation->test('a: A:B:C'), true);
        $this->assertIdentical($expectation->test('a: A:B:C:D'), false);
    }

    public function testMultilineSearch(): void
    {
        $expectation = new HttpHeaderExpectation('a', 'A');
        $this->assertIdentical($expectation->test("aa: A\r\nb: B\r\nc: C"), false);
        $this->assertIdentical($expectation->test("aa: A\r\na: A\r\nb: B"), true);
    }

    public function testMultilineSearchWithPadding(): void
    {
        $expectation = new HttpHeaderExpectation('a', ' A ');
        $this->assertIdentical($expectation->test("aa:A\r\nb:B\r\nc:C"), false);
        $this->assertIdentical($expectation->test("aa:A\r\na:A\r\nb:B"), true);
    }

    public function testPatternMatching(): void
    {
        $expectation = new HttpHeaderExpectation('a', new PatternExpectation('/A/'));
        $this->assertIdentical($expectation->test('a: A'), true);
        $this->assertIdentical($expectation->test('A: A'), true);
        $this->assertIdentical($expectation->test('A: a'), false);
        $this->assertIdentical($expectation->test('a: B'), false);
        $this->assertIdentical($expectation->test(' a : A '), true);
        $this->assertIdentical($expectation->test(' a : AB '), true);
    }

    public function testCaseInsensitivePatternMatching(): void
    {
        $expectation = new HttpHeaderExpectation('a', new PatternExpectation('/A/i'));
        $this->assertIdentical($expectation->test('a: a'), true);
        $this->assertIdentical($expectation->test('a: B'), false);
        $this->assertIdentical($expectation->test(' a : A '), true);
        $this->assertIdentical($expectation->test(' a : BAB '), true);
        $this->assertIdentical($expectation->test(' a : bab '), true);
    }

    public function testUnwantedHeader(): void
    {
        $expectation = new NoHttpHeaderExpectation('a');
        $this->assertIdentical($expectation->test(''), true);
        $this->assertIdentical($expectation->test('stuff'), true);
        $this->assertIdentical($expectation->test('b: B'), true);
        $this->assertIdentical($expectation->test('a: A'), false);
        $this->assertIdentical($expectation->test('A: A'), false);
    }

    public function testMultilineUnwantedSearch(): void
    {
        $expectation = new NoHttpHeaderExpectation('a');
        $this->assertIdentical($expectation->test("aa:A\r\nb:B\r\nc:C"), true);
        $this->assertIdentical($expectation->test("aa:A\r\na:A\r\nb:B"), false);
    }

    public function testLocationHeaderSplitsCorrectly(): void
    {
        $expectation = new HttpHeaderExpectation('Location', 'http://here/');
        $this->assertIdentical($expectation->test('Location: http://here/'), true);
    }
}

class TestOfTextExpectations extends UnitTestCase
{
    public function testMatchingSubString(): void
    {
        $expectation = new TextExpectation('wanted');
        $this->assertIdentical($expectation->test(''), false);
        $this->assertIdentical($expectation->test('Wanted'), false);
        $this->assertIdentical($expectation->test('wanted'), true);
        $this->assertIdentical($expectation->test('the wanted text is here'), true);
    }

    public function testNotMatchingSubString(): void
    {
        $expectation = new NoTextExpectation('wanted');
        $this->assertIdentical($expectation->test(''), true);
        $this->assertIdentical($expectation->test('Wanted'), true);
        $this->assertIdentical($expectation->test('wanted'), false);
        $this->assertIdentical($expectation->test('the wanted text is here'), false);
    }
}

class TestOfGenericAssertionsInWebTester extends WebTestCase
{
    public function testEquality(): void
    {
        $this->assertEqual('a', 'a');
        $this->assertNotEqual('a', 'A');
    }
}

class TestOfWebTesterExtra extends UnitTestCase
{
    public function testFieldExpectationWithNonSingleNonArrayReturnsFalse(): void
    {
        $fe = new FieldExpectation(new stdClass);
        $this->assertFalse($fe->test('anything'));
    }

    public function testFieldExpectationSingleAndArrayBehavior(): void
    {
        $fe = new FieldExpectation('x');
        // single value compared to single-element array
        $this->assertTrue($fe->test(['x']));
        // single value compared to non-single
        $this->assertFalse($fe->test(['a', 'b']));

        $fm = new FieldExpectation(['a', 'b']);
        // multiple value compared to shuffled array
        $this->assertTrue($fm->test(['b', 'a']));
        // multiple value compared to string (should be converted)
        $this->assertFalse($fm->test('b'));
    }

    public function testFieldExpectationValueFalse(): void
    {
        $fe = new FieldExpectation(false);
        $this->assertTrue($fe->test(false));
        $this->assertFalse($fe->test('anything'));
    }

    public function testFieldExpectationMessageWhenTrue(): void
    {
        $fe  = new FieldExpectation('ok');
        $msg = $fe->testMessage('ok');
        $this->assertTrue(\str_contains($msg, 'Field expectation'));
    }

    public function testFieldExpectationTestMultipleStringMismatch(): void
    {
        $fm = new FieldExpectation(['a', 'b']);
        // compare given as string that does not match both
        $this->assertFalse($fm->test('a'));
    }

    public function testDescribeTextMatchIncludesPosition(): void
    {
        $t   = new TextExpectation('needle');
        $msg = $t->testMessage('xxneedleyy');
        $this->assertTrue(\str_contains($msg, 'detected at character'));
    }

    public function testHttpHeaderExpectationWithExpectationObject(): void
    {
        $hdrs = "X-Value:  trimmed \r\nAnother: v\r\n";
        // use TextExpectation as an expectation object for header value
        $expect = new TextExpectation('trimmed');
        $h      = new HttpHeaderExpectation('x-value', $expect);
        $this->assertTrue($h->test($hdrs));
        // message should indicate found
        $this->assertTrue(\str_contains(\strtolower($h->testMessage($hdrs)), 'found'));
    }

    public function testHttpHeaderMalformedLinesAndTrim(): void
    {
        // line without colon should be ignored
        $hdrs = "BadLineWithoutColon\r\nX-Test: v\r\n";
        $h    = new HttpHeaderExpectation('X-Test', 'v');
        $this->assertTrue($h->test($hdrs));

        // header value trimming
        $hdrs2 = "X-Tr:   v  \r\n";
        $h2    = new HttpHeaderExpectation('X-Tr', 'v');
        $this->assertTrue($h2->test($hdrs2));
    }

    public function testNoTextExpectationNegation(): void
    {
        $n = new NoTextExpectation('absent');
        $this->assertTrue($n->test('this has no word'));
        $this->assertFalse($n->test('contains absent here'));
    }

    public function testFieldExpectationTestMessageSortsArrays(): void
    {
        $fe  = new FieldExpectation(['b', 'a']);
        $msg = $fe->testMessage(['a', 'b']);
        $this->assertTrue(\is_string($msg));
        $this->assertTrue(\str_contains($msg, 'Field expectation'));
    }

    public function testHttpHeaderExpectationFindsHeaderAndValues(): void
    {
        $hdrs = "X-Test: value\r\nOther: y\r\n";
        $h    = new HttpHeaderExpectation('X-Test', 'value');
        $this->assertTrue($h->test($hdrs));
        $this->assertTrue(\str_contains(\strtolower($h->testMessage($hdrs)), 'found'));

        $h2 = new HttpHeaderExpectation('Missing', false);
        $this->assertFalse($h2->test($hdrs));
    }

    public function testNoHttpHeaderExpectation(): void
    {
        $hdrs = "X-Unwanted: here\r\n";
        $n    = new NoHttpHeaderExpectation('X-Unwanted');
        $this->assertFalse($n->test($hdrs));
        $this->assertTrue(\str_contains($n->testMessage($hdrs), 'Found unwanted header'));

        $n2 = new NoHttpHeaderExpectation('Not-There');
        $this->assertTrue($n2->test($hdrs));
        $this->assertTrue(\str_contains($n2->testMessage($hdrs), 'Did not find unwanted header'));
    }

    public function testTextExpectation(): void
    {
        $t = new TextExpectation('needle');
        $this->assertTrue($t->test('this has a needle inside'));
        $this->assertTrue(\str_contains($t->testMessage('nope'), 'not detected'));
    }
}
