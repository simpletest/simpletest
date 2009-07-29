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
        unset($this->page);
        unset($this->forms);
        unset($this->labels);
    }

    /**
     *    Reads the raw content the page.
     *    @param $response SimpleHttpResponse  Fetched response.
     *    @return SimplePage                   Newly parsed page.
     *    @access public
     */
    function parse($response) {
        $this->page = new SimplePage($response);
        $tidied = tidy_parse_string($response->getContent(), array('output-html' => true), 'latin1');
        if ($tidied->errorBuffer) {
            foreach(explode("\n", $tidied->errorBuffer) as $notice) {
                //user_error($notice, E_USER_NOTICE);
            }
        }
        $this->walkTree($tidied->html());
        $this->page->setForms($this->attachLabels($this->forms, $this->labels));
        return $this->page;
    }

    /**
     * Visits the given node and all children
     */
    private function walkTree($node) {
        if ($node->name == 'a') {
            $this->page->addLink($this->tags()->createTag($node->name, (array)$node->attribute)
                                        ->addContent($this->innerHtml($node)));
        } elseif ($node->name == 'base') {
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

    private function walkForm($node, $form) {
        if ($node->name == 'a') {
            $this->page->addLink($this->tags()->createTag($node->name, (array)$node->attribute)
                                        ->addContent($this->innerHtml($node)));
        } elseif ($node->name == 'input') {
            $form->addWidget($this->tags()->createTag($node->name, (array)$node->attribute));
        } elseif ($node->name == 'button' || $node->name == 'textarea') {
            $form->addWidget($this->tags()->createTag($node->name, (array)$node->attribute)
                                          ->addContent($this->innerHtml($node)));
        } elseif ($node->name == 'label') {
            $this->labels[] = $this->tags()->createTag($node->name, (array)$node->attribute)
                                           ->addContent($this->innerHtml($node));
        }
        if ($node->hasChildren()) {
            foreach ($node->child as $child) {
                $this->walkForm($child, $form);
            }
        }
        return $form;
    }

    private function collectFrames($node) {
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
        return $raw;
    }

    private function tags() {
        return new SimpleTagBuilder();
    }

    private function attachLabels($forms, $labels) {
        foreach ($labels as $label) {
            foreach($forms as $form) {
                $form->attachLabelBySelector(
                        new SimpleById($label->getFor()),
                        $label->getText());
            }
        }
        return $forms;
    }
}
?>