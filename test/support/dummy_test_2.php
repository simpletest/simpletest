<?php
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../../");
    }

    class DummyTestTwo extends TestCase {
        function DummyTestTwo() {
            $this->TestCase();
        }
        function testTwo() {
            $this->assertTrue(true, "True");
        }
    }
?>