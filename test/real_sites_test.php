<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'web_tester.php');

    class LiveSitesTestCase extends WebTestCase {
        function LiveSitesTestCase() {
            $this->WebTestCase();
        }
        function testLastCraft() {
            $this->assertTrue($this->get('http://www.lastcraft.com'));
            $this->assertResponse(array(200));
            $this->assertMime(array('text/html'));
            $this->clickLink('About');
            $this->assertTitle('About Last Craft');
        }
        //function testMirrormill() {
        //    $this->assertTrue($this->get('http://www.mirrormill.com'));
        //    $this->setField('q', 'php simpletest');
        //    $this->clickSubmit('Google Search');
        //    $this->assertWantedPattern('/lastcraft/i');
        //}
        function testSourceforge() {
            $this->assertTrue($this->get('http://sourceforge.net/projects/simpletest/'));
            $this->clickLink('statistics');
            $this->assertWantedPattern('/Statistics for the past 7 days/');
            $this->assertTrue($this->setField('report', 'Monthly'));
            $this->clickSubmit('Change Stats View');
            $this->assertWantedPattern('/Statistics for the past \d+ months/');
        }
    }
?>