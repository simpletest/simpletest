<?php declare(strict_types=1);

require_once __DIR__ . '/../src/scorer.php';

require_once __DIR__ . '/treemap_reporter/treemap_recorder.php';

/**
 * Constructs and renders a treemap visualization of a test run.
 */
class TreemapReporter extends SimpleReporterDecorator
{
    public $_reporter;

    public function __construct()
    {
        parent::__construct(new TreemapRecorder);
    }

    /**
     * basic CSS for floating nested divs.
     *
     * @todo checkout some weird border bugs
     */
    public function _getCss()
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
     * paints the HTML header and sets up results.
     */
    public function paintResultsHeader(): void
    {
        $title = $this->_reporter->getTitle();
        print '<html><head>';
        print "<title>{$title}</title>";
        print '<style type="text/css">' . $this->_getCss() . '</style>';
        print '</head><body>';
        print "<h1>{$title}</h1>";
    }

    /**
     * places a clearing break below the end of the test nodes.
     */
    public function paintResultsFooter(): void
    {
        print '<br clear="all">';
        print '</body></html>';
    }

    /**
     * paints start tag for div representing a test node.
     */
    public function paintRectangleStart($node, $horiz, $vert): void
    {
        $name        = $node->getName();
        $description = $node->getDescription();
        $status      = $node->getStatus();
        print "<div title=\"{$name}: {$description}\" class=\"{$status}\" style=\"width:{$horiz}%;height:{$vert}%\">";
    }

    /**
     * paints end tag for test node div.
     */
    public function paintRectangleEnd(): void
    {
        print '</div>';
    }

    /**
     * paints wrapping treemap divs.
     *
     * @todo how to configure aspect and other parameters?
     */
    public function paintFooter($group): void
    {
        $aspect = 1;
        $this->paintResultsHeader();
        $this->paintRectangleStart($this->_reporter->getGraph(), 100, 100);
        $this->divideMapNodes($this->_reporter->getGraph(), $aspect);
        $this->paintRectangleEnd();
        $this->paintResultsFooter();
    }

    /**
     * divides the test results based on a slice and dice algorithm.
     *
     * @param TreemapNode $map    sorted
     * @param bool        $aspect flips the aspect between horizontal and vertical
     *
     * @private
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
