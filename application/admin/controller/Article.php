<?php
namespace app\admin\controller;

use app\common\model\Article as ars;
use think\Db;
use think\Config;
use app\common\model\Article as arr;
use app\common\model\Articlebanner;

class Article extends AdminBase
{

    protected $art;

    protected $banner;

    protected function _initialize()
    {
        parent::_initialize();
        $this->art = new ars();
        $this->banner = new Articlebanner();
    }

    function articlelist($classid = '', $page = 1)
    {
        if ($classid) {
            $res = $this->art->where('classid', $classid)
                ->order('createtime', 'desc')
                ->paginate(Config::get('pageSize'), false, [
                'page' => $page
            ]);
        } else {

            $res = $this->art->order('createtime', 'desc')->paginate(Config::get('pageSize'), false, [
                'page' => $page
            ]);
        }

        $recount = $this->art->count();
        $this->assign('articlelist', $res);
        $r = Db::name('artclass')->select();
        $this->assign("artclass", $r);
        $this->assign('article', null);
        $this->assign('articlecount', $recount);
        return $this->fetch('article_list');
    }

    function addarticlebanner()
    {
        $data = input("post.");

        if (! $data) {

            $resclass = Db::name('artclass')->where("isok", 1)->select();

            $this->assign("artclass", $resclass);

            return $this->fetch('addbanner');
        } else {

            $result = $this->validate($data, 'app\admin\Validate\Articlebanner');

            if (true !== $result) {

                return $result;
            } else {

                if (input('id') != null) {

                    $res = $this->banner->allowField(true)
                        ->isUpdate(true)
                        ->save($data);
                } else {

                    $res = $this->banner->allowField(true)->save($data);
                }

                if ($res != null) {
                    $this->success("成功！", url('articlebannerlist'));
                } else {

                    $this->error("失败！", url('getarticlebanner', array(
                        'id' => input('id')
                    )));
                }
            }
        }
    }

    /**
     * 添加文章
     */
    function addarticle()
    {
        adminLog('添加文章');
        $data = input("post.");

        if ($data == null) {

            $r = Db::name('artclass')->where("isok", 1)->select();

            $this->assign("artclass", $r);

            $this->assign("article", null);

            return $this->fetch('article_add');
        } else {

            $result = $this->validate($data, 'app\admin\Validate\Article');

            if (true !== $result) {

                return $result;
            } else {

                if (input('id') != null) {

                    $res = $this->art->allowField(true)
                        ->isUpdate(true)
                        ->save($data);
                } else {

                    $res = $this->art->allowField(true)->save($data);
                }

                if ($res != null) {
                    $this->success("成功！", url('articlelist'));
                } else {

                    $this->error("失败！", url('getarticle', array(
                        'id' => input('id')
                    )));
                }
            }
        }
    }

    /**
     * 轮播图列表
     *
     * @param string $classid
     * @param number $page
     * @return mixed
     */
    function articlebannerlist($classid = '', $page = 1, $bannertype = '')
    {
        $where = [];
        $wheres = [];
        if ($classid) {
            $where['classid'] = $classid;
            $wheres['id']=$classid;

        }

        if ($bannertype) {

            $where['bannertype'] = $bannertype;
        }

        $res = $this->banner->where($where)->paginate(Config::get('pageSize'), false, [
            'page' => $page
        ]);

        $r = Db::name('artclass')->where("isok", 1)
            ->where($wheres)
            ->select();
        $recount = $this->banner->count();

        if($this->request->isAjax()){

            return json([
                'code' => 1002,
                "data" => [
                    'artclass' => $r,
                    'bannercount' => $recount,
                    'bannerinfo' => $res

                ]
            ]);



        }else {


        $this->assign("artclass", $r);
        $this->assign('bannercount', $recount);
        $this->assign("bannerinfo", $res);
        return $this->fetch();
        }
    }

    /**
     * 删除轮播图
     *
     * @param number $id
     * @param unknown $ids
     */
    function delarticlebanner($id = 0, $ids = [])
    {
        adminLog('删除轮播图。');
        $id = $id ? $id : $ids;
        if (Articlebanner::destroy($id)) {
            $this->success("删除成功！");
        }

        $this->error("删除失败！");
    }

    /**
     * 删除文章
     *
     * @param number $id
     * @param unknown $ids
     */
    function delarticle($id = 0, $ids = [])
    {
        adminLog('删除文章。');
        $id = $id ? $id : $ids;
        if (arr::destroy($id)) {
            $this->success("删除成功！");
        }

        $this->error("删除失败！");
    }

    /**
     * 获取单篇文章
     *
     * @param unknown $id
     */
    function getarticle($id = '')
    {
        $result = $this->art->get($id);

        if ($result != null) {

            $r = Db::name('artclass')->select();

            $this->assign("artclass", $r);
            $this->assign("article", $result);
            return $this->fetch("article_add");
        } else {

            $this->articlelist();
        }
    }

    /**
     * 获取单张轮播图
     *
     * @param unknown $id
     */
    function getarticlebanner($id = '')
    {
        $result = $this->banner->get($id);

        if ($result != null) {

            $r = Db::name('artclass')->where("isok", 1)->select();
            $this->assign("artclass", $r);
            $this->assign("article", $result);
            return $this->fetch('articlebannereditor');
        } else {

            $this->articlelist();
        }
    }
}

?>