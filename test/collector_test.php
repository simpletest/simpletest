<?php
// $Id$

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '../collector.php');
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '../unit_tester.php');
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '../reporter.php');
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '../mock_objects.php');

class TestOfSimpleCollector extends UnitTestCase
{
    var $_path = '/tmp/CollectorTests';
    var $_files = array('1.php', '2.php', '3.html', '4.txt');
    
    function setUp()
    {
        mkdir($this->_path);
        foreach ($this->_files as $file) {
            touch($this->_path . '/' . $file);
        }
    }
    
    
    function tearDown()
    {
        foreach ($this->_files as $file) {
            unlink($this->_path . '/' . $file);
        }
        
        rmdir($this->_path);
    }
    
    
    function testCollector()
    {
        $groupTest = $this->_generateMockGroupTest();
        $groupTest->expectCallCount('addTestFile', 4);
        
        foreach ($this->_files as $i => $file) {
            $expected = array($this->_path . '/' . $file);
            $groupTest->expectArgumentsAt($i, 'addTestFile', $expected);
        }
        
        $collector = new SimpleCollector($groupTest, $this->_path);
        $collector->collect();
    }
    
    
    function testPatternCollector()
    {
        $groupTest = $this->_generateMockGroupTest();
        $groupTest->expectCallCount('addTestFile', 2);
        $groupTest->expectArgumentsAt(0, 'addTestFile',
            array($this->_path . '/' . $this->_files[0]));
        $groupTest->expectArgumentsAt(1, 'addTestFile',
            array($this->_path . '/' . $this->_files[1]));
        
        $collector = new SimplePatternCollector($groupTest, $this->_path);
        $collector->collect();
        
        unset($groupTest);
        unset($collector);
        
        // Try with a custom pattern now
        $groupTest = $this->_generateMockGroupTest();
        $groupTest->expectCallCount('addTestFile', 1);
        $groupTest->expectArgumentsAt(0, 'addTestFile',
            array($this->_path . '/' . $this->_files[2]));
        
        $collector = new SimplePatternCollector($groupTest, $this->_path,
            '/html$/');
        $collector->collect();
        
    }
    
    
    function _generateMockGroupTest()
    {
        if (!class_exists('MockGroupTest')) {
            Mock::generatePartial('GroupTest', 'MockGroupTest', 
                array('addTestFile'));
        }
        
        return new MockGroupTest($this);
    }
}

