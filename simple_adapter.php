<?php
    // $Id$
    
    /**
     *    Wrapper for test cases to allow legacy
     *    test cases to be treated as SimpleTest
     *    cases and be picked up in group tests.
     */
    class TestCaseAdapter {
        
        /**
         *    Instantiates a test case to chain to.
         *    @param $name        Name of test case.
         *    @public
         */
        function TestCaseAdapter($name) {
        }
    }
?>