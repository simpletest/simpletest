<?php
    // $Id$
    
    /**
     *    Displays variables as text and does diffs.
     */
    class SimpleDumper {
        
        /**
         *    Do nothing constructor.
         */
        function SimpleDumper() {
        }
        
        /**
         *    Renders a variable in a shorter form than print_r().
         *    @param $value      Variable to render as a string.
         *    @return            Human readable string form.
         *    @public
         *    @static
         */
        function describeValue($value) {
            if (!isset($value)) {
                return "NULL";
            } elseif (is_bool($value)) {
                return "Boolean: " . ($value ? "true" : "false");
            } elseif (is_string($value)) {
                return "String: " . $this->clipString($value, 40);
            } elseif (is_integer($value)) {
                return "Integer: $value";
            } elseif (is_float($value)) {
                return "Float: $value";
            } elseif (is_array($value)) {
                return "Array: " . count($value) . " items";
            } elseif (is_resource($value)) {
                return "Resource: $value";
            } elseif (is_object($value)) {
                return "Object: of " . get_class($value);
            }
            return "Unknown";
        }

        /**
         *    Creates a human readable description of the
         *    difference between two variables.
         *    @param $first             First variable.
         *    @param $second            Value to compare with.
         *    @return                   Descriptive string.
         *    @public
         *    @static
         */
        function describeDifference($first, $second) {
            if (!isset($first)) {
                return "as [" . $this->describeValue($first) .
                        "] does not match [" .
                        $this->describeValue($second) . "]";
            } elseif (is_bool($first)) {
                return "as [" . $this->describeValue($first) .
                        "] does not match [" .
                        $this->describeValue($second) . "]";
            } elseif (is_string($first)) {
                return $this->_describeStringDifference($first, $second);
            } elseif (is_integer($first)) {
                return $this->_describeIntegerDifference($first, $second);
            } elseif (is_float($first)) {
                return $this->_describeFloatDifference($first, $second);
            } elseif (is_array($first)) {
                return $this->_describeArrayDifference($first, $second);
            } elseif (is_resource($first)) {
                return "as [" . $this->describeValue($first) .
                        "] does not match [" . $this->describeValue($second) . "]";
            } elseif (is_object($first)) {
                return $this->_describeObjectDifference($first, $second);
            }
            return "by value";
        }

        /**
         *    Clips a string to a maximum length.
         *    @param $value        String to truncate.
         *    @param $size         Minimum string size to show.
         *    @param $position     Centre of string section.
         *    @return              Shortened version.
         *    @public
         *    @static
         */
        function clipString($value, $size, $position = 0) {
            $length = strlen($value);
            if ($length <= $size) {
                return $value;
            }
            $position = min($position, $length);
            $start = ($size/2 > $position ? 0 : $position - $size/2);
            if ($start + $size > $length) {
                $start = $length - $size;
            }
            $value = substr($value, $start, $size);
            return ($start > 0 ? "..." : "") . $value . ($start + $size < $length ? "..." : "");
        }
        
        /**
         *    Creates a human readable description of the
         *    difference between two integers.
         *    @param $first             First string.
         *    @param $second            String to compare with.
         *    @return                   Descriptive string.
         *    @private
         *    @static
         */
        function _describeStringDifference($first, $second) {
            $position = $this->_stringDiffersAt($first, $second);
            return "at character $position with [" .
                    $this->clipString($first, 100, $position) . "] and [" .
                    $this->clipString($second, 100, $position) . "]";
        }
        
        /**
         *    Creates a human readable description of the
         *    difference between two integers.
         *    @param $first             First number.
         *    @param $second            Number to compare with.
         *    @return                   Descriptive string.
         *    @private
         *    @static
         */
        function _describeIntegerDifference($first, $second) {
            return "because [" . $this->describeValue($first) .
                    "] differs from [" .
                    $this->describeValue($second) . "] by " .
                    abs($first - $second);
        }
        
        /**
         *    Creates a human readable description of the
         *    difference between two floating point numbers.
         *    @param $first             First float.
         *    @param $second            Float to compare with.
         *    @return                   Descriptive string.
         *    @private
         *    @static
         */
        function _describeFloatDifference($first, $second) {
            return "because [Float: " . $this->describeValue($first) .
                    "] differs from [" .
                    $this->describeValue($second) . "]";
        }
        
        /**
         *    Creates a human readable description of the
         *    difference between two arrays.
         *    @param $first             First array.
         *    @param $second            Array to compare with.
         *    @return                   Descriptive string.
         *    @private
         *    @static
         */
        function _describeArrayDifference($first, $second) {
            if (array_keys($first) != array_keys($second)) {
                return "as key list [" .
                        implode(", ", array_keys($first)) . "] does not match key list [" .
                        implode(", ", array_keys($second)) . "]";
            }
            foreach (array_keys($first) as $key) {
                $expectation_class = get_class($this);
                $test = &new $expectation_class($first[$key]);
                if (!$test->test($second[$key])) {
                    return "with member [$key] " . $this->describeDifference(
                            $first[$key],
                            $second[$key]);
                }
            }
            return "";
        }
        
        /**
         *    Creates a human readable description of the
         *    difference between two objects.
         *    @param $first             First object.
         *    @param $second            Object to compare with.
         *    @return                   Descriptive string.
         *    @private
         *    @static
         */
        function _describeObjectDifference($first, $second) {
            return $this->_describeArrayDifference(
                    get_object_vars($first),
                    get_object_vars($second));
        }
        
        /**
         *    Find the first character position that differs
         *    in two strings by binary chop.
         *    @param $first        First string.
         *    @param $second       String to compare with.
         *    @return              Integer position.
         *    @private
         *    @static
         */
        function _stringDiffersAt($first, $second) {
            if (!$first || !$second) {
                return 0;
            }
            if (strlen($first) < strlen($second)) {
                list($first, $second) = array($second, $first);
            }
            $position = 0;
            $step = strlen($first);
            while ($step > 1) {
                $step = (integer)(($step + 1)/2);
                if (strncmp($first, $second, $position + $step) == 0) {
                    $position += $step;
                }
            }
            return $position;
        }
    }
?>