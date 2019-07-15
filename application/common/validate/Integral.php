<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/25 0025
 * Time: 下午 4:25
 */

namespace app\common\validate;




use think\Validate;

class Integral extends Validate
{


    protected   $rule=[
           'order_amount'=>'number|between:500,999999',
    ];
    protected   $massage=[
           'order_amount.require'=>'订货金额必须且需要大于500'


    ];
    protected  $scene=[

        'add'   =>['order_amount']
    ];



}