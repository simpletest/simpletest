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
            $this->assertIdentical($query->asString(), '');
        }
        function testSingleParameter() {
            $query = &new SimpleQueryString();
            $query->add('a', 'Hello');
            $this->assertIdentical($query->asString(), 'a=Hello');
        }
    }
    
?>