<?php

declare(strict_types=1);

namespace App\Message;

use App\Entity\Department;

class UpdateDepartmentMessage
{
    protected Department $old;
    protected Department $new;

    public function __construct(Department $old, Department $new)
    {
        $this->old = $old;
        $this->new = $new;
    }

    public function getOld(): Department
    {
        return $this->old;
    }

    public function getNew(): Department
    {
        return $this->new;
    }
}
