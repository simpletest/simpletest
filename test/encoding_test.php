<?php
    // $Id$
    
    require_once(dirname(__FILE__) . '/../url.php');
    
    class QueryStringTestCase extends UnitTestCase {
        
        function testEmpty() {
            $query = &new SimpleQueryString();
            $this->assertIdentical($query->getValue('a'), false);
            $this->assertIdentical($query->getKeys(), array());
            $this->assertIdentical($query->asString(), '');
            $this->assertIdentical($query->getAll(), array());
        }
        
        function testPrefilled() {
            $query = &new SimpleQueryString(array('a' => 'aaa'));
            $this->assertIdentical($query->getValue('a'), 'aaa');
            $this->assertIdentical($query->getKeys(), array('a'));
            $this->assertIdentical($query->asString(), 'a=aaa');
            $this->assertIdentical($query->getAll(), array('a' => 'aaa'));
        }
        
        function testPrefilledWithObject() {
            $query = &new SimpleQueryString(new SimpleQueryString(array('a' => 'aaa')));
            $this->assertIdentical($query->getValue('a'), 'aaa');
            $this->assertIdentical($query->getKeys(), array('a'));
            $this->assertIdentical($query->asString(), 'a=aaa');
        }
        
        function testMultiplePrefilled() {
            $query = &new SimpleQueryString(array('a' => array('a1', 'a2')));
            $this->assertIdentical($query->getValue('a'), array('a1', 'a2'));
            $this->assertIdentical($query->asString(), 'a=a1&a=a2');
            $this->assertIdentical($query->getAll(), array('a' => array('a1', 'a2')));
        }
        
        function testSingleParameter() {
            $query = &new SimpleQueryString();
            $query->add('a', 'Hello');
            $this->assertEqual($query->getValue('a'), 'Hello');
            $this->assertIdentical($query->asString(), 'a=Hello');
        }
        
        function testUrlEncoding() {
            $query = &new SimpleQueryString();
            $query->add('a', 'Hello there!');
            $this->assertIdentical($query->asString(), 'a=Hello+there%21');
        }
        
        function testMultipleParameter() {
            $query = &new SimpleQueryString();
            $query->add('a', 'Hello');
            $query->add('b', 'Goodbye');
            $this->assertIdentical($query->asString(), 'a=Hello&b=Goodbye');
        }
        
        function testEmptyParameters() {
            $query = &new SimpleQueryString();
            $query->add('a', '');
            $query->add('b', '');
            $this->assertIdentical($query->asString(), 'a=&b=');
        }
        
        function testRepeatedParameter() {
            $query = &new SimpleQueryString();
            $query->add('a', 'Hello');
            $query->add('a', 'Goodbye');
            $this->assertIdentical($query->getValue('a'), array('Hello', 'Goodbye'));
            $this->assertIdentical($query->asString(), 'a=Hello&a=Goodbye');
        }
        
        function testSettingCordinates() {
            $query = &new SimpleQueryString();
            $query->setCoordinates('32', '45');
            $this->assertIdentical($query->getX(), 32);
            $this->assertIdentical($query->getY(), 45);
        }
        
        function testAddingLists() {
            $query = &new SimpleQueryString();
            $query->add('a', array('Hello', 'Goodbye'));
            $this->assertIdentical($query->getValue('a'), array('Hello', 'Goodbye'));
            $this->assertIdentical($query->asString(), 'a=Hello&a=Goodbye');
        }
        
        function testMergeInHash() {
            $query = &new SimpleQueryString(array('a' => 'A1', 'b' => 'B'));
            $query->merge(array('a' => 'A2'));
            $this->assertIdentical($query->getValue('a'), array('A1', 'A2'));
            $this->assertIdentical($query->getValue('b'), 'B');
        }
        
        function testMergeInObject() {
            $query = &new SimpleQueryString(array('a' => 'A1', 'b' => 'B'));
            $query->merge(new SimpleQueryString(array('a' => 'A2')));
            $this->assertIdentical($query->getValue('a'), array('A1', 'A2'));
            $this->assertIdentical($query->getValue('b'), 'B');
        }
    }
?>