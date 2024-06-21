<?php declare(strict_types=1);

require_once __DIR__ . '/../../shell_tester.php';

require_once __DIR__ . '/../../mock_objects.php';

require_once __DIR__ . '/../../xml.php';

require_once __DIR__ . '/../../autorun.php';

class VisualTestOfErrors extends UnitTestCase
{
    public function testErrorDisplay(): void
    {
        $this->dump('Four exceptions...');
        \trigger_error('Default');
        \trigger_error('Error', E_USER_ERROR);
        \trigger_error('Warning', E_USER_WARNING);
        \trigger_error('Notice', E_USER_NOTICE);
    }

    public function testErrorTrap(): void
    {
        $this->dump('Pass...');
        $this->expectError();
        \trigger_error('Error');
    }

    public function testUnusedErrorExpectationsCauseFailures(): void
    {
        $this->dump('Two failures...');
        $this->expectError('Some error');
        $this->expectError();
    }

    public function testErrorTextIsSentImmediately(): void
    {
        $this->dump('One failure...');
        $this->expectError('Error');
        \trigger_error('Error almost');
        $this->dump('This should lie between the two errors');
        \trigger_error('Error after');
    }
}

class VisualTestOfExceptions extends UnitTestCase
{
    public function testExceptionTrap(): void
    {
        $this->dump('One exception...');
        $this->ouch();
        $this->fail('Should not be here');
    }

    public function testExceptionExpectationShowsErrorWhenNoException(): void
    {
        $this->dump('One failure...');
        $this->expectException('SomeException');
        $this->expectException('LaterException');
    }

    public function testExceptionExpectationShowsPassWhenException(): void
    {
        $this->dump('Pass...');
        $this->expectException();
        $this->ouch();
    }

    public function ouch(): void
    {
        eval('throw new Exception("Ouch!");');
    }
}

class OpaqueContainer
{
    private $stuff;
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }
}

class VisualTestOfObjectComparison extends UnitTestCase
{
    public function testDifferenceBetweenPrivateMembersCanBeDescribed(): void
    {
        $this->assertIdentical(new OpaqueContainer(1), new OpaqueContainer(2));
    }
}
