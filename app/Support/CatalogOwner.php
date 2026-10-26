<?php

namespace App\Support;

use App\Models\User;

class CatalogOwner
{
    public static function id(): ?int
    {
        return User::query()
            ->where('is_admin', true)
            ->orderBy('id')
            ->value('id');
    }
}
