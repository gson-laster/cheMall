# 更新服务中心充值表

ALTER TABLE ty_apply_recharge ADD ticket VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '充值单号';

