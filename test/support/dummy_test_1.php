<?php
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../../");
    }

    class DummyTestOneA extends SimpleTestCase {
        function DummyTestOneA() {
            $this->SimpleTestCase();
        }
        function testOneA() {
            $this->assertTrue(true, "True");
        }
    }

    class DummyTestOneB extends SimpleTestCase {
        function DummyTestOneB() {
            $this->SimpleTestCase();
        }
        function testOneB() {
            $this->assertTrue(true, "True");
        }
    }    
?>