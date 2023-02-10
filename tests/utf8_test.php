<?php

// Handle with care : this file is UTF8.

require_once __DIR__.'/../src/autorun.php';
require_once __DIR__.'/../src/php_parser.php';
require_once __DIR__.'/../src/url.php';

Mock::generate('SimpleHtmlSaxParser');
Mock::generate('SimplePhpPageBuilder');

class TestOfHtmlSaxParserWithDifferentCharset extends UnitTestCase
{
    public function testWithTextInUTF8()
    {
        $regex = new ParallelRegex(false);
        $regex->addPattern('eé');
        $this->assertTrue($regex->match('eéêè', $match));
        $this->assertEqual($match, 'eé');
    }

    public function testWithTextInLatin1()
    {
        $regex = new ParallelRegex(false);
        $regex->addPattern(utf8_decode('eé'));
        $this->assertTrue($regex->match(utf8_decode('eéêè'), $match));
        $this->assertEqual($match, utf8_decode('eé'));
    }

    public function createParser()
    {
        $parser = new MockSimpleHtmlSaxParser();
        $parser->returnsByValue('acceptStartToken', true);
        $parser->returnsByValue('acceptEndToken', true);
        $parser->returnsByValue('acceptAttributeToken', true);
        $parser->returnsByValue('acceptEntityToken', true);
        $parser->returnsByValue('acceptTextToken', true);
        $parser->returnsByValue('ignore', true);

        return $parser;
    }

    public function testTagWithAttributesInUTF8()
    {
        $parser = $this->createParser();
        $parser->expectOnce('acceptTextToken', ['label', '*']);
        $parser->expectAt(0, 'acceptStartToken', ['<a', '*']);
        $parser->expectAt(1, 'acceptStartToken', ['href', '*']);
        $parser->expectAt(2, 'acceptStartToken', ['>', '*']);
        $parser->expectCallCount('acceptStartToken', 3);
        $parser->expectAt(0, 'acceptAttributeToken', ['= "', '*']);
        $parser->expectAt(1, 'acceptAttributeToken', ['hère.html', '*']);
        $parser->expectAt(2, 'acceptAttributeToken', ['"', '*']);
        $parser->expectCallCount('acceptAttributeToken', 3);
        $parser->expectOnce('acceptEndToken', ['</a>', '*']);
        $lexer = new SimpleHtmlLexer($parser);
        $this->assertTrue($lexer->parse('<a href = "hère.html">label</a>'));
    }

    public function testTagWithAttributesInLatin1()
    {
        $parser = $this->createParser();
        $parser->expectOnce('acceptTextToken', ['label', '*']);
        $parser->expectAt(0, 'acceptStartToken', ['<a', '*']);
        $parser->expectAt(1, 'acceptStartToken', ['href', '*']);
        $parser->expectAt(2, 'acceptStartToken', ['>', '*']);
        $parser->expectCallCount('acceptStartToken', 3);
        $parser->expectAt(0, 'acceptAttributeToken', ['= "', '*']);
        $parser->expectAt(1, 'acceptAttributeToken', [utf8_decode('hère.html'), '*']);
        $parser->expectAt(2, 'acceptAttributeToken', ['"', '*']);
        $parser->expectCallCount('acceptAttributeToken', 3);
        $parser->expectOnce('acceptEndToken', ['</a>', '*']);
        $lexer = new SimpleHtmlLexer($parser);
        $this->assertTrue($lexer->parse(utf8_decode('<a href = "hère.html">label</a>')));
    }
}

class TestOfUrlithDifferentCharset extends UnitTestCase
{
    public function testUsernameAndPasswordInUTF8()
    {
        $url = new SimpleUrl('http://pÈrick:penËt@www.lastcraft.com');
        $this->assertEqual($url->getUsername(), 'pÈrick');
        $this->assertEqual($url->getPassword(), 'penËt');
    }
}
