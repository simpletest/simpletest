<?php

require_once __DIR__.'/scorer.php';

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
        $this->namespace = ($namespace ? $namespace.':' : '');
        $this->indent = $indent;
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
        return str_repeat(
            $this->indent,
            count($this->getTestList()) + $offset
        );
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
        return str_replace(
            ['&', '<', '>', '"', '\''],
            ['&amp;', '&lt;', '&gt;', '&quot;', '&apos;'],
            $text
        );
    }

    /**
     * Paints the start of a group test.
     *
     * @param string $test_name name of test that is starting
     * @param int    $size      number of test cases starting
     *
     * @return void
     */
    public function paintGroupStart($test_name, $size)
    {
        parent::paintGroupStart($test_name, $size);
        echo $this->getIndent();
        echo '<'.$this->namespace."group size=\"$size\">\n";
        echo $this->getIndent(1);
        echo '<'.$this->namespace.'name>'.
                $this->toParsedXml($test_name).
                '</'.$this->namespace."name>\n";
    }

    /**
     * Paints the end of a group test.
     *
     * @param string $test_name name of test that is ending
     *
     * @return void
     */
    public function paintGroupEnd($test_name)
    {
        echo $this->getIndent();
        echo '</'.$this->namespace."group>\n";
        parent::paintGroupEnd($test_name);
    }

    /**
     * Paints the start of a test case.
     *
     * @param string $test_name name of test that is starting
     *
     * @return void
     */
    public function paintCaseStart($test_name)
    {
        parent::paintCaseStart($test_name);
        echo $this->getIndent();
        echo '<'.$this->namespace."case>\n";
        echo $this->getIndent(1);
        echo '<'.$this->namespace.'name>'.
                $this->toParsedXml($test_name).
                '</'.$this->namespace."name>\n";
    }

    /**
     * Paints the end of a test case.
     *
     * @param string $test_name name of test that is ending
     *
     * @return void
     */
    public function paintCaseEnd($test_name)
    {
        echo $this->getIndent();
        echo '</'.$this->namespace."case>\n";
        parent::paintCaseEnd($test_name);
    }

    /**
     * Paints the start of a test method.
     *
     * @param string $test_name name of test that is starting
     *
     * @return void
     */
    public function paintMethodStart($test_name)
    {
        parent::paintMethodStart($test_name);
        echo $this->getIndent();
        echo '<'.$this->namespace."test>\n";
        echo $this->getIndent(1);
        echo '<'.$this->namespace.'name>'.
                $this->toParsedXml($test_name).
                '</'.$this->namespace."name>\n";
    }

    /**
     * Paints the end of a test method.
     *
     * @param string $test_name name of test that is ending
     *
     * @return void
     */
    public function paintMethodEnd($test_name)
    {
        echo $this->getIndent();
        echo '</'.$this->namespace."test>\n";
        parent::paintMethodEnd($test_name);
    }

    /**
     * Paints pass as XML.
     *
     * @param string $message message to encode
     *
     * @return void
     */
    public function paintPass($message)
    {
        parent::paintPass($message);
        echo $this->getIndent(1);
        echo '<'.$this->namespace.'pass>';
        echo $this->toParsedXml($message);
        echo '</'.$this->namespace."pass>\n";
    }

    /**
     * Paints failure as XML.
     *
     * @param string $message message to encode
     *
     * @return void
     */
    public function paintFail($message)
    {
        parent::paintFail($message);
        echo $this->getIndent(1);
        echo '<'.$this->namespace.'fail>';
        echo $this->toParsedXml($message);
        echo '</'.$this->namespace."fail>\n";
    }

    /**
     * Paints error as XML.
     *
     * @param string $message message to encode
     *
     * @return void
     */
    public function paintError($message)
    {
        parent::paintError($message);
        echo $this->getIndent(1);
        echo '<'.$this->namespace.'exception>';
        echo $this->toParsedXml($message);
        echo '</'.$this->namespace."exception>\n";
    }

    /**
     * Paints exception as XML.
     *
     * @param Exception $exception exception to encode
     *
     * @return void
     */
    public function paintException($exception)
    {
        parent::paintException($exception);
        echo $this->getIndent(1);
        echo '<'.$this->namespace.'exception>';
        $message = 'Unexpected exception of type ['.get_class($exception).
                '] with message ['.$exception->getMessage().
                '] in ['.$exception->getFile().
                ' line '.$exception->getLine().']';
        echo $this->toParsedXml($message);
        echo '</'.$this->namespace."exception>\n";
    }

    /**
     * Paints the skipping message and tag.
     *
     * @param string $message text to display in skip tag
     *
     * @return void
     */
    public function paintSkip($message)
    {
        parent::paintSkip($message);
        echo $this->getIndent(1);
        echo '<'.$this->namespace.'skip>';
        echo $this->toParsedXml($message);
        echo '</'.$this->namespace."skip>\n";
    }

    /**
     * Paints a simple supplementary message.
     *
     * @param string $message text to display
     *
     * @return void
     */
    public function paintMessage($message)
    {
        parent::paintMessage($message);
        echo $this->getIndent(1);
        echo '<'.$this->namespace.'message>';
        echo $this->toParsedXml($message);
        echo '</'.$this->namespace."message>\n";
    }

    /**
     * Paints a formatted ASCII message such as a privateiable dump.
     *
     * @param string $message text to display
     *
     * @return void
     */
    public function paintFormattedMessage($message)
    {
        parent::paintFormattedMessage($message);
        echo $this->getIndent(1);
        echo '<'.$this->namespace.'formatted>';
        echo "<![CDATA[$message]]>";
        echo '</'.$this->namespace."formatted>\n";
    }

    /**
     * Serialises the event object.
     *
     * @param string $type    event type as text
     * @param mixed  $payload message or object
     *
     * @return void
     */
    public function paintSignal($type, $payload)
    {
        parent::paintSignal($type, $payload);
        echo $this->getIndent(1);
        echo '<'.$this->namespace."signal type=\"$type\">";
        echo '<![CDATA['.serialize($payload).']]>';
        echo '</'.$this->namespace."signal>\n";
    }

    /**
     * Paints the test document header.
     *
     * @param string $test_name first test top level to start
     *
     * @abstract
     *
     * @return void
     */
    public function paintHeader($test_name)
    {
        if (!SimpleReporter::inCli()) {
            header('Content-type: text/xml');
        }
        echo '<?xml version="1.0"';
        if ($this->namespace) {
            echo ' xmlns:'.$this->namespace.
                    '="www.lastcraft.com/SimpleTest/Beta3/Report"';
        }
        echo "?>\n";
        echo '<'.$this->namespace."run>\n";
    }

    /**
     * Paints the test document footer.
     *
     * @param string $test_name the top level test
     *
     * @abstract
     *
     * @return void
     */
    public function paintFooter($test_name)
    {
        echo '</'.$this->namespace."run>\n";
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
    /** @var string|false */
    private $name;

    /**
     * Sets the basic test information except the name.
     *
     * @param mixed $attributes name value pairs
     */
    public function __construct($attributes)
    {
        $this->name = false;
        $this->attributes = $attributes;
    }

    /**
     * Sets the test case/method name.
     *
     * @param string $name name of test
     *
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Accessor for name.
     *
     * @return string|false name of test
     */
    public function getName()
    {
        return $this->name;
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
     *
     * @return void
     */
    public function paintStart(&$listener)
    {
        $listener->paintMethodStart($this->getName());
    }

    /**
     * Signals the appropriate end event on the listener.
     *
     * @param SimpleReporter $listener target for events
     *
     * @return void
     */
    public function paintEnd(&$listener)
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
    public function paintStart(&$listener)
    {
        $listener->paintCaseStart($this->getName());
    }

    /**
     * Signals the appropriate end event on the listener.
     *
     * @param SimpleReporter $listener target for events
     */
    public function paintEnd(&$listener)
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
    public function paintStart(&$listener)
    {
        $listener->paintGroupStart($this->getName(), $this->getSize());
    }

    /**
     * Signals the appropriate end event on the listener.
     *
     * @param SimpleReporter $listener target for events
     */
    public function paintEnd(&$listener)
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
    private $attributes;
    /** @var string */
    private $content;
    /** @var mixed */
    private $expat;
    /** @var bool */
    private $in_content_tag;
    /** @var SimpleReporter */
    private $listener;
    /** @var array */
    private $tag_stack;

    /**
     * Loads a listener with the SimpleReporter interface.
     *
     * @param SimpleReporter $listener listener of tag events
     */
    public function __construct(&$listener)
    {
        $this->attributes = [];
        $this->content = '';
        $this->expat = $this->createParser();
        $this->in_content_tag = false;
        $this->listener = $listener;
        $this->tag_stack = [];
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
        if (!xml_parse($this->expat, $chunk)) {
            $code = xml_get_error_code($this->expat);
            $message = sprintf(
                "XML parse error %d '%s' at line %d, column %d (byte %d).",
                $code,
                xml_error_string($code),
                xml_get_current_line_number($this->expat),
                xml_get_current_column_number($this->expat),
                xml_get_current_byte_index($this->expat)
            );
            simpletest_trigger_error($message);

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
        $expat = xml_parser_create();
        xml_set_element_handler($expat, [$this, 'startElement'], [$this, 'endElement']);
        xml_set_character_data_handler($expat, [$this, 'addContent']);
        xml_set_default_handler($expat, [$this, 'defaultContent']);

        return $expat;
    }

    /**
     * Opens a new test nesting level.
     *
     * @return NestedXmlTag the group, case or method tag to start
     */
    protected function pushNestingTag($nested)
    {
        array_unshift($this->tag_stack, $nested);
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
        return array_shift($this->tag_stack);
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
        return in_array($tag, [
                'NAME', 'PASS', 'FAIL', 'EXCEPTION', 'SKIP', 'MESSAGE', 'FORMATTED', 'SIGNAL', ]);
    }

    /**
     * Handler for start of event element.
     *
     * @param resource $expat      parser handle
     * @param string   $tag        element name
     * @param mixed    $attributes Name value pairs. Attributes without content are marked as true.
     *
     * @return void
     */
    protected function startElement($expat, $tag, $attributes)
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
            $this->content = '';
        }
    }

    /**
     * End of element event.
     *
     * @param resource $expat parser handle
     * @param string   $tag   element name
     *
     * @return void
     */
    protected function endElement($expat, $tag)
    {
        $this->in_content_tag = false;
        if (in_array($tag, ['GROUP', 'CASE', 'TEST'])) {
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
                unserialize($this->content)
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
     *
     * @return void
     */
    protected function defaultContent($expat, $default)
    {
        // TODO
    }
}
