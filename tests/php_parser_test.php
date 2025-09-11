<?php declare(strict_types=1);

require_once __DIR__ . '/../src/autorun.php';

require_once __DIR__ . '/../src/php_parser.php';

require_once __DIR__ . '/../src/tag.php';

Mock::generate('SimplePage');
Mock::generate('SimplePhpPageBuilder');
Mock::generate('SimpleHttpResponse');
Mock::generatePartial(
    'SimplePhpPageBuilder',
    'PartialSimplePhpPageBuilder',
    ['createPage', 'createParser'],
);
Mock::generate('SimpleHtmlSaxParser');
Mock::generate('SimplePhpPageBuilder');

class TestOfParallelRegex extends UnitTestCase
{
    public function testNoPatterns(): void
    {
        $regex = new ParallelRegex(false);
        $this->assertFalse($regex->match('Hello', $match));
        $this->assertEqual($match, '');
    }

    public function testNoSubject(): void
    {
        $regex = new ParallelRegex(false);
        $regex->addPattern('.*');
        $this->assertTrue($regex->match('', $match));
        $this->assertEqual($match, '');
    }

    public function testMatchAll(): void
    {
        $regex = new ParallelRegex(false);
        $regex->addPattern('.*');
        $this->assertTrue($regex->match('Hello', $match));
        $this->assertEqual($match, 'Hello');
    }

    public function testCaseSensitive(): void
    {
        $regex = new ParallelRegex(true);
        $regex->addPattern('abc');
        $this->assertTrue($regex->match('abcdef', $match));
        $this->assertEqual($match, 'abc');
        $this->assertTrue($regex->match('AAABCabcdef', $match));
        $this->assertEqual($match, 'abc');
    }

    public function testCaseInsensitive(): void
    {
        $regex = new ParallelRegex(false);
        $regex->addPattern('abc');
        $this->assertTrue($regex->match('abcdef', $match));
        $this->assertEqual($match, 'abc');
        $this->assertTrue($regex->match('AAABCabcdef', $match));
        $this->assertEqual($match, 'ABC');
    }

    public function testMatchMultiple(): void
    {
        $regex = new ParallelRegex(true);
        $regex->addPattern('abc');
        $regex->addPattern('ABC');
        $this->assertTrue($regex->match('abcdef', $match));
        $this->assertEqual($match, 'abc');
        $this->assertTrue($regex->match('AAABCabcdef', $match));
        $this->assertEqual($match, 'ABC');
        $this->assertFalse($regex->match('Hello', $match));
    }

    public function testPatternLabels(): void
    {
        $regex = new ParallelRegex(false);
        $regex->addPattern('abc', 'letter');
        $regex->addPattern('123', 'number');
        $this->assertIdentical($regex->match('abcdef', $match), 'letter');
        $this->assertEqual($match, 'abc');
        $this->assertIdentical($regex->match('0123456789', $match), 'number');
        $this->assertEqual($match, '123');
    }
}

class TestOfStateStack extends UnitTestCase
{
    public function testStartState(): void
    {
        $stack = new SimpleStateStack('one');
        $this->assertEqual($stack->getCurrent(), 'one');
    }

    public function testExhaustion(): void
    {
        $stack = new SimpleStateStack('one');
        $this->assertFalse($stack->leave());
    }

    public function testStateMoves(): void
    {
        $stack = new SimpleStateStack('one');
        $stack->enter('two');
        $this->assertEqual($stack->getCurrent(), 'two');
        $stack->enter('three');
        $this->assertEqual($stack->getCurrent(), 'three');
        $this->assertTrue($stack->leave());
        $this->assertEqual($stack->getCurrent(), 'two');
        $stack->enter('third');
        $this->assertEqual($stack->getCurrent(), 'third');
        $this->assertTrue($stack->leave());
        $this->assertTrue($stack->leave());
        $this->assertEqual($stack->getCurrent(), 'one');
    }
}

class TestParser
{
    public function accept(): void
    {
    }

    public function a(): void
    {
    }

    public function b(): void
    {
    }
}
Mock::generate('TestParser');

class TestOfLexer extends UnitTestCase
{
    public function testEmptyPage(): void
    {
        $handler = new MockTestParser;
        $handler->expectNever('accept');
        $handler->returnsByValue('accept', true);
        $handler->expectNever('accept');
        $handler->returnsByValue('accept', true);
        $lexer = new SimpleLexer($handler);
        $lexer->addPattern('a+');
        $this->assertTrue($lexer->parse(''));
    }

    public function testSinglePattern(): void
    {
        $handler = new MockTestParser;
        $handler->expectAt(0, 'accept', ['aaa', LEXER_MATCHED]);
        $handler->expectAt(1, 'accept', ['x', LEXER_UNMATCHED]);
        $handler->expectAt(2, 'accept', ['a', LEXER_MATCHED]);
        $handler->expectAt(3, 'accept', ['yyy', LEXER_UNMATCHED]);
        $handler->expectAt(4, 'accept', ['a', LEXER_MATCHED]);
        $handler->expectAt(5, 'accept', ['x', LEXER_UNMATCHED]);
        $handler->expectAt(6, 'accept', ['aaa', LEXER_MATCHED]);
        $handler->expectAt(7, 'accept', ['z', LEXER_UNMATCHED]);
        $handler->expectCallCount('accept', 8);
        $handler->returnsByValue('accept', true);
        $lexer = new SimpleLexer($handler);
        $lexer->addPattern('a+');
        $this->assertTrue($lexer->parse('aaaxayyyaxaaaz'));
    }

    public function testMultiplePattern(): void
    {
        $handler = new MockTestParser;
        $target  = ['a', 'b', 'a', 'bb', 'x', 'b', 'a', 'xxxxxx', 'a', 'x'];
        $counter = \count($target);

        for ($i = 0; $i < $counter; $i++) {
            $handler->expectAt($i, 'accept', [$target[$i], '*']);
        }
        $handler->expectCallCount('accept', \count($target));
        $handler->returnsByValue('accept', true);
        $lexer = new SimpleLexer($handler);
        $lexer->addPattern('a+');
        $lexer->addPattern('b+');
        $this->assertTrue($lexer->parse('ababbxbaxxxxxxax'));
    }
}

class TestOfLexerModes extends UnitTestCase
{
    public function testIsolatedPattern(): void
    {
        $handler = new MockTestParser;
        $handler->expectAt(0, 'a', ['a', LEXER_MATCHED]);
        $handler->expectAt(1, 'a', ['b', LEXER_UNMATCHED]);
        $handler->expectAt(2, 'a', ['aa', LEXER_MATCHED]);
        $handler->expectAt(3, 'a', ['bxb', LEXER_UNMATCHED]);
        $handler->expectAt(4, 'a', ['aaa', LEXER_MATCHED]);
        $handler->expectAt(5, 'a', ['x', LEXER_UNMATCHED]);
        $handler->expectAt(6, 'a', ['aaaa', LEXER_MATCHED]);
        $handler->expectAt(7, 'a', ['x', LEXER_UNMATCHED]);
        $handler->expectCallCount('a', 8);
        $handler->returnsByValue('a', true);
        $lexer = new SimpleLexer($handler, 'a');
        $lexer->addPattern('a+', 'a');
        $lexer->addPattern('b+', 'b');
        $this->assertTrue($lexer->parse('abaabxbaaaxaaaax'));
    }

    public function testModeChange(): void
    {
        $handler = new MockTestParser;
        $handler->expectAt(0, 'a', ['a', LEXER_MATCHED]);
        $handler->expectAt(1, 'a', ['b', LEXER_UNMATCHED]);
        $handler->expectAt(2, 'a', ['aa', LEXER_MATCHED]);
        $handler->expectAt(3, 'a', ['b', LEXER_UNMATCHED]);
        $handler->expectAt(4, 'a', ['aaa', LEXER_MATCHED]);
        $handler->expectAt(0, 'b', [':', LEXER_ENTER]);
        $handler->expectAt(1, 'b', ['a', LEXER_UNMATCHED]);
        $handler->expectAt(2, 'b', ['b', LEXER_MATCHED]);
        $handler->expectAt(3, 'b', ['a', LEXER_UNMATCHED]);
        $handler->expectAt(4, 'b', ['bb', LEXER_MATCHED]);
        $handler->expectAt(5, 'b', ['a', LEXER_UNMATCHED]);
        $handler->expectAt(6, 'b', ['bbb', LEXER_MATCHED]);
        $handler->expectAt(7, 'b', ['a', LEXER_UNMATCHED]);
        $handler->expectCallCount('a', 5);
        $handler->expectCallCount('b', 8);
        $handler->returnsByValue('a', true);
        $handler->returnsByValue('b', true);
        $lexer = new SimpleLexer($handler, 'a');
        $lexer->addPattern('a+', 'a');
        $lexer->addEntryPattern(':', 'a', 'b');
        $lexer->addPattern('b+', 'b');
        $this->assertTrue($lexer->parse('abaabaaa:ababbabbba'));
    }

    public function testNesting(): void
    {
        $handler = new MockTestParser;
        $handler->returnsByValue('a', true);
        $handler->returnsByValue('b', true);
        $handler->expectAt(0, 'a', ['aa', LEXER_MATCHED]);
        $handler->expectAt(1, 'a', ['b', LEXER_UNMATCHED]);
        $handler->expectAt(2, 'a', ['aa', LEXER_MATCHED]);
        $handler->expectAt(3, 'a', ['b', LEXER_UNMATCHED]);
        $handler->expectAt(0, 'b', ['(', LEXER_ENTER]);
        $handler->expectAt(1, 'b', ['bb', LEXER_MATCHED]);
        $handler->expectAt(2, 'b', ['a', LEXER_UNMATCHED]);
        $handler->expectAt(3, 'b', ['bb', LEXER_MATCHED]);
        $handler->expectAt(4, 'b', [')', LEXER_EXIT]);
        $handler->expectAt(4, 'a', ['aa', LEXER_MATCHED]);
        $handler->expectAt(5, 'a', ['b', LEXER_UNMATCHED]);
        $handler->expectCallCount('a', 6);
        $handler->expectCallCount('b', 5);
        $lexer = new SimpleLexer($handler, 'a');
        $lexer->addPattern('a+', 'a');
        $lexer->addEntryPattern('(', 'a', 'b');
        $lexer->addPattern('b+', 'b');
        $lexer->addExitPattern(')', 'b');
        $this->assertTrue($lexer->parse('aabaab(bbabb)aab'));
    }

    public function testSingular(): void
    {
        $handler = new MockTestParser;
        $handler->returnsByValue('a', true);
        $handler->returnsByValue('b', true);
        $handler->expectAt(0, 'a', ['aa', LEXER_MATCHED]);
        $handler->expectAt(1, 'a', ['aa', LEXER_MATCHED]);
        $handler->expectAt(2, 'a', ['xx', LEXER_UNMATCHED]);
        $handler->expectAt(3, 'a', ['xx', LEXER_UNMATCHED]);
        $handler->expectAt(0, 'b', ['b', LEXER_SPECIAL]);
        $handler->expectAt(1, 'b', ['bbb', LEXER_SPECIAL]);
        $handler->expectCallCount('a', 4);
        $handler->expectCallCount('b', 2);
        $lexer = new SimpleLexer($handler, 'a');
        $lexer->addPattern('a+', 'a');
        $lexer->addSpecialPattern('b+', 'a', 'b');
        $this->assertTrue($lexer->parse('aabaaxxbbbxx'));
    }

    public function testUnwindTooFar(): void
    {
        $handler = new MockTestParser;
        $handler->returnsByValue('a', true);
        $handler->expectAt(0, 'a', ['aa', LEXER_MATCHED]);
        $handler->expectAt(1, 'a', [')', LEXER_EXIT]);
        $handler->expectCallCount('a', 2);
        $lexer = new SimpleLexer($handler, 'a');
        $lexer->addPattern('a+', 'a');
        $lexer->addExitPattern(')', 'a');
        $this->assertFalse($lexer->parse('aa)aa'));
    }
}

class TestOfLexerHandlers extends UnitTestCase
{
    public function testModeMapping(): void
    {
        $handler = new MockTestParser;
        $handler->returnsByValue('a', true);
        $handler->expectAt(0, 'a', ['aa', LEXER_MATCHED]);
        $handler->expectAt(1, 'a', ['(', LEXER_ENTER]);
        $handler->expectAt(2, 'a', ['bb', LEXER_MATCHED]);
        $handler->expectAt(3, 'a', ['a', LEXER_UNMATCHED]);
        $handler->expectAt(4, 'a', ['bb', LEXER_MATCHED]);
        $handler->expectAt(5, 'a', [')', LEXER_EXIT]);
        $handler->expectAt(6, 'a', ['b', LEXER_UNMATCHED]);
        $handler->expectCallCount('a', 7);
        $lexer = new SimpleLexer($handler, 'mode_a');
        $lexer->addPattern('a+', 'mode_a');
        $lexer->addEntryPattern('(', 'mode_a', 'mode_b');
        $lexer->addPattern('b+', 'mode_b');
        $lexer->addExitPattern(')', 'mode_b');
        $lexer->mapHandler('mode_a', 'a');
        $lexer->mapHandler('mode_b', 'a');
        $this->assertTrue($lexer->parse('aa(bbabb)b'));
    }
}

class TestOfSimpleHtmlLexer extends UnitTestCase
{
    public function createParser()
    {
        $parser = new MockSimpleHtmlSaxParser;
        $parser->returnsByValue('acceptStartToken', true);
        $parser->returnsByValue('acceptEndToken', true);
        $parser->returnsByValue('acceptAttributeToken', true);
        $parser->returnsByValue('acceptTextToken', true);
        $parser->returnsByValue('ignore', true);

        return $parser;
    }

    public function testNoContent(): void
    {
        $parser = $this->createParser();
        $parser->expectNever('acceptStartToken');
        $parser->expectNever('acceptEndToken');
        $parser->expectNever('acceptAttributeToken');
        $parser->expectNever('acceptTextToken');
        $lexer = new SimpleHtmlLexer($parser);
        $this->assertTrue($lexer->parse(''));
    }

    public function testUninteresting(): void
    {
        $parser = $this->createParser();
        $parser->expectOnce('acceptTextToken', ['<html></html>', '*']);
        $lexer = new SimpleHtmlLexer($parser);
        $this->assertTrue($lexer->parse('<html></html>'));
    }

    public function testSkipCss(): void
    {
        $parser = $this->createParser();
        $parser->expectNever('acceptTextToken');
        $parser->expectAtLeastOnce('ignore');
        $lexer = new SimpleHtmlLexer($parser);
        $this->assertTrue($lexer->parse("<style>Lot's of styles</style>"));
    }

    public function testSkipJavaScript(): void
    {
        $parser = $this->createParser();
        $parser->expectNever('acceptTextToken');
        $parser->expectAtLeastOnce('ignore');
        $lexer = new SimpleHtmlLexer($parser);
        $this->assertTrue($lexer->parse("<SCRIPT>Javascript code {';:^%^%�$'@\"*(}</SCRIPT>"));
    }

    public function testSkipHtmlComments(): void
    {
        $parser = $this->createParser();
        $parser->expectNever('acceptTextToken');
        $parser->expectAtLeastOnce('ignore');
        $lexer = new SimpleHtmlLexer($parser);
        $this->assertTrue($lexer->parse('<!-- <title>title</title><style>styles</style> -->'));
    }

    public function testTagWithNoAttributes(): void
    {
        $parser = $this->createParser();
        $parser->expectAt(0, 'acceptStartToken', ['<title', '*']);
        $parser->expectAt(1, 'acceptStartToken', ['>', '*']);
        $parser->expectCallCount('acceptStartToken', 2);
        $parser->expectOnce('acceptTextToken', ['Hello', '*']);
        $parser->expectOnce('acceptEndToken', ['</title>', '*']);
        $lexer = new SimpleHtmlLexer($parser);
        $this->assertTrue($lexer->parse('<title>Hello</title>'));
    }

    public function testTagWithAttributes(): void
    {
        $parser = $this->createParser();
        $parser->expectOnce('acceptTextToken', ['label', '*']);
        $parser->expectAt(0, 'acceptStartToken', ['<a', '*']);
        $parser->expectAt(1, 'acceptStartToken', ['href', '*']);
        $parser->expectAt(2, 'acceptStartToken', ['>', '*']);
        $parser->expectCallCount('acceptStartToken', 3);
        $parser->expectAt(0, 'acceptAttributeToken', ['= "', '*']);
        $parser->expectAt(1, 'acceptAttributeToken', ['here.html', '*']);
        $parser->expectAt(2, 'acceptAttributeToken', ['"', '*']);
        $parser->expectCallCount('acceptAttributeToken', 3);
        $parser->expectOnce('acceptEndToken', ['</a>', '*']);
        $lexer = new SimpleHtmlLexer($parser);
        $this->assertTrue($lexer->parse('<a href = "here.html">label</a>'));
    }
}

class TestOfHtmlSaxParser extends UnitTestCase
{
    public function createListener()
    {
        $listener = new MockSimplePhpPageBuilder;
        $listener->returnsByValue('startElement', true);
        $listener->returnsByValue('addContent', true);
        $listener->returnsByValue('endElement', true);

        return $listener;
    }

    public function testTagWithUnquotedAttributes(): void
    {
        $listener = $this->createListener();
        $listener->expectOnce(
            'startElement',
            ['input', ['name' => 'a.b.c', 'value' => 'd']],
        );
        $parser = new SimpleHtmlSaxParser($listener);
        $this->assertTrue($parser->parse('<input name=a.b.c value = d>'));
    }

    public function testTagInsideContent(): void
    {
        $listener = $this->createListener();
        $listener->expectOnce('startElement', ['a', []]);
        $listener->expectAt(0, 'addContent', ['<html>']);
        $listener->expectAt(1, 'addContent', ['</html>']);
        $parser = new SimpleHtmlSaxParser($listener);
        $this->assertTrue($parser->parse('<html><a></a></html>'));
    }

    public function testTagWithInternalContent(): void
    {
        $listener = $this->createListener();
        $listener->expectOnce('startElement', ['a', []]);
        $listener->expectOnce('addContent', ['label']);
        $listener->expectOnce('endElement', ['a']);
        $parser = new SimpleHtmlSaxParser($listener);
        $this->assertTrue($parser->parse('<a>label</a>'));
    }

    public function testLinkAddress(): void
    {
        $listener = $this->createListener();
        $listener->expectOnce('startElement', ['a', ['href' => 'here.html']]);
        $listener->expectOnce('addContent', ['label']);
        $listener->expectOnce('endElement', ['a']);
        $parser = new SimpleHtmlSaxParser($listener);
        $this->assertTrue($parser->parse("<a href = 'here.html'>label</a>"));
    }

    public function testEncodedAttribute(): void
    {
        $listener = $this->createListener();
        $listener->expectOnce('startElement', ['a', ['href' => 'here&there.html']]);
        $listener->expectOnce('addContent', ['label']);
        $listener->expectOnce('endElement', ['a']);
        $parser = new SimpleHtmlSaxParser($listener);
        $this->assertTrue($parser->parse("<a href = 'here&amp;there.html'>label</a>"));
    }

    public function testTagWithId(): void
    {
        $listener = $this->createListener();
        $listener->expectOnce('startElement', ['a', ['id' => '0']]);
        $listener->expectOnce('addContent', ['label']);
        $listener->expectOnce('endElement', ['a']);
        $parser = new SimpleHtmlSaxParser($listener);
        $this->assertTrue($parser->parse('<a id="0">label</a>'));
    }

    public function testTagWithEmptyAttributes(): void
    {
        $listener = $this->createListener();
        $listener->expectOnce(
            'startElement',
            ['option', ['value' => '', 'selected' => '']],
        );
        $listener->expectOnce('addContent', ['label']);
        $listener->expectOnce('endElement', ['option']);
        $parser = new SimpleHtmlSaxParser($listener);
        $this->assertTrue($parser->parse('<option value="" selected>label</option>'));
    }

    public function testComplexTagWithLotsOfCaseVariations(): void
    {
        $listener = $this->createListener();
        $listener->expectOnce(
            'startElement',
            ['a', ['href' => 'here.html', 'style' => "'cool'"]],
        );
        $listener->expectOnce('addContent', ['label']);
        $listener->expectOnce('endElement', ['a']);
        $parser = new SimpleHtmlSaxParser($listener);
        $this->assertTrue($parser->parse('<A HREF = \'here.html\' Style="\'cool\'">label</A>'));
    }

    public function testXhtmlSelfClosingTag(): void
    {
        $listener = $this->createListener();
        $listener->expectOnce(
            'startElement',
            ['input', ['type' => 'submit', 'name' => 'N', 'value' => 'V']],
        );
        $parser = new SimpleHtmlSaxParser($listener);
        $this->assertTrue($parser->parse('<input type="submit" name="N" value="V" />'));
    }
}
