<?php
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'tag.php');
    
    class TestOfTag extends UnitTestCase {
        function TestOfTag() {
            $this->UnitTestCase();
        }
        function testStartValues() {
            $tag = new SimpleTag("hello", array("a" => 1, "b" => true));
            $this->assertEqual($tag->getname(), "hello");
            $this->assertIdentical($tag->getAttribute("a"), "1");
            $this->assertIdentical($tag->getAttribute("b"), true);
            $this->assertIdentical($tag->getAttribute("c"), false);
            $this->assertIdentical($tag->getContent(), "");
        }
        function testContent() {
            $tag = new SimpleTag("a", array());
            $tag->addContent("Hello");
            $tag->addContent("World");
            $this->assertEqual($tag->getContent(), "HelloWorld");
        }
    }
?>