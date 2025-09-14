<?php declare(strict_types=1);

require_once __DIR__ . '/scorer.php';

/**
 * Creates the XML needed for remote communication by SimpleTest.
 */
class XmlReporter extends SimpleReporter
{
    private $indent;
    private $namespace;

    /**
     * Sets up indentation and namespace.
     *
     * @param string $namespace namespace to add to each tag
     * @param string $indent    indenting to add on each nesting
     */
    public function __construct($namespace = false, $indent = '  ')
    {
        parent::__construct();
        $this->namespace = ($namespace ? $namespace . ':' : '');
        $this->indent    = $indent;
    }

    /**
     * Converts character string to parsed XML entities string.
     *
     * @param string $text Unparsed character data
     *
     * @return string parsed character data
     */
    public function toParsedXml($text)
    {
        return \str_replace(
            ['&', '<', '>', '"', '\''],
            ['&amp;', '&lt;', '&gt;', '&quot;', '&apos;'],
            $text,
        );
    }

    /**
     * Paints the start of a group test.
     *
     * @param string $test_name name of test that is starting
     * @param int    $size      number of test cases starting
     */
    public function paintGroupStart($test_name, $size): void
    {
        parent::paintGroupStart($test_name, $size);
        print $this->getIndent();
        print '<' . $this->namespace . "group size=\"{$size}\">\n";
        print $this->getIndent(1);
        print '<' . $this->namespace . 'name>' .
                $this->toParsedXml($test_name) .
                '</' . $this->namespace . "name>\n";
    }

    /**
     * Paints the end of a group test.
     *
     * @param string $test_name name of test that is ending
     */
    public function paintGroupEnd($test_name): void
    {
        print $this->getIndent();
        print '</' . $this->namespace . "group>\n";
        parent::paintGroupEnd($test_name);
    }

    /**
     * Paints the start of a test case.
     *
     * @param string $test_name name of test that is starting
     */
    public function paintCaseStart($test_name): void
    {
        parent::paintCaseStart($test_name);
        print $this->getIndent();
        print '<' . $this->namespace . "case>\n";
        print $this->getIndent(1);
        print '<' . $this->namespace . 'name>' .
                $this->toParsedXml($test_name) .
                '</' . $this->namespace . "name>\n";
    }

    /**
     * Paints the end of a test case.
     *
     * @param string $test_name name of test that is ending
     */
    public function paintCaseEnd($test_name): void
    {
        print $this->getIndent();
        print '</' . $this->namespace . "case>\n";
        parent::paintCaseEnd($test_name);
    }

    /**
     * Paints the start of a test method.
     *
     * @param string $test_name name of test that is starting
     */
    public function paintMethodStart($test_name): void
    {
        parent::paintMethodStart($test_name);
        print $this->getIndent();
        print '<' . $this->namespace . "test>\n";
        print $this->getIndent(1);
        print '<' . $this->namespace . 'name>' .
                $this->toParsedXml($test_name) .
                '</' . $this->namespace . "name>\n";
    }

    /**
     * Paints the end of a test method.
     *
     * @param string $test_name name of test that is ending
     */
    public function paintMethodEnd($test_name): void
    {
        print $this->getIndent();
        print '</' . $this->namespace . "test>\n";
        parent::paintMethodEnd($test_name);
    }

    /**
     * Paints pass as XML.
     *
     * @param string $message message to encode
     */
    public function paintPass($message): void
    {
        parent::paintPass($message);
        print $this->getIndent(1);
        print '<' . $this->namespace . 'pass>';
        print $this->toParsedXml($message);
        print '</' . $this->namespace . "pass>\n";
    }

    /**
     * Paints failure as XML.
     *
     * @param string $message message to encode
     */
    public function paintFail($message): void
    {
        parent::paintFail($message);
        print $this->getIndent(1);
        print '<' . $this->namespace . 'fail>';
        print $this->toParsedXml($message);
        print '</' . $this->namespace . "fail>\n";
    }

    /**
     * Paints error as XML.
     *
     * @param string $message message to encode
     */
    public function paintError($message): void
    {
        parent::paintError($message);
        print $this->getIndent(1);
        print '<' . $this->namespace . 'exception>';
        print $this->toParsedXml($message);
        print '</' . $this->namespace . "exception>\n";
    }

    /**
     * Paints exception as XML.
     *
     * @param Exception $exception exception to encode
     */
    public function paintException($exception): void
    {
        parent::paintException($exception);
        print $this->getIndent(1);
        print '<' . $this->namespace . 'exception>';
        $message = 'Unexpected exception of type [' . $exception::class .
                '] with message [' . $exception->getMessage() .
                '] in [' . $exception->getFile() .
                ' line ' . $exception->getLine() . ']';
        print $this->toParsedXml($message);
        print '</' . $this->namespace . "exception>\n";
    }

    /**
     * Paints the skipping message and tag.
     *
     * @param string $message text to display in skip tag
     */
    public function paintSkip($message): void
    {
        parent::paintSkip($message);
        print $this->getIndent(1);
        print '<' . $this->namespace . 'skip>';
        print $this->toParsedXml($message);
        print '</' . $this->namespace . "skip>\n";
    }

    /**
     * Paints a simple supplementary message.
     *
     * @param string $message text to display
     */
    public function paintMessage($message): void
    {
        parent::paintMessage($message);
        print $this->getIndent(1);
        print '<' . $this->namespace . 'message>';
        print $this->toParsedXml($message);
        print '</' . $this->namespace . "message>\n";
    }

    /**
     * Paints a formatted ASCII message such as a privateiable dump.
     *
     * @param string $message text to display
     */
    public function paintFormattedMessage($message): void
    {
        parent::paintFormattedMessage($message);
        print $this->getIndent(1);
        print '<' . $this->namespace . 'formatted>';
        print "<![CDATA[{$message}]]>";
        print '</' . $this->namespace . "formatted>\n";
    }

    /**
     * Serialises the event object.
     *
     * @param string $type    event type as text
     * @param mixed  $payload message or object
     */
    public function paintSignal($type, $payload): void
    {
        parent::paintSignal($type, $payload);
        print $this->getIndent(1);
        print '<' . $this->namespace . "signal type=\"{$type}\">";
        print '<![CDATA[' . \serialize($payload) . ']]>';
        print '</' . $this->namespace . "signal>\n";
    }

    /**
     * Paints the test document header.
     *
     * @param string $test_name first test top level to start
     */
    public function paintHeader($test_name): void
    {
        if (!SimpleReporter::inCli()) {
            \header('Content-type: text/xml');
        }
        print '<?xml version="1.0"';

        if ($this->namespace) {
            print ' xmlns:' . $this->namespace .
                    '="www.lastcraft.com/SimpleTest/Beta3/Report"';
        }
        print "?>\n";
        print '<' . $this->namespace . "run>\n";
    }

    /**
     * Paints the test document footer.
     *
     * @param string $test_name the top level test
     */
    public function paintFooter($test_name): void
    {
        print '</' . $this->namespace . "run>\n";
    }

    /**
     * Calculates the pretty printing indent level from the current level of nesting.
     *
     * @param int $offset extra indenting level
     *
     * @return string leading space
     */
    protected function getIndent($offset = 0)
    {
        return \str_repeat(
            $this->indent,
            \count($this->getTestList()) + $offset,
        );
    }
}

/**
 * Accumulator for incoming tag.
 * Holds the incoming test structure information for later dispatch to the reporter.
 */
class NestedXmlTag
{
    /** @var array */
    private $attributes;

    /** @var false|string */
    private $name = false;

    /**
     * Sets the basic test information except the name.
     *
     * @param mixed $attributes name value pairs
     */
    public function __construct($attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * Sets the test case/method name.
     *
     * @param string $name name of test
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * Accessor for name.
     *
     * @return false|string name of test
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Default no-op paintStart for non-nesting tags. Subclasses override as needed.
     *
     * @param SimpleReporter $listener target for events
     */
    public function paintStart(&$listener): void
    {
        // no-op in base class
    }

    /**
     * Default no-op paintEnd for non-nesting tags. Subclasses override as needed.
     *
     * @param SimpleReporter $listener target for events
     */
    public function paintEnd(&$listener): void
    {
        // no-op in base class
    }

    /**
     * Accessor for attributes.
     *
     * @return mixed all attributes
     */
    protected function getAttributes()
    {
        return $this->attributes;
    }
}

/**
 * Accumulator for incoming method tag.
 * Holds the incoming test structure information for later dispatch to the reporter.
 */
class NestedMethodTag extends NestedXmlTag
{
    /**
     * Sets the basic test information except the name.
     *
     * @param array $attributes name value pairs
     */
    public function __construct($attributes)
    {
        parent::__construct($attributes);
    }

    /**
     * Signals the appropriate start event on the listener.
     *
     * @param SimpleReporter $listener target for events
     */
    public function paintStart(&$listener): void
    {
        $listener->paintMethodStart($this->getName());
    }

    /**
     * Signals the appropriate end event on the listener.
     *
     * @param SimpleReporter $listener target for events
     */
    public function paintEnd(&$listener): void
    {
        $listener->paintMethodEnd($this->getName());
    }
}

/**
 * Accumulator for incoming case tag.
 * Holds the incoming test structure information for later dispatch to the reporter.
 */
class NestedCaseTag extends NestedXmlTag
{
    /**
     * Sets the basic test information except the name.
     *
     * @param array $attributes name value pairs
     */
    public function __construct($attributes)
    {
        parent::__construct($attributes);
    }

    /**
     * Signals the appropriate start event on the listener.
     *
     * @param SimpleReporter $listener target for events
     */
    public function paintStart(&$listener): void
    {
        $listener->paintCaseStart($this->getName());
    }

    /**
     * Signals the appropriate end event on the listener.
     *
     * @param SimpleReporter $listener target for events
     */
    public function paintEnd(&$listener): void
    {
        $listener->paintCaseEnd($this->getName());
    }
}

/**
 * Accumulator for incoming group tag.
 * Holds the incoming test structure information for later dispatch to the reporter.
 */
class NestedGroupTag extends NestedXmlTag
{
    /**
     * Sets the basic test information except the name.
     *
     * @param array $attributes name value pairs
     */
    public function __construct($attributes)
    {
        parent::__construct($attributes);
    }

    /**
     * Signals the appropriate start event on the listener.
     *
     * @param SimpleReporter $listener target for events
     */
    public function paintStart(&$listener): void
    {
        $listener->paintGroupStart($this->getName(), $this->getSize());
    }

    /**
     * Signals the appropriate end event on the listener.
     *
     * @param SimpleReporter $listener target for events
     */
    public function paintEnd(&$listener): void
    {
        $listener->paintGroupEnd($this->getName());
    }

    /**
     * The size in the attributes.
     *
     * @return int value of size attribute or zero
     */
    public function getSize()
    {
        $attributes = $this->getAttributes();

        if (isset($attributes['SIZE'])) {
            return (int) $attributes['SIZE'];
        }

        return 0;
    }
}

/**
 * Parser for importing the output of the XmlReporter.
 * Dispatches that output to another reporter.
 */
class SimpleTestXmlParser
{
    /** @var array */
    private $attributes = [];

    /** @var string */
    private $content = '';

    /** @var mixed */
    private $expat;

    /** @var bool */
    private $in_content_tag = false;

    /** @var SimpleReporter */
    private $listener;

    /** @var array */
    private $tag_stack = [];

    /**
     * Loads a listener with the SimpleReporter interface.
     *
     * @param SimpleReporter $listener listener of tag events
     */
    public function __construct(&$listener)
    {
        $this->expat    = $this->createParser();
        $this->listener = $listener;
    }

    /**
     * Parses a block of XML sending the results to the listener.
     *
     * @param string $chunk block of text to read
     *
     * @return bool true if valid XML
     */
    public function parse($chunk)
    {
        if (\xml_parse($this->expat, $chunk) === 0) {
            $code    = \xml_get_error_code($this->expat);
            $message = \sprintf(
                "XML parse error %d '%s' at line %d, column %d (byte %d).",
                $code,
                \xml_error_string($code),
                \xml_get_current_line_number($this->expat),
                \xml_get_current_column_number($this->expat),
                \xml_get_current_byte_index($this->expat),
            );
            \trigger_error($message);

            return false;
        }

        return true;
    }

    /**
     * Sets up expat as the XML parser.
     *
     * @return mixed expat handle
     */
    protected function createParser()
    {
        $expat = \xml_parser_create();
        \xml_set_element_handler($expat, [$this, 'startElement'], [$this, 'endElement']);
        \xml_set_character_data_handler($expat, [$this, 'addContent']);
        \xml_set_default_handler($expat, [$this, 'defaultContent']);

        return $expat;
    }

    /**
     * Opens a new test nesting level.
     *
     * @return NestedXmlTag the group, case or method tag to start
     */
    protected function pushNestingTag($nested): void
    {
        \array_unshift($this->tag_stack, $nested);
    }

    /**
     * Accessor for current test structure tag.
     *
     * @return NestedXmlTag the group, case or method tag being parsed
     */
    protected function getCurrentNestingTag()
    {
        return $this->tag_stack[0];
    }

    /**
     * Ends a nesting tag.
     *
     * @return NestedXmlTag the group, case or method tag just finished
     */
    protected function popNestingTag()
    {
        return \array_shift($this->tag_stack);
    }

    /**
     * Test if tag is a leaf node with only text content.
     *
     * @param string $tag XML tag name
     *
     * @return bool True, if leaf. False, if nesting.
     */
    protected function isLeaf($tag)
    {
        return \in_array($tag, [
            'NAME', 'PASS', 'FAIL', 'EXCEPTION', 'SKIP', 'MESSAGE', 'FORMATTED', 'SIGNAL', ], true);
    }

    /**
     * Handler for start of event element.
     *
     * @param resource $expat      parser handle
     * @param string   $tag        element name
     * @param mixed    $attributes Name value pairs. Attributes without content are marked as true.
     */
    protected function startElement($expat, $tag, $attributes): void
    {
        $this->attributes = $attributes;

        if ('GROUP' === $tag) {
            $this->pushNestingTag(new NestedGroupTag($attributes));
        } elseif ('CASE' === $tag) {
            $this->pushNestingTag(new NestedCaseTag($attributes));
        } elseif ('TEST' === $tag) {
            $this->pushNestingTag(new NestedMethodTag($attributes));
        } elseif ($this->isLeaf($tag)) {
            $this->in_content_tag = true;
            $this->content        = '';
        }
    }

    /**
     * End of element event.
     *
     * @param resource $expat parser handle
     * @param string   $tag   element name
     */
    protected function endElement($expat, $tag): void
    {
        $this->in_content_tag = false;

        if (\in_array($tag, ['GROUP', 'CASE', 'TEST'], true)) {
            $nesting_tag = $this->popNestingTag();
            $nesting_tag->paintEnd($this->listener);
        } elseif ('NAME' === $tag) {
            $nesting_tag = $this->getCurrentNestingTag();
            $nesting_tag->setName($this->content);
            $nesting_tag->paintStart($this->listener);
        } elseif ('PASS' === $tag) {
            $this->listener->paintPass($this->content);
        } elseif ('FAIL' === $tag) {
            $this->listener->paintFail($this->content);
        } elseif ('EXCEPTION' === $tag) {
            $this->listener->paintError($this->content);
        } elseif ('SKIP' === $tag) {
            $this->listener->paintSkip($this->content);
        } elseif ('SIGNAL' === $tag) {
            $this->listener->paintSignal(
                $this->attributes['TYPE'],
                \unserialize($this->content),
            );
        } elseif ('MESSAGE' === $tag) {
            $this->listener->paintMessage($this->content);
        } elseif ('FORMATTED' === $tag) {
            $this->listener->paintFormattedMessage($this->content);
        }
    }

    /**
     * Content between start and end elements.
     *
     * @param resource $expat parser handle
     * @param string   $text  usually output messages
     *
     * @return true
     */
    protected function addContent($expat, $text)
    {
        if ($this->in_content_tag) {
            $this->content .= $text;
        }

        return true;
    }

    /**
     * XML and Doctype handler. Discards all such content.
     *
     * @param resource $expat   parser handle
     * @param string   $default text of default content
     */
    protected function defaultContent($expat, $default): void
    {
        // TODO
    }
}
