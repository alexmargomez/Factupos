<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SaleDetail extends Model
{
    use HasFactory;
    
    protected $table = 'Sale_details';
    protected $fillable = [ 'sale_id', 'product_id', 'quantity', 'price_total', 'customer_id'];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
