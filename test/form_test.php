<?php
    // $Id$
    
    require_once(dirname(__FILE__) . '/../form.php');
    require_once(dirname(__FILE__) . '/../encoding.php');
    
    class TestOfForm extends UnitTestCase {
        
        function testFormAttributes() {
            $tag = &new SimpleFormTag(array('Method' => 'GET', 'action' => 'here.php', 'id' => '33'));
            $form = &new SimpleForm($tag, new SimpleUrl('http://host/a/index.html'));
            $this->assertEqual($form->getMethod(), 'get');
            $this->assertEqual(
                    $form->getAction(),
                    new SimpleUrl('http://host/a/here.php'));
            $this->assertIdentical($form->getId(), '33');
            $this->assertNull($form->getValue('a'));
        }
        
        function testEmptyAction() {
            $tag = &new SimpleFormTag(array('Method' => 'GET', 'action' => '', 'id' => '33'));
            $form = &new SimpleForm($tag, new SimpleUrl('http://host/a/index.html'));
            $this->assertEqual(
                    $form->getAction(),
                    new SimpleUrl('http://host/a/index.html'));
        }
        
        function testMissingAction() {
            $tag = &new SimpleFormTag(array('Method' => 'GET', 'id' => '33'));
            $form = &new SimpleForm($tag, new SimpleUrl('http://host/a/index.html'));
            $this->assertEqual(
                    $form->getAction(),
                    new SimpleUrl('http://host/a/index.html'));
        }
        
        function testRootAction() {
            $tag = &new SimpleFormTag(array('Method' => 'GET', 'action' => '/', 'id' => '33'));
            $form = &new SimpleForm($tag, new SimpleUrl('http://host/a/index.html'));
            $this->assertEqual(
                    $form->getAction(),
                    new SimpleUrl('http://host/'));
        }
        
        function testDefaultFrameTargetOnForm() {
            $tag = &new SimpleFormTag(array('Method' => 'GET', 'action' => 'here.php', 'id' => '33'));
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
                    array('Name' => 'me', 'Type' => 'text', 'Value' => 'Myself')));
            $this->assertIdentical($form->getValue('me'), 'Myself');
            $this->assertTrue($form->setField('me', 'Not me'));
            $this->assertFalse($form->setField('not_present', 'Not me'));
            $this->assertIdentical($form->getValue('me'), 'Not me');
            $this->assertNull($form->getValue('not_present'));
        }
        
        function testTextWidgetById() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
            $form->addWidget(new SimpleTextTag(
                    array('Name' => 'me', 'Type' => 'text', 'Value' => 'Myself', 'id' => 50)));
            $this->assertIdentical($form->getValueById(50), 'Myself');
            $this->assertTrue($form->setFieldById(50, 'Not me'));
            $this->assertIdentical($form->getValueById(50), 'Not me');
        }
        
        function testSubmitEmpty() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
            $this->assertIdentical($form->submit(), array());
        }
        
        function testSubmitButton() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('http://host'));
            $form->addWidget(new SimpleSubmitTag(
                    array('type' => 'submit', 'name' => 'go', 'value' => 'Go!', 'id' => '9')));
            $this->assertTrue($form->hasSubmitName('go'));
            $this->assertEqual($form->getValue('go'), 'Go!');
            $this->assertEqual($form->getValueById(9), 'Go!');
            $this->assertEqual($form->submitButtonByName('go'), array('go' => 'Go!'));            
            $this->assertEqual($form->submitButtonByLabel('Go!'), array('go' => 'Go!'));            
            $this->assertEqual($form->submitButtonById(9), array('go' => 'Go!'));            
        }
        
        function testSubmitButtonWithLabelOfSubmit() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('http://host'));
            $form->addWidget(new SimpleSubmitTag(
                    array('type' => 'submit', 'name' => 'test', 'value' => 'Submit', 'id' => '9')));
            $this->assertTrue($form->hasSubmitName('test'));
            $this->assertEqual($form->getValue('test'), 'Submit');
            $this->assertEqual($form->getValueById(9), 'Submit');
            $this->assertEqual($form->submitButtonByName('test'), array('test' => 'Submit'));            
            $this->assertEqual($form->submitButtonByLabel('Submit'), array('test' => 'Submit'));            
            $this->assertEqual($form->submitButtonById(9), array('test' => 'Submit'));            
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
            $this->assertTrue($form->hasImageLabel('Go!'));
            $this->assertEqual(
                    $form->submitImageByLabel('Go!', 100, 101),
                    array('go.x' => 100, 'go.y' => 101));
            $this->assertTrue($form->hasImageName('go'));
            $this->assertEqual(
                    $form->submitImageByName('go', 100, 101),
                    array('go.x' => 100, 'go.y' => 101));
            $this->assertTrue($form->hasImageId(9));
            $this->assertEqual(
                    $form->submitImageById(9, 100, 101),
                    array('go.x' => 100, 'go.y' => 101));
        }
        
        function testButtonTag() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('http://host'));
            $widget = &new SimpleButtonTag(
                    array('type' => 'submit', 'name' => 'go', 'value' => 'Go', 'id' => '9'));
            $widget->addContent('Go!');
            $form->addWidget($widget);
            $this->assertTrue($form->hasSubmitName('go'));
            $this->assertTrue($form->hasSubmitLabel('Go!'));
            $this->assertEqual($form->submitButtonByName('go'), array('go' => 'Go'));            
            $this->assertEqual($form->submitButtonByLabel('Go!'), array('go' => 'Go'));            
            $this->assertEqual($form->submitButtonById(9), array('go' => 'Go'));            
        }
        
        function testSingleSelectFieldSubmitted() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
            $select = &new SimpleSelectionTag(array('name' => 'a'));
            $select->addTag(new SimpleOptionTag(
                    array('value' => 'aaa', 'selected' => '')));
            $form->addWidget($select);
            $this->assertIdentical($form->submit(), array('a' => 'aaa'));
        }
        
        function testUnchecked() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
            $form->addWidget(new SimpleCheckboxTag(
                    array('name' => 'me', 'type' => 'checkbox')));
            $this->assertIdentical($form->getValue('me'), false);
            $this->assertTrue($form->setField('me', 'on'));
            $this->assertEqual($form->getValue('me'), 'on');
            $this->assertFalse($form->setField('me', 'other'));
            $this->assertEqual($form->getValue('me'), 'on');
        }
        
        function testChecked() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
            $form->addWidget(new SimpleCheckboxTag(
                    array('name' => 'me', 'value' => 'a', 'type' => 'checkbox', 'checked' => '')));
            $this->assertIdentical($form->getValue('me'), 'a');
            $this->assertFalse($form->setField('me', 'on'));
            $this->assertEqual($form->getValue('me'), 'a');
            $this->assertTrue($form->setField('me', false));
            $this->assertEqual($form->getValue('me'), false);
        }
        
        function testSingleUncheckedRadioButton() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
            $form->addWidget(new SimpleRadioButtonTag(
                    array('name' => 'me', 'value' => 'a', 'type' => 'radio')));
            $this->assertIdentical($form->getValue('me'), false);
            $this->assertTrue($form->setField('me', 'a'));
            $this->assertIdentical($form->getValue('me'), 'a');
        }
        
        function testSingleCheckedRadioButton() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
            $form->addWidget(new SimpleRadioButtonTag(
                    array('name' => 'me', 'value' => 'a', 'type' => 'radio', 'checked' => '')));
            $this->assertIdentical($form->getValue('me'), 'a');
            $this->assertFalse($form->setField('me', 'other'));
        }
        
        function testUncheckedRadioButtons() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
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
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
            $form->addWidget(new SimpleRadioButtonTag(
                    array('name' => 'me', 'value' => 'a', 'type' => 'radio')));
            $form->addWidget(new SimpleRadioButtonTag(
                    array('name' => 'me', 'value' => 'b', 'type' => 'radio', 'checked' => '')));
            $this->assertIdentical($form->getValue('me'), 'b');
            $this->assertTrue($form->setField('me', 'a'));
            $this->assertIdentical($form->getValue('me'), 'a');
        }
        
        function testMultipleFieldsWithSameKey() {
            $form = &new SimpleForm(
                    new SimpleFormTag(array()),
                    new SimpleUrl('htp://host'));
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