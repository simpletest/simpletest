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
