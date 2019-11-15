<?php
Db::query("

CREATE TABLE `bbc_pin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sld_goods_id` int(11) DEFAULT '0' COMMENT '商品common_id',
  `sld_vid` int(11) DEFAULT '0' COMMENT '店铺id',
  `sld_type` int(11) DEFAULT '1' COMMENT '拼团分类id',
  `sld_pic` varchar(128) DEFAULT '0' COMMENT '封面图',
  `sld_start_time` int(11) DEFAULT '0' COMMENT '拼团有效期 开始',
  `sld_end_time` int(11) DEFAULT NULL COMMENT '拼团有效期 结束',
  `sld_return_leader` decimal(10,2) DEFAULT NULL COMMENT '团长返 多少钱',
  `sld_max_buy` int(3) DEFAULT NULL COMMENT '每人限购',
  `sld_team_count` int(3) DEFAULT NULL COMMENT '成团人数',
  `sld_success_time` decimal(10,2) DEFAULT '0.00' COMMENT '成功时间',
  `sld_status` int(11) DEFAULT '1' COMMENT '活动状态 0 未发布 1  正常（可拼） ',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=47 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='开团管理';

CREATE TABLE `bbc_pin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `goods_id` int(11) DEFAULT '0',
  `user_id` int(11) DEFAULT '0',
  `prolong_hours` int(11) DEFAULT '0',
  `tlevel` int(11) DEFAULT '1',
  `tuan_time` int(11) DEFAULT '0',
  `success_time` int(11) DEFAULT '0',
  `tuan_status` int(11) DEFAULT '1',
  `do_status` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='开团管理';

CREATE TABLE `bbc_pin_goods` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `sld_pin_id` int(10) DEFAULT '0' COMMENT '关联拼团活动id',
  `sld_gid` int(10) DEFAULT NULL COMMENT '商品gid',
  `sld_pin_price` decimal(10,2) DEFAULT '0.00' COMMENT '商品拼团价',
  `sld_stock` int(10) DEFAULT '0' COMMENT '拼团库存',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=37 DEFAULT CHARSET=utf8 COMMENT='拼团商品关联表';

CREATE TABLE `bbc_pin_team` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sld_pin_id` int(11) DEFAULT '0' COMMENT '活动id',
  `sld_common_id` int(11) DEFAULT '0' COMMENT '产品id',
  `sld_add_time` int(11) DEFAULT '0' COMMENT '参团时间',
  `sld_leader_id` int(11) DEFAULT NULL COMMENT '团长id',
  `sld_tuan_status` int(1) DEFAULT '0' COMMENT '成团 0 进行中 1 成功 2失败',
  PRIMARY KEY (`id`),
  KEY `idx_tuid_tyid` (`sld_pin_id`)
) ENGINE=MyISAM AUTO_INCREMENT=42 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='开团队伍表';

CREATE TABLE `bbc_pin_team_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sld_gid` int(11) DEFAULT '0' COMMENT '产品id',
  `sld_order_id` int(11) DEFAULT '0' COMMENT '订单id',
  `sld_team_id` int(11) DEFAULT NULL COMMENT '队伍id',
  `sld_user_id` int(11) DEFAULT '0' COMMENT '用户id',
  `sld_add_time` int(11) DEFAULT '0' COMMENT '参团时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`sld_user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=49 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='开团队伍表';

CREATE TABLE `bbc_pin_type` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '分类id',
  `sld_typename` varchar(32) DEFAULT NULL COMMENT '名称',
  `sld_banner` varchar(128) DEFAULT NULL COMMENT 'banner图',
  `sld_parent_id` int(10) DEFAULT '0' COMMENT '上级id',
  `sld_icon` varchar(128) DEFAULT NULL COMMENT '标志图片',
  `sld_status` tinyint(1) DEFAULT '1' COMMENT '是否启用',
  `sld_sort` int(5) DEFAULT '50' COMMENT '排序',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 COMMENT='拼团分类表';

ALTER TABLE `bbc_order` 
ADD COLUMN `pin_id` int(10) NULL DEFAULT 0 COMMENT '拼团id';

");

