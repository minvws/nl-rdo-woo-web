<?php

class SomeClass
{
    /**
     * @param array<array{nested: array<string>}> $items
     */
    public function doSomething(array $items): void
    {
    }
}
-----
<?php

class SomeClass
{
    /**
     * @param array<array-key, array{nested: array<array-key, string>}> $items
     */
    public function doSomething(array $items): void
    {
    }
}
