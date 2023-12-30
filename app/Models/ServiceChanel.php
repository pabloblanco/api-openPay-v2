<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceChanel extends Model {
	protected $table = 'islim_service_channel';

	protected $fillable = [
		'id_channel',
		'id_concentrator',
		'id_list_dns',
		'id_service',
		'status',
		'date_reg'
	];
    
    public $timestamps = false;
}