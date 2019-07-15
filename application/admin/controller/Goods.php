<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use think\Page;
use think\Config;
use think\Db;
use app\common\model\GoodsAttr;
use app\common\model\GoodsAttribute;
use app\common\model\GoodsModel as GoodsType;
use app\common\model\GoodsCategory;
use app\common\model\Brand;
use app\common\model\Region;
use app\common\model\Payment;
use app\common\model\GoodsImages;
use app\common\model\GoodsDetails;
use app\common\model\Goods as GoodsModel;

class Goods extends AdminBase
{
    protected $goodsMdl;
    protected $GoodsMdl;
    protected function _initialize() {
        parent::_initialize();
        $this->GoodsMdl = new GoodsType();
        $this->goods = new GoodsModel();
    }
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index($keywords = '',$createtime = '', $page=1)
    {
        //dump(date('Y-m-d H:i:s',1491446218));
        $map = [];
        if($keywords){
          $map['title'] = ['LIKE',"%{$keywords}%"];
        }
        if($createtime){
          $map['g.createtime'] = ['between', [strtotime($createtime), (strtotime($createtime) + 86400000)]];
        }
        $goods_list =$this->goods->getGoodsList($map, $page);
//         dump($goods_list);
        return $this->fetch('index',['keywords'=>$keywords, 'list'=>$goods_list,'keywords'=>$keywords, 'start'=>$createtime]);
    }

    /*创建商品货号*/
   public  function  createSn(){
       $sn = 'NO'.date('Ymd').mt_rand(1000,9999);
       $res = $this->goods->where("artnum='{$sn}'")->find();
       return $res ? $this->createSn() : $sn;
   }

    /**
     * [add 添加商品]
     * @Author:  slade
     * @DateTime :2017-03-29T09:17:10+080
     */
    public function add($id = 0){
      adminLog('添加/修改商品');
      if($id){
        $goods = $this->goods->find($id);
        //dump($goods);
        $goods->agent_config = json_decode($goods->agent_config, true);
        $this->assign('goods', $goods);
        $img = new GoodsImages();
        $goodsImg = $img->where("goods_id='{$id}'")->select();
        $this->assign('goodsImg', $goodsImg ? $goodsImg : '');
        $detail = new GoodsDetails();
        $goods_details = $detail->where("goods_id='{$id}'")->find();
        $this->assign('gid', $id);
        $this->assign('goode_details', $goods_details);
      }
    	if($this->request->isPost()){//修改或者添加入库动作
		    $goods_info = $this->request->post(); //收集传递的数据
        // $type = $id > 0 ? 2 : 1;
        // dump($goods_info);exit;
        $goodsMdl = $this->goods;
		    $goodsMdl->data($goods_info, true); //给模型传递数据
		    $type = input('post.goods_id') > 0 ? 2 : 1 ; //1插入2更新
        //组织数据
        if(isset($goods_info['agent'])) {
          $goodsMdl->agent_config = (String) json_encode( $goods_info[ 'agent' ] );
        }
        if(isset($goods_info['item'])){
          $goodsMdl->spec_config = (String)json_encode($goods_info['item']);
        }
        if(isset($goods_info['attr'])){
          $goodsMdl->goods_attr_config = (String)json_encode($goods_info['attr']);
        }
        // $goodsMdl->shipping_area_ids = (String)json_encode($goods_info['shipping_area_ids']);
        $goodsMdl->is_real = isset($goods_info['is_real']) && ($goods_info['is_real'] == 'on') ? true : false;
        $goodsMdl->is_on_sale = isset($goods_info['is_on_sale']) && ($goods_info['is_on_sale'] == 'on') ? true : false;
        if($goodsMdl->is_on_sale){ //上架时间
          $goodsMdl->on_time = time();
        }
        $goodsMdl->artnum = $goods_info['artnum'] == '' ? $this->createSn() : $goods_info['artnum'];
        $goodsMdl->is_recommend = isset($goods_info['is_recommend']) && ($goods_info['is_recommend'] == 'on') ? true : false;
        $goodsMdl->is_new = isset($goods_info['is_new']) && ($goods_info['is_new'] == 'on') ? true : false;
        $goodsMdl->is_hot = isset($goods_info['is_hot']) && ($goods_info['is_hot'] == 'on') ? true : false;
        // dump($goodsMdl);exit;
		    if($type == 1){
		    	$result = $goodsMdl->allowField(true)->save();
		    } else{
          $goodsMdl->id = $goods_info['goods_id'];
		    	$result = $goodsMdl->allowField(true)->isUpdate(true)->save();
		    }

		    if($result){
          //成功后的操作
          //保存商品图片
          if(isset($goods_info['goods_images'])){
            $img = new GoodsImages();
            //商品图片
            $data_images_res = $img->where("goods_id={$goodsMdl->id}")->delete();
            $images_arr = [];
            foreach($goods_info['goods_images'] as $v => $k){
              $temp = [
                'goods_id' => $goodsMdl->id,
                'image_url' => $k
              ];
              $images_arr[] = $temp;
            }
            // dump($images_arr);exit;
            // dump($goods_info['goods_details']);exit;
            $img_res = $img->saveAll($images_arr);
            $detail = new GoodsDetails();
            $detail->where("goods_id={$goodsMdl->id}")->delete(); $detail->save(['goods_id'=>$goodsMdl->id, 'details'=>$goods_info['goods_details']]);
          }
          $this->success('保存成功', url('index'));
		    }else{
		    	$this->error('操作失败');
		    }
	    }else{
		    $goodsInfo = $this->goods->where('id=' . input('get.id', 0))->find();
	    }
      //商品的模型
      $goods_type = Db::name('GoodsModel')->select();
      //物流
      // dump(json_decode('{"areaId":"22","text":"崇文区","first_weight":"500","first_money":"20","second_weight":"500","second_money":"1.2"}'));exit;
      $shipping = Db::name('transport')->select();
      foreach ($shipping as $key => &$value) {
        $temp_conf = explode("|$|",$value['config']);
        $temp_arr = [];
        foreach ($temp_conf as $k => $v) {
          $temp_arr[] = json_decode($v, true);
        }
        $value['config'] = $temp_arr;
      }
      // dump($shipping);
      $this->assign("shipping", $shipping);
      $this->assign("goodsInfo", $goodsInfo);
      //商品分类
	    $category = new GoodsCategory();
	    $cate_list = $category->getLevelList();
	    $this->assign('cate_list', $cate_list);
	    //品牌
	    $brand = new Brand();
	    $brand_list = $brand->order('sort desc')->select();
	    $this->assign('brand_list', $brand_list);
      //支付方式
      $payment = new Payment();
      $payment_list = $payment->field(['id', 'name', 'logo'])->where('status=1')->select();
      $this->assign('payment_list', $payment_list);
      $this->assign('goodsModel', $goods_type);
      return $this->fetch();
    }

    public function deletegoods($id = 0, $ids = []){
      $id = $ids ? $ids : $id;
      if($id){
        if($this->goods->destroy($id)){
          //删除购物车
          Db::name('shopcar')->where("goods_id IN ({$id})")->delete();

          //删除图片
          Db::name('goods_images')->where("goods_id IN ({$id})")->delete();

          //删除商品详情
          Db::name('goods_details')->where("goods_id IN ({$id})")->delete();
          adminLog('删除商品');

          $this->success('删除成功');
        }else{
          $this->error('删除失败');
        }
      }else{
        $this->error('请选择需要删除的商品');
      }
    }
    /**
     * 动态获取商品规格选择框 根据不同的数据返回不同的选择框
     */
    public function ajaxGetSpecSelect(){
        $goods_id = input('get.goods_id/d') ? input('get.goods_id/d') : 0;
        //$_GET['spec_type'] =  13;
        $specList = Db::name('Spec')->where("type_id = ".input('get.spec_type/d'))->order('`zid` desc')->select();
        foreach($specList as $k => $v)
            $specList[$k]['spec_item'] = Db::name('SpecItem')->where("spec_id = ".$v['id'])->order('id')->column('id,item'); // 获取规格项
        if( $goods_id ){

          // $items_id = db::name('SpecGoodsPrice')->where('goods_id = '.$goods_id)->column("GROUP_CONCAT(`key` SEPARATOR '_') AS items_id");
          // if($items_id){
          //   $items_ids = explode('_', $items_id);
          // }
          $spec_config = Db::name('goods')->field('spec_config')->find($goods_id);
          $spec_c = '';
          $items_id = [];
          // dump($spec_config['spec_config'] != 'null');
          if($spec_config['spec_config'] != '' && !is_null($spec_config['spec_config']) && $spec_config['spec_config'] != 'null'){
            $spec_c = json_decode($spec_config['spec_config'], true);
            $items_ids = array_keys($spec_c);
            foreach ($items_ids as $k => $v) {
              $v = explode('_', $v);
              $items_id = array_merge($items_id, $v);
            }
          }
          // dump($items_id);exit;
          $this->assign('goods_id', $goods_id);
          $this->assign('items_ids', $items_id);
          $this->assign('$spec_c',$spec_c);
        }
        // dump(db::getLastSql());exit;

        // 获取商品规格图片
        if($goods_id)
        {
          // $specImageList = Db::name('SpecImage')->where("goods_id = $goods_id")->column('spec_image_id,src');
          // $this->assign('specImageList',$specImageList);
        }
        // dump($specList);
        $this->assign('specList',$specList);
        $this->view->engine->layout(false);
        return $this->fetch('ajax_spec_select');
    }
    /**
     * 动态获取商品属性输入框 根据不同的数据返回不同的输入框类型
     */
    public function ajaxGetAttrInput(){
        $str = $this->getAttrInput($_REQUEST['goods_id'],$_REQUEST['type_id']);
        exit($str);
    }

    /**
     * 动态获取商品属性输入框 根据不同的数据返回不同的输入框类型
     * @param int $goods_id 商品id
     * @param int $type_id 商品属性类型id
     */
    public function getAttrInput($goods_id,$type_id)
    {
        header("Content-type: text/html; charset=utf-8");
        $GoodsAttribute = Db::name('GoodsAttribute');
        $attributeList = $GoodsAttribute->where("type_id = $type_id")->select();
        $str = '';
        foreach($attributeList as $key => $val)
        {
            $curAttrVal = $this->getGoodsAttrVal(NULL,$goods_id, $val['attr_id']);
            // dump($curAttrVal);
             //促使他 循环
            if(count($curAttrVal) == 0)
                $curAttrVal[] = array('goods_attr_id' =>'','goods_id' => '','attr_id' => '','attr_value' => '','attr_price' => '');
            foreach($curAttrVal as $k =>$v)
            {
                $str .= "<tr class='attr_{$val['attr_id']}'>";
                $addDelAttr = ''; // 加减符号
                // 单选属性 或者 复选属性
                if($val['attr_type'] == 1 || $val['attr_type'] == 2)
                {
                    if($k == 0)
                        $addDelAttr .= "<a onclick='addAttr(this)' href='javascript:void(0);'>[+]</a>&nbsp&nbsp";
                    else
                         $addDelAttr .= "<a onclick='delAttr(this)' href='javascript:void(0);'>[-]</a>&nbsp&nbsp";
                }

                $str .= "<td>$addDelAttr {$val['attr_name']}</td> <td>";
                // dump($val);
                // 手工录入
                if($val['attr_input_type'] == 0)
                {
                    $str .= "<input type='text' size='40' value='".($goods_id ? $v['attr_value'] : $val['attr_values'])."' name='attr[{$val['attr_id']}]' />";
                }
                // 从下面的列表中选择（一行代表一个可选值）
                if($val['attr_input_type'] == 1)
                {
                    $str .= "<select name='attr[{$val['attr_id']}]'>";
                    $tmp_option_val = explode(',', $val['attr_values']);
                    foreach($tmp_option_val as $k2=>$v2)
                    {
                        // 编辑的时候 有选中值
                        $v2 = preg_replace("/\s/","",$v2);
                        if($v == $v2)
                            $str .= "<option selected='selected' value='{$v2}'>{$v2}</option>";
                        else
                            $str .= "<option value='{$v2}'>{$v2}</option>";
                    }
                    $str .= "</select>";
                }
                // 多行文本框
                if($val['attr_input_type'] == 2)
                {
                    $str .= "<textarea cols='40' rows='3' name='attr[{$val['attr_id']}]'>".($goods_id ? $v['attr_value'] : $val['attr_values'])."</textarea>";
                }
                $str .= "</td></tr>";
            }
        }
        return  $str;
    }
    /**
     * 获取 goods_attr 表中指定 goods_id  指定 attr_id  或者 指定 goods_attr_id 的值 可是字符串 可是数组
     * @param int $goods_attr_id tp_goods_attr表id
     * @param int $goods_id 商品id
     * @param int $attr_id 商品属性id
     * @return array 返回数组
     */
    public function getGoodsAttrVal($goods_attr_id = 0 ,$goods_id = 0, $attr_id = 0)
    {
        $GoodsAttr = Db::name('GoodsAttr');
        $res = json_decode($goods = $this->goods->field('goods_attr_config')->find($goods_id)['goods_attr_config'], true);
        // dump($res);exit;
        if($goods_attr_id > 0)
            return $res;
        $data = [];
        if($goods_id > 0 && $attr_id > 0){
          if(isset($res[$attr_id])){
            $data[] = $res[$attr_id];
          }
        }
        // dump($data);
            return $data;
    }
    /**
     * 动态获取商品规格输入框 根据不同的数据返回不同的输入框
     */
    public function ajaxGetSpecInput(){
         $goods_id = input('goods_id/d') ? input('goods_id/d') : 0;
         $str = $this->getSpecInput($goods_id ,input('post.spec_arr/a',[[]]));
         exit($str);
    }
    /**
     * 获取 规格的 笛卡尔积
     * @param $goods_id 商品 id
     * @param $spec_arr 笛卡尔积
     * @return string 返回表格字符串
     */
    public function getSpecInput($goods_id, $spec_arr)
    {
        // dump($spec_arr);
        // 排序
        $str = '';
        foreach ($spec_arr as $k => $v)
        {
          if(!empty($v)){
            $spec_arr_sort[$k] = count($v);
          }
        }
        if(isset($spec_arr_sort)){
          asort($spec_arr_sort);
          foreach ($spec_arr_sort as $key =>$val)
          {
              $spec_arr2[$key] = $spec_arr[$key];
          }

          $clo_name = array_keys($spec_arr2);
          $spec_arr2 = combineDika($spec_arr2); //  获取 规格的 笛卡尔积

          $spec = Db::name('Spec')->column('id,name'); // 规格表
          $specItem = Db::name('SpecItem')->column('id,item,spec_id');//规格项
          // $keySpecGoodsPrice = Db::name('SpecGoodsPrice')->where('goods_id = '.$goods_id)->column('key,key_name,price,store_count,bar_code,sku');//规格项
          $keySpecGoodsPrice = json_decode(Db::name('goods')->field('spec_config')->find($goods_id)['spec_config'], true);
          $str = "<table class='table table-bordered' style='align:left' id='spec_input_tab'>";
          $str .="<tr>";
          // 显示第一行的数据
          // dump($spec_arr2);
          foreach ($clo_name as $k => $v)
          {
              $str .=" <td><b>{$spec[$v]}</b></td>";
          }
           $str .="<td><b>价格</b></td>
                  <td><b>库存</b></td>
                  <td><b>利润</b></td>
                </tr>";
          // 显示第二行开始
          // dump($spec_arr2);
          foreach ($spec_arr2 as $k => $v)
          {
               $str .="<tr>";
               $item_key_name = array();
               foreach($v as $k2 => $v2)
               {
                  // dump($specItem[$v2]);
                   $str .="<td>{$specItem[$v2]['item']}</td>";
                   $item_key_name[$v2] = $spec[$specItem[$v2]['spec_id']].':'.$specItem[$v2]['item'];
               }
               ksort($item_key_name);
               $item_key = implode('_', array_keys($item_key_name));
               $item_name = implode(' ', $item_key_name);
              //  dump($item_key);
              //  dump($keySpecGoodsPrice);exit;
               isset($keySpecGoodsPrice[$item_key]['price']) ? false : $keySpecGoodsPrice[$item_key]['price'] = 0; // 价格默认为0
               isset($keySpecGoodsPrice[$item_key]['store_count']) ? false : $keySpecGoodsPrice[$item_key]['store_count'] = 0; //库存默认为0
               isset($keySpecGoodsPrice[$item_key]['store_profit']) ? false : $keySpecGoodsPrice[$item_key]['store_profit'] = 0;
               $str .="<td><input name='item[$item_key][price]' value='{$keySpecGoodsPrice[$item_key]['price']}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")' /></td>";
               $str .="<td><input name='item[$item_key][store_count]' value='{$keySpecGoodsPrice[$item_key]['store_count']}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")'/></td>";
               $str .="<td><input name='item[$item_key][store_profit]' value='{$keySpecGoodsPrice[$item_key]['store_profit']}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")'/></td>";
               $str .="</tr>";
          }
         $str .= "</table>";
        }
       return $str;
    }
    /**
     * 获取商品分类 的帅选规格 复选框
     */
    public function ajaxGetSpecList(){
        $spec_id = input('get.spec_type', 0);
        $filter_spec_arr = [];
        // $filter_spec_arr = explode(',',$filter_spec);
        $str = $this->GetSpecCheckboxList($spec_id,$filter_spec_arr);
        $str = $str ? $str : '没有可帅选的商品规格';
        exit($str);
    }

    /**
     * 获取商品分类 的属性 复选框
     */
    public function ajaxGetAttrList(){
        $_REQUEST['category_id'] = isset($_REQUEST['category_id']) ? $_REQUEST['category_id'] : 0;
        $filter_attr = Db::name('GoodsCategory')->where("id = ".$_REQUEST['category_id'])->column('filter_attr');
        $filter_attr_arr = explode(',',$filter_attr);
        $str = $this->GetAttrCheckboxList($_REQUEST['type_id'], $filter_attr_arr);
        $str = $str ? $str : '没有可筛选的商品属性';
        exit($str);
    }

    /**
     * 获取指定规格类型下面的所有规格  但不包括规格项 供商品分类列表页帅选作用
     * @param type $type_id
     * @param type $checked
     */
    function GetSpecCheckboxList($type_id, $checked = array()){
        $list = Db::name('Spec')->where("type_id = $type_id")->order('`zid` desc')->select();
        //$list = M('Spec')->where("1=1")->order('`order` desc')->select();
        $str = '';

        foreach($list as $key => $val)
        {
            if(in_array($val['id'],$checked))
                $str .= $val['name'].":<input type='checkbox' name='spec_id[]' value='{$val['id']}' checked='checked'/>&nbsp;&nbsp";
            else
                $str .= $val['name'].":<input type='checkbox' name='spec_id[]' value='{$val['id']}' />&nbsp;&nbsp";
        }
        return $str;
    }

    /**
     * 获取指定商品类型下面的所有属性  供商品分类列表页帅选作用
     * @param type $type_id
     * @param type $checked
     */
    function GetAttrCheckboxList($type_id, $checked = array()){
        $list = Db::name('GoodsAttribute')->where("type_id = $type_id and attr_index > 0 ")->order('`order` desc')->select();
        $str = '';

        foreach($list as $key => $val)
        {
            if(in_array($val['attr_id'],$checked))
                $str .= $val['attr_name'].":<input type='checkbox' name='attr_id[]' value='{$val['attr_id']}' checked='checked'/>&nbsp;&nbsp";
            else
                $str .= $val['attr_name'].":<input type='checkbox' name='attr_id[]' value='{$val['attr_id']}' />&nbsp;&nbsp";
        }
        return $str;
    }

    //=============================================================================================================================================================================================================================================\
    /**
     * [attributelist 商品属性]
     * @return   {[type]                  [description]
     * @Author:  slade
     * @DateTime :2017-03-25T15:41:09+080
     */
    public function attributelist($page = 1){
      $where = ' 1 = 1 '; // 搜索条件
      // I('type_id')   && $where = "$where and type_id = ".I('type_id') ;
      // 关键词搜索
      $model = new GoodsAttribute();
      $count = $model->where($where)->count();
      $goodsAttributeList = $model->where($where)->order('`order` desc,attr_id DESC')->paginate(Config::get('pageSize'),false, ['page'=>$page]);
      $goodsTypeList = Db("GoodsModel")->column('id,name');
      $attr_input_type = array(0=>'手工录入',1=>' 从列表中选择',2=>' 多行文本框');
      // dump($goodsAttributeList);
      $this->assign('attr_input_type',$attr_input_type);
      $this->assign('goodsTypeList',$goodsTypeList);
      $this->assign('count', $count);
      $this->assign('goodsAttributeList',$goodsAttributeList);
      return $this->fetch();
    }

    /**
     * [addAtribute 添加商品属性]
     * @Author:  slade
     * @DateTime :2017-03-27T09:42:04+080
     */
    public function addEditAtribute(){

      $model = new GoodsAttribute();
      $type = input('post.attr_id') > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新
      $attr_values = str_replace('_', '', input('post.attr_values')); // 替换特殊字符
      $attr_values = str_replace('@', '', $attr_values); // 替换特殊字符
      $attr_values = trim($attr_values);
      // 数据验证
      if($this->request->isPost()){
        $data = $this->request->post();
        $data['attr_values'] = $attr_values;
        $validate_result = $this->validate($data, 'GoodsAttribute');
        if(!$validate_result){
          $this->error($validate_result);
        } else {
           $model->data($data,true); // 收集数据
           if($type == 1){
             $result = $model->save();
           }else{
             $result = $model->isUpdate(true)->save();
           }
           if( $result !== false){// 写入数据到数据库
             adminLog('添加商品属性');
             $this->success('操作成功');
           }else{
             $this->error('操作失败');
           }
         }
      }
    }

    /**
     * [getAttrById 获取单个属性]
     * @return   {[type]                  [description]
     * @Author:  slade
     * @DateTime :2017-03-27T09:41:26+080
     */
    public function getAttrById($id = 0){
      if($id){
        $attrMdl = new GoodsAttribute();
        $attrInfo = $attrMdl->find($id);
        if($attrInfo){
          $this->success('商品属性获取成功', url('index'), $attrInfo);
        }else{
          $this->error('商品属性获取失败');
        }
      }else{
        $this->error('请选择你需要的商品属性');
      }
    }

    /**
     * [deleteAttr 删除商品属性]
     * @param    integer                  $id  [description]
     * @param    [type]                   $ids [description]
     * @return   {[type]                  [description]
     * @Author:  slade
     * @DateTime :2017-03-27T10:08:22+080
     */
    public function deleteAttr($id=0, $ids=[]){
      $id = $id ? $id : $ids;
      if($this->request->isGet()){
        $attrMdl = new goodsAttribute();
        if($attrMdl->destroy($id)){
          adminLog('删除商品属性');
          $this->success('删除成功');
        }else{
          $this->error('删除失败');
        }
      }else{
        $this->error('访问的方式错误');
      }
    }

    /**
     * [GoodsBrandList 品牌列表]
     * @param    integer                  $page [description]
     * @Author:  slade
     * @DateTime :2017-03-27T10:32:34+080
     */
    public function GoodsBrandList($page =1){
      $brandMdl = new Brand();
      $count = $brandMdl->count();
      $brandList = $brandMdl->paginate(Config::get('pageSize'),false, ['page'=>$page]);
      $cate = new GoodsCategory();
      $cate_list = $cate->where('pid=0')->select();
      $this->assign('count', $count);
      $this->assign('brandList', $brandList);
      $this->assign('cate_list', $cate_list);
      return $this->fetch();
    }

    /**
     * [getBrandById 查询单个品牌]
     * @param    integer                  $id [description]
     * @return   {[type]                  [description]
     * @Author:  slade
     * @DateTime :2017-03-27T20:37:44+080
     */
    public function getBrandById($id = 0){
      if($id){
        $brandMdl = new Brand();
        $info = $brandMdl->find($id);
        if($info){
          $this->success('查询成功', url('index'), $info);
        }else{
          $this->error('查询失败');
        }
      }else{
        $this->error('没有这样的品牌');
      }
    }

    /**
     * [addEditBrand 添加修改品牌]
     * @Author:  slade
     * @DateTime :2017-03-27T10:33:18+080
     */
    public function addEditBrand(){

      adminLog('添加修改品牌');
      if($this->request->isPost()){
        $data = $this->request->post();
        $brand = new Brand();
        $type = input('post.id') > 0 ? 2 : 1; //1插入2,更新
        $cate = new GoodsCategory();
        $cate_id = (isset($data['cat_id']) && $data['cat_id'] && $data['cat_id'] != '') ? $data['cat_id'] : $data['parent_cat_id'];
        $data['cat_name'] = $cate->find($cate_id)->name;
        $brand->data($data, true);
        if($type === 1){
          $result = $brand->save();
        }else{
          $result = $brand->isUpdate(true)->save();
        }
        if($result){
          $this->success('操作成功', url('goodsbrandlist'));
        }else{
          $this->error('操作失败');
        }
      }else{
        $this->error('保存信息的方式错误');
      }
    }

    /**
     * [deleteBrand s删除品牌]
     * @return   {[type]                  [description]
     * @Author:  slade
     * @DateTime :2017-03-27T10:39:15+080
     */
    public function deleteBrand($id=0,$ids=[]){

        adminLog('删除品牌');
      $id = $id ? $id : $ids;
      if($this->request->isGet()){
        $attrMdl = new Brand();
        if($attrMdl->destroy($id)){
          $this->success('删除成功');
        }else{
          $this->error('删除失败');
        }
      }else{
        $this->error('访问的方式错误');
      }
    }

    /**
     * 删除商品缩略图图片
     * @return   [type]                   [description]
     * @Author:  slade
     * @DateTime :2017-04-27T14:57:01+080
     */
    public function delGoodsImg($id){
        adminLog('删除商品缩略图图片');
      if($id){
        if(delDirAndFile('/public/upload/goods/thumb/'.$id)){
          $this->success('删除缩略图成功');
        }else{
          $this->error('删除缩略图失败');
        }
      }else{
        $this->error('没有这样的图片');
      }
    }
}
