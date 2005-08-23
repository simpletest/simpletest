<?php
    // $Id$
    
    class AnyOldThing {
        function aMethod() {
        }
    }

    class TestOfReflection extends UnitTestCase {
        
        function testClassExistence() {
            $this->assertTrue(SimpleReflection::classOrInterfaceExists('AnyOldThing'));
            $this->assertFalse(SimpleReflection::classOrInterfaceExists('UnknownThing'));
            $this->assertTrue(SimpleReflection::classOrInterfaceExistsSansAutoload('AnyOldThing'));
            $this->assertFalse(SimpleReflection::classOrInterfaceExistsSansAutoload('UnknownThing'));
        }
        
        function testMethodsListFromClass() {
            $methods = SimpleReflection::getMethods(new AnyOldThing());
            $this->assertEqualIgnoringCase($methods[0], 'aMethod');
        }
        
        function testNoInterfacesForPHP4() {
        	$this->assertEqual(
        			SimpleReflection::getInterfaces('AnyOldThing'),
        			array());
        }
        
        function testMostGeneralPossibleSignature() {
        	$ths->assertEqualIgnoringCase(
        			SimpleReflection::getSignature('AnyOldThing', 'aMethod'),
        			'function &aMethod()');
        }
        
        function assertEqualIgnoringCase($a, $b) {
            return $this->assertEqual(strtolower($a), strtolower($b));
        }
    }
?>