ALTER TABLE `projekt`.`bbc_member`
ADD COLUMN `member_identification_picture` varchar(255) NULL COMMENT ''身份证图片'' AFTER `member_truename`,
ADD COLUMN `member_certification` tinyint(1) NULL DEFAULT 0 COMMENT ''是否通过认证，1 通过，0 未通过'' AFTER `member_identification_picture`,
ADD COLUMN `member_identification_number` varchar(18) NULL COMMENT ''身份证号码'' AFTER `member_certification`,
ADD COLUMN `member_mobile_imei` varchar(255) NULL COMMENT '手机识别号，串号' AFTER `member_mobile`,
DROP TABLE IF EXISTS `bbc_member_login_log`;
CREATE TABLE `bbc_member_login_log`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NULL DEFAULT NULL,
  `ip` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `ua` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `login_time` timestamp(0) NULL DEFAULT NULL COMMENT '登录时间',
  `create_time` timestamp(0) NULL DEFAULT NULL,
  `update_time` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;