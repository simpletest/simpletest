<?php
/**
 * Static methods for compatibility between different PHP versions.
 */
class SimpleTestCompatibility
{
    /**
     * Recursive type test.
     *
     * @param mixed $first    Test subject.
     * @param mixed $second   Comparison object.
     *
     * @return bool        True if same type.
     */
    public static function isIdentical($first, $second)
    {
        if (gettype($first) != gettype($second)) {
            return false;
        }
        if (is_object($first) && is_object($second)) {
            if (get_class($first) != get_class($second)) {
                return false;
            }

            return self::isArrayOfIdenticalTypes(
                    (array) $first,
                    (array) $second);
        }
        if (is_array($first) && is_array($second)) {
            return self::isArrayOfIdenticalTypes($first, $second);
        }
        if ($first !== $second) {
            return false;
        }

        return true;
    }

    /**
     * Recursive type test for each element of an array.
     *
     * @param mixed $first    Test subject.
     * @param mixed $second   Comparison object.
     *
     * @return bool        True if identical.
     */
    protected static function isArrayOfIdenticalTypes($first, $second)
    {
        if (array_keys($first) != array_keys($second)) {
            return false;
        }
        foreach (array_keys($first) as $key) {
            $is_identical = self::isIdentical(
                    $first[$key],
                    $second[$key]);
            if (! $is_identical) {
                return false;
            }
        }

        return true;
    }

    /**
     * Test for two variables being aliases.
     *
     * @param mixed $first    Test subject.
     * @param mixed $second   Comparison object.
     *
     * @return bool        True if same.
     */
    public static function isReference(&$first, &$second)
    {
        if (is_object($first)) {
            return ($first === $second);
        }
        if (is_object($first) && is_object($second)) {
            $id         = uniqid(mt_rand());
            $first->$id = true;
            $is_ref     = isset($second->$id);
            unset($first->$id);

            return $is_ref;
        }
        $temp   = $first;
        $first  = uniqid(mt_rand());
        $is_ref = ($first === $second);
        $first  = $temp;

        return $is_ref;
    }
}
