<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Inventory extends Model
{
    use HasFactory;

    protected $table = 'Inventory';
    protected $fillable = ['stock', 'product_id'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class, 'inventory_id');
    }
}
