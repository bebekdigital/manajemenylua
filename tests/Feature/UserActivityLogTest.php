<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserActivityLogTest extends TestCase
{
    protected User $superadmin;

    protected User $guru;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Schema::dropAllTables();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('username')->unique()->nullable();
            $table->string('role')->default('Guru');
            $table->boolean('is_active')->default(true);
            $table->string('email')->unique()->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('must_change_password')->default(false);
            $table->string('nipy')->nullable();
            $table->string('status_kepegawaian')->nullable();
            $table->string('jk')->nullable();
            $table->string('ttl')->nullable();
            $table->string('unit')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('no_wa')->nullable();
            $table->string('foto')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('academic_years', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('semester')->default('Ganjil');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->string('nisn')->nullable();
            $table->string('nama');
            $table->string('jk')->nullable();
            $table->string('unit')->nullable();
            $table->string('unit_pesantren')->nullable();
            $table->string('jenjang')->nullable();
            $table->string('program')->nullable();
            $table->string('angkatan')->nullable();
            $table->string('status_keaktifan')->nullable();
            $table->string('status_registrasi')->nullable();
            $table->date('tanggal_diterima')->nullable();
            $table->string('sekolah_asal')->nullable();
            $table->string('npsn')->nullable();
            $table->string('dusun')->nullable();
            $table->string('desa')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kabupaten')->nullable();
            $table->string('info_psb')->nullable();
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->string('nipy')->nullable();
            $table->string('nama')->nullable();
            $table->string('email')->nullable();
            $table->string('jk')->nullable();
            $table->string('status_keaktifan')->nullable();
            $table->string('unit_keaktifan_dapodik')->nullable();
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('role');
            $table->string('feature_group');
            $table->string('feature_name')->nullable();
            $table->boolean('can_view')->default(false);
            $table->boolean('can_create')->default(false);
            $table->boolean('can_update')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->boolean('can_export')->default(false);
            $table->boolean('can_import')->default(false);
            $table->timestamps();
        });

        Schema::create('employee_permits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('user_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('user_name', 120)->nullable();
            $table->string('user_role', 80)->nullable();
            $table->string('action', 40);
            $table->string('module', 60);
            $table->string('menu_label', 100);
            $table->string('path', 255)->nullable();
            $table->string('description', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        $this->superadmin = User::create([
            'name' => 'Superadmin Uji',
            'username' => 'superadminuji',
            'email' => 'super@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->guru = User::create([
            'name' => 'Ustadz Ahmad',
            'username' => 'ustadzahmad',
            'email' => 'ahmad@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'Guru',
            'is_active' => true,
        ]);
    }

    public function test_only_superadmin_can_view_and_prune_activity_logs(): void
    {
        $this->get('/activity-logs')->assertRedirect('/');

        $this->actingAs($this->guru)->get('/activity-logs')->assertForbidden();
        $this->actingAs($this->guru)->delete('/activity-logs/prune')->assertForbidden();

        $this->actingAs($this->superadmin)
            ->get('/activity-logs')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ActivityLogs/Index')
                ->where('tableReady', true)
                ->where('retentionDays', 7)
            );
    }

    public function test_login_and_menu_visits_are_logged_with_session_cooldown(): void
    {
        $this->post('/login', [
            'user_id' => 'ustadzahmad',
            'password' => 'secret123',
        ])->assertRedirect('/beranda');

        $this->assertDatabaseHas('user_activity_logs', [
            'user_id' => $this->guru->id,
            'action' => 'login',
            'module' => 'auth',
        ]);

        // Kunjungi Pemetaan PSB dua kali berturut-turut dalam sesi yang sama -> tercatat 1x (cooldown 5 menit)
        $this->actingAs($this->guru)->get('/psb/ringkasan')->assertOk();
        $this->actingAs($this->guru)->get('/psb/ringkasan')->assertOk();

        // Kunjungi menu lain sebagai pembanding
        $this->actingAs($this->guru)->get('/beranda')->assertOk();

        $psbVisits = UserActivityLog::where('user_id', $this->guru->id)
            ->where('module', 'psb')
            ->where('action', 'page_visit')
            ->count();
        $berandaVisits = UserActivityLog::where('user_id', $this->guru->id)
            ->where('module', 'beranda')
            ->where('action', 'page_visit')
            ->count();

        $this->assertSame(1, $psbVisits);
        $this->assertSame(1, $berandaVisits);
    }

    public function test_psb_event_endpoint_tracks_mode_and_export(): void
    {
        $this->actingAs($this->guru)
            ->postJson('/activity-logs/psb-event', [
                'event' => 'mode_welfare',
                'mode' => 'welfare',
            ])
            ->assertOk()
            ->assertJson(['recorded' => true]);

        $this->actingAs($this->guru)
            ->postJson('/activity-logs/psb-event', [
                'event' => 'export_excel',
                'mode' => 'welfare',
            ])
            ->assertOk()
            ->assertJson(['recorded' => true]);

        $this->assertDatabaseHas('user_activity_logs', [
            'user_id' => $this->guru->id,
            'action' => 'psb_mode_welfare',
            'module' => 'psb',
        ]);
        $this->assertDatabaseHas('user_activity_logs', [
            'user_id' => $this->guru->id,
            'action' => 'psb_export_excel',
            'module' => 'psb',
        ]);
    }

    public function test_logs_older_than_seven_days_are_automatically_pruned(): void
    {
        UserActivityLog::create([
            'user_id' => $this->guru->id,
            'user_name' => $this->guru->name,
            'user_role' => $this->guru->role,
            'action' => 'page_visit',
            'module' => 'psb',
            'menu_label' => 'Pemetaan PSB',
            'path' => '/psb/ringkasan',
            'description' => 'Log lama 8 hari',
            'created_at' => now()->subDays(8),
        ]);

        UserActivityLog::create([
            'user_id' => $this->guru->id,
            'user_name' => $this->guru->name,
            'user_role' => $this->guru->role,
            'action' => 'page_visit',
            'module' => 'psb',
            'menu_label' => 'Pemetaan PSB',
            'path' => '/psb/ringkasan',
            'description' => 'Log baru 2 hari',
            'created_at' => now()->subDays(2),
        ]);

        $this->actingAs($this->superadmin)
            ->get('/activity-logs')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ActivityLogs/Index')
                ->where('summary.psb_visits', 1)
                ->where('summary.total_rows_7d', 1)
            );

        $this->assertSame(1, UserActivityLog::count());
        $this->assertDatabaseHas('user_activity_logs', ['description' => 'Log baru 2 hari']);
        $this->assertDatabaseMissing('user_activity_logs', ['description' => 'Log lama 8 hari']);
    }
}
