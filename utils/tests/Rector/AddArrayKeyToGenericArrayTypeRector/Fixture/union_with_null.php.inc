<?php

class SomeClass
{
    /**
     * @return array<string>|null
     */
    public function getItems(): ?array
    {
        return null;
    }
}
-----
<?php

class SomeClass
{
    /**
     * @return array<array-key, string>|null
     */
    public function getItems(): ?array
    {
        return null;
    }
}
