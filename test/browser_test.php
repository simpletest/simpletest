<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'browser.php');

    class TestOfBrowser extends UnitTestCase {
        function TestOfBrowser() {
            $this->UnitTestCase();
        }
    }
?>