<?php
namespace app\admin\Validate;


use think\Validate;
class Articlebanner extends Validate
{

    protected $rule=[
        ['title','require','标题不能为空！'],
        ['classid','require','分类不能为空！'],

    ];
}

?>