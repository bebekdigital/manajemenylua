<?php

use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\AdministrationController;
use App\Http\Controllers\StudentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return Inertia::render('Auth/Login');
})->name('login');

// TEMPORARY ROUTE: Untuk setup superadmin tanpa terminal
Route::get('/setup-superadmin', function () {
    try {
        // 1. Paksa jalankan migrasi
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        
        // 2. Buat akun superadmin jika belum ada
        $user = \App\Models\User::updateOrCreate(
            ['username' => 'fathudinmahmud'],
            [
                'name' => 'Fathudin Mahmud',
                'email' => 'fathudinmahmud@admin.com',
                'password' => \Illuminate\Support\Facades\Hash::make('Terusberkarya100@'),
                'role' => 'superadmin',
            ]
        );

        // 3. Ambil semua data user untuk di-audit
        $allUsers = \App\Models\User::all();

        return response()->json([
            'status' => 'Sukses!',
            'pesan' => 'Migrasi berhasil dijalankan dan akun superadmin telah dibuat.',
            'superadmin_dibuat' => $user,
            'audit_semua_user' => $allUsers
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});

Route::post('/login', [AuthController::class, 'login'])->name('login.post');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

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
});
