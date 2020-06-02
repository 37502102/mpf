SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for mpf_dictionaries
-- ----------------------------
DROP TABLE IF EXISTS `mpf_dictionaries`;
CREATE TABLE `mpf_dictionaries`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `dic_key` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `dic_value` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `dic_order` smallint(6) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `name`(`name`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 13 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of mpf_dictionaries
-- ----------------------------
INSERT INTO `mpf_dictionaries` VALUES (1, '是否', '0', '否', 0);
INSERT INTO `mpf_dictionaries` VALUES (2, '是否', '1', '是', 1);
INSERT INTO `mpf_dictionaries` VALUES (3, '状态', '0', '禁用', 0);
INSERT INTO `mpf_dictionaries` VALUES (4, '状态', '1', '正常', 1);
INSERT INTO `mpf_dictionaries` VALUES (5, '模块状态', '0', '禁用', 0);
INSERT INTO `mpf_dictionaries` VALUES (6, '模块状态', '1', '启用', 1);
INSERT INTO `mpf_dictionaries` VALUES (7, '模块状态', '2', '隐藏', 2);
INSERT INTO `mpf_dictionaries` VALUES (8, '部门', '0', '无', 0);
INSERT INTO `mpf_dictionaries` VALUES (9, '部门', '1', '总裁办', 1);
INSERT INTO `mpf_dictionaries` VALUES (10, '部门', '2', '技术部', 2);
INSERT INTO `mpf_dictionaries` VALUES (11, '模块平台', '1', '后台', 0);
INSERT INTO `mpf_dictionaries` VALUES (12, '模块平台', '2', '前台', 1);

-- ----------------------------
-- Table structure for mpf_groups
-- ----------------------------
DROP TABLE IF EXISTS `mpf_groups`;
CREATE TABLE `mpf_groups`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(60) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `modules` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `edits` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `department` tinyint(4) UNSIGNED NOT NULL DEFAULT 0,
  `status` tinyint(4) UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `department`(`department`) USING BTREE,
  UNIQUE INDEX `name`(`name`, `department`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of mpf_groups
-- ----------------------------
INSERT INTO `mpf_groups` VALUES (1, '系统管理员', 'all', 'all', 0, 1);

-- ----------------------------
-- Table structure for mpf_modules
-- ----------------------------
DROP TABLE IF EXISTS `mpf_modules`;
CREATE TABLE `mpf_modules`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(60) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `url` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `actions` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `fatherid` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `bewrite` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `mod_order` smallint(6) UNSIGNED NOT NULL DEFAULT 0,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `icon` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `platform` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fatherid`(`fatherid`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 10 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of mpf_modules
-- ----------------------------
INSERT INTO `mpf_modules` VALUES (1, '系统', '', '', 0, '', 1, 1, 'el-icon-setting', 1);
INSERT INTO `mpf_modules` VALUES (2, '字典', 'mpf_Dictionary', '', 1, '项目的字典管理', 1, 1, '', 1);
INSERT INTO `mpf_modules` VALUES (3, '模块', 'mpf_Module', '', 1, '管理各平台下的模块', 2, 1, '', 1);
INSERT INTO `mpf_modules` VALUES (4, '用户', 'mpf_User', '', 1, '管理用户及分配岗位', 4, 1, '', 1);
INSERT INTO `mpf_modules` VALUES (5, '岗位', 'mpf_Group', '', 1, '管理岗位及分配权限', 3, 1, '', 1);
INSERT INTO `mpf_modules` VALUES (6, '使用日志', 'mpf_UseLog', '', 1, '用户操作后台的日志', 5, 1, '', 1);
INSERT INTO `mpf_modules` VALUES (7, '更新代码', 'mpf_Codeupdate', '', 1, '更新服务器端代码', 10, 1, '', 1);
INSERT INTO `mpf_modules` VALUES (8, '用户登陆', 'login', '', 1, '占位,用于在使用日志中查看用户登陆情况', 101, 2, '', 1);
INSERT INTO `mpf_modules` VALUES (9, '定时任务', 'cron', '', 1, '占位,用于在使用日志中查看定时任务运行情况', 100, 2, '', 1);

-- ----------------------------
-- Table structure for mpf_uselogs
-- ----------------------------
DROP TABLE IF EXISTS `mpf_uselogs`;
CREATE TABLE `mpf_uselogs`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) UNSIGNED NOT NULL,
  `module_id` int(11) UNSIGNED NOT NULL,
  `action` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `data` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `ip` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `create_date` date NOT NULL,
  `create_time` time(0) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `create_date`(`create_date`) USING BTREE,
  INDEX `module_id`(`module_id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of mpf_uselogs
-- ----------------------------
INSERT INTO `mpf_uselogs` VALUES (1, 1, 9, 'cache', '[]', '127.0.0.1', '2020-06-02', '07:52:23');

-- ----------------------------
-- Table structure for mpf_users
-- ----------------------------
DROP TABLE IF EXISTS `mpf_users`;
CREATE TABLE `mpf_users`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(60) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `passwd` varchar(60) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '$2y$10$fj0Tb2E3wZNVy2oXCWeOGej.qKQbBgdAqQHJ0sFJzPTdw.fDn9nZi',
  `realname` varchar(60) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `pagesize` int(10) UNSIGNED NOT NULL DEFAULT 20,
  `department` tinyint(4) UNSIGNED NOT NULL DEFAULT 0,
  `status` tinyint(4) UNSIGNED NOT NULL DEFAULT 1,
  `groups` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `remember_token` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `create_time` datetime(0) NOT NULL,
  `update_time` datetime(0) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `department`(`department`) USING BTREE,
  UNIQUE INDEX `username`(`username`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of mpf_users
-- ----------------------------
INSERT INTO `mpf_users` VALUES (1, 'admin', '$2y$10$x.SRQ55nVrZROgdC4bz66.cAdP9Bm6hEAAnExkLnOAnP3zTCDgAUy', '管理', 100, 0, 1, '1', 'J8Oqh54PkoZMvabFYLiN6jQpYTOQqHfnccMLh3OujM6hUgqQFbyDix1BQuiC', '2017-03-29 17:20:27', '2020-05-30 13:17:33');

SET FOREIGN_KEY_CHECKS = 1;
