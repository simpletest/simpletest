<?php
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'tag.php');
    
    class TestOfTag extends UnitTestCase {
        function TestOfTag() {
            $this->UnitTestCase();
        }
        function testStartValues() {
            $tag = new SimpleTag("hello", array("a" => 1, "b" => true));
            $this->assertEqual($tag->getname(), "hello");
            $this->assertIdentical($tag->getAttribute("a"), "1");
            $this->assertIdentical($tag->getAttribute("b"), true);
            $this->assertIdentical($tag->getAttribute("c"), false);
            $this->assertIdentical($tag->getContent(), "");
        }
        function testContent() {
            $tag = new SimpleTag("a", array());
            $tag->addContent("Hello");
            $tag->addContent("World");
            $this->assertEqual($tag->getContent(), "HelloWorld");
        }
    }
    
    class TestOfForm extends UnitTestCase {
        function TestOfForm() {
            $this->UnitTestCase();
        }
        function testFormActions() {
            $tag = new SimpleTag("form", array("method" => "get", "action" => "here.php"));
            $form = new SimpleHtmlForm($tag);
            $this->assertEqual($form->getMethod(), "GET");
            $this->assertEqual($form->getAction(), "here.php");
            $this->assertIdentical($form->getValue("a"), false);
            $this->assertEqual($form->submit("go", "Go!"), array("go" => "Go!"));
        }
        function testTextWidget() {
            $form = new SimpleHtmlForm(new SimpleTag("form", array()));
            $form->addWidget(new SimpleTag(
                    "input",
                    array("name" => "me", "type" => "text", "value" => "Myself")));
            $this->assertIdentical($form->getValue("me"), "Myself");
            $form->setValue("me", "Not me");
            $this->assertIdentical($form->getValue("me"), "Not me");
            $this->assertEqual(
                    $form->submit("go", "Go!"),
                    array("go" => "Go!", "me" => "Not me"));
        }
    }
?>