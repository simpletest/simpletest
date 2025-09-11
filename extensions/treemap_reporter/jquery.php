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
    public function paintResultsHeader(): void
    {
        $title = $this->_reporter->getTitle();
        $css   = $this->_getCss();

        $html = '<html><head>';
        $html .= "<title>{$title}</title>";
        $html .= "<style type=\"text/css\">{$css}</style>";
        $html .= '<script src="http://code.jquery.com/jquery-latest.js"></script>';
        $html .= '<script src="https://cdnjs.cloudflare.com/ajax/libs/jqwidgets/19.2.0/jqwidgets/jqxtreemap.min.js"></script>';
        $html .= '<script type="text/javascript">
            window.onload = function() {
                jQuery("ul").treemap(800, 600, { getData: getDataFromUL });
            };
            function getDataFromUL(el) {
                var data = [];
                jQuery("li", el).each(function() {
                    var item = jQuery(this);
                    var row = [item.find("span.desc").html(), item.find("span.data").html()];
                    data.push(row);
                });
                return data;
            }
        </script>';
        $html .= '</head><body><ul>';

        print $html;
    }

    public function paintRectangleStart($node): void
    {
        $html = '<li><span class="desc">' . \basename($node->getDescription()) . '</span>';
        $html .= '<span class="data">' . $node->getTotalSize() . '</span>';

        print $html;
    }

    public function paintRectangleEnd(): void
    {
    }

    public function paintResultsFooter(): void
    {
        $html = '</ul></body>';
        $html .= '</html>';

        print $html;
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
