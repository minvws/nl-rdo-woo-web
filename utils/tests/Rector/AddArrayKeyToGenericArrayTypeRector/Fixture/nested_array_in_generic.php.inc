<?php

class SomeClass
{
    /**
     * @param array<string, array<int>> $items
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
     * @param array<string, array<array-key, int>> $items
     */
    public function doSomething(array $items): void
    {
    }
}
