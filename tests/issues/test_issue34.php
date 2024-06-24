<?php declare(strict_types=1);

require_once \dirname(__DIR__, 2) . '/src/autorun.php';

require_once \dirname(__DIR__, 2) . '/src/test_case.php';

require_once \dirname(__DIR__, 2) . '/src/browser.php';

/**
 * @see https://github.com/simpletest/simpletest/issues/34
 */
class issue34 extends UnitTestCase
{
    /*public function testShouldAccessWebsiteURLUsingTLS11(): void
    {
        $browser = new SimpleBrowser;
        $browser->get('https://tls1test.salesforce.com');

        $this->assertEqual($browser->getResponseCode(), 200);
    }*/

    public function testSecureSocketConnection(): void
    {
        $host      = 'google.com';
        $port      = 443;
        $timeout   = 10;
        $transport = 'tlsv1.2';
        $socket    = new SimpleSecureSocket($host, $port, $timeout, $transport);
        $r         = $socket->openSocket($host, $port, $error_number, $error, $timeout);
        $this->assertTrue((bool) $r);
    }

    /*public function testPlatformFsockopen(): void
    {
        // TLSv1.1 request:
        \fsockopen('tlsv1.1://na160.salesforce.com', 443, $errno, $errstr, 30);
        $this->assertEqual($errno, '');
        $this->assertEqual($errstr, '');

        // TLSv1.2 request:
        \fsockopen('tlsv1.2://na160.salesforce.com', 443, $errno, $errstr, 30);
        $this->assertEqual($errno, '');
        $this->assertEqual($errstr, '');
    }*/

    /*public function testDebuggingHelper()
    {
        var_dump(stream_get_transports(), OPENSSL_VERSION_TEXT);
    }*/
}
