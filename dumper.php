<?php
    // $Id$
    
    define('TYPE_MATTERS', true);
    
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
         *    @access public
         */
        function describeValue($value) {
            $type = $this->getType($value);
            switch($type) {
                case "NULL":
                    return $type;
                case "Boolean":
                    return "Boolean: " . ($value ? "true" : "false");
                case "Array":
                    return "Array: " . count($value) . " items";
                case "Object":
                    return "Object: of " . get_class($value);
                case "String":
                    return "String: " . $this->clipString($value, 100);
                default:
                    return "$type: $value";
            }
            return "Unknown";
        }
        
        /**
         *    Gets the string representation of a type.
         *    @param $value    Variable to check against.
         *    @return          Type as string.
         *    @access public
         */
        function getType($value) {
            if (!isset($value)) {
                return "NULL";
            } elseif (is_bool($value)) {
                return "Boolean";
            } elseif (is_string($value)) {
                return "String";
            } elseif (is_integer($value)) {
                return "Integer";
            } elseif (is_float($value)) {
                return "Float";
            } elseif (is_array($value)) {
                return "Array";
            } elseif (is_resource($value)) {
                return "Resource";
            } elseif (is_object($value)) {
                return "Object";
            }
            return "Unknown";
        }

        /**
         *    Creates a human readable description of the
         *    difference between two variables. Uses a
         *    dynamic call.
         *    @param $first        First variable.
         *    @param $second       Value to compare with.
         *    @param $identical    If true then type anomolies count.
         *    @return              Descriptive string.
         *    @access public
         */
        function describeDifference($first, $second, $identical = false) {
            if ($identical) {
                if (! $this->_isTypeMatch($first, $second)) {
                    return "with type mismatch as [" . $this->describeValue($first) .
                        "] does not match [" . $this->describeValue($second) . "]";
                }
            }
            $type = $this->getType($first);
            if ($type == "Unknown") {
                return "with unknown type";
            }
            $method = '_describe' . $type . 'Difference';
            return $this->$method($first, $second, $identical);
        }
        
        /**
         *    Tests to see if types match.
         *    @param $first        First variable.
         *    @param $second       Value to compare with.
         *    @return              True if matches.
         *    @access private
         */
        function _isTypeMatch($first, $second) {
            return ($this->getType($first) == $this->getType($second));
        }

        /**
         *    Clips a string to a maximum length.
         *    @param $value        String to truncate.
         *    @param $size         Minimum string size to show.
         *    @param $position     Centre of string section.
         *    @return              Shortened version.
         *    @access public
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
         *    difference between a null and another variable.
         *    @param $first       First null.
         *    @param $second      Null to compare with.
         *    @param $identical   If true then type anomolies count.
         *    @return             Descriptive string.
         *    @access private
         *    @static
         */
        function _describeNullDifference($first, $second, $identical) {
            return "as [" . $this->describeValue($first) .
                    "] does not match [" .
                    $this->describeValue($second) . "]";
        }
        
        /**
         *    Creates a human readable description of the
         *    difference between a boolean and another variable.
         *    @param $first       First boolean.
         *    @param $second      Boolean to compare with.
         *    @param $identical   If true then type anomolies count.
         *    @return             Descriptive string.
         *    @access private
         *    @static
         */
        function _describeBooleanDifference($first, $second, $identical) {
            return "as [" . $this->describeValue($first) .
                    "] does not match [" .
                    $this->describeValue($second) . "]";
        }
        
        /**
         *    Creates a human readable description of the
         *    difference between a string and another variable.
         *    @param $first       First string.
         *    @param $second      String to compare with.
         *    @param $identical   If true then type anomolies count.
         *    @return             Descriptive string.
         *    @access private
         *    @static
         */
        function _describeStringDifference($first, $second, $identical) {
            $position = $this->_stringDiffersAt($first, $second);
            return "at character $position with [" .
                    $this->clipString($first, 100, $position) . "] and [" .
                    $this->clipString($second, 100, $position) . "]";
        }
        
        /**
         *    Creates a human readable description of the
         *    difference between an integer and another variable.
         *    @param $first       First number.
         *    @param $second      Number to compare with.
         *    @param $identical   If true then type anomolies count.
         *    @return             Descriptive string.
         *    @access private
         *    @static
         */
        function _describeIntegerDifference($first, $second, $identical) {
            return "because [" . $this->describeValue($first) .
                    "] differs from [" .
                    $this->describeValue($second) . "] by " .
                    abs($first - $second);
        }
        
        /**
         *    Creates a human readable description of the
         *    difference between two floating point numbers.
         *    @param $first       First float.
         *    @param $second      Float to compare with.
         *    @param $identical   If true then type anomolies count.
         *    @return             Descriptive string.
         *    @access private
         *    @static
         */
        function _describeFloatDifference($first, $second, $identical) {
            return "because " . $this->describeValue($first) .
                    "] differs from [" .
                    $this->describeValue($second) . "]";
        }
        
        /**
         *    Creates a human readable description of the
         *    difference between two arrays.
         *    @param $first       First array.
         *    @param $second      Array to compare with.
         *    @param $identical   If true then type anomolies count.
         *    @return             Descriptive string.
         *    @access private
         *    @static
         */
        function _describeArrayDifference($first, $second, $identical) {
            if (! is_array($second)) {
                return " as " . $this->describeValue($first) .
                        "] differs from [" .
                        $this->describeValue($second) . "]";
            }
            if (array_keys($first) != array_keys($second)) {
                return "as key list [" .
                        implode(", ", array_keys($first)) . "] does not match key list [" .
                        implode(", ", array_keys($second)) . "]";
            }
            foreach (array_keys($first) as $key) {
                if ($identical && ($first[$key] === $second[$key])) {
                    continue;
                }
                if (!$identical && ($first[$key] == $second[$key])) {
                    continue;
                }
                return "with member [$key] " . $this->describeDifference(
                        $first[$key],
                        $second[$key],
                        $identical);
            }
            return "";
        }
        
        /**
         *    Creates a human readable description of the
         *    difference between a resource and another variable.
         *    @param $first       First resource.
         *    @param $second      Resource to compare with.
         *    @param $identical   If true then type anomolies count.
         *    @return             Descriptive string.
         *    @access private
         *    @static
         */
        function _describeResourceDifference($first, $second, $identical) {
            return "as [" . $this->describeValue($first) .
                    "] does not match [" .
                    $this->describeValue($second) . "]";
        }
        
        /**
         *    Creates a human readable description of the
         *    difference between two objects.
         *    @param $first       First object.
         *    @param $second      Object to compare with.
         *    @param $identical   If true then type anomolies count.
         *    @return             Descriptive string.
         *    @access private
         *    @static
         */
        function _describeObjectDifference($first, $second, $identical) {
            if (! is_object($second)) {
                return " as " . $this->describeValue($first) .
                        "] differs from [" .
                        $this->describeValue($second) . "]";
            }
            return $this->_describeArrayDifference(
                    get_object_vars($first),
                    get_object_vars($second),
                    $identical);
        }
        
        /**
         *    Find the first character position that differs
         *    in two strings by binary chop.
         *    @param $first        First string.
         *    @param $second       String to compare with.
         *    @return              Integer position.
         *    @access private
         *    @static
         */
        function _stringDiffersAt($first, $second) {
            if (! $first || ! $second) {
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