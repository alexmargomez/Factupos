<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $table = 'Inventory_movements';
    protected $fillable = ['inventory_id', 'movement_type', 'quantity', 'movement_date', 'reason'];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }
}
