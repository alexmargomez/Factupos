<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'Customers';
    protected $fillable = ['id', 'name', 'email', 'phone'];

    public function sales()
    {
        return $this->hasMany(Sale::class, 'customer_id');
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class, 'customer_id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'customer_id');
    }
}
