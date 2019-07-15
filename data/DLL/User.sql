-- auto-generated definition
create table ty_user
(
  id int not null auto_increment
    primary key,
  phone varchar(20) default '' null,
  nickname varchar(32) default '' null,
  userimg varchar(255) default '' null,
  password varchar(32) default '' null,
  email char(255) default '' null,
  sex mediumint(1) default '1' null,
  record int default '0' null,
  user_vb decimal(10,2) unsigned default '0.00' null,
  address_now int default '0' null,
  bindwx int default '0' null,
  real_id int default '0' null,
  createtime int(10) default '0' null,
  updatetime int(10) default '0' null,
  status tinyint(1) default '1' null,
  token varchar(500) default '' null,
  pid int default '0' null,
  agent_type int default '0' null,
  parent_agent int default '0' null,
  is_employ_agent tinyint default '0' null,
  is_show_qrcode tinyint default '0' null,
  is_privilege tinyint default '0' not null,
  pay_password varchar(50) default '' not null,
  is_cansharered tinyint default '0' not null,
  trading_stamp int default '0' null,
  order_amount decimal(9,2) default '0.00' null,
  proportions float null,
  constraint phone
  unique (phone)
)
;

comment on column ty_user.phone is '用户登录的电话号码'
;

comment on column ty_user.nickname is '用户名'
;

comment on column ty_user.userimg is '用户头像'
;

comment on column ty_user.password is '用户密码'
;

comment on column ty_user.email is '用户邮箱'
;

comment on column ty_user.sex is '性别1男2女3保密'
;

comment on column ty_user.record is '用户积分'
;

comment on column ty_user.user_vb is '用户vb'
;

comment on column ty_user.address_now is '当前默认收货地址的id'
;

comment on column ty_user.bindwx is '是否绑定微信0否，其他表示微信信息表的id'
;

comment on column ty_user.real_id is '实名认证id'
;

comment on column ty_user.status is '-1=>'删除',0=>'禁用',1=>'正常',2=>'待审核''
;

comment on column ty_user.parent_agent is '上级区域代理id'
;

comment on column ty_user.is_employ_agent is '是否是公司代理'
;

comment on column ty_user.is_show_qrcode is '是否显示二维码'
;

comment on column ty_user.is_privilege is '是否是特权用户'
;

comment on column ty_user.pay_password is '支付密码'
;

comment on column ty_user.is_cansharered is '可以分红'
;

comment on column ty_user.trading_stamp is '赠品券'
;

comment on column ty_user.order_amount is '订货额'
;

comment on column ty_user.proportions is '推荐分成比例'
;

