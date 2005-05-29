<?php
    // $Id$
    
    require_once(dirname(__FILE__) . '/../web_tester.php');

    class LiveSitesTestCase extends WebTestCase {
        
        function testLastCraft() {
            $this->assertTrue($this->get('http://www.lastcraft.com'));
            $this->assertResponse(array(200));
            $this->assertMime(array('text/html'));
            $this->clickLink('About');
            $this->assertTitle('About Last Craft');
        }
        
        function testSourceforge() {
            $this->assertTrue($this->get('http://sourceforge.net/'));
            $this->setField('words', 'simpletest');
            $this->assertTrue($this->clickImageByName('imageField'));
            $this->assertTitle('SourceForge.net: Search');
            $this->assertTrue($this->clickLink('SimpleTest'));
            $this->clickLink('statistics');
            $this->assertWantedText('SimpleTest: Statistics');
            $this->assertTrue($this->setField('mode', 'All Time'));
            $this->clickSubmit('Change View');
            $this->assertWantedText('Mar 2003');
        }
    }
?>