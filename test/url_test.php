<?php
    // $Id$
    
    require_once(dirname(__FILE__) . '/../url.php');
    
    class QueryStringTestCase extends UnitTestCase {
        function QueryStringTestCase() {
            $this->UnitTestCase();
        }
        function testEmpty() {
            $query = &new SimpleQueryString();
            $this->assertIdentical($query->getValue('a'), false);
            $this->assertIdentical($query->getKeys(), array());
            $this->assertIdentical($query->asString(), '');
            $this->assertIdentical($query->getAll(), array());
        }
        function testPrefilled() {
            $query = &new SimpleQueryString(array('a' => 'aaa'));
            $this->assertIdentical($query->getValue('a'), 'aaa');
            $this->assertIdentical($query->getKeys(), array('a'));
            $this->assertIdentical($query->asString(), 'a=aaa');
            $this->assertIdentical($query->getAll(), array('a' => 'aaa'));
        }
        function testPrefilledWithObject() {
            $query = &new SimpleQueryString(new SimpleQueryString(array('a' => 'aaa')));
            $this->assertIdentical($query->getValue('a'), 'aaa');
            $this->assertIdentical($query->getKeys(), array('a'));
            $this->assertIdentical($query->asString(), 'a=aaa');
        }
        function testMultiplePrefilled() {
            $query = &new SimpleQueryString(array('a' => array('a1', 'a2')));
            $this->assertIdentical($query->getValue('a'), array('a1', 'a2'));
            $this->assertIdentical($query->asString(), 'a=a1&a=a2');
            $this->assertIdentical($query->getAll(), array('a' => array('a1', 'a2')));
        }
        function testSingleParameter() {
            $query = &new SimpleQueryString();
            $query->add('a', 'Hello');
            $this->assertEqual($query->getValue('a'), 'Hello');
            $this->assertIdentical($query->asString(), 'a=Hello');
        }
        function testUrlEncoding() {
            $query = &new SimpleQueryString();
            $query->add('a', 'Hello there!');
            $this->assertIdentical($query->asString(), 'a=Hello+there%21');
        }
        function testMultipleParameter() {
            $query = &new SimpleQueryString();
            $query->add('a', 'Hello');
            $query->add('b', 'Goodbye');
            $this->assertIdentical($query->asString(), 'a=Hello&b=Goodbye');
        }
        function testEmptyParameters() {
            $query = &new SimpleQueryString();
            $query->add('a', '');
            $query->add('b', '');
            $this->assertIdentical($query->asString(), 'a=&b=');
        }
        function testRepeatedParameter() {
            $query = &new SimpleQueryString();
            $query->add('a', 'Hello');
            $query->add('a', 'Goodbye');
            $this->assertIdentical($query->getValue('a'), array('Hello', 'Goodbye'));
            $this->assertIdentical($query->asString(), 'a=Hello&a=Goodbye');
        }
        function testAddingLists() {
            $query = &new SimpleQueryString();
            $query->add('a', array('Hello', 'Goodbye'));
            $this->assertIdentical($query->getValue('a'), array('Hello', 'Goodbye'));
            $this->assertIdentical($query->asString(), 'a=Hello&a=Goodbye');
        }
        function testMergeInHash() {
            $query = &new SimpleQueryString(array('a' => 'A1', 'b' => 'B'));
            $query->merge(array('a' => 'A2'));
            $this->assertIdentical($query->getValue('a'), array('A1', 'A2'));
            $this->assertIdentical($query->getValue('b'), 'B');
        }
        function testMergeInObject() {
            $query = &new SimpleQueryString(array('a' => 'A1', 'b' => 'B'));
            $query->merge(new SimpleQueryString(array('a' => 'A2')));
            $this->assertIdentical($query->getValue('a'), array('A1', 'A2'));
            $this->assertIdentical($query->getValue('b'), 'B');
        }
    }

    class TestOfUrl extends UnitTestCase {
        function TestOfUrl() {
            $this->UnitTestCase();
        }
        function testDefaultUrl() {
            $url = new SimpleUrl('');
            $this->assertEqual($url->getScheme(), '');
            $this->assertEqual($url->getHost(), '');
            $this->assertEqual($url->getScheme('http'), 'http');
            $this->assertEqual($url->getHost('localhost'), 'localhost');
            $this->assertEqual($url->getPath(), '');
        }
        function testBasicParsing() {
            $url = new SimpleUrl('https://www.lastcraft.com/test/');
            $this->assertEqual($url->getScheme(), 'https');
            $this->assertEqual($url->getHost(), 'www.lastcraft.com');
            $this->assertEqual($url->getPath(), '/test/');
        }
        function testRelativeUrls() {
            $url = new SimpleUrl('../somewhere.php');
            $this->assertEqual($url->getScheme(), false);
            $this->assertEqual($url->getHost(), false);
            $this->assertEqual($url->getPath(), '../somewhere.php');
        }
        function testParseBareParameter() {
            $url = new SimpleUrl('?a');
            $this->assertEqual($url->getPath(), '');
            $this->assertEqual($url->getRequest(), array('a' => ''));
        }
        function testParseEmptyParameter() {
            $url = new SimpleUrl('?a=');
            $this->assertEqual($url->getPath(), '');
            $this->assertEqual($url->getRequest(), array('a' => ''));
        }
        function testParseParameterPair() {
            $url = new SimpleUrl('?a=A');
            $this->assertEqual($url->getPath(), '');
            $this->assertEqual($url->getRequest(), array('a' => 'A'));
        }
        function testParseMultipleParameters() {
            $url = new SimpleUrl('?a=A&b=B');
            $this->assertEqual($url->getRequest(), array('a' => 'A', 'b' => 'B'));
            $this->assertEqual($url->getEncodedRequest(), '?a=A&b=B');
        }
        function testParsingParameterMixture() {
            $url = new SimpleUrl('?a=A&b=&c');
            $this->assertEqual(
                    $url->getRequest(),
                    array('a' => 'A', 'b' => '', 'c' => ''));
        }
        function testAddParameters() {
            $url = new SimpleUrl('');
            $url->addRequestParameter('a', 'A');
            $this->assertEqual($url->getRequest(), array('a' => 'A'));
            $url->addRequestParameter('b', 'B');
            $this->assertEqual($url->getRequest(), array('a' => 'A', 'b' => 'B'));
            $url->addRequestParameter('a', 'aaa');
            $this->assertEqual($url->getRequest(), array('a' => array('A', 'aaa'), 'b' => 'B'));
        }
        function testClearingParameters() {
            $url = new SimpleUrl('');
            $url->addRequestParameter('a', 'A');
            $url->clearRequest();
            $request = $url->getRequest();
            $this->assertIdentical($request, array());
        }
        function testEncodingParameters() {
            $url = new SimpleUrl('');
            $url->addRequestParameter('a', '?!"\'#~@[]{}:;<>,./|£$%^&*()_+-=');
            $this->assertIdentical(
                    $request = $url->getEncodedRequest(),
                    '?a=%3F%21%22%27%23%7E%40%5B%5D%7B%7D%3A%3B%3C%3E%2C.%2F%7C%A3%24%25%5E%26%2A%28%29_%2B-%3D');
        }
        function testDecodingParameters() {            
            $url = new SimpleUrl('?a=%3F%21%22%27%23%7E%40%5B%5D%7B%7D%3A%3B%3C%3E%2C.%2F%7C%A3%24%25%5E%26%2A%28%29_%2B-%3D');
            $this->assertEqual(
                    $url->getRequest(),
                    array('a' => '?!"\'#~@[]{}:;<>,./|£$%^&*()_+-='));
        }
        function testPageSplitting() {
            $url = new SimpleUrl("./here/../there/somewhere.php");
            $this->assertEqual($url->getPath(), "./here/../there/somewhere.php");
            $this->assertEqual($url->getPage(), "somewhere.php");
            $this->assertEqual($url->getBasePath(), "./here/../there/");
        }
        function testAbsolutePathPageSplitting() {
            $url = new SimpleUrl("http://host.com/here/there/somewhere.php");
            $this->assertEqual($url->getPath(), "/here/there/somewhere.php");
            $this->assertEqual($url->getPage(), "somewhere.php");
            $this->assertEqual($url->getBasePath(), "/here/there/");
        }
        function testPathNormalisation() {
            $this->assertEqual(
                    SimpleUrl::normalisePath('https://host.com/I/am/here/../there/somewhere.php'),
                    'https://host.com/I/am/there/somewhere.php');
        }
        function testMakingAbsolute() {
            $url = new SimpleUrl('../there/somewhere.php');
            $this->assertEqual($url->getPath(), '../there/somewhere.php');
            $absolute = $url->makeAbsolute('https://host.com/I/am/here/');
            $this->assertEqual($absolute->getScheme(), 'https');
            $this->assertEqual($absolute->getHost(), 'host.com');
            $this->assertEqual($absolute->getPath(), '/I/am/there/somewhere.php');
        }
        function testMakingAnEmptyUrlAbsolute() {
            $url = new SimpleUrl('');
            $this->assertEqual($url->getPath(), '');
            $absolute = $url->makeAbsolute('http://host.com/I/am/here/');
            $this->assertEqual($absolute->getScheme(), 'http');
            $this->assertEqual($absolute->getHost(), 'host.com');
            $this->assertEqual($absolute->getPath(), '/I/am/here/');
        }
        function testMakingAShortQueryUrlAbsolute() {
            $url = new SimpleUrl('?a#b');
            $this->assertEqual($url->getPath(), '');
            $absolute = $url->makeAbsolute('http://host.com/I/am/here/');
            $this->assertEqual($absolute->getScheme(), 'http');
            $this->assertEqual($absolute->getHost(), 'host.com');
            $this->assertEqual($absolute->getPath(), '/I/am/here/');
            $this->assertEqual($absolute->getEncodedRequest(), '?a=');
            $this->assertEqual($absolute->getFragment(), 'b');
        }
        function testMakingARootUrlAbsolute() {
            $url = new SimpleUrl('/');
            $this->assertEqual($url->getPath(), '/');
            $absolute = $url->makeAbsolute('http://host.com/I/am/here/');
            $this->assertEqual($absolute->getScheme(), 'http');
            $this->assertEqual($absolute->getHost(), 'host.com');
            $this->assertEqual($absolute->getPath(), '/');
        }
        function testMakingAbsoluteAppendedPath() {
            $url = new SimpleUrl('./there/somewhere.php');
            $absolute = $url->makeAbsolute('https://host.com/here/');
            $this->assertEqual($absolute->getPath(), '/here/there/somewhere.php');
        }
        function testMakingAbsolutehasNoEffectWhenAlreadyAbsolute() {
            $url = new SimpleUrl('https://test:secret@www.lastcraft.com/stuff/?a=1#f');
            $absolute = $url->makeAbsolute('http://host.com/here/');
            $this->assertEqual($absolute->getScheme(), 'https');
            $this->assertEqual($absolute->getUsername(), 'test');
            $this->assertEqual($absolute->getPassword(), 'secret');
            $this->assertEqual($absolute->getHost(), 'www.lastcraft.com');
            $this->assertEqual($absolute->getPath(), '/stuff/');
            $this->assertEqual($absolute->getEncodedRequest(), '?a=1');
            $this->assertEqual($absolute->getFragment(), 'f');
        }
        function testUsernameAndPasswordAreUrlDecoded() {
            $url = new SimpleUrl('http://' . urlencode('test@test') .
                    ':' . urlencode('$!£@*&%') . '@www.lastcraft.com');
            $this->assertEqual($url->getUsername(), 'test@test');
            $this->assertEqual($url->getPassword(), '$!£@*&%');
        }
        function testRequestEncoding() {
            $this->assertEqual(
                    SimpleUrl::encodeRequest(array('a' => '1')),
                    'a=1');
            $this->assertEqual(SimpleUrl::encodeRequest(false), '');
            $this->assertEqual(
                    SimpleUrl::encodeRequest(array('a' => array('1', '2'))),
                    'a=1&a=2');
        }
        function testBlitz() {
            $this->assertUrl(
                    "https://username:password@www.somewhere.com:243/this/that/here.php?a=1&b=2#anchor",
                    array("https", "username", "password", "www.somewhere.com", 243, "/this/that/here.php", "com", "?a=1&b=2", "anchor"),
                    array("a" => "1", "b" => "2"));
            $this->assertUrl(
                    "username:password@www.somewhere.com/this/that/here.php?a=1",
                    array(false, "username", "password", "www.somewhere.com", false, "/this/that/here.php", "com", "?a=1", false),
                    array("a" => "1"));
            $this->assertUrl(
                    "username:password@somewhere.com:243",
                    array(false, "username", "password", "somewhere.com", 243, "/", "com", "", false));
            $this->assertUrl(
                    "https://www.somewhere.com",
                    array("https", false, false, "www.somewhere.com", false, "/", "com", "", false));
            $this->assertUrl(
                    "username@www.somewhere.com:243#anchor",
                    array(false, "username", false, "www.somewhere.com", 243, "/", "com", "", "anchor"));
            $this->assertUrl(
                    "/this/that/here.php?a=1&b=2#anchor",
                    array(false, false, false, false, false, "/this/that/here.php", false, "?a=1&b=2", "anchor"),
                    array("a" => "1", "b" => "2"));
            $this->assertUrl(
                    "username@/here.php?a=1&b=2",
                    array(false, "username", false, false, false, "/here.php", false, "?a=1&b=2", false),
                    array("a" => "1", "b" => "2"));
        }
        function testAmbiguousHosts() {
            $this->assertUrl(
                    "tigger",
                    array(false, false, false, false, false, "tigger", false, "", false));
            $this->assertUrl(
                    "/tigger",
                    array(false, false, false, false, false, "/tigger", false, "", false));
            $this->assertUrl(
                    "//tigger",
                    array(false, false, false, "tigger", false, "/", false, "", false));
            $this->assertUrl(
                    "//tigger/",
                    array(false, false, false, "tigger", false, "/", false, "", false));
            $this->assertUrl(
                    "tigger.com",
                    array(false, false, false, "tigger.com", false, "/", "com", "", false));
            $this->assertUrl(
                    "me.net/tigger",
                    array(false, false, false, "me.net", false, "/tigger", "net", "", false));
        }
        function assertUrl($raw, $parts, $params = false) {
            if (! is_array($params)) {
                $params = array();
            }
            $url = new SimpleUrl($raw);
            $this->assertIdentical($url->getScheme(), $parts[0], "[$raw] scheme->%s");
            $this->assertIdentical($url->getUsername(), $parts[1], "[$raw] username->%s");
            $this->assertIdentical($url->getPassword(), $parts[2], "[$raw] password->%s");
            $this->assertIdentical($url->getHost(), $parts[3], "[$raw] host->%s");
            $this->assertIdentical($url->getPort(), $parts[4], "[$raw] port->%s");
            $this->assertIdentical($url->getPath(), $parts[5], "[$raw] path->%s");
            $this->assertIdentical($url->getTld(), $parts[6], "[$raw] tld->%s");
            $this->assertIdentical($url->getEncodedRequest(), $parts[7], "[$raw] encoded->%s");
            $this->assertIdentical($url->getRequest(), $params, "[$raw] request->%s");
            $this->assertIdentical($url->getFragment(), $parts[8], "[$raw] fragment->%s");
        }
    }
?>