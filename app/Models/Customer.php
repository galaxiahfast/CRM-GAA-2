<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'last_name',
        'maternal_last_name',
        'email',
        'rfc',
        'phone',
        'address',
        'observation',
        'codePhone',
        'url_photo',
        'created_by',
    ];

    public function accountants()
    {
        return $this->belongsToMany(User::class, 'customer_accountants', 'customer_id', 'accountant_id')
            ->withPivot('status')
            ->withTimestamps();
    }
    public function services()
    {
        return $this->belongsToMany(SubService::class, 'customer_services', 'customer_id', 'sub_service_id')
            ->withTimestamps();
    }
    public function files()
    {
        return $this->belongsToMany(SubService::class, 'customer_files', 'customer_id', 'sub_service_id')
            ->withPivot('id', 'file_path', 'sub_service_id', 'file_type', 'declaration_type', 'statement_id', 'state_id', 'sub_service_id', 'upload_period')
            ->withTimestamps();
    }
    public function states()
    {
        return $this->belongsToMany(State::class, 'customer_states', 'customer_id', 'state_id')
            ->withTimestamps();
    }
    public function statements()
    {
        return $this->belongsToMany(Statement::class, 'customer_statements', 'customer_id', 'statement_id')
            ->withTimestamps();
    }
    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }
}
