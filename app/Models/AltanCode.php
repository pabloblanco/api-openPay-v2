<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Utilities\Common;

class AltanCode extends Model
{
    protected $table = 'islim_altan_codes';

	protected $fillable = [
        'services_id', 'codeAltan', 'supplementary', 'status'
    ];

    public $timestamps = false;

    public static function getCode($service = false, $broadband = false, $serviceability = false, $isChange = true, $forceNS = false){
        if($service && $broadband && $serviceability){
            $isSupp = 'N';

            if(Common::compareWide($serviceability, $broadband, true) && $isChange && !$forceNS){
                $isSupp = 'Y';
            }

            return AltanCode::select('codeAltan', 'supplementary')
                              ->where([
                                ['services_id', $service],
                                ['supplementary', $isSupp],
                                ['status', 'A']
                              ])
                              ->first();
        }

        return null;
    }
}