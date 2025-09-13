<?php declare(strict_types=1);

/**
 * Selenium Remote Control Class.
 */
class SimpleSeleniumRemoteControl
{
    private $_browser    = '';
    private $_browserUrl = '';
    private $_host       = 'localhost';
    private $_port       = 4444;
    private $_timeout    = 30000;
    private $_sessionId;
    private $_commandMap = [
        'bool' => [
            'verify',
            'verifyTextPresent',
            'verifyTextNotPresent',
            'verifyValue',
        ],
        'string' => [
            'getNewBrowserSession',
        ],
    ];

    public function __construct($browser, $browserUrl, $host = 'localhost', $port = 4444, $timeout = 30000)
    {
        $this->_browser    = $browser;
        $this->_browserUrl = $browserUrl;
        $this->_host       = $host;
        $this->_port       = $port;
        $this->_timeout    = $timeout;
    }

    public function __call($method, $arguments)
    {
        $response = $this->cmd($method, $arguments);

        $type = null;

        foreach ($this->_commandMap as $candidateType => $commands) {
            if (\in_array($method, $commands, true)) {
                $type = $candidateType;

                break;
            }
        }

        switch ($type) {
            case 'bool' :
                return \substr($response, 0, 2) === 'OK';

                break;

            case 'string' :
            default:
                return $response;
        }
    }

    public function sessionIdParser($response)
    {
        return \substr($response, 3);
    }

    public function start(): void
    {
        $response         = $this->cmd('getNewBrowserSession', [$this->_browser, $this->_browserUrl]);
        $this->_sessionId = $this->sessionIdParser($response);
    }

    public function stop(): void
    {
        $this->cmd('testComplete');
        $this->_sessionId = null;
    }

    public function buildUrlCmd($method, $arguments = [])
    {
        $params = ['cmd=' . \urlencode($method)];
        $i      = 1;

        foreach ($arguments as $param) {
            $params[] = $i++ . '=' . \urlencode(\trim($param));
        }

        if ($this->_sessionId !== null) {
            $params[] = 'sessionId=' . $this->_sessionId;
        }

        return $this->_server() . '?' . \implode('&', $params);
    }

    public function cmd($method, $arguments = [])
    {
        $url = $this->buildUrlCmd($method, $arguments);

        return $this->_sendRequest($url);
    }

    public function isUp()
    {
        return (bool) @\fsockopen($this->_host, $this->_port, $errno, $errstr, 30);
    }

    private function _server()
    {
        return "http://{$this->_host}:{$this->_port}/selenium-server/driver/";
    }

    private function _initCurl($url)
    {
        if (!\function_exists('curl_init')) {
            throw new Exception('this code currently requires the curl extension');
        }

        if (!$ch = \curl_init($url)) {
            throw new Exception('Unable to setup curl');
        }
        \curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($ch, \CURLOPT_TIMEOUT, \floor($this->_timeout));

        return $ch;
    }

    private function _sendRequest($url)
    {
        $ch     = $this->_initCurl($url);
        $result = \curl_exec($ch);

        if (($errno = \curl_errno($ch)) != 0) {
            throw new Exception('Curl returned non-null errno ' . $errno . ':' . \curl_error($ch));
        }
        \curl_close($ch);

        return $result;
    }
}
