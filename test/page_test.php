<?php
    // $Id$
    
    require_once(dirname(__FILE__) . '/../http.php');
    require_once(dirname(__FILE__) . '/../page.php');
    require_once(dirname(__FILE__) . '/../parser.php');
    
    Mock::generate('SimpleSaxParser');
    Mock::generate('SimplePage');
    Mock::generate('SimpleHttpResponse');
    Mock::generate('SimpleHttpHeaders');
    Mock::generate('SimplePageBuilder');
    Mock::generatePartial(
            'SimplePageBuilder',
            'PartialSimplePageBuilder',
            array('_createPage', '_createParser'));
    
    class TestOfPageBuilder extends UnitTestCase {
        function TestOfPageBuilder() {
            $this->UnitTestCase();
        }
        function testLink() {
            $tag = &new SimpleAnchorTag(array('href' => 'http://somewhere'));
            $tag->addContent('Label');
            
            $page = &new MockSimplePage($this);
            $page->expectArguments('acceptTag', array($tag));
            $page->expectCallCount('acceptTag', 1);
            
            $builder = &new PartialSimplePageBuilder($this);
            $builder->setReturnReference('_createPage', $page);
            $builder->setReturnReference('_createParser', new MockSimpleSaxParser($this));
            $builder->SimplePageBuilder();
            
            $builder->parse(new MockSimpleHttpResponse($this));
            $this->assertTrue($builder->startElement(
                    'a',
                    array('href' => 'http://somewhere')));
            $this->assertTrue($builder->addContent('Label'));
            $this->assertTrue($builder->endElement('a'));
            
            $page->tally();
        }
        function testLinkWithId() {
            $tag = &new SimpleAnchorTag(array("href" => "http://somewhere", "id" => "44"));
            $tag->addContent("Label");
            
            $page = &new MockSimplePage($this);
            $page->expectArguments("acceptTag", array($tag));
            $page->expectCallCount("acceptTag", 1);
            
            $builder = &new PartialSimplePageBuilder($this);
            $builder->setReturnReference('_createPage', $page);
            $builder->setReturnReference('_createParser', new MockSimpleSaxParser($this));
            $builder->SimplePageBuilder();
            
            $builder->parse(new MockSimpleHttpResponse($this));
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
            
            $builder = &new PartialSimplePageBuilder($this);
            $builder->setReturnReference('_createPage', $page);
            $builder->setReturnReference('_createParser', new MockSimpleSaxParser($this));
            $builder->SimplePageBuilder();
            
            $builder->parse(new MockSimpleHttpResponse($this));
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
            
            $builder = &new PartialSimplePageBuilder($this);
            $builder->setReturnReference('_createPage', $page);
            $builder->setReturnReference('_createParser', new MockSimpleSaxParser($this));
            $builder->SimplePageBuilder();
            
            $builder->parse(new MockSimpleHttpResponse($this));
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
            
            $builder = &new PartialSimplePageBuilder($this);
            $builder->setReturnReference('_createPage', $page);
            $builder->setReturnReference('_createParser', new MockSimpleSaxParser($this));
            $builder->SimplePageBuilder();
            
            $builder->parse(new MockSimpleHttpResponse($this));
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
            
            $builder = &new PartialSimplePageBuilder($this);
            $builder->setReturnReference('_createPage', $page);
            $builder->setReturnReference('_createParser', new MockSimpleSaxParser($this));
            $builder->SimplePageBuilder();
            
            $builder->parse(new MockSimpleHttpResponse($this));
            $builder->startElement("form", array());
            $builder->addContent("Stuff");
            $builder->endElement("form");
            $page->tally();
        }
    }
    
    class TestOfPageParsing extends UnitTestCase {
        function TestOfPageParsing() {
            $this->UnitTestCase();
        }
        function testParse() {
            $parser = &new MockSimpleSaxParser($this);
            $parser->expectOnce('parse', array('stuff'));

            $builder = &new PartialSimplePageBuilder($this);
            $builder->setReturnReference('_createPage', $page);
            $builder->setReturnReference('_createParser', $parser);
            $builder->SimplePageBuilder();
            
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue('getContent', 'stuff');

            $builder->parse($response);
            $parser->tally();
        }
    }
    
    class TestOfErrorPage extends UnitTestCase {
        function TestOfErrorPage() {
            $this->UnitTestCase();
        }
        function testInterface() {
            $page = &new SimpleErrorPage('An error');
            $this->assertEqual($page->getTransportError(), 'An error');
            $this->assertIdentical($page->getRaw(), false);
            $this->assertIdentical($page->getHeaders(), false);
            $this->assertIdentical($page->getMimeType(), false);
            $this->assertIdentical($page->getResponseCode(), false);
            $this->assertIdentical($page->getAuthentication(), false);
            $this->assertIdentical($page->getRealm(), false);
            $this->assertFalse($page->hasFrames());
            $this->assertIdentical($page->getAbsoluteLinks(), array());
            $this->assertIdentical($page->getRelativeLinks(), array());
            $this->assertIdentical($page->getTitle(), false);
        }
    }

    class TestOfPageHeaders extends UnitTestCase {
        function TestOfPageHeaders() {
            $this->UnitTestCase();
        }
        function testTransportError() {
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue('getError', 'Ouch');

            $page = &new SimplePage($response);
            $this->assertEqual($page->getTransportError(), 'Ouch');
        }
        function testHeadersAccessor() {
            $headers = &new MockSimpleHttpHeaders($this);
            $headers->setReturnValue('getRaw', 'My: Headers');
            
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue('getHeaders', $headers);

            $page = &new SimplePage($response);
            $this->assertEqual($page->getHeaders(), 'My: Headers');
        }
        function testMimeAccessor() {
            $headers = &new MockSimpleHttpHeaders($this);
            $headers->setReturnValue('getMimeType', 'text/html');
            
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue('getHeaders', $headers);

            $page = &new SimplePage($response);
            $this->assertEqual($page->getMimeType(), 'text/html');
        }
        function testResponseAccessor() {
            $headers = &new MockSimpleHttpHeaders($this);
            $headers->setReturnValue('getResponseCode', 301);
            
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue('getHeaders', $headers);

            $page = &new SimplePage($response);
            $this->assertIdentical($page->getResponseCode(), 301);
        }
        function testAuthenticationAccessors() {
            $headers = &new MockSimpleHttpHeaders($this);
            $headers->setReturnValue('getAuthentication', 'Basic');
            $headers->setReturnValue('getRealm', 'Secret stuff');
            
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue('getHeaders', $headers);

            $page = &new SimplePage($response);
            $this->assertEqual($page->getAuthentication(), 'Basic');
            $this->assertEqual($page->getRealm(), 'Secret stuff');
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
            $this->assertFalse($page->hasFrames());
            $this->assertIdentical($page->getFrames(), false);
        }
        function testHasEmptyFrameset() {
            $page = &new SimplePage(new MockSimpleHttpResponse($this));
            $page->acceptFramesetStart(new SimpleTag('frameset', array()));
            $page->acceptFramesetEnd();
            $this->assertTrue($page->hasFrames());
            $this->assertIdentical($page->getFrames(), array());
        }
        function testFramesInPage() {
            $page = &new SimplePage(new MockSimpleHttpResponse($this));
            $page->acceptFrame(new SimpleFrameTag(array('src' => '1.html')));
            $page->acceptFramesetStart(new SimpleTag('frameset', array()));
            $page->acceptFrame(new SimpleFrameTag(array('src' => '2.html')));
            $page->acceptFrame(new SimpleFrameTag(array('src' => '3.html')));
            $page->acceptFramesetEnd();
            $page->acceptFrame(new SimpleFrameTag(array('src' => '4.html')));
            
            $this->assertTrue($page->hasFrames());
            $this->assertIdentical(
                    $page->getFrames(),
                    array(0 => '2.html', 1 => '3.html'));
        }
        function testNamedFramesInPage() {
            $page = &new SimplePage(new MockSimpleHttpResponse($this));
            $page->acceptFramesetStart(new SimpleTag('frameset', array()));
            $page->acceptFrame(new SimpleFrameTag(array('src' => '1.html')));
            $page->acceptFrame(new SimpleFrameTag(array('src' => '2.html', 'name' => 'A')));
            $page->acceptFrame(new SimpleFrameTag(array('src' => '3.html', 'name' => 'B')));
            $page->acceptFrame(new SimpleFrameTag(array('src' => '4.html')));
            $page->acceptFramesetEnd();
            
            $this->assertTrue($page->hasFrames());
            $this->assertIdentical(
                    $page->getFrames(),
                    array(0 => '1.html', 'A' => '2.html', 'B' => '3.html', 3 => '4.html'));
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
        function testExtraClosingFormTag() {
            $page = &new SimplePage(new MockSimpleHttpResponse($this));
            $page->acceptFormStart(
                    new SimpleFormTag(array("method" => "GET", "action" => "here.php")));
            $forms = $page->getForms();
            $this->assertIdentical($forms[0]->getAction(), 'here.php');
            $this->assertIdentical($forms[0]->getMethod(), 'get');
            $page->acceptFormEnd();
            $page->acceptFormEnd();
            $forms = $page->getForms();
            $this->assertEqual(count($forms), 1);
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
        function &parse($response) {
            $builder = &new SimplePageBuilder();
            return $builder->parse($response);
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
            
            $page = &$this->parse($response);
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

            $page = &$this->parse($response);
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
            
            $page = &$this->parse($response);
            $this->assertEqual($page->getTitle(), 'Me');
        }
        function testNastyTitle() {
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue(
                    'getContent',
                    '<html><head><Title>Me&amp;Me</TITLE></head></html>');
            
            $page = &$this->parse($response);
            $this->assertEqual($page->getTitle(), "Me&amp;Me");
        }
        function testFrameset() {
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue(
                    'getContent',
                    '<html><frameset><frame src="a"></frameset></html>');
            
            $page = &$this->parse($response);
            $this->assertTrue($page->hasFrames());
        }
        function testFormByLabel() {
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue(
                    'getContent',
                    '<html><head><form><input type="submit"></form></head></html>');
            
            $page = &$this->parse($response);
            $this->assertNull($page->getFormBySubmitLabel('submit'));
            $this->assertIsA($page->getFormBySubmitLabel('Submit'), 'SimpleForm');
        }
        function testFormById() {
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue(
                    'getContent',
                    '<html><head><form id="55"><input type="submit"></form></head></html>');
            
            $page = &$this->parse($response);
            $this->assertNull($page->getFormById(54));
            $this->assertIsA($page->getFormById(55), 'SimpleForm');
        }
        function testReadingTextField() {
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue('getContent', '<html><head><form>' .
                    '<input type="text" name="a">' .
                    '<input type="text" name="b" value="bbb">' .
                    '</form></head></html>');
            
            $page = &$this->parse($response);
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
            
            $page = &$this->parse($response);
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
            
            $page = &$this->parse($response);
            $this->assertEqual($page->getField('a'), 'aaa');
        }
        function testSettingTextArea() {
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue('getContent', '<html><head><form>' .
                    '<textarea name="a">aaa</textarea>' .
                    '<input type="submit">' .
                    '</form></head></html>');
            
            $page = &$this->parse($response);
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
            
            $page = &$this->parse($response);
            $this->assertEqual($page->getField("a"), "bbb");
            $this->assertFalse($page->setField("a", "ccc"));
            $this->assertTrue($page->setField("a", "aaa"));
            $this->assertEqual($page->getField("a"), "aaa");
        }
    }
?>