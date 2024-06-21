<?php declare(strict_types=1);

require_once __DIR__ . '/../src/autorun.php';

require_once __DIR__ . '/../src/socket.php';

Mock::generate('SimpleSocket');

class TestOfSimpleStickyError extends UnitTestCase
{
    public function testSettingError(): void
    {
        $error = new SimpleStickyError;
        $this->assertFalse($error->isError());
        $error->setError('Ouch');
        $this->assertTrue($error->isError());
        $this->assertEqual($error->getError(), 'Ouch');
    }

    public function testClearingError(): void
    {
        $error = new SimpleStickyError;
        $error->setError('Ouch');
        $this->assertTrue($error->isError());
        $error->clearError();
        $this->assertFalse($error->isError());
    }
}
