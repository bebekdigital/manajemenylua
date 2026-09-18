<?php

use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\AdministrationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Auth/Login');
})->name('login');

// TEMPORARY ROUTE: Untuk setup superadmin tanpa terminal
Route::get('/setup-superadmin', function () {
    try {
        // 1. Paksa jalankan migrasi
        Artisan::call('migrate', ['--force' => true]);

        // 2. Buat akun superadmin jika belum ada
        $user = User::updateOrCreate(
            ['username' => 'fathudinmahmud'],
            [
                'name' => 'Fathudin Mahmud',
                'email' => 'fathudinmahmud@admin.com',
                'password' => Hash::make('Terusberkarya100@'),
                'role' => 'superadmin',
            ]
        );

        // 3. Ambil semua data user untuk di-audit
        $allUsers = User::all();

        return response()->json([
            'status' => 'Sukses!',
            'pesan' => 'Migrasi berhasil dijalankan dan akun superadmin telah dibuat.',
            'superadmin_dibuat' => $user,
            'audit_semua_user' => $allUsers,
        ]);
    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});

Route::post('/login', [AuthController::class, 'login'])->name('login.post');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/beranda', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo');

    Route::get('/students', [StudentController::class, 'index'])->name('students.index');
    Route::get('/students/inline-edit', [StudentController::class, 'inlineEdit'])->name('students.inline-edit');
    Route::post('/students/inline-update', [StudentController::class, 'inlineUpdate'])->name('students.inline-update');
    Route::get('/students/template', [StudentController::class, 'downloadTemplate'])->name('students.template');
    Route::post('/students/import', [StudentController::class, 'import'])->name('students.import');
    Route::get('/students/{nisn}', [StudentController::class, 'show'])->name('students.show');

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

    // Portal Management - Users (Superadmin only inside controller)
    Route::post('/portal/users', [UserController::class, 'store'])->name('portal.users.store');
    Route::put('/portal/users/{user}', [UserController::class, 'update'])->name('portal.users.update');
    Route::delete('/portal/users/{user}', [UserController::class, 'destroy'])->name('portal.users.destroy');
});
