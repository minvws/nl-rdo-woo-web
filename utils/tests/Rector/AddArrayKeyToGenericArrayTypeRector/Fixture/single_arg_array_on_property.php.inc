<?php

class SomeClass
{
    /**
     * @var array<string>
     */
    public array $items = [];
}
-----
<?php

class SomeClass
{
    /**
     * @var array<array-key, string>
     */
    public array $items = [];
}
