<?php
namespace app\home\controller;

use app\common\model\Usersqtx as usersqtxMdl;
use think\Db;
use think\Session;
use phpDocumentor\Reflection\Types\Float_;

class Usersqtx extends Validate {
  
  protected $usersqtx;
  
  protected function _initialize () {
    parent::_initialize();
    $this->usersqtx = new usersqtxMdl();
  }
  
  function tousersqtx () {
    return $this->fetch( 'user/withdraw' );
  }
  
  function addusersqtx ( $userid = "", $sqtxprice = "" ) {
    $data = input();
    
    if ( $data ) {
      
      $res1 = Db::name( 'user' )->alias( 'a' )->field( [ 'a.user_vb','a.is_cansharered', 'a.real_id', 'b.status' ] )->join( '__USER_REALNAME__ b', 'a.real_id=b.id' )->where( 'a.id', $data[ 'userid' ] )->find();
      

      
      if ( $res1[ 'status' ] != 1 ) {
        
        $this->error( "未实名认证，请先实名认证后提现。", url( 'home/user/realname' ) );
      } else {
        
        if ( $sqtxprice ) {
          
          if ( (int) $sqtxprice > $res1[ 'user_vb' ] ) {
            
            $this->error( "提现金额大于已有金额，请重新填写！" );
          } else {
            
            $num       = cache_data( 'site_config' )[ 'tax' ] * 1 / 100;
            $poundage  = cache_data( 'site_config' )[ 'poundage' ] * 1 / 100;
            $sqtxprice = $sqtxprice * 1;
            
            $data[ 'tax' ]      = $sqtxprice * $num;
            $data[ 'poundage' ] = $sqtxprice * $poundage;
            
            $data[ 'actual' ] = $sqtxprice - $sqtxprice * $num - $sqtxprice * $poundage;
            
            accountLog( $userid, $user_money = $sqtxprice, $type = 2, $desc = '提现' );
            
            $res2 = $this->usersqtx->allowField( true )->save( $data );
            
            if ( $res2 ) {
              
              $this->success( "提现成功,预计3-5个工作日到帐！" );
            } else {
              
              $this->error( "提现失败，请重试！" );
            }
          }
        } else {
          
          $this->error( "提现金额不能为空，请重新填写！" );
        }
      }
    } else {
      
      return $this->fetch( 'user/withdraw' );
    }
  }
  
  /**
   * 用户提现列表
   *
   * @param integer $page
   *            [description]
   *
   * @return   [type] [description]
   * @Author   : slade
   * @DateTime :2017-05-06T17:24:51+080
   */
  public function apply ( $pageSize = 10, $page = 1 ) {


    $res               = $this->usersqtx->where( 'userid', Session::get( 'qt_userId' ) )->paginate( $pageSize, false, [ 'page' => $page ] );
    $user_realname_res = Db::name( 'user_realname' )->where( 'user_id', Session::get( 'qt_userId' ) )->find();
    $tx_way            = [ '1' => [ '银行卡', $user_realname_res[ 'bank_card_code' ] ], '2' => [ '微信支付', $user_realname_res[ 'wxpay' ] ], '3' => [ '支付宝', $user_realname_res[ 'alipay' ] ] ];
   // dump($res);
    if ( $this->request->isAjax() ) {
      return $this->success( '获取成功', '', [ 'list' => $res, 'pay_way' => $tx_way ] );
    } else {
      return $this->fetch( 'user/apply', [ 'list' => $res, 'pay_way' => $tx_way ] );
    }
  }
}

?>
