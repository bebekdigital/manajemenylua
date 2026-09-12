<?php

use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\AdministrationController;
use App\Http\Controllers\StudentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Dashboard');
});

Route::get('/students', [StudentController::class, 'index'])->name('students.index');
Route::get('/students/template', [StudentController::class, 'downloadTemplate'])->name('students.template');
Route::post('/students/import', [StudentController::class, 'import'])->name('students.import');

// Administrasi & Cetak Berkas Siswa
Route::get('/administration', [AdministrationController::class, 'index'])->name('administration.index');
Route::get('/administration/identity-document', [AdministrationController::class, 'identityDocument'])->name('administration.identity-document');
Route::get('/administration/identity-document/template', [AdministrationController::class, 'editTemplate'])->name('administration.template.edit');
Route::post('/administration/identity-document/template', [AdministrationController::class, 'updateTemplate'])->name('administration.template.update');
Route::post('/administration/identity-document/template/reset', [AdministrationController::class, 'resetTemplate'])->name('administration.template.reset');

Route::post('/academic-years/switch', function (Request $request) {
    $validated = $request->validate([
        'academic_year_id' => 'required|exists:academic_years,id',
    ]);

    session(['selected_academic_year_id' => $validated['academic_year_id']]);

    return back();
})->name('academic-years.switch');

// Portal Management
Route::get('/portal', [AcademicYearController::class, 'index'])->name('portal.index');
Route::post('/portal/academic-years', [AcademicYearController::class, 'store'])->name('portal.academic-years.store');
Route::put('/portal/academic-years/{academicYear}', [AcademicYearController::class, 'update'])->name('portal.academic-years.update');
Route::delete('/portal/academic-years/{academicYear}', [AcademicYearController::class, 'destroy'])->name('portal.academic-years.destroy');
Route::post('/portal/academic-years/{academicYear}/set-active', [AcademicYearController::class, 'setActive'])->name('portal.academic-years.set-active');
