<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use \Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // $this->call(UsersTableSeeder::class);
        DB::table('users')->insert([
            'name'       => 'Игорь',
            'surname'    => 'Снежко',
            'email'      => 'i.s.sergeevich@yandex.ru',
            'password'   => Hash::make('Florida840905'),
            'phone'      => '+79213887398',
            'birthday'   => null,
            'country'   => null,
            'city'   => null,
            'active'   => true,
            'role' => 1,
            'created_at' => new DateTime(),
            'updated_at' => new DateTime()
        ]);
    }
}
