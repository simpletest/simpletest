<?php declare(strict_types=1);

require_once __DIR__ . '/tag.php';

require_once __DIR__ . '/encoding.php';

require_once __DIR__ . '/selector.php';

/**
 * Form tag class to hold widget values.
 */
class SimpleForm
{
    private $action;
    private $buttons    = [];
    private $checkboxes = [];
    private $encoding;
    private $id;
    private $images = [];
    private $method;
    private $radios  = [];
    private $widgets = [];

    /**
     * Starts with no held controls/widgets.
     *
     * @param SimpleTag  $tag  form tag to read
     * @param SimplePage $page holding page
     */
    public function __construct($tag, $page)
    {
        $this->action   = $this->createAction($tag->getAttribute('action'), $page);
        $this->encoding = $this->setEncodingClass($tag);
        $this->id       = $tag->getAttribute('id');
        $this->method   = $tag->getAttribute('method');
    }

    /**
     * Accessor for method of form submission.
     *
     * @return string either get or post
     */
    public function getMethod()
    {
        return $this->method ? \strtolower($this->method) : 'get';
    }

    /**
     * Absolute URL of the target.
     *
     * @return simpleUrl URL target
     */
    public function getAction()
    {
        $url = $this->action;

        if ('get' === $this->getMethod()) {
            $url->clearRequest();
        }

        return $url;
    }

    /**
     * ID field of form for unique identification.
     *
     * @return string unique tag ID
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Adds a tag contents to the form.
     *
     * @param SimpleWidget $tag input tag to add
     */
    public function addWidget($tag): void
    {
        $type = $tag->getAttribute('type');

        if ($type && 'submit' === \strtolower($type)) {
            $this->buttons[] = $tag;
        } elseif ($type && 'image' === \strtolower($type)) {
            $this->images[] = $tag;
        } elseif ($tag->getName()) {
            $this->setWidget($tag);
        }
    }

    /**
     * Extracts current value from form.
     *
     * @param $selector criteria to apply
     *
     * @return array|string Value(s) as string or null if not set
     */
    public function getValue(SelectorInterface $selector)
    {
        foreach ($this->widgets as $widget) {
            if ($selector->isMatch($widget)) {
                return $widget->getValue();
            }
        }

        foreach ($this->buttons as $button) {
            if ($selector->isMatch($button)) {
                return $button->getValue();
            }
        }

        return null;
    }

    /**
     * Sets a widget value within the form.
     *
     * @param        $selector criteria to apply
     * @param string $value    value to input into the widget
     *
     * @return bool True if value is legal, false otherwise.
     *              If the field is not present, nothing will be set.
     */
    public function setField(SelectorInterface $selector, $value, $position = false)
    {
        $success   = false;
        $_position = 0;

        foreach ($this->widgets as $widget) {
            if ($selector->isMatch($widget)) {
                $_position++;

                if (false === $position || $_position === (int) $position) {
                    if ($widget->setValue($value)) {
                        $success = true;
                    }
                }
            }
        }

        return $success;
    }

    /**
     * Used by the page object to set widgets labels to external label tags.
     *
     * @param $selector criteria to apply
     */
    public function attachLabelBySelector(SelectorInterface $selector, $label): void
    {
        foreach ($this->widgets as $widget) {
            if ($selector->isMatch($widget)) {
                if (\method_exists($widget, 'setLabel')) {
                    $widget->setLabel($label);

                    return;
                }
            }
        }
    }

    /**
     * Test to see if a form has a submit button.
     *
     * @param $selector criteria to apply
     *
     * @return bool true if present
     */
    public function hasSubmit(SelectorInterface $selector)
    {
        foreach ($this->buttons as $button) {
            if ($selector->isMatch($button)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Test to see if a form has an image control.
     *
     * @param $selector criteria to apply
     *
     * @return bool true if present
     */
    public function hasImage(SelectorInterface $selector)
    {
        foreach ($this->images as $image) {
            if ($selector->isMatch($image)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Test to see if a form has a button element (eg. <button>).
     *
     * @param SelectorInterface $selector criteria to apply
     *
     * @return bool true if present
     */
    public function hasButton(SelectorInterface $selector)
    {
        foreach ($this->widgets as $widget) {
            if ($selector->isMatch($widget)) {
                // Only treat real button elements (and input type=button if present)
                $tag = $widget->getTagName();

                if ('button' === $tag) {
                    return true;
                }

                $type = $widget->getAttribute('type');

                if ($type && 'button' === \strtolower($type)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Gets the submit values for a selected button.
     *
     * @param            $selector   criteria to apply
     * @param array|bool $additional additional data for the form
     *
     * @return SimpleEncoding submitted values or false if there is no such button in the
     *                        form
     */
    public function submitButton(SelectorInterface $selector, $additional = false)
    {
        $additional = $additional ?: [];

        foreach ($this->buttons as $button) {
            if ($selector->isMatch($button)) {
                $encoding = $this->encode();
                $button->write($encoding);

                if ($additional) {
                    $encoding->merge($additional);
                }

                return $encoding;
            }
        }

        return false;
    }

    /**
     * Gets the submit values for an image.
     *
     * @param                  $selector   criteria to apply
     * @param int              $x          X-coordinate of click
     * @param int              $y          Y-coordinate of click
     * @param array|bool|mixed $additional additional data for the form
     *
     * @return false|SimpleFormEncoding submitted values or false if there is no such button in the form
     */
    public function submitImage(SelectorInterface $selector, $x, $y, $additional = false)
    {
        $additional = $additional ?: [];

        foreach ($this->images as $image) {
            if ($selector->isMatch($image)) {
                $encoding = $this->encode();
                $image->write($encoding, $x, $y);

                if ($additional) {
                    $encoding->merge($additional);
                }

                return $encoding;
            }
        }

        return false;
    }

    /**
     * Simply submits the form without the submit button value.
     * Used when there is only one button or it is unimportant.
     *
     * @param false|mixed $additional
     *
     * @return hash submitted values
     */
    public function submit($additional = false)
    {
        $encoding = $this->encode();

        if ($additional) {
            $encoding->merge($additional);
        }

        return $encoding;
    }

    /**
     * Gets the submit values for a button-like widget (eg. <button>).
     *
     * @param SelectorInterface $selector   criteria to apply
     * @param array|bool        $additional additional data for the form
     *
     * @return false|SimpleEncoding submitted values or false if there is no such widget in the form
     */
    public function submitWidget(SelectorInterface $selector, $additional = false)
    {
        $additional = $additional ?: [];

        foreach ($this->widgets as $widget) {
            if ($selector->isMatch($widget)) {
                $tag = $widget->getTagName();

                $type = $widget->getAttribute('type');

                if ('button' === $tag || ($type && 'button' === \strtolower($type))) {
                    $encoding = $this->encode();
                    $widget->write($encoding);

                    if ($additional) {
                        $encoding->merge($additional);
                    }

                    return $encoding;
                }
            }
        }

        return false;
    }

    /**
     * Creates the request packet to be sent by the form.
     *
     * @param SimpleTag $tag form tag to read
     *
     * @return string packet class
     */
    protected function setEncodingClass($tag)
    {
        $method = $tag->getAttribute('method');

        if ($method && 'post' === \strtolower($method)) {
            $enctype = $tag->getAttribute('enctype');

            if ($enctype && 'multipart/form-data' === \strtolower($enctype)) {
                return 'SimpleMultipartEncoding';
            }

            return 'SimplePostEncoding';
        }

        return 'SimpleGetEncoding';
    }

    /**
     * Combined action attribute with current location to get an absolute form target.
     *
     * @param string $action action attribute from form tag
     * @param mixed  $page   page location
     *
     * @return SimpleUrl absolute form target
     */
    protected function createAction($action, $page)
    {
        if (('' === $action) || (false === $action)) {
            return $page->expandUrl($page->getUrl());
        }

        return $page->expandUrl(new SimpleUrl($action));
    }

    /**
     * Creates the encoding for the current values in the form.
     *
     * @return object|SimpleFormEncoding request to submit
     */
    protected function encode()
    {
        $class    = $this->encoding;
        $encoding = new $class;

        foreach ($this->widgets as $widget) {
            $widget->write($encoding);
        }

        return $encoding;
    }

    /**
     * Sets the widget into the form, grouping radio buttons if any.
     *
     * @param SimpleWidget $tag incoming form control
     */
    protected function setWidget($tag): void
    {
        $type = $tag->getAttribute('type');

        if ($type && 'radio' === \strtolower($type)) {
            $this->addRadioButton($tag);
        } elseif ($type && 'checkbox' === \strtolower($type)) {
            $this->addCheckbox($tag);
        } else {
            $this->widgets[] = $tag;
        }
    }

    /**
     * Adds a radio button, building a group if necessary.
     *
     * @param SimpleRadioButtonTag $tag incoming form control
     */
    protected function addRadioButton($tag): void
    {
        if (!isset($this->radios[$tag->getName()])) {
            $this->widgets[]               = new SimpleRadioGroup;
            $this->radios[$tag->getName()] = \count($this->widgets) - 1;
        }
        $this->widgets[$this->radios[$tag->getName()]]->addWidget($tag);
    }

    /**
     * Adds a checkbox, making it a group on a repeated name.
     *
     * @param SimpleCheckboxTag $tag incoming form control
     */
    protected function addCheckbox($tag): void
    {
        if (!isset($this->checkboxes[$tag->getName()])) {
            $this->widgets[]                   = $tag;
            $this->checkboxes[$tag->getName()] = \count($this->widgets) - 1;
        } else {
            $index = $this->checkboxes[$tag->getName()];

            if (!\is_a($this->widgets[$index], 'SimpleCheckboxGroup')) {
                $previous              = $this->widgets[$index];
                $this->widgets[$index] = new SimpleCheckboxGroup;
                $this->widgets[$index]->addWidget($previous);
            }
            $this->widgets[$index]->addWidget($tag);
        }
    }
}
