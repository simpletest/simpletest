<?php declare(strict_types=1);

require_once __DIR__ . '/../src/autorun.php';

require_once __DIR__ . '/../src/collector.php';

SimpleTest::ignore('MockTestSuite');
Mock::generate('TestSuite');

class PathEqualExpectation extends EqualExpectation
{
    public function __construct($value, $message = '%s')
    {
        parent::__construct(\str_replace('\\', '/', $value), $message);
    }

    public function test($compare)
    {
        return parent::test(\str_replace('\\', '/', $compare));
    }
}

class TestOfCollector extends UnitTestCase
{
    public function testCollectionIsAddedToGroup(): void
    {
        $suite = new MockTestSuite;
        $suite->expectMinimumCallCount('addFile', 2);
        $suite->expect(
            'addFile',
            [new PatternExpectation('/collectable\\.(1|2)$/')],
        );
        $collector = new SimpleCollector;
        $collector->collect($suite, __DIR__ . '/support/collector/');
    }
}

class TestOfPatternCollector extends UnitTestCase
{
    public function testAddingEverythingToGroup(): void
    {
        $suite = new MockTestSuite;
        $suite->expectCallCount('addFile', 2);
        $suite->expect(
            'addFile',
            [new PatternExpectation('/collectable\\.(1|2)$/')],
        );
        $collector = new SimplePatternCollector('/.*/');
        $collector->collect($suite, __DIR__ . '/support/collector/');
    }

    public function testOnlyMatchedFilesAreAddedToGroup(): void
    {
        $suite = new MockTestSuite;
        $suite->expectOnce('addFile', [new PathEqualExpectation(
            __DIR__ . '/support/collector/collectable.1',
        )]);
        $collector = new SimplePatternCollector('/1$/');
        $collector->collect($suite, __DIR__ . '/support/collector/');
    }
}
