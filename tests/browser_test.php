<?php declare(strict_types=1);

require_once __DIR__ . '/../src/autorun.php';

require_once __DIR__ . '/../src/browser.php';

require_once __DIR__ . '/../src/user_agent.php';

require_once __DIR__ . '/../src/http.php';

require_once __DIR__ . '/../src/page.php';

require_once __DIR__ . '/../src/encoding.php';

Mock::generate('SimpleHttpResponse');
Mock::generate('SimplePage');
Mock::generate('SimpleForm');
Mock::generate('SimpleUserAgent');
Mock::generatePartial(
    'SimpleBrowser',
    'MockParseSimpleBrowser',
    ['createUserAgent', 'parse'],
);
Mock::generatePartial(
    'SimpleBrowser',
    'MockUserAgentSimpleBrowser',
    ['createUserAgent'],
);

class TestOfHistory extends UnitTestCase
{
    public function testEmptyHistoryHasFalseContents(): void
    {
        $history = new SimpleBrowserHistory;
        $this->assertIdentical($history->getUrl(), false);
        $this->assertIdentical($history->getParameters(), false);
    }

    public function testCannotMoveInEmptyHistory(): void
    {
        $history = new SimpleBrowserHistory;
        $this->assertFalse($history->back());
        $this->assertFalse($history->forward());
    }

    public function testCurrentTargetAccessors(): void
    {
        $history = new SimpleBrowserHistory;
        $history->recordEntry(
            new SimpleUrl('http://www.here.com/'),
            new SimpleGetEncoding,
        );
        $this->assertIdentical($history->getUrl(), new SimpleUrl('http://www.here.com/'));
        $this->assertIdentical($history->getParameters(), new SimpleGetEncoding);
    }

    public function testSecondEntryAccessors(): void
    {
        $history = new SimpleBrowserHistory;
        $history->recordEntry(
            new SimpleUrl('http://www.first.com/'),
            new SimpleGetEncoding,
        );
        $history->recordEntry(
            new SimpleUrl('http://www.second.com/'),
            new SimplePostEncoding(['a' => 1]),
        );
        $this->assertIdentical($history->getUrl(), new SimpleUrl('http://www.second.com/'));
        $this->assertIdentical(
            $history->getParameters(),
            new SimplePostEncoding(['a' => 1]),
        );
    }

    public function testGoingBackwards(): void
    {
        $history = new SimpleBrowserHistory;
        $history->recordEntry(
            new SimpleUrl('http://www.first.com/'),
            new SimpleGetEncoding,
        );
        $history->recordEntry(
            new SimpleUrl('http://www.second.com/'),
            new SimplePostEncoding(['a' => 1]),
        );
        $this->assertTrue($history->back());
        $this->assertIdentical($history->getUrl(), new SimpleUrl('http://www.first.com/'));
        $this->assertIdentical($history->getParameters(), new SimpleGetEncoding);
    }

    public function testGoingBackwardsOffBeginning(): void
    {
        $history = new SimpleBrowserHistory;
        $history->recordEntry(
            new SimpleUrl('http://www.first.com/'),
            new SimpleGetEncoding,
        );
        $this->assertFalse($history->back());
        $this->assertIdentical($history->getUrl(), new SimpleUrl('http://www.first.com/'));
        $this->assertIdentical($history->getParameters(), new SimpleGetEncoding);
    }

    public function testGoingForwardsOffEnd(): void
    {
        $history = new SimpleBrowserHistory;
        $history->recordEntry(
            new SimpleUrl('http://www.first.com/'),
            new SimpleGetEncoding,
        );
        $this->assertFalse($history->forward());
        $this->assertIdentical($history->getUrl(), new SimpleUrl('http://www.first.com/'));
        $this->assertIdentical($history->getParameters(), new SimpleGetEncoding);
    }

    public function testGoingBackwardsAndForwards(): void
    {
        $history = new SimpleBrowserHistory;
        $history->recordEntry(
            new SimpleUrl('http://www.first.com/'),
            new SimpleGetEncoding,
        );
        $history->recordEntry(
            new SimpleUrl('http://www.second.com/'),
            new SimplePostEncoding(['a' => 1]),
        );
        $this->assertTrue($history->back());
        $this->assertTrue($history->forward());
        $this->assertIdentical($history->getUrl(), new SimpleUrl('http://www.second.com/'));
        $this->assertIdentical(
            $history->getParameters(),
            new SimplePostEncoding(['a' => 1]),
        );
    }

    public function testNewEntryReplacesNextOne(): void
    {
        $history = new SimpleBrowserHistory;
        $history->recordEntry(
            new SimpleUrl('http://www.first.com/'),
            new SimpleGetEncoding,
        );
        $history->recordEntry(
            new SimpleUrl('http://www.second.com/'),
            new SimplePostEncoding(['a' => 1]),
        );
        $history->back();
        $history->recordEntry(
            new SimpleUrl('http://www.third.com/'),
            new SimpleGetEncoding,
        );
        $this->assertIdentical($history->getUrl(), new SimpleUrl('http://www.third.com/'));
        $this->assertIdentical($history->getParameters(), new SimpleGetEncoding);
    }

    public function testNewEntryDropsFutureEntries(): void
    {
        $history = new SimpleBrowserHistory;
        $history->recordEntry(
            new SimpleUrl('http://www.first.com/'),
            new SimpleGetEncoding,
        );
        $history->recordEntry(
            new SimpleUrl('http://www.second.com/'),
            new SimpleGetEncoding,
        );
        $history->recordEntry(
            new SimpleUrl('http://www.third.com/'),
            new SimpleGetEncoding,
        );
        $history->back();
        $history->back();
        $history->recordEntry(
            new SimpleUrl('http://www.fourth.com/'),
            new SimpleGetEncoding,
        );
        $this->assertIdentical($history->getUrl(), new SimpleUrl('http://www.fourth.com/'));
        $this->assertFalse($history->forward());
        $history->back();
        $this->assertIdentical($history->getUrl(), new SimpleUrl('http://www.first.com/'));
        $this->assertFalse($history->back());
    }
}

class TestOfParsedPageAccess extends UnitTestCase
{
    public function loadPage(&$page)
    {
        $response = new MockSimpleHttpResponse($this);
        $agent    = new MockSimpleUserAgent($this);
        $agent->returns('fetchResponse', $response);

        $browser = new MockParseSimpleBrowser($this);
        $browser->returns('createUserAgent', $agent);
        $browser->returns('parse', $page);
        $browser->__constructor();

        $browser->get('http://this.com/page.html');

        return $browser;
    }

    public function testAccessorsWhenNoPage(): void
    {
        $agent   = new MockSimpleUserAgent($this);
        $browser = new MockParseSimpleBrowser($this);
        $browser->returns('createUserAgent', $agent);
        $browser->__constructor();
        $this->assertEqual($browser->getContent(), '');
    }

    public function testParse(): void
    {
        $page = new MockSimplePage;
        $page->returnsByValue('getRequest', "GET here.html\r\n\r\n");
        $page->returnsByValue('getRaw', 'Raw HTML');
        $page->returnsByValue('getTitle', 'Here');
        $page->returnsByValue('getFrameFocus', 'Frame');
        $page->returnsByValue('getMimeType', 'text/html');
        $page->returnsByValue('getResponseCode', 200);
        $page->returnsByValue('getAuthentication', 'Basic');
        $page->returnsByValue('getRealm', 'Somewhere');
        $page->returnsByValue('getTransportError', 'Ouch!');

        $browser = $this->loadPage($page);
        $this->assertEqual($browser->getRequest(), "GET here.html\r\n\r\n");
        $this->assertEqual($browser->getContent(), 'Raw HTML');
        $this->assertEqual($browser->getTitle(), 'Here');
        $this->assertEqual($browser->getFrameFocus(), 'Frame');
        $this->assertIdentical($browser->getResponseCode(), 200);
        $this->assertEqual($browser->getMimeType(), 'text/html');
        $this->assertEqual($browser->getAuthentication(), 'Basic');
        $this->assertEqual($browser->getRealm(), 'Somewhere');
        $this->assertEqual($browser->getTransportError(), 'Ouch!');
    }

    public function testLinkAffirmationWhenPresent(): void
    {
        $page = new MockSimplePage;
        $page->returnsByValue('getUrlsByLabel', ['http://www.nowhere.com']);
        $page->expectOnce('getUrlsByLabel', ['a link label']);
        $browser = $this->loadPage($page);
        $this->assertIdentical($browser->getLink('a link label'), 'http://www.nowhere.com');
    }

    public function testLinkAffirmationByIdWhenPresent(): void
    {
        $page = new MockSimplePage;
        $page->returnsByValue('getUrlById', 'a_page.com', [99]);
        $page->returnsByValue('getUrlById', false, ['*']);
        $browser = $this->loadPage($page);
        $this->assertIdentical($browser->getLinkById(99), 'a_page.com');
        $this->assertFalse($browser->getLinkById(98));
    }

    public function testSettingFieldIsPassedToPage(): void
    {
        $page = new MockSimplePage;
        $page->expectOnce('setField', [new SelectByLabelOrName('key'), 'Value', false]);
        $page->returnsByValue('getField', 'Value');
        $browser = $this->loadPage($page);
        $this->assertEqual($browser->getField('key'), 'Value');
        $browser->setField('key', 'Value');
    }
}

class TestOfBrowserNavigation extends UnitTestCase
{
    public function createBrowser($agent, $page)
    {
        $browser = new MockParseSimpleBrowser;
        $browser->returns('createUserAgent', $agent);
        $browser->returns('parse', $page);
        $browser->__constructor();

        return $browser;
    }

    public function testBrowserRequestMethods(): void
    {
        $agent = new MockSimpleUserAgent;
        $agent->returns('fetchResponse', new MockSimpleHttpResponse);
        $agent->expectAt(
            0,
            'fetchResponse',
            [new SimpleUrl('http://this.com/get.req'), new SimpleGetEncoding],
        );
        $agent->expectAt(
            1,
            'fetchResponse',
            [new SimpleUrl('http://this.com/post.req'), new SimplePostEncoding],
        );
        $agent->expectAt(
            2,
            'fetchResponse',
            [new SimpleUrl('http://this.com/put.req'), new SimplePutEncoding],
        );
        $agent->expectAt(
            3,
            'fetchResponse',
            [new SimpleUrl('http://this.com/delete.req'), new SimpleDeleteEncoding],
        );
        $agent->expectAt(
            4,
            'fetchResponse',
            [new SimpleUrl('http://this.com/head.req'), new SimpleHeadEncoding],
        );
        $agent->expectCallCount('fetchResponse', 5);

        $page = new MockSimplePage;

        $browser = $this->createBrowser($agent, $page);
        $browser->get('http://this.com/get.req');
        $browser->post('http://this.com/post.req');
        $browser->put('http://this.com/put.req');
        $browser->delete('http://this.com/delete.req');
        $browser->head('http://this.com/head.req');
    }

    public function testClickLinkRequestsPage(): void
    {
        $agent = new MockSimpleUserAgent;
        $agent->returns('fetchResponse', new MockSimpleHttpResponse);
        $agent->expectAt(
            0,
            'fetchResponse',
            [new SimpleUrl('http://this.com/page.html'), new SimpleGetEncoding],
        );
        $agent->expectAt(
            1,
            'fetchResponse',
            [new SimpleUrl('http://this.com/new.html'), new SimpleGetEncoding],
        );
        $agent->expectCallCount('fetchResponse', 2);

        $page = new MockSimplePage;
        $page->returnsByValue('getUrlsByLabel', [new SimpleUrl('http://this.com/new.html')]);
        $page->expectOnce('getUrlsByLabel', ['New']);
        $page->returnsByValue('getRaw', 'A page');

        $browser = $this->createBrowser($agent, $page);
        $browser->get('http://this.com/page.html');
        $this->assertTrue($browser->clickLink('New'));
    }

    public function testClickLinkWithUnknownFrameStillRequestsWholePage(): void
    {
        $agent = new MockSimpleUserAgent;
        $agent->returns('fetchResponse', new MockSimpleHttpResponse);
        $agent->expectAt(
            0,
            'fetchResponse',
            [new SimpleUrl('http://this.com/page.html'), new SimpleGetEncoding],
        );
        $target = new SimpleUrl('http://this.com/new.html');
        $target->setTarget('missing');
        $agent->expectAt(
            1,
            'fetchResponse',
            [$target, new SimpleGetEncoding],
        );
        $agent->expectCallCount('fetchResponse', 2);

        $parsed_url = new SimpleUrl('http://this.com/new.html');
        $parsed_url->setTarget('missing');

        $page = new MockSimplePage;
        $page->returnsByValue('getUrlsByLabel', [$parsed_url]);
        $page->returnsByValue('hasFrames', false);
        $page->expectOnce('getUrlsByLabel', ['New']);
        $page->returnsByValue('getRaw', 'A page');

        $browser = $this->createBrowser($agent, $page);
        $browser->get('http://this.com/page.html');
        $this->assertTrue($browser->clickLink('New'));
    }

    public function testClickingMissingLinkFails(): void
    {
        $agent = new MockSimpleUserAgent($this);
        $agent->returns('fetchResponse', new MockSimpleHttpResponse);

        $page = new MockSimplePage;
        $page->returnsByValue('getUrlsByLabel', []);
        $page->returnsByValue('getRaw', 'stuff');

        $browser = $this->createBrowser($agent, $page);
        $this->assertTrue($browser->get('http://this.com/page.html'));
        $this->assertFalse($browser->clickLink('New'));
    }

    public function testClickIndexedLink(): void
    {
        $agent = new MockSimpleUserAgent;
        $agent->returns('fetchResponse', new MockSimpleHttpResponse);
        $agent->expectAt(
            1,
            'fetchResponse',
            [new SimpleUrl('1.html'), new SimpleGetEncoding],
        );
        $agent->expectCallCount('fetchResponse', 2);

        $page = new MockSimplePage;
        $page->returnsByValue(
            'getUrlsByLabel',
            [new SimpleUrl('0.html'), new SimpleUrl('1.html')],
        );
        $page->returnsByValue('getRaw', 'A page');

        $browser = $this->createBrowser($agent, $page);
        $browser->get('http://this.com/page.html');
        $this->assertTrue($browser->clickLink('New', 1));
    }

    public function testClinkLinkById(): void
    {
        $agent = new MockSimpleUserAgent;
        $agent->returns('fetchResponse', new MockSimpleHttpResponse);
        $agent->expectAt(1, 'fetchResponse', [
            new SimpleUrl('http://this.com/link.html'),
            new SimpleGetEncoding, ]);
        $agent->expectCallCount('fetchResponse', 2);

        $page = new MockSimplePage;
        $page->returnsByValue('getUrlById', new SimpleUrl('http://this.com/link.html'));
        $page->expectOnce('getUrlById', [2]);
        $page->returnsByValue('getRaw', 'A page');

        $browser = $this->createBrowser($agent, $page);
        $browser->get('http://this.com/page.html');
        $this->assertTrue($browser->clickLinkById(2));
    }

    public function testClickingMissingLinkIdFails(): void
    {
        $agent = new MockSimpleUserAgent;
        $agent->returns('fetchResponse', new MockSimpleHttpResponse);

        $page = new MockSimplePage;
        $page->returnsByValue('getUrlById', false);
        $page->returnsByValue('getUrlsByLabel', []);

        $browser = $this->createBrowser($agent, $page);
        $browser->get('http://this.com/page.html');
        $this->assertFalse($browser->clickLink(0));
    }

    public function testSubmitFormByLabel(): void
    {
        $agent = new MockSimpleUserAgent;
        $agent->returns('fetchResponse', new MockSimpleHttpResponse);
        $agent->expectAt(1, 'fetchResponse', [
            new SimpleUrl('http://this.com/handler.html'),
            new SimplePostEncoding(['a' => 'A']), ]);
        $agent->expectCallCount('fetchResponse', 2);

        $form = new MockSimpleForm;
        $form->returnsByValue('getAction', new SimpleUrl('http://this.com/handler.html'));
        $form->returnsByValue('getMethod', 'post');
        $form->returnsByValue('submitButton', new SimplePostEncoding(['a' => 'A']));
        $form->expectOnce('submitButton', [new SelectByLabel('Go'), false]);

        $page = new MockSimplePage;
        $page->returns('getFormBySubmit', $form);
        $page->expectOnce('getFormBySubmit', [new SelectByLabel('Go')]);
        $page->returnsByValue('getRaw', 'stuff');

        $browser = $this->createBrowser($agent, $page);
        $browser->get('http://this.com/page.html');
        $this->assertTrue($browser->clickSubmit('Go'));
    }

    public function testDefaultSubmitFormByLabel(): void
    {
        $agent = new MockSimpleUserAgent;
        $agent->returns('fetchResponse', new MockSimpleHttpResponse);
        $agent->expectAt(1, 'fetchResponse', [
            new SimpleUrl('http://this.com/page.html'),
            new SimpleGetEncoding(['a' => 'A']), ]);
        $agent->expectCallCount('fetchResponse', 2);

        $form = new MockSimpleForm;
        $form->returnsByValue('getAction', new SimpleUrl('http://this.com/page.html'));
        $form->returnsByValue('getMethod', 'get');
        $form->returnsByValue('submitButton', new SimpleGetEncoding(['a' => 'A']));

        $page = new MockSimplePage;
        $page->returns('getFormBySubmit', $form);
        $page->expectOnce('getFormBySubmit', [new SelectByLabel('Submit')]);
        $page->returnsByValue('getRaw', 'stuff');
        $page->returnsByValue('getUrl', new SimpleUrl('http://this.com/page.html'));

        $browser = $this->createBrowser($agent, $page);
        $browser->get('http://this.com/page.html');
        $this->assertTrue($browser->clickSubmit());
    }

    public function testSubmitFormByName(): void
    {
        $agent = new MockSimpleUserAgent;
        $agent->returns('fetchResponse', new MockSimpleHttpResponse);

        $form = new MockSimpleForm;
        $form->returnsByValue('getAction', new SimpleUrl('http://this.com/handler.html'));
        $form->returnsByValue('getMethod', 'post');
        $form->returnsByValue('submitButton', new SimplePostEncoding(['a' => 'A']));

        $page = new MockSimplePage;
        $page->returns('getFormBySubmit', $form);
        $page->expectOnce('getFormBySubmit', [new SelectByName('me')]);
        $page->returnsByValue('getRaw', 'stuff');

        $browser = $this->createBrowser($agent, $page);
        $browser->get('http://this.com/page.html');
        $this->assertTrue($browser->clickSubmitByName('me'));
    }

    public function testSubmitFormById(): void
    {
        $agent = new MockSimpleUserAgent;
        $agent->returns('fetchResponse', new MockSimpleHttpResponse);

        $form = new MockSimpleForm;
        $form->returnsByValue('getAction', new SimpleUrl('http://this.com/handler.html'));
        $form->returnsByValue('getMethod', 'post');
        $form->returnsByValue('submitButton', new SimplePostEncoding(['a' => 'A']));
        $form->expectOnce('submitButton', [new SelectById(99), false]);

        $page = new MockSimplePage;
        $page->returns('getFormBySubmit', $form);
        $page->expectOnce('getFormBySubmit', [new SelectById(99)]);
        $page->returnsByValue('getRaw', 'stuff');

        $browser = $this->createBrowser($agent, $page);
        $browser->get('http://this.com/page.html');
        $this->assertTrue($browser->clickSubmitById(99));
    }

    public function testSubmitFormByImageLabel(): void
    {
        $agent = new MockSimpleUserAgent;
        $agent->returns('fetchResponse', new MockSimpleHttpResponse);

        $form = new MockSimpleForm;
        $form->returnsByValue('getAction', new SimpleUrl('http://this.com/handler.html'));
        $form->returnsByValue('getMethod', 'post');
        $form->returnsByValue('submitImage', new SimplePostEncoding(['a' => 'A']));
        $form->expectOnce('submitImage', [new SelectByLabel('Go!'), 10, 11, false]);

        $page = new MockSimplePage;
        $page->returns('getFormByImage', $form);
        $page->expectOnce('getFormByImage', [new SelectByLabel('Go!')]);
        $page->returnsByValue('getRaw', 'stuff');

        $browser = $this->createBrowser($agent, $page);
        $browser->get('http://this.com/page.html');
        $this->assertTrue($browser->clickImage('Go!', 10, 11));
    }

    public function testSubmitFormByImageName(): void
    {
        $agent = new MockSimpleUserAgent;
        $agent->returns('fetchResponse', new MockSimpleHttpResponse);

        $form = new MockSimpleForm;
        $form->returnsByValue('getAction', new SimpleUrl('http://this.com/handler.html'));
        $form->returnsByValue('getMethod', 'post');
        $form->returnsByValue('submitImage', new SimplePostEncoding(['a' => 'A']));
        $form->expectOnce('submitImage', [new SelectByName('a'), 10, 11, false]);

        $page = new MockSimplePage;
        $page->returns('getFormByImage', $form);
        $page->expectOnce('getFormByImage', [new SelectByName('a')]);
        $page->returnsByValue('getRaw', 'stuff');

        $browser = $this->createBrowser($agent, $page);
        $browser->get('http://this.com/page.html');
        $this->assertTrue($browser->clickImageByName('a', 10, 11));
    }

    public function testSubmitFormByImageId(): void
    {
        $agent = new MockSimpleUserAgent;
        $agent->returns('fetchResponse', new MockSimpleHttpResponse);

        $form = new MockSimpleForm;
        $form->returnsByValue('getAction', new SimpleUrl('http://this.com/handler.html'));
        $form->returnsByValue('getMethod', 'post');
        $form->returnsByValue('submitImage', new SimplePostEncoding(['a' => 'A']));
        $form->expectOnce('submitImage', [new SelectById(99), 10, 11, false]);

        $page = new MockSimplePage;
        $page->returns('getFormByImage', $form);
        $page->expectOnce('getFormByImage', [new SelectById(99)]);
        $page->returnsByValue('getRaw', 'stuff');

        $browser = $this->createBrowser($agent, $page);
        $browser->get('http://this.com/page.html');
        $this->assertTrue($browser->clickImageById(99, 10, 11));
    }

    public function testSubmitFormByFormId(): void
    {
        $agent = new MockSimpleUserAgent;
        $agent->returns('fetchResponse', new MockSimpleHttpResponse);
        $agent->expectAt(1, 'fetchResponse', [
            new SimpleUrl('http://this.com/handler.html'),
            new SimplePostEncoding(['a' => 'A']), ]);
        $agent->expectCallCount('fetchResponse', 2);

        $form = new MockSimpleForm;
        $form->returnsByValue('getAction', new SimpleUrl('http://this.com/handler.html'));
        $form->returnsByValue('getMethod', 'post');
        $form->returnsByValue('submit', new SimplePostEncoding(['a' => 'A']));

        $page = new MockSimplePage;
        $page->returns('getFormById', $form);
        $page->expectOnce('getFormById', [33]);
        $page->returnsByValue('getRaw', 'stuff');

        $browser = $this->createBrowser($agent, $page);
        $browser->get('http://this.com/page.html');
        $this->assertTrue($browser->submitFormById(33));
    }
}

class TestOfBrowserFrames extends UnitTestCase
{
    public function createBrowser($agent)
    {
        $browser = new MockUserAgentSimpleBrowser;
        $browser->returns('createUserAgent', $agent);
        $browser->__constructor();

        return $browser;
    }

    public function createUserAgent($pages)
    {
        $agent = new MockSimpleUserAgent;

        foreach ($pages as $url => $raw) {
            $url      = new SimpleUrl($url);
            $response = new MockSimpleHttpResponse;
            $response->returnsByValue('getUrl', $url);
            $response->returnsByValue('getContent', $raw);
            $agent->returns('fetchResponse', $response, [$url, '*']);
        }

        return $agent;
    }

    public function testSimplePageHasNoFrames(): void
    {
        $browser = $this->createBrowser($this->createUserAgent(
            ['http://site.with.no.frames/' => 'A non-framed page'],
        ));
        $this->assertEqual(
            $browser->get('http://site.with.no.frames/'),
            'A non-framed page',
        );
        $this->assertIdentical($browser->getFrames(), 'http://site.with.no.frames/');
    }

    public function testFramesetWithSingleFrame(): void
    {
        $frameset = '<frameset><frame name="a" src="frame.html"></frameset>';
        $browser  = $this->createBrowser($this->createUserAgent([
            'http://site.with.one.frame/'           => $frameset,
            'http://site.with.one.frame/frame.html' => 'A frame', ]));
        $this->assertEqual($browser->get('http://site.with.one.frame/'), 'A frame');
        $this->assertIdentical(
            $browser->getFrames(),
            ['a' => 'http://site.with.one.frame/frame.html'],
        );
    }

    public function testTitleTakenFromFramesetPage(): void
    {
        $frameset = '<title>Frameset title</title>' .
                '<frameset><frame name="a" src="frame.html"></frameset>';
        $browser = $this->createBrowser($this->createUserAgent([
            'http://site.with.one.frame/'           => $frameset,
            'http://site.with.one.frame/frame.html' => '<title>Page title</title>', ]));
        $browser->get('http://site.with.one.frame/');
        $this->assertEqual($browser->getTitle(), 'Frameset title');
    }

    public function testFramesetWithSingleUnnamedFrame(): void
    {
        $frameset = '<frameset><frame src="frame.html"></frameset>';
        $browser  = $this->createBrowser($this->createUserAgent([
            'http://site.with.one.frame/'           => $frameset,
            'http://site.with.one.frame/frame.html' => 'One frame', ]));
        $this->assertEqual(
            $browser->get('http://site.with.one.frame/'),
            'One frame',
        );
        $this->assertIdentical(
            $browser->getFrames(),
            [1 => 'http://site.with.one.frame/frame.html'],
        );
    }

    public function testFramesetWithMultipleFrames(): void
    {
        $frameset = '<frameset>' .
                '<frame name="a" src="frame_a.html">' .
                '<frame name="b" src="frame_b.html">' .
                '<frame name="c" src="frame_c.html">' .
                '</frameset>';
        $browser = $this->createBrowser($this->createUserAgent([
            'http://site.with.frames/'             => $frameset,
            'http://site.with.frames/frame_a.html' => 'A frame',
            'http://site.with.frames/frame_b.html' => 'B frame',
            'http://site.with.frames/frame_c.html' => 'C frame', ]));
        $this->assertEqual(
            $browser->get('http://site.with.frames/'),
            'A frameB frameC frame',
        );
        $this->assertIdentical($browser->getFrames(), [
            'a' => 'http://site.with.frames/frame_a.html',
            'b' => 'http://site.with.frames/frame_b.html',
            'c' => 'http://site.with.frames/frame_c.html', ]);
    }

    public function testFrameFocusByName(): void
    {
        $frameset = '<frameset>' .
                '<frame name="a" src="frame_a.html">' .
                '<frame name="b" src="frame_b.html">' .
                '<frame name="c" src="frame_c.html">' .
                '</frameset>';
        $browser = $this->createBrowser($this->createUserAgent([
            'http://site.with.frames/'             => $frameset,
            'http://site.with.frames/frame_a.html' => 'A frame',
            'http://site.with.frames/frame_b.html' => 'B frame',
            'http://site.with.frames/frame_c.html' => 'C frame', ]));
        $browser->get('http://site.with.frames/');
        $browser->setFrameFocus('a');
        $this->assertEqual($browser->getContent(), 'A frame');
        $browser->setFrameFocus('b');
        $this->assertEqual($browser->getContent(), 'B frame');
        $browser->setFrameFocus('c');
        $this->assertEqual($browser->getContent(), 'C frame');
    }

    public function testFramesetWithSomeNamedFrames(): void
    {
        $frameset = '<frameset>' .
                '<frame name="a" src="frame_a.html">' .
                '<frame src="frame_b.html">' .
                '<frame name="c" src="frame_c.html">' .
                '<frame src="frame_d.html">' .
                '</frameset>';
        $browser = $this->createBrowser($this->createUserAgent([
            'http://site.with.frames/'             => $frameset,
            'http://site.with.frames/frame_a.html' => 'A frame',
            'http://site.with.frames/frame_b.html' => 'B frame',
            'http://site.with.frames/frame_c.html' => 'C frame',
            'http://site.with.frames/frame_d.html' => 'D frame', ]));
        $this->assertEqual(
            $browser->get('http://site.with.frames/'),
            'A frameB frameC frameD frame',
        );
        $this->assertIdentical($browser->getFrames(), [
            'a' => 'http://site.with.frames/frame_a.html',
            2   => 'http://site.with.frames/frame_b.html',
            'c' => 'http://site.with.frames/frame_c.html',
            4   => 'http://site.with.frames/frame_d.html', ]);
    }

    public function testFrameFocusWithMixedNamesAndIndexes(): void
    {
        $frameset = '<frameset>' .
                '<frame name="a" src="frame_a.html">' .
                '<frame src="frame_b.html">' .
                '<frame name="c" src="frame_c.html">' .
                '<frame src="frame_d.html">' .
                '</frameset>';
        $browser = $this->createBrowser($this->createUserAgent([
            'http://site.with.frames/'             => $frameset,
            'http://site.with.frames/frame_a.html' => 'A frame',
            'http://site.with.frames/frame_b.html' => 'B frame',
            'http://site.with.frames/frame_c.html' => 'C frame',
            'http://site.with.frames/frame_d.html' => 'D frame', ]));
        $browser->get('http://site.with.frames/');
        $browser->setFrameFocus('a');
        $this->assertEqual($browser->getContent(), 'A frame');
        $browser->setFrameFocus(2);
        $this->assertEqual($browser->getContent(), 'B frame');
        $browser->setFrameFocus('c');
        $this->assertEqual($browser->getContent(), 'C frame');
        $browser->setFrameFocus(4);
        $this->assertEqual($browser->getContent(), 'D frame');
        $browser->clearFrameFocus();
        $this->assertEqual($browser->getContent(), 'A frameB frameC frameD frame');
    }

    public function testNestedFrameset(): void
    {
        $inner = '<frameset>' .
                '<frame name="page" src="page.html">' .
                '</frameset>';
        $outer = '<frameset>' .
                '<frame name="inner" src="inner.html">' .
                '</frameset>';
        $browser = $this->createBrowser($this->createUserAgent([
            'http://site.with.nested.frame/'           => $outer,
            'http://site.with.nested.frame/inner.html' => $inner,
            'http://site.with.nested.frame/page.html'  => 'The page', ]));
        $this->assertEqual(
            $browser->get('http://site.with.nested.frame/'),
            'The page',
        );
        $this->assertIdentical($browser->getFrames(), [
            'inner' => [
                'page' => 'http://site.with.nested.frame/page.html', ], ]);
    }

    public function testCanNavigateToNestedFrame(): void
    {
        $inner = '<frameset>' .
                '<frame name="one" src="one.html">' .
                '<frame name="two" src="two.html">' .
                '</frameset>';
        $outer = '<frameset>' .
                '<frame name="inner" src="inner.html">' .
                '<frame name="three" src="three.html">' .
                '</frameset>';
        $browser = $this->createBrowser($this->createUserAgent([
            'http://site.with.nested.frames/'           => $outer,
            'http://site.with.nested.frames/inner.html' => $inner,
            'http://site.with.nested.frames/one.html'   => 'Page one',
            'http://site.with.nested.frames/two.html'   => 'Page two',
            'http://site.with.nested.frames/three.html' => 'Page three', ]));

        $browser->get('http://site.with.nested.frames/');
        $this->assertEqual($browser->getContent(), 'Page onePage twoPage three');

        $this->assertTrue($browser->setFrameFocus('inner'));
        $this->assertEqual($browser->getFrameFocus(), ['inner']);
        $this->assertTrue($browser->setFrameFocus('one'));
        $this->assertEqual($browser->getFrameFocus(), ['inner', 'one']);
        $this->assertEqual($browser->getContent(), 'Page one');

        $this->assertTrue($browser->setFrameFocus('two'));
        $this->assertEqual($browser->getFrameFocus(), ['inner', 'two']);
        $this->assertEqual($browser->getContent(), 'Page two');

        $browser->clearFrameFocus();
        $this->assertTrue($browser->setFrameFocus('three'));
        $this->assertEqual($browser->getFrameFocus(), ['three']);
        $this->assertEqual($browser->getContent(), 'Page three');

        $this->assertTrue($browser->setFrameFocus('inner'));
        $this->assertEqual($browser->getContent(), 'Page onePage two');
    }

    public function testCanNavigateToNestedFrameByIndex(): void
    {
        $inner = '<frameset>' .
                '<frame src="one.html">' .
                '<frame src="two.html">' .
                '</frameset>';
        $outer = '<frameset>' .
                '<frame src="inner.html">' .
                '<frame src="three.html">' .
                '</frameset>';
        $browser = $this->createBrowser($this->createUserAgent([
            'http://site.with.nested.frames/'           => $outer,
            'http://site.with.nested.frames/inner.html' => $inner,
            'http://site.with.nested.frames/one.html'   => 'Page one',
            'http://site.with.nested.frames/two.html'   => 'Page two',
            'http://site.with.nested.frames/three.html' => 'Page three', ]));

        $browser->get('http://site.with.nested.frames/');
        $this->assertEqual($browser->getContent(), 'Page onePage twoPage three');

        $this->assertTrue($browser->setFrameFocusByIndex(1));
        $this->assertEqual($browser->getFrameFocus(), [1]);
        $this->assertTrue($browser->setFrameFocusByIndex(1));
        $this->assertEqual($browser->getFrameFocus(), [1, 1]);
        $this->assertEqual($browser->getContent(), 'Page one');

        $this->assertTrue($browser->setFrameFocusByIndex(2));
        $this->assertEqual($browser->getFrameFocus(), [1, 2]);
        $this->assertEqual($browser->getContent(), 'Page two');

        $browser->clearFrameFocus();
        $this->assertTrue($browser->setFrameFocusByIndex(2));
        $this->assertEqual($browser->getFrameFocus(), [2]);
        $this->assertEqual($browser->getContent(), 'Page three');

        $this->assertTrue($browser->setFrameFocusByIndex(1));
        $this->assertEqual($browser->getContent(), 'Page onePage two');
    }
}
