<?php declare(strict_types=1);

/**
 * Static methods for compatibility between different PHP versions.
 */
class SimpleTestCompatibility
{
    /**
     * Recursive type test.
     *
     * @param mixed $first  test subject
     * @param mixed $second comparison object
     *
     * @return bool true if same type
     */
    public static function isIdentical($first, $second)
    {
        if (\gettype($first) !== \gettype($second)) {
            return false;
        }

        if (\is_object($first) && \is_object($second)) {
            if ($first::class !== $second::class) {
                return false;
            }

            return self::isArrayOfIdenticalTypes(
                (array) $first,
                (array) $second,
            );
        }

        if (\is_array($first) && \is_array($second)) {
            return self::isArrayOfIdenticalTypes($first, $second);
        }

        return $first === $second;
    }

    /**
     * Test for two variables being aliases.
     *
     * @param mixed $first  test subject
     * @param mixed $second comparison object
     *
     * @return bool true if same
     */
    public static function isReference(&$first, &$second)
    {
        if ($first !== $second) {
            return false;
        }
        $temp_first = $first;
        // modify $first
        $first = true !== $first;
        // after modifying $first, $second will not be equal to $first,
        // unless $second and $first points to the same variable.
        $is_ref = ($first === $second);
        // unmodify $first
        $first = $temp_first;

        return $is_ref;
    }

    /**
     * Recursive type test for each element of an array.
     *
     * @param mixed $first  test subject
     * @param mixed $second comparison object
     *
     * @return bool true if identical
     */
    protected static function isArrayOfIdenticalTypes($first, $second)
    {
        if (\array_keys($first) !== \array_keys($second)) {
            return false;
        }

        foreach (\array_keys($first) as $key) {
            $is_identical = self::isIdentical(
                $first[$key],
                $second[$key],
            );

            if (!$is_identical) {
                return false;
            }
        }

        return true;
    }
}
