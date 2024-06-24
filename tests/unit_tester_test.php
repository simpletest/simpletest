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
}
