<?php

namespace app\api\controller;

use think\Controller;
use think\Config;
/**
 * 物流查询结构
 */
class Shipping extends Controller
{
    public static function getShipping($shipping_code = '', $logisticCode = '', $OrderCode = ''){
      if(!$shipping_code || !$logisticCode) $this->error('缺少必要参数');
      $requestData= "{'OrderCode':'{$OrderCode}','ShipperCode':'{$shipping_code}','LogisticCode':'{$logisticCode}'}";
    	$datas = array(
            'EBusinessID' => Config::get('SHIPPING')['EBusinessID'],
            'RequestType' => '1002',
            'RequestData' => urlencode($requestData) ,
            'DataType' => '2',
        );
      $datas['DataSign'] = self::encrypt($requestData, Config::get('SHIPPING')['AppKey']);
  	  $result=sendPost(Config::get('SHIPPING')['ReqURL'], $datas);
      // dump();
    	return json_decode($result, true);
    }

  /**
   * 电商Sign签名生成
   * @param data 内容
   * @param appkey Appkey
   * @return DataSign签名
   */
  public static function encrypt($data, $appkey) {
      return urlencode(base64_encode(md5($data.$appkey)));
  }
}
