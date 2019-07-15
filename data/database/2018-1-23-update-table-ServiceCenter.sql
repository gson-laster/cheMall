# 更新服务中心表

ALTER TABLE ty_service_center ADD zid INT(11) NOT NULL DEFAULT 0 COMMENT '排序';
ALTER TABLE ty_service_center ADD recommend TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否推荐';
ALTER  TABLE ty_service_center ADD status TINYINT(1) NOT NULL DEFAULT 0 COMMENT '状态';

