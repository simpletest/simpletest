<?php
    // $Id$
    
    interface DummyInterface {
        function aMethod();
        function anotherMethod($a);
        function &referenceMethod(&$a);
    }
    
    Mock::generate('DummyInterface');
    Mock::generatePartial('DummyInterface', 'PartialDummyInterface', array());
    
    class TestOfMockInterfaces extends UnitTestCase {
        
        function testCanMockAnInterface() {
            $mock = new MockDummyInterface();
            $this->assertIsA($mock, 'SimpleMock');
            $this->assertIsA($mock, 'MockDummyInterface');
            $this->assertTrue(method_exists($mock, 'aMethod'));
            $this->assertNull($mock->aMethod());
        }
        
        function testMockedInterfaceExpectsParameters() {
            $mock = new MockDummyInterface();
            $mock->anotherMethod();
            $this->assertError();
        }
        
        function testCannotPartiallyMockAnInterface() {
            $this->assertFalse(class_exists('PartialDummyInterface'));
        }
    }
    
    class Hinter {
        function hinted(DummyInterface $object) { }
    }
    
    class ImplementsDummy implements DummyInterface {
        function aMethod() { }
        function anotherMethod($a) { }
        function &referenceMethod(&$a) { }
    }
    
    Mock::generate('ImplementsDummy');
    
    class TestOfTypeHints extends UnitTestCase {
    	
        function testMockedInterfaceCanPassThroughTypeHint() {
            $mock = new MockDummyInterface();
            $hinter = new Hinter();
            $hinter->hinted($mock);
        }
        
        function testImplementedInterfacesAreCarried() {
            $mock = new MockImplementsDummy();
            $hinter = new Hinter();
            $hinter->hinted($mock);
        }
    }
?>