<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'xml.php');
    
    Mock::generate('SimpleRunner');
    
    class TestOfNestingTags extends UnitTestCase {
        function TestOfNestingTags() {
            $this->UnitTestCase();
        }
        function testGroupSize() {
            $nesting = new NestingXmlTag('GROUP', array('SIZE' => 2));
            $this->assertEqual($nesting->getSize(), 2);
        }
    }
    
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
            $listener->expectOnce('paintGroupStart', array('a_group', 7));
            $listener->expectOnce('paintGroupEnd', array('a_group'));
            $parser = &new SimpleXmlImporter($listener);
            $parser->parse("<?xml version=\"1.0\"?>\n");
            $parser->parse("<run>\n");
            $this->assertTrue($parser->parse("<group size=\"7\">\n"));
            $this->assertTrue($parser->parse("<name><![CDATA[a_group]]></name>\n"));
            $this->assertTrue($parser->parse("</group>\n"));
            $parser->parse("</run>\n");
            $listener->tally();
        }
    }
?>