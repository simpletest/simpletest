<?php
require_once(dirname(__FILE__) . '/../autorun.php');
require_once(dirname(__FILE__) . '/../collector.php');

class ExtensionsTests extends TestSuite {
    function ExtensionsTests() {
        $this->TestSuite('Extension tests for SimpleTest ' . SimpleTest::getVersion());
        $collector = new SimplePatternCollector('/test\.php$/');
		$this->collect(dirname(__FILE__).'/extensions/testdox', $collector);
		$this->collect(dirname(__FILE__).'/extensions/treemap_reporter', $collector);
		$this->collect(dirname(__FILE__).'/extensions/dom_tester', $collector);
    }
}
?>