# 服务中心收入记录表

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE ty_service_center_free(
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `service_id` INT(11) NOT NULL DEFAULT 0 COMMENT '服务中心id',
  `user_id` INT(11) NOT NULL DEFAULT 0 COMMENT '用户id',
  `commission` DECIMAL(10, 2) NOT NULL DEFAULT 0 COMMENT '收入比例',
  `number` DECIMAL(11, 2) NOT NULL DEFAULT 0 COMMENT '收入数额',
  `type` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '何种币收入1、充值额度；2、配货券；3、礼品券',
  `createtime` int(10) NULL DEFAULT NULL ,
  `updatetime` int(10) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  INDEX `service_id`(`service_id`) USING BTREE
)ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;
SET FOREIGN_KEY_CHECKS = 1;
