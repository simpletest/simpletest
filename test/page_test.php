<?php
    // $Id$
    
    require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '../http.php');
    require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '../page.php');
    require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '../parser.php');
    
    Mock::generate('SimpleSaxParser');
    Mock::generate('SimplePage');
    Mock::generate('SimpleHttpResponse');
    Mock::generate('SimplePageBuilder');
    
    class TestOfPageBuilder extends UnitTestCase {
        function TestOfPageBuilder() {
            $this->UnitTestCase();
        }
        function testParserChaining() {
            $parser = &new MockSimpleSaxParser($this);
            $parser->setReturnValue("parse", true);
            $parser->expectArguments("parse", array("<html></html>"));
            $parser->expectCallCount("parse", 1);
            $builder = &new SimplePageBuilder(new MockSimplePage($this));
            $this->assertTrue($builder->parse("<html></html>", $parser));
            $parser->tally();
        }
        function testLink() {
            $tag = &new SimpleAnchorTag(array("href" => "http://somewhere"));
            $tag->addContent("Label");
            $page = &new MockSimplePage($this);
            $page->expectArguments("acceptTag", array($tag));
            $page->expectCallCount("acceptTag", 1);
            $builder = &new SimplePageBuilder($page);
            $this->assertTrue($builder->startElement(
                    "a",
                    array("href" => "http://somewhere")));
            $this->assertTrue($builder->addContent("Label"));
            $this->assertTrue($builder->endElement("a"));
            $page->tally();
        }
        function testLinkWithId() {
            $tag = &new SimpleAnchorTag(array("href" => "http://somewhere", "id" => "44"));
            $tag->addContent("Label");
            $page = &new MockSimplePage($this);
            $page->expectArguments("acceptTag", array($tag));
            $page->expectCallCount("acceptTag", 1);
            $builder = &new SimplePageBuilder($page);
            $this->assertTrue($builder->startElement(
                    "a",
                    array("href" => "http://somewhere", "id" => "44")));
            $this->assertTrue($builder->addContent("Label"));
            $this->assertTrue($builder->endElement("a"));
            $page->tally();
        }
        function testLinkExtraction() {
            $tag = &new SimpleAnchorTag(array("href" => "http://somewhere"));
            $tag->addContent("Label");
            $page = &new MockSimplePage($this);
            $page->expectArguments("acceptTag", array($tag));
            $page->expectCallCount("acceptTag", 1);
            $builder = &new SimplePageBuilder($page);
            $this->assertTrue($builder->addContent("Starting stuff"));
            $this->assertTrue($builder->startElement(
                    "a",
                    array("href" => "http://somewhere")));
            $this->assertTrue($builder->addContent("Label"));
            $this->assertTrue($builder->endElement("a"));
            $this->assertTrue($builder->addContent("Trailing stuff"));
            $page->tally();
        }
        function testMultipleLinks() {
            $a1 = new SimpleAnchorTag(array("href" => "http://somewhere"));
            $a1->addContent("1");
            $a2 = new SimpleAnchorTag(array("href" => "http://elsewhere"));
            $a2->addContent("2");
            $page = &new MockSimplePage($this);
            $page->expectArgumentsAt(0, "acceptTag", array($a1));
            $page->expectArgumentsAt(1, "acceptTag", array($a2));
            $page->expectCallCount("acceptTag", 2);
            $builder = &new SimplePageBuilder($page);
            $builder->startElement("a", array("href" => "http://somewhere"));
            $builder->addContent("1");
            $builder->endElement("a");
            $builder->addContent("Padding");
            $builder->startElement("a", array("href" => "http://elsewhere"));
            $builder->addContent("2");
            $builder->endElement("a");
            $page->tally();
        }
        function testTitle() {
            $tag = &new SimpleTitleTag(array());
            $tag->addContent("HereThere");
            $page = &new MockSimplePage($this);
            $page->expectArguments("acceptTag", array($tag));
            $page->expectCallCount("acceptTag", 1);
            $builder = &new SimplePageBuilder($page);
            $builder->startElement("title", array());
            $builder->addContent("Here");
            $builder->addContent("There");
            $builder->endElement("title");
            $page->tally();
        }
        function testForm() {
            $page = &new MockSimplePage($this);
            $page->expectArguments("acceptFormStart", array(new SimpleFormTag(array())));
            $page->expectCallCount("acceptFormStart", 1);
            $page->expectArguments("acceptFormEnd", array());
            $page->expectCallCount("acceptFormEnd", 1);
            $builder = &new SimplePageBuilder($page);
            $builder->startElement("form", array());
            $builder->addContent("Stuff");
            $builder->endElement("form");
            $page->tally();
        }
    }
    
    class TestVersionOfSimplePage extends SimplePage {
        var $_parser;
        var $_builder;
        
        function TestVersionOfSimplePage($response, &$parser, &$builder) {
            $this->_parser = &$parser;
            $this->_builder = &$builder;
            $this->SimplePage($response);
        }
        function &_createParser() {
            return $this->_parser;
        }
        function &_createBuilder() {
            return $this->_builder;
        }
    }
    
    class TestOfPageParsing extends UnitTestCase {
        function TestOfPageParsing() {
            $this->UnitTestCase();
        }
        function testParse() {
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue('getContent', 'stuff');

            $parser = &new MockSimpleSaxParser($this);

            $builder = &new MockSimplePageBuilder($this);
            $builder->expectArguments("parse", array("stuff", "*"));
            $builder->expectCallCount("parse", 1);
            
            $page = &new TestVersionOfSimplePage($response, $parser, $builder);
            $builder->tally();
        }
    }

    class TestOfHtmlPage extends UnitTestCase {
        function TestOfHtmlPage() {
            $this->UnitTestCase();
        }
        function testRawAccessor() {
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue('getContent', 'Raw HTML');

            $page = &new SimplePage($response);
            $this->assertEqual($page->getRaw(), 'Raw HTML');
        }
        function testNoLinks() {
            $page = &new SimplePage(new MockSimpleHttpResponse($this));
            $this->assertIdentical($page->getAbsoluteLinks(), array(), 'abs->%s');
            $this->assertIdentical($page->getRelativeLinks(), array(), 'rel->%s');
            $this->assertIdentical($page->getUrls('Label'), array());
        }
        function testAddAbsoluteLink() {
            $link = &new SimpleAnchorTag(array('href' => 'http://somewhere.com'));
            $link->addContent('Label');
            
            $page = &new SimplePage(new MockSimpleHttpResponse($this));
            $page->AcceptTag($link);
            
            $this->assertEqual($page->getAbsoluteLinks(), array('http://somewhere.com'), 'abs->%s');
            $this->assertIdentical($page->getRelativeLinks(), array(), 'rel->%s');
            $this->assertEqual($page->getUrls('Label'), array('http://somewhere.com'));
        }
        function testAddStrictRelativeLink() {
            $link = &new SimpleAnchorTag(array('href' => './somewhere.php'));
            $link->addContent('Label');
            $page = &new SimplePage(new MockSimpleHttpResponse($this));
            $page->AcceptTag($link);
            $this->assertEqual($page->getAbsoluteLinks(), array(), 'abs->%s');
            $this->assertIdentical($page->getRelativeLinks(), array('./somewhere.php'), 'rel->%s');
            $this->assertEqual($page->getUrls('Label'), array('./somewhere.php'));
        }
        function testAddRelativeLink() {
            $link = &new SimpleAnchorTag(array('href' => 'somewhere.php'));
            $link->addContent('Label');
            $page = &new SimplePage(new MockSimpleHttpResponse($this));
            $page->AcceptTag($link);
            $this->assertEqual($page->getAbsoluteLinks(), array(), 'abs->%s');
            $this->assertIdentical($page->getRelativeLinks(), array('somewhere.php'), 'rel->%s');
            $this->assertEqual($page->getUrls('Label'), array('somewhere.php'));
        }
        function testLinkIds() {
            $link = &new SimpleAnchorTag(array('href' => './somewhere.php', 'id' => 33));
            $link->addContent('Label');
            $page = &new SimplePage(new MockSimpleHttpResponse($this));
            $page->AcceptTag($link);
            $this->assertEqual($page->getUrls('Label'), array('./somewhere.php'));
            $this->assertFalse($page->getUrlById(0));
            $this->assertEqual($page->getUrlById(33), './somewhere.php');
        }
        function testFindLinkWithNormalisation() {
            $link = &new SimpleAnchorTag(array('href' => './somewhere.php', 'id' => 33));
            $link->addContent(' long  label ');
            $page = &new SimplePage(new MockSimpleHttpResponse($this));
            $page->AcceptTag($link);
            $this->assertEqual($page->getUrls('Long label'), array('./somewhere.php'));
        }
        function testTitleSetting() {
            $title = &new SimpleTitleTag(array());
            $title->addContent('Title');
            $page = &new SimplePage(new MockSimpleHttpResponse($this));
            $page->AcceptTag($title);
            $this->assertEqual($page->getTitle(), 'Title');
        }
        function testFramesetAbsence() {
            $page = &new SimplePage(new MockSimpleHttpResponse($this));
            $this->assertFalse($page->hasFrameset());
        }
        function testHasFrameset() {
            $page = &new SimplePage(new MockSimpleHttpResponse($this));
            $page->acceptFramesetStart(new SimpleFramesetTag(array()));
            $page->acceptFramesetEnd();
            $this->assertTrue($page->hasFrameset());
        }
    }
    
    class TestOfForms extends UnitTestCase {
        function TestOfForms() {
            $this->UnitTestCase();
        }
        function testEmptyForm() {
            $page = &new SimplePage(new MockSimpleHttpResponse($this));
            $page->acceptFormStart(new SimpleFormTag(array()));
            $forms = $page->getForms();
            $this->assertIdentical($forms[0]->getAction(), false);
            $this->assertIdentical($forms[0]->getMethod(), 'get');
            $page->acceptFormEnd();
            $forms = $page->getForms();
            $this->assertIdentical($forms[0]->getAction(), false);
        }
        function testCompleteForm() {
            $page = &new SimplePage(new MockSimpleHttpResponse($this));
            $page->acceptFormStart(
                    new SimpleFormTag(array("method" => "GET", "action" => "here.php")));
            $forms = $page->getForms();
            $this->assertIdentical($forms[0]->getAction(), 'here.php');
            $this->assertIdentical($forms[0]->getMethod(), 'get');
            $page->acceptFormEnd();
            $forms = $page->getForms();
            $this->assertIdentical($forms[0]->getAction(), 'here.php');
        }
        function testNestedForm() {
            $page = &new SimplePage(new MockSimpleHttpResponse($this));
            $page->acceptFormStart(new SimpleFormTag(array("method" => "GET", "action" => "outer.php")));
            $page->acceptFormStart(new SimpleFormTag(array("method" => "POST", "action" => "inner.php")));
            $forms = $page->getForms();
            $this->assertEqual($forms[0]->getAction(), "outer.php");
            $this->assertEqual($forms[1]->getAction(), "inner.php");
            $page->acceptFormEnd();
            $page->acceptFormEnd();
            $forms = $page->getForms();
            $this->assertEqual($forms[0]->getAction(), "inner.php");
            $this->assertEqual($forms[1]->getAction(), "outer.php");
        }
        function testButtons() {
            $page = &new SimplePage(new MockSimpleHttpResponse($this));
            $page->acceptFormStart(
                    new SimpleFormTag(array("method" => "GET", "action" => "here.php")));
            $page->AcceptTag(
                    new SimpleSubmitTag(array("type" => "submit", "name" => "s")));
            $page->acceptFormEnd();
            $form = &$page->getFormBySubmitLabel("Submit");
            $this->assertEqual($form->submitButton("s"), array("s" => "Submit"));
        }
    }
    
    class TestOfPageScraping extends UnitTestCase {
        function TestOfPageScraping() {
            $this->UnitTestCase();
        }
        function testEmptyPage() {
            $page = &new SimplePage(new MockSimpleHttpResponse($this));
            $this->assertIdentical($page->getAbsoluteLinks(), array());
            $this->assertIdentical($page->getRelativeLinks(), array());
            $this->assertIdentical($page->getTitle(), false);
        }
        function testUninterestingPage() {
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue('getContent', '<html><body><p>Stuff</p></body></html>');
            
            $page = &new SimplePage($response);
            $this->assertIdentical($page->getAbsoluteLinks(), array());
            $this->assertIdentical($page->getRelativeLinks(), array());
        }
        function testLinksPage() {
            $raw = '<html>';
            $raw .= '<a href="there.html">There</a>';
            $raw .= '<a href="http://there.com/that.html" id="0">That page</a>';
            $raw .= '</html>';
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue('getContent', $raw);

            $page = &new SimplePage($response);
            $this->assertIdentical(
                    $page->getAbsoluteLinks(),
                    array("http://there.com/that.html"));
            $this->assertIdentical(
                    $page->getRelativeLinks(),
                    array("there.html"));
            $this->assertIdentical($page->getUrls("There"), array("there.html"));
            $this->assertEqual($page->getUrlById("0"), "http://there.com/that.html");
        }
        function testTitle() {
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue('getContent', '<html><head><title>Me</title></head></html>');
            
            $page = &new SimplePage($response);
            $this->assertEqual($page->getTitle(), 'Me');
        }
        function testNastyTitle() {
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue(
                    'getContent',
                    '<html><head><Title>Me&amp;Me</TITLE></head></html>');
            
            $page = &new SimplePage($response);
            $this->assertEqual($page->getTitle(), "Me&amp;Me");
        }
        function testFrameset() {
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue(
                    'getContent',
                    '<html><frameset><frame src="a"></frameset></html>');
            
            $page = &new SimplePage($response);
            $this->assertTrue($page->hasFrameset());
        }
        function testFormByLabel() {
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue(
                    'getContent',
                    '<html><head><form><input type="submit"></form></head></html>');
            
            $page = &new SimplePage($response);
            $this->assertNull($page->getFormBySubmitLabel('submit'));
            $this->assertIsA($page->getFormBySubmitLabel('Submit'), 'SimpleForm');
        }
        function testFormById() {
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue(
                    'getContent',
                    '<html><head><form id="55"><input type="submit"></form></head></html>');
            
            $page = &new SimplePage($response);
            $this->assertNull($page->getFormById(54));
            $this->assertIsA($page->getFormById(55), 'SimpleForm');
        }
        function testReadingTextField() {
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue('getContent', '<html><head><form>' .
                    '<input type="text" name="a">' .
                    '<input type="text" name="b" value="bbb">' .
                    '</form></head></html>');
            
            $page = &new SimplePage($response);
            $this->assertNull($page->getField('missing'));
            $this->assertIdentical($page->getField('a'), '');
            $this->assertIdentical($page->getField('b'), 'bbb');
        }
        function testSettingTextField() {
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue('getContent', '<html><head><form>' .
                    '<input type="text" name="a">' .
                    '<input type="submit">' .
                    '</form></head></html>');
            
            $page = &new SimplePage($response);
            $this->assertTrue($page->setField('a', 'aaa'));
            $this->assertEqual($page->getField('a'), 'aaa');
            $this->assertFalse($page->setField('b', 'bbb'));
            $this->assertNull($page->getField('b'));
        }
        function testReadingTextArea() {
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue('getContent', '<html><head><form>' .
                    '<textarea name="a">aaa</textarea>' .
                    '<input type="submit">' .
                    '</form></head></html>');
            
            $page = &new SimplePage($response);
            $this->assertEqual($page->getField('a'), 'aaa');
        }
        function testSettingTextArea() {
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue('getContent', '<html><head><form>' .
                    '<textarea name="a">aaa</textarea>' .
                    '<input type="submit">' .
                    '</form></head></html>');
            
            $page = &new SimplePage($response);
            $this->assertTrue($page->setField("a", "AAA"));
            $this->assertEqual($page->getField("a"), "AAA");
        }
        function testSettingSelectionField() {
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue('getContent', '<html><head><form>' .
                    '<select name="a">' .
                    '<option>aaa</option>' .
                    '<option selected>bbb</option>' .
                    '</select>' .
                    '<input type="submit">' .
                    '</form></head></html>');
            
            $page = &new SimplePage($response);
            $this->assertEqual($page->getField("a"), "bbb");
            $this->assertFalse($page->setField("a", "ccc"));
            $this->assertTrue($page->setField("a", "aaa"));
            $this->assertEqual($page->getField("a"), "aaa");
        }
    }
?>