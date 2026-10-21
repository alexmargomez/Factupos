<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Worker extends Model
{
    use HasFactory;

    protected $table = 'Workers';
    protected $fillable = ['name', 'totalDia', 'commission_percentage'];

    public function sales()
    {
        return $this->hasMany(Sale::class, 'worker_id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'worker_id');
    }

    public function settlements()
    {
        return $this->hasMany(WorkerSettlement::class, 'worker_id');
    }
}
