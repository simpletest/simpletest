<?php
    // $Id$
    
    require_once(dirname(__FILE__) . '/../url.php');
    require_once(dirname(__FILE__) . '/../socket.php');
    
    Mock::generate('SimpleSocket');
    
    class TestOfFormEncoding extends UnitTestCase {
        var $_content_so_far;
        
        function write($content) {
            $this->_content_so_far .= $content;
        }
        
        function clear() {
            $this->_content_so_far = '';
        }
        
        function assertWritten($encoding, $content, $message = '%s') {
            $this->clear();
            $encoding->writeTo($this);
            $this->assertIdentical($this->_content_so_far, $content, $message);
        }
        
        function testEmpty() {
            $encoding = &new SimpleFormEncoding();
            $this->assertIdentical($encoding->getValue('a'), false);
            $this->assertIdentical($encoding->getKeys(), array());
            $this->assertWritten($encoding, '');
        }
        
        function testPrefilled() {
            $encoding = &new SimpleFormEncoding(array('a' => 'aaa'));
            $this->assertIdentical($encoding->getValue('a'), 'aaa');
            $this->assertIdentical($encoding->getKeys(), array('a'));
            $this->assertWritten($encoding, 'a=aaa');
        }
        
        function testPrefilledWithObject() {
            $encoding = &new SimpleFormEncoding(new SimpleFormEncoding(array('a' => 'aaa')));
            $this->assertIdentical($encoding->getValue('a'), 'aaa');
            $this->assertIdentical($encoding->getKeys(), array('a'));
            $this->assertWritten($encoding, 'a=aaa');
        }
        
        function testMultiplePrefilled() {
            $encoding = &new SimpleFormEncoding(array('a' => array('a1', 'a2')));
            $this->assertIdentical($encoding->getValue('a'), array('a1', 'a2'));
            $this->assertWritten($encoding, 'a=a1&a=a2');
        }
        
        function testSingleParameter() {
            $encoding = &new SimpleFormEncoding();
            $encoding->add('a', 'Hello');
            $this->assertEqual($encoding->getValue('a'), 'Hello');
            $this->assertWritten($encoding, 'a=Hello');
        }
        
        function testFalseParameter() {
            $encoding = &new SimpleFormEncoding();
            $encoding->add('a', false);
            $this->assertEqual($encoding->getValue('a'), false);
            $this->assertWritten($encoding, '');
        }
        
        function testUrlEncoding() {
            $encoding = &new SimpleFormEncoding();
            $encoding->add('a', 'Hello there!');
            $this->assertWritten($encoding, 'a=Hello+there%21');
        }
        
        function testMultipleParameter() {
            $encoding = &new SimpleFormEncoding();
            $encoding->add('a', 'Hello');
            $encoding->add('b', 'Goodbye');
            $this->assertWritten($encoding, 'a=Hello&b=Goodbye');
        }
        
        function testEmptyParameters() {
            $encoding = &new SimpleFormEncoding();
            $encoding->add('a', '');
            $encoding->add('b', '');
            $this->assertWritten($encoding, 'a=&b=');
        }
        
        function testRepeatedParameter() {
            $encoding = &new SimpleFormEncoding();
            $encoding->add('a', 'Hello');
            $encoding->add('a', 'Goodbye');
            $this->assertIdentical($encoding->getValue('a'), array('Hello', 'Goodbye'));
            $this->assertWritten($encoding, 'a=Hello&a=Goodbye');
        }
        
        function testAddingLists() {
            $encoding = &new SimpleFormEncoding();
            $encoding->add('a', array('Hello', 'Goodbye'));
            $this->assertIdentical($encoding->getValue('a'), array('Hello', 'Goodbye'));
            $this->assertWritten($encoding, 'a=Hello&a=Goodbye');
        }
        
        function testMergeInHash() {
            $encoding = &new SimpleFormEncoding(array('a' => 'A1', 'b' => 'B'));
            $encoding->merge(array('a' => 'A2'));
            $this->assertIdentical($encoding->getValue('a'), array('A1', 'A2'));
            $this->assertIdentical($encoding->getValue('b'), 'B');
        }
        
        function testMergeInObject() {
            $encoding = &new SimpleFormEncoding(array('a' => 'A1', 'b' => 'B'));
            $encoding->merge(new SimpleFormEncoding(array('a' => 'A2')));
            $this->assertIdentical($encoding->getValue('a'), array('A1', 'A2'));
            $this->assertIdentical($encoding->getValue('b'), 'B');
        }
        
        function testPrefilledMultipart() {
            $encoding = &new SimpleMultipartFormEncoding(array('a' => 'aaa'), 'boundary');
            $this->assertIdentical($encoding->getValue('a'), 'aaa');
            $this->assertIdentical($encoding->getKeys(), array('a'));
            $this->assertwritten($encoding,
                    "--boundary\r\n" .
                    "Content-Disposition: form-data; name=\"a\"\r\n" .
                    "\r\n" .
                    "aaa\r\n" .
                    "--boundary--\r\n");
        }
    }
    
    class TestOfFormHeaders extends UnitTestCase {
        
        function testEmptyEncodingWritesZeroContentLength() {
            $socket = &new MockSimpleSocket($this);
            $socket->expectArgumentsAt(0, 'write', array("Content-Length: 0\r\n"));
            $socket->expectArgumentsAt(1, 'write', array("Content-Type: application/x-www-form-urlencoded\r\n"));
            $encoding = &new SimpleFormEncoding();
            $encoding->writeHeadersTo($socket);
            $socket->tally();
        }
        
        function testEmptyMultipartEncodingWritesEndBoundaryContentLength() {
            $socket = &new MockSimpleSocket($this);
            $socket->expectArgumentsAt(0, 'write', array("Content-Length: 14\r\n"));
            $socket->expectArgumentsAt(1, 'write', array("Content-Type: multipart/form-data, boundary=boundary\r\n"));
            $encoding = &new SimpleMultipartFormEncoding(array(), 'boundary');
            $encoding->writeHeadersTo($socket);
            $socket->tally();
        }
    }
?>