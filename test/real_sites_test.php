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
        function testMirrormill() {
            $this->assertTrue($this->get('http://www.mirrormill.com'));
            $this->setField('q', 'php simpletest');
            $this->clickSubmit('Google Search');
            $this->assertWantedPattern('/lastcraft/i');
        }
    }
?>