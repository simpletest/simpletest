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
?>