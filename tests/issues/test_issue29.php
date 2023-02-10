<?php

require_once __DIR__.'/../../autorun.php';
require_once __DIR__.'/../../test_case.php';

/**
 * @see https://github.com/simpletest/simpletest/issues/29
 */
class issue29 extends UnitTestCase
{
    public function testShouldEscapePercentSignInMessageContainingAnUnescapedURL()
    {
        $this->assertEqual(1, 1, 'http://www.domain.com/some%20name.html');
        $this->assertEqual(1, 1, 'http://www.domain.com/some%20long%20name.html');
    }
}
