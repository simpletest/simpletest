<?php

require_once __DIR__.'/../src/autorun.php';
require_once __DIR__.'/../src/user_agent.php';
require_once __DIR__.'/../src/authentication.php';
require_once __DIR__.'/../src/http.php';
require_once __DIR__.'/../src/encoding.php';

Mock::generate('SimpleHttpRequest');
Mock::generate('SimpleHttpResponse');
Mock::generate('SimpleHttpHeaders');
Mock::generatePartial('SimpleUserAgent', 'MockRequestUserAgent', ['createHttpRequest']);

class TestOfFetchingUrlParameters extends UnitTestCase
{
    private $headers;
    private $request;
    private $response;

    public function setUp()
    {
        $this->headers = new MockSimpleHttpHeaders();
        $this->response = new MockSimpleHttpResponse();
        $this->response->returnsByValue('isError', false);
        $this->response->returns('getHeaders', new MockSimpleHttpHeaders());
        $this->request = new MockSimpleHttpRequest();
        $this->request->returns('fetch', $this->response);
    }

    public function testGetRequestWithoutIncidentGivesNoErrors()
    {
        $url = new SimpleUrl('http://test:secret@this.com/page.html');
        $url->addRequestParameters(['a' => 'A', 'b' => 'B']);

        $agent = new MockRequestUserAgent();
        $agent->returns('createHttpRequest', $this->request);
        $agent->__constructor();

        $response = $agent->fetchResponse(
            new SimpleUrl('http://test:secret@this.com/page.html'),
            new SimpleGetEncoding(['a' => 'A', 'b' => 'B'])
        );
        $this->assertFalse($response->isError());
    }
}

class TestOfAdditionalHeaders extends UnitTestCase
{
    public function testAdditionalHeaderAddedToRequest()
    {
        $response = new MockSimpleHttpResponse();
        $mockHeaders = new MockSimpleHttpHeaders();
        $response->returnsByReference('getHeaders', $mockHeaders);

        $request = new MockSimpleHttpRequest();
        $request->returnsByReference('fetch', $response);
        $request->expectOnce(
            'addHeaderLine',
            ['User-Agent: SimpleTest']
        );

        $agent = new MockRequestUserAgent();
        $agent->returnsByReference('createHttpRequest', $request);
        $agent->__constructor();
        $agent->addHeader('User-Agent: SimpleTest');
        $response = $agent->fetchResponse(new SimpleUrl('http://this.host/'), new SimpleGetEncoding());
    }
}

class TestOfBrowserCookies extends UnitTestCase
{
    private function createStandardResponse()
    {
        $response = new MockSimpleHttpResponse();
        $response->returnsByValue('isError', false);
        $response->returnsByValue('getContent', 'stuff');
        $mockHeaders = new MockSimpleHttpHeaders();
        $response->returnsByReference('getHeaders', $mockHeaders);

        return $response;
    }

    private function createCookieSite($header_lines)
    {
        $headers = new SimpleHttpHeaders($header_lines);
        $response = new MockSimpleHttpResponse();
        $response->returnsByValue('isError', false);
        $response->returnsByReference('getHeaders', $headers);
        $response->returnsByValue('getContent', 'stuff');
        $request = new MockSimpleHttpRequest();
        $request->returnsByReference('fetch', $response);

        return $request;
    }

    private function createMockedRequestUserAgent(&$request)
    {
        $agent = new MockRequestUserAgent();
        $agent->returnsByReference('createHttpRequest', $request);
        $agent->__constructor();

        return $agent;
    }

    public function testCookieJarIsSentToRequest()
    {
        $jar = new SimpleCookieJar();
        $jar->setCookie('a', 'A');

        $request = new MockSimpleHttpRequest();
        $request->returns('fetch', $this->createStandardResponse());
        $request->expectOnce('readCookiesFromJar', [$jar, '*']);

        $agent = $this->createMockedRequestUserAgent($request);
        $agent->setCookie('a', 'A');
        $agent->fetchResponse(
            new SimpleUrl('http://this.com/this/path/page.html'),
            new SimpleGetEncoding()
        );
    }

    public function testNoCookieJarIsSentToRequestWhenCookiesAreDisabled()
    {
        $request = new MockSimpleHttpRequest();
        $request->returns('fetch', $this->createStandardResponse());
        $request->expectNever('readCookiesFromJar');

        $agent = $this->createMockedRequestUserAgent($request);
        $agent->setCookie('a', 'A');
        $agent->ignoreCookies();
        $agent->fetchResponse(
            new SimpleUrl('http://this.com/this/path/page.html'),
            new SimpleGetEncoding()
        );
    }

    public function testReadingNewCookie()
    {
        $request = $this->createCookieSite('Set-cookie: a=AAAA');
        $agent = $this->createMockedRequestUserAgent($request);
        $agent->fetchResponse(
            new SimpleUrl('http://this.com/this/path/page.html'),
            new SimpleGetEncoding()
        );
        $this->assertEqual($agent->getCookieValue('this.com', 'this/path/', 'a'), 'AAAA');
    }

    public function testIgnoringNewCookieWhenCookiesDisabled()
    {
        $request = $this->createCookieSite('Set-cookie: a=AAAA');
        $agent = $this->createMockedRequestUserAgent($request);
        $agent->ignoreCookies();
        $agent->fetchResponse(
            new SimpleUrl('http://this.com/this/path/page.html'),
            new SimpleGetEncoding()
        );
        $this->assertIdentical($agent->getCookieValue('this.com', 'this/path/', 'a'), false);
    }

    public function testOverwriteCookieThatAlreadyExists()
    {
        $request = $this->createCookieSite('Set-cookie: a=AAAA');
        $agent = $this->createMockedRequestUserAgent($request);
        $agent->setCookie('a', 'A');
        $agent->fetchResponse(
            new SimpleUrl('http://this.com/this/path/page.html'),
            new SimpleGetEncoding()
        );
        $this->assertEqual($agent->getCookieValue('this.com', 'this/path/', 'a'), 'AAAA');
    }

    public function testClearCookieBySettingExpiry()
    {
        $request = $this->createCookieSite('Set-cookie: a=b');
        $agent = $this->createMockedRequestUserAgent($request);

        $agent->setCookie('a', 'A', 'this/path/', 'Wed, 25-Dec-02 04:24:21 GMT');
        $agent->fetchResponse(
            new SimpleUrl('http://this.com/this/path/page.html'),
            new SimpleGetEncoding()
        );
        $this->assertIdentical(
            $agent->getCookieValue('this.com', 'this/path/', 'a'),
            'b'
        );
        $agent->restart('Wed, 25-Dec-02 04:24:20 GMT');
        $this->assertIdentical(
            $agent->getCookieValue('this.com', 'this/path/', 'a'),
            false
        );
    }

    public function testAgeingAndClearing()
    {
        $request = $this->createCookieSite('Set-cookie: a=A; expires=Wed, 25-Dec-02 04:24:21 GMT; path=/this/path');
        $agent = $this->createMockedRequestUserAgent($request);

        $agent->fetchResponse(
            new SimpleUrl('http://this.com/this/path/page.html'),
            new SimpleGetEncoding()
        );
        $agent->restart('Wed, 25-Dec-02 04:24:20 GMT');
        $this->assertIdentical(
            $agent->getCookieValue('this.com', 'this/path/', 'a'),
            'A'
        );
        $agent->ageCookies(2);
        $agent->restart('Wed, 25-Dec-02 04:24:20 GMT');
        $this->assertIdentical(
            $agent->getCookieValue('this.com', 'this/path/', 'a'),
            false
        );
    }

    public function testReadingIncomingAndSettingNewCookies()
    {
        $request = $this->createCookieSite('Set-cookie: a=AAA');
        $agent = $this->createMockedRequestUserAgent($request);

        $this->assertNull($agent->getBaseCookieValue('a', false));
        $agent->fetchResponse(
            new SimpleUrl('http://this.com/this/path/page.html'),
            new SimpleGetEncoding()
        );
        $agent->setCookie('b', 'BBB', 'this.com', 'this/path/');
        $this->assertEqual(
            $agent->getBaseCookieValue('a', new SimpleUrl('http://this.com/this/path/page.html')),
            'AAA'
        );
        $this->assertEqual(
            $agent->getBaseCookieValue('b', new SimpleUrl('http://this.com/this/path/page.html')),
            'BBB'
        );
    }
}

class TestOfHttpRedirects extends UnitTestCase
{
    public function createRedirect($content, $redirect)
    {
        $headers = new MockSimpleHttpHeaders();
        $headers->returnsByValue('isRedirect', (bool) $redirect);
        $headers->returnsByValue('getLocation', $redirect);
        $response = new MockSimpleHttpResponse();
        $response->returnsByValue('getContent', $content);
        $response->returnsByReference('getHeaders', $headers);
        $request = new MockSimpleHttpRequest();
        $request->returnsByReference('fetch', $response);

        return $request;
    }

    public function testDisabledRedirects()
    {
        $agent = new MockRequestUserAgent();
        $agent->returns(
            'createHttpRequest',
            $this->createRedirect('stuff', 'there.html')
        );
        $agent->expectOnce('createHttpRequest');
        $agent->__constructor();
        $agent->setMaximumRedirects(0);
        $response = $agent->fetchResponse(new SimpleUrl('here.html'), new SimpleGetEncoding());
        $this->assertEqual($response->getContent(), 'stuff');
    }

    public function testSingleRedirect()
    {
        $agent = new MockRequestUserAgent();
        $agent->returnsAt(
            0,
            'createHttpRequest',
            $this->createRedirect('first', 'two.html')
        );
        $agent->returnsAt(
            1,
            'createHttpRequest',
            $this->createRedirect('second', 'three.html')
        );
        $agent->expectCallCount('createHttpRequest', 2);
        $agent->__constructor();

        $agent->setMaximumRedirects(1);
        $response = $agent->fetchResponse(new SimpleUrl('one.html'), new SimpleGetEncoding());
        $this->assertEqual($response->getContent(), 'second');
    }

    public function testDoubleRedirect()
    {
        $agent = new MockRequestUserAgent();
        $agent->returnsAt(
            0,
            'createHttpRequest',
            $this->createRedirect('first', 'two.html')
        );
        $agent->returnsAt(
            1,
            'createHttpRequest',
            $this->createRedirect('second', 'three.html')
        );
        $agent->returnsAt(
            2,
            'createHttpRequest',
            $this->createRedirect('third', 'four.html')
        );
        $agent->expectCallCount('createHttpRequest', 3);
        $agent->__constructor();

        $agent->setMaximumRedirects(2);
        $response = $agent->fetchResponse(new SimpleUrl('one.html'), new SimpleGetEncoding());
        $this->assertEqual($response->getContent(), 'third');
    }

    public function testSuccessAfterRedirect()
    {
        $agent = new MockRequestUserAgent();
        $agent->returnsAt(
            0,
            'createHttpRequest',
            $this->createRedirect('first', 'two.html')
        );
        $agent->returnsAt(
            1,
            'createHttpRequest',
            $this->createRedirect('second', false)
        );
        $agent->returnsAt(
            2,
            'createHttpRequest',
            $this->createRedirect('third', 'four.html')
        );
        $agent->expectCallCount('createHttpRequest', 2);
        $agent->__constructor();

        $agent->setMaximumRedirects(2);
        $response = $agent->fetchResponse(new SimpleUrl('one.html'), new SimpleGetEncoding());
        $this->assertEqual($response->getContent(), 'second');
    }

    public function testRedirectChangesPostToGet()
    {
        $agent = new MockRequestUserAgent();
        $agent->returnsAt(
            0,
            'createHttpRequest',
            $this->createRedirect('first', 'two.html')
        );
        $agent->expectAt(0, 'createHttpRequest', ['*', new IsAExpectation('SimplePostEncoding')]);
        $agent->returnsAt(
            1,
            'createHttpRequest',
            $this->createRedirect('second', 'three.html')
        );
        $agent->expectAt(1, 'createHttpRequest', ['*', new IsAExpectation('SimpleGetEncoding')]);
        $agent->expectCallCount('createHttpRequest', 2);
        $agent->__constructor();
        $agent->setMaximumRedirects(1);
        $response = $agent->fetchResponse(new SimpleUrl('one.html'), new SimplePostEncoding());
    }
}

class TestOfBadHosts extends UnitTestCase
{
    private function createSimulatedBadHost()
    {
        $response = new MockSimpleHttpResponse();
        $response->returnsByValue('isError', true);
        $response->returnsByValue('getError', 'Bad socket');
        $response->returnsByValue('getContent', false);
        $request = new MockSimpleHttpRequest();
        $request->returnsByReference('fetch', $response);

        return $request;
    }

    public function testUntestedHost()
    {
        $request = $this->createSimulatedBadHost();
        $agent = new MockRequestUserAgent();
        $agent->returnsByReference('createHttpRequest', $request);
        $agent->__constructor();
        $response = $agent->fetchResponse(
            new SimpleUrl('http://this.host/this/path/page.html'),
            new SimpleGetEncoding()
        );
        $this->assertTrue($response->isError());
    }
}

class TestOfAuthorisation extends UnitTestCase
{
    public function testAuthenticateHeaderAdded()
    {
        $response = new MockSimpleHttpResponse();
        $mockHeaders = new MockSimpleHttpHeaders();
        $response->returnsByReference('getHeaders', $mockHeaders);

        $request = new MockSimpleHttpRequest();
        $request->returns('fetch', $response);
        $request->expectOnce(
            'addHeaderLine',
            ['Authorization: Basic '.base64_encode('test:secret')]
        );

        $agent = new MockRequestUserAgent();
        $agent->returns('createHttpRequest', $request);
        $agent->__constructor();
        $response = $agent->fetchResponse(
            new SimpleUrl('http://test:secret@this.host'),
            new SimpleGetEncoding()
        );
    }
}
