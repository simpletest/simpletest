<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'runner.php');
    require_once(SIMPLE_TEST . 'browser.php');
    
    Mock::generate("TestBrowser");
    
    SimpleTestOptions::ignore("MockBrowserWebTestCase");

    class MockBrowserWebTestCase extends WebTestCase {
        function MockBrowserWebTestCase($label = false) {
            $this->WebTestCase($label);
        }
        function &createBrowser() {
            return new MockTestBrowser($this);
        }
    }
    
    class TestOfWebFetching extends MockBrowserWebTestCase {
        function TestOfWebFetching() {
            $this->MockBrowserWebTestCase();
        }
        function setUp() {
            $browser = &$this->getBrowser();
            $browser->setReturnValue("get", "Hello world");
            $browser->expectOnce("get", array("http://my-site.com/", false));
        }
        function tearDown() {
            $browser = &$this->getBrowser();
            $browser->_assertTrue(true, "Hello", $this);
            $browser->tally();
        }
        function testContentAccess() {
            $this->assertTrue(is_a($this->getBrowser(), "MockTestBrowser"));
            $this->get("http://my-site.com/");
        }
        function testRawPatternMatching() {
            $this->get("http://my-site.com/");
            $this->assertWantedPattern('/hello/i');
            $this->assertNoUnwantedPattern('/goodbye/i');
        }
        function testResponseCodes() {
            $browser = &$this->getBrowser();
            $browser->expectOnce("assertResponse", array(404, "%s"));
            $this->get("http://my-site.com/");
            $this->assertResponse(404);
        }
        function testMimeTypes() {
            $browser = &$this->getBrowser();
            $browser->expectOnce("assertMime", array("text/html", "%s"));
            $this->get("http://my-site.com/");
            $this->assertMime("text/html");
        }
    }
    
    class TestOfWebPageLinkParsing extends MockBrowserWebTestCase {
        function TestOfWebPageLinkParsing() {
            $this->MockBrowserWebTestCase();
        }
        function setUp() {
            $browser = &$this->getBrowser();
            $browser->setReturnValueAt(
                    0,
                    "get",
                    "<a href=\"http://my-site.com/there\" id=\"2\">Me</a>, <a href=\"a\">Me</a>");
            $browser->setReturnValueAt(1, "get", "Found it");
            $browser->expectCallCount("get", 2);
        }
        function tearDown() {
            $browser = &$this->getBrowser();
            $browser->tally();
        }
        function testLinkClick() {
            $browser = &$this->getBrowser();
            $browser->expectArgumentsAt(1, "get", array("http://my-site.com/there", false));
            $this->get("http://my-site.com/link");
            $this->assertFalse($this->clickLink('You'));
            $this->assertTrue($this->clickLink('Me'));
            $this->assertFalse($this->clickLink('Me'));
            $this->assertWantedPattern('/Found it/i');
        }
        function testLinkIdClick() {
            $browser = &$this->getBrowser();
            $browser->expectArgumentsAt(1, "get", array("http://my-site.com/there", false));
            $this->get("http://my-site.com/link");
            $this->assertFalse($this->clickLinkById(0));
            $this->_getHtml();
            $this->assertTrue($this->clickLinkById(2));
            $this->assertWantedPattern('/Found it/i');
        }
        function testLinkIndexClick() {
            $browser = &$this->getBrowser();
            $browser->expectArgumentsAt(1, "get", array("a", false));
            $this->get("http://my-site.com/link");
            $this->assertFalse($this->clickLink('Me', 2));
            $this->assertTrue($this->clickLink('Me', 1));
        }
    }
    
    class TestOfWebPageTitleParsing extends MockBrowserWebTestCase {
        function TestOfWebPageTitleParsing() {
            $this->MockBrowserWebTestCase();
        }
        function testTitle() {
            $browser = &$this->getBrowser();
            $browser->setReturnValue(
                    "get",
                    "<html><head><title>Pretty page</title></head></html>");
            $this->get("http://my-site.com/");
            $this->assertTitle("Pretty page");
            $browser->tally();
        }
    }
    
    class TestOfWebFormParsing extends MockBrowserWebTestCase {
        function TestOfWebFormParsing() {
            $this->MockBrowserWebTestCase();
        }
        function tearDown() {
            $browser = &$this->getBrowser();
            $browser->tally();
            $this->assertTitle('Done');
        }
        function testFormGet() {
            $browser = &$this->getBrowser();
            $form_code = '<html><head><form method="get" action="there.php">';
            $form_code .= '<input type="submit" name="wibble" value="wobble"/>';
            $form_code .= '</form></head></html>';
            $browser->setReturnValueAt(0, "get", $form_code);
            $browser->expectArgumentsAt(
                    0,
                    "get",
                    array("http://my-site.com/", false));
            $browser->setReturnValueAt(1, "get", '<html><title>Done</title></html>');
            $browser->expectArgumentsAt(
                    1,
                    "get",
                    array("there.php", array("wibble" => "wobble")));
            $browser->expectCallCount("get", 2);
            $this->get("http://my-site.com/");
            $this->assertTrue($this->clickSubmit("wobble"));
        }
        function testFormGetWithNoAction() {
            $browser = &$this->getBrowser();
            $form_code = '<html><head><form method="get">';
            $form_code .= '<input type="submit" name="wibble" value="wobble"/>';
            $form_code .= '</form></head></html>';
            $browser->setReturnValueAt(0, "get", $form_code);
            $browser->setReturnValueAt(1, "get", '<html><title>Done</title></html>');
            $browser->expectArgumentsAt(
                    1,
                    "get",
                    array("http://my-site.com/index.html", array("wibble" => "wobble")));
            $browser->expectCallCount("get", 2);
            $browser->setReturnValue("getCurrentUrl", "http://my-site.com/index.html");
            $this->get("http://my-site.com/");
            $this->assertTrue($this->clickSubmit("wobble"));
        }
        function testFormPost() {
            $browser = &$this->getBrowser();
            $form_code = '<html><head><form method="post" action="there.php">';
            $form_code .= '<input type="submit" name="wibble" value="wobble"/>';
            $form_code .= '</form></head></html>';
            $browser->setReturnValue("get", $form_code);
            $browser->expectOnce("get", array("http://my-site.com/", false));
            $browser->setReturnValue("post", '<html><title>Done</title></html>');
            $browser->expectOnce("post", array("there.php", array("wibble" => "wobble")));
            $this->get("http://my-site.com/");
            $this->assertTrue($this->clickSubmit("wobble"));
        }
        function testFormPostById() {
            $browser = &$this->getBrowser();
            $form_code = '<html><head><form method="post" action="there.php" id="3">';
            $form_code .= '<input type="submit" name="wibble" value="wobble"/>';
            $form_code .= '<input type="text" name="a" value="aaa"/>';
            $form_code .= '</form></head></html>';
            $browser->setReturnValue("get", $form_code);
            $browser->expectOnce("get", array("http://my-site.com/", false));
            $browser->setReturnValue("post", '<html><title>Done</title></html>');
            $browser->expectOnce("post", array("there.php", array("a" => "aaa")));
            $this->get("http://my-site.com/");
            $this->assertTrue($this->clickSubmitByFormId(3));
        }
        function testFormSeparation() {
            $browser = &$this->getBrowser();
            $form_code = '<html><head><form method="post" action="here.php">';
            $form_code .= '<input type="submit" name="s1" value="S1"/>';
            $form_code .= '<input type="text" name="a" value="aaa"/>';
            $form_code .= '</form><form method="post" action="there.php">';
            $form_code .= '<input type="submit" name="s2" value="S2"/>';
            $form_code .= '<input type="text" name="b" value="bbb"/>';
            $form_code .= '</form></head></html>';
            $browser->setReturnValue("get", $form_code);
            $browser->expectOnce("get", array("http://my-site.com/", false));
            $browser->setReturnValue("post", '<html><title>Done</title></html>');
            $browser->expectOnce(
                    "post",
                    array("there.php", array("s2" => "S2", "b" => "bbb")));
            $this->get("http://my-site.com/");
            $this->assertTrue($this->clickSubmit("S2"));
        }
    }
    
    class TestOfFormFilling extends MockBrowserWebTestCase {
        function TestOfFormFilling() {
            $this->WebTestCase();
        }
        function prepareForm(&$browser, $widgets) {
            $browser->setReturnValue("post", '<html><title>Done</title></html>');
            $form_code = '<html><head><form method="post" action="there.php">';
            $form_code .= implode('', $widgets);
            $form_code .= '<input type="submit" name="go" value="Go!"/>';
            $form_code .= '</form></head></html>';
            $browser->setReturnValue("get", $form_code);
        }
        function tearDown() {
            $browser = &$this->getBrowser();
            $browser->tally();
            $this->assertTitle('Done');
        }
        function testTextFieldDefault() {
            $widgets = array(
                    '<input type="text" name="a" value="aaa"/>',
                    '<input type="text" name="b"/>');
            $browser = &$this->getBrowser();
            $this->prepareForm($browser, $widgets);
            $browser->expectOnce("post", array("there.php", array(
                    "go" => "Go!",
                    "a" => "aaa",
                    "b" => "")));
            $this->get("http://my-site.com/");
            $this->assertTrue($this->clickSubmit("Go!"));
        }
        function testSettingTextField() {
            $widgets = array(
                    '<input type="text" name="a" value="aaa"/>',
                    '<input type="text" name="b" value="bbb"/>');
            $browser = &$this->getBrowser();
            $this->prepareForm($browser, $widgets);
            $browser->expectOnce("post", array("there.php", array(
                    "go" => "Go!",
                    "a" => "AAA",
                    "b" => "bbb")));
            $this->get("http://my-site.com/");
            $this->setField("a", "AAA");
            $this->assertTrue($this->clickSubmit("Go!"));
        }
        function testTextAreaDefault() {
            $widgets = array(
                    '<textarea name="a"></textarea>',
                    '<textarea name="b">bbb</textarea>');
            $browser = &$this->getBrowser();
            $this->prepareForm($browser, $widgets);
            $browser->expectOnce("post", array("there.php", array(
                    "go" => "Go!",
                    "a" => "",
                    "b" => "bbb")));
            $this->get("http://my-site.com/");
            $this->assertTrue($this->clickSubmit("Go!"));
        }
    }
?>