<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Create the local development administrator.
     *
     * Production must create its administrator through a secure process instead.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        User::updateOrCreate(
            ['email' => 'cable8mm@gmail.com'],
            [
                'name' => 'Filmo Admin',
                'password' => '1234',
            ],
        );
    }
}
