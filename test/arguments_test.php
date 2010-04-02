<?php
// $Id: cookies_test.php 1506 2007-05-07 00:58:03Z lastcraft $
require_once(dirname(__FILE__) . '/../autorun.php');
require_once(dirname(__FILE__) . '/../arguments.php');

class TestOfCommandLineArgumentParsing extends UnitTestCase {
    function testArgumentListWithJustProgramNameGivesFalseToEveryName() {
        $arguments = new SimpleArguments(array('me'));
        $this->assertIdentical($arguments->a, false);
        $this->assertIdentical($arguments->all(), array());
    }
    
    function testSingleArgumentNameRecordedAsTrue() {
        $arguments = new SimpleArguments(array('me', '-a'));
        $this->assertIdentical($arguments->a, true);
    }
    
    function testSingleArgumentCanBeGivenAValue() {
        $arguments = new SimpleArguments(array('me', '-a=AAA'));
        $this->assertIdentical($arguments->a, 'AAA');
    }
    
    function testSingleArgumentCanBeGivenSpaceSeparatedValue() {
        $arguments = new SimpleArguments(array('me', '-a', 'AAA'));
        $this->assertIdentical($arguments->a, 'AAA');
    }
    
    function testWillBuildArrayFromRepeatedValue() {
        $arguments = new SimpleArguments(array('me', '-a', 'A', '-a', 'AA'));
        $this->assertIdentical($arguments->a, array('A', 'AA'));
    }
    
    function testWillBuildArrayFromMultiplyRepeatedValues() {
        $arguments = new SimpleArguments(array('me', '-a', 'A', '-a', 'AA', '-a', 'AAA'));
        $this->assertIdentical($arguments->a, array('A', 'AA', 'AAA'));
    }
    
    function testCanParseLongFormArguments() {
        $arguments = new SimpleArguments(array('me', '--aa=AA', '--bb', 'BB'));
        $this->assertIdentical($arguments->aa, 'AA');
        $this->assertIdentical($arguments->bb, 'BB');
    }
}

class TestOfHelpOutput extends UnitTestCase {
    function testDisplaysGeneralHelpBanner() {
        $help = new SimpleHelp("This program is cool");
        $this->assertPattern('/This program is cool/', $help->render());
    }
}
?>