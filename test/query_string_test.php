<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'query_string.php');
    
    class QueryStringTestCase extends UnitTestCase {
        function QueryStringTestCase() {
            $this->UnitTestCase();
        }
        function testEmpty() {
            $query = &new SimpleQueryString();
            $this->assertIdentical($query->getValue('a'), false);
            $this->assertIdentical($query->asString(), '');
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
        function testAddingLists() {
            $query = &new SimpleQueryString();
            $query->add('a', array('Hello', 'Goodbye'));
            $this->assertIdentical($query->getValue('a'), array('Hello', 'Goodbye'));
            $this->assertIdentical($query->asString(), 'a=Hello&a=Goodbye');
        }
    }
?>