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
            $this->assertEqual($methods[0], 'aMethod');
        }
    }
?>