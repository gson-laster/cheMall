# 服务中心表

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE ty_service_center_detail(
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `service_id` INT(11) NOT NULL DEFAULT 0 COMMENT '服务中心id',
  `content` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci COMMENT '服务中心详细说明',
  `createtime` int(10) NULL DEFAULT NULL ,
  `updatetime` int(10) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `name`(`service_id`) USING BTREE
)ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;
SET FOREIGN_KEY_CHECKS = 1;
