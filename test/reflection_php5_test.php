<?php
    // $Id$

    abstract class AnyOldClass {
        function aMethod() {
        }
    }

    interface AnyOldInterface {
        function aMethod();
    }

    class AnyOldImplementation implements AnyOldInterface {
    	function aMethod() { }
    }

    class AnyOldSubclass extends AnyOldImplementation { }

	class AnyOldArgumentClass {
		function aMethod($argument) { }
	}

	class AnyOldTypeHintedClass {
		function aMethod(SimpleTest $argument) { }
	}

    class TestOfReflection extends UnitTestCase {

        function testClassExistence() {
            $reflection = new SimpleReflection('AnyOldClass');
            $this->assertTrue($reflection->classOrInterfaceExists());
            $this->assertTrue($reflection->classOrInterfaceExistsSansAutoload());
        }

        function testClassNonExistence() {
            $reflection = new SimpleReflection('UnknownThing');
            $this->assertFalse($reflection->classOrInterfaceExists());
            $this->assertFalse($reflection->classOrInterfaceExistsSansAutoload());
        }

        function testDetectionOfAbstractClass() {
            $reflection = new SimpleReflection('AnyOldClass');
            $this->assertTrue($reflection->isAbstract());
        }

        function testFindingParentClass() {
            $reflection = new SimpleReflection('AnyOldSubclass');
            $this->assertEqual($reflection->getParent(), 'AnyOldImplementation');
        }

        function testInterfaceExistence() {
            $reflection = new SimpleReflection('AnyOldInterface');
            $this->assertTrue(
            		$reflection->classOrInterfaceExists());
            $this->assertTrue(
            		$reflection->classOrInterfaceExistsSansAutoload());
        }

        function testMethodsListFromClass() {
            $reflection = new SimpleReflection('AnyOldClass');
            $methods = $reflection->getMethods();
            $this->assertEqual($methods[0], 'aMethod');
        }

        function testMethodsListFromInterface() {
            $reflection = new SimpleReflection('AnyOldInterface');
            $methods = $reflection->getMethods();
            $this->assertEqual($methods[0], 'aMethod');
        }

        function testInterfaceHasOnlyItselfToImplement() {
            $reflection = new SimpleReflection('AnyOldInterface');
        	$this->assertEqual(
        			$reflection->getInterfaces(),
        			array('AnyOldInterface'));
        }

        function testInterfacesListedForClass() {
            $reflection = new SimpleReflection('AnyOldImplementation');
        	$this->assertEqual(
        			$reflection->getInterfaces(),
        			array('AnyOldInterface'));
        }

        function testInterfacesListedForSubclass() {
            $reflection = new SimpleReflection('AnyOldSubclass');
        	$this->assertEqual(
        			$reflection->getInterfaces(),
        			array('AnyOldInterface'));
        }

		function testParameterCreationWithoutTypeHinting() {
			$reflection = new SimpleReflection('AnyOldArgumentClass');
			$function = $reflection->getSignature('aMethod');
			if (version_compare(phpversion(), '5.0.2', '<=')) {
			    $this->assertEqual('function amethod($argument)', $function);
    	    } else {
			    $this->assertEqual('function aMethod($argument)', $function);
    	    }
		}

		function testParameterCreationForTypeHinting() {
			$reflection = new SimpleReflection('AnyOldTypeHintedClass');
			$function = $reflection->getSignature('aMethod');
			if (version_compare(phpversion(), '5.0.2', '<=')) {
			    $this->assertEqual('function amethod(SimpleTest $argument)', $function);
    	    } else {
			    $this->assertEqual('function aMethod(SimpleTest $argument)', $function);
    	    }
		}
    }
?>