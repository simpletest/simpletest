<?php
    // $Id$
    
    class TestOfTextFormatting extends UnitTestCase {
        function TestOfTextFormatting() {
            $this->UnitTestCase();
        }
        function testClipping() {
            $this->assertEqual(
                    SimpleExpectation::clipString("Hello", 6),
                    "Hello",
                    "Hello, 6->%s");
            $this->assertEqual(
                    SimpleExpectation::clipString("Hello", 5),
                    "Hello",
                    "Hello, 5->%s");
            $this->assertEqual(
                    SimpleExpectation::clipString("Hello world", 3),
                    "Hel...",
                    "Hello world, 3->%s");
            $this->assertEqual(
                    SimpleExpectation::clipString("Hello world", 6, 3),
                    "Hello ...",
                    "Hello world, 6, 3->%s");
            $this->assertEqual(
                    SimpleExpectation::clipString("Hello world", 3, 6),
                    "...o w...",
                    "Hello world, 3, 6->%s");
            $this->assertEqual(
                    SimpleExpectation::clipString("Hello world", 4, 11),
                    "...orld",
                    "Hello world, 4, 11->%s");
            $this->assertEqual(
                    SimpleExpectation::clipString("Hello world", 4, 12),
                    "...orld",
                    "Hello world, 4, 12->%s");
        }
   }
?>