<?php

require_once __DIR__.'/../src/autorun.php';
require_once __DIR__.'/../src/default_reporter.php';

class TestOfCommandLineParsing extends UnitTestCase
{
    public function testDefaultsToEmptyStringToMeanNullToTheSelectiveReporter()
    {
        $parser = new SimpleCommandLineParser([]);
        $this->assertIdentical($parser->getTest(), '');
        $this->assertIdentical($parser->getTestCase(), '');
    }

    public function testNotXmlByDefault()
    {
        $parser = new SimpleCommandLineParser([]);
        $this->assertFalse($parser->isXml());
    }

    public function testCanDetectRequestForXml()
    {
        $parser = new SimpleCommandLineParser(['--xml']);
        $this->assertTrue($parser->isXml());
    }

    public function testCanReadAssignmentSyntax()
    {
        $parser = new SimpleCommandLineParser(['--test=myTest']);
        $this->assertEqual($parser->getTest(), 'myTest');
    }

    public function testCanReadFollowOnSyntax()
    {
        $parser = new SimpleCommandLineParser(['--test', 'myTest']);
        $this->assertEqual($parser->getTest(), 'myTest');
    }

    public function testCanReadShortForms()
    {
        $parser = new SimpleCommandLineParser(['-t', 'myTest', '-c', 'MyClass', '-x']);
        $this->assertEqual($parser->getTest(), 'myTest');
        $this->assertEqual($parser->getTestCase(), 'MyClass');
        $this->assertTrue($parser->isXml());
    }
}
