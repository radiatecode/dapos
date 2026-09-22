<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = new User();

        $user->name = 'Super Admin';
        $user->email = 'superadmin@example.com';
        $user->password = Hash::make('password');
        $user->is_super_admin = true;
        $user->is_active = true;
        $user->timezone = 'Asia/Dhaka';
        $user->country = 'BD';
        $user->address = '123 Main St, Anytown, USA';
        $user->city = 'Anytown';
        $user->state = 'CA';
        $user->zip = '12345';
        
        $user->save();
    }
}
