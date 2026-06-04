<?php

class SomeClass
{
    /**
     * @param array<string>&\ArrayAccess $items
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
     * @param array<array-key, string>&\ArrayAccess $items
     */
    public function doSomething(array $items): void
    {
    }
}
