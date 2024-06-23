<?php declare(strict_types=1);

/**
 * CssSelector.
 *
 * Allow to navigate a DOM with CSS selector.
 *
 * based on getElementsBySelector version 0.4 - Simon Willison, 2003-03-25
 * http://simon.incutio.com/archive/2003/03/25/getElementsBySelector
 *
 * derived from sfDomCssSelector Id 3053 (Symfony version 1.0.2) - Fabien Potencier, 2006-12-16
 * http://www.symfony-project.com/api/symfony/util/sfDomCssSelector.html
 *
 * @author Perrick Penet <perrick@noparking.net>
 *
 * @param DomDocument $dom
 */
class CssSelector
{
    protected $dom;

    public function __construct($dom)
    {
        $this->dom = $dom;
    }

    public function getTexts($selector)
    {
        $texts = [];

        foreach ($this->getElements($selector) as $element) {
            $texts[] = $element->nodeValue;
        }

        return $texts;
    }

    public function getElements($selector)
    {
        $all_nodes = [];

        foreach ($this->tokenize_selectors($selector) as $selector) {
            $nodes = [$this->dom];

            foreach ($this->tokenize($selector) as $token) {
                $combinator = $token['combinator'];
                $token      = \trim($token['name']);
                $pos        = \strpos($token, '#');

                if (false !== $pos && \preg_match('/^[A-Za-z0-9]*$/', \substr($token, 0, $pos))) {
                    // Token is an ID selector
                    $tagName = \substr($token, 0, $pos);
                    $id      = \substr($token, $pos + 1);
                    $xpath   = new DomXPath($this->dom);
                    $element = $xpath->query(\sprintf("//*[@id = '%s']", $id))->item(0);

                    if (!$element || ($tagName && \strtolower($element->nodeName) !== $tagName)) {
                        // tag with that ID not found
                        return [];
                    }

                    // Set nodes to contain just this element
                    $nodes = [$element];

                    continue; // Skip to next token
                }

                $pos = \strpos($token, '.');

                if (false !== $pos && \preg_match('/^[A-Za-z0-9]*$/', \substr($token, 0, $pos))) {
                    // Token contains a class selector
                    $tagName = \substr($token, 0, $pos);

                    if ($tagName === '' || $tagName === '0') {
                        $tagName = '*';
                    }
                    $className = \substr($token, $pos + 1);

                    // Get elements matching tag, filter them for class selector
                    $founds = $this->getElementsByTagName($nodes, $tagName, $combinator);
                    $nodes  = [];

                    foreach ($founds as $found) {
                        if (\preg_match('/\b' . $className . '\b/', $found->getAttribute('class'))) {
                            $nodes[] = $found;
                        }
                    }

                    continue; // Skip to next token
                }

                // Code to deal with attribute selectors
                if (\preg_match('/^(\w*)(\[.+\])$/', $token, $matches)) {
                    $tagName = $matches[1] ?: '*';
                    \preg_match_all('/
            \[
              (\w+)                 # attribute
              ([=~\|\^\$\*]?)       # modifier (optional)
              =?                    # equal (optional)
              (
                "([^"]*)"           # quoted value (optional)
                |
                ([^\]]*)            # non quoted value (optional)
              )
            \]
          /x', $matches[2], $matches, PREG_SET_ORDER);

                    // Grab all of the tagName elements within current node
                    $founds = $this->getElementsByTagName($nodes, $tagName, $combinator);
                    $nodes  = [];

                    foreach ($founds as $found) {
                        $ok = false;

                        foreach ($matches as $match) {
                            $attrName     = $match[1];
                            $attrOperator = $match[2];
                            $attrValue    = $match[4];

                            switch ($attrOperator) {
                                case '=': // Equality
                                    $ok = $found->getAttribute($attrName) == $attrValue;

                                    break;

                                case '~': // Match one of space seperated words
                                    $ok = \preg_match('/\b' . \preg_quote($attrValue, '/') . '\b/', $found->getAttribute($attrName));

                                    break;

                                case '|': // Match start with value followed by optional hyphen
                                    $ok = \preg_match('/^' . \preg_quote($attrValue, '/') . '-?/', $found->getAttribute($attrName));

                                    break;

                                case '^': // Match starts with value
                                    $ok = \str_starts_with($found->getAttribute($attrName), $attrValue);

                                    break;

                                case '$': // Match ends with value
                                    $ok = $attrValue == \substr($found->getAttribute($attrName), -\strlen($attrValue));

                                    break;

                                case '*': // Match ends with value
                                    $ok = \str_contains($found->getAttribute($attrName), $attrValue);

                                    break;

                                default:
                                    // Just test for existence of attribute
                                    $ok = $found->hasAttribute($attrName);
                            }

                            if (false == $ok) {
                                break;
                            }
                        }

                        if ($ok) {
                            $nodes[] = $found;
                        }
                    }

                    continue; // Skip to next token
                }

                if (\preg_match('/^(\w*)(:first-child)$/', $token, $matches)) {
                    $token      = $matches[1] ?: '*';
                    $combinator = $matches[2] ?: '';
                }

                // If we get here, token is JUST an element (not a class or ID selector)
                $nodes = $this->getElementsByTagName($nodes, $token, $combinator);
            }

            foreach ($nodes as $node) {
                if (!$node->getAttribute('sf_matched')) {
                    $node->setAttribute('sf_matched', true);
                    $all_nodes[] = $node;
                }
            }
        }

        foreach ($all_nodes as $node) {
            $node->removeAttribute('sf_matched');
        }

        return $all_nodes;
    }

    protected function getElementsByTagName($nodes, $tagName, $combinator = ' ')
    {
        $founds = [];

        foreach ($nodes as $node) {
            switch ($combinator) {
                case ' ':
                    foreach ($node->getElementsByTagName($tagName) as $element) {
                        $founds[] = $element;
                    }

                    break;

                case '>':
                    foreach ($node->childNodes as $element) {
                        if ($tagName == $element->nodeName) {
                            $founds[] = $element;
                        }
                    }

                    break;

                case '+':
                    $element = $node->nextSibling;

                    if (isset($element->nodeName) && $element->nodeName == '#text') {
                        $element = $element->nextSibling;
                    }

                    if ($element && $tagName == $element->nodeName) {
                        $founds[] = $element;
                    }

                    break;

                case ':first-child':
                    foreach ($node->getElementsByTagName($tagName) as $element) {
                        if (\count($founds) == 0) {
                            $founds[] = $element;
                        }
                    }

                    break;
            }
        }

        return $founds;
    }

    protected function tokenize_selectors($selector)
    {
        // split tokens by , except in an attribute selector
        $tokens = [];
        $quoted = false;
        $token  = '';

        for ($i = 0, $max = \strlen($selector); $i < $max; $i++) {
            if (',' == $selector[$i] && !$quoted) {
                $tokens[] = \trim($token);
                $token    = '';
            } elseif ('"' == $selector[$i]) {
                $token .= $selector[$i];
                $quoted = !$quoted;
            } else {
                $token .= $selector[$i];
            }
        }

        if ($token !== '' && $token !== '0') {
            $tokens[] = \trim($token);
        }

        return $tokens;
    }

    protected function tokenize($selector)
    {
        // split tokens by space except if space is in an attribute selector
        $tokens      = [];
        $combinators = [' ', '>', '+'];
        $quoted      = false;
        $token       = ['combinator' => ' ', 'name' => ''];

        for ($i = 0, $max = \strlen($selector); $i < $max; $i++) {
            if (\in_array($selector[$i], $combinators, true) && !$quoted) {
                // remove all whitespaces around the combinator
                $combinator = $selector[$i];

                while (\in_array($selector[$i + 1], $combinators, true)) {
                    if (' ' != $selector[++$i]) {
                        $combinator = $selector[$i];
                    }
                }

                $tokens[] = $token;
                $token    = ['combinator' => $combinator, 'name' => ''];
            } elseif ('"' == $selector[$i]) {
                $token['name'] .= $selector[$i];
                $quoted = !$quoted;
            } else {
                $token['name'] .= $selector[$i];
            }
        }

        if ($token['name'] !== '' && $token['name'] !== '0') {
            $tokens[] = $token;
        }

        return $tokens;
    }
}
