<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'http.php');

    class HttpTestCase extends UnitTestCase {
        function HttpTestCase() {
            $this->UnitTestCase();
        }
        function testReadBadConnection() {
            $http = new HttpRequest("http://bad.page/");
        }
    }
    
?>