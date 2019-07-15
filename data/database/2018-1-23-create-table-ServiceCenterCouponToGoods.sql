# 服务中心券换商品（兑货券和赠品券）表

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE ty_service_center_coupon_to_goods(
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `coupon_num` DECIMAL(11, 2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '申请兑换的券的数量',
  `user_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '申请兑换的会员',
  `address` VARCHAR(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '地址',
  `consignee` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '收件人',
  `phone` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL  DEFAULT '' COMMENT '收件人电话',
  `goods_num` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '多少单位的货',
  `coupon_ty` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '券的类型：1、兑货券；2、赠品券',
  `status` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '1、申请成功;2、审核成功;3、驳回',
  `createtime` int(10) NULL DEFAULT NULL ,
  `updatetime` int(10) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `name`(`user_id`) USING BTREE
)ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;
SET FOREIGN_KEY_CHECKS = 1;
