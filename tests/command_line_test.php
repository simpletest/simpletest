<?php declare(strict_types=1);

require_once __DIR__ . '/../src/autorun.php';

require_once __DIR__ . '/../src/default_reporter.php';

class TestOfCommandLineParsing extends UnitTestCase
{
    public function testDefaultsToEmptyStringToMeanNullToTheSelectiveReporter(): void
    {
        $parser = new SimpleCommandLineParser([]);
        $this->assertIdentical($parser->getTest(), '');
        $this->assertIdentical($parser->getTestCase(), '');
    }

    public function testNotXmlByDefault(): void
    {
        $parser = new SimpleCommandLineParser([]);
        $this->assertFalse($parser->isXml());
    }

    public function testCanDetectRequestForXml(): void
    {
        $parser = new SimpleCommandLineParser(['--xml']);
        $this->assertTrue($parser->isXml());
    }

    public function testCanReadAssignmentSyntax(): void
    {
        $parser = new SimpleCommandLineParser(['--test=myTest']);
        $this->assertEqual($parser->getTest(), 'myTest');
    }

    public function testCanReadFollowOnSyntax(): void
    {
        $parser = new SimpleCommandLineParser(['--test', 'myTest']);
        $this->assertEqual($parser->getTest(), 'myTest');
    }

    public function testCanReadShortForms(): void
    {
        $parser = new SimpleCommandLineParser(['-t', 'myTest', '-c', 'MyClass', '-x']);
        $this->assertEqual($parser->getTest(), 'myTest');
        $this->assertEqual($parser->getTestCase(), 'MyClass');
        $this->assertTrue($parser->isXml());
    }
}
