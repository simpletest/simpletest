<?php
    // $Id$
    
    require_once(dirname(__FILE__) . '/../form.php');
    require_once(dirname(__FILE__) . '/../encoding.php');
    
    class TestOfForm extends UnitTestCase {
        
        function testFormAttributes() {
            $tag = &new SimpleFormTag(array('method' => 'GET', 'action' => 'here.php', 'id' => '33'));
            $form = &new SimpleForm($tag, new SimpleUrl('http://host/a/index.html'));
            $this->assertEqual($form->getMethod(), 'get');
            $this->assertEqual(
                    $form->getAction(),
                    new SimpleUrl('http://host/a/here.php'));
            $this->assertIdentical($form->getId(), '33');
            $this->assertNull($form->getValueBySelector(new SimpleSelectByName('a')));
        }
        
        function testEmptyAction() {
            $tag = &new SimpleFormTag(array('method' => 'GET', 'action' => '', 'id' => '33'));
            $form = &new SimpleForm($tag, new SimpleUrl('http://host/a/index.html'));
            $this->assertEqual(
                    $form->getAction(),
                    new SimpleUrl('http://host/a/index.html'));
        }
        
        function testMissingAction() {
            $tag = &new SimpleFormTag(array('method' => 'GET', 'id' => '33'));
            $form = &new SimpleForm($tag, new SimpleUrl('http://host/a/index.html'));
            $this->assertEqual(
                    $form->getAction(),
                    new SimpleUrl('http://host/a/index.html'));
        }
        
        function testRootAction() {
            $tag = &new SimpleFormTag(array('method' => 'GET', 'action' => '/', 'id' => '33'));
            $form = &new SimpleForm($tag, new SimpleUrl('http://host/a/index.html'));
            $this->assertEqual(
                    $form->getAction(),
                    new SimpleUrl('http://host/'));
        }
        
        function testDefaultFrameTargetOnForm() {
            $tag = &new SimpleFormTag(array('method' => 'GET', 'action' => 'here.php', 'id' => '33'));
            $form = &new SimpleForm($tag, new SimpleUrl('http://host/a/index.html'));
            $form->setDefaultTarget('frame');
            
            $expected = new SimpleUrl('http://host/a/here.php');
            $expected->setTarget('frame');
            $this->assertEqual($form->getAction(), $expected);
        }
        
        function testTextWidget() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
            $form->addWidget(new SimpleTextTag(
                    array('name' => 'me', 'type' => 'text', 'value' => 'Myself')));
            $this->assertIdentical($form->getValueBySelector(new SimpleSelectByName('me')), 'Myself');
            $this->assertTrue($form->setFieldBySelector(new SimpleSelectByName('me'), 'Not me'));
            $this->assertFalse($form->setFieldBySelector(new SimpleSelectByName('not_present'), 'Not me'));
            $this->assertIdentical($form->getValueBySelector(new SimpleSelectByName('me')), 'Not me');
            $this->assertNull($form->getValueBySelector(new SimpleSelectByName('not_present')));
        }
        
        function testTextWidgetById() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
            $form->addWidget(new SimpleTextTag(
                    array('name' => 'me', 'type' => 'text', 'value' => 'Myself', 'id' => 50)));
            $this->assertIdentical($form->getValueBySelector(new SimpleSelectById(50)), 'Myself');
            $this->assertTrue($form->setFieldBySelector(new SimpleSelectById(50), 'Not me'));
            $this->assertIdentical($form->getValueBySelector(new SimpleSelectById(50)), 'Not me');
        }
        
        function testTextWidgetByLabel() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
            $widget = &new SimpleTextTag(array('name' => 'me', 'type' => 'text', 'value' => 'a'));
            $form->addWidget($widget);
            $widget->setLabel('thing');
            $this->assertIdentical($form->getValueBySelector(new SimpleSelectByLabel('thing')), 'a');
            $this->assertTrue($form->setFieldBySelector(new SimpleSelectByLabel('thing'), 'b'));
            $this->assertIdentical($form->getValueBySelector(new SimpleSelectByLabel('thing')), 'b');
        }
        
        function testSubmitEmpty() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
            $this->assertIdentical($form->submit(), new SimpleGetEncoding());
        }
        
        function testSubmitButton() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('http://host'));
            $form->addWidget(new SimpleSubmitTag(
                    array('type' => 'submit', 'name' => 'go', 'value' => 'Go!', 'id' => '9')));
            $this->assertTrue($form->hasSubmitBySelector(new SimpleSelectByName('go')));
            $this->assertEqual($form->getValueBySelector(new SimpleSelectByName('go')), 'Go!');
            $this->assertEqual($form->getValueBySelector(new SimpleSelectById(9)), 'Go!');
            $this->assertEqual(
                    $form->submitButtonBySelector(new SimpleSelectByName('go')),
                    new SimpleGetEncoding(array('go' => 'Go!')));            
            $this->assertEqual(
                    $form->submitButtonBySelector(new SimpleSelectByLabel('Go!')),
                    new SimpleGetEncoding(array('go' => 'Go!')));            
            $this->assertEqual(
                    $form->submitButtonBySelector(new SimpleSelectById(9)),
                    new SimpleGetEncoding(array('go' => 'Go!')));            
        }
        
        function testSubmitWithAdditionalParameters() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('http://host'));
            $form->addWidget(new SimpleSubmitTag(
                    array('type' => 'submit', 'name' => 'go', 'value' => 'Go!', 'id' => '9')));
            $this->assertEqual(
                    $form->submitButtonBySelector(new SimpleSelectByName('go'), array('a' => 'A')),
                    new SimpleGetEncoding(array('go' => 'Go!', 'a' => 'A')));            
            $this->assertEqual(
                    $form->submitButtonBySelector(new SimpleSelectByLabel('Go!'), array('a' => 'A')),
                    new SimpleGetEncoding(array('go' => 'Go!', 'a' => 'A')));            
            $this->assertEqual(
                    $form->submitButtonBySelector(new SimpleSelectById(9), array('a' => 'A')),
                    new SimpleGetEncoding(array('go' => 'Go!', 'a' => 'A')));            
        }
        
        function testSubmitButtonWithLabelOfSubmit() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('http://host'));
            $form->addWidget(new SimpleSubmitTag(
                    array('type' => 'submit', 'name' => 'test', 'value' => 'Submit', 'id' => '9')));
            $this->assertTrue($form->hasSubmitBySelector(new SimpleSelectByName('test')));
            $this->assertEqual($form->getValueBySelector(new SimpleSelectByName('test')), 'Submit');
            $this->assertEqual($form->getValueBySelector(new SimpleSelectById(9)), 'Submit');
            $this->assertEqual(
                    $form->submitButtonBySelector(new SimpleSelectByName('test')),
                    new SimpleGetEncoding(array('test' => 'Submit')));            
            $this->assertEqual(
                    $form->submitButtonBySelector(new SimpleSelectByLabel('Submit')),
                    new SimpleGetEncoding(array('test' => 'Submit')));            
            $this->assertEqual(
                    $form->submitButtonBySelector(new SimpleSelectById(9)),
                    new SimpleGetEncoding(array('test' => 'Submit')));            
        }
        
        function testSubmitButtonWithWhitespacePaddedLabelOfSubmit() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('http://host'));
            $form->addWidget(new SimpleSubmitTag(
                    array('type' => 'submit', 'name' => 'test', 'value' => ' Submit ', 'id' => '9')));
            $this->assertEqual($form->getValueBySelector(new SimpleSelectByName('test')), ' Submit ');
            $this->assertEqual($form->getValueBySelector(new SimpleSelectById(9)), ' Submit ');
            $this->assertEqual(
                    $form->submitButtonBySelector(new SimpleSelectByLabel('Submit')),
                    new SimpleGetEncoding(array('test' => ' Submit ')));            
        }
        
        function testImageSubmitButton() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
            $form->addWidget(new SimpleImageSubmitTag(array(
                    'type' => 'image',
                    'src' => 'source.jpg',
                    'name' => 'go',
                    'alt' => 'Go!',
                    'id' => '9')));
            $this->assertTrue($form->hasImageBySelector(new SimpleSelectByLabel('Go!')));
            $this->assertEqual(
                    $form->submitImageBySelector(new SimpleSelectByLabel('Go!'), 100, 101),
                    new SimpleGetEncoding(array('go.x' => 100, 'go.y' => 101)));
            $this->assertTrue($form->hasImageBySelector(new SimpleSelectByName('go')));
            $this->assertEqual(
                    $form->submitImageBySelector(new SimpleSelectByName('go'), 100, 101),
                    new SimpleGetEncoding(array('go.x' => 100, 'go.y' => 101)));
            $this->assertTrue($form->hasImageBySelector(new SimpleSelectById(9)));
            $this->assertEqual(
                    $form->submitImageBySelector(new SimpleSelectById(9), 100, 101),
                    new SimpleGetEncoding(array('go.x' => 100, 'go.y' => 101)));
        }
        
        function testImageSubmitButtonWithAdditionalData() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
            $form->addWidget(new SimpleImageSubmitTag(array(
                    'type' => 'image',
                    'src' => 'source.jpg',
                    'name' => 'go',
                    'alt' => 'Go!',
                    'id' => '9')));
            $this->assertEqual(
                    $form->submitImageBySelector(new SimpleSelectByLabel('Go!'), 100, 101, array('a' => 'A')),
                    new SimpleGetEncoding(array('go.x' => 100, 'go.y' => 101, 'a' => 'A')));
            $this->assertTrue($form->hasImageBySelector(new SimpleSelectByName('go')));
            $this->assertEqual(
                    $form->submitImageBySelector(new SimpleSelectByName('go'), 100, 101, array('a' => 'A')),
                    new SimpleGetEncoding(array('go.x' => 100, 'go.y' => 101, 'a' => 'A')));
            $this->assertTrue($form->hasImageBySelector(new SimpleSelectById(9)));
            $this->assertEqual(
                    $form->submitImageBySelector(new SimpleSelectById(9), 100, 101, array('a' => 'A')),
                    new SimpleGetEncoding(array('go.x' => 100, 'go.y' => 101, 'a' => 'A')));
        }
        
        function testButtonTag() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('http://host'));
            $widget = &new SimpleButtonTag(
                    array('type' => 'submit', 'name' => 'go', 'value' => 'Go', 'id' => '9'));
            $widget->addContent('Go!');
            $form->addWidget($widget);
            $this->assertTrue($form->hasSubmitBySelector(new SimpleSelectByName('go')));
            $this->assertTrue($form->hasSubmitBySelector(new SimpleSelectByLabel('Go!')));
            $this->assertEqual(
                    $form->submitButtonBySelector(new SimpleSelectByName('go')),
                    new SimpleGetEncoding(array('go' => 'Go')));
            $this->assertEqual(
                    $form->submitButtonBySelector(new SimpleSelectByLabel('Go!')),
                    new SimpleGetEncoding(array('go' => 'Go')));
            $this->assertEqual(
                    $form->submitButtonBySelector(new SimpleSelectById(9)),
                    new SimpleGetEncoding(array('go' => 'Go')));
        }
        
        function testSingleSelectFieldSubmitted() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
            $select = &new SimpleSelectionTag(array('name' => 'a'));
            $select->addTag(new SimpleOptionTag(
                    array('value' => 'aaa', 'selected' => '')));
            $form->addWidget($select);
            $this->assertIdentical(
                    $form->submit(),
                    new SimpleGetEncoding(array('a' => 'aaa')));
        }
        
        function testSingleSelectFieldSubmittedWithPost() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array('method' => 'post')),
                    new SimpleUrl('htp://host'));
            $select = &new SimpleSelectionTag(array('name' => 'a'));
            $select->addTag(new SimpleOptionTag(
                    array('value' => 'aaa', 'selected' => '')));
            $form->addWidget($select);
            $this->assertIdentical(
                    $form->submit(),
                    new SimplePostEncoding(array('a' => 'aaa')));
        }
        
        function testUnchecked() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
            $form->addWidget(new SimpleCheckboxTag(
                    array('name' => 'me', 'type' => 'checkbox')));
            $this->assertIdentical($form->getValueBySelector(new SimpleSelectByName('me')), false);
            $this->assertTrue($form->setFieldBySelector(new SimpleSelectByName('me'), 'on'));
            $this->assertEqual($form->getValueBySelector(new SimpleSelectByName('me')), 'on');
            $this->assertFalse($form->setFieldBySelector(new SimpleSelectByName('me'), 'other'));
            $this->assertEqual($form->getValueBySelector(new SimpleSelectByName('me')), 'on');
        }
        
        function testChecked() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
            $form->addWidget(new SimpleCheckboxTag(
                    array('name' => 'me', 'value' => 'a', 'type' => 'checkbox', 'checked' => '')));
            $this->assertIdentical($form->getValueBySelector(new SimpleSelectByName('me')), 'a');
            $this->assertTrue($form->setFieldBySelector(new SimpleSelectByName('me'), 'a'));
            $this->assertEqual($form->getValueBySelector(new SimpleSelectByName('me')), 'a');
            $this->assertTrue($form->setFieldBySelector(new SimpleSelectByName('me'), false));
            $this->assertEqual($form->getValueBySelector(new SimpleSelectByName('me')), false);
        }
        
        function testSingleUncheckedRadioButton() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
            $form->addWidget(new SimpleRadioButtonTag(
                    array('name' => 'me', 'value' => 'a', 'type' => 'radio')));
            $this->assertIdentical($form->getValueBySelector(new SimpleSelectByName('me')), false);
            $this->assertTrue($form->setFieldBySelector(new SimpleSelectByName('me'), 'a'));
            $this->assertEqual($form->getValueBySelector(new SimpleSelectByName('me')), 'a');
        }
        
        function testSingleCheckedRadioButton() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
            $form->addWidget(new SimpleRadioButtonTag(
                    array('name' => 'me', 'value' => 'a', 'type' => 'radio', 'checked' => '')));
            $this->assertIdentical($form->getValueBySelector(new SimpleSelectByName('me')), 'a');
            $this->assertFalse($form->setFieldBySelector(new SimpleSelectByName('me'), 'other'));
        }
        
        function testUncheckedRadioButtons() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
            $form->addWidget(new SimpleRadioButtonTag(
                    array('name' => 'me', 'value' => 'a', 'type' => 'radio')));
            $form->addWidget(new SimpleRadioButtonTag(
                    array('name' => 'me', 'value' => 'b', 'type' => 'radio')));
            $this->assertIdentical($form->getValueBySelector(new SimpleSelectByName('me')), false);
            $this->assertTrue($form->setFieldBySelector(new SimpleSelectByName('me'), 'a'));
            $this->assertIdentical($form->getValueBySelector(new SimpleSelectByName('me')), 'a');
            $this->assertTrue($form->setFieldBySelector(new SimpleSelectByName('me'), 'b'));
            $this->assertIdentical($form->getValueBySelector(new SimpleSelectByName('me')), 'b');
            $this->assertFalse($form->setFieldBySelector(new SimpleSelectByName('me'), 'c'));
            $this->assertIdentical($form->getValueBySelector(new SimpleSelectByName('me')), 'b');
        }
        
        function testCheckedRadioButtons() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
            $form->addWidget(new SimpleRadioButtonTag(
                    array('name' => 'me', 'value' => 'a', 'type' => 'radio')));
            $form->addWidget(new SimpleRadioButtonTag(
                    array('name' => 'me', 'value' => 'b', 'type' => 'radio', 'checked' => '')));
            $this->assertIdentical($form->getValueBySelector(new SimpleSelectByName('me')), 'b');
            $this->assertTrue($form->setFieldBySelector(new SimpleSelectByName('me'), 'a'));
            $this->assertIdentical($form->getValueBySelector(new SimpleSelectByName('me')), 'a');
        }
        
        function testMultipleFieldsWithSameKey() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
            $form->addWidget(new SimpleCheckboxTag(
                    array('name' => 'a', 'type' => 'checkbox', 'value' => 'me')));
            $form->addWidget(new SimpleCheckboxTag(
                    array('name' => 'a', 'type' => 'checkbox', 'value' => 'you')));
            $this->assertIdentical($form->getValueBySelector(new SimpleSelectByName('a')), false);
            $this->assertTrue($form->setFieldBySelector(new SimpleSelectByName('a'), 'me'));
            $this->assertIdentical($form->getValueBySelector(new SimpleSelectByName('a')), 'me');
        }
    }
?>