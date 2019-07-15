

# 服务中心货币表

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE ty_service_center_coin_note(
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL DEFAULT 0 COMMENT '用户id',
  `type` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1表示紧张2表示出账',
  `coin_type` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1、充值额度；2、配货券；3、赠品券',
  `note` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '变动说明',
  `order_id` INT(11) NOT NULL DEFAULT 0 COMMENT '相关联的申请表的id',
  `order_snapshot` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci COMMENT '当前订单快照（相关数据存json）',
  `createtime` int(10) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `name`(`user_id`) USING BTREE
)ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;
SET FOREIGN_KEY_CHECKS = 1;
