<?php
    // $Id$
    
    if (!defined('SIMPLE_TEST')) {
        define('SIMPLE_TEST', '../');
    }
    require_once(SIMPLE_TEST . 'authentication.php');
    require_once(SIMPLE_TEST . 'http.php');
    Mock::generate('SimpleHttpRequest');

    class TestOfRealm extends UnitTestCase {
        function TestOfRealm() {
            $this->UnitTestCase();
        }
        function testWithinSameUrl() {
            $realm = &new SimpleRealm(
                    'Basic',
                    new SimpleUrl('http://www.here.com/path/hello.html'));
            $this->assertTrue($realm->isWithin(
                    new SimpleUrl('http://www.here.com/path/hello.html')));
        }
        function testInsideWithLongerUrl() {
            $realm = &new SimpleRealm(
                    'Basic',
                    new SimpleUrl('http://www.here.com/path/'));
            $this->assertTrue($realm->isWithin(
                    new SimpleUrl('http://www.here.com/path/hello.html')));
        }
        function testOutsideOfDifferentHost() {
            $realm = &new SimpleRealm(
                    'Basic',
                    new SimpleUrl('http://www.here.com/path/'));
            $this->assertFalse($realm->isWithin(
                    new SimpleUrl('http://here.com/path/hello.html')));
        }
        function testBelowRootIsOutside() {
            $realm = &new SimpleRealm(
                    'Basic',
                    new SimpleUrl('http://www.here.com/path/'));
            $this->assertTrue($realm->isWithin(
                    new SimpleUrl('http://www.here.com/path/more/hello.html')));
        }
        function testOldNetscapeDefinitionIsOutside() {
            $realm = &new SimpleRealm(
                    'Basic',
                    new SimpleUrl('http://www.here.com/path/'));
            $this->assertFalse($realm->isWithin(
                    new SimpleUrl('http://www.here.com/pathmore/hello.html')));
        }
        function testDifferentPageNameStillInside() {
            $realm = &new SimpleRealm(
                    'Basic',
                    new SimpleUrl('http://www.here.com/path/hello.html'));
            $this->assertTrue($realm->isWithin(
                    new SimpleUrl('http://www.here.com/path/goodbye.html')));
        }
        function testNewUrlInSameDirectoryDoesNotChangeRealm() {
            $realm = &new SimpleRealm(
                    'Basic',
                    new SimpleUrl('http://www.here.com/path/hello.html'));
            $realm->mergeUrl(new SimpleUrl('http://www.here.com/path/goodbye.html'));
            $this->assertTrue($realm->isWithin(
                    new SimpleUrl('http://www.here.com/path/index.html')));
            $this->assertFalse($realm->isWithin(
                    new SimpleUrl('http://www.here.com/index.html')));
        }
    }

    class TestOfAuthenticator extends UnitTestCase {
        function TestOfAuthenticator() {
            $this->UnitTestCase();
        }
        function testNoRealms() {
            $request = &new MockSimpleHttpRequest($this);
            $request->expectNever('addHeaderLine');
            $authenticator = &new SimpleAuthenticator();
            $authenticator->addHeaders($request, new SimpleUrl('http://here.com/'));
            $request->tally();
        }
        function &createSingleRealm() {
            $authenticator = &new SimpleAuthenticator();
            $authenticator->addRealm(
                    new SimpleUrl('http://www.here.com/path/hello.html'),
                    'Basic',
                    'Sanctuary');
            $authenticator->setIdentityForRealm('Sanctuary', 'test', 'secret');
            return $authenticator;
        }
        function testOutsideRealm() {
            $request = &new MockSimpleHttpRequest($this);
            $request->expectNever('addHeaderLine');
            $authenticator = &$this->createSingleRealm();
            $authenticator->addHeaders(
                    $request,
                    new SimpleUrl('http://www.here.com/hello.html'));
            $request->tally();
        }
        function testWithinRealm() {
            $request = &new MockSimpleHttpRequest($this);
            $request->expectOnce('addHeaderLine');
            $authenticator = &$this->createSingleRealm();
            $authenticator->addHeaders(
                    $request,
                    new SimpleUrl('http://www.here.com/path/more/hello.html'));
            $request->tally();
        }
    }
?>