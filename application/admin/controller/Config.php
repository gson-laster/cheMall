<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\Config as Conf;
use app\common\model\SmsTemplate as SmsTpl;
use app\common\model\Config as ConfMdl;

/**
 * [_initialize 网站配置]
 * @return   {[type]                  [description]
 * @Author:  slade
 * @DateTime :2017-03-31T09:22:29+080
 */
class Config extends AdminBase
{
    protected $conMdl;
    protected function _initialize(){
      parent::_initialize();
      $this->conMdl = new ConfMdl();
    }
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $config = $this->conMdl->find(1);
        //dump($config);
        $this->assign('config', $config);
        return $this->fetch();
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        if($this->request->isPost()){
          $data = $this->request->post();
          $data['id'] = 1;
          $this->conMdl->data($data, true);
          cache_data('site_config', $data);
          $res = $this->conMdl->isUpdate(true)->save();
          if($res){
            $this->success('操作成功');
          }else{
            $this->error('操作失败');
          }
          // dump($data);
        }else{
          $this->error('访问方式错误');
        }
    }

    /**
     * [smstemplate 短信模版]
     * @return   {[type]                  [description]
     * @Author:  slade
     * @DateTime :2017-03-30T10:11:16+080
     */
    public function smstemplate(){
      $sms = Db::name('sms_template');
      $sms_tpl  = $sms->select();
      $count = $sms->count();
      // dump($sms_tpl);
      return $this->fetch('smstemplate', ['list'=>$sms_tpl, 'count'=>$count]);
    }

    /**
     * [getSmsTplById 根据id查询短信模板]
     * @param    integer                  $tpl_id [description]
     * @return   {[type]                  [description]
     * @Author:  slade
     * @DateTime :2017-03-30T11:35:44+080
     */
    public function getSmsTplById($tpl_id = 0){
      if($tpl_id){
        $sms = Db::name('sms_template')->find($tpl_id);
        if($sms){
          $this->success('查询成功', '', $sms);
        }else{
          $this->error('没有这样的短信模板');
        }
      }else{
        $this->error('没有这样的短信模板');
      }
    }

    public function addEditSmsTpl(){
        adminLog('添加短信模板');
      if($this->request->isPost()){
        $data = $this->request->post();
        $type = input('post.tpl_id') > 0 ? 2 : 1; //1插入2更新
        $sms = new SmsTpl();
        $data['add_time'] = time();
        $sms->data($data, true);
        if($type == 1){
          $result = $sms->save();
        } else {
          $result = $sms->isUpdate(true)->save();
        }
        if($result){
          $this->success('操作成功');
        }else{
          $this->error('操作失败');
        }
      }else{
        $this->error('访问错误');
      }
    }
    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function deleteSmsTpl($id = 0, $ids = [])
    {
        adminLog('删除短信模板');
      $id = $ids ? $ids : $id;
      if($id){
        $this->request->get();
        $sms = new SmsTpl();
        if($sms->destroy($id)){
          $this->success('删除成功');
        }else{
          $this->error('删除失败');
        }
      }else{
        $this->error('请选择需要删除的短信模板');
      }
    }
}
