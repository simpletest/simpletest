<?php declare(strict_types=1);

require_once \dirname(__DIR__, 2) . '/src/autorun.php';

require_once \dirname(__DIR__, 2) . '/test_case.php';

/**
 * @see https://github.com/simpletest/simpletest/issues/29
 */
class issue29 extends UnitTestCase
{
    public function testShouldEscapePercentSignInMessageContainingAnUnescapedURL(): void
    {
        $this->assertEqual(1, 1, 'http://www.domain.com/some%20name.html');
        $this->assertEqual(1, 1, 'http://www.domain.com/some%20long%20name.html');
    }
}
