<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Dosen
        User::create([
            'name'      => 'Dosen Pembimbing',
            'email'     => 'dosen@mail.com',
            'password'  => Hash::make('password'),
            'role'      => 'dosen',
            'nim_nidn'  => '1234567890',
            'jurusan'   => 'Teknik Informatika',
            'prodi'     => 'S1 Informatika',
        ]);

        // Mahasiswa
        User::create([
            'name'      => 'Zikri H.M',
            'email'     => 'mahasiswa@mail.com',
            'password'  => Hash::make('password'),
            'role'      => 'mahasiswa',
            'nim_nidn'  => '2110510020',
            'jurusan'   => 'Teknik Informatika',
            'prodi'     => 'S1 Informatika',
        ]);

        User::create([
            'name'      => 'admin Demo',
            'email'     => 'admin@mail.com',
            'password'  => Hash::make('password'),
            'role'      => 'mahasiswa',
            'nim_nidn'  => '222223333',
            'jurusan'   => 'Teknik Informatika',
            'prodi'     => 'S1 Informatika',
        ]);
    
    }


}

