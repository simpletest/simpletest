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
            $tag = new SimpleTitleTag(array("a" => 1, "b" => true));
            $this->assertEqual($tag->getTagName(), "title");
            $this->assertIdentical($tag->getAttribute("a"), "1");
            $this->assertIdentical($tag->getAttribute("b"), true);
            $this->assertIdentical($tag->getAttribute("c"), false);
            $this->assertIdentical($tag->getContent(), "");
        }
        function testTitleContent() {
            $tag = &new SimpleTitleTag(array());
            $this->assertTrue($tag->expectEndTag());
            $tag->addContent("Hello");
            $tag->addContent("World");
            $this->assertEqual($tag->getContent(), "HelloWorld");
        }
        function testTagWithNoEnd() {
            $tag = &new SimpleTextTag(array());
            $this->assertFalse($tag->expectEndTag());
        }
        function testWidgetCheck() {
            $tag = &new SimpleTitleTag(array());
            $this->assertFalse($tag->isWidget());
        }
    }
    
    class TestOfWidget extends UnitTestCase {
        function TestOfWidget() {
            $this->UnitTestCase();
        }
        function testWidgetCheck() {
            $tag = &new SimpleTextTag(array());
            $this->assertTrue($tag->isWidget());
        }
        function testTextDefault() {
            $tag = &new SimpleTextTag(array('value' => 'aaa'));
            $this->assertEqual($tag->getDefault(), 'aaa');
            $this->assertEqual($tag->getValue(), 'aaa');
        }
        function testSettingTextValue() {
            $tag = &new SimpleTextTag(array('value' => 'aaa'));
            $tag->setValue('bbb');
            $this->assertEqual($tag->getValue(), 'bbb');
            $tag->resetValue();
            $this->assertEqual($tag->getValue(), 'aaa');
        }
        function testTextAreaDefault() {
            $tag = &new SimpleTextAreaTag(array());
            $tag->addContent('Some text');
            $this->assertEqual($tag->getDefault(), 'Some text');
        }
        function testSubmitDefaults() {
            $tag = &new SimpleSubmitTag(array('type' => 'submit'));
            $this->assertEqual($tag->getName(), 'submit');
            $this->assertEqual($tag->getValue(), 'Submit');
            $tag->setValue('Cannot set this');
            $this->assertEqual($tag->getValue(), 'Submit');
        }
        function testPopulatedSubmit() {
            $tag = &new SimpleSubmitTag(
                    array('type' => 'submit', 'name' => 's', 'value' => 'Ok!'));
            $this->assertEqual($tag->getName(), 's');
            $this->assertEqual($tag->getValue(), 'Ok!');
        }
    }
    
    class TestOfForm extends UnitTestCase {
        function TestOfForm() {
            $this->UnitTestCase();
        }
        function testFormAttributes() {
            $tag = new SimpleFormTag(array("method" => "GET", "action" => "here.php", "id" => "33"));
            $form = new SimpleForm($tag);
            $this->assertEqual($form->getMethod(), "get");
            $this->assertEqual($form->getAction(), "here.php");
            $this->assertIdentical($form->getId(), "33");
            $this->assertNull($form->getValue("a"));
            $this->assertEqual($form->getValues(), array());
        }
        function testTextWidget() {
            $form = new SimpleForm(new SimpleFormTag(array()));
            $form->addWidget(new SimpleTextTag(
                    array("name" => "me", "type" => "text", "value" => "Myself")));
            $this->assertIdentical($form->getValue("me"), "Myself");
            $this->assertTrue($form->setField("me", "Not me"));
            $this->assertFalse($form->setField("not_present", "Not me"));
            $this->assertIdentical($form->getValue("me"), "Not me");
            $this->assertNull($form->getValue("not_present"));
            $this->assertEqual($form->getValues(), array("me" => "Not me"));
        }
        function testSubmitEmpty() {
            $form = new SimpleForm(new SimpleFormTag(array()));
            $this->assertIdentical($form->submit(), array());
        }
        function testSubmitButton() {
            $form = new SimpleForm(new SimpleFormTag(array()));
            $this->assertIdentical($form->submitButton("go"), false);
            $form->addWidget(new SimpleTextTag(
                    array("type" => "submit", "name" => "go", "value" => "Go!")));
            $this->assertEqual(
                    $form->submitButton("go"),
                    array("go" => "Go!"));            
        }
        function testSubmitButtonByLabel() {
            $form = new SimpleForm(new SimpleFormTag(array()));
            $this->assertIdentical($form->submitButtonByLabel("Go!"), false);
            $form->addWidget(new SimpleSubmitTag(
                    array("type" => "submit", "name" => "go", "value" => "Go!")));
            $this->assertEqual(
                    $form->submitButtonByLabel("Go!"),
                    array("go" => "Go!"));            
        }
    }
?>