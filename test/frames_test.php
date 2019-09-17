<?php

require_once __DIR__.'/../src/autorun.php';
require_once __DIR__.'/../src/tag.php';
require_once __DIR__.'/../src/page.php';
require_once __DIR__.'/../src/frames.php';

Mock::generate('SimplePage');
Mock::generate('SimpleForm');

class TestOfFrameset extends UnitTestCase
{
    public function testTitleReadFromFramesetPage()
    {
        $page = new MockSimplePage();
        $page->returnsByValue('getTitle', 'This page');
        $frameset = new SimpleFrameset($page);
        $this->assertEqual($frameset->getTitle(), 'This page');
    }

    public function testHeadersReadFromFramesetByDefault()
    {
        $page = new MockSimplePage();
        $page->returnsByValue('getHeaders', 'Header: content');
        $page->returnsByValue('getMimeType', 'text/xml');
        $page->returnsByValue('getResponseCode', 401);
        $page->returnsByValue('getTransportError', 'Could not parse headers');
        $page->returnsByValue('getAuthentication', 'Basic');
        $page->returnsByValue('getRealm', 'Safe place');

        $frameset = new SimpleFrameset($page);

        $this->assertIdentical($frameset->getHeaders(), 'Header: content');
        $this->assertIdentical($frameset->getMimeType(), 'text/xml');
        $this->assertIdentical($frameset->getResponseCode(), 401);
        $this->assertIdentical($frameset->getTransportError(), 'Could not parse headers');
        $this->assertIdentical($frameset->getAuthentication(), 'Basic');
        $this->assertIdentical($frameset->getRealm(), 'Safe place');
    }

    public function testEmptyFramesetHasNoContent()
    {
        $page = new MockSimplePage();
        $page->returnsByValue('getRaw', 'This content');
        $frameset = new SimpleFrameset($page);
        $this->assertEqual($frameset->getRaw(), '');
    }

    public function testRawContentIsFromOnlyFrame()
    {
        $page = new MockSimplePage();
        $page->expectNever('getRaw');

        $frame = new MockSimplePage();
        $frame->returnsByValue('getRaw', 'Stuff');

        $frameset = new SimpleFrameset($page);
        $frameset->addFrame($frame);
        $this->assertEqual($frameset->getRaw(), 'Stuff');
    }

    public function testRawContentIsFromAllFrames()
    {
        $page = new MockSimplePage();
        $page->expectNever('getRaw');

        $frame1 = new MockSimplePage();
        $frame1->returnsByValue('getRaw', 'Stuff1');

        $frame2 = new MockSimplePage();
        $frame2->returnsByValue('getRaw', 'Stuff2');

        $frameset = new SimpleFrameset($page);
        $frameset->addFrame($frame1);
        $frameset->addFrame($frame2);
        $this->assertEqual($frameset->getRaw(), 'Stuff1Stuff2');
    }

    public function testTextContentIsFromOnlyFrame()
    {
        $page = new MockSimplePage();
        $page->expectNever('getText');

        $frame = new MockSimplePage();
        $frame->returnsByValue('getText', 'Stuff');

        $frameset = new SimpleFrameset($page);
        $frameset->addFrame($frame);
        $this->assertEqual($frameset->getText(), 'Stuff');
    }

    public function testTextContentIsFromAllFrames()
    {
        $page = new MockSimplePage();
        $page->expectNever('getText');

        $frame1 = new MockSimplePage();
        $frame1->returnsByValue('getText', 'Stuff1');

        $frame2 = new MockSimplePage();
        $frame2->returnsByValue('getText', 'Stuff2');

        $frameset = new SimpleFrameset($page);
        $frameset->addFrame($frame1);
        $frameset->addFrame($frame2);
        $this->assertEqual($frameset->getText(), 'Stuff1 Stuff2');
    }

    public function testFieldFoundIsFirstInFramelist()
    {
        $frame1 = new MockSimplePage();
        $frame1->returnsByValue('getField', null);
        $frame1->expectOnce('getField', [new SelectByName('a')]);

        $frame2 = new MockSimplePage();
        $frame2->returnsByValue('getField', 'A');
        $frame2->expectOnce('getField', [new SelectByName('a')]);

        $frame3 = new MockSimplePage();
        $frame3->expectNever('getField');

        $page = new MockSimplePage();
        $frameset = new SimpleFrameset($page);
        $frameset->addFrame($frame1);
        $frameset->addFrame($frame2);
        $frameset->addFrame($frame3);
        $this->assertIdentical($frameset->getField(new SelectByName('a')), 'A');
    }

    public function testFrameReplacementByIndex()
    {
        $page = new MockSimplePage();
        $page->expectNever('getRaw');

        $frame1 = new MockSimplePage();
        $frame1->returnsByValue('getRaw', 'Stuff1');

        $frame2 = new MockSimplePage();
        $frame2->returnsByValue('getRaw', 'Stuff2');

        $frameset = new SimpleFrameset($page);
        $frameset->addFrame($frame1);
        $frameset->setFrame([1], $frame2);
        $this->assertEqual($frameset->getRaw(), 'Stuff2');
    }

    public function testFrameReplacementByName()
    {
        $page = new MockSimplePage();
        $page->expectNever('getRaw');

        $frame1 = new MockSimplePage();
        $frame1->returnsByValue('getRaw', 'Stuff1');

        $frame2 = new MockSimplePage();
        $frame2->returnsByValue('getRaw', 'Stuff2');

        $frameset = new SimpleFrameset($page);
        $frameset->addFrame($frame1, 'a');
        $frameset->setFrame(['a'], $frame2);
        $this->assertEqual($frameset->getRaw(), 'Stuff2');
    }
}

class TestOfFrameNavigation extends UnitTestCase
{
    public function testStartsWithoutFrameFocus()
    {
        $page = new MockSimplePage();
        $frameset = new SimpleFrameset($page);
        $frameset->addFrame(new MockSimplePage());
        $this->assertFalse($frameset->getFrameFocus());
    }

    public function testCanFocusOnSingleFrame()
    {
        $page = new MockSimplePage();
        $page->expectNever('getRaw');

        $frame = new MockSimplePage();
        $frame->returnsByValue('getFrameFocus', []);
        $frame->returnsByValue('getRaw', 'Stuff');

        $frameset = new SimpleFrameset($page);
        $frameset->addFrame($frame);

        $this->assertFalse($frameset->setFrameFocusByIndex(0));
        $this->assertTrue($frameset->setFrameFocusByIndex(1));
        $this->assertEqual($frameset->getRaw(), 'Stuff');
        $this->assertFalse($frameset->setFrameFocusByIndex(2));
        $this->assertIdentical($frameset->getFrameFocus(), [1]);
    }

    public function testContentComesFromFrameInFocus()
    {
        $page = new MockSimplePage();

        $frame1 = new MockSimplePage();
        $frame1->returnsByValue('getRaw', 'Stuff1');
        $frame1->returnsByValue('getFrameFocus', []);

        $frame2 = new MockSimplePage();
        $frame2->returnsByValue('getRaw', 'Stuff2');
        $frame2->returnsByValue('getFrameFocus', []);

        $frameset = new SimpleFrameset($page);
        $frameset->addFrame($frame1);
        $frameset->addFrame($frame2);

        $this->assertTrue($frameset->setFrameFocusByIndex(1));
        $this->assertEqual($frameset->getFrameFocus(), [1]);
        $this->assertEqual($frameset->getRaw(), 'Stuff1');

        $this->assertTrue($frameset->setFrameFocusByIndex(2));
        $this->assertEqual($frameset->getFrameFocus(), [2]);
        $this->assertEqual($frameset->getRaw(), 'Stuff2');

        $this->assertFalse($frameset->setFrameFocusByIndex(3));
        $this->assertEqual($frameset->getFrameFocus(), [2]);

        $frameset->clearFrameFocus();
        $this->assertEqual($frameset->getRaw(), 'Stuff1Stuff2');
    }

    public function testCanFocusByName()
    {
        $page = new MockSimplePage();

        $frame1 = new MockSimplePage();
        $frame1->returnsByValue('getRaw', 'Stuff1');
        $frame1->returnsByValue('getFrameFocus', []);

        $frame2 = new MockSimplePage();
        $frame2->returnsByValue('getRaw', 'Stuff2');
        $frame2->returnsByValue('getFrameFocus', []);

        $frameset = new SimpleFrameset($page);
        $frameset->addFrame($frame1, 'A');
        $frameset->addFrame($frame2, 'B');

        $this->assertTrue($frameset->setFrameFocus('A'));
        $this->assertEqual($frameset->getFrameFocus(), ['A']);
        $this->assertEqual($frameset->getRaw(), 'Stuff1');

        $this->assertTrue($frameset->setFrameFocusByIndex(2));
        $this->assertEqual($frameset->getFrameFocus(), ['B']);
        $this->assertEqual($frameset->getRaw(), 'Stuff2');

        $this->assertFalse($frameset->setFrameFocus('z'));

        $frameset->clearFrameFocus();
        $this->assertEqual($frameset->getRaw(), 'Stuff1Stuff2');
    }
}

class TestOfFramesetPageInterface extends UnitTestCase
{
    private $page_interface;
    private $frameset_interface;

    public function __construct()
    {
        parent::__construct();
        $this->page_interface = $this->getPageMethods();
        $this->frameset_interface = $this->getFramesetMethods();
    }

    public function assertListInAnyOrder($list, $expected)
    {
        sort($list);
        sort($expected);
        $this->assertEqual($list, $expected);
    }

    private function getPageMethods()
    {
        $methods = [];
        foreach (get_class_methods('SimplePage') as $method) {
            if (strtolower($method) === strtolower('SimplePage')) {
                continue;
            }
            if (strtolower($method) === strtolower('getFrameset')) {
                continue;
            }
            if (0 == strncmp($method, '_', 1)) {
                continue;
            }
            if (in_array($method, ['setTitle', 'setBase', 'setForms', 'normalise', 'setFrames', 'addLink'])) {
                continue;
            }
            $methods[] = $method;
        }

        return $methods;
    }

    private function getFramesetMethods()
    {
        $methods = [];
        foreach (get_class_methods('SimpleFrameset') as $method) {
            if (strtolower($method) === strtolower('SimpleFrameset')) {
                continue;
            }
            if (0 == strncmp($method, '_', 1)) {
                continue;
            }
            if (0 == strncmp($method, 'add', 3)) {
                continue;
            }
            $methods[] = $method;
        }

        return $methods;
    }

    public function testFramsetHasPageInterface()
    {
        $difference = [];
        foreach ($this->page_interface as $method) {
            if (!in_array($method, $this->frameset_interface)) {
                $this->fail("No [$method] in Frameset class");

                return;
            }
        }
        $this->pass('Frameset covers Page interface');
    }

    public function testHeadersReadFromFrameIfInFocus()
    {
        $frame = new MockSimplePage();
        $frame->returnsByValue('getUrl', new SimpleUrl('http://localhost/stuff'));

        $frame->returnsByValue('getRequest', 'POST stuff');
        $frame->returnsByValue('getMethod', 'POST');
        $frame->returnsByValue('getRequestData', ['a' => 'A']);
        $frame->returnsByValue('getHeaders', 'Header: content');
        $frame->returnsByValue('getMimeType', 'text/xml');
        $frame->returnsByValue('getResponseCode', 401);
        $frame->returnsByValue('getTransportError', 'Could not parse headers');
        $frame->returnsByValue('getAuthentication', 'Basic');
        $frame->returnsByValue('getRealm', 'Safe place');

        $frameset = new SimpleFrameset(new MockSimplePage());
        $frameset->addFrame($frame);
        $frameset->setFrameFocusByIndex(1);

        $url = new SimpleUrl('http://localhost/stuff');
        $url->setTarget(1);
        $this->assertIdentical($frameset->getUrl(), $url);

        $this->assertIdentical($frameset->getRequest(), 'POST stuff');
        $this->assertIdentical($frameset->getMethod(), 'POST');
        $this->assertIdentical($frameset->getRequestData(), ['a' => 'A']);
        $this->assertIdentical($frameset->getHeaders(), 'Header: content');
        $this->assertIdentical($frameset->getMimeType(), 'text/xml');
        $this->assertIdentical($frameset->getResponseCode(), 401);
        $this->assertIdentical($frameset->getTransportError(), 'Could not parse headers');
        $this->assertIdentical($frameset->getAuthentication(), 'Basic');
        $this->assertIdentical($frameset->getRealm(), 'Safe place');
    }

    public function testUrlsComeFromBothFrames()
    {
        $page = new MockSimplePage();
        $page->expectNever('getUrls');

        $frame1 = new MockSimplePage();
        $frame1->returnsByValue(
            'getUrls',
            ['http://www.lastcraft.com/', 'http://myserver/']
        );

        $frame2 = new MockSimplePage();
        $frame2->returnsByValue(
            'getUrls',
            ['http://www.lastcraft.com/', 'http://test/']
        );

        $frameset = new SimpleFrameset($page);
        $frameset->addFrame($frame1);
        $frameset->addFrame($frame2);
        $this->assertListInAnyOrder(
            $frameset->getUrls(),
            ['http://www.lastcraft.com/', 'http://myserver/', 'http://test/']
        );
    }

    public function testLabelledUrlsComeFromBothFrames()
    {
        $frame1 = new MockSimplePage();
        $frame1->returnsByValue(
            'getUrlsByLabel',
            [new SimpleUrl('goodbye.php')],
            ['a']
        );

        $frame2 = new MockSimplePage();
        $frame2->returnsByValue(
            'getUrlsByLabel',
            [new SimpleUrl('hello.php')],
            ['a']
        );

        $frameset = new SimpleFrameset(new MockSimplePage());
        $frameset->addFrame($frame1);
        $frameset->addFrame($frame2, 'Two');

        $expected1 = new SimpleUrl('goodbye.php');
        $expected1->setTarget(1);
        $expected2 = new SimpleUrl('hello.php');
        $expected2->setTarget('Two');
        $this->assertEqual(
            $frameset->getUrlsByLabel('a'),
            [$expected1, $expected2]
        );
    }

    public function testUrlByIdComesFromFirstFrameToRespond()
    {
        $frame1 = new MockSimplePage();
        $frame1->returnsByValue('getUrlById', new SimpleUrl('four.php'), [4]);
        $frame1->returnsByValue('getUrlById', false, [5]);

        $frame2 = new MockSimplePage();
        $frame2->returnsByValue('getUrlById', false, [4]);
        $frame2->returnsByValue('getUrlById', new SimpleUrl('five.php'), [5]);

        $frameset = new SimpleFrameset(new MockSimplePage());
        $frameset->addFrame($frame1);
        $frameset->addFrame($frame2);

        $four = new SimpleUrl('four.php');
        $four->setTarget(1);
        $this->assertEqual($frameset->getUrlById(4), $four);
        $five = new SimpleUrl('five.php');
        $five->setTarget(2);
        $this->assertEqual($frameset->getUrlById(5), $five);
    }

    public function testReadUrlsFromFrameInFocus()
    {
        $frame1 = new MockSimplePage();
        $frame1->returnsByValue('getUrls', ['a']);
        $frame1->returnsByValue('getUrlsByLabel', [new SimpleUrl('l')]);
        $frame1->returnsByValue('getUrlById', new SimpleUrl('i'));

        $frame2 = new MockSimplePage();
        $frame2->expectNever('getUrls');
        $frame2->expectNever('getUrlsByLabel');
        $frame2->expectNever('getUrlById');

        $frameset = new SimpleFrameset(new MockSimplePage());
        $frameset->addFrame($frame1, 'A');
        $frameset->addFrame($frame2, 'B');
        $frameset->setFrameFocus('A');

        $this->assertIdentical($frameset->getUrls(), ['a']);
        $expected = new SimpleUrl('l');
        $expected->setTarget('A');
        $this->assertIdentical($frameset->getUrlsByLabel('label'), [$expected]);
        $expected = new SimpleUrl('i');
        $expected->setTarget('A');
        $this->assertIdentical($frameset->getUrlById(99), $expected);
    }

    public function testReadFrameTaggedUrlsFromFrameInFocus()
    {
        $frame = new MockSimplePage();

        $by_label = new SimpleUrl('l');
        $by_label->setTarget('L');
        $frame->returnsByValue('getUrlsByLabel', [$by_label]);

        $by_id = new SimpleUrl('i');
        $by_id->setTarget('I');
        $frame->returnsByValue('getUrlById', $by_id);

        $frameset = new SimpleFrameset(new MockSimplePage());
        $frameset->addFrame($frame, 'A');
        $frameset->setFrameFocus('A');

        $this->assertIdentical($frameset->getUrlsByLabel('label'), [$by_label]);
        $this->assertIdentical($frameset->getUrlById(99), $by_id);
    }

    public function testFindingFormsById()
    {
        $frame = new MockSimplePage();
        $form = new MockSimpleForm();
        $frame->returns('getFormById', $form, ['a']);

        $frameset = new SimpleFrameset(new MockSimplePage());
        $frameset->addFrame(new MockSimplePage(), 'A');
        $frameset->addFrame($frame, 'B');
        $this->assertSame($frameset->getFormById('a'), $form);

        $frameset->setFrameFocus('A');
        $this->assertNull($frameset->getFormById('a'));

        $frameset->setFrameFocus('B');
        $this->assertSame($frameset->getFormById('a'), $form);
    }

    public function testFindingFormsBySubmit()
    {
        $frame = new MockSimplePage();
        $form = new MockSimpleForm();
        $frame->returns(
            'getFormBySubmit',
            $form,
            [new SelectByLabel('a')]
        );

        $frameset = new SimpleFrameset(new MockSimplePage());
        $frameset->addFrame(new MockSimplePage(), 'A');
        $frameset->addFrame($frame, 'B');
        $this->assertSame($frameset->getFormBySubmit(new SelectByLabel('a')), $form);

        $frameset->setFrameFocus('A');
        $this->assertNull($frameset->getFormBySubmit(new SelectByLabel('a')));

        $frameset->setFrameFocus('B');
        $this->assertSame($frameset->getFormBySubmit(new SelectByLabel('a')), $form);
    }

    public function testFindingFormsByImage()
    {
        $frame = new MockSimplePage();
        $form = new MockSimpleForm();
        $frame->returns(
            'getFormByImage',
            $form,
            [new SelectByLabel('a')]
        );

        $frameset = new SimpleFrameset(new MockSimplePage());
        $frameset->addFrame(new MockSimplePage(), 'A');
        $frameset->addFrame($frame, 'B');
        $this->assertSame($frameset->getFormByImage(new SelectByLabel('a')), $form);

        $frameset->setFrameFocus('A');
        $this->assertNull($frameset->getFormByImage(new SelectByLabel('a')));

        $frameset->setFrameFocus('B');
        $this->assertSame($frameset->getFormByImage(new SelectByLabel('a')), $form);
    }

    public function testSettingAllFrameFieldsWhenNoFrameFocus()
    {
        $frame1 = new MockSimplePage();
        $frame1->expectOnce('setField', [new SelectById(22), 'A']);

        $frame2 = new MockSimplePage();
        $frame2->expectOnce('setField', [new SelectById(22), 'A']);

        $frameset = new SimpleFrameset(new MockSimplePage());
        $frameset->addFrame($frame1, 'A');
        $frameset->addFrame($frame2, 'B');
        $frameset->setField(new SelectById(22), 'A');
    }

    public function testOnlySettingFieldFromFocusedFrame()
    {
        $frame1 = new MockSimplePage();
        $frame1->expectOnce('setField', [new SelectByLabelOrName('a'), 'A']);

        $frame2 = new MockSimplePage();
        $frame2->expectNever('setField');

        $frameset = new SimpleFrameset(new MockSimplePage());
        $frameset->addFrame($frame1, 'A');
        $frameset->addFrame($frame2, 'B');
        $frameset->setFrameFocus('A');
        $frameset->setField(new SelectByLabelOrName('a'), 'A');
    }

    public function testOnlyGettingFieldFromFocusedFrame()
    {
        $frame1 = new MockSimplePage();
        $frame1->returnsByValue('getField', 'f', [new SelectByName('a')]);

        $frame2 = new MockSimplePage();
        $frame2->expectNever('getField');

        $frameset = new SimpleFrameset(new MockSimplePage());
        $frameset->addFrame($frame1, 'A');
        $frameset->addFrame($frame2, 'B');
        $frameset->setFrameFocus('A');
        $this->assertIdentical($frameset->getField(new SelectByName('a')), 'f');
    }
}
