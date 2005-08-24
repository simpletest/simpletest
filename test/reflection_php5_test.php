<?php
    // $Id$
    
    class AnyOldClass {
        function aMethod() {
        }
    }

    interface AnyOldInterface {
        function aMethod();
    }
    
    class AnyOldImplementation implements AnyOldInterface {
    	function aMethod() { }
    }
    
    class AnyOldSubclass extends AnyOldIMplementation { }

    class TestOfReflection extends UnitTestCase {
        
        function testClassExistence() {
            $reflection = new SimpleReflection('AnyOldThing');
            $this->assertTrue($reflection->classOrInterfaceExists());
            $this->assertTrue($reflection->classOrInterfaceExistsSansAutoload());
        }
        
        function testClassNonExistence() {
            $reflection = new SimpleReflection('UnknownThing');
            $this->assertFalse($reflection->classOrInterfaceExists());
            $this->assertFalse($reflection->classOrInterfaceExistsSansAutoload(
        
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
    }
?>