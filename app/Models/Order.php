<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
     public function customer(): BelongsTo
     {
          return $this->belongsTo(Customer::class);
     }
     public function orderdetail(): HasMany
     {
          return $this->hasMany(OrderDetail::class);
     }

     protected $fillable = [
          'customer_id',
          'total_price',
          'date_order',
          'status',
          'discount',
          'discount_amount',
          'status_order',
          'total_payment'
     ];
}
