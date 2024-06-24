<?php declare(strict_types=1);

require_once __DIR__ . '/../src/autorun.php';

require_once __DIR__ . '/../src/compatibility.php';

class ComparisonClass
{
}
class ComparisonSubclass extends ComparisonClass
{
}
interface ComparisonInterface
{
}
class ComparisonClassWithInterface implements ComparisonInterface
{
}

class TestOfCompatibility extends UnitTestCase
{
    public function testIdentityOfNumericStrings(): void
    {
        $numericString1 = '123';
        $numericString2 = '00123';
        $this->assertNotIdentical($numericString1, $numericString2);
    }

    public function testIdentityOfObjects(): void
    {
        $object1 = new ComparisonClass;
        $object2 = new ComparisonClass;
        $this->assertIdentical($object1, $object2);
    }

    public function testReferences(): void
    {
        $thing           = 'Hello';
        $thing_reference = &$thing;
        $thing_copy      = $thing;
        $this->assertTrue(SimpleTestCompatibility::isReference($thing, $thing));
        // $this->assertFalse(SimpleTestCompatibility::isReference($thing, $thing_reference)); // fails
        $this->assertFalse(SimpleTestCompatibility::isReference($thing, $thing_copy));
    }

    public function testObjectReferences(): void
    {
        $object            = new ComparisonClass;
        $object_reference  = &$object;
        $object_copy       = clone $object;
        $object_assignment = &$object;
        $this->assertTrue(SimpleTestCompatibility::isReference(
            $object,
            $object,
        ));
        $this->assertTrue(SimpleTestCompatibility::isReference(
            $object,
            $object_reference,
        ));
        $this->assertFalse(SimpleTestCompatibility::isReference(
            $object,
            $object_copy,
        ));
        $this->assertTrue(SimpleTestCompatibility::isReference(
            $object,
            $object_assignment,
        ));
    }

    public function testInteraceComparison(): void
    {
        $object = new ComparisonClassWithInterface;
        $this->assertFalse(\is_a(new ComparisonClass, 'ComparisonInterface'));
        $this->assertTrue(\is_a(new ComparisonClassWithInterface, 'ComparisonInterface'));
    }
}
