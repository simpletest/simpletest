<?php declare(strict_types=1);

require_once __DIR__ . '/../src/autorun.php';

require_once __DIR__ . '/../src/expectation.php';

class TestOfEquality extends UnitTestCase
{
    public function testBoolean(): void
    {
        $is_true = new EqualExpectation(true);
        $this->assertTrue($is_true->test(true));
        $this->assertFalse($is_true->test(false));
    }

    public function testStringMatch(): void
    {
        $hello = new EqualExpectation('Hello');
        $this->assertTrue($hello->test('Hello'));
        $this->assertFalse($hello->test('Goodbye'));
    }

    public function testInteger(): void
    {
        $fifteen = new EqualExpectation(15);
        $this->assertTrue($fifteen->test(15));
        $this->assertFalse($fifteen->test(14));
    }

    public function testFloat(): void
    {
        $pi = new EqualExpectation(3.14);
        $this->assertTrue($pi->test(3.14));
        $this->assertFalse($pi->test(3.15));
    }

    public function testArray(): void
    {
        $colours = new EqualExpectation(['r', 'g', 'b']);
        $this->assertTrue($colours->test(['r', 'g', 'b']));
        $this->assertFalse($colours->test(['g', 'b', 'r']));
    }

    public function testHash(): void
    {
        $is_blue = new EqualExpectation(['r' => 0, 'g' => 0, 'b' => 255]);
        $this->assertTrue($is_blue->test(['r' => 0, 'g' => 0, 'b' => 255]));
        $this->assertFalse($is_blue->test(['r' => 0, 'g' => 255, 'b' => 0]));
    }

    public function testHashWithOutOfOrderKeysShouldStillMatch(): void
    {
        $any_order = new EqualExpectation(['a' => 1, 'b' => 2]);
        $this->assertTrue($any_order->test(['b' => 2, 'a' => 1]));
    }
}

class TestOfWithin extends UnitTestCase
{
    public function testWithinFloatingPointMargin(): void
    {
        $within = new WithinMarginExpectation(1.0, 0.2);
        $this->assertFalse($within->test(0.7));
        $this->assertTrue($within->test(0.8));
        $this->assertTrue($within->test(0.9));
        $this->assertTrue($within->test(1.1));
        $this->assertTrue($within->test(1.2));
        $this->assertFalse($within->test(1.3));
    }

    public function testOutsideFloatingPointMargin(): void
    {
        $within = new OutsideMarginExpectation(1.0, 0.2);
        $this->assertTrue($within->test(0.7));
        $this->assertFalse($within->test(0.8));
        $this->assertFalse($within->test(1.2));
        $this->assertTrue($within->test(1.3));
    }
}

class TestOfInequality extends UnitTestCase
{
    public function testStringMismatch(): void
    {
        $not_hello = new NotEqualExpectation('Hello');
        $this->assertTrue($not_hello->test('Goodbye'));
        $this->assertFalse($not_hello->test('Hello'));
    }
}

class RecursiveNasty
{
    private $me;

    public function __construct()
    {
        $this->me = $this;
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

class DerivedOpaqueContainer extends OpaqueContainer
{
    // Deliberately have a variable whose name with the same suffix as a later
    // variable
    private $new_value = 1;

    // Deliberately obscures the variable of the same name in the base
    // class.
    private $value;

    public function __construct($value, $base_value)
    {
        parent::__construct($base_value);
        $this->value = $value;
    }
}

class TestOfIdentity extends UnitTestCase
{
    public function testType(): void
    {
        $string = new IdenticalExpectation('37');
        $this->assertTrue($string->test('37'));
        $this->assertFalse($string->test(37));
        $this->assertFalse($string->test('38'));
    }

    public function _testNastyPhp5Bug(): void
    {
        $this->assertFalse(new RecursiveNasty != new RecursiveNasty);
    }

    public function _testReallyHorribleRecursiveStructure(): void
    {
        $hopeful = new IdenticalExpectation(new RecursiveNasty);
        $this->assertTrue($hopeful->test(new RecursiveNasty));
    }

    public function testCanComparePrivateMembers(): void
    {
        $expectFive = new IdenticalExpectation(new OpaqueContainer(5));
        $this->assertTrue($expectFive->test(new OpaqueContainer(5)));
        $this->assertFalse($expectFive->test(new OpaqueContainer(6)));
    }

    public function testCanComparePrivateMembersOfObjectsInArrays(): void
    {
        $expectFive = new IdenticalExpectation([new OpaqueContainer(5)]);
        $this->assertTrue($expectFive->test([new OpaqueContainer(5)]));
        $this->assertFalse($expectFive->test([new OpaqueContainer(6)]));
    }

    public function testCanComparePrivateMembersOfObjectsWherePrivateMemberOfBaseClassIsObscured(): void
    {
        $expectFive = new IdenticalExpectation([new DerivedOpaqueContainer(1, 2)]);
        $this->assertTrue($expectFive->test([new DerivedOpaqueContainer(1, 2)]));
        $this->assertFalse($expectFive->test([new DerivedOpaqueContainer(0, 2)]));
        $this->assertFalse($expectFive->test([new DerivedOpaqueContainer(0, 9)]));
        $this->assertFalse($expectFive->test([new DerivedOpaqueContainer(1, 0)]));
    }
}

class TransparentContainer
{
    public $value;

    public function __construct($value)
    {
        $this->value = $value;
    }
}

class TestOfMemberComparison extends UnitTestCase
{
    public function testMemberExpectationCanMatchPublicMember(): void
    {
        $expect_five = new MemberExpectation('value', 5);
        $this->assertTrue($expect_five->test(new TransparentContainer(5)));
        $this->assertFalse($expect_five->test(new TransparentContainer(8)));
    }

    public function testMemberExpectationCanMatchPrivateMember(): void
    {
        $expect_five = new MemberExpectation('value', 5);
        $this->assertTrue($expect_five->test(new OpaqueContainer(5)));
        $this->assertFalse($expect_five->test(new OpaqueContainer(8)));
    }

    public function testMemberExpectationCanMatchPrivateMemberObscuredByDerivedClass(): void
    {
        $expect_five = new MemberExpectation('value', 5);
        $this->assertTrue($expect_five->test(new DerivedOpaqueContainer(5, 8)));
        $this->assertTrue($expect_five->test(new DerivedOpaqueContainer(5, 5)));
        $this->assertFalse($expect_five->test(new DerivedOpaqueContainer(8, 8)));
        $this->assertFalse($expect_five->test(new DerivedOpaqueContainer(8, 5)));
    }
}

class DummyReferencedObject
{
}

class TestOfReference extends UnitTestCase
{
    public function testReference(): void
    {
        $foo     = 'foo';
        $ref     = &$foo;
        $not_ref = $foo;
        $bar     = 'bar';

        $expect = new ReferenceExpectation($foo);
        $this->assertTrue($expect->test($ref));
        $this->assertFalse($expect->test($not_ref));
        $this->assertFalse($expect->test($bar));
    }
}

class TestOfNonIdentity extends UnitTestCase
{
    public function testType(): void
    {
        $string = new NotIdenticalExpectation('37');
        $this->assertTrue($string->test('38'));
        $this->assertTrue($string->test(37));
        $this->assertFalse($string->test('37'));
    }
}

class TestOfPatterns extends UnitTestCase
{
    public function testWanted(): void
    {
        $pattern = new PatternExpectation('/hello/i');
        $this->assertTrue($pattern->test('Hello world'));
        $this->assertFalse($pattern->test('Goodbye world'));
    }

    public function testUnwanted(): void
    {
        $pattern = new NoPatternExpectation('/hello/i');
        $this->assertFalse($pattern->test('Hello world'));
        $this->assertTrue($pattern->test('Goodbye world'));
    }
}

class ExpectedMethodTarget
{
    public function hasThisMethod(): void
    {
    }
}

class TestOfMethodExistence extends UnitTestCase
{
    public function testHasMethod(): void
    {
        $instance    = new ExpectedMethodTarget;
        $expectation = new MethodExistsExpectation('hasThisMethod');
        $this->assertTrue($expectation->test($instance));
        $expectation = new MethodExistsExpectation('doesNotHaveThisMethod');
        $this->assertFalse($expectation->test($instance));
    }
}

class TestOfIsA extends UnitTestCase
{
    public function testString(): void
    {
        $expectation = new IsAExpectation('string');
        $this->assertTrue($expectation->test('Hello'));
        $this->assertFalse($expectation->test(5));
    }

    public function testBoolean(): void
    {
        $expectation = new IsAExpectation('boolean');
        $this->assertTrue($expectation->test(true));
        $this->assertFalse($expectation->test(1));
    }

    public function testBool(): void
    {
        $expectation = new IsAExpectation('bool');
        $this->assertTrue($expectation->test(true));
        $this->assertFalse($expectation->test(1));
    }

    public function testDouble(): void
    {
        $expectation = new IsAExpectation('double');
        $this->assertTrue($expectation->test(5.0));
        $this->assertFalse($expectation->test(5));
    }

    public function testFloat(): void
    {
        $expectation = new IsAExpectation('float');
        $this->assertTrue($expectation->test(5.0));
        $this->assertFalse($expectation->test(5));
    }

    public function testInteger(): void
    {
        $expectation = new IsAExpectation('integer');
        $this->assertTrue($expectation->test(5));
        $this->assertFalse($expectation->test(5.0));
    }

    public function testInt(): void
    {
        $expectation = new IsAExpectation('int');
        $this->assertTrue($expectation->test(5));
        $this->assertFalse($expectation->test(5.0));
    }

    public function testScalar(): void
    {
        $expectation = new IsAExpectation('scalar');
        $this->assertTrue($expectation->test(5));
        $this->assertFalse($expectation->test([5]));
    }

    public function testNumeric(): void
    {
        $expectation = new IsAExpectation('numeric');
        $this->assertTrue($expectation->test(5));
        $this->assertFalse($expectation->test('string'));
    }

    public function testNull(): void
    {
        $expectation = new IsAExpectation('null');
        $this->assertTrue($expectation->test(null));
        $this->assertFalse($expectation->test('string'));
    }
}

class TestOfNotA extends UnitTestCase
{
    public function testString(): void
    {
        $expectation = new NotAExpectation('string');
        $this->assertFalse($expectation->test('Hello'));
        $this->assertTrue($expectation->test(5));
    }
}
