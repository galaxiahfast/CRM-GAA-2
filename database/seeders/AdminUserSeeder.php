<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table("users")->insert([
            'name' => 'admin',
            'email' => 'admin@datamid.com.mx',
            'password'=> Hash::make('Datamid2025.'),
            'role_id' => 1
        ]);
    }
}
