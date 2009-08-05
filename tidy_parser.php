<?php
/**
 *  base include file for SimpleTest
 *  @package    SimpleTest
 *  @subpackage WebTester
 *  @version    $Id: php_parser.php 1911 2009-07-29 16:38:04Z lastcraft $
 */

/**
 *    Builds the page object.
 *    @package SimpleTest
 *    @subpackage WebTester
 */
class SimpleTidyPageBuilder {
    private $page;
    private $forms = array();
    private $labels = array();

    public function __destruct() {
        $this->free();
    }

    /**
     *    Frees up any references so as to allow the PHP garbage
     *    collection from unset() to work.
     */
    private function free() {
        unset($this->page);
        $this->forms = array();
        $this->labels = array();
    }

    /**
     *    This builder is only available if the 'tidy' extension is loaded.
     *    @return boolean       True if available.
     */
    function can() {
        return extension_loaded('tidy');
    }

    /**
     *    Reads the raw content the page.
     *    @param $response SimpleHttpResponse  Fetched response.
     *    @return SimplePage                   Newly parsed page.
     *    @access public
     */
    function parse($response) {
        $this->page = new SimplePage($response);
        $tidied = tidy_parse_string($input = $this->guardEmptyTags($response->getContent()),
                                    array('output-xml' => false,
                                          'wrap' => '0',
                                          'indent' => 'no'),
                                    'latin1');
        if ($tidied->errorBuffer) {
            foreach(explode("\n", $tidied->errorBuffer) as $notice) {
                // user_error($notice, E_USER_NOTICE);
            }
        }
        $this->walkTree($tidied->html());
        $this->page->setForms($this->attachLabels($this->forms, $this->labels));
        $page = $this->page;
        $this->free();
        return $page;
    }

    /**
     *    HTML tidy strips out empty tags such as <option> which we
     *    need to preserve. This method inserts an additional tag.
     *    @param string      The raw html.
     *    @return string     The html with guard tags inserted.
     */
    private function guardEmptyTags($html) {
        return preg_replace('#<(option|textarea)([^>]*)>(\s*)</(option|textarea)>#', '<\1\2>___EMPTY___\3</\4>', $html);
    }

    private function stripGuards($html) {
        return preg_replace('#(^|>)(\s*)___EMPTY___(\s*)(</|$)#i', '\2\3', $html);
    }

    /**
     * Visits the given node and all children
     */
    private function walkTree($node) {
        if ($node->name == 'a') {
            $this->page->addLink($this->tags()->createTag($node->name, (array)$node->attribute)
                                        ->addContent($this->innerHtml($node)));
        } elseif ($node->name == 'base' and isset($node->attribute['href'])) {
            $this->page->setBase($node->attribute['href']);
        } elseif ($node->name == 'title') {
            $this->page->setTitle($this->tags()->createTag($node->name, (array)$node->attribute)
                                         ->addContent($this->innerHtml($node)));
        } elseif ($node->name == 'frameset') {
            $this->page->setFrames($this->collectFrames($node));
        } elseif ($node->name == 'form') {
            $this->forms[] = $this->walkForm($node, $this->createEmptyForm($node));
        } elseif ($node->name == 'label') {
            $this->labels[] = $this->tags()->createTag($node->name, (array)$node->attribute)
                                           ->addContent($this->innerHtml($node));
        } else {
            $this->walkChildren($node);
        }
    }

    private function createEmptyForm($node) {
        return new SimpleForm($this->tags()->createTag($node->name, (array)$node->attribute), $this->page);
    }

    private function walkForm($node, $form, $enclosing_label = '') {
        if ($node->name == 'a') {
            $this->page->addLink($this->tags()->createTag($node->name, (array)$node->attribute)
                                              ->addContent($this->innerHtml($node)));
        } elseif (in_array($node->name, array('input', 'button', 'textarea', 'select'))) {
            $this->addWidgetToForm($node, $form, $enclosing_label);
        } elseif ($node->name == 'label') {
            $this->labels[] = $this->tags()->createTag($node->name, (array)$node->attribute)
                                           ->addContent($this->innerHtml($node));
            if ($node->hasChildren()) {
                foreach ($node->child as $child) {
                    $this->walkForm($child, $form, SimplePage::normalise($this->innerHtml($node)));
                }
            }
        } elseif ($node->hasChildren()) {
            foreach ($node->child as $child) {
                $this->walkForm($child, $form);
            }
        }
        return $form;
    }

    private function hasFor($node) {
        return isset($node->attribute) and $node->attribute['for'];
    }

    private function addWidgetToForm($node, $form, $enclosing_label) {
        $widget = $this->tags()->createTag($node->name, $this->attributes($node));
        if (! $widget) {
            return;
        }
        $widget->setLabel($enclosing_label)
               ->addContent($this->innerHtml($node));
        if ($node->name == 'select') {
            $widget->addTags($this->collectSelectOptions($node));
        }
        $form->addWidget($widget);
    }

    private function collectSelectOptions($node) {
        $options = array();
        if ($node->name == 'option') {
            $options[] = $this->tags()->createTag($node->name, $this->attributes($node))
                                      ->addContent($this->innerHtml($node));
        }
        if ($node->hasChildren()) {
            foreach ($node->child as $child) {
                $options = array_merge($options, $this->collectSelectOptions($child));
            }
        }
        return $options;
    }

    private function attributes($node) {
        if (! preg_match('|<[^ ]+\s(.*?)/?>|s', $node->value, $first_tag_contents)) {
            return array();
        }
        $attributes = array();
        preg_match_all('/\S+\s*=\s*\'[^\']*\'|(\S+\s*=\s*"[^"]*")|([^ =]+\s*=\s*[^ "\']+?)|[^ "\']+/', $first_tag_contents[1], $matches);
        foreach($matches[0] as $unparsed) {
            $attributes = $this->mergeAttribute($attributes, $unparsed);
        }
        return $attributes;
    }

    private function mergeAttribute($attributes, $raw) {
        $parts = explode('=', $raw);
        list($name, $value) = count($parts) == 1 ? array($parts[0], $parts[0]) : $parts;
        $attributes[trim($name)] = html_entity_decode($this->dequote(trim($value)), ENT_QUOTES);
        return $attributes;
    }

    private function dequote($quoted) {
        if (preg_match('/^(\'([^\']*)\'|"([^"]*)")$/', $quoted, $matches)) {
            return isset($matches[3]) ? $matches[3] : $matches[2];
        }
        return $quoted;
    }

    private function collectFrames($node) {
        $frames = array();
        if ($node->name == 'frame') {
            $frames = array($this->tags()->createTag($node->name, (array)$node->attribute));
        } else if ($node->hasChildren()) {
            $frames = array();
            foreach ($node->child as $child) {
                $frames = array_merge($frames, $this->collectFrames($child));
            }
        }
        return $frames;
    }

    private function walkChildren($node) {
        if ($node->hasChildren()) {
            foreach ($node->child as $child) {
                $this->walkTree($child);
            }
        }
    }

    private function innerHtml($node) {
        $raw = '';
        if ($node->hasChildren()) {
            foreach ($node->child as $child) {
                $raw .= $child->value;
            }
        }
        return $this->stripGuards($raw);
    }

    private function tags() {
        return new SimpleTagBuilder();
    }

    private function attachLabels($forms, $labels) {
        foreach ($labels as $label) {
            foreach($forms as $form) {
                if ($label->getFor()) {
                    $form->attachLabelBySelector(
                        new SimpleById($label->getFor()),
                        $label->getText());
                }
            }
        }
        return $forms;
    }
}
?>