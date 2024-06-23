<?php declare(strict_types=1);
/**
 * This file contains the following classes: {@link SimpleCollector},
 * {@link SimplePatternCollector}.
 *
 * @author Travis Swicegood <development@domain51.com>
 */

/**
 * The basic collector for {@link GroupTest}.
 *
 * @see collect(), GroupTest::collect()
 */
class SimpleCollector
{
    /**
     * Scans the directory and adds what it can.
     *
     * @param object $test group test with {@link GroupTest::addFile)} method
     * @param string $path directory to scan
     *
     * @see _attemptToAdd()
     */
    public function collect(&$test, $path): void
    {
        $path = $this->removeTrailingSlash($path);

        if ($handle = \opendir($path)) {
            while (false !== ($entry = \readdir($handle))) {
                if ($this->isHidden($entry)) {
                    continue;
                }
                $this->handle($test, $path . DIRECTORY_SEPARATOR . $entry);
            }
            \closedir($handle);
        }
    }

    /**
     * Strips off any kind of slash at the end so as to normalise the path.
     *
     * @param string $path path to normalise
     *
     * @return string path without trailing slash
     */
    protected function removeTrailingSlash($path)
    {
        if (DIRECTORY_SEPARATOR === \substr($path, -1)) {
            return \substr($path, 0, -1);
        }

        if ('/' === \substr($path, -1)) {
            return \substr($path, 0, -1);
        }

        return $path;

    }

    /**
     * This method determines what should be done with a given file and adds
     * it via {@link GroupTest::addFile)} if necessary.
     *
     * This method should be overriden to provide custom matching criteria,
     * such as pattern matching, recursive matching, etc.  For an example, see
     * {@link SimplePatternCollector::_handle()}.
     *
     * @param object $test group test with {@link GroupTest::addFile)} method
     * @param string $file
     *
     * @see collect()
     */
    protected function handle(&$test, $file): void
    {
        if (\is_dir($file)) {
            return;
        }
        $test->addFile($file);
    }

    /**
     * Tests for hidden files so as to skip them.
     * Currently only tests for Unix hidden files.
     *
     * @param string $filename plain filename
     *
     * @return bool true if hidden file
     */
    protected function isHidden($filename)
    {
        return 0 == \strncmp($filename, '.', 1);
    }
}

/**
 * An extension to {@link SimpleCollector} that only adds files matching a given pattern.
 *
 * See {@link http://php.net/manual/en/reference.pcre.pattern.syntax.php PHP's PCRE}
 * for documentation of valid patterns.
 *
 * @see SimpleCollector
 */
class SimplePatternCollector extends SimpleCollector
{
    /** @var string */
    private $pattern;

    /**
     * @param string $pattern Perl compatible regex to test name against
     */
    public function __construct($pattern = '/php$/i')
    {
        $this->pattern = $pattern;
    }

    /**
     * Attempts to add files that match a given pattern.
     *
     * @see SimpleCollector::_handle()
     *
     * @param object $test group test with {@link GroupTest::addFile)} method
     * @param string $file
     */
    protected function handle(&$test, $file): void
    {
        if (\preg_match($this->pattern, $file)) {
            parent::handle($test, $file);
        }
    }
}
