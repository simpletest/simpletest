<?php

require_once __DIR__.'/compatibility.php';

/**
 * Stashes an error for later.
 *
 * @todo  Useful for constructors until PHP gets exceptions.
 */
class SimpleStickyError
{
    /** @var string */
    private $error = 'Constructor not chained';

    /**
     * Sets the error to empty.
     */
    public function __construct()
    {
        $this->clearError();
    }

    /**
     * Test for an outstanding error.
     *
     * @return bool true if there is an error
     */
    public function isError()
    {
        return '' != $this->error;
    }

    /**
     * Accessor for an outstanding error.
     *
     * @return string empty string if no error otherwise the error message
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Sets the internal error.
     *
     * @param string $error Error Message
     *
     * @return void
     */
    public function setError($error)
    {
        $this->error = $error;
    }

    /**
     * Resets the error state to no error.
     *
     * @return void
     */
    public function clearError()
    {
        $this->setError('');
    }
}

/**
 * Wrapper for a file socket.
 */
class SimpleFileSocket extends SimpleStickyError
{
    /** @var false|resource */
    private $handle;
    /** @var bool */
    private $is_open = false;
    /** @var string */
    private $sent = '';
    /** @var int|null */
    private $block_size;

    /**
     * Opens a socket for reading and writing.
     *
     * @param SimpleUrl $file       target URI to fetch
     * @param int       $block_size size of chunk to read
     */
    public function __construct($file, $block_size = 1024)
    {
        parent::__construct();
        if (!($this->handle = $this->openFile($file, $error))) {
            $file_string = $file->asString();
            $this->setError("Cannot open [$file_string] with [$error]");

            return;
        }
        $this->is_open = true;
        $this->block_size = $block_size;
    }

    /**
     * Writes some data to the socket and saves alocal copy.
     *
     * @param string $message string to send to socket
     *
     * @return bool true if successful
     */
    public function write($message)
    {
        return true;
    }

    /**
     * Reads data from the socket.
     *
     * @todo The error suppression is a workaround for PHP4
     * always throwing a warning with a secure socket.
     *
     * @return false|string False on error. The read string.
     */
    public function read()
    {
        $raw = @fread($this->handle, $this->block_size);
        if (false === $raw) {
            $this->setError('Cannot read from socket');
            $this->close();
        }

        return $raw;
    }

    /**
     * Accessor for socket open state.
     *
     * @return bool true if open
     */
    public function isOpen()
    {
        return $this->is_open;
    }

    /**
     * Closes the socket preventing further reads. Cannot be reopened once closed.
     *
     * @return bool true if successful
     */
    public function close()
    {
        if (!$this->is_open) {
            return false;
        }
        $this->is_open = false;

        return fclose($this->handle);
    }

    /**
     * Accessor for content so far.
     *
     * @return string bytes sent only
     */
    public function getSent()
    {
        return $this->sent;
    }

    /**
     * Actually opens the low level socket.
     *
     * @param SimpleUrl $file  simpleUrl file target
     * @param string    $error recipient of error message
     *
     * @return false|resource
     */
    protected function openFile($file, &$error)
    {
        return @fopen($file->asString(), 'r');
    }
}

/**
 * Wrapper for TCP/IP socket.
 */
class SimpleSocket extends SimpleStickyError
{
    /** @var false|resource */
    private $handle;
    /** @var bool */
    private $is_open = false;
    /** @var string */
    private $sent = '';
    /** @var int|null */
    private $block_size;

    /**
     * Opens a socket for reading and writing.
     *
     * @param string $host       hostname to send request to
     * @param int    $port       port on remote machine to open
     * @param int    $timeout    connection timeout in seconds
     * @param int    $block_size size of chunk to read
     */
    public function __construct($host, $port, $timeout, $block_size = 255)
    {
        parent::__construct();
        if (!($this->handle = $this->openSocket($host, $port, $error_number, $error, $timeout))) {
            $this->setError("Cannot open [$host:$port] with [$error] within [$timeout] seconds");

            return;
        }
        $this->is_open = true;
        $this->block_size = $block_size;
        stream_set_timeout($this->handle, $timeout, 0);
    }

    /**
     * Writes some data to the socket and saves alocal copy.
     *
     * @param string $message string to send to socket
     *
     * @return bool true if successful
     */
    public function write($message)
    {
        if ($this->isError() || !$this->isOpen()) {
            return false;
        }
        $count = fwrite($this->handle, $message);
        if (!$count) {
            if (false === $count) {
                $this->setError('Cannot write to socket');
                $this->close();
            }

            return false;
        }
        fflush($this->handle);
        $this->sent .= $message;

        return true;
    }

    /**
     * Reads data from the socket.
     *
     * @return false|string Incoming bytes. False on error.
     */
    public function read()
    {
        if ($this->isError() || !$this->isOpen()) {
            return false;
        }
        $raw = @fread($this->handle, $this->block_size);
        if (false === $raw) {
            $this->setError('Cannot read from socket');
            $this->close();
        }

        return $raw;
    }

    /**
     *    Accessor for socket open state.
     *
     *    @return bool           true if open
     */
    public function isOpen()
    {
        return $this->is_open;
    }

    /**
     * Closes the socket preventing further reads. Cannot be reopened once closed.
     *
     * @return bool true if successful
     */
    public function close()
    {
        if (!$this->is_open) {
            return false;
        }
        $this->is_open = false;

        return fclose($this->handle);
    }

    /**
     * Accessor for content so far.
     *
     * @return string bytes sent only
     */
    public function getSent()
    {
        return $this->sent;
    }

    /**
     * Actually opens the low level socket.
     *
     * @param string $host         host to connect to
     * @param int    $port         port on host
     * @param int    $error_number recipient of error code
     * @param string $error        recipoent of error message
     * @param int    $timeout      maximum time to wait for connection
     *
     * @return false|resource
     */
    protected function openSocket($host, $port, &$error_number, &$error, $timeout)
    {
        return @fsockopen($host, $port, $error_number, $error, $timeout);
    }
}

/**
 * Wrapper for TCP/IP socket over TLS.
 */
class SimpleSecureSocket extends SimpleSocket
{
    /**
     * Opens a secure socket for reading and writing.
     *
     * @param string $host    hostname to send request to
     * @param int    $port    port on remote machine to open
     * @param int    $timeout connection timeout in seconds
     */
    public function __construct($host, $port, $timeout)
    {
        parent::__construct($host, $port, $timeout);
    }

    /**
     * Actually opens the low level socket.
     *
     * @param string $host         host to connect to
     * @param int    $port         port on host
     * @param int    $error_number recipient of error code
     * @param string $error        recipient of error message
     * @param int    $timeout      maximum time to wait for connection
     */
    public function openSocket($host, $port, &$error_number, &$error, $timeout)
    {
        return parent::openSocket("tls://$host", $port, $error_number, $error, $timeout);
    }
}
