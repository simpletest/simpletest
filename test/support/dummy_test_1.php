<?php
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../../");
    }

    class DummyTestOneA extends TestCase {
        function DummyTestOneA() {
            $this->TestCase();
        }
        function testOneA() {
            $this->assertTrue(true, "True");
        }
    }

    class DummyTestOneB extends TestCase {
        function DummyTestOneB() {
            $this->TestCase();
        }
        function testOneB() {
            $this->assertTrue(true, "True");
        }
    }    
?>