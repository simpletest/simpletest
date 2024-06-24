<?php declare(strict_types=1);

require_once __DIR__ . '/../src/autorun.php';

require_once __DIR__ . '/../src/authentication.php';

require_once __DIR__ . '/../src/http.php';

Mock::generate('SimpleHttpRequest');

class TestOfRealm extends UnitTestCase
{
    public function testWithinSameUrl(): void
    {
        $realm = new SimpleRealm(
            'Basic',
            new SimpleUrl('http://www.here.com/path/hello.html'),
        );
        $this->assertTrue($realm->isWithin(
            new SimpleUrl('http://www.here.com/path/hello.html'),
        ));
    }

    public function testInsideWithLongerUrl(): void
    {
        $realm = new SimpleRealm(
            'Basic',
            new SimpleUrl('http://www.here.com/path/'),
        );
        $this->assertTrue($realm->isWithin(
            new SimpleUrl('http://www.here.com/path/hello.html'),
        ));
    }

    public function testBelowRootIsOutside(): void
    {
        $realm = new SimpleRealm(
            'Basic',
            new SimpleUrl('http://www.here.com/path/'),
        );
        $this->assertTrue($realm->isWithin(
            new SimpleUrl('http://www.here.com/path/more/hello.html'),
        ));
    }

    public function testOldNetscapeDefinitionIsOutside(): void
    {
        $realm = new SimpleRealm(
            'Basic',
            new SimpleUrl('http://www.here.com/path/'),
        );
        $this->assertFalse($realm->isWithin(
            new SimpleUrl('http://www.here.com/pathmore/hello.html'),
        ));
    }

    public function testInsideWithMissingTrailingSlash(): void
    {
        $realm = new SimpleRealm(
            'Basic',
            new SimpleUrl('http://www.here.com/path/'),
        );
        $this->assertTrue($realm->isWithin(
            new SimpleUrl('http://www.here.com/path'),
        ));
    }

    public function testDifferentPageNameStillInside(): void
    {
        $realm = new SimpleRealm(
            'Basic',
            new SimpleUrl('http://www.here.com/path/hello.html'),
        );
        $this->assertTrue($realm->isWithin(
            new SimpleUrl('http://www.here.com/path/goodbye.html'),
        ));
    }

    public function testNewUrlInSameDirectoryDoesNotChangeRealm(): void
    {
        $realm = new SimpleRealm(
            'Basic',
            new SimpleUrl('http://www.here.com/path/hello.html'),
        );
        $realm->stretch(new SimpleUrl('http://www.here.com/path/goodbye.html'));
        $this->assertTrue($realm->isWithin(
            new SimpleUrl('http://www.here.com/path/index.html'),
        ));
        $this->assertFalse($realm->isWithin(
            new SimpleUrl('http://www.here.com/index.html'),
        ));
    }

    public function testNewUrlMakesRealmTheCommonPath(): void
    {
        $realm = new SimpleRealm(
            'Basic',
            new SimpleUrl('http://www.here.com/path/here/hello.html'),
        );
        $realm->stretch(new SimpleUrl('http://www.here.com/path/there/goodbye.html'));
        $this->assertTrue($realm->isWithin(
            new SimpleUrl('http://www.here.com/path/here/index.html'),
        ));
        $this->assertTrue($realm->isWithin(
            new SimpleUrl('http://www.here.com/path/there/index.html'),
        ));
        $this->assertTrue($realm->isWithin(
            new SimpleUrl('http://www.here.com/path/index.html'),
        ));
        $this->assertFalse($realm->isWithin(
            new SimpleUrl('http://www.here.com/index.html'),
        ));
        $this->assertFalse($realm->isWithin(
            new SimpleUrl('http://www.here.com/paths/index.html'),
        ));
        $this->assertFalse($realm->isWithin(
            new SimpleUrl('http://www.here.com/pathindex.html'),
        ));
    }
}

class TestOfAuthenticator extends UnitTestCase
{
    public function testNoRealms(): void
    {
        $request = new MockSimpleHttpRequest;
        $request->expectNever('addHeaderLine');
        $authenticator = new SimpleAuthenticator;
        $authenticator->addHeaders($request, new SimpleUrl('http://here.com/'));
    }

    public function createSingleRealm()
    {
        $authenticator = new SimpleAuthenticator;
        $authenticator->addRealm(
            new SimpleUrl('http://www.here.com/path/hello.html'),
            'Basic',
            'Sanctuary',
        );
        $authenticator->setIdentityForRealm('www.here.com', 'Sanctuary', 'test', 'secret');

        return $authenticator;
    }

    public function testOutsideRealm(): void
    {
        $request = new MockSimpleHttpRequest;
        $request->expectNever('addHeaderLine');
        $authenticator = $this->createSingleRealm();
        $authenticator->addHeaders(
            $request,
            new SimpleUrl('http://www.here.com/hello.html'),
        );
    }

    public function testWithinRealm(): void
    {
        $request = new MockSimpleHttpRequest;
        $request->expectOnce('addHeaderLine');
        $authenticator = $this->createSingleRealm();
        $authenticator->addHeaders(
            $request,
            new SimpleUrl('http://www.here.com/path/more/hello.html'),
        );
    }

    public function testRestartingClearsRealm(): void
    {
        $request = new MockSimpleHttpRequest;
        $request->expectNever('addHeaderLine');
        $authenticator = $this->createSingleRealm();
        $authenticator->restartSession();
        $authenticator->addHeaders(
            $request,
            new SimpleUrl('http://www.here.com/hello.html'),
        );
    }

    public function testDifferentHostIsOutsideRealm(): void
    {
        $request = new MockSimpleHttpRequest;
        $request->expectNever('addHeaderLine');
        $authenticator = $this->createSingleRealm();
        $authenticator->addHeaders(
            $request,
            new SimpleUrl('http://here.com/path/hello.html'),
        );
    }
}
