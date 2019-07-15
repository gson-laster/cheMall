<?php
namespace app\api\controller;

class Imageout extends Base
{

 public function imagesOut($url = '')
    {
        $pics = file($url);
        for ($i = 0; $i < count($pics); $i ++) {
            echo $pics[$i];
        }
    }
}

?>