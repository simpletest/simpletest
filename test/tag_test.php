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
            $tag = new SimpleTitleTag(array("a" => "1", "b" => ""));
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
    }
    
    class TestOfWidget extends UnitTestCase {
        function TestOfWidget() {
            $this->UnitTestCase();
        }
        function testTextEmptyDefault() {
            $tag = &new SimpleTextTag(array('' => 'text'));
            $this->assertIdentical($tag->getDefault(), '');
            $this->assertIdentical($tag->getValue(), '');
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
        function testFailToSetHiddenValue() {
            $tag = &new SimpleTextTag(array('value' => 'aaa', 'type' => 'hidden'));
            $this->assertFalse($tag->setValue('bbb'));
            $this->assertEqual($tag->getValue(), 'aaa');
        }
        function testSubmitDefaults() {
            $tag = &new SimpleSubmitTag(array('type' => 'submit'));
            $this->assertEqual($tag->getName(), 'submit');
            $this->assertEqual($tag->getValue(), 'Submit');
            $this->assertFalse($tag->setValue('Cannot set this'));
            $this->assertEqual($tag->getValue(), 'Submit');
        }
        function testPopulatedSubmit() {
            $tag = &new SimpleSubmitTag(
                    array('type' => 'submit', 'name' => 's', 'value' => 'Ok!'));
            $this->assertEqual($tag->getName(), 's');
            $this->assertEqual($tag->getValue(), 'Ok!');
        }
    }
    
    class TestOfTextArea extends UnitTestCase {
        function TestOfTextArea() {
            $this->UnitTestCase();
        }
        function testDefault() {
            $tag = &new SimpleTextAreaTag(array('name' => 'a'));
            $tag->addContent('Some text');
            $this->assertEqual($tag->getName(), 'a');
            $this->assertEqual($tag->getDefault(), 'Some text');
        }
        function testWrapping() {
            $tag = &new SimpleTextAreaTag(array('cols' => '10', 'wrap' => 'physical'));
            $tag->addContent("Lot's of text that should be wrapped");
            $this->assertEqual(
                    $tag->getDefault(),
                    "Lot's of\ntext that\nshould be\nwrapped");
            $tag->setValue("New long text\nwith two lines");
            $this->assertEqual(
                    $tag->getValue(),
                    "New long\ntext\nwith two\nlines");
        }
    }
    
    class TestOfSelection extends UnitTestCase {
        function TestOfSelection() {
            $this->UnitTestCase();
        }
        function testEmpty() {
            $tag = &new SimpleSelectionTag(array('name' => 'a'));
            $this->assertIdentical($tag->getValue(), '');
        }
        function testSingle() {
            $tag = &new SimpleSelectionTag(array('name' => 'a'));
            $option = &new SimpleOptionTag(array());
            $option->addContent('AAA');
            $tag->addTag($option);
            $this->assertEqual($tag->getValue(), 'AAA');
        }
        function testSingleDefault() {
            $tag = &new SimpleSelectionTag(array('name' => 'a'));
            $option = &new SimpleOptionTag(array('selected' => ''));
            $option->addContent('AAA');
            $tag->addTag($option);
            $this->assertEqual($tag->getValue(), 'AAA');
        }
        function testSingleMappedDefault() {
            $tag = &new SimpleSelectionTag(array('name' => 'a'));
            $option = &new SimpleOptionTag(array('selected' => '', 'value' => 'aaa'));
            $option->addContent('AAA');
            $tag->addTag($option);
            $this->assertEqual($tag->getValue(), 'aaa');
        }
        function testDefault() {
            $tag = &new SimpleSelectionTag(array('name' => 'a'));
            $a = &new SimpleOptionTag(array());
            $a->addContent('AAA');
            $tag->addTag($a);
            $b = &new SimpleOptionTag(array('selected' => ''));
            $b->addContent('BBB');
            $tag->addTag($b);
            $c = &new SimpleOptionTag(array());
            $c->addContent('CCC');
            $tag->addTag($c);
            $this->assertEqual($tag->getValue(), 'BBB');
        }
        function testSettingOption() {
            $tag = &new SimpleSelectionTag(array('name' => 'a'));
            $a = &new SimpleOptionTag(array());
            $a->addContent('AAA');
            $tag->addTag($a);
            $b = &new SimpleOptionTag(array('selected' => ''));
            $b->addContent('BBB');
            $tag->addTag($b);
            $c = &new SimpleOptionTag(array());
            $c->addContent('CCC');
            $tag->setValue('AAA');
            $this->assertEqual($tag->getValue(), 'AAA');
        }
        function testSettingMappedOption() {
            $tag = &new SimpleSelectionTag(array('name' => 'a'));
            $a = &new SimpleOptionTag(array('value' => 'aaa'));
            $a->addContent('AAA');
            $tag->addTag($a);
            $b = &new SimpleOptionTag(array('value' => 'bbb', 'selected' => ''));
            $b->addContent('BBB');
            $tag->addTag($b);
            $c = &new SimpleOptionTag(array('value' => 'ccc'));
            $c->addContent('CCC');
            $tag->setValue('AAA');
            $this->assertEqual($tag->getValue(), 'aaa');
        }
        function testFailToSetIllegalOption() {
            $tag = &new SimpleSelectionTag(array('name' => 'a'));
            $a = &new SimpleOptionTag(array());
            $a->addContent('AAA');
            $tag->addTag($a);
            $b = &new SimpleOptionTag(array('selected' => ''));
            $b->addContent('BBB');
            $tag->addTag($b);
            $c = &new SimpleOptionTag(array());
            $c->addContent('CCC');
            $tag->addTag($c);
            $this->assertFalse($tag->setValue('Not present'));
            $this->assertEqual($tag->getValue(), 'BBB');
        }
        function testMultipleDefaultWithNoSelections() {
            $tag = &new MultipleSelectionTag(array('name' => 'a', 'multiple' => ''));
            $a = &new SimpleOptionTag(array());
            $a->addContent('AAA');
            $tag->addTag($a);
            $b = &new SimpleOptionTag(array());
            $b->addContent('BBB');
            $tag->addTag($b);
            $this->assertIdentical($tag->getDefault(), array());
            $this->assertIdentical($tag->getValue(), array());
        }
        function testMultipleDefaultWithSelections() {
            $tag = &new MultipleSelectionTag(array('name' => 'a', 'multiple' => ''));
            $a = &new SimpleOptionTag(array('selected' => ''));
            $a->addContent('AAA');
            $tag->addTag($a);
            $b = &new SimpleOptionTag(array('selected' => ''));
            $b->addContent('BBB');
            $tag->addTag($b);
            $this->assertIdentical($tag->getDefault(), array('AAA', 'BBB'));
            $this->assertIdentical($tag->getValue(), array('AAA', 'BBB'));
        }
        function testSettingMultiple() {
            $tag = &new MultipleSelectionTag(array('name' => 'a', 'multiple' => ''));
            $a = &new SimpleOptionTag(array('selected' => ''));
            $a->addContent('AAA');
            $tag->addTag($a);
            $b = &new SimpleOptionTag(array());
            $b->addContent('BBB');
            $tag->addTag($b);
            $c = &new SimpleOptionTag(array('selected' => ''));
            $c->addContent('CCC');
            $tag->addTag($c);
            $this->assertIdentical($tag->getDefault(), array('AAA', 'CCC'));
            $this->assertTrue($tag->setValue(array('BBB', 'CCC')));
            $this->assertIdentical($tag->getValue(), array('BBB', 'CCC'));
            $this->assertTrue($tag->setValue(array()));
            $this->assertIdentical($tag->getValue(), array());
        }
        function testFailToSetIllegalOptionsInMultiple() {
            $tag = &new MultipleSelectionTag(array('name' => 'a', 'multiple' => ''));
            $a = &new SimpleOptionTag(array('selected' => ''));
            $a->addContent('AAA');
            $tag->addTag($a);
            $b = &new SimpleOptionTag(array());
            $b->addContent('BBB');
            $tag->addTag($b);
            $this->assertFalse($tag->setValue(array('CCC')));
            $this->assertTrue($tag->setValue(array('AAA', 'BBB')));
            $this->assertFalse($tag->setValue(array('AAA', 'CCC')));
        }
    }
    
    class TestOfRadioGroup extends UnitTestCase {
        function TestOfRadioGroup() {
            $this->UnitTestCase();
        }
        function testEmptyGroup() {
            $group = &new SimpleRadioGroup();
            $this->assertIdentical($group->getDefault(), false);
            $this->assertIdentical($group->getValue(), false);
            $this->assertFalse($group->setValue('a'));
        }
        function testReadingSingleButtonGroup() {
            $group = &new SimpleRadioGroup();
            $group->addWidget(new SimpleRadioButtonTag(
                    array('value' => 'A', 'checked' => '')));
            $this->assertIdentical($group->getDefault(), 'A');
            $this->assertIdentical($group->getValue(), 'A');
        }
        function testReadingMultipleButtonGroup() {
            $group = &new SimpleRadioGroup();
            $group->addWidget(new SimpleRadioButtonTag(
                    array('value' => 'A')));
            $group->addWidget(new SimpleRadioButtonTag(
                    array('value' => 'B', 'checked' => '')));
            $this->assertIdentical($group->getDefault(), 'B');
            $this->assertIdentical($group->getValue(), 'B');
        }
        function testFailToSetUnlistedValue() {
            $group = &new SimpleRadioGroup();
            $group->addWidget(new SimpleRadioButtonTag(array('value' => 'z')));
            $this->assertFalse($group->setValue('a'));
            $this->assertIdentical($group->getValue(), false);
        }
        function testSettingNewValueClearsTheOldOne() {
            $group = &new SimpleRadioGroup();
            $group->addWidget(new SimpleRadioButtonTag(
                    array('value' => 'A')));
            $group->addWidget(new SimpleRadioButtonTag(
                    array('value' => 'B', 'checked' => '')));
            $this->assertTrue($group->setValue('A'));
            $this->assertIdentical($group->getValue(), 'A');
        }
    }
    
    class TestOfTagGroup extends UnitTestCase {
        function TestOfTagGroup() {
            $this->UnitTestCase();
        }
        function testReadingMultipleCheckboxGroup() {
            $group = &new SimpleCheckboxGroup();
            $group->addWidget(new SimpleCheckboxTag(array('value' => 'A')));
            $group->addWidget(new SimpleCheckboxTag(
                    array('value' => 'B', 'checked' => '')));
            $this->assertIdentical($group->getDefault(), 'B');
            $this->assertIdentical($group->getValue(), 'B');
        }
        function testReadingMultipleUncheckedItems() {
            $group = &new SimpleCheckboxGroup();
            $group->addWidget(new SimpleCheckboxTag(array('value' => 'A')));
            $group->addWidget(new SimpleCheckboxTag(array('value' => 'B')));            
            $this->assertIdentical($group->getDefault(), false);
            $this->assertIdentical($group->getValue(), false);
        }
        function testReadingMultipleCheckedItems() {
            $group = &new SimpleCheckboxGroup();
            $group->addWidget(new SimpleCheckboxTag(
                    array('value' => 'A', 'checked' => '')));
            $group->addWidget(new SimpleCheckboxTag(
                    array('Value' => 'B', 'Checked' => '')));
            $this->assertIdentical($group->getDefault(), array('A', 'B'));
            $this->assertIdentical($group->getValue(), array('A', 'B'));
        }
        function testSettingSingleValue() {
            $group = &new SimpleCheckboxGroup();
            $group->addWidget(new SimpleCheckboxTag(array('value' => 'A')));
            $group->addWidget(new SimpleCheckboxTag(array('Value' => 'B')));
            $this->assertTrue($group->setValue('A'));
            $this->assertIdentical($group->getValue(), 'A');
            $this->assertTrue($group->setValue('B'));
            $this->assertIdentical($group->getValue(), 'B');
        }
        function testSettingMultipleValues() {
            $group = &new SimpleCheckboxGroup();
            $group->addWidget(new SimpleCheckboxTag(array('value' => 'A')));
            $group->addWidget(new SimpleCheckboxTag(array('Value' => 'B')));
            $this->assertTrue($group->setValue(array('A', 'B')));
            $this->assertIdentical($group->getValue(), array('A', 'B'));
        }
        function testSettingNoValue() {
            $group = &new SimpleCheckboxGroup();
            $group->addWidget(new SimpleCheckboxTag(array('value' => 'A')));
            $group->addWidget(new SimpleCheckboxTag(array('value' => 'B')));
            $this->assertTrue($group->setValue(false));
            $this->assertIdentical($group->getValue(), false);
        }
    }
    
    class TestOfForm extends UnitTestCase {
        function TestOfForm() {
            $this->UnitTestCase();
        }
        function testFormAttributes() {
            $tag = &new SimpleFormTag(array("Method" => "GET", "action" => "here.php", "id" => "33"));
            $form = &new SimpleForm($tag);
            $this->assertEqual($form->getMethod(), "get");
            $this->assertEqual($form->getAction(), "here.php");
            $this->assertIdentical($form->getId(), "33");
            $this->assertNull($form->getValue("a"));
            $this->assertEqual($form->getValues(), array());
        }
        function testTextWidget() {
            $form = &new SimpleForm(new SimpleFormTag(array()));
            $form->addWidget(new SimpleTextTag(
                    array("Name" => "me", "Type" => "text", "Value" => "Myself")));
            $this->assertIdentical($form->getValue("me"), "Myself");
            $this->assertTrue($form->setField("me", "Not me"));
            $this->assertFalse($form->setField("not_present", "Not me"));
            $this->assertIdentical($form->getValue("me"), "Not me");
            $this->assertNull($form->getValue("not_present"));
            $this->assertEqual($form->getValues(), array("me" => "Not me"));
        }
        function testSubmitEmpty() {
            $form = &new SimpleForm(new SimpleFormTag(array()));
            $this->assertIdentical($form->submit(), array());
        }
        function testSubmitButton() {
            $form = &new SimpleForm(new SimpleFormTag(array()));
            $this->assertIdentical($form->submitButton("go"), false);
            $form->addWidget(new SimpleTextTag(
                    array("type" => "submit", "name" => "go", "value" => "Go!")));
            $this->assertEqual(
                    $form->submitButton("go"),
                    array("go" => "Go!"));            
        }
        function testSubmitButtonByLabel() {
            $form = &new SimpleForm(new SimpleFormTag(array()));
            $this->assertIdentical($form->submitButtonByLabel("Go!"), false);
            $form->addWidget(new SimpleSubmitTag(
                    array("type" => "Submit", "name" => "go", "value" => "Go!")));
            $this->assertEqual(
                    $form->submitButtonByLabel("Go!"),
                    array("go" => "Go!"));            
        }
        function testSingleSelectFieldSubmitted() {
            $form = &new SimpleForm(new SimpleFormTag(array()));
            $select = &new SimpleSelectionTag(array('name' => 'a'));
            $select->addTag(new SimpleOptionTag(
                    array('value' => 'aaa', 'selected' => '')));
            $form->addWidget($select);
            $this->assertIdentical($form->submit(), array('a' => 'aaa'));
        }
        function testUnchecked() {
            $form = &new SimpleForm(new SimpleFormTag(array()));
            $form->addWidget(new SimpleCheckboxTag(
                    array('name' => 'me', 'type' => 'checkbox')));
            $this->assertIdentical($form->getValue('me'), false);
            $this->assertTrue($form->setField('me', 'on'));
            $this->assertEqual($form->getValue('me'), 'on');
            $this->assertFalse($form->setField('me', 'other'));
            $this->assertEqual($form->getValue('me'), 'on');
        }
        function testChecked() {
            $form = &new SimpleForm(new SimpleFormTag(array()));
            $form->addWidget(new SimpleCheckboxTag(
                    array('name' => 'me', 'value' => 'a', 'type' => 'checkbox', 'checked' => '')));
            $this->assertIdentical($form->getValue('me'), 'a');
            $this->assertFalse($form->setField('me', 'on'));
            $this->assertEqual($form->getValue('me'), 'a');
            $this->assertTrue($form->setField('me', false));
            $this->assertEqual($form->getValue('me'), false);
        }
        function testSingleUncheckedRadioButton() {
            $form = &new SimpleForm(new SimpleFormTag(array()));
            $form->addWidget(new SimpleRadioButtonTag(
                    array('name' => 'me', 'value' => 'a', 'type' => 'radio')));
            $this->assertIdentical($form->getValue('me'), false);
            $this->assertTrue($form->setField('me', 'a'));
            $this->assertIdentical($form->getValue('me'), 'a');
        }
        function testSingleCheckedRadioButton() {
            $form = &new SimpleForm(new SimpleFormTag(array()));
            $form->addWidget(new SimpleRadioButtonTag(
                    array('name' => 'me', 'value' => 'a', 'type' => 'radio', 'checked' => '')));
            $this->assertIdentical($form->getValue('me'), 'a');
            $this->assertFalse($form->setField('me', 'other'));
        }
        function testUncheckedRadioButtons() {
            $form = &new SimpleForm(new SimpleFormTag(array()));
            $form->addWidget(new SimpleRadioButtonTag(
                    array('name' => 'me', 'value' => 'a', 'type' => 'radio')));
            $form->addWidget(new SimpleRadioButtonTag(
                    array('name' => 'me', 'value' => 'b', 'type' => 'radio')));
            $this->assertIdentical($form->getValue('me'), false);
            $this->assertTrue($form->setField('me', 'a'));
            $this->assertIdentical($form->getValue('me'), 'a');
            $this->assertTrue($form->setField('me', 'b'));
            $this->assertIdentical($form->getValue('me'), 'b');
            $this->assertFalse($form->setField('me', 'c'));
            $this->assertIdentical($form->getValue('me'), 'b');
        }
        function testCheckedRadioButtons() {
            $form = &new SimpleForm(new SimpleFormTag(array()));
            $form->addWidget(new SimpleRadioButtonTag(
                    array('name' => 'me', 'value' => 'a', 'type' => 'radio')));
            $form->addWidget(new SimpleRadioButtonTag(
                    array('name' => 'me', 'value' => 'b', 'type' => 'radio', 'checked' => '')));
            $this->assertIdentical($form->getValue('me'), 'b');
            $this->assertTrue($form->setField('me', 'a'));
            $this->assertIdentical($form->getValue('me'), 'a');
        }
        function testMultipleFieldsWithSameKey() {
            $form = &new SimpleForm(new SimpleFormTag(array()));
            $form->addWidget(new SimpleCheckboxTag(
                    array('name' => 'a', 'type' => 'checkbox', 'value' => 'me')));
            $form->addWidget(new SimpleCheckboxTag(
                    array('name' => 'a', 'type' => 'checkbox', 'value' => 'you')));
            $this->assertIdentical($form->getValue('a'), false);
            $this->assertTrue($form->setField('a', 'me'));
            $this->assertIdentical($form->getValue('a'), 'me');
        }
    }
?>