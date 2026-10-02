<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use Inertia\Inertia;
use Inertia\Response;

class PembelajaranController extends Controller
{
    public function classrooms(): Response
    {
        $classrooms = Classroom::orderBy('jenjang')
            ->orderBy('name')
            ->get()
            ->map(fn (Classroom $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'jenjang' => $c->jenjang,
                'unit' => $c->unit,
                'tingkat' => $c->tingkat,
            ]);

        return Inertia::render('Pembelajaran/Classrooms', [
            'classrooms' => $classrooms,
        ]);
    }
}
