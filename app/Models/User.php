<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            $user->vehicles()->each(fn ($v) => $v->delete());
            $user->homeAssets()->each(fn ($ha) => $ha->delete());
            $user->documents()->each(fn ($doc) => $doc->delete());
            $user->damageReports()->each(fn ($dr) => $dr->delete());
            $user->inspectionRuns()->each(fn ($ir) => $ir->delete());
            $user->fuelLogs()->each(fn ($fl) => $fl->delete());
            $user->maintenanceRecords()->each(fn ($mr) => $mr->delete());
            $user->schedules()->each(fn ($s) => $s->delete());
            $user->budgets()->delete();
            $user->activityLogs()->delete();
            $user->attachments()->each(fn ($att) => $att->delete());
        });
    }

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
     * @return HasMany<Vehicle, $this>
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * @return HasMany<HomeAsset, $this>
     */
    public function homeAssets(): HasMany
    {
        return $this->hasMany(HomeAsset::class);
    }

    /**
     * @return HasMany<MaintenanceSchedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(MaintenanceSchedule::class);
    }

    /**
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * @return HasMany<DamageReport, $this>
     */
    public function damageReports(): HasMany
    {
        return $this->hasMany(DamageReport::class);
    }

    /**
     * @return HasMany<InspectionRun, $this>
     */
    public function inspectionRuns(): HasMany
    {
        return $this->hasMany(InspectionRun::class);
    }

    /**
     * @return HasMany<FuelLog, $this>
     */
    public function fuelLogs(): HasMany
    {
        return $this->hasMany(FuelLog::class);
    }

    /**
     * @return HasMany<MaintenanceRecord, $this>
     */
    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class);
    }

    /**
     * @return HasMany<Budget, $this>
     */
    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    /**
     * @return HasMany<Attachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * @return HasMany<ActivityLog, $this>
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
