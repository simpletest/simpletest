<?php declare(strict_types=1);

require_once \dirname(__DIR__) . '/src/scorer.php';

require_once __DIR__ . '/treemap_reporter/treemap_recorder.php';

/**
 * Constructs and renders a treemap visualization of a test run.
 */
class TreemapReporter extends SimpleReporterDecorator
{
    public $_reporter;
    private $aspect = 1;

    public function __construct()
    {
        parent::__construct(new TreemapRecorder);
    }

    /**
     * Return CSS.
     */
    public function getCss()
    {
        $css = '.pass{background-color:green;}.fail{background-color:red;}';
        $css .= 'body {background-color:white;margin:0;padding:1em;}';
        $css .= 'div{float:right;margin:0;color:black;}';
        $css .= 'div{border-left:1px solid white;border-bottom:1px solid white;}';
        $css .= 'h1 {font:normal 1.8em Arial;color:black;margin:0 0 0.3em 0.1em;}';
        $css .= '.clear { clear:both; }';

        return $css;
    }

    /**
     * Paints the HTML header and sets up results.
     */
    public function paintResultsHeader(): void
    {
        $title = $this->_reporter->getTitle();
        $css   = $this->getCss();

        $html = '<html><head>';
        $html .= "<title>{$title}</title>";
        $html .= '<style type="text/css">' . $css . '</style>';
        $html .= '</head><body>';
        $html .= "<h1>{$title}</h1>";

        print $html;
    }

    /**
     * Places a clearing break below the end of the test nodes.
     */
    public function paintResultsFooter(): void
    {
        $html = '<br clear="all">';
        $html .= '</body></html>';

        print $html;
    }

    /**
     * Paints start tag for div representing a test node.
     */
    public function paintRectangleStart($node, $horiz, $vert): void
    {
        $name        = $node->getName();
        $description = $node->getDescription();
        $status      = $node->getStatus();

        print \sprintf(
            '<div title="%s: %s" class="%s" style="width:%d%%; height:%d%%;">',
            $name,
            $description,
            $status,
            $horiz,
            $vert,
        );
    }

    /**
     * Paints end tag for test node div.
     */
    public function paintRectangleEnd(): void
    {
        print '</div>';
    }

    /**
     * Paints wrapping treemap divs.
     *
     * @todo how to configure aspect and other parameters?
     */
    public function paintFooter($group): void
    {
        $this->paintResultsHeader();
        $this->paintRectangleStart($this->_reporter->getGraph(), 100, 100);
        $this->divideMapNodes($this->_reporter->getGraph(), $this->aspect);
        $this->paintRectangleEnd();
        $this->paintResultsFooter();
    }

    /**
     * divides the test results based on a slice and dice algorithm.
     *
     * @param TreemapNode $map    sorted
     * @param bool        $aspect flips the aspect between horizontal and vertical
     */
    public function divideMapNodes($map, $aspect): void
    {
        $aspect    = !$aspect;
        $divisions = $map->getSize();
        $total     = $map->getTotalSize();

        foreach ($map->getChildren() as $node) {
            $dist = $node->isLeaf() ? 1 / $total * 100 : $node->getTotalSize() / $total * 100;

            if ($aspect) {
                $horiz = $dist;
                $vert  = 100;
            } else {
                $horiz = 100;
                $vert  = $dist;
            }
            $this->paintRectangleStart($node, $horiz, $vert);
            $this->divideMapNodes($node, $aspect);
            $this->paintRectangleEnd();
        }
    }

    public function paintGroupEnd($group): void
    {
        $this->_reporter->paintGroupEnd($group);

        if ($this->_reporter->isComplete()) {
            $this->paintFooter($group);
        }
    }
}
