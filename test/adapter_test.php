<?php
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'simple_adapter.php');

    class TestOfAdapter extends UnitTestCase {
        function TestOfAdapter() {
            $this->UnitTestCase();
        }
    }
    
?>