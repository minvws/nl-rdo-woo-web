<?php

class SomeClass
{
    /**
     * @param array{key: array<string>} $items
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
     * @param array{key: array<array-key, string>} $items
     */
    public function doSomething(array $items): void
    {
    }
}
