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
        function testFormAttributes() {
            $tag = new SimpleTag("form", array("method" => "GET", "action" => "here.php", "id" => "33"));
            $form = new SimpleForm($tag);
            $this->assertEqual($form->getMethod(), "get");
            $this->assertEqual($form->getAction(), "here.php");
            $this->assertIdentical($form->getId(), "33");
            $this->assertNull($form->getValue("a"));
            $this->assertEqual($form->getValues(), array());
        }
        function testTextWidget() {
            $form = new SimpleForm(new SimpleTag("form", array()));
            $form->addWidget(new SimpleTag(
                    "input",
                    array("name" => "me", "type" => "text", "value" => "Myself")));
            $this->assertIdentical($form->getValue("me"), "Myself");
            $this->assertTrue($form->setField("me", "Not me"));
            $this->assertFalse($form->setField("not_present", "Not me"));
            $this->assertIdentical($form->getValue("me"), "Not me");
            $this->assertNull($form->getValue("not_present"));
            $this->assertEqual($form->getValues(), array("me" => "Not me"));
        }
        function testSubmitEmpty() {
            $form = new SimpleForm(new SimpleTag("form", array()));
            $this->assertIdentical($form->submit(), array());
        }
        function testSubmitButton() {
            $form = new SimpleForm(new SimpleTag("form", array()));
            $this->assertIdentical($form->submitButton("go"), false);
            $form->addWidget(new SimpleTag(
                    "input",
                    array("type" => "submit", "name" => "go", "value" => "Go!")));
            $this->assertEqual(
                    $form->submitButton("go"),
                    array("go" => "Go!"));            
        }
        function testSubmitButtonByLabel() {
            $form = new SimpleForm(new SimpleTag("form", array()));
            $this->assertIdentical($form->submitButtonByLabel("Go!"), false);
            $form->addWidget(new SimpleTag(
                    "input",
                    array("type" => "submit", "name" => "go", "value" => "Go!")));
            $this->assertEqual(
                    $form->submitButtonByLabel("Go!"),
                    array("go" => "Go!"));            
        }
    }
?>