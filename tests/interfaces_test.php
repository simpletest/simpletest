<?php declare(strict_types=1);

require_once __DIR__ . '/../src/autorun.php';

include __DIR__ . '/support/spl_examples.php';

interface DummyInterface
{
    public function aMethod();

    public function anotherMethod($a);

    // Deprecated: Returning by reference from a void function is deprecated
    // public function &referenceMethod(&$a);
}

Mock::generate('DummyInterface');
Mock::generatePartial('DummyInterface', 'PartialDummyInterface', []);

class TestOfMockInterfaces extends UnitTestCase
{
    public function testCanMockAnInterface(): void
    {
        $mock = new MockDummyInterface;
        $this->assertIsA($mock, 'SimpleMock');
        $this->assertIsA($mock, 'MockDummyInterface');
        $this->assertTrue(\method_exists($mock, 'aMethod'));
        $this->assertTrue(\method_exists($mock, 'anotherMethod'));
        $this->assertNull($mock->aMethod());
    }

    public function testMockedInterfaceExpectsParameters(): void
    {
        $mock = new MockDummyInterface;
        $this->expectError();

        try {
            $mock->anotherMethod();
        } catch (Error $e) {
            \trigger_error($e->getMessage());
        }
    }

    public function testCannotPartiallyMockAnInterface(): void
    {
        $this->assertFalse(\class_exists('PartialDummyInterface'));
    }
}

class TestOfSpl extends UnitTestCase
{
    public function testCanMockAllSplClasses(): void
    {
        static $classesToExclude = [
            'SplHeap', // the method compare() is missing (protected)
            // 'FilterIterator', // the method accept() is missing
            // 'RecursiveFilterIterator', // the method hasChildren() must contain body
        ];

        foreach (\spl_classes() as $class) {
            // exclude classes
            if (\in_array($class, $classesToExclude, true)) {
                continue;
            }

            $mock_class = "Mock{$class}";
            Mock::generate($class);
            $this->assertIsA(new $mock_class, $mock_class);
        }
    }

    public function testExtensionOfCommonSplClasses(): void
    {
        Mock::generate('IteratorImplementation');
        $this->assertIsA(
            new IteratorImplementation,
            'IteratorImplementation',
        );
        Mock::generate('IteratorAggregateImplementation');
        $this->assertIsA(
            new IteratorAggregateImplementation,
            'IteratorAggregateImplementation',
        );
    }
}

class WithHint
{
    public function hinted(DummyInterface $object): void
    {
    }
}

class ImplementsDummy implements DummyInterface
{
    public function aMethod(): void
    {
    }

    public function anotherMethod($a): void
    {
    }

    // Deprecated: Returning by reference from a void function is deprecated
    /*public function &referenceMethod(&$a): void
    {
    }*/

    public function extraMethod($a = false): void
    {
    }
}
Mock::generate('ImplementsDummy');

class TestOfImplementations extends UnitTestCase
{
    public function testMockedInterfaceCanPassThroughTypeHint(): void
    {
        $mock   = new MockDummyInterface;
        $hinter = new WithHint;
        $hinter->hinted($mock);
    }

    public function testImplementedInterfacesAreCarried(): void
    {
        $mock   = new MockImplementsDummy;
        $hinter = new WithHint;
        $hinter->hinted($mock);
    }

    public function testNoSpuriousWarningsWhenSkippingDefaultedParameter(): void
    {
        $mock = new MockImplementsDummy;
        $mock->extraMethod();
    }
}

interface SampleInterfaceWithClone
{
    public function __clone();
}

class TestOfSampleInterfaceWithClone extends UnitTestCase
{
    public function testCanMockWithoutErrors(): void
    {
        Mock::generate('SampleInterfaceWithClone');
    }
}

interface SampleInterfaceWithHintInSignature
{
    public function method(array $hinted);
}

class TestOfInterfaceMocksWithHintInSignature extends UnitTestCase
{
    public function testBasicConstructOfAnInterfaceWithHintInSignature(): void
    {
        Mock::generate('SampleInterfaceWithHintInSignature');
        $mock = new MockSampleInterfaceWithHintInSignature;
        $this->assertIsA($mock, 'SampleInterfaceWithHintInSignature');
    }
}
