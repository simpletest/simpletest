<?php declare(strict_types=1);

require_once __DIR__ . '/../src/autorun.php';

require_once __DIR__ . '/../src/reflection.php';

class AnyOldLeafClass
{
    public function aMethod(): void
    {
    }
}

abstract class AnyOldClass
{
    public function aMethod(): void
    {
    }
}

class AnyOldLeafClassWithAFinal
{
    final public function aMethod(): void
    {
    }
}

interface AnyOldInterface
{
    public function aMethod();
}

interface AnyOldArgumentInterface
{
    public function aMethod(AnyOldInterface $argument);
}

interface AnyDescendentInterface extends AnyOldInterface
{
}

class AnyOldImplementation implements AnyOldInterface
{
    public function aMethod(): void
    {
    }

    public function extraMethod(): void
    {
    }
}

abstract class AnyAbstractImplementation implements AnyOldInterface
{
}

abstract class AnotherOldAbstractClass
{
    protected function aMethod(AnyOldInterface $argument): void
    {
    }
}

class AnyOldSubclass extends AnyOldImplementation
{
}

class AnyOldArgumentClass
{
    public function aMethod($argument): void
    {
    }
}

class AnyOldArgumentImplementation implements AnyOldArgumentInterface
{
    public function aMethod(AnyOldInterface $argument): void
    {
    }
}

class AnyOldTypeHintedClass implements AnyOldArgumentInterface
{
    public function aMethod(AnyOldInterface $argument): void
    {
    }
}

class AnyDescendentImplementation implements AnyDescendentInterface
{
    public function aMethod(): void
    {
    }
}

class AnyOldOverloadedClass
{
    public function __isset($key)
    {
    }

    public function __unset($key): void
    {
    }
}

class AnyOldClassWithStaticMethods
{
    public static function aStatic(): void
    {
    }

    public static function aStaticWithParameters($arg1, $arg2): void
    {
    }
}

abstract class AnyOldAbstractClassWithAbstractMethods
{
    abstract public function anAbstract();

    abstract public function anAbstractWithParameter($foo);

    abstract public function anAbstractWithMultipleParameters($foo, $bar);
}

class TestOfReflection extends UnitTestCase
{
    public function testClassExistence(): void
    {
        $reflection = new SimpleReflection('AnyOldLeafClass');
        $this->assertTrue($reflection->classOrInterfaceExists());
        $this->assertTrue($reflection->classOrInterfaceExistsWithoutAutoload());
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isInterface());
    }

    public function testClassNonExistence(): void
    {
        $reflection = new SimpleReflection('UnknownThing');
        $this->assertFalse($reflection->classOrInterfaceExists());
        $this->assertFalse($reflection->classOrInterfaceExistsWithoutAutoload());
    }

    public function testDetectionOfAbstractClass(): void
    {
        $reflection = new SimpleReflection('AnyOldClass');
        $this->assertTrue($reflection->isAbstract());
    }

    public function testDetectionOfFinalMethods(): void
    {
        $reflectionA = new SimpleReflection('AnyOldClass');
        $this->assertFalse($reflectionA->hasFinal());

        $reflectionB = new SimpleReflection('AnyOldLeafClassWithAFinal');
        $this->assertTrue($reflectionB->hasFinal());
    }

    public function testFindingParentClass(): void
    {
        $reflection = new SimpleReflection('AnyOldSubclass');
        $this->assertEqual($reflection->getParent(), 'AnyOldImplementation');
    }

    public function testInterfaceExistence(): void
    {
        $reflection = new SimpleReflection('AnyOldInterface');
        $this->assertTrue($reflection->classOrInterfaceExists());
        $this->assertTrue($reflection->classOrInterfaceExistsWithoutAutoload());
        $this->assertTrue($reflection->isInterface());
    }

    public function testMethodsListFromClass(): void
    {
        $reflection = new SimpleReflection('AnyOldClass');
        $this->assertIdentical($reflection->getMethods(), ['aMethod']);
    }

    public function testMethodsListFromInterface(): void
    {
        $reflection = new SimpleReflection('AnyOldInterface');
        $this->assertIdentical($reflection->getMethods(), ['aMethod']);
        $this->assertIdentical($reflection->getInterfaceMethods(), ['aMethod']);
    }

    public function testMethodsComeFromDescendentInterfacesASWell(): void
    {
        $reflection = new SimpleReflection('AnyDescendentInterface');
        $this->assertIdentical($reflection->getMethods(), ['aMethod']);
    }

    public function testCanSeparateInterfaceMethodsFromOthers(): void
    {
        $reflection = new SimpleReflection('AnyOldImplementation');
        $this->assertIdentical($reflection->getMethods(), ['aMethod', 'extraMethod']);
        $this->assertIdentical($reflection->getInterfaceMethods(), ['aMethod']);
    }

    public function testMethodsComeFromDescendentInterfacesInAbstractClass(): void
    {
        $reflection = new SimpleReflection('AnyAbstractImplementation');
        $this->assertIdentical($reflection->getMethods(), ['aMethod']);
    }

    public function testInterfaceHasOnlyItselfToImplement(): void
    {
        $reflection = new SimpleReflection('AnyOldInterface');
        $this->assertEqual(
            $reflection->getInterfaces(),
            ['AnyOldInterface'],
        );
    }

    public function testInterfacesListedForClass(): void
    {
        $reflection = new SimpleReflection('AnyOldImplementation');
        $this->assertEqual(
            $reflection->getInterfaces(),
            ['AnyOldInterface'],
        );
    }

    public function testInterfacesListedForSubclass(): void
    {
        $reflection = new SimpleReflection('AnyOldSubclass');
        $this->assertEqual(
            $reflection->getInterfaces(),
            ['AnyOldInterface'],
        );
    }

    public function testNoParameterCreationWhenNoInterface(): void
    {
        $reflection = new SimpleReflection('AnyOldArgumentClass');
        $function   = $reflection->getSignature('aMethod');
        $this->assertEqual('public function aMethod($argument): void', $function);
    }

    public function testParameterCreationWithoutTypeHinting(): void
    {
        $reflection = new SimpleReflection('AnyOldArgumentImplementation');
        $function   = $reflection->getSignature('aMethod');
        $this->assertEqual('public function aMethod(\AnyOldInterface $argument): void', $function);
    }

    public function testParameterCreationForTypeHinting(): void
    {
        $reflection = new SimpleReflection('AnyOldTypeHintedClass');
        $function   = $reflection->getSignature('aMethod');
        $this->assertEqual('public function aMethod(\AnyOldInterface $argument): void', $function);
    }

    public function testIssetFunctionSignature(): void
    {
        $reflection = new SimpleReflection('AnyOldOverloadedClass');
        $function   = $reflection->getSignature('__isset');
        $this->assertEqual('public function __isset($key)', $function);
    }

    public function testUnsetFunctionSignature(): void
    {
        $reflection = new SimpleReflection('AnyOldOverloadedClass');
        $function   = $reflection->getSignature('__unset');
        $this->assertEqual('public function __unset($key): void', $function);
    }

    public function testProperlyReflectsTheFinalInterfaceWhenObjectImplementsAnExtendedInterface(): void
    {
        $reflection = new SimpleReflection('AnyDescendentImplementation');
        $interfaces = $reflection->getInterfaces();
        $this->assertEqual(1, \count($interfaces));
        $this->assertEqual('AnyDescendentInterface', \array_shift($interfaces));
    }

    public function testCreatingSignatureForAbstractMethod(): void
    {
        $reflection = new SimpleReflection('AnotherOldAbstractClass');
        $this->assertEqual(
            $reflection->getSignature('aMethod'),
            // non abstract method - with body
            'protected function aMethod(\AnyOldInterface $argument): void',
        );
    }

    public function testCanProperlyGenerateStaticMethodSignatures(): void
    {
        $reflection = new SimpleReflection('AnyOldClassWithStaticMethods');
        $this->assertEqual('public static function aStatic(): void', $reflection->getSignature('aStatic'));
        $this->assertEqual(
            'public static function aStaticWithParameters($arg1, $arg2): void',
            $reflection->getSignature('aStaticWithParameters'),
        );
    }
}

class TestOfReflectionWithTypeHints extends UnitTestCase
{
    public function testParameterCreationForTypeHintingWithArray(): void
    {
        eval('interface AnyOldArrayTypeHintedInterface {
				  function amethod(array $argument);
			  }
			  class AnyOldArrayTypeHintedClass implements AnyOldArrayTypeHintedInterface {
				  function amethod(array $argument) {}
			  }');
        $reflection = new SimpleReflection('AnyOldArrayTypeHintedClass');
        $function   = $reflection->getSignature('amethod');
        $this->assertEqual('public function amethod(array $argument)', $function);
    }
}

/**
 * Abstract method's are public or protected.
 *
 * @see http://php.net/manual/en/language.oop5.abstract.php
 */
class TestOfAbstractsWithAbstractMethods extends UnitTestCase
{
    public function testCanProperlyGenerateAbstractMethods(): void
    {
        $reflection = new SimpleReflection('AnyOldAbstractClassWithAbstractMethods');
        $this->assertEqual(
            'abstract public function anAbstract()',
            $reflection->getSignature('anAbstract'),
        );
        $this->assertEqual(
            'abstract public function anAbstractWithParameter($foo)',
            $reflection->getSignature('anAbstractWithParameter'),
        );
        $this->assertEqual(
            'abstract public function anAbstractWithMultipleParameters($foo, $bar)',
            $reflection->getSignature('anAbstractWithMultipleParameters'),
        );
    }
}
