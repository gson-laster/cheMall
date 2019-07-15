<?php

$APP =  [
    // +----------------------------------------------------------------------
    // | 应用设置
    // +----------------------------------------------------------------------

    // 应用命名空间
    'app_namespace'          => 'app',
    // 应用调试模式
    'app_debug'              => true,
    // 应用Trace
    'app_trace'              => true,
    // 应用模式状态
    'app_status'             => '',
    // 是否支持多模块
    'app_multi_module'       => true,
    // 入口自动绑定模块
    'auto_bind_module'       => false,
    // 注册的根命名空间
    'root_namespace'         => [],
    // 扩展函数文件
    'extra_file_list'        => [THINK_PATH . 'helper' . EXT, APP_PATH.'functions'.EXT],
    // 默认输出类型
    'default_return_type'    => 'html',
    // 默认AJAX 数据返回格式,可选json xml ...
    'default_ajax_return'    => 'json',
    // 默认JSONP格式返回的处理方法
    'default_jsonp_handler'  => 'jsonpReturn',
    // 默认JSONP处理方法
    'var_jsonp_handler'      => 'callback',
    // 默认时区
    'default_timezone'       => 'PRC',
    // 是否开启多语言
    'lang_switch_on'         => false,
    // 默认全局过滤方法 用逗号分隔多个
    'default_filter'         => '',
    // 默认语言
    'default_lang'           => 'zh-cn',
    // 应用类库后缀
    'class_suffix'           => false,
    // 控制器类后缀
    'controller_suffix'      => false,

    'extra_config_list'      => ['database', 'shipping_cof'],

    // +----------------------------------------------------------------------
    // | 模块设置
    // +----------------------------------------------------------------------

    // 默认模块名
    'default_module'         => 'home',
    // 禁止访问模块
    'deny_module_list'       => ['common'],
    // 默认控制器名
    'default_controller'     => 'Index',
    // 默认操作名
    'default_action'         => 'index',
    // 默认验证器
    'default_validate'       => '',
    // 默认的空控制器名
    'empty_controller'       => 'Error',
    // 操作方法后缀
    'action_suffix'          => '',
    // 自动搜索控制器
    'controller_auto_search' => false,

    // +----------------------------------------------------------------------
    // | URL设置
    // +----------------------------------------------------------------------

    // PATHINFO变量名 用于兼容模式
    'var_pathinfo'           => 's',
    // 兼容PATH_INFO获取
    'pathinfo_fetch'         => ['ORIG_PATH_INFO', 'REDIRECT_PATH_INFO', 'REDIRECT_URL'],
    // pathinfo分隔符
    'pathinfo_depr'          => '/',
    // URL伪静态后缀
    'url_html_suffix'        => 'html',
    // URL普通方式参数 用于自动生成
    'url_common_param'       => false,
    // URL参数方式 0 按名称成对解析 1 按顺序解析
    'url_param_type'         => 0,
    // 是否开启路由
    'url_route_on'           => true,
    // 路由使用完整匹配
    'route_complete_match'   => false,
    // 路由配置文件（支持配置多个）
    'route_config_file'      => ['admin_route', 'home_router'],
    // 是否强制使用路由
    'url_route_must'         => false,
    // 域名部署
    'url_domain_deploy'      => false,
    // 域名根，如thinkphp.cn
    'url_domain_root'        => '',
    // 是否自动转换URL中的控制器和操作名
    'url_convert'            => true,
    // 默认的访问控制器层
    'url_controller_layer'   => 'controller',
    // 表单请求类型伪装变量
    'var_method'             => '_method',
    // 表单ajax伪装变量
    'var_ajax'               => '_ajax',
    // 表单pjax伪装变量
    'var_pjax'               => '_pjax',
    // 是否开启请求缓存 true自动缓存 支持设置请求缓存规则
    'request_cache'          => false,
    // 请求缓存有效期
    'request_cache_expire'   => null,

    // +----------------------------------------------------------------------
    // | 模板设置
    // +----------------------------------------------------------------------

    'template'               => [
        // 模板引擎类型 支持 php think 支持扩展
        'type'         => 'Think',
        // 模板路径
        'view_path'    => '',
        // 模板后缀
        'view_suffix'  => 'html',
        // 模板文件名分隔符
        'view_depr'    => DS,
        // 模板引擎普通标签开始标记
        'tpl_begin'    => '{',
        // 模板引擎普通标签结束标记
        'tpl_end'      => '}',
        // 标签库标签开始标记
        'taglib_begin' => '{',
        // 标签库标签结束标记
        'taglib_end'   => '}',
    ],

    // 视图输出字符串内容替换
    'view_replace_str'       => [],
    // 默认跳转页面对应的模板文件
    'dispatch_success_tmpl'  => THINK_PATH . 'tpl' . DS . 'dispatch_jump.tpl',
    'dispatch_error_tmpl'    => THINK_PATH . 'tpl' . DS . 'dispatch_jump.tpl',

    //====================================自有配置======================================================
    'user_token' => 'tywlkj',
    'user_login_key' => 'tywlkjslade',
    'pageSize'  => 15,
    'upload_size' => 5120 * 1000, //上传大小限制 单位b
    'upload_ext' => 'jpg,png,gif,jpeg', //上传文件类型限定，
    'upload_main_path' => ROOT_PATH . 'public' . DS . 'upload', //上传文件存储主文件夹
    'upload_file_name' => 'date', //上传文件命名方式

    //短信模板相关
    'SEND_SCENE' => [//使用场景
      'register' => ['用户注册','您正在注册${name}，验证码${code}'],
      'login'    => ['用户登录', '您正在注册${name}，验证码${code}'],
      'forget_password' => ['找回密码','您正在进行${site_name}手机号码验证，验证码${code}，请于${minute}分钟内输入验证，工作人员不会向您索取，请勿泄露。'],
      'do_order_success'=> ['下单成功','您在${site_name}的订单${order_sn}创建成功，请及时支付，我们将尽快给您安排发货！'],
      'do_pay_success'  => ['支付成功','您在${site_name}的订单,订单号:${order_code}已经成功支付，支付方式:${payment},请耐心等待发货.'],
      'do_send_goods_success' => ['发货成功','尊敬的${site_name}用户，您的订单${order_sn}已发货，收货人${consignee}，物流单号:${shipping_code}，请留意物流信息及时查收'],
      'do_get_money_form_other' => ['分成成功','您有一笔订单分成成功，金额${price}，请登录平台确认'],
      'change_phone'      => ['修改手机号码','尊敬的${user_name}用户，验证码${code}, 您正在修改/绑定手机号码, 打死不要告诉别人.']
    ],
    'SMS_DEV_ID'   => 'fbfdbc3be0ad40c1bcd5c6c9ce196d33',
    'SMS_DEV_KEY'  => '6ff70ca3c0394888abf7653c75b890ff',
    'SMS_SEND_URL' => 'http://www.xinxinke.com/api/send',
    'SMS_SIGN'     => '世星数码商城',
    'ADMIN_TOKEN'  => 'tywlkj',
    'PAY_TOKEN'    => 'tywlkj5355988',

    //订单状态
    'ORDER_STATUS' =>[
        1 => '待确认',
        2 => '已确认',
        3 => '已收货',
        4 => '已取消',
        5 => '已完成', //评价完
        6 => '已作废',
        7 => '已分成',
        8 => '暂不分成'
    ],
    'SHIPPING_STATUS' => array(
        1 => '未发货',
        2 => '已发货',
        3 => '部分发货'
    ),
    'PAY_STATUS' => array(
        1 => '未支付',
        2 => '已支付',
        3 => '支付失败'
    ),
    'SEX' => [
        0 => '保密',
        1 => '男',
        2 => '女'
    ],
    'agent_kefu_account' => '18978908220', // 财务人员  充值提醒 申请代理提醒
    'shipping_kefu_account' => '18978908220', //  发货人员 订单提醒 发货确认收货提醒
    'message_kefu_account' => '18978908220', // 客服人员 消息管理提醒
    'ORDER_STATUS_DESC' => [
        'WAITPAY' => '待支付',
        'WAITSEND'=>'待发货',
        'WAITRECEIVE'=>'待收货',
        'WAITCCOMMENT'=> '待评价',
        'CANCEL'=> '已取消',
        'FINISH'=> '已完成', //
        'CANCELLED'=> '已作废'
    ],
    // 订单用户端显示状态
    'WAITPAY'=>' AND pay_status = 1 AND order_status = 1 ', //订单查询状态 待支付
    'WAITPAY2'=>' AND pay_status = 1 AND (order_status = 1 OR order_status=2) ', //订单查询状态 待支付
    'WAITSEND'=>' AND pay_status=2 AND shipping_status =1 AND order_status in(1,2) ', //订单查询状态 待发货
    'WAITRECEIVE'=>' AND shipping_status=2 AND order_status = 2 ', //订单查询状态 待收货
    'WAITCCOMMENT'=> ' AND order_status=3 ', // 待评价 确认收货     //'FINISHED'=>'  AND order_status=1 ', //订单查询状态 已完成
    'FINISH'=> ' AND order_status = 5 ', // 已完成
    'CANCEL'=> ' AND order_status = 4 ', // 已取消
    'CANCELLED'=> ' AND order_status = 6 ',//已作废
    'PAYEND'  => ' AND pay_status=2 AND order_status in (2,3,4,5,6,7)', // 已经付款
    'ALL' => '', //全部
    //图片水印
    'water' => [
      'is_mark' => 0,
      'mark_txt' => '全民商城',
      'mark_img'  => '',
      'mark_width'=> '',
      'mark_height'=> '',
      'mark_degree' => 54,//水印透明度
      'mark_quality'=> 56, // JPEG 水印质量
      'sel'=>9
    ],

    'SHIPPING'=>[
      'EBusinessID' => '1258996',//电商ID
      'AppKey'      =>  'c5dad4b4-8569-41c9-9f2c-868e9711fe96',//电商加密私钥，快递鸟提供，注意保管，不要泄漏
      'ReqURL'      =>  'http://api.kdniao.cc/Ebusiness/EbusinessOrderHandle.aspx' ////请求url
    ],
    "shipping_config" => [
      'ZITI'        => '自提',
      'ANE'         => '安能物流',
      'AXD'         => '安信达快递',
      'BTWL'        => '百世快运',
      'CITY100'     => '城市100',
      'DBL'         => '德邦',
      'EMS'         => 'EMS',
      'FEDEX'       => 'FEDEX联邦(国内件）',
      'FEDEX_GJ'    => 'FEDEX联邦(国际件）',
      'GDEMS'       => '广东邮政',
      'GTO'         => '国通快递',
      'GTSD'        => '高铁速递',
      'HHTT'        => '天天快递',
      'HTKY'        => '百世快递',
      'SF'          => '顺丰快递',
      'ST'          => '速通物流',
      'STO'         => '申通快递',
      'UC'          => '优速快递',
      'WXWL'        => '万象物流',
      'YD'          => '韵达快递',
      'YTO'         => '圆通速递',
      'YZPY'        => '邮政平邮/小包',
      'ZTE'         => '众通快递',
      'ZTO'         => '中通速递',
      'JIAXIN'      => '嘉欣物流',
      'SURE'        => '速尔快递'
    ],

    // 微信模板消息
    "WECHAT_TEAMPLATE" => [
      'ADD_ORDER'     => 'duaFNxORVKiHWQTXE-DUymogwjCePmZ_W3wPeWKQS2M',    //下单成功
      'ACOUNT_CHANGE'=> 'WbAStXPdHQSnn7nbQRjgrV2qpHu8XxCY_9z7pATOIgg',    //vb变动
      'PAY_SUCCESS'   => 'BoWzN4t-o8oH6ZscYkI1exq1p74thv-dXVuB-EnCHSI',    //消费成功
      'SEND_GOODS'    => 'ksGzzEZtZneN2JuFjMTH0t18ARHBuTfS7eh4G62dsFg',    //发货成功
      'CONFIRM_GOODS' =>'teOg4hf-5lONGdlKwQnkYH04vXpYms7qnS61LU6QxS8',     //确认收货通知
      'TASK_PROCESSING'=>'R3fHNW_D2KbBWjVqVMO5CokYKVi9aWJnlAl4Gisugzg',    //任务处理通知
      'PAY_SUC'=>'BoWzN4t-o8oH6ZscYkI1exq1p74thv-dXVuB-EnCHSI',          //支付成功
      'COUNSEL'=>'ugigEQJChnnP3BSPBdzlKsreB33Pi1qeobh1V9NqGlI'          // 咨询回复
    ],

      "WX_NOTIFY" => 'http://sxsmsc.weifwpt.com/home/Weichat/pay_callback/',

    // +----------------------------------------------------------------------
    // | 异常及错误设置
    // +----------------------------------------------------------------------
    /*'exception_tmpl' => [
       // 404 => ROOT_PATH . '/public/404phone.html',
       // 500 => ROOT_PATH . '/public/500phone.html',
    ],*/
    // 异常页面的模板文件
   'exception_tmpl'         => THINK_PATH . 'tpl' . DS . 'think_exception.tpl',

    // 错误显示信息,非调试模式有效
    'error_message'          => '页面错误！请稍后再试～',
    // 显示错误信息
    'show_error_msg'         => true,
    // 异常处理handle类 留空使用 \think\exception\Handle
    'exception_handle'       => '',

    // +----------------------------------------------------------------------
    // | 日志设置
    // +----------------------------------------------------------------------

    'log'                    => [
        // 日志记录方式，内置 file socket 支持扩展
        'type'  => 'File',
        // 日志保存目录
        'path'  => LOG_PATH,
        // 日志记录级别
        'level' => [],
    ],
    'app_trace' => false,
    // +----------------------------------------------------------------------
    // | Trace设置 开启 app_trace 后 有效
    // +----------------------------------------------------------------------
    'trace'                  => [
        // 内置Html Console 支持扩展
        'type' => 'Html',
    ],

    // +----------------------------------------------------------------------
    // | 缓存设置
    // +----------------------------------------------------------------------

    'cache'                  => [
        // 驱动方式
        'type'   => 'File',
        // 缓存保存目录
        'path'   => CACHE_PATH,
        // 缓存前缀
        'prefix' => '',
        // 缓存有效期 0表示永久缓存
        'expire' => 0,
    ],

    // +----------------------------------------------------------------------
    // | 会话设置
    // +----------------------------------------------------------------------

    'session'                => [
        'id'             => '',
        // SESSION_ID的提交变量,解决flash上传跨域
        'var_session_id' => '',
        // SESSION 前缀
        'prefix'         => 'think',
        // 驱动方式 支持redis memcache memcached
        'type'           => '',
        // 是否自动开启 SESSION
        'auto_start'     => true,
    ],

    // +----------------------------------------------------------------------
    // | Cookie设置
    // +----------------------------------------------------------------------
    'cookie'                 => [
        // cookie 名称前缀
        'prefix'    => '',
        // cookie 保存时间
        'expire'    => 0,
        // cookie 保存路径
        'path'      => '/',
        // cookie 有效域名
        'domain'    => '',
        //  cookie 启用安全传输
        'secure'    => false,
        // httponly设置
        'httponly'  => '',
        // 是否使用 setcookie
        'setcookie' => true,
    ],

    //分页配置
    'paginate'               => [
        'type'      => 'bootstrap',
        'var_page'  => 'page',
        'list_rows' => 15,
    ],

    //验证码
    'captcha'  => [
        // 验证码字符集合
        'codeSet'  => '2345678abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRTUVWXY',
        // 验证码字体大小(px)
        'fontSize' => 16,
        // 是否画混淆曲线
        'useCurve' => true,
         // 验证码图片高度
        'imageH'   => 38,
        // 验证码图片宽度
        'imageW'   => 140,
        // 验证码位数
        'length'   => 4,
        // 验证成功后是否重置
        'reset'    => true
    ],
    'HTML_CACHE_ARR'=> [
        ['mca'=>'home_Goods_index','p'=>['id']],
        ['mca'=>'home_Index_index'],  // 缓存首页静态页面
        // ['mca'=>'home_Goods_ajaxComment','p'=>['goods_id','commentType','p']],  // 缓存评论静态页面 http://www.tpshop2.0.com/index.php?m=Home&c=Goods&a=ajaxComment&goods_id=142&commentType=1&p=1
        // ['mca'=>'home_Goods_ajax_consult','p'=>['goods_id','consult_type','p']],  // 缓存咨询静态页面 http://www.tpshop2.0.com/index.php?m=Home&c=Goods&a=ajax_consult&goods_id=142&consult_type=0&p=2
    ],
    'Message_Register_Address' => '127.0.0.1:1238',
    'every_month_divide' => 0.05, //每月分成多少
    'every_agent_company' => 0.4,


    // =============================== 活动 ============================
    // 1、充值活动2、购买活动3、代理开通
    'active_type' => [
//      'RECHARGE' => '充值活动',
      'GOODSORDER' => '升级活动',
//      'AGENTAPPLY' => '代理开通活动'
    ],

    // 活动字段
    'active_field' => [
      'user@user_vb' => '用户vb',
      'user@agent_type' => '用户身份',
    ],
    'active_hook' => [
      /*'Order@oneAddSuccess' => '单个商品下单成功',
      'Order@mutiAddSuccess' => '多个商品下单成功',
      'Order@PayBeagin' => '商品订单开始支付',*/
      'Order@PaySuccess' => '商品订单支付成功',
      'Order@SendSuccess' => '商品订单发货成功',
      /*'Agent@addSuccess' => '申请开通代理成功时',
      'Agent@confirm'    => '确认完成代理申请时',
      'Agent@returnToUser' => '申请开通代理退回给客户时',
      'Recharge@addSuccess' => '用户充值申请成功',
      'Recharge@success' => '用户充值成功',
      'Recharge@returnToUser' => '用户充值退回',*/
    ],

    'redis' => [
      'host'       => '127.0.0.1',
      'port'       => '6379',
      'password'   => '',
      'select'     => 0,
      'timeout'    => 0,
      'expire'     => 0,
      'persistent' => false,
      'prefix'     => '',
    ],

    'queue' => [
      'agentTeamNote' => 'app\api\job\agentTeam@queueAgentTeamNote',
      'agentTeamFree' => 'app\api\job\agentTeam@queueAgentTeamFreeJob',
      'agentTeamDivide' => 'app\api\job\agentTeam@queueAgentDivideJob',
      'agentTeamReward' => 'app\api\job\agentFivePoint@queueAgentTeamReward',
      'moAgentTeamDivide' => 'app\api\job\moAgentTeam@queueMoAgentDivide',
      'moShareRed' => 'app\api\job\shareRed@queueShareRed'
    ],
    //===========================================================================================
    'mo_agent_config' => [
      '1' => [
        'commission' => 0.03,
        'selfNumber' => 10,
        'teamNumber' => 50
      ],
      '2' => [
        'commission' => 0.02,
        'selfNumber' => 50,
        'teamNumber' => 250
      ],
      '3' => [
        'commission' => 0.01,
        'selfNumber' => 100,
        'teamNumber' => 1000
      ],
    ],

  // 市场营业额分红补贴
    'share_red_one' => '0.1',
    'share_red_two' => '0.05',
    'share_red_total_one' => 1000,
    'share_red_total_two' => 20000,
];
$APP_CONF = include_once WEB_ROOT.'/config/app.conf.php';
$config = array_merge($APP, $APP_CONF);
return $config;
