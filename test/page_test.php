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
            $page = &new SimplePage();
            $this->assertEqual($page->getTransportError(), 'No page fetched yet');
            $this->assertIdentical($page->getRaw(), false);
            $this->assertIdentical($page->getHeaders(), false);
            $this->assertIdentical($page->getMimeType(), false);
            $this->assertIdentical($page->getResponseCode(), false);
            $this->assertIdentical($page->getAuthentication(), false);
            $this->assertIdentical($page->getRealm(), false);
            $this->assertFalse($page->hasFrames());
            $this->assertIdentical($page->getAbsoluteUrls(), array());
            $this->assertIdentical($page->getRelativeUrls(), array());
            $this->assertIdentical($page->getTitle(), false);
        }
    }

    class TestOfPageHeaders extends UnitTestCase {
        function TestOfPageHeaders() {
            $this->UnitTestCase();
        }
        function testUrlAccessor() {
            $headers = &new MockSimpleHttpHeaders($this);
            
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue('getHeaders', $headers);
            $response->setReturnValue('getMethod', 'POST');
            $response->setReturnValue('getUrl', new SimpleUrl('here'));
            $response->setReturnValue('getRequestData', array('a' => 'A'));

            $page = &new SimplePage($response);
            $this->assertEqual($page->getRequestMethod(), 'POST');
            $this->assertEqual($page->getRequestUrl(), new SimpleUrl('here'));
            $this->assertEqual($page->getRequestData(), array('a' => 'A'));
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
            $this->assertIdentical($page->getAbsoluteUrls(), array(), 'abs->%s');
            $this->assertIdentical($page->getRelativeUrls(), array(), 'rel->%s');
            $this->assertIdentical($page->getUrlsByLabel('Label'), array());
        }
        function testAddAbsoluteLink() {
            $link = &new SimpleAnchorTag(array('href' => 'http://somewhere.com'));
            $link->addContent('Label');
            
            $page = &new SimplePage(new MockSimpleHttpResponse($this));
            $page->AcceptTag($link);
            
            $this->assertEqual($page->getAbsoluteUrls(), array('http://somewhere.com'), 'abs->%s');
            $this->assertIdentical($page->getRelativeUrls(), array(), 'rel->%s');
            $this->assertEqual($page->getUrlsByLabel('Label'), array('http://somewhere.com'));
        }
        function testAddStrictRelativeLink() {
            $link = &new SimpleAnchorTag(array('href' => './somewhere.php'));
            $link->addContent('Label');
            $page = &new SimplePage(new MockSimpleHttpResponse($this));
            $page->AcceptTag($link);
            $this->assertEqual($page->getAbsoluteUrls(), array(), 'abs->%s');
            $this->assertIdentical($page->getRelativeUrls(), array('./somewhere.php'), 'rel->%s');
            $this->assertEqual($page->getUrlsByLabel('Label'), array('./somewhere.php'));
        }
        function testAddRelativeLink() {
            $link = &new SimpleAnchorTag(array('href' => 'somewhere.php'));
            $link->addContent('Label');
            $page = &new SimplePage(new MockSimpleHttpResponse($this));
            $page->AcceptTag($link);
            $this->assertEqual($page->getAbsoluteUrls(), array(), 'abs->%s');
            $this->assertIdentical($page->getRelativeUrls(), array('somewhere.php'), 'rel->%s');
            $this->assertEqual($page->getUrlsByLabel('Label'), array('somewhere.php'));
        }
        function testLinkIds() {
            $link = &new SimpleAnchorTag(array('href' => './somewhere.php', 'id' => 33));
            $link->addContent('Label');
            $page = &new SimplePage(new MockSimpleHttpResponse($this));
            $page->AcceptTag($link);
            $this->assertEqual($page->getUrlsByLabel('Label'), array('./somewhere.php'));
            $this->assertFalse($page->getUrlById(0));
            $this->assertEqual($page->getUrlById(33), './somewhere.php');
        }
        function testFindLinkWithNormalisation() {
            $link = &new SimpleAnchorTag(array('href' => './somewhere.php', 'id' => 33));
            $link->addContent(' long  label ');
            $page = &new SimplePage(new MockSimpleHttpResponse($this));
            $page->AcceptTag($link);
            $this->assertEqual($page->getUrlsByLabel('Long label'), array('./somewhere.php'));
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
        function testButtons() {
            $page = &new SimplePage(new MockSimpleHttpResponse($this));
            $page->acceptFormStart(
                    new SimpleFormTag(array("method" => "GET", "action" => "here.php")));
            $page->AcceptTag(
                    new SimpleSubmitTag(array("type" => "submit", "name" => "s")));
            $page->acceptFormEnd();
            $form = &$page->getFormBySubmitLabel("Submit");
            $this->assertEqual($form->submitButtonByLabel("Submit"), array("s" => "Submit"));
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
            $this->assertIdentical($page->getAbsoluteUrls(), array());
            $this->assertIdentical($page->getRelativeUrls(), array());
            $this->assertIdentical($page->getTitle(), false);
        }
        function testUninterestingPage() {
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue('getContent', '<html><body><p>Stuff</p></body></html>');
            
            $page = &$this->parse($response);
            $this->assertIdentical($page->getAbsoluteUrls(), array());
            $this->assertIdentical($page->getRelativeUrls(), array());
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
                    $page->getAbsoluteUrls(),
                    array("http://there.com/that.html"));
            $this->assertIdentical(
                    $page->getRelativeUrls(),
                    array("there.html"));
            $this->assertIdentical($page->getUrlsByLabel("There"), array("there.html"));
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
        function testEmptyFrameset() {
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue(
                    'getContent',
                    '<html><frameset></frameset></html>');
            
            $page = &$this->parse($response);
            $this->assertTrue($page->hasFrames());
            $this->assertIdentical($page->getFrames(), array());
        }
        function testSingleFrame() {
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue(
                    'getContent',
                    '<html><frameset><frame src="a.html"></frameset></html>');
            
            $page = &$this->parse($response);
            $this->assertTrue($page->hasFrames());
            $this->assertIdentical($page->getFrames(), array(0 => 'a.html'));
        }
        function testSingleFrameInNestedFrameset() {
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue('getContent',
                    '<html><frameset><frameset>' .
                    '<frame src="a.html">' .
                    '</frameset></frameset></html>');
            
            $page = &$this->parse($response);
            $this->assertTrue($page->hasFrames());
            $this->assertIdentical($page->getFrames(), array(0 => 'a.html'));
        }
        function testFrameWithNoSource() {
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue(
                    'getContent',
                    '<html><frameset><frame></frameset></html>');
            
            $page = &$this->parse($response);
            $this->assertTrue($page->hasFrames());
            $this->assertIdentical($page->getFrames(), array());
        }
        function testFramesCollectedWithNestedFramesetTags() {
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue('getContent',
                    '<html><frameset>' .
                    '<frame src="a.html">' .
                    '<frameset><frame src="b.html"></frameset>' .
                    '<frame src="c.html">' .
                    '</frameset></html>');
            
            $page = &$this->parse($response);
            $this->assertTrue($page->hasFrames());
            $this->assertIdentical(
                    $page->getFrames(),
                    array(0 => 'a.html', 1 => 'b.html', 2 => 'c.html'));
        }
        function testNamedFrames() {
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue('getContent', '<html><frameset>' .
                    '<frame src="a.html">' .
                    '<frame name="_one" src="b.html">' .
                    '<frame src="c.html">' .
                    '<frame src="d.html" name="_two">' .
                    '</frameset></html>');
            
            $page = &$this->parse($response);
            $this->assertTrue($page->hasFrames());
            $this->assertIdentical($page->getFrames(), array(
                    0 => 'a.html',
                    '_one' => 'b.html',
                    2 => 'c.html',
                    '_two' => 'd.html'));
        }
        function testFindFormByLabel() {
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue(
                    'getContent',
                    '<html><head><form><input type="submit"></form></head></html>');
            
            $page = &$this->parse($response);
            $this->assertNull($page->getFormBySubmitLabel('submit'));
            $this->assertIsA($page->getFormBySubmitLabel('Submit'), 'SimpleForm');
        }
        function testFindFormByImage() {
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue(
                    'getContent',
                    '<html><head><form>' .
                    '<input type="image" id=100 alt="Label" name="me">' .
                    '</form></head></html>');
            
            $page = &$this->parse($response);
            $this->assertIsA($page->getFormByImageLabel('Label'), 'SimpleForm');
            $this->assertIsA($page->getFormByImageName('me'), 'SimpleForm');
            $this->assertIsA($page->getFormByImageId(100), 'SimpleForm');
        }
        function testFindFormById() {
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
                    '<input type="text" name="b" value="bbb" id=3>' .
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
                    '<input type="text" name="b" id=3>' .
                    '<input type="submit">' .
                    '</form></head></html>');
            
            $page = &$this->parse($response);
            $this->assertTrue($page->setField('a', 'aaa'));
            $this->assertEqual($page->getField('a'), 'aaa');
            $this->assertTrue($page->setFieldById(3, 'bbb'));
            $this->assertEqual($page->getFieldById(3), 'bbb');
            $this->assertFalse($page->setField('z', 'zzz'));
            $this->assertNull($page->getField('z'));
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
            $this->assertTrue($page->setField('a', 'AAA'));
            $this->assertEqual($page->getField('a'), 'AAA');
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
            $this->assertEqual($page->getField('a'), 'bbb');
            $this->assertFalse($page->setField('a', 'ccc'));
            $this->assertTrue($page->setField('a', 'aaa'));
            $this->assertEqual($page->getField('a'), 'aaa');
        }
    }
?>