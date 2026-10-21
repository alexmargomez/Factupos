<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkerSettlement extends Model
{
    use HasFactory;

    protected $table = 'WorkerSettlements';

    protected $fillable = [
        'worker_id',
        'worker_name',
        'gross_amount',
        'commission_percentage',
        'paid_amount',
        'workshop_share',
        'settlement_date',
        'notes',
    ];

    public function worker()
    {
        return $this->belongsTo(Worker::class, 'worker_id');
    }
}
