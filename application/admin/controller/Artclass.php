<?php
namespace app\admin\controller;

use app\admin\controller\AdminBase;
use app\common\model\Artclass as Artclassmdl;
use think\Config;

class Artclass extends AdminBase
{

    protected $artc;

    protected function _initialize()
    {
        parent::_initialize();
        $this->artc = new Artclassmdl();
    }

    function artclasslist($page = 1,$pid='0')
    {



        $res = $this->artc->paginate(Config::get('pageSize'), false, [
            'page' => $page
       ]);

        //dump($res);

      //  exit;

        $result_res=array2level($res,$pid);
        //dump($result_res);
        //exit;
        if($this->request->isAjax()){
            return json([
                'code' => 1002,
                "data" => [
                    'artclass' => $result_res,


                ]
            ]);

        }else{
        $this->assign("aclass", $result_res);
        return $this->fetch("article_Sort");
        }


    }

    function getartclass($id = '')
    {
        $res = $this->artc->where('id', $id)->select();

        if ($res != null) {

            return json([
                'code' => 1002,
                "data" => $res
            ]);
        } else {

            $this->error("获取分类失败！");
        }
    }

    function addartclass()
    {
        adminLog('添加分类');
        $data = input();
     if ($data) {

            $result = $this->validate($data, 'Artclass');

            if (true !== $result) {

                $this->error($result, 'url(artclasslist)');
            } else {

                // dump($data);
                // exit;

                if (input('id') != null) {

                    $res = $this->artc->allowField(true)
                        ->isUpdate(true)
                        ->save($data);
                } else {
                   // $data['isok']=1;
                     $res = $this->artc->allowField(true)->save($data);
                }

                if ($res != null) {
                    $this->success("成功！");
                } else {

                    $this->error("失败！");
                }
            }
        } else {

            return $this->error('请填写分类信息');
        }
    }

    function delartclass($id = 0, $ids = [])
    {
        adminLog('删除分类');
        $id = $id ? $id : $ids;
        if ($this->artc->destroy($id)) {
            $this->success("删除成功！");
        }

        $this->error("删除失败！");
    }
}

?>