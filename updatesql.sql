ALTER TABLE `projekt`.`bbc_member`
ADD COLUMN `member_identification_picture` varchar(255) NULL COMMENT ''身份证图片'' AFTER `member_truename`,
ADD COLUMN `member_certification` tinyint(1) NULL DEFAULT 0 COMMENT ''是否通过认证，1 通过，0 未通过'' AFTER `member_identification_picture`,
ADD COLUMN `member_identification_number` varchar(18) NULL COMMENT ''身份证号码'' AFTER `member_certification`,
ADD COLUMN `member_mobile_imei` varchar(255) NULL COMMENT '手机识别号，串号' AFTER `member_mobile`,