<?php declare(strict_types=1);

require_once __DIR__ . '/../src/autorun.php';

require_once __DIR__ . '/../src/socket.php';

require_once __DIR__ . '/../src/http.php';

require_once __DIR__ . '/../src/compatibility.php';

if (SimpleTest::getDefaultProxy()) {
    SimpleTest::ignore('LiveHttpTestCase');
}

class LiveHttpTestCase extends UnitTestCase
{
    protected $host = 'localhost';
    protected $port = 8080;

    public function skip(): void
    {
        $socket = new SimpleSocket($this->host, $this->port, 15, 8);

        parent::skipIf(
            !$socket->isOpen(),
            \sprintf('The LiveHttpTestCase requires that a webserver runs at %s:%s', $this->host, $this->port),
        );
    }

    public function testBadSocket(): void
    {
        $socket = new SimpleSocket('bad_url', 111, 5);
        $this->assertTrue($socket->isError());
        $this->assertPattern(
            '/Cannot open \\[bad_url:111\\] with \\[/',
            $socket->getError(),
        );
        $this->assertFalse($socket->isOpen());
        $this->assertFalse($socket->write('A message'));
    }

    public function testSocketClosure(): void
    {
        $socket = new SimpleSocket($this->host, $this->port, 15, 8);
        $this->assertTrue($socket->isOpen());
        $this->assertTrue($socket->write("GET /network_confirm.php HTTP/1.0\r\n"));
        $socket->write("Host: {$this->host}\r\n");
        $socket->write("Connection: close\r\n\r\n");
        $this->assertEqual($socket->read(), 'HTTP/1.0');
        $socket->close();
        $this->assertIdentical($socket->read(), false);
    }

    public function testRecordOfSentCharacters(): void
    {
        $socket = new SimpleSocket($this->host, $this->port, 15);
        $this->assertTrue($socket->write("GET /network_confirm.php HTTP/1.0\r\n"));
        $socket->write("Host: {$this->host}\r\n");
        $socket->write("Connection: close\r\n\r\n");
        $socket->close();
        $this->assertEqual(
            $socket->getSent(),
            "GET /network_confirm.php HTTP/1.0\r\n" .
                "Host: {$this->host}\r\n" .
                "Connection: close\r\n\r\n",
        );
    }
}
