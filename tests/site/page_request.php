<?php declare(strict_types=1);

class PageRequest
{
    private $parsed = [];

    public static function get()
    {
        if (isset($_SERVER['QUERY_STRING'])) {
            $request = new self($_SERVER['QUERY_STRING']);

            return $request->getAll();
        }

        return [];
    }

    public static function post()
    {
        $request = new self(\file_get_contents('php://input'));

        return $request->getAll();
    }

    public function __construct($raw)
    {
        $statements = \explode('&', $raw);

        foreach ($statements as $statement) {
            if (!\str_contains($statement, '=')) {
                continue;
            }
            $this->parseStatement($statement);
        }
    }

    public function getAll()
    {
        return $this->parsed;
    }

    private function parseStatement($statement): void
    {
        [$key, $value] = \explode('=', $statement);
        $key           = \urldecode($key);

        if (\preg_match('/(.*)\[\]$/', $key, $matches)) {
            $key = $matches[1];

            if (!isset($this->parsed[$key])) {
                $this->parsed[$key] = [];
            }
            $this->addValue($key, $value);
        } elseif (isset($this->parsed[$key])) {
            $this->addValue($key, $value);
        } else {
            $this->setValue($key, $value);
        }
    }

    private function addValue($key, $value): void
    {
        if (!\is_array($this->parsed[$key])) {
            $this->parsed[$key] = [$this->parsed[$key]];
        }
        $this->parsed[$key][] = \urldecode($value);
    }

    private function setValue($key, $value): void
    {
        $this->parsed[$key] = \urldecode($value);
    }
}
