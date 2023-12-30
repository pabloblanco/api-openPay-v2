<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\Curl;

class Model_islim_table_tokenLife extends Model
{
    use HasFactory;
    protected $table = 'islim_api_all_token_life';

  protected $fillable = [
    'id',           // id autoincrementado int(11)
    'token',        // Cadena con el token. Varchar(255)
    'date_star',    // Inicio de vida. Datetime
    'date_end',     // Fin de vida. DateTime
    'status',       // Activo, Eliminado. Enum(A,T)
    'tokenType',    // Tipo de token. Bearer u otros
    'expire_in',    // tiempo en seg de vida del token
    'api'];         // Enum('telmovPay','99v3','altan')


    public function __construct()
  {
    date_default_timezone_set('America/Mexico_City');
  }
  /**
   * Metodo para seleccionar conexion a la bd, escritura-lectura o solo escritura
   * @param String $typeCon
   *
   * @return App\models\Model_islim_table_tokenLife
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

  public static function getToken($request)
  {
    $TokenA = self::getConnect('R')
      ->select('*')
      ->where([
        ['status', 'A'],
        ['api', env('APP_NAME')]])
      ->first();

    if (!empty($TokenA)) {

      $date1       = new \DateTime($TokenA->date_end);
      $date2       = new \DateTime("now");
      $timeDiffSeg = $date1->getTimestamp() - $date2->getTimestamp();

      if ($timeDiffSeg > 15) {
        //Token vigente con 15 seg de utilidad
        return $TokenA;
      } else {
        //Tiempo vencido, Elimino el token activo y
        //creo un nuevo token
        self::deleteToken($TokenA->id);
        return self::newToken($request);
      }
    } else {
      return self::newToken($request);
    }
  }

  private static function newToken($request)
  {
    /*Datos para solicitar un nuevo token en api final*/
    $data = array(
      '' => '',
      '' => '');

    $NewToke = Curl::executeCurl('oauth/token', 'POST', [], $data, $request);
    if ($NewToke['success']) {
      $TokenN            = self::getConnect('W');
      $TokenN->token     = $NewToke['data']->access_token;
      $TokenN->tokenType = ucwords($NewToke['data']->token_type);

      $timeLife = $NewToke['data']->expires_in;

      $fecha_actual = date("Y-m-d H:i:s");
      //sumo los segundos de vida
      $fechaCaducidad = date("Y-m-d H:i:s", strtotime($fecha_actual . "+ " . $timeLife . " seconds"));

      $TokenN->date_star = $fecha_actual;
      $TokenN->date_end  = $fechaCaducidad;
      $TokenN->expire_in = $timeLife;
      $TokenN->api = env('APP_NAME');
      $TokenN->save();
      return $TokenN;
    } else {
      return null;
    }
  }

  public static function deleteToken($id)
  {
    return self::getConnect('W')
      ->where('id', $id)
      ->update([
        'status' => 'T',
      ]);
  }

}