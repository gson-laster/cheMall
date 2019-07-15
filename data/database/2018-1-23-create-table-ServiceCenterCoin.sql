# 服务中心货币记录表

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE ty_service_center_coin(
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL DEFAULT 0 COMMENT '用户id',
  `recharge_quota` DECIMAL(11, 2) NOT NULL DEFAULT 0 COMMENT '充值额度',
  `distribution_ticket` DECIMAL(11, 2) NOT NULL DEFAULT 0 COMMENT '配货券',
  `comp_ticket` DECIMAL(11, 2) NOT NULL DEFAULT 0 COMMENT '赠品券',
  `createtime` int(10) NULL DEFAULT NULL ,
  `updatetime` int(10) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `name`(`user_id`) USING BTREE
)ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;
SET FOREIGN_KEY_CHECKS = 1;
