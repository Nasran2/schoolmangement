<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $attributes = ['active' => true];

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function landingRoute(): string
    {
        if ($this->hasRole('Developer')) {
            return 'developer.dashboard';
        }

        $destinations = [
            'dashboard.view' => 'dashboard',
            'teacher_evaluations.view' => 'teacher-evaluations.index',
            'hr.directory.view' => 'hr.teachers.index',
            'students.manage' => 'students.index',
            'students.add' => 'students.create',
            'teachers.manage' => 'teachers.index',
            'teachers.add' => 'teachers.create',
            'revenue.manage' => 'revenue.items.index',
            'revenue.add' => 'revenue.items.create',
            'expense.manage' => 'expense.items.index',
            'expense.add' => 'expense.items.create',
            'owner_transfers.manage' => 'owner-transfers.index',
            'classrooms.view' => 'classrooms.index',
            'class_fees.view' => 'class-fees.index',
            'classrooms.create' => 'classrooms.create',
            'seminars.manage' => 'seminars.index',
            'extra_classes.manage' => 'extra-classes.index',
            'visiting_teachers.manage' => 'visiting-teachers.index',
            'teachers.salary.pay' => 'teacher-salary-payments.index',
            'teachers.salary.summary.view' => 'teacher-salary-payments.summary',
            'reports.view' => 'reports.index',
            'users.manage' => 'users.index',
            'roles.manage' => 'rbac.roles.index',
            'settings.manage' => 'settings.general.edit',
            'settings.general.manage' => 'settings.general.edit',
            'settings.status.view' => 'settings.status.index',
            'settings.promotion.manage' => 'settings.promotion.edit',
            'settings.printer.manage' => 'settings.printer.edit',
            'settings.sms.manage' => 'settings.sms.edit',
            'settings.email.manage' => 'settings.email.edit',
            'settings.salary_components.manage' => 'settings.salary-components.edit',
            'settings.backups.manage' => 'settings.backups.index',
            'settings.opening_balance.manage' => 'settings.opening-balance.edit',
            'audit_logs.view' => 'audit_logs.index',
        ];
        foreach ($destinations as $permission => $route) {
            if ($this->can($permission)) {
                return $route;
            }
        }

        return 'profile.edit';
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
            'active' => 'boolean',
        ];
    }
}
