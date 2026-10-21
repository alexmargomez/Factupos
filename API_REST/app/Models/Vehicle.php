<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vehicle extends Model
{
    use HasFactory;

    protected $table = 'Vehicles';
    protected $fillable = [
        'plate',
        'model',
        'make',
        'customer_id',
        'current_mileage',
        'next_oil_change_km',
        'soat_due_date',
        'tecno_due_date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'vehicle_id');
    }
}
