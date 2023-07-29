<?php

require_once __DIR__.'/cookies.php';
require_once __DIR__.'/http.php';
require_once __DIR__.'/encoding.php';
require_once __DIR__.'/authentication.php';

if (!defined('DEFAULT_MAX_REDIRECTS')) {
    define('DEFAULT_MAX_REDIRECTS', 3);
}
if (!defined('DEFAULT_CONNECTION_TIMEOUT')) {
    define('DEFAULT_CONNECTION_TIMEOUT', 15);
}

/**
 * Fetches web pages whilst keeping track of cookies and authentication.
 */
class SimpleUserAgent
{
    /** @var array */
    private $additional_headers = [];
    private $authenticator;
    private $connection_timeout = DEFAULT_CONNECTION_TIMEOUT;
    private $cookie_jar;
    private $cookies_enabled = true;
    private $http_referer;
    private $proxy = false;
    private $proxy_password = false;
    private $proxy_username = false;
    /** @var int */
    private $max_redirects = DEFAULT_MAX_REDIRECTS;

    /**
     * Starts with no cookies, realms or proxies.
     */
    public function __construct()
    {
        $this->cookie_jar = new SimpleCookieJar();
        $this->authenticator = new SimpleAuthenticator();
    }

    /**
     * Removes expired and temporary cookies as if the browser was closed and re-opened.
     * Authorisation has to be obtained again as well.
     *
     * @param string|int $date Time when session restarted. If omitted then all persistent
     *                             cookies are kept.
     *
     * @return void
     */
    public function restart($date = false)
    {
        $this->cookie_jar->restartSession($date);
        $this->authenticator->restartSession();
        $this->http_referer = null;
    }

    /**
     * Adds a header to every fetch.
     *
     * @param string $header header line to add to every request until cleared
     *
     * @return void
     */
    public function addHeader($header)
    {
        $this->additional_headers[] = $header;
    }

    /**
     * Sets the referrer to send with the request, as long as
     * it is not set explicitely via {@link addHeader()}.
     *
     * @param string $referer referer URI to add to every request until cleared
     *
     * @return void
     */
    public function setReferer($referer)
    {
        $this->http_referer = $referer;
    }

    /**
     * Ages the cookies by the specified time.
     *
     * @param int $interval amount in seconds
     *
     * @return void
     */
    public function ageCookies($interval)
    {
        $this->cookie_jar->agePrematurely($interval);
    }

    /**
     * Sets an additional cookie. If a cookie has the same name and path it is replaced.
     *
     * @param string            $name   cookie key
     * @param string            $value  value of cookie
     * @param string|false      $host   host upon which the cookie is valid
     * @param string            $path   cookie path if not host wide
     * @param bool|int|string   $expiry expiry date
     *
     * @return void
     */
    public function setCookie($name, $value, $host = false, $path = '/', $expiry = false)
    {
        $this->cookie_jar->setCookie($name, $value, $host, $path, $expiry);
    }

    /**
     * Get current list of cookies.
     *
     * @return array
     */
    public function getCookies()
    {
        $lstCookies = $this->cookie_jar->getCookies();
        $aCookies = [];
        foreach ($lstCookies as $oCookies) {
            $aCookies[] = [
                'name' => $oCookies->getName(),
                'value' => $oCookies->getValue(),
                'host' => $oCookies->getHost(),
                'path' => $oCookies->getPath(),
                'expiry' => $oCookies->getExpiry(),
            ];
        }

        return $aCookies;
    }

    /**
     * Import a list of cookies.
     *
     * @param array $lstCookies
     *
     * @return void
     */
    public function setCookies(array $lstCookies)
    {
        foreach ($lstCookies as $aCookies) {
            $this->cookie_jar->setCookie(
                $aCookies['name'],
                $aCookies['value'],
                $aCookies['host'],
                $aCookies['path'],
                $aCookies['expiry']
            );
        }
    }

    /**
     * Reads the most specific cookie value from the browser cookies.
     *
     * @param string $host host to search
     * @param string $path applicable path
     * @param string $name name of cookie to read
     *
     * @return string false if not present, else the value as a string
     */
    public function getCookieValue($host, $path, $name)
    {
        return $this->cookie_jar->getCookieValue($host, $path, $name);
    }

    /**
     * Reads the current cookies within the base URL.
     *
     * @param string    $name key of cookie to find
     * @param SimpleUrl $base base URL to search from
     *
     * @return string|bool Null if there is no base URL, false if the cookie is not set
     */
    public function getBaseCookieValue($name, $base)
    {
        if (!$base) {
            return;
        }

        return $this->getCookieValue($base->getHost(), $base->getPath(), $name);
    }

    /**
     * Switches off cookie sending and recieving.
     *
     * @return void
     */
    public function ignoreCookies()
    {
        $this->cookies_enabled = false;
    }

    /**
     * Switches back on the cookie sending and recieving.
     *
     * @return void
     */
    public function useCookies()
    {
        $this->cookies_enabled = true;
    }

    /**
     * Sets the socket timeout for opening a connection.
     *
     * @param int $timeout maximum time in seconds
     *
     * @return void
     */
    public function setConnectionTimeout($timeout)
    {
        $this->connection_timeout = $timeout;
    }

    /**
     * Sets the maximum number of redirects before a page will be loaded anyway.
     *
     * @param int $max most hops allowed
     *
     * @return void
     */
    public function setMaximumRedirects($max)
    {
        $this->max_redirects = $max;
    }

    /**
     * Sets proxy to use on all requests for when testing from behind a firewall.
     * Set URL to false to disable.
     *
     * @param string $proxy    proxy URL
     * @param string $username proxy username for authentication
     * @param string $password proxy password for authentication
     *
     * @return void
     */
    public function useProxy($proxy, $username, $password)
    {
        if (!$proxy) {
            $this->proxy = false;

            return;
        }
        if ((0 != strncmp($proxy, 'http://', 7)) && (0 != strncmp($proxy, 'https://', 8))) {
            $proxy = 'http://'.$proxy;
        }
        $this->proxy = new SimpleUrl($proxy);
        $this->proxy_username = $username;
        $this->proxy_password = $password;
    }

    /**
     * Test to see if the redirect limit is passed.
     *
     * @param int $redirects count so far
     *
     * @return bool true if over
     */
    protected function isTooManyRedirects($redirects)
    {
        return $redirects > $this->max_redirects;
    }

    /**
     * Sets the identity for the current realm.
     *
     * @param string $host     host to which realm applies
     * @param string $realm    full name of realm
     * @param string $username username for realm
     * @param string $password password for realm
     *
     * @return void
     */
    public function setIdentity($host, $realm, $username, $password)
    {
        $this->authenticator->setIdentityForRealm($host, $realm, $username, $password);
    }

    /**
     * Fetches a URL as a response object. Will keep trying if redirected.
     * It will also collect authentication realm information.
     *
     * @param string|SimpleUrl $url      Target to fetch
     * @param SimpleEncoding   $encoding additional parameters for request
     *
     * @return SimpleHttpResponse hopefully the target page
     */
    public function fetchResponse($url, $encoding)
    {
        if (!in_array($encoding->getMethod(), ['POST', 'PUT'])) {
            $url->addRequestParameters($encoding);
            $encoding->clear();
        }
        $response = $this->fetchWhileRedirected($url, $encoding);
        if ($headers = $response->getHeaders()) {
            if ($headers->isChallenge()) {
                $this->authenticator->addRealm(
                    $url,
                    $headers->getAuthentication(),
                    $headers->getRealm()
                );
            }
        }

        return $response;
    }

    /**
     * Fetches the page until no longer redirected or until the redirect limit runs out.
     *
     * @param SimpleUrl          $url      target to fetch
     * @param SimpelFormEncoding $encoding additional parameters for request
     *
     * @return SimpleHttpResponse hopefully the target page
     */
    protected function fetchWhileRedirected($url, $encoding)
    {
        $redirects = 0;
        do {
            $response = $this->fetch($url, $encoding);
            if ($response->isError()) {
                return $response;
            }
            $headers = $response->getHeaders();
            if ($this->cookies_enabled) {
                $headers->writeCookiesToJar($this->cookie_jar, $url);
            }
            if (!$headers->isRedirect()) {
                break;
            }
            $location = new SimpleUrl($headers->getLocation());
            $url = $location->makeAbsolute($url);
            $encoding = new SimpleGetEncoding();
        } while (!$this->isTooManyRedirects(++$redirects));

        return $response;
    }

    /**
     * Actually make the web request.
     *
     * @param SimpleUrl          $url      target to fetch
     * @param SimpleFormEncoding $encoding additional parameters for request
     *
     * @return SimpleHttpResponse headers and hopefully content
     */
    protected function fetch($url, $encoding)
    {
        $request = $this->createRequest($url, $encoding);

        return $request->fetch($this->connection_timeout);
    }

    /**
     * Creates a full page request.
     *
     * @param SimpleUrl          $url      target to fetch as url object
     * @param SimpleFormEncoding $encoding POST/GET parameters
     *
     * @return SimpleHttpRequest new request
     */
    protected function createRequest($url, $encoding)
    {
        $request = $this->createHttpRequest($url, $encoding);
        $this->addAdditionalHeaders($request);
        if ($this->cookies_enabled) {
            $request->readCookiesFromJar($this->cookie_jar, $url);
        }
        $this->authenticator->addHeaders($request, $url);

        // Add referer header, if not set explicitly
        if ($this->http_referer) {
            $headers = $request->getHeaders();
            if (is_array($headers)) {
                $custom_referer = false;
                foreach ($headers as $header) {
                    if (preg_match('~^referer:~i', $header)) {
                        $custom_referer = true;
                        break;
                    }
                }
                if (!$custom_referer) {
                    $request->addHeaderLine('Referer: '.$this->http_referer);
                }
            }
        }

        return $request;
    }

    /**
     * Builds the appropriate HTTP request object.
     *
     * @param SimpleUrl $url target to fetch as url object
     * @param SimpleFormEncoding $encoding Encoding
     *
     * @return SimpleHttpRequest new request object
     */
    protected function createHttpRequest($url, $encoding)
    {
        return new SimpleHttpRequest($this->createRoute($url), $encoding);
    }

    /**
     * Sets up either a direct route or via a proxy.
     *
     * @param SimpleUrl $url target to fetch as url object
     *
     * @return SimpleRoute route to take to fetch URL
     */
    protected function createRoute($url)
    {
        if ($this->proxy) {
            return new SimpleProxyRoute(
                $url,
                $this->proxy,
                $this->proxy_username,
                $this->proxy_password
            );
        }

        return new SimpleRoute($url);
    }

    /**
     * Adds additional manual headers.
     *
     * @param SimpleHttpRequest $request outgoing request
     *
     * @return void
     */
    protected function addAdditionalHeaders(&$request)
    {
        foreach ($this->additional_headers as $header) {
            $request->addHeaderLine($header);
        }
    }
}
