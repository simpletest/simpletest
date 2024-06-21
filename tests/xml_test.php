<?php declare(strict_types=1);

require_once __DIR__ . '/../src/autorun.php';

require_once __DIR__ . '/../src/xml.php';

Mock::generate('SimpleScorer');

if (!\function_exists('xml_parser_create')) {
    SimpleTest::ignore('TestOfXmlStructureParsing');
    SimpleTest::ignore('TestOfXmlResultsParsing');
}

class TestOfNestingTags extends UnitTestCase
{
    public function testGroupSize(): void
    {
        $nesting = new NestedGroupTag(['SIZE' => 2]);
        $this->assertEqual($nesting->getSize(), 2);
    }
}

class TestOfXmlStructureParsing extends UnitTestCase
{
    public function testValidXml(): void
    {
        $listener = new MockSimpleScorer;
        $listener->expectNever('paintGroupStart');
        $listener->expectNever('paintGroupEnd');
        $listener->expectNever('paintCaseStart');
        $listener->expectNever('paintCaseEnd');
        $parser = new SimpleTestXmlParser($listener);
        $this->assertTrue($parser->parse("<?xml version=\"1.0\"?>\n"));
        $this->assertTrue($parser->parse("<run>\n"));
        $this->assertTrue($parser->parse("</run>\n"));
    }

    public function testEmptyGroup(): void
    {
        $listener = new MockSimpleScorer;
        $listener->expectOnce('paintGroupStart', ['a_group', 7]);
        $listener->expectOnce('paintGroupEnd', ['a_group']);
        $parser = new SimpleTestXmlParser($listener);
        $parser->parse("<?xml version=\"1.0\"?>\n");
        $parser->parse("<run>\n");
        $this->assertTrue($parser->parse("<group size=\"7\">\n"));
        $this->assertTrue($parser->parse("<name>a_group</name>\n"));
        $this->assertTrue($parser->parse("</group>\n"));
        $parser->parse("</run>\n");
    }

    public function testEmptyCase(): void
    {
        $listener = new MockSimpleScorer;
        $listener->expectOnce('paintCaseStart', ['a_case']);
        $listener->expectOnce('paintCaseEnd', ['a_case']);
        $parser = new SimpleTestXmlParser($listener);
        $parser->parse("<?xml version=\"1.0\"?>\n");
        $parser->parse("<run>\n");
        $this->assertTrue($parser->parse("<case>\n"));
        $this->assertTrue($parser->parse("<name>a_case</name>\n"));
        $this->assertTrue($parser->parse("</case>\n"));
        $parser->parse("</run>\n");
    }

    public function testEmptyMethod(): void
    {
        $listener = new MockSimpleScorer;
        $listener->expectOnce('paintCaseStart', ['a_case']);
        $listener->expectOnce('paintCaseEnd', ['a_case']);
        $listener->expectOnce('paintMethodStart', ['a_method']);
        $listener->expectOnce('paintMethodEnd', ['a_method']);
        $parser = new SimpleTestXmlParser($listener);
        $parser->parse("<?xml version=\"1.0\"?>\n");
        $parser->parse("<run>\n");
        $parser->parse("<case>\n");
        $parser->parse("<name>a_case</name>\n");
        $this->assertTrue($parser->parse("<test>\n"));
        $this->assertTrue($parser->parse("<name>a_method</name>\n"));
        $this->assertTrue($parser->parse("</test>\n"));
        $parser->parse("</case>\n");
        $parser->parse("</run>\n");
    }

    public function testNestedGroup(): void
    {
        $listener = new MockSimpleScorer;
        $listener->expectAt(0, 'paintGroupStart', ['a_group', 7]);
        $listener->expectAt(1, 'paintGroupStart', ['b_group', 3]);
        $listener->expectCallCount('paintGroupStart', 2);
        $listener->expectAt(0, 'paintGroupEnd', ['b_group']);
        $listener->expectAt(1, 'paintGroupEnd', ['a_group']);
        $listener->expectCallCount('paintGroupEnd', 2);

        $parser = new SimpleTestXmlParser($listener);
        $parser->parse("<?xml version=\"1.0\"?>\n");
        $parser->parse("<run>\n");

        $this->assertTrue($parser->parse("<group size=\"7\">\n"));
        $this->assertTrue($parser->parse("<name>a_group</name>\n"));
        $this->assertTrue($parser->parse("<group size=\"3\">\n"));
        $this->assertTrue($parser->parse("<name>b_group</name>\n"));
        $this->assertTrue($parser->parse("</group>\n"));
        $this->assertTrue($parser->parse("</group>\n"));
        $parser->parse("</run>\n");
    }
}

class AnyOldSignal
{
    public $stuff = true;
}

class TestOfXmlResultsParsing extends UnitTestCase
{
    public function sendValidStart(&$parser): void
    {
        $parser->parse("<?xml version=\"1.0\"?>\n");
        $parser->parse("<run>\n");
        $parser->parse("<case>\n");
        $parser->parse("<name>a_case</name>\n");
        $parser->parse("<test>\n");
        $parser->parse("<name>a_method</name>\n");
    }

    public function sendValidEnd(&$parser): void
    {
        $parser->parse("</test>\n");
        $parser->parse("</case>\n");
        $parser->parse("</run>\n");
    }

    public function testPass(): void
    {
        $listener = new MockSimpleScorer;
        $listener->expectOnce('paintPass', ['a_message']);
        $parser = new SimpleTestXmlParser($listener);
        $this->sendValidStart($parser);
        $this->assertTrue($parser->parse("<pass>a_message</pass>\n"));
        $this->sendValidEnd($parser);
    }

    public function testFail(): void
    {
        $listener = new MockSimpleScorer;
        $listener->expectOnce('paintFail', ['a_message']);
        $parser = new SimpleTestXmlParser($listener);
        $this->sendValidStart($parser);
        $this->assertTrue($parser->parse("<fail>a_message</fail>\n"));
        $this->sendValidEnd($parser);
    }

    public function testException(): void
    {
        $listener = new MockSimpleScorer;
        $listener->expectOnce('paintError', ['a_message']);
        $parser = new SimpleTestXmlParser($listener);
        $this->sendValidStart($parser);
        $this->assertTrue($parser->parse("<exception>a_message</exception>\n"));
        $this->sendValidEnd($parser);
    }

    public function testSkip(): void
    {
        $listener = new MockSimpleScorer;
        $listener->expectOnce('paintSkip', ['a_message']);
        $parser = new SimpleTestXmlParser($listener);
        $this->sendValidStart($parser);
        $this->assertTrue($parser->parse("<skip>a_message</skip>\n"));
        $this->sendValidEnd($parser);
    }

    public function testSignal(): void
    {
        $signal        = new AnyOldSignal;
        $signal->stuff = 'Hello';
        $listener      = new MockSimpleScorer;
        $listener->expectOnce('paintSignal', ['a_signal', $signal]);
        $parser = new SimpleTestXmlParser($listener);
        $this->sendValidStart($parser);
        $this->assertTrue($parser->parse(
            '<signal type="a_signal"><![CDATA[' .
                \serialize($signal) . "]]></signal>\n",
        ));
        $this->sendValidEnd($parser);
    }

    public function testMessage(): void
    {
        $listener = new MockSimpleScorer;
        $listener->expectOnce('paintMessage', ['a_message']);
        $parser = new SimpleTestXmlParser($listener);
        $this->sendValidStart($parser);
        $this->assertTrue($parser->parse("<message>a_message</message>\n"));
        $this->sendValidEnd($parser);
    }

    public function testFormattedMessage(): void
    {
        $listener = new MockSimpleScorer;
        $listener->expectOnce('paintFormattedMessage', ["\na\tmessage\n"]);
        $parser = new SimpleTestXmlParser($listener);
        $this->sendValidStart($parser);
        $this->assertTrue($parser->parse("<formatted><![CDATA[\na\tmessage\n]]></formatted>\n"));
        $this->sendValidEnd($parser);
    }
}
