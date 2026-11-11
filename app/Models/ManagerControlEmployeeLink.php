<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManagerControlEmployeeLink extends Model
{
    protected $table = 'managercontrol_employee_links';

    protected $fillable = ['user_id', 'legacy_employee_id', 'legacy_employee_name'];

    protected $casts = ['legacy_employee_id' => 'integer'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
