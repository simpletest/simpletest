<?php declare(strict_types=1);

require_once __DIR__ . '/../../../src/autorun.php';

require_once __DIR__ . '/../../treemap_reporter.php';

class TestOfTreemapDataTypes extends UnitTestCase
{
    public function testEmptyRootNode(): void
    {
        $node = new TreemapNode('test', 'test graph');
        $this->assertEqual($node->getSize(), 0);
        $this->assertEqual($node->getTotalSize(), 0);
    }

    public function testChildNodeDepth(): void
    {
        $root = new TreemapNode('root', 'test');
        $root->putChild(new TreemapNode('child', 'test'));
        $childOne = new TreemapNode('child1', 'test');
        $childTwo = new TreemapNode('child2', 'test');
        $childTwo->putChild(new TreemapNode('child3', 'test'));
        $childOne->putChild($childTwo);
        $root->putChild($childOne);
        $this->assertEqual($root->getSize(), 2);
        $this->assertEqual($root->getTotalSize(), 4);
    }

    public function testGraphDepthSpread(): void
    {
        $root = new TreemapNode('root', 'test');
        $root->putChild(new TreemapNode('child', 'test'));
        $childOne   = new TreemapNode('child1', 'test');
        $childTwo   = new TreemapNode('child2', 'test');
        $childThree = new TreemapNode('child3', 'test');
        $childFour  = new TreemapNode('child4', 'test');
        $childFive  = new TreemapNode('child5', 'test');
        $childSix   = new TreemapNode('child6', 'test');
        $childFour->putChild($childFive);
        $childFour->putChild($childSix);
        $this->assertEqual($childFour->getSize(), 2);
        $this->assertEqual($childFour->getTotalSize(), 2);
        $childThree->putChild($childFour);
        $this->assertEqual($childThree->getSize(), 1);
        $this->assertEqual($childThree->getTotalSize(), 3);
        $childTwo->putChild($childThree);
        $this->assertEqual($childTwo->getSize(), 1);
        $this->assertEqual($childTwo->getTotalSize(), 4);
        $childOne->putChild($childTwo);
        $root->putChild($childOne);
        $this->assertEqual($root->getSize(), 2);
        $this->assertEqual($root->getTotalSize(), 7);
    }

    public function testMutableStack(): void
    {
        $stack = new TreemapStack;
        $this->assertEqual($stack->size(), 0);
        $stack->push(new TreemapNode('a', 'one'));
        $this->assertEqual($stack->size(), 1);
        $stack->push(new TreemapNode('b', 'one'));
        $this->assertIdentical($stack->peek(), new TreemapNode('b', 'one'));
        $stack->push(new TreemapNode('c', 'three'));
        $stack->push(new TreemapNode('d', 'four'));
        $this->assertEqual($stack->size(), 4);
        $this->assertIdentical($stack->pop(), new TreemapNode('d', 'four'));
        $this->assertEqual($stack->size(), 3);
        $this->assertIdentical($stack->pop(), new TreemapNode('c', 'three'));
        $this->assertEqual($stack->size(), 2);
    }
}
