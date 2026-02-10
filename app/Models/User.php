<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_STAFF = 'staff';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'role',
        'permissions',
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
            'permissions' => 'array',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($permission === 'users.manage') {
            return false;
        }

        $permissions = $this->permissions ?? [];

        return ! empty($permissions[$permission]);
    }

    public static function availablePermissions(): array
    {
        return [
            'consoles.manage' => 'مدیریت کنسول‌ها',
            'tables.manage' => 'مدیریت میزها',
            'board_games.manage' => 'مدیریت بردگیم‌ها',
            'customers.manage' => 'مدیریت مشتریان',
            'cafe_items.manage' => 'مدیریت منوی کافه',
            'console_sessions.manage' => 'مدیریت سشن‌های کنسول',
            'table_sessions.manage' => 'مدیریت سشن‌های میز',
            'board_game_sessions.manage' => 'مدیریت سشن‌های بردگیم',
            'orders.manage' => 'مدیریت سفارشات کافه',
            'invoices.manage' => 'مدیریت فاکتورها',
            'invoices.delete' => 'حذف فاکتور',
            'pricing_plans.manage' => 'مدیریت طرح‌های قیمتی',
        ];
    }
}
