<?php

class SomeClass
{
    /**
     * @param ?array<string> $items
     */
    public function doSomething(?array $items): void
    {
    }
}
-----
<?php

class SomeClass
{
    /**
     * @param ?array<array-key, string> $items
     */
    public function doSomething(?array $items): void
    {
    }
}
