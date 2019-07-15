<?php
namespace app\admin\Validate;

use think\Validate;
class Artclass extends Validate
{
    protected  $rule=[

        ['title','require','分类不能为空！'],

        ['zid','require','请填写排序'],

        ['titlecon','require','请填写 '],

    ];
}
?>