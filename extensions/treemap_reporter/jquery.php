<?php declare(strict_types=1);

require_once __DIR__ . '/../treemap_reporter.php';

/**
 * Outputs unordered list representing treemap of test report,
 * and attaches jQuery Treemap to render results.
 */
class JqueryTreemapReporter extends TreemapReporter
{
    public $_reporter;

    public function _getCss()
    {
        return '.treemapView { color:white; }
				.treemapCell {background-color:green;font-size:10px;font-family:Arial;}
  				.treemapHead {cursor:pointer;background-color:#B34700}
				.treemapCell.selected, .treemapCell.selected .treemapCell.selected {background-color:#FFCC80}
  				.treemapCell.selected .treemapCell {background-color:#FF9900}
  				.treemapCell.selected .treemapHead {background-color:#B36B00}
  				.transfer {border:1px solid black}';
    }

    /**
     * Render the results header.
     *
     * @todo  Check URLs of JS. Find repo/alternative for treemap.js.
     *
     * @return string HTML of results header
     */
    public function paintResultsHeader()
    {
        $title = $this->_reporter->getTitle();
        print '<html><head>';
        print "<title>{$title}</title>";
        print '<style type="text/css">' . $this->_getCss() . '</style>';
        print '<script type="text/javascript" src="http://code.jquery.com/jquery-latest.js"></script>';
        print '<script type="text/javascript" src="http://www.fbtools.com/jquery/treemap/treemap.js"></script>';
        print "<script type=\"text/javascript\">\n";
        print '	window.onload = function() { jQuery("ul").treemap(800,600,{getData:getDataFromUL}); };
					function getDataFromUL(el) {
					 var data = [];
					 jQuery("li",el).each(function(){
					   var item = jQuery(this);
					   var row = [item.find("span.desc").html(),item.find("span.data").html()];
					   data.push(row);
					 });
					 return data;
					}';
        print '</script></head>';
        print '<body><ul>';
    }

    public function paintRectangleStart($node): void
    {
        print '<li><span class="desc">' . \basename($node->getDescription()) . '</span>';
        print '<span class="data">' . $node->getTotalSize() . '</span>';
    }

    public function paintRectangleEnd(): void
    {
    }

    public function paintResultsFooter(): void
    {
        print '</ul></body>';
        print '</html>';
    }

    public function divideMapNodes($map): void
    {
        foreach ($map->getChildren() as $node) {
            if (!$node->isLeaf()) {
                $this->paintRectangleStart($node);
                $this->divideMapNodes($node);
            }
        }
    }
}
