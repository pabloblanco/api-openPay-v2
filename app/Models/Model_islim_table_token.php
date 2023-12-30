<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Model_islim_table_token extends Model
{
    use HasFactory;

   protected $table = 'islim_api_all_token_server';

  protected $fillable = [
    'id',           // id autoincrementado int(11)
    'token',        // Cadena con el token. Varchar(255)
    'type',         // Produccion o Desarrollo. Enum(P,D)
    'status',       // Activo, eliminado o Inactivo. Enum(A,I,T)
    'date_create',  // Fecha que se agrego el token. DateTime
    'api'];         // Enum('telmovPay','99v3','altan')


  public $timestamps = false;

  /**
   * Metodo para seleccionar conexion a la bd, escritura-lectura o solo escritura
   * @param String $typeCon
   *
   * @return App\models\Model_islim_table_token
   */
  public static function getConnect($typeCon = false)
  {
    if ($typeCon) {
      $obj = new self;
      $obj->setConnection($typeCon == 'W' ? 'mysql::write' : 'mysql::read');

      return $obj;
    }
    return null;
  }

  /**
   * [isTokenValid Retorna si es valido el token de conexion]
   * @return boolean [description]
   */
  public static function isTokenValid($request)
  {
    $entorno = env('APP_ENV', 'local');
    $type    = '';
    if ($entorno == 'local' || $entorno == 'test') {
      $type = 'D';
    } elseif ($entorno == 'production') {
      $type = 'P';
    }

    $KEY = self::getConnect('R')
      ->select('islim_api_all_ip_server.id')
      ->join('islim_api_all_ip_server',
        'islim_api_all_ip_server.token',
        'islim_api_all_token_server.token')
      ->where([
        ['islim_api_all_ip_server.token', $request->bearerToken()],
        ['islim_api_all_token_server.type', $type],
        ['islim_api_all_token_server.status', 'A'],
        ['islim_api_all_ip_server.status', 'A'],
        ['islim_api_all_ip_server.api', env('APP_NAME')],
        ['islim_api_all_token_server.api', env('APP_NAME')],
        ['islim_api_all_ip_server.ip', $request->ip()]])
      ->first();

    if (!empty($KEY)) {
      return true;
    }
    return false;
  }

}
