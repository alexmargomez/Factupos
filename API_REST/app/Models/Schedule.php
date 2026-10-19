<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $table = 'Schedules';
    protected $fillable = [
        'servicios',
        'customer_id',
        'vehicle_id',
        'state',
        'worker_id',
    ];

    protected $casts = [
        'servicios' => 'array',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function worker()
    {
        return $this->belongsTo(Worker::class, 'worker_id');
    }
}
