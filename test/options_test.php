<?php
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'options.php');
    
    class TestOfOptions extends UnitTestCase {
        function TestOfOptions() {
            $this->UnitTestCase();
        }
        function testMockBase() {
            $old_class = SimpleTestOptions::getMockBaseClass();
            SimpleTestOptions::setMockBaseClass('Fred');
            $this->assertEqual(SimpleTestOptions::getMockBaseClass(), 'Fred');
            SimpleTestOptions::setMockBaseClass($old_class);
        }
        function testIgnoreList() {
            $this->assertFalse(SimpleTestOptions::isIgnored('ImaginaryTestCase'));
            SimpleTestOptions::ignore('ImaginaryTestCase');
            $this->assertTrue(SimpleTestOptions::isIgnored('ImaginaryTestCase'));
        }
    }
?>