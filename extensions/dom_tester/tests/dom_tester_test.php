<?php declare(strict_types=1);

require_once __DIR__ . '/../../../src/autorun.php';

require_once __DIR__ . '/../../dom_tester.php';

class TestOfLiveCssSelectors extends DomTestCase
{
    protected function setUp(): void
    {
        $this->addHeader('User-Agent: SimpleTest ' . SimpleTest::getVersion());
    }

    public function testGet(): void
    {
        $url = 'file://' . __DIR__ . '/support/dom_tester.html';
        $this->assertTrue($this->get($url));
        $this->assertElementsBySelector('h1', ['Test page']);
        $this->assertElementsBySelector('ul#list li a[href]', ['link']);
        $this->assertElementsBySelector('body  h1', ['Test page']);
        $this->assertElementsBySelector('#mybar', ['myfoo bis']);
    }
}

class TestOfCssSelectors extends UnitTestCase
{
    public $dom;

    protected function setUp(): void
    {
        $html                       = \file_get_contents(__DIR__ . '/support/dom_tester.html');
        $this->dom                  = new DomDocument('1.0', 'utf-8');
        $this->dom->validateOnParse = true;
        $this->dom->loadHTML($html);
    }

    public function testBasicSelector(): void
    {
        $expectation = new CssSelectorExpectation($this->dom, 'h1');
        $this->assertTrue($expectation->test(['Test page']));

        $expectation = new CssSelectorExpectation($this->dom, 'h2');
        $this->assertTrue($expectation->test(['Title 1', 'Title 2']));

        $expectation = new CssSelectorExpectation($this->dom, '#footer');
        $this->assertTrue($expectation->test(['footer']));

        $expectation = new CssSelectorExpectation($this->dom, 'div#footer');
        $this->assertTrue($expectation->test(['footer']));

        $expectation = new CssSelectorExpectation($this->dom, '.header');
        $this->assertTrue($expectation->test(['header']));

        $expectation = new CssSelectorExpectation($this->dom, 'p.header');
        $this->assertTrue($expectation->test(['header']));

        $expectation = new CssSelectorExpectation($this->dom, 'div.header');
        $this->assertTrue($expectation->test([]));

        $expectation = new CssSelectorExpectation($this->dom, 'ul#mylist ul li');
        $this->assertTrue($expectation->test(['element 3', 'element 4']));

        $expectation = new CssSelectorExpectation($this->dom, '#nonexistant');
        $this->assertTrue($expectation->test([]));
    }

    public function testAttributeSelectors(): void
    {
        $expectation = new CssSelectorExpectation($this->dom, 'ul#list li a[href]');
        $this->assertTrue($expectation->test(['link']));

        $expectation = new CssSelectorExpectation($this->dom, 'ul#list li a[class~="foo1"]');
        $this->assertTrue($expectation->test(['link']));

        $expectation = new CssSelectorExpectation($this->dom, 'ul#list li a[class~="bar1"]');
        $this->assertTrue($expectation->test(['link']));

        $expectation = new CssSelectorExpectation($this->dom, 'ul#list li a[class~="foobar1"]');
        $this->assertTrue($expectation->test(['link']));

        $expectation = new CssSelectorExpectation($this->dom, 'ul#list li a[class^="foo1"]');
        $this->assertTrue($expectation->test(['link']));

        $expectation = new CssSelectorExpectation($this->dom, 'ul#list li a[class$="foobar1"]');
        $this->assertTrue($expectation->test(['link']));

        $expectation = new CssSelectorExpectation($this->dom, 'ul#list li a[class*="oba"]');
        $this->assertTrue($expectation->test(['link']));

        $expectation = new CssSelectorExpectation($this->dom, 'ul#list li a[href="http://www.google.com/"]');
        $this->assertTrue($expectation->test(['link']));

        $expectation = new CssSelectorExpectation($this->dom, 'ul#anotherlist li a[class|="bar1"]');
        $this->assertTrue($expectation->test(['another link']));

        $expectation = new CssSelectorExpectation($this->dom, 'ul#list li a[class*="oba"][class*="ba"]');
        $this->assertTrue($expectation->test(['link']));

        $expectation = new CssSelectorExpectation($this->dom, 'p[class="myfoo"][id="mybar"]');
        $this->assertTrue($expectation->test(['myfoo bis']));

        $expectation = new CssSelectorExpectation($this->dom, 'p[onclick*="a . and a #"]');
        $this->assertTrue($expectation->test(['works great']));
    }

    public function testCombinators(): void
    {
        $expectation = new CssSelectorExpectation($this->dom, 'body  h1');
        $this->assertTrue($expectation->test(['Test page']));

        $expectation = new CssSelectorExpectation($this->dom, 'div#combinators > ul  >   li');
        $this->assertTrue($expectation->test(['test 1', 'test 2']));

        $expectation = new CssSelectorExpectation($this->dom, 'div#combinators>ul>li');
        $this->assertTrue($expectation->test(['test 1', 'test 2']));

        $expectation = new CssSelectorExpectation($this->dom, 'div#combinators li  +   li');
        $this->assertTrue($expectation->test(['test 2', 'test 4']));

        $expectation = new CssSelectorExpectation($this->dom, 'div#combinators li+li');
        $this->assertTrue($expectation->test(['test 2', 'test 4']));

        $expectation = new CssSelectorExpectation($this->dom, 'h1, h2');
        $this->assertTrue($expectation->test(['Test page', 'Title 1', 'Title 2']));

        $expectation = new CssSelectorExpectation($this->dom, 'h1,h2');
        $this->assertTrue($expectation->test(['Test page', 'Title 1', 'Title 2']));

        $expectation = new CssSelectorExpectation($this->dom, 'h1  ,   h2');
        $this->assertTrue($expectation->test(['Test page', 'Title 1', 'Title 2']));

        $expectation = new CssSelectorExpectation($this->dom, 'h1, h1,h1');
        $this->assertTrue($expectation->test(['Test page']));

        $expectation = new CssSelectorExpectation($this->dom, 'h1,h2,h1');
        $this->assertTrue($expectation->test(['Test page', 'Title 1', 'Title 2']));

        $expectation = new CssSelectorExpectation($this->dom, 'p[onclick*="a . and a #"], div#combinators > ul li + li');
        $this->assertTrue($expectation->test(['works great', 'test 2', 'test 4']));
    }

    /* TODO - disabled, because tests fail (fix implementation or fix tests) -- jakoch
    /*public function testChildSelectors()
    {
        $expectation = new CssSelectorExpectation($this->dom, '.myfoo:contains("bis")');
        $this->assertTrue($expectation->test(array('myfoo bis')));

        $expectation = new CssSelectorExpectation($this->dom, '.myfoo:eq(1)');
        $this->assertTrue($expectation->test(array('myfoo bis')));

        $expectation = new CssSelectorExpectation($this->dom, '.myfoo:last');
        $this->assertTrue($expectation->test(array('myfoo bis')));

        $expectation = new CssSelectorExpectation($this->dom, '.myfoo:first');
        $this->assertTrue($expectation->test(array('myfoo')));

        $expectation = new CssSelectorExpectation($this->dom, 'h2:first');
        $this->assertTrue($expectation->test(array('Title 1')));

        $expectation = new CssSelectorExpectation($this->dom, 'h2:first');
        $this->assertTrue($expectation->test(array('Title 1')));

        $expectation = new CssSelectorExpectation($this->dom, 'p.myfoo:first');
        $this->assertTrue($expectation->test(array('myfoo')));

        $expectation = new CssSelectorExpectation($this->dom, 'p:lt(2)');
        $this->assertTrue($expectation->test(array('header', 'multi-classes')));

        $expectation = new CssSelectorExpectation($this->dom, 'p:gt(2)');
        $this->assertTrue($expectation->test(array('myfoo bis', 'works great', 'First paragraph', 'Second paragraph', 'Third paragraph')));

        $expectation = new CssSelectorExpectation($this->dom, 'p:odd');
        $this->assertTrue($expectation->test(array('multi-classes', 'myfoo bis', 'First paragraph', 'Third paragraph')));

        $expectation = new CssSelectorExpectation($this->dom, 'p:even');
        $this->assertTrue($expectation->test(array('header', 'myfoo', 'works great', 'Second paragraph')));

        $expectation = new CssSelectorExpectation($this->dom, '#simplelist li:first-child');
        $this->assertTrue($expectation->test(array('First', 'First')));

        $expectation = new CssSelectorExpectation($this->dom, '#simplelist li:nth-child(1)');
        $this->assertTrue($expectation->test(array('First', 'First')));

        $expectation = new CssSelectorExpectation($this->dom, '#simplelist li:nth-child(2)');
        $this->assertTrue($expectation->test(array('Second with a link', 'Second')));

        $expectation = new CssSelectorExpectation($this->dom, '#simplelist li:nth-child(3)');
        $this->assertTrue($expectation->test(array('Third with another link')));

        $expectation = new CssSelectorExpectation($this->dom, '#simplelist li:last-child');
        $this->assertTrue($expectation->test(array('Second with a link', 'Third with another link')));
    }*/
}

class TestsOfChildAndAdjacentSelectors extends DomTestCase
{
    public function __construct()
    {
        $html                       = \file_get_contents(__DIR__ . '/support/child_adjacent.html');
        $this->dom                  = new DomDocument('1.0', 'utf-8');
        $this->dom->validateOnParse = true;
        $this->dom->loadHTML($html);
    }

    public function testFirstChild(): void
    {
        $expectation = new CssSelectorExpectation($this->dom, 'p:first-child');
        $this->assertTrue($expectation->test(['First paragraph']));

        $expectation = new CssSelectorExpectation($this->dom, 'body > p:first-child');
        $this->assertTrue($expectation->test(['First paragraph']));

        $expectation = new CssSelectorExpectation($this->dom, 'body > p > a:first-child');
        $this->assertTrue($expectation->test(['paragraph']));
    }

    public function testChildren(): void
    {
        $expectation = new CssSelectorExpectation($this->dom, 'body > p');
        $this->assertTrue($expectation->test(['First paragraph', 'Second paragraph', 'Third paragraph']));

        $expectation = new CssSelectorExpectation($this->dom, 'body > p > a');
        $this->assertTrue($expectation->test(['paragraph']));
    }

    public function testAdjacents(): void
    {
        $expectation = new CssSelectorExpectation($this->dom, 'p + p');
        $this->assertTrue($expectation->test(['Second paragraph', 'Third paragraph']));

        $expectation = new CssSelectorExpectation($this->dom, 'body > p + p');
        $this->assertTrue($expectation->test(['Second paragraph', 'Third paragraph']));

        $expectation = new CssSelectorExpectation($this->dom, 'body > p + p > a');
        $this->assertTrue($expectation->test(['paragraph']));
    }
}
