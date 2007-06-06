<?php

// $Id$

require_once(dirname(__FILE__) . '/../../autorun.php');
require_once(dirname(__FILE__) . '/remote-control.php');

class TestOfSimpleSeleniumRemoteControl extends UnitTestCase {
	function testSesssionIdShouldBePreserved() {
		$remote_control = new SimpleSeleniumRemoteControl("tester", "http://simpletest.org/");
		$this->assertEqual($remote_control->sessionIdParser('OK,123456789123456789'), '123456789123456789');
	}
	
	function testIsUpReturnsFalseWhenDirectedToLocalhostDown() {
		$remote_control = new SimpleSeleniumRemoteControl("tester", "http://simpletest.org/", "localhost-down");;
		$this->assertFalse($remote_control->isUp());
	}

	function testIsUpReturnsTrueWhenDirectedToLocalhostOnPort80() {
		$remote_control = new SimpleSeleniumRemoteControl("tester", "http://simpletest.org/", "localhost", "80");
		$this->assertFalse($remote_control->isUp());
	}
}

