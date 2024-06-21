<?php declare(strict_types=1);

require_once __DIR__ . '/../src/autorun.php';

require_once __DIR__ . '/../src/url.php';

require_once __DIR__ . '/../src/socket.php';

Mock::generate('SimpleSocket');

class TestOfEncodedParts extends UnitTestCase
{
    public function testFormEncodedAsKeyEqualsValue(): void
    {
        $pair = new SimpleEncodedPair('a', 'A');
        $this->assertEqual($pair->asRequest(), 'a=A');
    }

    public function testMimeEncodedAsHeadersAndContent(): void
    {
        $pair = new SimpleEncodedPair('a', 'A');
        $this->assertEqual(
            $pair->asMime(),
            "Content-Disposition: form-data; name=\"a\"\r\n\r\nA",
        );
    }

    public function testAttachmentEncodedAsHeadersWithDispositionAndContent(): void
    {
        $part = new SimpleAttachment('a', 'A', 'aaa.txt');
        $this->assertEqual(
            $part->asMime(),
            "Content-Disposition: form-data; name=\"a\"; filename=\"aaa.txt\"\r\n" .
                        "Content-Type: text/plain\r\n\r\nA",
        );
    }
}

class TestOfEncoding extends UnitTestCase
{
    private $content_so_far;

    public function write($content): void
    {
        $this->content_so_far .= $content;
    }

    public function clear(): void
    {
        $this->content_so_far = '';
    }

    public function assertWritten($encoding, $content, $message = '%s'): void
    {
        $this->clear();
        $encoding->writeTo($this);
        $this->assertIdentical($this->content_so_far, $content, $message);
    }

    public function testGetEmpty(): void
    {
        $encoding = new SimpleGetEncoding;
        $this->assertIdentical($encoding->getValue('a'), false);
        $this->assertIdentical($encoding->asUrlRequest(), '');
    }

    public function testPostEmpty(): void
    {
        $encoding = new SimplePostEncoding;
        $this->assertIdentical($encoding->getValue('a'), false);
        $this->assertWritten($encoding, '');
    }

    public function testPrefilled(): void
    {
        $encoding = new SimplePostEncoding(['a' => 'aaa']);
        $this->assertIdentical($encoding->getValue('a'), 'aaa');
        $this->assertWritten($encoding, 'a=aaa');
    }

    public function testPrefilledWithTwoLevels(): void
    {
        $query    = ['a' => ['aa' => 'aaa']];
        $encoding = new SimplePostEncoding($query);
        $this->assertTrue($encoding->hasMoreThanOneLevel($query));
        $this->assertEqual($encoding->rewriteArrayWithMultipleLevels($query), ['a[aa]' => 'aaa']);
        $this->assertIdentical($encoding->getValue('a[aa]'), 'aaa');
        $this->assertWritten($encoding, 'a%5Baa%5D=aaa');
    }

    public function testPrefilledWithThreeLevels(): void
    {
        $query    = ['a' => ['aa' => ['aaa' => 'aaaa']]];
        $encoding = new SimplePostEncoding($query);
        $this->assertTrue($encoding->hasMoreThanOneLevel($query));
        $this->assertEqual($encoding->rewriteArrayWithMultipleLevels($query), ['a[aa][aaa]' => 'aaaa']);
        $this->assertIdentical($encoding->getValue('a[aa][aaa]'), 'aaaa');
        $this->assertWritten($encoding, 'a%5Baa%5D%5Baaa%5D=aaaa');
    }

    public function testPrefilledWithObject(): void
    {
        $encoding = new SimplePostEncoding(new SimpleEncoding(['a' => 'aaa']));
        $this->assertIdentical($encoding->getValue('a'), 'aaa');
        $this->assertWritten($encoding, 'a=aaa');
    }

    public function testMultiplePrefilled(): void
    {
        $query    = ['a' => ['a1', 'a2']];
        $encoding = new SimplePostEncoding($query);
        $this->assertTrue($encoding->hasMoreThanOneLevel($query));
        $this->assertEqual($encoding->rewriteArrayWithMultipleLevels($query), ['a[0]' => 'a1', 'a[1]' => 'a2']);
        $this->assertIdentical($encoding->getValue('a[0]'), 'a1');
        $this->assertIdentical($encoding->getValue('a[1]'), 'a2');
        $this->assertWritten($encoding, 'a%5B0%5D=a1&a%5B1%5D=a2');
    }

    public function testSingleParameter(): void
    {
        $encoding = new SimplePostEncoding;
        $encoding->add('a', 'Hello');
        $this->assertEqual($encoding->getValue('a'), 'Hello');
        $this->assertWritten($encoding, 'a=Hello');
    }

    public function testFalseParameter(): void
    {
        $encoding = new SimplePostEncoding;
        $encoding->add('a', false);
        $this->assertEqual($encoding->getValue('a'), false);
        $this->assertWritten($encoding, '');
    }

    public function testUrlEncoding(): void
    {
        $encoding = new SimplePostEncoding;
        $encoding->add('a', 'Hello there!');
        $this->assertWritten($encoding, 'a=Hello+there%21');
    }

    public function testUrlEncodingOfKey(): void
    {
        $encoding = new SimplePostEncoding;
        $encoding->add('a!', 'Hello');
        $this->assertWritten($encoding, 'a%21=Hello');
    }

    public function testMultipleParameter(): void
    {
        $encoding = new SimplePostEncoding;
        $encoding->add('a', 'Hello');
        $encoding->add('b', 'Goodbye');
        $this->assertWritten($encoding, 'a=Hello&b=Goodbye');
    }

    public function testEmptyParameters(): void
    {
        $encoding = new SimplePostEncoding;
        $encoding->add('a', '');
        $encoding->add('b', '');
        $this->assertWritten($encoding, 'a=&b=');
    }

    public function testRepeatedParameter(): void
    {
        $encoding = new SimplePostEncoding;
        $encoding->add('a', 'Hello');
        $encoding->add('a', 'Goodbye');
        $this->assertIdentical($encoding->getValue('a'), ['Hello', 'Goodbye']);
        $this->assertWritten($encoding, 'a=Hello&a=Goodbye');
    }

    public function testAddingLists(): void
    {
        $encoding = new SimplePostEncoding;
        $encoding->add('a', ['Hello', 'Goodbye']);
        $this->assertIdentical($encoding->getValue('a'), ['Hello', 'Goodbye']);
        $this->assertWritten($encoding, 'a=Hello&a=Goodbye');
    }

    public function testMergeInHash(): void
    {
        $encoding = new SimpleGetEncoding(['a' => 'A1', 'b' => 'B']);
        $encoding->merge(['a' => 'A2']);
        $this->assertIdentical($encoding->getValue('a'), ['A1', 'A2']);
        $this->assertIdentical($encoding->getValue('b'), 'B');
    }

    public function testMergeInObject(): void
    {
        $encoding = new SimpleGetEncoding(['a' => 'A1', 'b' => 'B']);
        $encoding->merge(new SimpleEncoding(['a' => 'A2']));
        $this->assertIdentical($encoding->getValue('a'), ['A1', 'A2']);
        $this->assertIdentical($encoding->getValue('b'), 'B');
    }

    public function testPrefilledMultipart(): void
    {
        $encoding = new SimpleMultipartEncoding(['a' => 'aaa'], 'boundary');
        $this->assertIdentical($encoding->getValue('a'), 'aaa');
        $this->assertwritten(
            $encoding,
            "--boundary\r\n" .
                "Content-Disposition: form-data; name=\"a\"\r\n" .
                "\r\n" .
                "aaa\r\n" .
                "--boundary--\r\n",
        );
    }

    public function testAttachment(): void
    {
        $encoding = new SimpleMultipartEncoding([], 'boundary');
        $encoding->attach('a', 'aaa', 'aaa.txt');
        $this->assertIdentical($encoding->getValue('a'), 'aaa.txt');
        $this->assertwritten(
            $encoding,
            "--boundary\r\n" .
                "Content-Disposition: form-data; name=\"a\"; filename=\"aaa.txt\"\r\n" .
                "Content-Type: text/plain\r\n" .
                "\r\n" .
                "aaa\r\n" .
                "--boundary--\r\n",
        );
    }

    public function testEntityEncodingDefaultContentType(): void
    {
        $encoding = new SimpleEntityEncoding;
        $this->assertIdentical($encoding->getContentType(), 'application/x-www-form-urlencoded');
        $this->assertWritten($encoding, '');
    }

    public function testEntityEncodingTextBody(): void
    {
        $encoding = new SimpleEntityEncoding('plain text');
        $this->assertIdentical($encoding->getContentType(), 'text/plain');
        $this->assertWritten($encoding, 'plain text');
    }

    public function testEntityEncodingXmlBody(): void
    {
        $encoding = new SimpleEntityEncoding('<p><a>xml</b><b>text</b></p>', 'text/xml');
        $this->assertIdentical($encoding->getContentType(), 'text/xml');
        $this->assertWritten($encoding, '<p><a>xml</b><b>text</b></p>');
    }
}

class TestOfEncodingHeaders extends UnitTestCase
{
    public function testEmptyEncodingWritesZeroContentLength(): void
    {
        $socket = new MockSimpleSocket;
        $socket->expectAt(0, 'write', ["Content-Length: 0\r\n"]);
        $socket->expectAt(1, 'write', ["Content-Type: application/x-www-form-urlencoded\r\n"]);
        $encoding = new SimpleEntityEncoding;
        $encoding->writeHeadersTo($socket);
    }

    public function testTextEncodingWritesDefaultContentType(): void
    {
        $socket = new MockSimpleSocket;
        $socket->expectAt(0, 'write', ["Content-Length: 18\r\n"]);
        $socket->expectAt(1, 'write', ["Content-Type: text/plain\r\n"]);
        $encoding = new SimpleEntityEncoding('one two three four');
        $encoding->writeHeadersTo($socket);
    }

    public function testEmptyMultipartEncodingWritesEndBoundaryContentLength(): void
    {
        $socket = new MockSimpleSocket;
        $socket->expectAt(0, 'write', ["Content-Length: 14\r\n"]);
        $socket->expectAt(1, 'write', ["Content-Type: multipart/form-data; boundary=boundary\r\n"]);
        $encoding = new SimpleMultipartEncoding([], 'boundary');
        $encoding->writeHeadersTo($socket);
    }
}
