<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invoice extends Model
{
    use HasFactory;
    
    protected $table = 'Invoices';
    protected $fillable = ['sale_id', 'invoice_number'];

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!empty($model->invoice_number)) {
                return;
            }
            $lastInvoice = static::orderBy('id', 'desc')->first();
            $lastInvoiceNumber = 0;
            if ($lastInvoice && !empty($lastInvoice->invoice_number)) {
                $parts = explode('-', $lastInvoice->invoice_number);
                $lastInvoiceNumber = intval(end($parts));
            }
            $newInvoiceNumber = $lastInvoiceNumber + 1;
            $date = date('Ymd'); // Fecha actual en formato YYYYMMDD
            $model->invoice_number = $date . '-' . str_pad($newInvoiceNumber, 6, '0', STR_PAD_LEFT);
        });
    }
}

