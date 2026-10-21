<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'Products';
    protected $fillable = [
        'sku',
        'name',
        'cost_price',
        'price',
        'min_stock',
        'category_id',
        'supplier_id',
    ];

    public function saleDetails()
    {
        return $this->hasMany(SaleDetail::class, 'product_id');
    }

    public function inventory()
    {
        return $this->hasOne(Inventory::class, 'product_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
