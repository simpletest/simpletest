<?php declare(strict_types=1);

class IteratorImplementation implements Iterator
{
    #[ReturnTypeWillChange]
    public function current(): void
    {
    }

    #[ReturnTypeWillChange]
    public function next(): void
    {
    }

    #[ReturnTypeWillChange]
    public function key(): void
    {
    }

    #[ReturnTypeWillChange]
    public function valid(): void
    {
    }

    #[ReturnTypeWillChange]
    public function rewind(): void
    {
    }
}

class IteratorAggregateImplementation implements IteratorAggregate
{
    #[ReturnTypeWillChange]
    public function getIterator(): void
    {
    }
}
