<?php

declare(strict_types=1);

require_once __DIR__ . '/compatibility.php';

/**
 * Stashes an error for later.
 *
 * @todo  Useful for constructors until PHP gets exceptions.
 */
class SimpleStickyError
{
    /** @var string */
    private $error = 'Unknown Error';

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
     */
    public function setError($error): void
    {
        $this->error = $error;
    }

    /**
     * Resets the error state to no error.
     */
    public function clearError(): void
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

    /** @var int */
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

        $this->handle = $this->openFile($file);

        if ($this->handle === false) {
            $file_string = $file->asString();

            /** @var array */
            $last_error         = \error_get_last();
            $last_error_message = $last_error['message'];
            $this->setError("Cannot open [{$file_string}] with [{$last_error_message}]");

            return;
        }

        $this->is_open    = true;
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
        $raw = @\fread($this->handle, $this->block_size);

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

        return \fclose($this->handle);
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
     * @param SimpleUrl $file
     *
     * @return false|resource
     */
    protected function openFile($file)
    {
        return @\fopen($file->asString(), 'r');
    }
}

/**
 * Wrapper for TCP/IP socket.
 */
class SimpleSocket extends SimpleStickyError
{
    /** @var mixed|resource */
    private $handle;

    /** @var bool */
    private $is_open = false;

    /** @var string */
    private $sent = '';

    /** @var int */
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
            $this->setError("Cannot open [{$host}:{$port}] with [{$error}] within [{$timeout}] seconds");

            return;
        }
        $this->is_open    = true;
        $this->block_size = $block_size;
        \stream_set_timeout($this->handle, $timeout, 0);
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
        $count = \fwrite($this->handle, $message);

        if ($count === 0 || $count === false) {
            if (false === $count) {
                $this->setError('Cannot write to socket');
                $this->close();
            }

            return false;
        }
        \fflush($this->handle);
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
        $raw = @\fread($this->handle, $this->block_size);

        if (false === $raw) {
            $this->setError('Cannot read from socket');
            $this->close();
        }

        return $raw;
    }

    /**
     *    Accessor for socket open state.
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

        return \fclose($this->handle);
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
        return @\fsockopen($host, $port, $error_number, $error, $timeout);
    }
}

/**
 * Wrapper for TCP/IP socket over TLS.
 */
class SimpleSecureSocket extends SimpleSocket
{
    /**
     * @var string The transport protocol to use
     */
    private $transport = 'tlsv1.2';

    /**
     * @var array config for stream_context_create()
     */
    private $stream_config = [
        'ssl' => [
            'cafile'            => '/etc/ssl/certs/ca-certificates.crt',
            'verify_peer'       => true,
            'verify_peer_name'  => true,
            'capture_peer_cert' => true,
        ],
    ];

    /**
     * Opens a secure socket for reading and writing.
     *
     * @param string $host      hostname to send request to
     * @param int    $port      port on remote machine to open
     * @param int    $timeout   connection timeout in seconds
     * @param string $transport transport protocol to use
     */
    public function __construct($host, $port, $timeout, $transport = 'tlsv1.2')
    {
        parent::__construct($host, $port, $timeout);
        $this->transport = $transport;
    }

    /**
     * Sets the transport protocol.
     *
     * @param string $transport transport protocol to use
     *
     * @throws InvalidArgumentException if the transport protocol is not supported
     */
    public function setTransport($transport): void
    {
        $possibleTransportProtocols = \stream_get_transports();

        if (!\in_array($transport, $possibleTransportProtocols, true)) {
            throw new InvalidArgumentException("Transport protocol '{$transport}' is not supported.");
        }
        $this->transport = $transport;
    }

    public function disableConnectionVerification(): void
    {
        $this->stream_config['ssl']['verify_peer']       = false;
        $this->stream_config['ssl']['verify_peer_name']  = false;
        $this->stream_config['ssl']['verify_host']       = false;
        $this->stream_config['ssl']['allow_self_signed'] = true;
        $this->stream_config['ssl']['sni_enabled']       = true;
        // $this->stream_config['ssl']['local_cert'] =
        // $this->stream_config['ssl']['local_pk'] =
    }

    /**
     * Set the stream config for stream_context_create().
     *
     * @return array
     */
    public function setStreamConfig(array $stream_config): void
    {
        $this->stream_config = $stream_config;
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
        $context = \stream_context_create($this->stream_config);

        $r = \stream_socket_client("{$this->transport}://{$host}:{$port}", $error_number, $error, DEFAULT_CONNECTION_TIMEOUT, STREAM_CLIENT_CONNECT, $context);

        if (!$r) {
            throw new Exception("Cannot connect to server '{$host}': {$error_number} {$error}");
        }

        return $r;
        // return parent::openSocket("{$this->transport}://{$host}", $port, $error_number, $error, $timeout);
    }
}
