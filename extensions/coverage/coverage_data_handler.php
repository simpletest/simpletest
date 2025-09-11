<?php declare(strict_types=1);

/**
 * Persists code coverage data into SQLite database and aggregate data for convienent
 * interpretation in report generator.  Be sure to not to keep an instance longer
 * than you have, otherwise you risk overwriting database edits from another process
 * also trying to make updates.
 */
class CoverageDataHandler
{
    public $filename;
    public $db; // PDO instance
    public $baseDir; // optional base directory used for computing relative filenames

    public static function ltrim($cruft, $pristine)
    {
        if (\stripos($pristine, $cruft) === 0) {
            return \substr($pristine, \strlen($cruft));
        }

        return $pristine;
    }

    public function __construct($filename, $baseDir = null)
    {
        $this->filename = $filename;

        // Normalize baseDir to an absolute canonical path when possible.
        // If it does not exist or is null, fall back to cwd.
        if ($baseDir !== null) {
            $real          = \realpath($baseDir);
            $this->baseDir = $real !== false ? $real : null;
        } else {
            $this->baseDir = null;
        }

        try {
            $this->db = new PDO('sqlite:' . $filename);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Use exceptions for errors; keep default fetch mode when needed
        } catch (Exception $e) {
            throw new Exception('Could not create SQLite DB ' . $filename . ' - ' . $e->getMessage());
        }
    }

    public function __destruct()
    {
        // ensure PDO handle is released when the handler is garbage collected
        $this->close();
    }

    public function createSchema(): void
    {
        $this->db->exec('CREATE TABLE IF NOT EXISTS untouched (filename text)');
        $this->db->exec('CREATE TABLE IF NOT EXISTS coverage (name text, coverage text)');
    }

    public function getFilenames()
    {
        $filenames = [];
        $stmt      = $this->db->query('SELECT DISTINCT name FROM coverage');

        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $filenames[] = $row[0];
        }

        return $filenames;
    }

    public function write($coverage): void
    {
        $sql  = 'INSERT INTO coverage (name, coverage) VALUES (:name, :coverage)';
        $stmt = $this->db->prepare($sql);

        $maxAttempts = 5;
        $attempt     = 0;

        // wrap multi-row insert in a transaction for atomicity and performance
        while (true) {
            try {
                $this->db->beginTransaction();

                foreach ($coverage as $file => $lines) {
                    // store as JSON text to enable SQL JSON functions and safer storage
                    try {
                        $coverageJson = \json_encode($lines, \JSON_THROW_ON_ERROR);
                    } catch (Exception $e) {
                        // fallback to a best-effort encode without throwing
                        $coverageJson = \json_encode($lines);
                    }

                    // Compute a relative filename without relying on the process
                    // current working directory. Use a canonical realpath base
                    // when available; otherwise fall back to the cwd.
                    $baseRoot = $this->baseDir !== null ? $this->baseDir : \realpath(\getcwd());

                    if ($baseRoot === false || $baseRoot === null) {
                        $baseRoot = \getcwd();
                    }
                    $base             = \rtrim($baseRoot, '/') . '/';
                    $relativeFilename = self::ltrim($base, $file);

                    $stmt->execute([':name' => $relativeFilename, ':coverage' => $coverageJson]);
                }

                $this->db->commit();

                break;
            } catch (PDOException $e) {
                // rollback if in transaction
                try {
                    $this->db->rollBack();
                } catch (Exception $ignore) {
                }

                $attempt++;
                $driverCode = isset($e->errorInfo[1]) ? (int) $e->errorInfo[1] : 0;

                // SQLITE_BUSY driver code is 5; also check message fallback
                $isBusy = ($driverCode === 5) || (\stripos($e->getMessage(), 'busy') !== false);

                if ($attempt >= $maxAttempts || !$isBusy) {
                    throw $e;
                }

                // exponential backoff (microseconds)
                \usleep(100000 * (1 << ($attempt - 1)));
                // retry
            }
        }
    }

    public function read()
    {
        $coverage = \array_flip($this->getFilenames());

        foreach (\array_keys($coverage) as $file) {
            $coverage[$file] = $this->readFile($file);
        }

        return $coverage;
    }

    public function readFile($file)
    {
        $aggregate = [];
        $sql       = 'SELECT coverage FROM coverage WHERE name = :name';
        $stmt      = $this->db->prepare($sql);
        $stmt->execute([':name' => $file]);

        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $raw = $row[0];

            // Coverage is persisted as JSON. For security we no longer accept PHP
            // serialized payloads; skip non-JSON rows to avoid object
            // instantiation via unserialize(). This keeps the DB readable while
            // removing the unserialize attack surface.
            if ($raw === null || $raw === '') {
                continue;
            }

            try {
                $decoded = \json_decode($raw, true, 512, \JSON_THROW_ON_ERROR);
            } catch (Exception $e) {
                // Skip non-JSON data; log for operators to perform migration if
                // necessary.
                \error_log('CoverageDataHandler: skipping non-JSON coverage row for file ' . $file);

                continue;
            }

            if (\is_array($decoded)) {
                $this->aggregateCoverage($aggregate, $decoded);
            }
        }

        return $aggregate;
    }

    public function aggregateCoverage(&$total, $next): void
    {
        foreach ($next as $lineno => $code) {
            $total[$lineno] = isset($total[$lineno]) ? $this->aggregateCoverageCode($total[$lineno], $code) : $code;
        }
    }

    public function aggregateCoverageCode($code1, $code2)
    {
        switch ($code1) {
            case -2: return -2;

            case -1: return $code2;

            default:
                switch ($code2) {
                    case -2: return -2;

                    case -1: return $code1;
                }
        }

        return $code1 + $code2;
    }

    public function writeUntouchedFile($file): void
    {
        // Compute relative filename consistent with write(); prefer canonical
        // base directory when available.
        $baseRoot = $this->baseDir !== null ? $this->baseDir : \realpath(\getcwd());

        if ($baseRoot === false || $baseRoot === null) {
            $baseRoot = \getcwd();
        }
        $relativeFile = self::ltrim(\rtrim($baseRoot, '/') . '/', $file);
        $sql          = 'INSERT INTO untouched (filename) VALUES (:filename)';
        $stmt         = $this->db->prepare($sql);

        $maxAttempts = 5;
        $attempt     = 0;

        while (true) {
            try {
                $stmt->execute([':filename' => $relativeFile]);

                break;
            } catch (PDOException $e) {
                $attempt++;
                $driverCode = isset($e->errorInfo[1]) ? (int) $e->errorInfo[1] : 0;
                $isBusy     = ($driverCode === 5) || (\stripos($e->getMessage(), 'busy') !== false);

                if ($attempt >= $maxAttempts || !$isBusy) {
                    throw $e;
                }

                \usleep(100000 * (1 << ($attempt - 1)));
            }
        }
    }

    public function readUntouchedFiles()
    {
        $untouched = [];
        $stmt      = $this->db->query('SELECT filename FROM untouched ORDER BY filename');

        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $untouched[] = $row[0];
        }

        return $untouched;
    }

    /**
     * Close the PDO connection to release locks and file handles.
     */
    public function close(): void
    {
        $this->db = null;
    }
}
