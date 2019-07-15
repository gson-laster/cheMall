<?php
namespace app\admin\controller;

class Gtwxarticle extends AdminBase
{

    function getwxarticle($htmlurl = '')
    {
        if (! $htmlurl) {
            $this->error("采集的链接为空！");
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $htmlurl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $htmlurl = curl_exec($ch);
        $hobj = \phpQuery::newDocumentHTML('<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />' . $htmlurl, 'utf-8');
        $imgs = pq('img');
        foreach ($imgs as $img) {
            $src = pq($img)->attr('data-src');
            $srcs ='/api/Imageout/imagesout?url='.$src;
            $simg = pq($img)->attr('src', $srcs);
            pq($img)->attr('data-src', '');
        }

        $vedios = pq('iframe');

        $vplay = 'https://v.qq.com/iframe/preview.html';
        foreach ($vedios as $vedio) {

            $vds = pq($vedio)->attr('data-src');

            $reg = "/width=[0-9]*&height=[0-9]*&/";

            $vdrp = preg_replace($reg, $vplay, $vds);
            pq($vedio)->attr('src', $vdrp);
            pq($vedio)->attr('data-src', '');
        }

        pq('#activity-name')->remove();
        pq('.rich_media_meta_list')->remove();
        pq('#js_toobar3')->remove();
        pq('.qr_code_pc')->remove();
        if (pq('#js_cmt_area')) {

            pq('#js_cmt_area')->remove();
        }

        if (pq('#js_minipro_dialog')) {

            pq('#js_minipro_dialog')->remove();
        }
        $title = pq('title')->text();

        pq('script')->remove();

        $this->assign("title", $title);
        $this->assign("content", $hobj);
        return $this->fetch("article/collectedArticle");


    }




  public    function      tocaiji(){


     return  $this ->fetch("article/articleCollect");



  }
}
?>