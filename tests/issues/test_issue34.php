<?php declare(strict_types=1);

require_once __DIR__ . '/../../autorun.php';

require_once __DIR__ . '/../../test_case.php';

require_once __DIR__ . '/../../browser.php';

/**
 * @see https://github.com/simpletest/simpletest/issues/34
 */
class issue34 extends UnitTestCase
{
    public function testShouldAccessWebsiteURLUsingTLS11(): void
    {
        $browser = new SimpleBrowser;
        $browser->get('https://tls1test.salesforce.com');

        $this->assertEqual($browser->getResponseCode(), 200);
    }

    public function testPlatformFsockopen(): void
    {
        // TLSv1.1 request:
        \fsockopen('tlsv1.1://tls1test.salesforce.com', 443, $errno, $errstr, 30);
        $this->assertEqual($errno, '');
        $this->assertEqual($errstr, '');

        // TLSv1.2 request:
        \fsockopen('tlsv1.2://tls1test.salesforce.com', 443, $errno, $errstr, 30);
        $this->assertEqual($errno, '');
        $this->assertEqual($errstr, '');
    }

    /*public function testDebuggingHelper()
    {
        var_dump(stream_get_transports(), OPENSSL_VERSION_TEXT);
    }*/
}
