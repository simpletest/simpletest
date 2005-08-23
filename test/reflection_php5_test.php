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
            $this->assertTrue(SimpleReflection::classOrInterfaceExists('AnyOldClass'));
            $this->assertFalse(SimpleReflection::classOrInterfaceExists('UnknownClass'));
            $this->assertTrue(SimpleReflection::classOrInterfaceExistsSansAutoload('AnyOldClass'));
            $this->assertFalse(SimpleReflection::classOrInterfaceExistsSansAutoload('UnknownClass'));
        }
        
        function testInterfaceExistence() {
            $this->assertTrue(
            		SimpleReflection::classOrInterfaceExists('AnyOldInterface'));
            $this->assertTrue(
            		SimpleReflection::classOrInterfaceExistsSansAutoload('AnyOldInterface'));
        }
        
        function testMethodsListFromClass() {
            $methods = SimpleReflection::getMethods('AnyOldClass');
            $this->assertEqual($methods[0], 'aMethod');
        }
        
        function testMethodsListFromInterface() {
            $methods = SimpleReflection::getMethods('AnyOldInterface');
            $this->assertEqual($methods[0], 'aMethod');
        }
        
        function testInterfaceHasOnlyItselfToImplement() {
        	$this->assertEqual(
        			SimpleReflection::getInterfaces('AnyOldInterface'),
        			array('AnyOldInterface'));
        }
        
        function testInterfacesListedForClass() {
        	$this->assertEqual(
        			SimpleReflection::getInterfaces('AnyOldImplementation'),
        			array('AnyOldInterface'));
        }
        
        function testInterfacesListedForSubclass() {
        	$this->assertEqual(
        			SimpleReflection::getInterfaces('AnyOldSubclass'),
        			array('AnyOldInterface'));
        }
    }
?>