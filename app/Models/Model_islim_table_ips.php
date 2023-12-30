<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Model_islim_table_ips extends Model
{
    use HasFactory;
   protected $table = 'islim_api_all_ip_server';

  protected $fillable = [
    'id',           // id autoincrementado. int(11)
    'token',        // Cadena con el token. Varchar(255)
    'ip',           // IP del origen de conexion. Varchar(100)
    'status',       // Activo o Inactivo. Enum(A,I)
    'date_reg',     // Fecha que se agrego la ip. DateTime
    'propietario',  // Propietario del registro. varchar(100)
    'api'];         // Enum('telmovPay','99v3','altan')

  public $timestamps = false;

  /**
   * Metodo para seleccionar conexion a la bd, escritura-lectura o solo escritura
   * @param String $typeCon
   *
   * @return App\models\Model_islim_table_ips
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
 * [isIpValid Consulta si la IP de donde hace la peticion a la api esta registrada y activa]
 * @param  [type]  $ipRequest [description]
 * @return boolean            [description]
 */
  public static function isIpValid($ipRequest)
  {
    $IP = self::getConnect('R')
      ->where([['ip', $ipRequest],
        ['api', env('APP_NAME')],
        ['status', 'A']])
      ->first();

    if (!empty($IP)) {
      return true;
    }
    return false;
  }

}
