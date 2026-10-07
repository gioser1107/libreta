<?php

namespace Database\Seeders;

use App\Support\Permissions;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        Permissions::syncCatalog();
    }
}
