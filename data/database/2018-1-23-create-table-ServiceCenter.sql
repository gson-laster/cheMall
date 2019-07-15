# 服务中心表

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE ty_service_center(
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '服务中心名称',
  `thumb_pic` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '服务中心缩略图',
  `sub_title` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci COMMENT '服务中心简要说明',
  `longitude` FLOAT(7, 7) DEFAULT 0 NOT NULL  COMMENT '服务中心经度',
  `dimensionality` FLOAT(7, 7) DEFAULT 0 NOT NULL COMMENT '服务中心维度',
  `address` VARCHAR(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '服务中心地址',
  `createtime` int(10) NULL DEFAULT NULL ,
  `updatetime` int(10) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `name`(`title`) USING BTREE
)ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;
SET FOREIGN_KEY_CHECKS = 1;
