<?php declare(strict_types=1);

require_once __DIR__ . '/simpletest.php';

require_once __DIR__ . '/http.php';

require_once __DIR__ . '/encoding.php';

require_once __DIR__ . '/page.php';

require_once __DIR__ . '/php_parser.php';

require_once __DIR__ . '/tidy_parser.php';

require_once __DIR__ . '/selector.php';

require_once __DIR__ . '/user_agent.php';

if (!SimpleTest::getParsers()) {
    SimpleTest::setParsers([new SimpleTidyPageBuilder, new SimplePhpPageBuilder]);
    // SimpleTest::setParsers([new SimplePhpPageBuilder()]);
}

/**
 * Browser history list.
 */
class SimpleBrowserHistory
{
    /** @var array */
    private $sequence = [];

    /** @var int|mixed */
    private $position = -1;

    /**
     * Adds a successfully fetched page to the history.
     *
     * @param SimpleUrl      $url        URL of fetch
     * @param SimpleEncoding $parameters any post data with the fetch
     */
    public function recordEntry($url, $parameters): void
    {
        $this->dropFuture();

        $this->sequence[] = ['url' => $url, 'parameters' => $parameters];
        $this->position++;
    }

    /**
     * Last fully qualified URL for current history position.
     *
     * @return false|SimpleUrl URL for this position
     */
    public function getUrl()
    {
        if ($this->isEmpty()) {
            return false;
        }

        return $this->sequence[$this->position]['url'];
    }

    /**
     * Parameters of last fetch from current history position.
     *
     * @return array|false post parameters
     */
    public function getParameters()
    {
        if ($this->isEmpty()) {
            return false;
        }

        return $this->sequence[$this->position]['parameters'];
    }

    /**
     * Step back one place in the history. Stops at the first page.
     *
     * @return bool true if any previous entries
     */
    public function back()
    {
        if ($this->isEmpty() || $this->atBeginning()) {
            return false;
        }
        $this->position--;

        return true;
    }

    /**
     * Step forward one place. If already at the latest entry then nothing will happen.
     *
     * @return bool true if any future entries
     */
    public function forward()
    {
        if ($this->isEmpty() || $this->atEnd()) {
            return false;
        }
        $this->position++;

        return true;
    }

    /**
     * Test for no entries yet.
     *
     * @return bool true if empty
     */
    protected function isEmpty()
    {
        return -1 == $this->position;
    }

    /**
     * Test for being at the beginning.
     *
     * @return bool true if first
     */
    protected function atBeginning()
    {
        return (0 == $this->position) && !$this->isEmpty();
    }

    /**
     * Test for being at the last entry.
     *
     * @return bool true if last
     */
    protected function atEnd()
    {
        return ($this->position + 1 >= \count($this->sequence)) && !$this->isEmpty();
    }

    /**
     * Ditches all future entries beyond the current point.
     */
    protected function dropFuture(): void
    {
        if ($this->isEmpty()) {
            return;
        }

        while (!$this->atEnd()) {
            \array_pop($this->sequence);
        }
    }
}

/**
 * Simulated web browser. This is an aggregate of the user agent,
 * the HTML parsing, request history and the last header set.
 */
class SimpleBrowser
{
    /** @var SimpleUserAgent */
    private $user_agent;

    /** @var SimplePage */
    private $page;

    /** @var SimpleBrowserHistory */
    private $history;

    /** @var bool */
    private $ignore_frames = false;

    /** @var int */
    private $maximum_nested_frames = 1;

    /** @var mixed */
    private $parser;

    /**
     * Starts with a fresh browser with no cookie or any other state information.
     * The exception is that a default proxy will be set up if specified in the options.
     */
    public function __construct()
    {
        $this->user_agent = $this->createUserAgent();
        $this->user_agent->useProxy(
            SimpleTest::getDefaultProxy(),
            SimpleTest::getDefaultProxyUsername(),
            SimpleTest::getDefaultProxyPassword(),
        );
        $this->page    = new SimplePage;
        $this->history = $this->createHistory();
    }

    /**
     * Override the default HTML parser, allowing parsers to be plugged in.
     *
     * @param mixed A parser object instance
     */
    public function setParser($parser): void
    {
        $this->parser = $parser;
    }

    /**
     * Switches off cookie sending and recieving.
     */
    public function ignoreCookies(): void
    {
        $this->user_agent->ignoreCookies();
    }

    /**
     * Switches back on the cookie sending and recieving.
     */
    public function useCookies(): void
    {
        $this->user_agent->useCookies();
    }

    /**
     * Get current list of cookies.
     *
     * @return array
     */
    public function getCookies()
    {
        return $this->user_agent->getCookies();
    }

    /**
     * Import a list of cookies.
     *
     * @return array
     */
    public function setCookies(array $lstCookies)
    {
        return $this->user_agent->setCookies($lstCookies);
    }

    /**
     * Removes expired and temporary cookies as if the browser was closed and re-opened.
     *
     * @param int|string $date Time when session restarted. If omitted then all persistent
     *                         cookies are kept.
     */
    public function restart($date = false): void
    {
        $this->user_agent->restart($date);
    }

    /**
     * Adds a header to every fetch.
     *
     * @param string $header header line to add to every request until cleared
     */
    public function addHeader($header): void
    {
        $this->user_agent->addHeader($header);
    }

    /**
     * Ages the cookies by the specified time.
     *
     * @param int $interval amount in seconds
     */
    public function ageCookies($interval): void
    {
        $this->user_agent->ageCookies($interval);
    }

    /**
     * Sets an additional cookie.
     * If a cookie has the same name and path it is replaced.
     *
     * @param string $name   cookie key
     * @param string $value  value of cookie
     * @param string $host   host upon which the cookie is valid
     * @param string $path   cookie path if not host wide
     * @param string $expiry expiry date
     */
    public function setCookie($name, $value, $host = false, $path = '/', $expiry = false): void
    {
        $this->user_agent->setCookie($name, $value, $host, $path, $expiry);
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
        return $this->user_agent->getCookieValue($host, $path, $name);
    }

    /**
     * Reads the current cookies for the current URL.
     *
     * @param string $name key of cookie to find
     *
     * @return string null if there is no current URL, false if the cookie is not set
     */
    public function getCurrentCookieValue($name)
    {
        return $this->user_agent->getBaseCookieValue($name, $this->page->getUrl());
    }

    /**
     * Sets the maximum number of redirects before a page will be loaded anyway.
     *
     * @param int $max most hops allowed
     */
    public function setMaximumRedirects($max): void
    {
        $this->user_agent->setMaximumRedirects($max);
    }

    /**
     * Sets the socket timeout for opening a connection.
     *
     * @param int $timeout maximum time in seconds
     */
    public function setConnectionTimeout($timeout): void
    {
        $this->user_agent->setConnectionTimeout($timeout);
    }

    /**
     * Sets proxy to use on all requests for when testing from behind a firewall.
     * Set URL to false to disable.
     *
     * @param string $proxy    proxy URL
     * @param string $username proxy username for authentication
     * @param string $password proxy password for authentication
     */
    public function useProxy($proxy, $username = false, $password = false): void
    {
        $this->user_agent->useProxy($proxy, $username, $password);
    }

    /**
     * Fetches the page content with a HEAD request.
     * Will affect cookies, but will not change the base URL.
     *
     * @param string/SimpleUrl        $url        Target to fetch as string
     * @param hash/SimpleHeadEncoding $parameters Additional parameters for HEAD request
     *
     * @return bool true if successful
     */
    public function head($url, $parameters = false)
    {
        if (!\is_object($url)) {
            $url = new SimpleUrl($url);
        }

        if ($this->getUrl()) {
            $url = $url->makeAbsolute($this->getUrl());
        }
        $response   = $this->user_agent->fetchResponse($url, new SimpleHeadEncoding($parameters));
        $this->page = new SimplePage($response);

        return !$response->isError();
    }

    /**
     * Fetches the page content with a simple GET request.
     *
     * @param string/SimpleUrl        $url        Target to fetch
     * @param hash/SimpleFormEncoding $parameters Additional parameters for GET request
     *
     * @return string content of page or false
     */
    public function get($url, $parameters = false)
    {
        if (!\is_object($url)) {
            $url = new SimpleUrl($url);
        }

        if ($this->getUrl()) {
            $url = $url->makeAbsolute($this->getUrl());
        }

        return $this->load($url, new SimpleGetEncoding($parameters));
    }

    /**
     * Fetches the page content with a POST request.
     *
     * @param string $content_type MIME Content-Type of the request body
     * @param string/SimpleUrl        $url          Target to fetch as string
     * @param hash/SimpleFormEncoding $parameters   POST parameters or request body
     *
     * @return string content of page
     */
    public function post($url, $parameters = false, $content_type = false)
    {
        if (!\is_object($url)) {
            $url = new SimpleUrl($url);
        }

        if ($this->getUrl()) {
            $url = $url->makeAbsolute($this->getUrl());
        }

        return $this->load($url, new SimplePostEncoding($parameters, $content_type));
    }

    /**
     * Fetches the page content with a PUT request.
     *
     * @param string $content_type MIME Content-Type of the request body
     * @param string/SimpleUrl        $url          Target to fetch as string
     * @param hash/SimpleFormEncoding $parameters   PUT request body
     *
     * @return string content of page
     */
    public function put($url, $parameters = false, $content_type = false)
    {
        if (!\is_object($url)) {
            $url = new SimpleUrl($url);
        }

        return $this->load($url, new SimplePutEncoding($parameters, $content_type));
    }

    /**
     * Sends a DELETE request and fetches the response.
     *
     * @param string/SimpleUrl        $url        Target to fetch
     * @param hash/SimpleFormEncoding $parameters Additional parameters for DELETE request
     *
     * @return string content of page or false
     */
    public function delete($url, $parameters = false)
    {
        if (!\is_object($url)) {
            $url = new SimpleUrl($url);
        }

        return $this->load($url, new SimpleDeleteEncoding($parameters));
    }

    /**
     * Equivalent to hitting the retry button on the browser.
     * Will attempt to repeat the page fetch.
     * If there is no history to repeat it will give false.
     *
     * @return bool|string Content if fetch succeeded else false
     */
    public function retry()
    {
        if ($url = $this->history->getUrl()) {
            $this->page = $this->fetch($url, $this->history->getParameters());

            return $this->page->getRaw();
        }

        return false;
    }

    /**
     * Equivalent to hitting the back button on the browser.
     * The browser history is unchanged on failure.
     * The page content is refetched as there is no concept of content caching in SimpleTest.
     *
     * @return bool True if history entry and fetch succeeded
     */
    public function back()
    {
        if (!$this->history->back()) {
            return false;
        }
        $content = $this->retry();

        if (!$content) {
            $this->history->forward();
        }

        return $content;
    }

    /**
     * Equivalent to hitting the forward button on the browser.
     * The browser history is unchanged on failure.
     * The page content is refetched as there is no concept of content caching in SimpleTest.
     *
     * @return bool True if history entry and fetch succeeded
     */
    public function forward()
    {
        if (!$this->history->forward()) {
            return false;
        }
        $content = $this->retry();

        if (!$content) {
            $this->history->back();
        }

        return $content;
    }

    /**
     * Retries a request after setting the authentication for the current realm.
     *
     * @param string $username username for realm
     * @param string $password password for realm
     *
     * @return bool True if successful fetch. Note that authentication may still have failed.
     */
    public function authenticate($username, $password)
    {
        if (!$this->page->getRealm()) {
            return false;
        }
        $url = $this->page->getUrl();

        if (!$url) {
            return false;
        }
        $this->user_agent->setIdentity(
            $url->getHost(),
            $this->page->getRealm(),
            $username,
            $password,
        );

        return $this->retry();
    }

    /**
     * Accessor for last error.
     *
     * @return string error from last response
     */
    public function getTransportError()
    {
        return $this->page->getTransportError();
    }

    /**
     * Accessor for current MIME type.
     *
     * @return string MIME type as string; e.g. 'text/html'
     */
    public function getMimeType()
    {
        return $this->page->getMimeType();
    }

    /**
     * Accessor for last response code.
     *
     * @return int last HTTP response code received
     */
    public function getResponseCode()
    {
        return $this->page->getResponseCode();
    }

    /**
     * Accessor for last Authentication type. Only valid straight after a challenge (401).
     *
     * @return string description of challenge type
     */
    public function getAuthentication()
    {
        return $this->page->getAuthentication();
    }

    /**
     * Accessor for last Authentication realm. Only valid straight after a challenge (401).
     *
     * @return string name of security realm
     */
    public function getRealm()
    {
        return $this->page->getRealm();
    }

    /**
     * Accessor for current URL of page or frame if focused.
     *
     * @return string location of current page or frame as a string
     */
    public function getUrl()
    {
        $url = $this->page->getUrl();

        return $url ? $url->asString() : false;
    }

    /**
     * Accessor for base URL of page if set via BASE tag.
     *
     * @return string base URL
     */
    public function getBaseUrl()
    {
        $url = $this->page->getBaseUrl();

        return $url ? $url->asString() : false;
    }

    /**
     * Accessor for raw bytes sent down the wire.
     *
     * @return string original text sent
     */
    public function getRequest()
    {
        return $this->page->getRequest();
    }

    /**
     * Accessor for raw header information.
     *
     * @return string header block
     */
    public function getHeaders()
    {
        return $this->page->getHeaders();
    }

    /**
     * Accessor for raw page information.
     *
     * @return string original text content of web page
     */
    public function getContent()
    {
        return $this->page->getRaw();
    }

    /**
     * Accessor for plain text version of the page.
     *
     * @return string normalised text representation
     */
    public function getContentAsText()
    {
        return $this->page->getText();
    }

    /**
     * Accessor for parsed title.
     *
     * @return string title or false if no title is present
     */
    public function getTitle()
    {
        return $this->page->getTitle();
    }

    /**
     * Accessor for a list of all links in current page.
     *
     * @return array list of urls with scheme of http or https and hostname
     */
    public function getUrls()
    {
        return $this->page->getUrls();
    }

    /**
     * Sets all form fields with that name or label.
     *
     * @param string $label name or label of field in forms
     * @param string $value new value of field
     *
     * @return bool true if field exists, otherwise false
     */
    public function setField($label, $value, $position = false)
    {
        return $this->page->setField(new SelectByLabelOrName($label), $value, $position);
    }

    /**
     * Sets all form fields with that name. Will use label if one is available (not yet
     * implemented).
     *
     * @param string $name  name of field in forms
     * @param string $value new value of field
     *
     * @return bool true if field exists, otherwise false
     */
    public function setFieldByName($name, $value, $position = false)
    {
        return $this->page->setField(new SelectByName($name), $value, $position);
    }

    /**
     * Sets all form fields with that label.
     *
     * @param string $label label of field in forms
     * @param string $value new value of field
     *
     * @return bool true if field exists, otherwise false
     */
    public function setFieldByLabel($label, $value, $position = false): bool
    {
        return $this->page->setField(new SelectByLabel($label), $value, $position);
    }

    /**
     * Sets all form fields with that id attribute.
     *
     * @param int|string $id    Id of field in forms
     * @param string     $value new value of field
     *
     * @return bool true if field exists, otherwise false
     */
    public function setFieldById($id, $value)
    {
        return $this->page->setField(new SelectById($id), $value);
    }

    /**
     * Accessor for a form element value within the page.
     * Finds the first match.
     *
     * @param string $label field label
     *
     * @return bool|string A value if the field is present, false if unchecked and null if
     *                     missing
     */
    public function getField($label)
    {
        return $this->page->getField(new SelectByLabelOrName($label));
    }

    /**
     * Accessor for a form element value within the page. Finds the first match.
     *
     * @param string $name field name
     *
     * @return bool|string A string if the field is present, false if unchecked and null if
     *                     missing
     */
    public function getFieldByName($name)
    {
        return $this->page->getField(new SelectByName($name));
    }

    /**
     * Accessor for a form element value within the page.
     *
     * @param int|string $id Id of field in forms
     *
     * @return bool|string A string if the field is present, false if unchecked and null if
     *                     missing
     */
    public function getFieldById($id)
    {
        return $this->page->getField(new SelectById($id));
    }

    /**
     * Clicks the submit button by label. The owning form will be submitted by this.
     *
     * @param string     $label      Button label. An unlabeled button can be triggered by 'Submit'.
     * @param array|bool $additional additional form data
     *
     * @return bool|string Page on success
     */
    public function clickSubmit($label = 'Submit', $additional = false)
    {
        if (!($form = $this->page->getFormBySubmit(new SelectByLabel($label)))) {
            return false;
        }
        $success = $this->load(
            $form->getAction(),
            $form->submitButton(new SelectByLabel($label), $additional),
        );

        return $success ? $this->getContent() : $success;
    }

    /**
     * Clicks the submit button by name attribute. The owning form will be submitted by this.
     *
     * @param string $name       button name
     * @param hash   $additional additional form data
     *
     * @return bool|string Page on success
     */
    public function clickSubmitByName($name, $additional = false)
    {
        if (!($form = $this->page->getFormBySubmit(new SelectByName($name)))) {
            return false;
        }
        $success = $this->load(
            $form->getAction(),
            $form->submitButton(new SelectByName($name), $additional),
        );

        return $success ? $this->getContent() : $success;
    }

    /**
     * Clicks the submit button by ID attribute of the button itself. The owning form will be
     * submitted by this.
     *
     * @param string $id         button ID
     * @param hash   $additional additional form data
     *
     * @return bool|string Page on success
     */
    public function clickSubmitById($id, $additional = false)
    {
        if (!($form = $this->page->getFormBySubmit(new SelectById($id)))) {
            return false;
        }
        $success = $this->load(
            $form->getAction(),
            $form->submitButton(new SelectById($id), $additional),
        );

        return $success ? $this->getContent() : $success;
    }

    /**
     * Tests to see if a submit button exists with this label.
     *
     * @param string $label button label
     *
     * @return bool true if present
     */
    public function isSubmit($label)
    {
        return (bool) $this->page->getFormBySubmit(new SelectByLabel($label));
    }

    /**
     * Clicks the submit image by some kind of label.
     * Usually the alt tag or the nearest equivalent.
     * The owning form will be submitted by this.
     * Clicking outside of the boundary of the coordinates will result in a failure.
     *
     * @param string $label      ID attribute of button
     * @param int    $x          X-coordinate of imaginary click
     * @param int    $y          Y-coordinate of imaginary click
     * @param hash   $additional additional form data
     *
     * @return bool|string Page on success
     */
    public function clickImage($label, $x = 1, $y = 1, $additional = false)
    {
        if (!($form = $this->page->getFormByImage(new SelectByLabel($label)))) {
            return false;
        }
        $success = $this->load(
            $form->getAction(),
            $form->submitImage(new SelectByLabel($label), $x, $y, $additional),
        );

        return $success ? $this->getContent() : $success;
    }

    /**
     * Clicks the submit image by the name.
     * Usually the alt tag or the nearest equivalent.
     * The owning form will be submitted by this.
     * Clicking outside of the boundary of the coordinates will result in a failure.
     *
     * @param string $name       name attribute of button
     * @param int    $x          X-coordinate of imaginary click
     * @param int    $y          Y-coordinate of imaginary click
     * @param hash   $additional additional form data
     *
     * @return bool|string Page on success
     */
    public function clickImageByName($name, $x = 1, $y = 1, $additional = false)
    {
        if (!($form = $this->page->getFormByImage(new SelectByName($name)))) {
            return false;
        }
        $success = $this->load(
            $form->getAction(),
            $form->submitImage(new SelectByName($name), $x, $y, $additional),
        );

        return $success ? $this->getContent() : $success;
    }

    /**
     * Clicks the submit image by ID attribute.
     * The owning form will be submitted by this.
     * Clicking outside of the boundary of the coordinates will result in a failure.
     *
     * @param int|string       $id         ID attribute of button
     * @param int              $x          X-coordinate of imaginary click
     * @param int              $y          Y-coordinate of imaginary click
     * @param array|bool|mixed $additional additional form data
     *
     * @return bool|string Page on success
     */
    public function clickImageById($id, $x = 1, $y = 1, $additional = false)
    {
        if (!($form = $this->page->getFormByImage(new SelectById($id)))) {
            return false;
        }
        $success = $this->load(
            $form->getAction(),
            $form->submitImage(new SelectById($id), $x, $y, $additional),
        );

        return $success ? $this->getContent() : $success;
    }

    /**
     * Tests to see if an image exists with this title or alt text.
     *
     * @param string $label image text
     *
     * @return bool true if present
     */
    public function isImage($label)
    {
        return (bool) $this->page->getFormByImage(new SelectByLabel($label));
    }

    /**
     * Submits a form by the ID.
     *
     * @param string $id The form ID. No submit button value will be sent.
     *
     * @return bool|string Page on success
     */
    public function submitFormById($id, $additional = false)
    {
        if (!($form = $this->page->getFormById($id))) {
            return false;
        }
        $success = $this->load(
            $form->getAction(),
            $form->submit($additional),
        );

        return $success ? $this->getContent() : $success;
    }

    /**
     * Finds a URL by label. Will find the first link found with this link text by default,
     * or a later one if an index is given. The match ignores case and white space issues.
     *
     * @param string $label text between the anchor tags
     * @param int    $index link position counting from zero
     *
     * @return bool|string URL on success
     */
    public function getLink($label, $index = 0)
    {
        $urls = $this->page->getUrlsByLabel($label);

        if (0 == \count($urls)) {
            return false;
        }

        if (\count($urls) < $index + 1) {
            return false;
        }

        return $urls[$index];
    }

    /**
     * Follows a link by label.
     * Will click the first link found with this link text by default,
     * or a later one, if an index is given.
     * The match ignores case and white space issues.
     *
     * @param string $label text between the anchor tags
     * @param int    $index link position counting from zero
     *
     * @return bool true on success
     */
    public function clickLink($label, $index = 0)
    {
        $url = $this->getLink($label, $index);

        if (false === $url) {
            return false;
        }

        $this->load($url, new SimpleGetEncoding);

        return (bool) $this->getContent();
    }

    /**
     * Finds a link by id attribute.
     *
     * @param string $id ID attribute value
     *
     * @return bool|string URL on success
     */
    public function getLinkById($id)
    {
        return $this->page->getUrlById($id);
    }

    /**
     * Follows a link by id attribute.
     *
     * @param string $id ID attribute value
     *
     * @return bool|string Page on success
     */
    public function clickLinkById($id)
    {
        if (!($url = $this->getLinkById($id))) {
            return false;
        }
        $this->load($url, new SimpleGetEncoding);

        return $this->getContent();
    }

    /**
     * Clicks a visible text item. Will first try buttons, then links and then images.
     *
     * @param string $label visible text or alt text
     *
     * @return bool|string Raw page or false
     */
    public function click($label)
    {
        $raw = $this->clickSubmit($label);

        if (!$raw) {
            $raw = $this->clickLink($label);
        }

        if (!$raw) {
            $raw = $this->clickImage($label);
        }

        return $raw;
    }

    /**
     * Tests to see if a click target exists.
     *
     * @param string $label visible text or alt text
     *
     * @return bool true if target present
     */
    public function isClickable($label)
    {
        return $this->isSubmit($label) || (false !== $this->getLink($label)) || $this->isImage($label);
    }

    /**
     * Creates the underlying user agent.
     *
     * @return SimpleUserAgent content fetcher
     */
    protected function createUserAgent()
    {
        return new SimpleUserAgent;
    }

    /**
     * Creates a new empty history list.
     *
     * @return SimpleBrowserHistory new list
     */
    protected function createHistory()
    {
        return new SimpleBrowserHistory;
    }

    /**
     * Get the HTML parser to use. Can be overridden by setParser.
     * Otherwise scans through the available parsers and uses the first one which is available.
     *
     * @return object SimplePHPPageBuilder or SimpleTidyPageBuilder
     */
    protected function getParser()
    {
        if ($this->parser) {
            return $this->parser;
        }

        foreach (SimpleTest::getParsers() as $parser) {
            if ($parser->can()) {
                return $parser;
            }
        }

        return null;
    }

    /**
     * Parses the raw content into a page.
     *
     * @param SimpleHttpResponse $response response from fetch
     *
     * @return SimplePage parsed HTML
     */
    protected function parse($response)
    {
        return $this->buildPage($response);
    }

    /**
     * Assembles the parsing machinery and actually parses a single page.
     * Frees all of the builder memory and so unjams the PHP memory management.
     *
     * @param SimpleHttpResponse $response response from fetch
     *
     * @return SimplePage parsed top level page
     */
    protected function buildPage($response)
    {
        return $this->getParser()->parse($response);
    }

    /**
     * Fetches a page.
     * Jointly recursive with the parse() method as it descends a frameset.
     *
     * @param SimpleUrl|string $url      Target to fetch
     * @param SimpleEncoding   $encoding GET/POST parameters
     *
     * @return SimplePage parsed page
     */
    protected function fetch($url, $encoding)
    {
        $http_referer = $this->history->getUrl();

        if ($http_referer) {
            $this->user_agent->setReferer($http_referer->asString());
        } else {
            $this->user_agent->setReferer(null);
        }

        $response = $this->user_agent->fetchResponse($url, $encoding);

        if ($response->isError()) {
            return new SimplePage($response);
        }

        return $this->parse($response);
    }

    /**
     * Fetches a page.
     *
     * @param SimpleUrl      $url        target to fetch
     * @param simpleEncoding $parameters GET/POST parameters
     *
     * @return string raw content of page
     */
    protected function load($url, $parameters)
    {
        return $this->loadPage($url, $parameters);
    }

    /**
     * Fetches a page and makes it the current page/frame.
     *
     * @param simplePostEncoding $parameters POST parameters
     * @param string/SimpleUrl   $url        Target to fetch as string
     *
     * @return string raw content of page
     */
    protected function loadPage($url, $parameters)
    {
        $this->page = $this->fetch($url, $parameters);
        $this->history->recordEntry(
            $this->page->getUrl(),
            $this->page->getRequestData(),
        );

        return $this->page->getRaw();
    }
}
