<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Request_curl extends Model
{
    use HasFactory;

    protected $ip;
  protected $header;
  protected $url;
  protected $method;
  protected $bearerToken;
  protected $path;
  protected $method_intermedio;
  protected $url_intermedia;

//Sets //
  public function setIp($ip)
  {
    $this->ip = $ip;
  }
  public function setHeader($header)
  {
    $this->header = $header;
  }
  public function setUrl($url)
  {
    $this->url = $url;
  }
  public function setMethod($method)
  {
    $this->method = $method;
  }
  public function setBearerToken($bearerToken)
  {
    $this->bearerToken = $bearerToken;
  }
  public function setPath($path)
  {
    $this->path = $path;
  }
  public function setUrlIntermedia($url_intermedia)
  {
    $this->url_intermedia = $url_intermedia;
  }
  public function setMethodIntermedia($method)
  {
    $this->method_intermedio = $method;
  }

//Gets //
  public function ip()
  {
    return $this->ip;
  }
  public function header()
  {
    return $this->header;
  }
  public function url()
  {
    return $this->url;
  }
  public function method()
  {
    return $this->method;
  }
  public function bearerToken()
  {
    return $this->bearerToken;
  }
  public function path()
  {
    return $this->path;
  }
  public function urlIntermedia()
  {
    return $this->url_intermedia;
  }
  public function methodIntermedia()
  {
    return $this->method_intermedio;
  }

}
