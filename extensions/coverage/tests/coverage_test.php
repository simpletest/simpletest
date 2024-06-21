<?php

require_once __DIR__ . '/../../../src/autorun.php';
require_once __DIR__ . '/../../../src/mock_objects.php';

class CodeCoverageTest extends UnitTestCase
{
    public function skip()
    {
        $this->skipIf(
            !extension_loaded('sqlite3'),
            'The Coverage extension requires the PHP extension "php_sqlite3".'
        );
    }

    protected function setUp()
    {
        require_once __DIR__ . '/../coverage.php';
    }

    public function testIsFileIncluded()
    {
        $coverage = new CodeCoverage();
        $this->assertTrue($coverage->isFileIncluded('aaa'));
        $coverage->includes = ['a'];
        $this->assertTrue($coverage->isFileIncluded('aaa'));
        $coverage->includes = ['x'];
        $this->assertFalse($coverage->isFileIncluded('aaa'));
        $coverage->excludes = ['aa'];
        $this->assertFalse($coverage->isFileIncluded('aaa'));
    }

    public function testIsFileIncludedRegexp()
    {
        $coverage           = new CodeCoverage();
        $coverage->includes = ['modules/.*\.php$'];
        $coverage->excludes = ['bad-bunny.php'];
        $this->assertFalse($coverage->isFileIncluded('modules/a.test'));
        $this->assertFalse($coverage->isFileIncluded('modules/bad-bunny.test'));
        $this->assertTrue($coverage->isFileIncluded('modules/test.php'));
        $this->assertFalse($coverage->isFileIncluded('module-bad/good-bunny.php'));
        $this->assertTrue($coverage->isFileIncluded('modules/good-bunny.php'));
    }

    public function testIsDirectoryIncludedPastMaxDepth()
    {
        $coverage                    = new CodeCoverage();
        $coverage->maxDirectoryDepth = 5;
        $this->assertTrue($coverage->isDirectoryIncluded('aaa', 1));
        $this->assertFalse($coverage->isDirectoryIncluded('aaa', 5));
    }

    public function testIsDirectoryIncluded()
    {
        $coverage = new CodeCoverage();
        $this->assertTrue($coverage->isDirectoryIncluded('aaa', 0));
        $coverage->excludes = ['b$'];
        $this->assertTrue($coverage->isDirectoryIncluded('aaa', 0));
        $coverage->includes = ['a$']; // includes are ignore, all dirs are included unless excluded
        $this->assertTrue($coverage->isDirectoryIncluded('aaa', 0));
        $coverage->excludes = ['.*a$'];
        $this->assertFalse($coverage->isDirectoryIncluded('aaa', 0));
    }

    public function testFilter()
    {
        $coverage           = new CodeCoverage();
        $data               = ['a' => 0, 'b' => 0, 'c' => 0];
        $coverage->includes = ['b'];
        $coverage->filter($data);
        $this->assertEqual(['b' => 0], $data);
    }

    public function testUntouchedFiles()
    {
        $coverage           = new CodeCoverage();
        $touched            = array_flip(['test/coverage_test.php']);
        $actual             = [];
        $coverage->includes = ['coverage_test\.php$'];
        $parentDir          = realpath(__DIR__);
        $coverage->getUntouchedFiles($actual, $touched, $parentDir, $parentDir);
        $this->assertEqual(['coverage_test.php'], $actual);
    }

    public function testResetLog()
    {
        $coverage      = new CodeCoverage();
        $coverage->log = tempnam(null, 'php.xdebug.coverage.test.');
        $coverage->resetLog();
        $this->assertTrue(file_exists($coverage->log));
    }

    public function testSettingsSerialization()
    {
        $coverage           = new CodeCoverage();
        $coverage->log      = sys_get_temp_dir();
        $coverage->includes = ['apple', 'orange'];
        $coverage->excludes = ['tomato', 'pea'];
        $data               = $coverage->getSettings();
        $this->assertNotNull($data);

        $actual = new CodeCoverage();
        $actual->setSettings($data);
        $this->assertEqual(sys_get_temp_dir(), $actual->log);
        $this->assertEqual(['apple', 'orange'], $actual->includes);
        $this->assertEqual(['tomato', 'pea'], $actual->excludes);
    }

    public function testSettingsCanBeReadWrittenToDisk()
    {
        $settings_file = '0-coverage-settings-test.dat';

        $coverage               = new CodeCoverage();
        $coverage->log          = sys_get_temp_dir();
        $coverage->settingsFile = $settings_file;
        $coverage->writeSettings();

        $actual               = new CodeCoverage();
        $actual->settingsFile = $settings_file;
        $actual->readSettings();
        $this->assertEqual(sys_get_temp_dir(), $actual->log);

        unlink($settings_file);
    }
}
