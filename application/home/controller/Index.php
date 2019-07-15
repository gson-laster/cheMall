<?php
namespace app\home\controller;

use app\common\Hook;
use think\Db;

use app\common\model\Banner;
use app\common\model\Goods;
use app\common\model\GoodsCategory;
use app\common\model\SearchHot;
use think\Session;

/**
* 前台首页
*/
class Index extends HomeBase
{

	/**
	 * [index 首页]
	 * @return   {[type]                  [description]
	 * @Author:  slade
	 * @DateTime :2017-04-06T14:53:23+080
	 */
	function index()
	{
		//顶部轮播图
		$banner = new Banner();
		$banenr_list = $banner->where("status=1")->order('zid DESC')->select();

		// 已经申请并且已经审核成功的服务中心
    $result = model('service_center')->where(['recommend' => 1, 'status' => 2])->order('zid desc')->select();
		return $this->fetch('index',
		[
			'banner'    => $banenr_list,
      'recommond' => $result
		]);
	}

  /**
   * 服务中心详情
   * @methods
   * @desc
   * @author slide
   *
   */
	function serviceCenterDetail($id){
	  if(!$id) return $this->error('没有这样的服务中心');

	  $detail = model('service_center')->find($id);

	  return $this->fetch('service_center_detail', ['detail' => $detail]);

  }

  /**
   * 服务中心搜索列表
   * @methods
   * @desc
   * @author slide
   *
   * @param string $keyword
   *
   */
  function search($keyword = '', $page = 1){
    $where = [];
    if($keyword) {
      $where['title'] = ['like', "%{$keyword}%"];
    }
    $where['status'] = 1;
    $list = model('service_center')->where($where)->order('zid desc')->paginate(12, ['page' => $page, 'query' => ['keyword' => $keyword]]);

    return $this->fetch('service_center_search', ['list' => $list]);
  }


	/**
	 * 客服
	 * @return   [type]                   [description]
	 * @Author:  slade
	 * @DateTime :2017-04-20T17:25:45+080
	 */
	public function service(){
		$data = cache_data('site_config');
		return $this->fetch('service', ['mobile'=>$data['site_servicePhone'], 'qq'=>$data['site_serviceQQ'],'wx'=>$data['site_serviceWechat']]);
	}

  /**
   * 联系方式
   * @author: slide
   *
   */
	public function payend(){
	  // 获取我i的省级代理收款信息
    if(Session::get('userInfo')['parent_agent']){
      $result = Db::name('agent_getmoney_info')->where('user_id', Session::get('userInfo')['parent_agent'])->find();
      if(!$result){
        $result['site_servicePhone'] = cache_data('site_config')['site_servicePhone'];
        $result['site_serviceQQ'] = cache_data('site_config')['site_serviceQQ'];
        $result['site_serviceWechat'] = cache_data('site_config')['site_serviceWechat'];
        $result['site_bank_code'] = cache_data('site_config')['site_bank_code'];
        $result['site_bank_name'] = cache_data('site_config')['site_bank_name'];
        $result['site_bank_khrname'] = cache_data('site_config')['site_bank_khrname'];
        $result['site_alipay_account'] = cache_data('site_config')['site_alipay_account'];
        $result['site_alipay_name'] = cache_data('site_config')['site_alipay_name'];
      }
    }else{
      $result['site_servicePhone'] = cache_data('site_config')['site_servicePhone'];
      $result['site_serviceQQ'] = cache_data('site_config')['site_serviceQQ'];
      $result['site_serviceWechat'] = cache_data('site_config')['site_serviceWechat'];
      $result['site_bank_code'] = cache_data('site_config')['site_bank_code'];
      $result['site_bank_name'] = cache_data('site_config')['site_bank_name'];
      $result['site_bank_khrname'] = cache_data('site_config')['site_bank_khrname'];
      $result['site_alipay_account'] = cache_data('site_config')['site_alipay_account'];
      $result['site_alipay_name'] = cache_data('site_config')['site_alipay_name'];
    }
	  return $this->fetch('payend', ['result'=>$result]);
  }
  /**
   * 导航地图展示
   * @author: slide
   *
   */
  public function showMap($id){

    if(!$id) return $this->error('没有这样的服务中心');

    $detail = model('service_center')->find($id);
    $this -> assign('detail', $detail);
    return $this -> fetch();
  }
}







