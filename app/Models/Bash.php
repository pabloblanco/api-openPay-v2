<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bash extends Model
{
    protected $table = 'islim_bash';

	protected $fillable = [
        'id',
        'action',
        'unlook',
        'date_begin'
    ];

    public $timestamps = false;

    public static function isActive(){
        $data = self::where('action', 'open-pay')->first();

        if(empty($data)){
            self::insert([
                'action' => 'open-pay',
                'unlook' => 'N',
                'date_begin' => null
            ]); 

            return false;
        }else{
            if($data->unlook == 'N'){
                return false;
            }
        }

        return true;
    }

    public static function active(){
        self::where('action', 'open-pay')->update(['unlook' => 'Y', 'date_begin' => date('Y-m-d H:i:s')]);
        return true;
    }

    public static function inactive(){
        self::where('action', 'open-pay')->update(['unlook' => 'N', 'date_begin' => null]);
        return true;
    }

    public static function getBash(){
        return self::where('action', 'open-pay')->first();
    }
}