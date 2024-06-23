<?php declare(strict_types=1);

require_once __DIR__ . '/http.php';

/**
 * Represents a single security realm's identity.
 */
class SimpleRealm
{
    /** @var string */
    private $type;

    /** @var string */
    private $root;

    /** @var string */
    private $username = '';

    /** @var string */
    private $password = '';

    /**
     * Starts with the initial entry directory.
     *
     * @param string    $type Authentication type for this realm. Only Basic
     *                        authentication is currently supported.
     * @param SimpleUrl $url  somewhere in realm
     */
    public function __construct($type, $url)
    {
        $this->type = $type;
        $this->root = $url->getBasePath();
    }

    /**
     * Adds another location to the realm.
     *
     * @param SimpleUrl $url somewhere in realm
     */
    public function stretch($url): void
    {
        $this->root = $this->getCommonPath($this->root, $url->getPath());
    }

    /**
     * Sets the identity to try within this realm.
     *
     * @param string $username username in authentication dialog
     * @param string $password password in authentication dialog
     */
    public function setIdentity($username, $password): void
    {
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Accessor for current identity.
     *
     * @return string last succesful username
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Accessor for current identity.
     *
     * @return string last succesful password
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Test to see if the URL is within the directory tree of the realm.
     *
     * @param SimpleUrl $url URL to test
     *
     * @return bool true if subpath
     */
    public function isWithin($url)
    {
        if ($this->isIn($this->root, $url->getBasePath())) {
            return true;
        }

        return $this->isIn($this->root, $url->getBasePath() . $url->getPage() . '/');
    }

    /**
     * Finds the common starting path.
     *
     * @param string $first  path to compare
     * @param string $second path to compare
     *
     * @return string common directories
     */
    protected function getCommonPath(string $first, string $second) /* : string */
    {
        if ($first === '' || $first === '0' || ($second === '' || $second === '0')) {
            return '';
        }

        $firstParts  = \explode('/', $first);
        $secondParts = \explode('/', $second);
        $commonParts = [];

        $minCount = \min(\count($firstParts), \count($secondParts));

        for ($i = 0; $i < $minCount; $i++) {
            if ($firstParts[$i] !== $secondParts[$i]) {
                break;
            }
            $commonParts[] = $firstParts[$i];
        }

        $path = \implode('/', $commonParts);

        if ($commonParts !== []) {
            $path .= '/';
        }

        return $path;
    }

    /**
     * Tests to see if one string is a substring of another.
     *
     * @param string $part  small bit
     * @param string $whole big bit
     *
     * @return bool true if the small bit is in the big bit
     */
    protected function isIn($part, $whole)
    {
        return \str_starts_with($whole, $part);
    }
}

/**
 * Manages security realms.
 */
class SimpleAuthenticator
{
    private $realms;

    /**
     * Clears the realms.
     */
    public function __construct()
    {
        $this->restartSession();
    }

    /**
     * Presents the appropriate headers for this location for basic authentication.
     *
     * @param SimpleHttpRequest $request  request to modify
     * @param string            $username username for realm
     * @param string            $password password for realm
     */
    public function addBasicHeaders(&$request, $username, $password): void
    {
        if ($username && $password) {
            $request->addHeaderLine(
                'Authorization: Basic ' . \base64_encode("{$username}:{$password}"),
            );
        }
    }

    /**
     * Starts with no realms set up.
     */
    public function restartSession(): void
    {
        $this->realms = [];
    }

    /**
     * Adds a new realm centered the current URL. Browsers privatey wildly on
     * their behaviour in this regard. Mozilla ignores the realm and presents
     * only when challenged, wasting bandwidth. IE just carries on presenting
     * until a new challenge occours. SimpleTest tries to follow the spirit of
     * the original standards committee and treats the base URL as the root of a
     * file tree shaped realm.
     *
     * @param SimpleUrl $url   base of realm
     * @param string    $type  Authentication type for this realm. Only
     *                         Basicauthentication is currently supported.
     * @param string    $realm name of realm
     */
    public function addRealm($url, $type, $realm): void
    {
        $this->realms[$url->getHost()][$realm] = new SimpleRealm($type, $url);
    }

    /**
     * Sets the current identity to be presented against that realm.
     *
     * @param string $host     server hosting realm
     * @param string $realm    name of realm
     * @param string $username username for realm
     * @param string $password password for realm
     */
    public function setIdentityForRealm($host, $realm, $username, $password): void
    {
        if (isset($this->realms[$host][$realm])) {
            $this->realms[$host][$realm]->setIdentity($username, $password);
        }
    }

    /**
     * Presents the appropriate headers for this location.
     *
     * @param SimpleHttpRequest $request request to modify
     * @param SimpleUrl         $url     base of realm
     */
    public function addHeaders($request, $url): void
    {
        if ($url->getUsername() && $url->getPassword()) {
            $username = $url->getUsername();
            $password = $url->getPassword();
        } elseif ($realm = $this->findRealmFromUrl($url)) {
            $username = $realm->getUsername();
            $password = $realm->getPassword();
        } else {
            return;
        }
        $this->addBasicHeaders($request, $username, $password);
    }

    /**
     * Finds the name of the realm by comparing URLs.
     *
     * @param SimpleUrl $url URL to test
     *
     * @return bool|SimpleRealm name of realm
     */
    protected function findRealmFromUrl($url)
    {
        if (!isset($this->realms[$url->getHost()])) {
            return false;
        }

        foreach ($this->realms[$url->getHost()] as $name => $realm) {
            if ($realm->isWithin($url)) {
                return $realm;
            }
        }

        return false;
    }
}
