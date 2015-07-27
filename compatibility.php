<?php
/**
 *  base include file for SimpleTest
 *  @package    SimpleTest
 */

/**
 *  Static methods for compatibility between different PHP versions.
 *  @package    SimpleTest
 */
class SimpleTestCompatibility
{

    /**
     *    Creates a copy.
     *    @param object $object     Thing to copy.
     *    @return object            A copy.
     *    @access public
     */
    static function copy($object) {        
        $copy = clone $object;
        return $copy;        
    }

    /**
     *    Identity test. Drops back to equality + types for PHP5
     *    objects as the === operator counts as the
     *    stronger reference constraint.
     *    @param mixed $first    Test subject.
     *    @param mixed $second   Comparison object.
     *    @return boolean        True if identical.
     *    @access public
     */
    static function isIdentical($first, $second) {
        return SimpleTestCompatibility::isIdenticalType($first, $second);        
    }

    /**
     *    Recursive type test.
     *    @param mixed $first    Test subject.
     *    @param mixed $second   Comparison object.
     *    @return boolean        True if same type.
     *    @access private
     */
    protected static function isIdenticalType($first, $second) {
        if (gettype($first) != gettype($second)) {
            return false;
        }
        if (is_object($first) && is_object($second)) {
            if (get_class($first) != get_class($second)) {
                return false;
            }
            return SimpleTestCompatibility::isArrayOfIdenticalTypes(
                    (array) $first,
                    (array) $second);
        }
        if (is_array($first) && is_array($second)) {
            return SimpleTestCompatibility::isArrayOfIdenticalTypes($first, $second);
        }
        if ($first !== $second) {
            return false;
        }
        return true;
    }

    /**
     *    Recursive type test for each element of an array.
     *    @param mixed $first    Test subject.
     *    @param mixed $second   Comparison object.
     *    @return boolean        True if identical.
     *    @access private
     */
    protected static function isArrayOfIdenticalTypes($first, $second) {
        if (array_keys($first) != array_keys($second)) {
            return false;
        }
        foreach (array_keys($first) as $key) {
            $is_identical = SimpleTestCompatibility::isIdenticalType(
                    $first[$key],
                    $second[$key]);
            if (! $is_identical) {
                return false;
            }
        }
        return true;
    }

    /**
     *    Test for two variables being aliases.
     *    @param mixed $first    Test subject.
     *    @param mixed $second   Comparison object.
     *    @return boolean        True if same.
     *    @access public
     */
    static function isReference(&$first, &$second) {
        if (is_object($first)) {
            return ($first === $second);
        }
        if (is_object($first) && is_object($second)) {
            $id = uniqid("test");
            $first->$id = true;
            $is_ref = isset($second->$id);
            unset($first->$id);
            return $is_ref;
        }
        $temp = $first;
        $first = uniqid("test");
        $is_ref = ($first === $second);
        $first = $temp;
        return $is_ref;
    }

    /**
     *    Sets a socket timeout for each chunk.
     *    @param resource $handle    Socket handle.
     *    @param integer $timeout    Limit in seconds.
     *    @access public
     */
    static function setTimeout($handle, $timeout) {       
        stream_set_timeout($handle, $timeout, 0);        
    }
}