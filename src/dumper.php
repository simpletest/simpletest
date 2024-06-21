<?php

/**
 * does type matter.
 */
if (!defined('TYPE_MATTERS')) {
    define('TYPE_MATTERS', true);
}

/**
 * Displays variables as text and does diffs.
 */
class SimpleDumper
{
    /**
     * Renders a variable in a shorter form than print_r().
     *
     * @param mixed $value variable to render as a string
     *
     * @return string human readable string form
     */
    public function describeValue($value)
    {
        $type = $this->getType($value);
        switch ($type) {
            case 'Null':
                return 'NULL';
            case 'bool':
                return 'bool: '.($value ? 'true' : 'false');
            case 'Array':
                return 'Array: '.count($value).' items';
            case 'Object':
                return 'Object: of '.get_class($value);
            case 'String':
                return 'String: '.$this->clipString($value, 200);
            default:
                return $type.': '.$value;
        }
    }

    /**
     * Gets the string representation of a type.
     *
     * @param mixed $value variable to check against
     *
     * @return string type
     */
    public function getType($value)
    {
        if (!isset($value)) {
            return 'Null';
        } elseif (is_bool($value)) {
            return 'bool';
        } elseif (is_string($value)) {
            return 'String';
        } elseif (is_integer($value)) {
            return 'Integer';
        } elseif (is_float($value)) {
            return 'Float';
        } elseif (is_array($value)) {
            return 'Array';
        } elseif (is_resource($value)) {
            return 'Resource';
        } elseif (is_object($value)) {
            return 'Object';
        }

        return 'Unknown';
    }

    /**
     * Creates a human readable description of the difference between two variables.
     * Uses a dynamic call.
     *
     * @param mixed $first     first variable
     * @param mixed $second    value to compare with
     * @param bool  $identical if true then type anomolies count
     *
     * @return string description of difference
     */
    public function describeDifference($first, $second, $identical = false)
    {
        if ($identical) {
            if (!$this->isTypeMatch($first, $second)) {
                return sprintf(
                    'with type mismatch as [%s] does not match [%s]',
                    $this->describeValue($first),
                    $this->describeValue($second)
                );
            }
        }
        $type = $this->getType($first);
        if ('Unknown' === $type) {
            return 'with unknown type';
        }
        $method = 'describe'.$type.'Difference';

        return $this->$method($first, $second, $identical);
    }

    /**
     * Tests to see if types match.
     *
     * @param mixed $first  first variable
     * @param mixed $second value to compare with
     *
     * @return bool true if matches
     */
    protected function isTypeMatch($first, $second)
    {
        return $this->getType($first) == $this->getType($second);
    }

    /**
     * Clips a string to a maximum length.
     *
     * @param string $string   string to truncate
     * @param int    $size     minimum string size to show
     * @param int    $position centre of string section
     *
     * @return string shortened version
     */
    public function clipString($string, $size, $position = 0)
    {
        $utf8 = false;
        if (function_exists('mb_detect_encoding') && 'UTF-8' == mb_detect_encoding($string)) {
            $utf8 = true;
        }

        $length = (true == $utf8) ? mb_strlen($string, 'UTF-8') : strlen($string);

        if ($length <= $size) {
            return $string;
        }

        $position = min($position, $length);
        $half_size = (int) $size / 2;
        $start = (int) ($half_size > $position ? 0 : $position - $half_size);
        if ($start + $size > $length) {
            $start = $length - $size;
        }
        $string = (true == $utf8) ? mb_substr($string, $start, $size) : substr($string, $start, $size);

        return ($start > 0 ? '...' : '').$string.($start + $size < $length ? '...' : '');
    }

    /**
     * Creates a human readable description of the difference between two variables.
     * The minimal version.
     *
     * @param mixed $first  first value
     * @param mixed $second value to compare with
     *
     * @return string human readable description
     */
    protected function describeGenericDifference($first, $second)
    {
        return sprintf(
            'as [%s] does not match [%s]',
            $this->describeValue($first),
            $this->describeValue($second)
        );
    }

    /**
     * Creates a human readable description of the difference between a null and another variable.
     *
     * @param null  $first     first null
     * @param mixed $second    null to compare with
     * @param bool  $identical if true then type anomolies count
     *
     * @return string human readable description
     */
    protected function describeNullDifference($first, $second, $identical)
    {
        return $this->describeGenericDifference($first, $second);
    }

    /**
     * Creates a human readable description of the difference between a bool and another
     * variable.
     *
     * @param bool  $first     first bool
     * @param mixed $second    bool to compare with
     * @param bool  $identical if true then type anomolies count
     *
     * @return string human readable description
     */
    protected function describeboolDifference($first, $second, $identical)
    {
        return $this->describeGenericDifference($first, $second);
    }

    /**
     * Creates a human readable description of the difference between
     * a string and another variable.
     *
     * @param string $first     first string
     * @param mixed  $second    string to compare with
     * @param bool   $identical if true then type anomolies count
     *
     * @return string human readable description
     */
    protected function describeStringDifference($first, $second, $identical)
    {
        if (is_object($second) || is_array($second)) {
            return $this->describeGenericDifference($first, $second);
        }
        $position = $this->stringDiffersAt($first, $second);

        return sprintf(
            'at character %s with [%s] and [%s]',
            $position,
            $this->clipString($first, 200, $position),
            $this->clipString($second, 200, $position)
        );
    }

    /**
     * Creates a human readable description of the difference between
     * an integer and another variable.
     *
     * @param int   $first     first number
     * @param mixed $second    number to compare with
     * @param bool  $identical if true then type anomolies count
     *
     * @return string human readable description
     */
    protected function describeIntegerDifference($first, $second, $identical)
    {
        if (is_object($second) || is_array($second)) {
            return $this->describeGenericDifference($first, $second);
        }

        return sprintf(
            'because [%s] differs from [%s] by %s',
            $this->describeValue($first),
            $this->describeValue($second),
            abs($first - $second)
        );
    }

    /**
     * Creates a human readable description of the difference between
     * two floating point numbers.
     *
     * @param float $first     first float
     * @param mixed $second    float to compare with
     * @param bool  $identical if true then type anomolies count
     *
     * @return string human readable description
     */
    protected function describeFloatDifference($first, $second, $identical)
    {
        if (is_object($second) || is_array($second)) {
            return $this->describeGenericDifference($first, $second);
        }

        return sprintf(
            'because [%s] differs from [%s] by %s',
            $this->describeValue($first),
            $this->describeValue($second),
            abs($first - $second)
        );
    }

    /**
     * Creates a human readable description of the difference between two arrays.
     *
     * @param array $first     first array
     * @param mixed $second    array to compare with
     * @param bool  $identical if true then type anomolies count
     *
     * @return string human readable description
     */
    protected function describeArrayDifference($first, $second, $identical)
    {
        if (!is_array($second)) {
            return $this->describeGenericDifference($first, $second);
        }
        if (!$this->isMatchingKeys($first, $second, $identical)) {
            return sprintf(
                'as key list [%s] does not match key list [%s]',
                implode(', ', array_keys($first)),
                implode(', ', array_keys($second))
            );
        }
        foreach (array_keys($first) as $key) {
            if ($identical && ($first[$key] === $second[$key])) {
                continue;
            }
            if (!$identical && ($first[$key] == $second[$key])) {
                continue;
            }

            return sprintf(
                'with member [%s] %s',
                $key,
                $this->describeDifference($first[$key], $second[$key], $identical)
            );
        }

        return '';
    }

    /**
     * Compares two arrays to see if their key lists match.
     * For an identical match, the ordering and types of the keys is significant.
     *
     * @param array $first     first array
     * @param array $second    array to compare with
     * @param bool  $identical if true then type anomolies count
     *
     * @return bool true if matching
     */
    protected function isMatchingKeys($first, $second, $identical)
    {
        $first_keys = array_keys($first);
        $second_keys = array_keys($second);
        if ($identical) {
            return $first_keys === $second_keys;
        }
        sort($first_keys);
        sort($second_keys);

        return $first_keys == $second_keys;
    }

    /**
     * Creates a human readable description of the difference between
     * a resource and another variable.
     *
     * @param resource $first     first resource
     * @param mixed    $second    resource to compare with
     * @param bool     $identical if true then type anomolies count
     *
     * @return string human readable description
     */
    protected function describeResourceDifference($first, $second, $identical)
    {
        return $this->describeGenericDifference($first, $second);
    }

    /**
     * Creates a human readable description of the difference between two objects.
     *
     * @param object $first     first object
     * @param mixed  $second    object to compare with
     * @param bool   $identical if true then type anomolies count
     *
     * @return string human readable description
     */
    protected function describeObjectDifference($first, $second, $identical)
    {
        if (!is_object($second)) {
            return $this->describeGenericDifference($first, $second);
        }

        return $this->describeArrayDifference(
            $this->getMembers($first),
            $this->getMembers($second),
            $identical
        );
    }

    /**
     * Get all members of an object including private and protected ones.
     * A safer form of casting to an array.
     *
     * @param object $object object to list members of, including private ones
     *
     * @return array names and values in the object
     */
    protected function getMembers($object)
    {
        $reflection = new ReflectionObject($object);
        $members = [];
        foreach ($reflection->getProperties() as $property) {
            if (method_exists($property, 'setAccessible')) {
                $property->setAccessible(true);
            }
            try {
                $members[$property->getName()] = $property->getValue($object);
            } catch (ReflectionException $e) {
                $members[$property->getName()] =
                    $this->getPrivatePropertyNoMatterWhat($property->getName(), $object);
            }
        }

        return $members;
    }

    /**
     * Extracts a private member's value when reflection won't play ball.
     *
     * @param string $name   property name
     * @param object $object object to read
     *
     * @return mixed value of property
     */
    private function getPrivatePropertyNoMatterWhat($name, $object)
    {
        foreach ((array) $object as $mangled_name => $value) {
            if ($this->unmangle($mangled_name) == $name) {
                return $value;
            }
        }
    }

    /**
     * Removes crud from property name after it's been converted to an array.
     *
     * @param string $mangled name from array cast
     *
     * @return string cleaned up name
     */
    public function unmangle($mangled)
    {
        $parts = preg_split('/[^a-zA-Z0-9_\x7f-\xff]+/', $mangled);

        return array_pop($parts);
    }

    /**
     * Find the first character position that differs in two strings by binary chop.
     *
     * @param string $first  first string
     * @param string $second string to compare with
     *
     * @return int position of first differing character
     */
    protected function stringDiffersAt($first, $second)
    {
        if (!$first || !$second) {
            return 0;
        }
        if (strlen($first) < strlen($second)) {
            [$first, $second] = [$second, $first];
        }
        $position = 0;
        $step = strlen($first);
        while ($step > 1) {
            $step = (int) (($step + 1) / 2);
            if (0 == strncmp($first, $second, $position + $step)) {
                $position += $step;
            }
        }

        return $position;
    }

    /**
     * Sends a formatted dump of a variable to a string.
     *
     * @param mixed $variable variable to display
     *
     * @return string output from print_r()
     */
    public function dump($variable)
    {
        ob_start();
        print_r($variable);
        $formatted = ob_get_contents();
        ob_end_clean();

        return $formatted;
    }
}
