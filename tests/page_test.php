<?php declare(strict_types=1);

require_once __DIR__ . '/../src/autorun.php';

require_once __DIR__ . '/../src/expectation.php';

require_once __DIR__ . '/../src/http.php';

require_once __DIR__ . '/../src/page.php';

Mock::generate('SimpleHttpHeaders');
Mock::generate('SimpleHttpResponse');

class TestOfPageInterface extends UnitTestCase
{
    public function testInterfaceOnEmptyPage(): void
    {
        $page = new SimplePage;
        $this->assertEqual($page->getTransportError(), 'No page fetched yet');
        $this->assertIdentical($page->getRaw(), false);
        $this->assertIdentical($page->getHeaders(), false);
        $this->assertIdentical($page->getMimeType(), false);
        $this->assertIdentical($page->getResponseCode(), false);
        $this->assertIdentical($page->getAuthentication(), false);
        $this->assertIdentical($page->getRealm(), false);
        $this->assertFalse($page->hasFrames());
        $this->assertIdentical($page->getUrls(), []);
        $this->assertIdentical($page->getTitle(), false);
    }
}

class TestOfPageHeaders extends UnitTestCase
{
    public function testUrlAccessor(): void
    {
        $headers = new MockSimpleHttpHeaders;

        $response = new MockSimpleHttpResponse;
        $response->returnsByValue('getHeaders', $headers);
        $response->returnsByValue('getMethod', 'POST');
        $response->returnsByValue('getUrl', new SimpleUrl('here'));
        $response->returnsByValue('getRequestData', ['a' => 'A']);

        $page = new SimplePage($response);
        $this->assertEqual($page->getMethod(), 'POST');
        $this->assertEqual($page->getUrl(), new SimpleUrl('here'));
        $this->assertEqual($page->getRequestData(), ['a' => 'A']);
    }

    public function testTransportError(): void
    {
        $response = new MockSimpleHttpResponse;
        $response->returnsByValue('getError', 'Ouch');

        $page = new SimplePage($response);
        $this->assertEqual($page->getTransportError(), 'Ouch');
    }

    public function testHeadersAccessor(): void
    {
        $headers = new MockSimpleHttpHeaders;
        $headers->returnsByValue('getRaw', 'My: Headers');

        $response = new MockSimpleHttpResponse;
        $response->returnsByValue('getHeaders', $headers);

        $page = new SimplePage($response);
        $this->assertEqual($page->getHeaders(), 'My: Headers');
    }

    public function testMimeAccessor(): void
    {
        $headers = new MockSimpleHttpHeaders;
        $headers->returnsByValue('getMimeType', 'text/html');

        $response = new MockSimpleHttpResponse;
        $response->returnsByValue('getHeaders', $headers);

        $page = new SimplePage($response);
        $this->assertEqual($page->getMimeType(), 'text/html');
    }

    public function testResponseAccessor(): void
    {
        $headers = new MockSimpleHttpHeaders;
        $headers->returnsByValue('getResponseCode', 301);

        $response = new MockSimpleHttpResponse;
        $response->returnsByValue('getHeaders', $headers);

        $page = new SimplePage($response);
        $this->assertIdentical($page->getResponseCode(), 301);
    }

    public function testAuthenticationAccessors(): void
    {
        $headers = new MockSimpleHttpHeaders;
        $headers->returnsByValue('getAuthentication', 'Basic');
        $headers->returnsByValue('getRealm', 'Secret stuff');

        $response = new MockSimpleHttpResponse;
        $response->returnsByValue('getHeaders', $headers);

        $page = new SimplePage($response);
        $this->assertEqual($page->getAuthentication(), 'Basic');
        $this->assertEqual($page->getRealm(), 'Secret stuff');
    }
}

class TestOfHtmlStrippingAndNormalisation extends UnitTestCase
{
    public function testImageSuppressionWhileKeepingParagraphsAndAltText(): void
    {
        $this->assertEqual(
            SimplePage::normalise('<img src="foo.png" /><p>some text</p><img src="bar.png" alt="bar" />'),
            'some text bar',
        );
    }

    public function testSpaceNormalisation(): void
    {
        $this->assertEqual(
            SimplePage::normalise("\nOne\tTwo   \nThree\t"),
            'One Two Three',
        );
    }

    public function testMultilinesCommentSuppression(): void
    {
        $this->assertEqual(
            SimplePage::normalise('<!--\n Hello \n-->'),
            '',
        );
    }

    public function testCommentSuppression(): void
    {
        $this->assertEqual(
            SimplePage::normalise('<!--Hello-->'),
            '',
        );
    }

    public function testJavascriptSuppression(): void
    {
        $this->assertEqual(
            SimplePage::normalise('<script attribute="test">\nHello\n</script>'),
            '',
        );
        $this->assertEqual(
            SimplePage::normalise('<script attribute="test">Hello</script>'),
            '',
        );
        $this->assertEqual(
            SimplePage::normalise('<script>Hello</script>'),
            '',
        );
    }

    public function testTagSuppression(): void
    {
        $this->assertEqual(
            SimplePage::normalise('<b>Hello</b>'),
            'Hello',
        );
    }

    public function testAdjoiningTagSuppression(): void
    {
        $this->assertEqual(
            SimplePage::normalise('<b>Hello</b><em>Goodbye</em>'),
            'HelloGoodbye',
        );
    }

    public function testExtractImageAltTextWithDifferentQuotes(): void
    {
        $this->assertEqual(
            SimplePage::normalise('<img alt="One"><img alt=\'Two\'><img alt=Three>'),
            'One Two Three',
        );
    }

    public function testExtractImageAltTextMultipleTimes(): void
    {
        $this->assertEqual(
            SimplePage::normalise('<img alt="One"><img alt="Two"><img alt="Three">'),
            'One Two Three',
        );
    }

    public function testHtmlEntityTranslation(): void
    {
        $this->assertEqual(
            SimplePage::normalise('&lt;&gt;&quot;&amp;&#039;'),
            '<>"&\'',
        );
    }
}
