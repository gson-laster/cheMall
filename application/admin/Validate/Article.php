<?php
namespace app\admin\Validate;

use think\Validate;
class Article  extends Validate
{

    protected $rule=[

        ['title','require','标题不能为空！'],
        ['classid','require','分类不能为空！'],
        ['content','require','内容不能为空！'],


    ];

}

?>