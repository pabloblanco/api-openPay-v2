<?php
namespace App\Utilities;

use App\Utilities\Common;
/*
	Clase que contiene metodos para conectarse con la API altan-netwey, se pueden utilizar desde cualqueir controlador
*/
class Altan{

	/*Retorna la servicialidad de un DN*/
	public static function getServiceability($lat = false, $lng = false){
		if($lat && $lng){
			$data = [
				'lat' => $lat,
				'lng' => $lng,
				'apiKey' => env('API_KEY_ALTAM')
			];
			
			//Consultan servicialidad en altan
			$res = Common::executeCurl(env('URL_ALTAM').'serviceability/', 'POST', [], $data);

			if($res['success'] && strtolower($res['data']->status) == 'success')
				return $res['data']->service;
		}

		return false;
	}

	/*Retorna el profile de un dn dado*/
	public static function getProfile($dn = false){
		if($dn && strlen($dn) == 10){
			$res = Common::executeCurl(
							env('URL_ALTAM').'profile/'.$dn,
							'POST',
							[],
							['apiKey' => env('API_KEY_ALTAM')]
						);
						
			if($res['success'] && strtolower($res['data']->status) == 'success')
				return $res['data']->msisdn;
		}

		return false;
	}

	/*Retorna el profile-nuevo de un dn dado*/
	public static function getQuickProfile($dn = false){
		if($dn && strlen($dn) == 10){
			$res = Common::executeCurl(
							env('URL_ALTAM').'quickProfile/'.$dn,
							'POST',
							[],
							['apiKey' => env('API_KEY_ALTAM')]
						);

			if($res['success'] && strtolower($res['data']->status) == 'success')
				return $res['data']->msisdn;
		}

		return false;
	}
}