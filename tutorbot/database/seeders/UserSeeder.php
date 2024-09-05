<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Cursos;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $administrador = User::create([
            'username' => 'admin',
            'rut' => '11111111-1',
            'fecha_nacimiento' => Carbon::parse('07-09-2000')->toDate(),
            'firstname' => 'Admin',
            'lastname' => 'Admin',
            'email' => 'admin@tutorbot.com',
            'password' => 'admin'
        ]);
        $estudiante = User::create([
            'username' => 'estudiante',
            'rut' => '22222222-2',
            'fecha_nacimiento' => Carbon::parse('07-09-2001')->toDate(),
            'firstname' => 'Estudiante',
            'lastname' => 'Estudiante',
            'email' => 'estudiante@tutorbot.com',
            'password' => 'estudiante'
        ]);
        $profesor = User::create([
            'username' => 'profesor',
            'rut' => '33333333-3',
            'fecha_nacimiento' => Carbon::parse('07-09-2002')->toDate(),
            'firstname' => 'Profesor',
            'lastname' => 'Profesor',
            'email' => 'profesor@tutorbot.com',
            'password' => 'profesor'
        ]);
        $administrador->assignRole('administrador');
        $profesor->assignRole('profesor');
        $estudiante->assignRole('estudiante');

        $curso = Cursos::first();
        $administrador->cursos()->save($curso);
        $profesor->cursos()->save($curso);
        $estudiante->cursos()->save($curso);

    }
}
