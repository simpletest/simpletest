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
            $nesting = new NestingGroupTag(array('SIZE' => 2));
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
            $this->assertTrue($parser->parse("<name>a_group</name>\n"));
            $this->assertTrue($parser->parse("</group>\n"));
            $parser->parse("</run>\n");
            $listener->tally();
        }
        function testEmptyCase() {
            $listener = &new MockSimpleRunner($this);
            $listener->expectOnce('paintCaseStart', array('a_case'));
            $listener->expectOnce('paintCaseEnd', array('a_case'));
            $parser = &new SimpleXmlImporter($listener);
            $parser->parse("<?xml version=\"1.0\"?>\n");
            $parser->parse("<run>\n");
            $this->assertTrue($parser->parse("<case>\n"));
            $this->assertTrue($parser->parse("<name>a_case</name>\n"));
            $this->assertTrue($parser->parse("</case>\n"));
            $parser->parse("</run>\n");
            $listener->tally();
        }
        function testEmptyMethod() {
            $listener = &new MockSimpleRunner($this);
            $listener->expectOnce('paintCaseStart', array('a_case'));
            $listener->expectOnce('paintCaseEnd', array('a_case'));
            $listener->expectOnce('paintMethodStart', array('a_method'));
            $listener->expectOnce('paintMethodEnd', array('a_method'));
            $parser = &new SimpleXmlImporter($listener);
            $parser->parse("<?xml version=\"1.0\"?>\n");
            $parser->parse("<run>\n");
            $parser->parse("<case>\n");
            $parser->parse("<name>a_case</name>\n");
            $this->assertTrue($parser->parse("<test>\n"));
            $this->assertTrue($parser->parse("<name>a_method</name>\n"));
            $this->assertTrue($parser->parse("</test>\n"));
            $parser->parse("</case>\n");
            $parser->parse("</run>\n");
            $listener->tally();
        }
    }
?>