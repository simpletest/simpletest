<?php
    // $Id$
    
    interface DummyInterface {
        function aMethod();
        function anotherMethod($a);
        function &referenceMethod(&$a);
    }
    
    class Hinter {
        function hinted(DummyInterface $object) { }
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
    
    class TestOfTypeHints {
        function testMockedInterfaceCanPassThroughTypeHint() {
            $mock = new MockDummyInterface();
            $hinter = new Hinter();
            $hinter->hinted($mock);
        }
    }
?>