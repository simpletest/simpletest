<?php

require_once __DIR__ . '/../src/unit_tester.php';
require_once __DIR__ . '/selenium/remote-control.php';

/**
 * Provides test case wrapper to a Selenium remote control instance.
 */
class SeleniumTestCase extends UnitTestCase
{
    /**
     * Selenium instantiation variables
     */
    protected $browser    = '';
    protected $browserUrl = '';
    protected $host       = 'localhost';
    protected $port       = '4444';
    protected $timeout    = 30000;

    protected $selenium;
    protected $newInstanceEachTest = true;

    public function __construct($name = 'Selenium Test Case')
    {
        parent::__construct($name);

        if (empty($this->browser)) {
            simpletest_trigger_error('browser property must be set in ' . get_class($this));
            exit;
        }

        if (empty($this->browserUrl)) {
            simpletest_trigger_error('browserUrl property must be set in ' . get_class($this));
            exit;
        }
    }

    public function setUp()
    {
        parent::setUp();

        if (is_null($this->selenium)) {
            $this->selenium = new SimpleSeleniumRemoteControl(
                $this->browser,
                $this->browserUrl,
                $this->host,
                $this->port,
                $this->timeout
            );
            $this->selenium->start();
        }
    }

    public function tearDown()
    {
        parent::tearDown();

        if ($this->newInstanceEachTest) {
            $this->selenium->stop();
            $this->selenium = null;
        }
    }

    public function __call($method, $arguments)
    {
        if (substr($method, 0, 6) == 'verify') {
            return $this->assertTrue(
                call_user_func_array(
                    array($this->selenium, $method),
                    $arguments
                ),
                sprintf('%s failed', $method)
            );
        }

        return call_user_func_array(
            array($this->selenium, $method),
            $arguments
        );
    }

    public function verifyText($text)
    {
        return $this->assertTrue(
            $this->selenium->verifyText($text),
            sprintf(
                'verifyText failed when on [%s]',
                $text
            )
        );
    }

    public function verifyTextPresent($text)
    {
        return $this->assertTrue(
            $this->selenium->verifyTextPresent($text),
            sprintf(
                'verifyTextPresent failed when on [%s]',
                $text
            )
        );
    }

    public function verifyTextNotPresent($text)
    {
        return $this->assertTrue(
            $this->selenium->verifyTextNotPresent($text),
            sprintf(
                'verifyTextNotPresent failed on [%s]',
                $text
            )
        );
    }

    public function verifyValue($selector, $value)
    {
        return $this->assertTrue(
            $this->selenium->verifyValue($selector, $value),
            sprintf(
                'verifyValue failed on [%s] == [%s]',
                $selector,
                $value
            )
        );
    }

    public function verifyTitle($pattern)
    {
        return $this->assertTrue(
            $this->selenium->verifyTitle($pattern),
            sprintf(
                'verifyTitle failed on [%s]',
                $pattern
            )
        );
    }
}
