<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'runner.php');
    require_once(SIMPLE_TEST . 'browser.php');
    
    Mock::generate("SimpleBrowser");
    
    SimpleTestOptions::ignore("MockBrowserWebTestCase");

    class MockBrowserWebTestCase extends WebTestCase {
        function MockBrowserWebTestCase($label = false) {
            $this->WebTestCase($label);
        }
        function &createBrowser() {
            return new MockSimpleBrowser($this);
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
        function _testFormGet() {
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
        function _testFormGetWithNoAction() {
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
        function _testFormPost() {
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
        function _testFormPostById() {
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
        function _testFormSeparation() {
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
        function _testTextFieldDefault() {
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
            $this->assertField('a', 'aaa');
            $this->assertField('b', '');
            $this->assertTrue($this->clickSubmit("Go!"));
        }
        function _testSettingTextField() {
            $widgets = array(
                    '<input type="text" name="a" value="aaa"/>',
                    '<input type="text" name="b" value="bbb"/>');
            $browser = &$this->getBrowser();
            $this->prepareForm($browser, $widgets);
            $browser->expectOnce('post', array('there.php', array(
                    'go' => 'Go!',
                    'a' => 'AAA',
                    'b' => 'bbb')));
            $this->get('http://my-site.com/');
            $this->assertField('a', 'aaa');
            $this->assertField('b', 'bbb');
            $this->setField('a', 'AAA');
            $this->assertField('a', 'AAA');
            $this->assertTrue($this->clickSubmit('Go!'));
        }
        function _testTextAreaDefault() {
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