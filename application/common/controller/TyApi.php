<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/25 0025
 * Time: 下午 2:15
 */

namespace app\common\controller;

/**
 * 拓源API平台接口调用封装类
 * Class TyApi
 *
 * @package app\common\controller
 */
class TyApi {
  protected $app_id = 'TY7616516167';
  protected $app_secret = 'NCRJ8ALKGXWWPHVUEXHLMDOPENGHYF9Q40S6ZAML02A8LVAUOBO6TGPECOC9CRUY';
  protected $nonce = null;
  protected $method = null;
  protected $sign = null;
  protected $sign_method = 'md5';
  protected $format = null;
  protected $private_params = [];
  protected $apiUrl = 'http://api.tywebs.cn/api/router';
  protected $requestParams = [];
  protected $resource = 0;
  protected static $instrance = null;

  protected function __construct ($method = '', $options = [], $resource = 0) {
    if(!$method || $method == '') return false;
    $this->private_params = $options;
    $this->method = $method;
    $this->nonce = generate_str(32);
    $this->$resource = $resource;
    $params = array_merge($this->_generateRequestParams(), $this->private_params);
    ksort($params);
    $this->requestParams = $params;
    $this->_sign();
  }

  private function _generateRequestParams(){
    $params_arr = [];
    $params_arr = [
      'app_id'  => $this->app_id,
      'method'  => $this->method,
      'nonce'   => $this->nonce,
      'resource' => $this->resource
    ];

    return $params_arr;
  }

  private function _sign(){
    $tmp = [];
    foreach ( $this->requestParams as $k => $v) {
      $tmp[] = $k.$v;
    }

    $needSignStr = $this->app_secret.implode('', $tmp).$this->app_secret;
    $this->sign = strtoupper(md5($needSignStr));

    return $this->sign;
  }

  public function getRequestParams(){
    return $this->requestParams;
  }

  public static function instrance($method, $options = [], $resource){
    if(is_null(self::$instrance)){
      self::$instrance = new self($method, $options, $resource);
    }

    return self::$instrance;
  }

  public function getUrl(){
    $this->requestParams['sign'] = $this->sign;
    $query_str = urlStringfy($this->requestParams);

    return ['url' => $this->apiUrl, 'query' => $query_str];
  }



}
