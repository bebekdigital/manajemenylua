<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\Carbon;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'role', 'is_active', 'email', 'password', 'nipy', 'status_kepegawaian', 'jk', 'ttl', 'unit', 'jabatan', 'no_wa', 'foto'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Determine if the user has a teacher (Guru) role or position.
     */
    public function isTeacher(): bool
    {
        $role = strtolower(trim((string) $this->role));
        $jabatan = strtolower(trim((string) $this->jabatan));

        if ($role === 'superadmin') {
            return false;
        }

        return $role === 'guru'
            || str_contains($role, 'guru')
            || $role === 'wali kelas'
            || str_contains($role, 'wali kelas')
            || $jabatan === 'guru'
            || str_contains($jabatan, 'guru')
            || $jabatan === 'wali kelas'
            || str_contains($jabatan, 'wali kelas');
    }

    /**
     * Get the employee profile corresponding to this user account.
     */
    public function employeeProfile(): ?Employee
    {
        if (! empty($this->nipy)) {
            $emp = Employee::where('nipy', $this->nipy)->first();
            if ($emp) {
                return $emp;
            }
        }

        if (! empty($this->email)) {
            $emp = Employee::where('email', $this->email)->first();
            if ($emp) {
                return $emp;
            }
        }

        $emp = Employee::where('nama', $this->name)->first();
        if ($emp) {
            return $emp;
        }

        return Employee::all()->first(function (Employee $e) {
            return $e->nama_lengkap_gelar === $this->name || $e->nama === $this->name;
        });
    }

    /**
     * Get default password generated from user account (tanggal lahir YYYYMMDD).
     */
    public function getDefaultPassword(?Employee $matchedEmployee = null): ?string
    {
        $emp = $matchedEmployee ?? $this->employeeProfile();
        if ($emp && $emp->tanggal_lahir) {
            return $emp->tanggal_lahir->format('Ymd');
        }

        if (! empty($this->ttl)) {
            if (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $this->ttl, $matches)) {
                return $matches[1].$matches[2].$matches[3];
            }
            if (preg_match('/(\d{2})[- \/](\d{2})[- \/](\d{4})/', $this->ttl, $matches)) {
                return $matches[3].$matches[2].$matches[1];
            }
            try {
                $parsed = Carbon::parse($this->ttl);
                if ($parsed && $parsed->year > 1900 && $parsed->year < 2100) {
                    return $parsed->format('Ymd');
                }
            } catch (\Throwable) {
                // ignore
            }
        }

        return null;
    }

    /**
     * Tentukan apakah user berwenang menyetujui / memberi approval pada perizinan pegawai.
     * Aturan: Hanya Atasan Langsung sesuai hierarki diagram susunan organisasi dan Superadmin.
     */
    public function canApprovePermitFor(Employee $employee): bool
    {
        // 1. Superadmin atau Admin selalu berhak
        if (in_array(strtolower((string) $this->role), ['superadmin', 'admin'])) {
            return true;
        }

        // 2. Pegawai tidak dapat menyetujui izin atas namanya sendiri
        $myProfile = $this->employeeProfile();
        if ($myProfile && (int) $myProfile->id === (int) $employee->id) {
            return false;
        }

        // 3. Cari atasan langsung pegawai yang mengajukan izin
        $supervisorInfo = $employee->getDirectSupervisorPosition();
        $parentPosName = $supervisorInfo['parent_position_name'];
        $parentDeptName = $supervisorInfo['parent_department_name'];

        if (! $parentPosName) {
            return false;
        }

        $parentNameClean = strtolower(trim($parentPosName));
        $parentDeptClean = strtolower(trim((string) $parentDeptName));

        $userRole = strtolower(trim((string) $this->role));
        $userJabatan = strtolower(trim((string) $this->jabatan));
        $userUnit = strtolower(trim((string) $this->unit));

        $isGlobalDept = in_array($parentDeptClean, ['yayasan', 'badan pelaksana', '']);

        $roleMatch = ($userRole === $parentNameClean || str_contains($userRole, $parentNameClean) ||
                      $userJabatan === $parentNameClean || str_contains($userJabatan, $parentNameClean));

        $unitMatch = $isGlobalDept || (
            $userUnit === $parentDeptClean ||
            str_contains($userUnit, $parentDeptClean) ||
            str_contains($parentDeptClean, $userUnit)
        );

        if ($roleMatch && $unitMatch) {
            return true;
        }

        // 4. Periksa juga riwayat posisi di profil pegawai milik user jika ada
        if ($myProfile) {
            $myPositions = $myProfile->positions;
            foreach ($myPositions as $pos) {
                $posTitle = strtolower(trim((string) $pos->jabatan));
                $posUnit = strtolower(trim((string) $pos->unit_kerja));

                $pMatch = ($posTitle === $parentNameClean || str_contains($posTitle, $parentNameClean));
                $uMatch = $isGlobalDept || (
                    $posUnit === $parentDeptClean ||
                    str_contains($posUnit, $parentDeptClean) ||
                    str_contains($parentDeptClean, $posUnit)
                );

                if ($pMatch && $uMatch) {
                    return true;
                }
            }
        }

        return false;
    }
}
