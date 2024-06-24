<?php declare(strict_types=1);

require_once __DIR__ . '/../src/autorun.php';

require_once __DIR__ . '/../src/cookies.php';

class TestOfCookie extends UnitTestCase
{
    public function testCookieDefaults(): void
    {
        $cookie = new SimpleCookie('name');
        $this->assertFalse($cookie->getValue());
        $this->assertEqual($cookie->getPath(), '/');
        $this->assertIdentical($cookie->getHost(), false);
        $this->assertFalse($cookie->getExpiry());
        $this->assertFalse($cookie->isSecure());
    }

    public function testCookieAccessors(): void
    {
        $cookie = new SimpleCookie(
            'name',
            'value',
            '/path',
            'Mon, 18 Nov 2002 15:50:29 GMT',
            true,
        );
        $this->assertEqual($cookie->getName(), 'name');
        $this->assertEqual($cookie->getValue(), 'value');
        $this->assertEqual($cookie->getPath(), '/path/');
        $this->assertEqual($cookie->getExpiry(), 'Mon, 18 Nov 2002 15:50:29 GMT');
        $this->assertTrue($cookie->isSecure());
    }

    public function testFullHostname(): void
    {
        $cookie = new SimpleCookie('name');
        $this->assertTrue($cookie->setHost('host.name.here'));
        $this->assertEqual($cookie->getHost(), 'host.name.here');
        $this->assertTrue($cookie->setHost('host.com'));
        $this->assertEqual($cookie->getHost(), 'host.com');
    }

    public function testHostTruncation(): void
    {
        $cookie = new SimpleCookie('name');
        $cookie->setHost('this.host.name.here');
        $this->assertEqual($cookie->getHost(), 'host.name.here');
        $cookie->setHost('this.host.com');
        $this->assertEqual($cookie->getHost(), 'host.com');
        $this->assertTrue($cookie->setHost('dashes.in-host.com'));
        $this->assertEqual($cookie->getHost(), 'in-host.com');
    }

    public function testBadHosts(): void
    {
        $cookie = new SimpleCookie('name');
        $this->assertFalse($cookie->setHost('gibberish'));
        $this->assertFalse($cookie->setHost('host.here'));
        $this->assertFalse($cookie->setHost('host..com'));
        $this->assertFalse($cookie->setHost('...'));
        $this->assertFalse($cookie->setHost('host.com.'));
    }

    public function testHostValidity(): void
    {
        $cookie = new SimpleCookie('name');
        $cookie->setHost('this.host.name.here');
        $this->assertTrue($cookie->isValidHost('host.name.here'));
        $this->assertTrue($cookie->isValidHost('that.host.name.here'));
        $this->assertFalse($cookie->isValidHost('bad.host'));
        $this->assertFalse($cookie->isValidHost('nearly.name.here'));
    }

    public function testPathValidity(): void
    {
        $cookie = new SimpleCookie('name', 'value', '/path');
        $this->assertFalse($cookie->isValidPath('/'));
        $this->assertTrue($cookie->isValidPath('/path/'));
        $this->assertTrue($cookie->isValidPath('/path/more'));
    }

    public function testSessionExpiring(): void
    {
        $cookie = new SimpleCookie('name', 'value', '/path');
        $this->assertTrue($cookie->isExpired(0));
    }

    public function testTimestampExpiry(): void
    {
        $cookie = new SimpleCookie('name', 'value', '/path', 456);
        $this->assertFalse($cookie->isExpired(0));
        $this->assertTrue($cookie->isExpired(457));
        $this->assertFalse($cookie->isExpired(455));
    }

    public function testDateExpiry(): void
    {
        $cookie = new SimpleCookie(
            'name',
            'value',
            '/path',
            'Mon, 18 Nov 2002 15:50:29 GMT',
        );
        $this->assertTrue($cookie->isExpired('Mon, 18 Nov 2002 15:50:30 GMT'));
        $this->assertFalse($cookie->isExpired('Mon, 18 Nov 2002 15:50:28 GMT'));
    }

    public function testAging(): void
    {
        $cookie = new SimpleCookie('name', 'value', '/path', 200);
        $cookie->agePrematurely(199);
        $this->assertFalse($cookie->isExpired(0));
        $cookie->agePrematurely(2);
        $this->assertTrue($cookie->isExpired(0));
    }
}

class TestOfCookieJar extends UnitTestCase
{
    public function testAddCookie(): void
    {
        $jar = new SimpleCookieJar;
        $jar->setCookie('a', 'A');
        $this->assertEqual($jar->selectAsPairs(new SimpleUrl('/')), ['a=A']);
    }

    public function testHostFilter(): void
    {
        $jar = new SimpleCookieJar;
        $jar->setCookie('a', 'A', 'my-host.com');
        $jar->setCookie('b', 'B', 'another-host.com');
        $jar->setCookie('c', 'C');
        $this->assertEqual(
            $jar->selectAsPairs(new SimpleUrl('my-host.com')),
            ['a=A', 'c=C'],
        );
        $this->assertEqual(
            $jar->selectAsPairs(new SimpleUrl('another-host.com')),
            ['b=B', 'c=C'],
        );
        $this->assertEqual(
            $jar->selectAsPairs(new SimpleUrl('www.another-host.com')),
            ['b=B', 'c=C'],
        );
        $this->assertEqual(
            $jar->selectAsPairs(new SimpleUrl('new-host.org')),
            ['c=C'],
        );
        $this->assertEqual(
            $jar->selectAsPairs(new SimpleUrl('/')),
            ['a=A', 'b=B', 'c=C'],
        );
    }

    public function testPathFilter(): void
    {
        $jar = new SimpleCookieJar;
        $jar->setCookie('a', 'A', false, '/path/');
        $this->assertEqual($jar->selectAsPairs(new SimpleUrl('/')), []);
        $this->assertEqual($jar->selectAsPairs(new SimpleUrl('/elsewhere')), []);
        $this->assertEqual($jar->selectAsPairs(new SimpleUrl('/path/')), ['a=A']);
        $this->assertEqual($jar->selectAsPairs(new SimpleUrl('/path')), ['a=A']);
        $this->assertEqual($jar->selectAsPairs(new SimpleUrl('/pa')), []);
        $this->assertEqual($jar->selectAsPairs(new SimpleUrl('/path/here')), ['a=A']);
    }

    public function testPathFilterDeeply(): void
    {
        $jar = new SimpleCookieJar;
        $jar->setCookie('a', 'A', false, '/path/more_path/');
        $this->assertEqual($jar->selectAsPairs(new SimpleUrl('/path/')), []);
        $this->assertEqual($jar->selectAsPairs(new SimpleUrl('/path')), []);
        $this->assertEqual($jar->selectAsPairs(new SimpleUrl('/pa')), []);
        $this->assertEqual($jar->selectAsPairs(new SimpleUrl('/path/more_path/')), ['a=A']);
        $this->assertEqual($jar->selectAsPairs(new SimpleUrl('/path/more_path/and_more')), ['a=A']);
        $this->assertEqual($jar->selectAsPairs(new SimpleUrl('/path/not_here/')), []);
    }

    public function testMultipleCookieWithDifferentPathsButSameName(): void
    {
        $jar = new SimpleCookieJar;
        $jar->setCookie('a', 'abc', false, '/');
        $jar->setCookie('a', '123', false, '/path/here/');
        $this->assertEqual(
            $jar->selectAsPairs(new SimpleUrl('/')),
            ['a=abc'],
        );
        $this->assertEqual(
            $jar->selectAsPairs(new SimpleUrl('my-host.com/')),
            ['a=abc'],
        );
        $this->assertEqual(
            $jar->selectAsPairs(new SimpleUrl('my-host.com/path/')),
            ['a=abc'],
        );
        $this->assertEqual(
            $jar->selectAsPairs(new SimpleUrl('my-host.com/path/here')),
            ['a=abc', 'a=123'],
        );
        $this->assertEqual(
            $jar->selectAsPairs(new SimpleUrl('my-host.com/path/here/there')),
            ['a=abc', 'a=123'],
        );
    }

    public function testOverwrite(): void
    {
        $jar = new SimpleCookieJar;
        $jar->setCookie('a', 'abc', false, '/');
        $jar->setCookie('a', 'cde', false, '/');
        $this->assertEqual($jar->selectAsPairs(new SimpleUrl('/')), ['a=cde']);
    }

    public function testClearSessionCookies(): void
    {
        $jar = new SimpleCookieJar;
        $jar->setCookie('a', 'A', false, '/');
        $jar->restartSession();
        $this->assertEqual($jar->selectAsPairs(new SimpleUrl('/')), []);
    }

    public function testExpiryFilterByDate(): void
    {
        $jar = new SimpleCookieJar;
        $jar->setCookie('a', 'A', false, '/', 'Wed, 25-Dec-02 04:24:20 GMT');
        $jar->restartSession('Wed, 25-Dec-02 04:24:19 GMT');
        $this->assertEqual($jar->selectAsPairs(new SimpleUrl('/')), ['a=A']);
        $jar->restartSession('Wed, 25-Dec-02 04:24:21 GMT');
        $this->assertEqual($jar->selectAsPairs(new SimpleUrl('/')), []);
    }

    public function testExpiryFilterByAgeing(): void
    {
        $jar = new SimpleCookieJar;
        $jar->setCookie('a', 'A', false, '/', 'Wed, 25-Dec-02 04:24:20 GMT');
        $jar->restartSession('Wed, 25-Dec-02 04:24:19 GMT');
        $this->assertEqual($jar->selectAsPairs(new SimpleUrl('/')), ['a=A']);
        $jar->agePrematurely(2);
        $jar->restartSession('Wed, 25-Dec-02 04:24:19 GMT');
        $this->assertEqual($jar->selectAsPairs(new SimpleUrl('/')), []);
    }

    public function testCookieClearing(): void
    {
        $jar = new SimpleCookieJar;
        $jar->setCookie('a', 'abc', false, '/');
        $jar->setCookie('a', '', false, '/');
        $this->assertEqual($jar->selectAsPairs(new SimpleUrl('/')), ['a=']);
    }

    public function testCookieClearByLoweringDate(): void
    {
        $jar = new SimpleCookieJar;
        $jar->setCookie('a', 'abc', false, '/', 'Wed, 25-Dec-02 04:24:21 GMT');
        $jar->setCookie('a', 'def', false, '/', 'Wed, 25-Dec-02 04:24:19 GMT');
        $this->assertEqual($jar->selectAsPairs(new SimpleUrl('/')), ['a=def']);
        $jar->restartSession('Wed, 25-Dec-02 04:24:20 GMT');
        $this->assertEqual($jar->selectAsPairs(new SimpleUrl('/')), []);
    }
}
