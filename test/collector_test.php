<?php
// $Id$

require_once(dirname(__FILE__) . '/../collector.php');
Mock::generate('GroupTest');

class TestOfCollector extends UnitTestCase {
    
    function testCollectionIsAddedToGroup() {
        $group = &new MockGroupTest($this);
        $group->expectCallCount('addTestFile', 2);
        $group->expectArguments(
                'addTestFile',
                array(new WantedPatternExpectation('/collectable\\.(1|2)$/')));
        
        $collector = &new SimpleCollector();
        $collector->collect($group, dirname(__FILE__) . '/support/');
        
        $group->tally();
    }
}
    
class TestOfPatternCollector extends UnitTestCase {
    
    function testAddingEverythingToGroup() {
        $group = &new MockGroupTest($this);
        $group->expectCallCount('addTestFile', 2);
        $group->expectArguments(
                'addTestFile',
                array(new WantedPatternExpectation('/collectable\\.(1|2)$/')));
        
        $collector = &new SimplePatternCollector();
        $collector->collect($group, dirname(__FILE__) . '/support/', '/.*/');
        
        $group->tally();
    }
        
    function testOnlyMatchedFilesAreAddedToGroup() {
        $group = &new MockGroupTest($this);
        $group->expectOnce('addTestFile', array(dirname(__FILE__) . '/support/collectable.1'));
        
        $collector = &new SimplePatternCollector();
        $collector->collect($group, dirname(__FILE__) . '/support/', '/1$/');
        
        $group->tally();
    }
}
?>