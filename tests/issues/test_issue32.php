<?php declare(strict_types=1);

require_once \dirname(__DIR__, 2) . '/src/autorun.php';

require_once \dirname(__DIR__, 2) . '/src/web_tester.php';

class TestCrossSubdomainCookies extends WebTestCase
{
    // URL of the main domain where the cookie is set
    private $mainDomainUrl = 'http://localhost:8080/set_cookies.php';

    // URL of the subdomain where the cookie should be accessible
    private $subDomainUrl = 'http://localhost:8081/check_subdomain_cookie.php';
    private $cookieName   = 'mydomain_cookie';
    private $cookieValue  = 'is_accessible_on_subdomain';

    public function testCookieSetOnMainDomain(): void
    {
        $this->get($this->mainDomainUrl);
        $this->assertCookie($this->cookieName, $this->cookieValue);
    }

    public function testCookieAccessibleOnSubdomain(): void
    {
        $this->get($this->subDomainUrl);

        // Verify the cookie is accessible on the subdomain
        $this->assertCookie($this->cookieName, $this->cookieValue);
    }
}
