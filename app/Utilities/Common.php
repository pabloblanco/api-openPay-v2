<?php
namespace App\Utilities;

/*Clase que contiene metodos "varios" que se pueden utilizar desde cualqueir controlador*/
class Common{

	/*Retorna el dn - ultimos 10 digitos de una referencia enviada desde open pay*/
	public static function getNumberFromReference($reference = false){
		if($reference){
			return substr($reference, -10);
		}

		return false;
	}

	/*Limpia los decimales de un string en caso de ser 0´s*/
	public static function cleanNumber($number = false){
		if($number){
			return strpos($number, '.') !== false ? (substr($number, strpos($number, '.') + 1) == 0 ?  substr($number, 0, strpos($number, '.')) : $number) : $number;
		}
		return 0;
	}

	/*
		Ejecuta un curl

		@url string -> endpoint
		@type string -> tipo de ejecucion [GET, POST, DELETE, ..]
		@header array -> campo opcional, de ser enviado reemplaza la cabecera que se envia en el curl
		@data array -> data que sera enviada en el curl
	*/
	public static function executeCurl($url = false, $type = false, $header = [], $data = []){
		if($url && $type){
			$curl = curl_init();

			if(!count($header)){
				$header = [
					"accept: */*",
				    "Content-Type: application/json",
				    "cache-control: no-cache",
				    "accept-language: en-US,en;q=0.8"
				];
			}

			$options = [
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => $type,
				CURLOPT_HTTPHEADER => $header
				//CURLOPT_SSL_VERIFYHOST => false,
				//CURLOPT_SSL_VERIFYPEER => false
			];

			if(count($data))
				$options[CURLOPT_POSTFIELDS] = json_encode($data);

			curl_setopt_array($curl, $options);

			$response = curl_exec($curl);
			$err = curl_error($curl);

			curl_close($curl);

			if($err)
				return ['success' => false, 'data' => $err];
			else{
				$dataJson = json_decode($response);

				if(!empty($dataJson))
					return ['success' => true, 'data' => $dataJson, 'original' => $response];
				else
					return ['success' => false, 'data' => 'No se pudo obtener json.', 'original' => $response];
			}
		}
		
		return ['success' => false, 'data' => 'Faltan datos.'];
	}

	/*Devuelve un string con el caracter (car) concatenado n veces*/
   	public static function getStrConcat($n = 1, $car = '0'){
   		$str = "";

   		for($i = 0; $i < $n; $i++) $str .= $car;

   		return $str;
   	}

   	/*Retorna n digitos aleatorios*/
	public static function getRandDig($n = 0){
		if($n > 0){
			mt_srand(time());
			$digits = '';
			for($i = 0; $i < $n; $i++){
			   $digits .= mt_rand(1,9);
			}
			return $digits;
		}
		return 0;
	}

   	/*Retornar un entero verificando los ultimos tres o dos caracteres de una cadena*/
   	public static function getLastNumber($str = 0){
   		$str = substr($str,strlen($str)-3,strlen($str));

		if(is_numeric($str)) return (int)$str;
		else{
			$str = substr($str,strlen($str)-2,strlen($str));

			if(is_numeric($str)) return (int)$str;
			else{
				$str = substr($str,strlen($str)-1,strlen($str));
				if(is_numeric($str)) return (int)$str;
			}
		}

		return 0;
   	}

   	/*Retorna true si el ancho de banda que se quiere activar es permitido para el usuario*/
	public static function compareWide($newWide = false, $serviceWide = false, $equal = false){
		if($newWide && $serviceWide){
			$newWide = SELF::getLastNumber($newWide);
			$serviceWide = SELF::getLastNumber($serviceWide);
			if($equal)
				return ($newWide != 0 && $serviceWide != 0 && $newWide == $serviceWide);
			return ($newWide != 0 && $serviceWide != 0 && $newWide > $serviceWide);
		}
		return false;
	}
}