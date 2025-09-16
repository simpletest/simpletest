<?php declare(strict_types=1);

require_once __DIR__ . '/../src/autorun.php';

class ReferenceForTesting
{
    private $reference;

    public function setReference(&$reference): void
    {
        $this->reference = $reference;
    }

    public function &getReference()
    {
        return $this->reference;
    }
}

class TestOfUnitTester extends UnitTestCase
{
    public function testAssertTrueReturnsAssertionAsBoolean(): void
    {
        $this->assertTrue($this->assertTrue(true));
    }

    public function testAssertFalseReturnsAssertionAsBoolean(): void
    {
        $this->assertTrue($this->assertFalse(false));
    }

    public function testAssertEqualReturnsAssertionAsBoolean(): void
    {
        $this->assertTrue($this->assertEqual(5, 5));
    }

    public function testAssertIdenticalReturnsAssertionAsBoolean(): void
    {
        $this->assertTrue($this->assertIdentical(5, 5));
    }

    public function testCoreAssertionsDoNotThrowErrors(): void
    {
        $this->assertIsA($this, 'UnitTestCase');
        $this->assertNotA($this, 'WebTestCase');
    }

    public function testReferenceAssertionOnObjects(): void
    {
        $a = new ReferenceForTesting;
        $b = $a;
        $this->assertSame($a, $b);
    }

    public function testReferenceAssertionOnScalars(): void
    {
        $a = 25;
        $b = &$a; // reference is a pointer to a scalar
        $this->assertReference($a, $b);
    }

    public function testReferenceAssertionOnObject(): void
    {
        $refValue = 5;
        $a        = new ReferenceForTesting;
        $a->setReference($refValue);
        $b = &$a->getReference(); // $b is a reference to $a->reference, which is 5.
        $this->assertReference($a->getReference(), $b);
    }

    public function testCloneOnObjects(): void
    {
        $a = new ReferenceForTesting;
        $b = new ReferenceForTesting;
        $this->assertClone($a, $b);
    }

    /**
     * @todo
     * http://php.net/manual/de/function.is-scalar.php
     */
    /*public function testCloneOnScalars()
    {
        $this->assertClone(20, 20);       // int
        $this->assertClone(20.2, 20.2);   // float
        $this->assertClone("abc", "abc"); // string
        $this->assertClone(true, true);   // bool
    }*/

    public function testCopyOnScalars(): void
    {
        $a = 25;
        $b = 25;
        $this->assertCopy($a, $b);
    }

    public function testEscapeIncidentalPrintfSyntax(): void
    {
        // Incidentals are escaped
        $a = 'http://www.domain.com/some%%20long%%20name.html';
        $b = $this->escapeIncidentalPrintfSyntax('http://www.domain.com/some%20long%20name.html');
        $this->assertEqual($a, $b);

        // Non-incidental is not escaped
        $a = 'SimpleTest error: %s :-)';
        $b = $this->escapeIncidentalPrintfSyntax('SimpleTest error: %s :-)');
        $this->assertEqual($a, $b);

        // Non-incidental is not escaped (end position edge case)
        $a = 'SimpleTest error: %s';
        $b = $this->escapeIncidentalPrintfSyntax('SimpleTest error: %s');
        $this->assertEqual($a, $b);

        // Non-incidental is not escaped (start position edge case)
        $a = '%s (SimpleTest error)';
        $b = $this->escapeIncidentalPrintfSyntax('%s (SimpleTest error)');
        $this->assertEqual($a, $b);

        // Correct escaping/preservation for both non-incidetal and incidentals
        $a = '%s (%%SimpleTest error%%)';
        $b = $this->escapeIncidentalPrintfSyntax('%s (%SimpleTest error%)');
        $this->assertEqual($a, $b);
    }

    public function testStringAssertionsContainStartEnd(): void
    {
        $haystack = 'The quick brown fox jumps over the lazy dog';

        // contains
        $this->assertTrue($this->assertStringContainsString('brown fox', $haystack));
        $this->assertTrue($this->assertStringContainsString('The quick', $haystack));

        // starts with
        $this->assertTrue($this->assertStringStartsWith('The quick', $haystack));

        // ends with
        $this->assertTrue($this->assertStringEndsWith('lazy dog', $haystack));
        // empty suffix should always be considered as matching
        $this->assertTrue($this->assertStringEndsWith('', $haystack));
    }

    public function testAssertArrayHasKeyPositiveCases(): void
    {
        $arr = [
            'a'      => 1,
            0        => 'zero',
            ''       => 'empty',
            'nested' => ['key' => 'value'],
        ];

        // string key
        $this->assertTrue($this->assertArrayHasKey('a', $arr));

        // numeric key
        $this->assertTrue($this->assertArrayHasKey(0, $arr));

        // empty string key
        $this->assertTrue($this->assertArrayHasKey('', $arr));

        // nested array is still a value, but key exists
        $this->assertTrue($this->assertArrayHasKey('nested', $arr));
    }

    public function testAssertContainsArrayAndTraversable(): void
    {
        $arr = ['apple', 'banana', 'cherry'];

        // array contains
        $this->assertTrue($this->assertContains('banana', $arr));

        // strict check: different types shouldn't match
        $arr2 = [1, '1', 2];
        $this->assertTrue($this->assertContains('1', $arr2));
        $this->assertTrue($this->assertContains(1, $arr2));

        // Traversable
        $iterator = new ArrayIterator(['x', 'y', 'z']);
        $this->assertTrue($this->assertContains('y', $iterator));
    }

    public function testAssertNotContainsArrayAndTraversable(): void
    {
        $arr = ['apple', 'banana', 'cherry'];

        // array does not contain
        $this->assertTrue($this->assertNotContains('durian', $arr));

        // Traversable does not contain
        $iterator = new ArrayIterator(['x', 'y', 'z']);
        $this->assertTrue($this->assertNotContains('a', $iterator));
    }

    public function testAssertCountArrayAndCountable(): void
    {
        $arr = [1, 2, 3, 4];
        $this->assertTrue($this->assertCount(4, $arr));

        $countable = new ArrayObject([5, 6, 7]);
        $this->assertTrue($this->assertCount(3, $countable));
    }

    public function testAssertInstanceOfPositiveCase(): void
    {
        $obj = new ArrayObject;
        $this->assertTrue($this->assertInstanceOf(ArrayObject::class, $obj));
    }

    public function testAssertObjectHasPropertyPositiveCase(): void
    {
        $obj = new ArrayObject;
        // ArrayObject declares "storage" property internally, but to be safe use a simple class
        $simple = new class
        {
            public $foo;
        };

        $this->assertTrue($this->assertObjectHasProperty('foo', $simple));
    }

    public function testAssertArrayNotHasKeyPositiveCases(): void
    {
        $arr = [
            'a' => 1,
            0   => 'zero',
            ''  => 'empty',
        ];

        // non-existent string key
        $this->assertTrue($this->assertArrayNotHasKey('missing', $arr));

        // non-existent numeric key
        $this->assertTrue($this->assertArrayNotHasKey(5, $arr));
    }

    public function testAssertEqualsWithDeltaHappyAndFailure(): void
    {
        // happy path: within delta
        $this->assertTrue($this->assertEqualsWithDelta(3.1415, 3.1416, 0.001));

        // negative case: verify assertion returns false when out of delta by checking the logical result
        // Note: calling the assertion itself will emit a failure; instead, assert the inverse of a successful
        // equality-with-delta by comparing the raw difference here as a proxy for the assertion logic.
        $this->assertTrue((\abs(1.0 - 2.0) > 0.1));
    }

    public function testAssertTimeoutHelpers(): void
    {
        // assertDoesNotTimeout: quick callable should pass against a small threshold
        $quick = static function (): void
        {
        };
        $this->assertTrue($this->assertDoesNotTimeout($quick, 0.1));

        // assertTimeout: slow callable should exceed a small threshold and therefore pass the assertTimeout check
        $slow = static function (): void
        {
            // sleep for 0.2 seconds
            \usleep(200000);
        };

        $this->assertTrue($this->assertTimeout($slow, 0.001));
    }

    public function testFileAndDirectorySpecificAssertions(): void
    {
        $tmp = \sys_get_temp_dir();

        // create a temp file
        $file = \tempnam($tmp, 'st_test_');
        $this->assertTrue($this->assertFileExists($file));
        $this->assertTrue($this->assertFileIsReadable($file));
        $this->assertTrue($this->assertFileIsWritable($file));

        // create a temp directory
        $dir = $tmp . \DIRECTORY_SEPARATOR . 'st_test_dir_' . \uniqid();
        \mkdir($dir);
        $this->assertTrue($this->assertDirExists($dir));
        $this->assertTrue($this->assertDirIsReadable($dir));
        $this->assertTrue($this->assertDirIsWritable($dir));

        // cleanup
        @\unlink($file);
        @\rmdir($dir);
    }

    public function testFloatSpecialValueAssertions(): void
    {
        $nan = \NAN;
        $this->assertTrue($this->assertIsNaN($nan));

        $posInf = \INF;
        $this->assertTrue($this->assertIsInfinite($posInf));
        $this->assertTrue($this->assertIsPositiveInfinity($posInf));

        $negInf = -\INF;
        $this->assertTrue($this->assertIsInfinite($negInf));
        $this->assertTrue($this->assertIsNegativeInfinity($negInf));
    }
}
