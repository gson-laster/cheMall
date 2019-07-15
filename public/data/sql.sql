CREATE TABLE ty_agent_getmoney_info (
  `id` INT(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL COMMENT '代理id',
  `bank_code` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '银行卡号',
  `bank_name` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '银行名称',
  `bank_kfh` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '开户行名称'
)
