# 服务中心券换商品（兑货券和赠品券）表

ALTER TABLE ty_service_center_coupon_to_goods ADD shipping_number VARCHAR(120) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '快递单号';

ALTER TABLE ty_service_center_coupon_to_goods ADD shipping_code VARCHAR(120) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '快递编号';

ALTER TABLE ty_service_center_coupon_to_goods ADD shipping_time INT(10) DEFAULT 0 NOT NULL COMMENT '发货时间';

ALTER TABLE ty_service_center_coupon_to_goods ADD receipt_time INT(10) DEFAULT 0 NOT NULL COMMENT '收货时间';
