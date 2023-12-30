<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Concentrator extends Model {
	protected $table = 'islim_concentrators';

	protected $fillable = [
        'id',
        'name',
        'rfc',
        'email',
        'dni',
        'business_name',
        'phone',
        'address',
        'balance',
        'commissions',
        'date_reg',
        'status',
        'postpaid',
        'amount_alert',
        'amount_allocate',
        'id_channel'
    ];
    
    public $timestamps = false;
}