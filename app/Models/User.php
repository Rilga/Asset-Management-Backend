<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'no_telp',
        'role',
        'password',
    ];

    public function retrievalLogs()
    {
        return $this->hasMany(RetrievalLog::class);
    }

    public function chatHistories()
    {
        return $this->hasMany(ChatHistory::class);
    }

    public function repairReports()
    {
        return $this->hasMany(RepairReport::class, 'mechanic_id');
    }

    public function repairRequests()
    {
        return $this->hasMany(RepairRequest::class, 'mechanic_id');
    }

    public function verifiedRepairRequests()
    {
        return $this->hasMany(RepairRequest::class, 'verified_by');
    }

    public function maintenanceReports()
    {
        return $this->hasMany(MaintenanceReport::class, 'mechanic_id');
    }

    public function operatingHours()
    {
        return $this->hasMany(OperatingHour::class);
    }

    public function assignedMaintenanceTasks()
    {
        return $this->hasMany(MaintenanceTask::class, 'assigned_to');
    }

    public function createdMaintenanceTasks()
    {
        return $this->hasMany(MaintenanceTask::class, 'assigned_by');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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
}
