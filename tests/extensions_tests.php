<?php declare(strict_types=1);

require_once __DIR__ . '/../src/autorun.php';

require_once __DIR__ . '/../src/collector.php';

class ExtensionsTests extends TestSuite
{
    public function __construct()
    {
        parent::__construct('Extension tests for SimpleTest ' . SimpleTest::getVersion());

        $nodes = new RecursiveDirectoryIterator(__DIR__ . '/../extensions/');

        foreach (new RecursiveIteratorIterator($nodes) as $node) {
            if (\preg_match('/test\.php$/', $node->getFilename())) {
                $this->addFile($node->getPathname());
            }
        }
    }
}
