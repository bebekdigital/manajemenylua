<?php

use App\Http\Controllers\AcademicYearClassController;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\AdministrationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PembelajaranController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SchoolClassController;
use App\Http\Controllers\SchoolLevelController;
use App\Http\Controllers\SchoolProgramController;
use App\Http\Controllers\SchoolUnitController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentMappingController;
use App\Http\Controllers\UserController;
use App\Models\StudentAcademicRecord;
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

    Route::get('/students/mapping', [StudentMappingController::class, 'index'])->name('students.mapping.index');
    Route::post('/students/mapping', [StudentMappingController::class, 'store'])->name('students.mapping.store');

    // DEBUG ROUTE
    Route::get('/debug-mapping', function () {
        return StudentAcademicRecord::all();
    });
    Route::get('/api/students/mapping/classes', [StudentMappingController::class, 'getClasses'])->name('students.mapping.classes');
    Route::get('/api/students/mapping/students', [StudentMappingController::class, 'getStudents'])->name('students.mapping.students');
    Route::post('/students/upload-photos', [StudentController::class, 'uploadPhotos'])->name('students.upload-photos');
    Route::get('/students/photo/{filename}', [StudentController::class, 'showPhoto'])->name('students.photo');
    Route::get('/students/{nisn}', [StudentController::class, 'show'])->name('students.show');

    // Statistik
    Route::get('/statistics', [StatisticsController::class, 'index'])->name('statistics.index');

    // Data Pegawai
    Route::get('/staff', [EmployeeController::class, 'index'])->name('staff.index');
    Route::get('/staff/template', [EmployeeController::class, 'downloadTemplate'])->name('staff.template');
    Route::post('/staff/import', [EmployeeController::class, 'import'])->name('staff.import');
    Route::get('/staff/{nipy}', [EmployeeController::class, 'show'])->name('staff.show');

    // Administrasi & Cetak Berkas Siswa
    Route::get('/administration', [AdministrationController::class, 'index'])->name('administration.index');
    Route::get('/administration/identity-document', [AdministrationController::class, 'identityDocument'])->name('administration.identity-document');
    Route::get('/administration/identity-document/template', [AdministrationController::class, 'editTemplate'])->name('administration.template.edit');
    Route::post('/administration/identity-document/template', [AdministrationController::class, 'updateTemplate'])->name('administration.template.update');
    Route::post('/administration/identity-document/template/reset', [AdministrationController::class, 'resetTemplate'])->name('administration.template.reset');
    Route::get('/administration/student-photos', [AdministrationController::class, 'studentPhotos'])->name('administration.student-photos');

    // Pembelajaran
    Route::get('/pembelajaran/kelas', [PembelajaranController::class, 'classrooms'])->name('pembelajaran.classrooms');
    Route::post('/pembelajaran/kelas/add-students', [PembelajaranController::class, 'addStudents'])->name('pembelajaran.classrooms.add-students');

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
    Route::get('/portal/academic-years/{id}/active-classes', [AcademicYearClassController::class, 'getActiveClasses'])->name('portal.academic-years.active-classes');

    // Portal Management - Users (Superadmin only inside controller)
    Route::post('/portal/users', [UserController::class, 'store'])->name('portal.users.store');
    Route::put('/portal/users/{user}', [UserController::class, 'update'])->name('portal.users.update');
    Route::delete('/portal/users/{user}', [UserController::class, 'destroy'])->name('portal.users.destroy');

    // Portal Management - School Levels and Units
    Route::post('/portal/school-levels', [SchoolLevelController::class, 'store'])->name('portal.school-levels.store');
    Route::put('/portal/school-levels/{schoolLevel}', [SchoolLevelController::class, 'update'])->name('portal.school-levels.update');
    Route::delete('/portal/school-levels/{schoolLevel}', [SchoolLevelController::class, 'destroy'])->name('portal.school-levels.destroy');

    Route::post('/portal/school-levels/{schoolLevel}/units', [SchoolUnitController::class, 'store'])->name('portal.school-units.store');
    Route::put('/portal/school-units/{schoolUnit}', [SchoolUnitController::class, 'update'])->name('portal.school-units.update');
    Route::delete('/portal/school-units/{schoolUnit}', [SchoolUnitController::class, 'destroy'])->name('portal.school-units.destroy');

    Route::post('/portal/school-units/{schoolUnit}/programs', [SchoolProgramController::class, 'store'])->name('portal.school-programs.store');
    Route::put('/portal/school-programs/{schoolProgram}', [SchoolProgramController::class, 'update'])->name('portal.school-programs.update');
    Route::delete('/portal/school-programs/{schoolProgram}', [SchoolProgramController::class, 'destroy'])->name('portal.school-programs.destroy');

    Route::post('/portal/school-units/{schoolUnit}/classes', [SchoolClassController::class, 'store'])->name('portal.school-classes.store');
    Route::put('/portal/school-classes/{schoolClass}', [SchoolClassController::class, 'update'])->name('portal.school-classes.update');
    Route::delete('/portal/school-classes/{schoolClass}', [SchoolClassController::class, 'destroy'])->name('portal.school-classes.destroy');
});
