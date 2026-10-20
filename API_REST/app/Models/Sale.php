<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sale extends Model
{
    use HasFactory;
    
    protected $table = 'Sales';
    protected $fillable = [
        'customer_id',
        'vehicle_id',
        'total',
        'payment_method',
        'payment_status',
        'paid_amount',
        'pending_balance',
        'payment_notes',
        'worker_id',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function saleDetails()
    {
        return $this->hasMany(SaleDetail::class, 'sale_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'sale_id');
    }

    public function worker()
    {
        return $this->belongsTo(Worker::class, 'worker_id');
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'sale_id');
    }
}
