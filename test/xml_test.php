<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'xml.php');
    
    Mock::generate('SimpleRunner');
    
    class TestOfXmlParsing extends UnitTestCase {
        function TestOfXmlParsing() {
            $this->UnitTestCase();
        }
        function testValidXml() {
            $listener = &new MockSimpleRunner($this);
            $listener->expectNever('paintGroupStart');
            $listener->expectNever('paintGroupEnd');
            $listener->expectNever('paintCaseStart');
            $listener->expectNever('paintCaseEnd');
            $parser = &new SimpleXmlImporter($listener);
            $this->assertTrue($parser->parse("<?xml version=\"1.0\"?>\n"));
            $this->assertTrue($parser->parse("<run>\n"));
            $this->assertTrue($parser->parse("</run>\n"));
        }
        function testEmptyGroup() {
            $listener = &new MockSimpleRunner($this);
            $listener->expectOnce('paintGroupStart');
            $listener->expectOnce('paintGroupEnd');
            $parser = &new SimpleXmlImporter($listener);
            $parser->parse("<?xml version=\"1.0\"?>\n");
            $parser->parse("<run>\n");
            $this->assertTrue($parser->parse("<group>\n"));
            $this->assertTrue($parser->parse("</group>\n"));
            $parser->parse("</run>\n");
            $listener->tally();
        }
    }
?>