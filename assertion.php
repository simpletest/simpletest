<?php
    // $Id$
    
    /**
     *    Assertion that can display failure information.
     *    @abstract
     */
    class SimpleAssertion {
        
        /**
         *    Does nothing.
         */
        function SimpleAssertion() {
        }
        
        /**
         *    Tests the assertion. True if correct.
         *    @param $compare        Comparison value.
         *    @return                True if correct.
         *    @public
         */
        function is($compare) {
        }
    }
    
    /**
     *    Test for equality.
     */
    class EqualityAssertion extends SimpleAssertion {
        var $_value;
        
        /**
         *    Sets the value to compare against.
         *    @param $value        Test value to match.
         *    @public
         */
        function EqualityAssertion($value) {
            $this->_value = $value;
        }
        
        /**
         *    Tests the assertion. True if it matches the
         *    held value.
         *    @param $compare        Comparison value.
         *    @return                True if correct.
         *    @public
         */
        function is($compare) {
            return ($this->_value == $compare);
        }
    }
?>