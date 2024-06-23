<?php declare(strict_types=1);

class CoverageUtils
{
    public static function reportFilename($file)
    {
        return \preg_replace('|[:/\\\\]|i', '_', $file) . '.html';
    }

    public static function mkdir($dir): void
    {
        if (!\file_exists($dir)) {
            \mkdir($dir, 0o777, true);
        } elseif (!\is_dir($dir)) {
            throw new Exception($dir . ' exists as a file, not a directory');
        }
    }

    public static function isPackageClassAvailable($file, $class)
    {
        @include_once $file;

        return \class_exists($class);
    }

    /**
     * Parses simple parameters from CLI.
     *
     * Puts trailing parameters into string array in 'extraArguments'
     *
     * Example:
     * $args = CoverageUtil::parseArguments($_SERVER['argv']);
     * if ($args['verbose']) echo "Verbose Mode On\n";
     * $files = $args['extraArguments'];
     *
     * Example CLI:
     *  --foo=blah -x -h  some trailing arguments
     *
     * if multiValueMode is true
     * Example CLI:
     *  --include=a --include=b --exclude=c
     * Then
     *  $args = CoverageUtil::parseArguments($_SERVER['argv']);
     *  $args['include[]'] will equal array('a', 'b')
     *  $args['exclude[]'] will equal array('c')
     *  $args['exclude'] will equal c
     *  $args['include'] will equal b   NOTE: only keeps last value
     *
     * @param array $argv
     * @param supportMutliValue - will store 2nd copy of value in an array with key "foo[]"
     *
     * @return array
     */
    public static function parseArguments($argv, $mutliValueMode = false)
    {
        $args                   = [];
        $args['extraArguments'] = [];
        \array_shift($argv); // scriptname

        foreach ($argv as $arg) {
            if (\preg_match('#^--([^=]+)=(.*)#', $arg, $reg)) {
                $args[$reg[1]] = $reg[2];

                if ($mutliValueMode) {
                    self::addItemAsArray($args, $reg[1], $reg[2]);
                }
            } elseif (\preg_match('#^[-]{1,2}([^[:blank:]]+)#', $arg, $reg)) {
                $nonnull       = '';
                $args[$reg[1]] = $nonnull;

                if ($mutliValueMode) {
                    self::addItemAsArray($args, $reg[1], $nonnull);
                }
            } else {
                $args['extraArguments'][] = $arg;
            }
        }

        return $args;
    }

    /**
     * Adds a value as an array of one, or appends to an existing array elements.
     *
     * @param unknown_type $array
     * @param unknown_type $item
     */
    public static function addItemAsArray(&$array, $key, $item): void
    {
        $array_key = $key . '[]';

        if (\array_key_exists($array_key, $array)) {
            $array[$array_key][] = $item;
        } else {
            $array[$array_key] = [$item];
        }
    }

    /**
     * isset function with default value.
     *
     * Example:  $z = CoverageUtils::issetOr($array[$key], 'no value given')
     *
     * @param unknown_type $val
     * @param unknown_type $default
     *
     * @return first value unless value is not set then returns 2nd arg or null if no 2nd arg
     */
    public static function issetOrDefault(&$val, $default = null)
    {
        return $val ?? $default;
    }
}
