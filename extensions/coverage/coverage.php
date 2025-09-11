<?php declare(strict_types=1);

require_once __DIR__ . '/coverage_data_handler.php';

/**
 * SimpleTest - CodeCoverage.
 */
class CodeCoverage
{
    public static $instance;
    public $log;
    public $root;
    public $includes;
    public $excludes;
    public $directoryDepth;
    public $maxDirectoryDepth = 20; // reasonable, otherwise arbitrary
    public $title             = 'Code Coverage';

    # NOTE: This assumes all code shares the same current working directory.
    public $settingsFile = './coverage-settings.json';

    public static function isCoverageOn()
    {
        $coverage = self::getInstance();

        if (empty($coverage->log) || !\file_exists($coverage->log)) {
            throw new Exception('Could not find the coverage log file.');
        }

        return true;
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self;
            self::$instance->readSettings();
        }

        return self::$instance;
    }

    public function writeUntouched(): void
    {
        $touched   = \array_flip($this->getTouchedFiles());
        $untouched = [];
        $this->getUntouchedFiles($untouched, $touched, '.', '.');
        $this->includeUntouchedFiles($untouched);
    }

    public function getTouchedFiles()
    {
        $handler = new CoverageDataHandler($this->log, $this->root ?? \getcwd());
        $files   = $handler->getFilenames();
        $handler->close();

        return $files;
    }

    public function includeUntouchedFiles($untouched): void
    {
        $handler = new CoverageDataHandler($this->log, $this->root ?? \getcwd());

        foreach ($untouched as $file) {
            $handler->writeUntouchedFile($file);
        }

        $handler->close();
    }

    public function getUntouchedFiles(&$untouched, $touched, $parentPath, $rootPath, $directoryDepth = 1): void
    {
        $parent = \opendir($parentPath);

        while ($file = \readdir($parent)) {
            $path = "{$parentPath}/{$file}";

            if (\is_dir($path)) {
                if ($file !== '.' && $file !== '..') {
                    if ($this->isDirectoryIncluded($path, $directoryDepth)) {
                        $this->getUntouchedFiles($untouched, $touched, $path, $rootPath, $directoryDepth + 1);
                    }
                }
            } elseif ($this->isFileIncluded($path)) {
                $relativePath = CoverageDataHandler::ltrim($rootPath . '/', $path);

                if (!\array_key_exists($relativePath, $touched)) {
                    $untouched[] = $relativePath;
                }
            }
        }
        \closedir($parent);
    }

    public function resetLog(): void
    {
        $file = \fopen($this->log, 'w');

        if (!$file) {
            throw new Exception('Could not create ' . $this->log);
        }
        \fclose($file);

        if (!\chmod($this->log, 0o666)) {
            throw new Exception('Could not change ownership on file  ' . $this->log);
        }
        $handler = new CoverageDataHandler($this->log, $this->root ?? \getcwd());
        $handler->createSchema();
    }

    public function isXdebugCoverageEnabled(): bool
    {
        $env  = $_ENV['XDEBUG_MODE'] ?? '';
        $mode = \ini_get('xdebug.mode');

        if (\version_compare(\phpversion('xdebug'), '3.0.0', '>=')) {
            if (!\str_contains($mode, 'coverage') && $env !== 'coverage') {
                return false;
            }
        }

        return true;
    }

    public function startCoverage(): void
    {
        $rootReal   = \realpath(\getcwd());
        $this->root = $rootReal !== false ? $rootReal : \getcwd();

        if (!\extension_loaded('xdebug')) {
            throw new Exception('The PHP extension XDebug is not loaded. It is required for CodeCoverage to work! Please adjust your php.ini.');
        }

        if ($this->isXdebugCoverageEnabled() === false) {
            throw new Exception('XDebug is loaded, but code coverage is not enabled. Please set the environment variable XDEBUG_MODE=coverage or adjust your php.ini to include "coverage" in the xdebug.mode setting.');
        }

        xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
    }

    public function stopCoverage(): void
    {
        $cov = xdebug_get_code_coverage();
        $this->filter($cov);
        $data = new CoverageDataHandler($this->log, $this->root ?? \getcwd());
        $data->write($cov);
        $data->close();
        unset($data); // release sqlite connection
        xdebug_stop_code_coverage();
    }

    public function readSettings(): void
    {
        if (!\file_exists($this->settingsFile)) {
            \error_log('Could not find settings file ' . $this->settingsFile);

            return;
        }

        $contents = \file_get_contents($this->settingsFile);

        if ($contents === false) {
            \error_log('Could not read settings file ' . $this->settingsFile);

            return;
        }

        $data = \json_decode($contents, true);

        if (!\is_array($data)) {
            \error_log('Settings file ' . $this->settingsFile . ' contains invalid JSON');

            return;
        }

        $this->setSettings($data);

        // Normalize excludes immediately after reading settings to keep a
        // consistent internal representation
        if (isset($this->excludes) && \is_array($this->excludes)) {
            $this->excludes = $this->normalizePatterns($this->excludes);
        }
    }

    /**
     * Normalize and deduplicate an array of regex patterns.
     * This keeps representations consistent across callers that may escape
     * slashes differently.
     */
    public function normalizePatterns(array $patterns): array
    {
        $normalized = [];

        foreach ($patterns as $pattern) {
            $p = \str_replace(['\\\\/', '\\/', '\\/'], '/', $pattern);
            $p = \trim($p);

            if ($p !== '' && !\in_array($p, $normalized, true)) {
                $normalized[] = $p;
            }
        }

        return $normalized;
    }

    public function writeSettings(): void
    {
        $data = $this->getSettings();

        // Ensure excludes are normalized and deduplicated so repeated runs don't
        // append duplicate patterns (some callers may pass differently escaped
        // strings). We only normalize escaped forward slashes here to avoid
        // touching other intentional backslashes in regex patterns.
        if (isset($data['excludes']) && \is_array($data['excludes'])) {
            $normalized = [];

            foreach ($data['excludes'] as $pattern) {
                // Replace both "\/" and "\\\/" sequences with a plain '/'
                $p = \str_replace(['\\\\/', '\\/', '\\/'], '/', $pattern);
                // Trim whitespace and keep order; use the pattern as-is otherwise
                $p = \trim($p);

                if ($p !== '' && !\in_array($p, $normalized, true)) {
                    $normalized[] = $p;
                }
            }

            $data['excludes'] = $normalized;
        }

        try {
            \file_put_contents($this->settingsFile, \json_encode($data, \JSON_PRETTY_PRINT));
        } catch (Throwable $e) {
            \error_log('Could not write settings file ' . $this->settingsFile . ': ' . $e->getMessage());
        }
    }

    public function getSettings()
    {
        return [
            'log'      => \realpath($this->log),
            'includes' => $this->includes,
            'excludes' => $this->excludes,
        ];
    }

    public function setSettings($data): void
    {
        $this->log      = $data['log'];
        $this->includes = $data['includes'];
        $this->excludes = $data['excludes'];
    }

    public function filter(&$coverage): void
    {
        foreach ($coverage as $file => $line) {
            if (!$this->isFileIncluded($file)) {
                unset($coverage[$file]);
            }
        }
    }

    public function isFileIncluded($file)
    {
        if (!empty($this->excludes)) {
            foreach ($this->excludes as $path) {
                if (\preg_match('|' . $path . '|', $file)) {
                    return false;
                }
            }
        }

        if (!empty($this->includes)) {
            foreach ($this->includes as $path) {
                if (\preg_match('|' . $path . '|', $file)) {
                    return true;
                }
            }

            return false;
        }

        return true;
    }

    public function isDirectoryIncluded($dir, $directoryDepth)
    {
        if ($directoryDepth >= $this->maxDirectoryDepth) {
            return false;
        }

        if ($this->excludes !== null) {
            foreach ($this->excludes as $path) {
                if (\preg_match('|' . $path . '|', $dir)) {
                    return false;
                }
            }
        }

        return true;
    }
}
