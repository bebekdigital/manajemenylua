<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'role', 'email', 'password', 'nipy', 'status_kepegawaian', 'jk', 'ttl', 'unit', 'jabatan', 'no_wa', 'foto'])]
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

        return Employee::where('nama', $this->name)
            ->orWhere('nama_lengkap_gelar', $this->name)
            ->first();
    }
}
