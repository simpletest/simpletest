<?php declare(strict_types=1);

require_once __DIR__ . '/../runner.php';

require_once __DIR__ . '/../reporter.php';

/**
 * Main sprintf template for the start of the page.
 * Sequence of parameters is:
 * - title - string
 * - script path - string
 * - script path - string
 * - css path - string
 * - additional css - string
 * - title - string
 * - image path - string.
 */
\define(
    'SIMPLETEST_WEBUNIT_HEAD_TPL',
    <<<'EOS'
<html>
<head>
<title>%s</title>
<script type="text/javascript" src="%sx.js"></script>
<script type="text/javascript" src="%swebunit.js"></script>
<link rel="stylesheet" type="text/css" href="%swebunit.css" title="Default"></link>
<style type="text/css">
%s
</style>
</head>
<body>
<div id="wait">
    <h1>&nbsp;Running %s&nbsp;</h1>
    Please wait...<br />
    <img src="%swait.gif" border="0"><br />&nbsp;
</div>
<script type="text/javascript">
wait_start();
</script>
<div id="webunit">
    <div id="run"></div><br />
    <div id="tabs">
        <div id="visible_tab">visible tab content</div>
        &nbsp;&nbsp;<span id="failtab" class="activetab">&nbsp;&nbsp;<a href="javascript:activate_tab('fail');">Fail</a>&nbsp;&nbsp;</span>
        <span id="treetab" class="inactivetab">&nbsp;&nbsp;<a href="javascript:activate_tab('tree');">Tree</a>&nbsp;&nbsp;</span>
    </div>
    <div id="msg">Click on a failed test case method in the tree tab to view output here.</div>
</div>
<div id="fail"></div>
<div id="tree"></div>
<!-- open a new script to capture js vars as the tests run -->
<script type="text/javascript">
layout();

EOS
);

/**
 * Not used yet.
 * May be needed for localized styles we need at runtime, not in the stylesheet.
 */
\define('SIMPLETEST_WEBUNIT_CSS', '/* this space reseved for future use */');

/**
 * Sample minimal test displayer. Generates only failure messages and a pass count.
 */
class WebUnitReporter extends SimpleReporter
{
    /**
     * @var string Base directory for PUnit script, images and style sheets.
     *
     * Needs to be a relative path from where the test scripts are run
     * (and obviously, visible in the document root).
     */
    public $path;

    /**
     * Does nothing yet.
     * The first output will be sent on the first test start.
     * For use by a web browser.
     */
    public function __construct($path = '../ui/')
    {
        parent::__construct();
        $this->path = $path;
    }

    /**
     * Paints the top of the web page setting the title to the name of the starting test.
     *
     * @param string $test_name name class of test
     */
    public function paintHeader($test_name): void
    {
        $this->sendNoCacheHeaders();
        print \sprintf(
            SIMPLETEST_WEBUNIT_HEAD_TPL,
            $test_name,
            $this->path . 'webunit/js/',
            $this->path . 'webunit/js/',
            $this->path . 'webunit/css/',
            $this->_getCss(),
            $test_name,
            $this->path . 'webunit/img/',
        );
        \flush();
    }

    /**
     * Send the headers necessary to ensure the page is reloaded on every request.
     * Otherwise you could be scratching your head over out of date test data.
     */
    public function sendNoCacheHeaders(): void
    {
        \header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        \header('Last-Modified: ' . \gmdate('D, d M Y H:i:s') . ' GMT');
        \header('Cache-Control: no-store, no-cache, must-revalidate');
        \header('Cache-Control: post-check=0, pre-check=0', false);
        \header('Pragma: no-cache');
    }

    /**
     * Paints the CSS. Add additional styles here.
     *
     * @return string CSS code as text
     */
    public function _getCss()
    {
        return SIMPLETEST_WEBUNIT_CSS;
    }

    /**
     * Paints the end of the test with a summary of the passes and failures.
     *
     * @param string $test_name name class of test
     */
    public function paintFooter($test_name): void
    {
        print 'make_tree();</script>' . $this->outputScript("xHide('wait');");
        $colour  = ($this->getFailCount() + $this->getExceptionCount() > 0 ? 'red' : 'green');
        $content = "<h1>{$test_name}</h1>\n";
        $content .= '<div style="';
        $content .= "padding: 8px; margin-top: 1em; background-color: {$colour}; color: white;";
        $content .= '">';
        $content .= $this->getTestCaseProgress() . '/' . $this->getTestCaseCount();
        $content .= " test cases complete:\n";
        $content .= '<strong>' . $this->getPassCount() . '</strong> passes, ';
        $content .= '<strong>' . $this->getFailCount() . '</strong> fails and ';
        $content .= '<strong>' . $this->getExceptionCount() . '</strong> exceptions.';
        $content .= "</div>\n";

        print $this->outputScript('foo = "' . $this->toJsString($content) . '";' . "\nset_div_content('run', foo);");
        print "\n</body>\n</html>\n";
    }

    /**
     * Paints formatted text such as dumped variables.
     *
     * @param string $message text to show
     */
    public function paintFormattedMessage($message): void
    {
        print 'add_log("' . $this->toJsString("<pre>{$message}</pre>", true) . "\");\n";
    }

    /**
     * Paints the start of a group test.
     * Will also paint the page header and footer if this is the first test.
     * Will stash the size if the first start.
     *
     * @param string $test_name name of test that is starting
     * @param int    $size      number of test cases starting
     */
    public function paintGroupStart($test_name, $size): void
    {
        parent::paintGroupStart($test_name, $size);
        print "add_group('{$test_name}');\n";
    }

    /**
     * Paints the start of a test case.
     * Will also paint the page header and footer if this is the first test.
     * Will stash the size if the first start.
     *
     * @param string $test_name name of test that is starting
     */
    public function paintCaseStart($test_name): void
    {
        parent::paintCaseStart($test_name);
        print "add_case('{$test_name}');\n";
    }

    /**
     * Paints the start of a test method.
     *
     * @param string $test_name name of test that is starting
     */
    public function paintMethodStart($test_name): void
    {
        parent::paintMethodStart($test_name);
        print "add_method('{$test_name}');\n";
    }

    /**
     * Paints the end of a test method.
     *
     * @param string $test_name name of test that is ending
     */
    public function paintMethodEnd($test_name): void
    {
        parent::paintMethodEnd($test_name);
    }

    /**
     * Paints the test failure with a breadcrumbs trail
     * of the nesting test suites below the top level test.
     *
     * @param string $message failure message displayed in the context of the other tests
     */
    public function paintFail($message): void
    {
        parent::paintFail($message);
        $msg        = '<span class="fail">Fail</span>: ';
        $breadcrumb = $this->getTestList();
        \array_shift($breadcrumb);
        $msg .= \implode('-&gt;', $breadcrumb);
        $msg .= '-&gt;' . \htmlentities($message) . '<br />';
        print "add_fail('{$msg}');\n";
    }

    /**
     * Paints a PHP error or exception.
     *
     * @param string $message message is ignored
     *
     * @abstract
     */
    public function paintException($message): void
    {
        parent::paintException($message);
        $msg        = '<span class="fail">Exception</span>: ';
        $breadcrumb = $this->getTestList();
        \array_shift($breadcrumb);
        $msg .= \implode('-&gt;', $breadcrumb);
        $msg .= '-&gt;<strong>' . \htmlentities($message) . '</strong><br />';
        print "add_fail('{$msg}');\n";
    }

    /**
     * Returns the script passed in wrapped in script tags.
     *
     * @param string $script the script to output
     *
     * @return string the script wrapped with script tags
     */
    public function outputScript($script)
    {
        return "<script type=\"text/javascript\">\n" . $script . "\n</script>\n";
    }

    /**
     * Transform a string into a format acceptable to JavaScript.
     *
     * @param string $str the string to transform
     *
     * @return string
     */
    public function toJsString($str, $preserveCr = false)
    {
        $cr = ($preserveCr) ? '\\n' : '';

        return \str_replace(['"', "\n"], ['\"', "{$cr}\"\n\t+\""], $str);
    }
}
