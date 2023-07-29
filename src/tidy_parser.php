<?php
/**
 * Builds the page object.
 */
class SimpleTidyPageBuilder
{
    /** @var array */
    private $forms = [];
    /** @var array */
    private $labels = [];
    /** @var SimplePage */
    private $page;
    /** @var array */
    private $widgets_by_id = [];

    public function __destruct()
    {
        $this->free();
    }

    /**
     * Frees up any references so as to allow the PHP garbage collection from unset() to work.
     *
     * @return void
     */
    private function free()
    {
        unset($this->page);
        $this->forms = [];
        $this->labels = [];
    }

    /**
     * This builder is only available if the 'tidy' extension is loaded.
     *
     * @return bool true if available
     */
    public function can()
    {
        return extension_loaded('tidy');
    }

    /**
     * Reads the raw content the page using HTML Tidy.
     *
     * @param SimpleHttpResponse $response SimpleHttpResponse  Fetched response
     *
     * @return SimplePage newly parsed page
     */
    public function parse($response)
    {
        $this->page = new SimplePage($response);

        $tidy_input = $this->insertGuards($response->getContent());

        $tidy_config = [
            'output-xml' => false,
            'wrap' => '0',
            'indent' => 'no'
        ];

        /** @var object $tidied */
        $tidied = tidy_parse_string($tidy_input, $tidy_config, 'latin1');

        $this->walkTree($tidied->html());
        $this->attachLabels($this->widgets_by_id, $this->labels);
        $this->page->setForms($this->forms);
        $page = $this->page;
        $this->free();

        return $page;
    }

    /**
     * Stops HTMLTidy stripping content that we wish to preserve.
     *
     * @param string $html the raw html
     *
     * @return string the html with guard tags inserted
     */
    private function insertGuards($html)
    {
        return $this->insertEmptyTagGuards($this->insertTextareaSimpleWhitespaceGuards($html));
    }

    /**
     * Removes the extra content added during the parse stage in order to preserve content we don't
     * want stripped out by HTMLTidy.
     *
     * @param string $html the raw html
     *
     * @return string the html with guard tags removed
     */
    private function stripGuards($html)
    {
        return $this->stripTextareaWhitespaceGuards($this->stripEmptyTagGuards($html));
    }

    /**
     * HTML tidy strips out empty tags such as <option> which we need to preserve.
     * This method inserts an additional marker.
     *
     * @param string $html the raw html
     *
     * @return string the html with guards inserted
     */
    private function insertEmptyTagGuards($html)
    {
        return preg_replace(
            '#<(option|textarea)([^>]*)>(\s*)</(option|textarea)>#is',
            '<\1\2>___EMPTY___\3</\4>',
            $html
        );
    }

    /**
     * HTML tidy strips out empty tags such as <option> which we need to preserve.
     * This method strips additional markers inserted by SimpleTest
     * to the tidy output used to make the tags non-empty.
     * This ensures their preservation.
     *
     * @param string $html the raw html
     *
     * @return string the html with guards removed
     */
    private function stripEmptyTagGuards($html)
    {
        return preg_replace('#(^|>)(\s*)___EMPTY___(\s*)(</|$)#i', '\2\3', $html);
    }

    /**
     * By parsing the XML output of tidy, we lose some whitespace information in textarea tags.
     * We temporarily recode this data ourselves so as not to lose it.
     *
     * @param string $html the raw html
     *
     * @return string the html with guards inserted
     */
    private function insertTextareaSimpleWhitespaceGuards($html)
    {
        return preg_replace_callback(
            '#<textarea([^>]*)>(.*?)</textarea>#is',
            [$this, 'insertWhitespaceGuards'],
            $html
        );
    }

    /**
     * Callback for insertTextareaSimpleWhitespaceGuards().
     *
     * @param array $matches result of preg_replace_callback()
     *
     * @return string guard tags now replace whitespace
     */
    private function insertWhitespaceGuards($matches)
    {
        return '<textarea'.$matches[1].'>'.
                str_replace(
                    ["\n", "\r", "\t", ' '],
                    ['___NEWLINE___', '___CR___', '___TAB___', '___SPACE___'],
                    $matches[2]
                ).
                '</textarea>';
    }

    /**
     * Removes the whitespace preserving guards we added before parsing.
     *
     * @param string $html the raw html
     *
     * @return string the html with guards removed
     */
    private function stripTextareaWhitespaceGuards($html)
    {
        return str_replace(
            ['___NEWLINE___', '___CR___', '___TAB___', '___SPACE___'],
            ["\n", "\r", "\t", ' '],
            $html
        );
    }

    /**
     * Visits the given node and all children.
     *
     * @param object $node tidy XML node
     *
     * @return void
     */
    private function walkTree($node)
    {
        if ('a' === $node->name) {
            $this->page->addLink($this->tags()->createTag($node->name, (array) $node->attribute)
                                        ->addContent($this->innerHtml($node)));
        } elseif ('base' === $node->name and isset($node->attribute['href'])) {
            $this->page->setBase($node->attribute['href']);
        } elseif ('title' === $node->name) {
            $this->page->setTitle($this->tags()->createTag($node->name, (array) $node->attribute)
                                         ->addContent($this->innerHtml($node)));
        } elseif ('frameset' === $node->name) {
            $this->page->setFrames($this->collectFrames($node));
        } elseif ('form' === $node->name) {
            $this->forms[] = $this->walkForm($node, $this->createEmptyForm($node));
        } elseif ('label' === $node->name) {
            $this->labels[] = $this->tags()->createTag($node->name, (array) $node->attribute)
                                           ->addContent($this->innerHtml($node));
        } else {
            $this->walkChildren($node);
        }
    }

    /**
     * Helper method for traversing the XML tree.
     *
     * @param object $node tidy XML node
     *
     * @return void
     */
    private function walkChildren($node)
    {
        if ($node->hasChildren()) {
            foreach ($node->child as $child) {
                $this->walkTree($child);
            }
        }
    }

    /**
     * Facade for forms containing preparsed widgets.
     *
     * @param object $node tidy XML node
     *
     * @return SimpleForm facade for SimpleBrowser
     */
    private function createEmptyForm($node)
    {
        /** @var SimpleTag */
        $tag = $this->tags()->createTag($node->name, (array) $node->attribute);

        return new SimpleForm($tag, $this->page);
    }

    /**
     * Visits the given node and all children.
     *
     * @param object $node tidy XML node
     * @param object $form
     * @param string $enclosing_label
     *
     * @return object|SimpleForm
     */
    private function walkForm($node, $form, $enclosing_label = '')
    {
        if ('a' === $node->name) {
            $this->page->addLink($this->tags()->createTag($node->name, (array) $node->attribute)
                                              ->addContent($this->innerHtml($node)));
        } elseif (in_array($node->name, ['input', 'button', 'textarea', 'select'])) {
            $this->addWidgetToForm($node, $form, $enclosing_label);
        } elseif ('label' === $node->name) {
            $this->labels[] = $this->tags()->createTag($node->name, (array) $node->attribute)
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

    /**
     * Tests a node for a "for" atribute. Used for attaching labels.
     *
     * @param object $node tidy XML node
     *
     * @return bool true if the "for" attribute exists
     */
    private function hasFor($node)
    {
        return isset($node->attribute) and $node->attribute['for'];
    }

    /**
     * Adds the widget into the form container.
     *
     * @param object     $node            tidy XML node of widget
     * @param SimpleForm $form            form to add it to
     * @param string     $enclosing_label the label of any label tag we might be in
     *
     * @return void
     */
    private function addWidgetToForm($node, $form, $enclosing_label)
    {
        /** @var object|SimpleTag */
        $widget = $this->tags()->createTag($node->name, $this->attributes($node));
        if (!$widget) {
            return;
        }
        $widget->setLabel($enclosing_label)
               ->addContent($this->innerHtml($node));
        if ('select' == $node->name) {
            $widget->addTags($this->collectSelectOptions($node));
        }
        $form->addWidget($widget);
        $this->indexWidgetById($widget);
    }

    /**
     * Fills the widget cache to speed up searching.
     *
     * @param SimpleTag $widget parsed widget to cache
     *
     * @return void
     */
    private function indexWidgetById($widget)
    {
        $id = $widget->getAttribute('id');
        if (!$id) {
            return;
        }
        if (!isset($this->widgets_by_id[$id])) {
            $this->widgets_by_id[$id] = [];
        }
        $this->widgets_by_id[$id][] = $widget;
    }

    /**
     * Parses the options from inside an XML select node.
     *
     * @param object $node tidy XML node
     *
     * @return array list of SimpleTag options
     */
    private function collectSelectOptions($node)
    {
        $options = [];
        if ('option' === $node->name) {
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

    /**
     * Convenience method for collecting all the attributes of a tag.
     * Not sure why Tidy does not have this.
     *
     * @param object $node tidy XML node
     *
     * @return array hash of attribute strings
     */
    private function attributes($node)
    {
        if (!preg_match('|<[^ ]+\s(.*?)/?>|s', $node->value, $first_tag_contents)) {
            return [];
        }
        $attributes = [];
        preg_match_all('/\S+\s*=\s*\'[^\']*\'|(\S+\s*=\s*"[^"]*")|([^ =]+\s*=\s*[^ "\']+?)|[^ "\']+/', $first_tag_contents[1], $matches);
        foreach ($matches[0] as $unparsed) {
            $attributes = $this->mergeAttribute($attributes, $unparsed);
        }

        return $attributes;
    }

    /**
     * Overlay an attribute into the attributes hash.
     *
     * @param array  $attributes current attribute list
     * @param string $raw        raw attribute string with both key and value
     *
     * @return array new attribute hash
     */
    private function mergeAttribute($attributes, $raw)
    {
        $parts = explode('=', $raw);
        list($name, $value) = 1 === count($parts) ? [$parts[0], $parts[0]] : $parts;
        $attributes[trim($name)] = html_entity_decode($this->dequote(trim($value)), ENT_QUOTES);

        return $attributes;
    }

    /**
     * Remove start and end quotes.
     *
     * @param string $quoted a quoted string
     *
     * @return string quotes are gone
     */
    private function dequote($quoted)
    {
        if (preg_match('/^(\'([^\']*)\'|"([^"]*)")$/', $quoted, $matches)) {
            return $matches[3] ?? $matches[2];
        }

        return $quoted;
    }

    /**
     * Collects frame information inside a frameset tag.
     *
     * @param object $node tidy XML node
     *
     * @return array list of SimpleTag frame descriptions
     */
    private function collectFrames($node)
    {
        $frames = [];
        if ('frame' === $node->name) {
            $frames = [$this->tags()->createTag($node->name, (array) $node->attribute)];
        } elseif ($node->hasChildren()) {
            $frames = [];
            foreach ($node->child as $child) {
                $frames = array_merge($frames, $this->collectFrames($child));
            }
        }

        return $frames;
    }

    /**
     * Extracts the XML node text.
     *
     * @param object $node tidy XML node
     *
     * @return string the text only
     */
    private function innerHtml($node)
    {
        $raw = '';
        if ($node->hasChildren()) {
            foreach ($node->child as $child) {
                $raw .= $child->value;
            }
        }

        return $this->stripGuards($raw);
    }

    /**
     * Factory for parsed content holders.
     *
     * @return SimpleTagBuilder factory
     */
    private function tags()
    {
        return new SimpleTagBuilder();
    }

    /**
     * Called at the end of a parse run.
     * Attaches any non-wrapping labels to their form elements.
     *
     * @param array $widgets_by_id cached SimpleTag hash
     * @param array $labels        simpleTag label elements
     *
     * @return void
     */
    private function attachLabels($widgets_by_id, $labels)
    {
        foreach ($labels as $label) {
            $for = $label->getFor();
            if ($for and isset($widgets_by_id[$for])) {
                $text = $label->getText();
                foreach ($widgets_by_id[$for] as $widget) {
                    $widget->setLabel($text);
                }
            }
        }
    }
}
