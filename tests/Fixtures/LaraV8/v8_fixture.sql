-- Lara CMS v8.2.46 fixture for the lara:v8:* tests.
-- Schema: the v8 entity configuration and auth tables, from a v8.2.46 dump (only the groups → users foreign key removed).

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;
CREATE TABLE `lara_ent_entities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entity_model_class` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entity_key` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entity_controller` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `resource_routes` tinyint(1) NOT NULL DEFAULT '0',
  `has_front_auth` tinyint(1) NOT NULL DEFAULT '0',
  `menu_parent` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `menu_position` int(10) unsigned DEFAULT NULL,
  `menu_icon` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `locked_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lara_ent_entities_group_id_foreign` (`group_id`),
  CONSTRAINT `lara_ent_entities_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `lara_ent_entity_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `lara_ent_entity_groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `key` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `group_has_columns` tinyint(1) NOT NULL DEFAULT '0',
  `group_has_objectrelations` tinyint(1) NOT NULL DEFAULT '0',
  `group_has_filters` tinyint(1) NOT NULL DEFAULT '0',
  `group_has_panels` tinyint(1) NOT NULL DEFAULT '0',
  `group_has_media` tinyint(1) NOT NULL DEFAULT '0',
  `group_has_customcolumns` tinyint(1) NOT NULL DEFAULT '0',
  `group_has_relations` tinyint(1) NOT NULL DEFAULT '0',
  `group_has_views` tinyint(1) NOT NULL DEFAULT '0',
  `group_has_widgets` tinyint(1) NOT NULL DEFAULT '0',
  `group_has_sortable` tinyint(1) NOT NULL DEFAULT '0',
  `group_has_managedtable` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `locked_by` bigint(20) unsigned DEFAULT NULL,
  `position` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `lara_ent_entity_groups_locked_by_foreign` (`locked_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `lara_ent_entity_columns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entity_id` bigint(20) unsigned NOT NULL,
  `has_user` tinyint(1) NOT NULL DEFAULT '0',
  `has_lang` tinyint(1) NOT NULL DEFAULT '0',
  `has_slug` tinyint(1) NOT NULL DEFAULT '0',
  `has_lead` tinyint(1) NOT NULL DEFAULT '0',
  `has_body` tinyint(1) NOT NULL DEFAULT '0',
  `has_status` tinyint(1) NOT NULL DEFAULT '0',
  `has_hideinlist` tinyint(1) NOT NULL DEFAULT '0',
  `has_expiration` tinyint(1) NOT NULL DEFAULT '0',
  `has_app` tinyint(1) NOT NULL DEFAULT '0',
  `has_groups` tinyint(1) NOT NULL DEFAULT '0',
  `group_values` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `group_default` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_sortable` tinyint(1) NOT NULL DEFAULT '0',
  `sort_field` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sort_order` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sort2_field` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sort2_order` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `has_fields` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `lara_ent_entity_columns_entity_id_foreign` (`entity_id`),
  CONSTRAINT `lara_ent_entity_columns_entity_id_foreign` FOREIGN KEY (`entity_id`) REFERENCES `lara_ent_entities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `lara_ent_entity_panels` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entity_id` bigint(20) unsigned NOT NULL,
  `has_search` tinyint(1) NOT NULL DEFAULT '0',
  `has_batch` tinyint(1) NOT NULL DEFAULT '0',
  `has_filters` tinyint(1) NOT NULL DEFAULT '0',
  `show_author` tinyint(1) NOT NULL DEFAULT '0',
  `show_status` tinyint(1) NOT NULL DEFAULT '0',
  `has_tiny_lead` tinyint(1) NOT NULL DEFAULT '0',
  `has_tiny_body` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `lara_ent_entity_panels_entity_id_foreign` (`entity_id`),
  CONSTRAINT `lara_ent_entity_panels_entity_id_foreign` FOREIGN KEY (`entity_id`) REFERENCES `lara_ent_entities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `lara_ent_entity_object_relations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entity_id` bigint(20) unsigned NOT NULL,
  `has_seo` tinyint(1) NOT NULL DEFAULT '0',
  `has_opengraph` tinyint(1) NOT NULL DEFAULT '0',
  `has_layout` tinyint(1) NOT NULL DEFAULT '0',
  `has_related` tinyint(1) NOT NULL DEFAULT '0',
  `is_relatable` tinyint(1) NOT NULL DEFAULT '0',
  `has_tags` tinyint(1) NOT NULL DEFAULT '0',
  `tag_default` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `has_sync` tinyint(1) NOT NULL DEFAULT '0',
  `has_images` tinyint(1) NOT NULL DEFAULT '0',
  `has_videos` tinyint(1) NOT NULL DEFAULT '0',
  `has_videofiles` tinyint(1) NOT NULL DEFAULT '0',
  `has_files` tinyint(1) NOT NULL DEFAULT '0',
  `max_images` int(10) unsigned NOT NULL DEFAULT '1',
  `max_videos` int(10) unsigned NOT NULL DEFAULT '1',
  `max_videofiles` int(10) unsigned NOT NULL DEFAULT '1',
  `max_files` int(10) unsigned NOT NULL DEFAULT '1',
  `disk_images` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `disk_videos` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `disk_files` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lara_ent_entity_object_relations_entity_id_foreign` (`entity_id`),
  CONSTRAINT `lara_ent_entity_object_relations_entity_id_foreign` FOREIGN KEY (`entity_id`) REFERENCES `lara_ent_entities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `lara_ent_entity_custom_columns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entity_id` bigint(20) unsigned NOT NULL,
  `fieldtitle` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `fieldname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `fieldtype` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `fieldhook` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `fielddata` text COLLATE utf8_unicode_ci,
  `primary` tinyint(1) NOT NULL DEFAULT '0',
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `fieldstate` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `condition_field` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `condition_operator` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `condition_value` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `position` int(10) unsigned NOT NULL,
  `field_lock` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `lara_ent_entity_custom_columns_entity_id_foreign` (`entity_id`),
  CONSTRAINT `lara_ent_entity_custom_columns_entity_id_foreign` FOREIGN KEY (`entity_id`) REFERENCES `lara_ent_entities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `lara_ent_entity_relations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entity_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `related_entity_id` bigint(20) unsigned NOT NULL,
  `foreign_key` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_filter` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `lara_ent_entity_relations_entity_id_foreign` (`entity_id`),
  KEY `lara_ent_entity_relations_related_entity_id_foreign` (`related_entity_id`),
  CONSTRAINT `lara_ent_entity_relations_entity_id_foreign` FOREIGN KEY (`entity_id`) REFERENCES `lara_ent_entities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lara_ent_entity_relations_related_entity_id_foreign` FOREIGN KEY (`related_entity_id`) REFERENCES `lara_ent_entities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `lara_ent_entity_views` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entity_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `method` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `filename` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `showtags` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `paginate` int(11) NOT NULL DEFAULT '0',
  `infinite` tinyint(1) NOT NULL DEFAULT '0',
  `prevnext` tinyint(1) NOT NULL DEFAULT '0',
  `publish` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lara_ent_entity_views_entity_id_foreign` (`entity_id`),
  CONSTRAINT `lara_ent_entity_views_entity_id_foreign` FOREIGN KEY (`entity_id`) REFERENCES `lara_ent_entities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `lara_sys_languages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` text COLLATE utf8_unicode_ci,
  `default` tinyint(1) NOT NULL DEFAULT '0',
  `backend` tinyint(1) NOT NULL DEFAULT '0',
  `backend_default` tinyint(1) NOT NULL DEFAULT '0',
  `publish` tinyint(1) NOT NULL DEFAULT '0',
  `position` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lara_sys_languages_position_index` (`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Fixture data: three site content types that cover the hard cases of the entity import.
-- v8fxbook  (entity): geolocation parts, a duplicate field, a condition, a gallery, hasMany v8fxevent
-- v8fxevent (entity): belongsTo v8fxbook (filter on), single image
-- v8fxform  (form):   form field types incl. yesno
-- The names are unusual on purpose, so they never collide with a site's own content types.

INSERT INTO `lara_ent_entity_groups` (`id`, `title`, `key`, `position`) VALUES
(1, 'Page', 'page', 1), (2, 'Blocks', 'block', 2), (3, 'Menu', 'menu', 3), (8, 'Form', 'form', 8), (9, 'Entity', 'entity', 9);

INSERT INTO `lara_sys_languages` (`id`, `code`, `name`, `default`, `publish`, `position`) VALUES
(1, 'nl', 'Nederlands', 1, 1, 1), (2, 'en', 'English', 0, 1, 2);

INSERT INTO `lara_ent_entities` (`id`, `group_id`, `title`, `entity_model_class`, `entity_key`, `entity_controller`, `resource_routes`, `has_front_auth`, `menu_parent`, `menu_position`) VALUES
(101, 9, 'Fixture Books', 'Eve\\Models\\V8fxbook', 'v8fxbook', 'V8fxbooksController', 1, 0, 'modules', 901),
(102, 9, 'Fixture Events', 'Eve\\Models\\V8fxevent', 'v8fxevent', 'V8fxeventsController', 1, 0, 'modules', 902),
(103, 8, 'Fixture Form', 'Eve\\Models\\V8fxform', 'v8fxform', 'V8fxformsController', 1, 0, 'forms', 903),
(104, 3, 'Fixture Menu', 'Lara\\Common\\Models\\Menu', 'v8fxmenu', 'MenusController', 1, 0, 'menu', 904);

INSERT INTO `lara_ent_entity_columns` (`entity_id`, `has_user`, `has_lang`, `has_slug`, `has_lead`, `has_body`, `has_status`, `has_hideinlist`, `has_expiration`, `has_app`, `has_groups`, `group_values`, `group_default`, `is_sortable`, `sort_field`, `sort_order`, `sort2_field`, `sort2_order`, `has_fields`) VALUES
(101, 1, 1, 1, 1, 1, 1, 0, 1, 0, 0, NULL, NULL, 0, 'created_at', 'desc', 'NULL', NULL, 1),
(102, 1, 1, 1, 0, 1, 1, 0, 0, 0, 0, NULL, NULL, 1, 'position', 'asc', NULL, NULL, 1),
(103, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, 0, 'created_at', 'desc', NULL, NULL, 1);

INSERT INTO `lara_ent_entity_panels` (`entity_id`, `has_search`, `has_batch`, `has_filters`, `show_author`, `show_status`, `has_tiny_lead`, `has_tiny_body`) VALUES
(101, 1, 1, 1, 1, 1, 0, 1),
(102, 1, 0, 0, 0, 1, 0, 1),
(103, 0, 0, 0, 0, 0, 0, 0);

INSERT INTO `lara_ent_entity_object_relations` (`entity_id`, `has_seo`, `has_opengraph`, `has_layout`, `has_related`, `is_relatable`, `has_tags`, `tag_default`, `has_sync`, `has_images`, `has_videos`, `has_videofiles`, `has_files`, `max_images`, `max_videos`, `max_videofiles`, `max_files`, `disk_images`, `disk_videos`, `disk_files`) VALUES
(101, 1, 1, 0, 0, 0, 1, NULL, 0, 1, 0, 0, 1, 12, 1, 1, 3, 'localdisk', 'localdisk', 'localdisk'),
(102, 0, 0, 0, 0, 0, 0, '', 0, 1, 0, 0, 0, 1, 1, 1, 1, 'localdisk', 'localdisk', 'localdisk'),
(103, 0, 0, 0, 0, 0, 0, '', 0, 0, 0, 0, 0, 1, 1, 1, 1, 'localdisk', 'localdisk', 'localdisk');

INSERT INTO `lara_ent_entity_custom_columns` (`entity_id`, `fieldtitle`, `fieldname`, `fieldtype`, `fieldhook`, `fielddata`, `primary`, `required`, `fieldstate`, `condition_field`, `condition_operator`, `condition_value`, `position`) VALUES
(101, 'Subtitle', 'subtitle', 'string', 'before', '', 1, 0, 'enabled', 'NULL', 'isequal', '', 1),
(101, 'Sticky', 'sticky', 'boolean', 'between', '', 0, 0, 'enabled', NULL, NULL, NULL, 2),
(101, 'Kind', 'kind', 'selectone', 'after', '{"0":"novel","1":"poetry"}', 0, 1, 'enabled', NULL, NULL, NULL, 3),
(101, 'Note', 'note', 'text', 'after', '', 0, 0, 'enabledif', 'kind', 'isequal', 'poetry', 4),
(101, 'Address', 'address', 'string', 'after', '', 0, 0, 'enabled', NULL, NULL, NULL, 5),
(101, 'City', 'city', 'string', 'after', '', 0, 0, 'enabled', NULL, NULL, NULL, 6),
(101, 'Geolocation', 'geolocation', 'geolocation', 'after', '', 0, 0, 'enabled', NULL, NULL, NULL, 7),
(101, 'Latitude', 'latitude', 'decimal108', 'after', '', 0, 0, 'enabledif', 'geolocation', 'isequal', '1', 8),
(101, 'Latitude', 'latitude', 'decimal108', 'after', '', 0, 0, 'enabledif', 'geolocation', 'isequal', '1', 9),
(101, 'Pages', 'pages', 'intunsigned', 'after', '', 0, 0, 'enabled', NULL, NULL, NULL, 10),
(101, 'Pages', 'pages', 'intunsigned', 'after', '', 0, 0, 'enabled', NULL, NULL, NULL, 11),
(102, 'Start date', 'startdate', 'date', 'between', '', 1, 1, 'enabled', NULL, NULL, NULL, 1),
(103, 'Name', 'name', 'string', 'default', '', 1, 1, 'enabled', NULL, NULL, NULL, 1),
(103, 'Email', 'email', 'email', 'default', '', 1, 1, 'enabled', NULL, NULL, NULL, 2),
(103, 'Comment', 'comment', 'text', 'default', '', 0, 0, 'enabled', NULL, NULL, NULL, 3),
(103, 'Agree', 'agree', 'yesno', 'default', '', 0, 0, 'enabled', NULL, NULL, NULL, 4);

INSERT INTO `lara_ent_entity_relations` (`entity_id`, `type`, `related_entity_id`, `foreign_key`, `is_filter`) VALUES
(101, 'hasMany', 102, 'v8fxbook_id', 0),
(102, 'belongsTo', 101, 'v8fxbook_id', 1);

INSERT INTO `lara_ent_entity_views` (`entity_id`, `title`, `method`, `filename`, `type`, `showtags`, `paginate`, `infinite`, `prevnext`, `publish`) VALUES
(101, 'Index', 'index', 'index', 'grid3', '_sortbytaxonomy', 12, 0, 0, 1),
(101, 'Show', 'show', 'show', '_single', 'none', 0, 0, 1, 1),
(102, 'Index', 'index', 'index', 'list', 'none', 0, 0, 0, 1),
(102, 'Show', 'show', 'show', '_single', 'none', 0, 0, 0, 1),
(103, 'Form', 'form', 'form', '_form', 'none', 0, 0, 0, 1);

CREATE TABLE `lara_auth_users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `firstname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `middlename` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lastname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `username` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_language` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_loggedin` tinyint(1) NOT NULL DEFAULT '0',
  `last_login` timestamp NULL DEFAULT NULL,
  `api_token` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `locked_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lara_auth_users_email_unique` (`email`),
  UNIQUE KEY `lara_auth_users_username_unique` (`username`),
  KEY `lara_auth_users_locked_by_foreign` (`locked_by`),
  CONSTRAINT `lara_auth_users_locked_by_foreign` FOREIGN KEY (`locked_by`) REFERENCES `lara_auth_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `lara_auth_users_profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `dark_mode` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lara_auth_users_profiles_user_id_foreign` (`user_id`),
  CONSTRAINT `lara_auth_users_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `lara_auth_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `lara_auth_roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `level` int(10) unsigned DEFAULT NULL,
  `scope` int(11) DEFAULT NULL,
  `has_backend_access` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `locked_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lara_auth_roles_name_unique` (`name`),
  KEY `lara_auth_roles_locked_by_foreign` (`locked_by`),
  KEY `lara_auth_roles_scope_index` (`scope`),
  CONSTRAINT `lara_auth_roles_locked_by_foreign` FOREIGN KEY (`locked_by`) REFERENCES `lara_auth_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `lara_auth_abilities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entity_id` bigint(20) unsigned DEFAULT NULL,
  `entity_type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entity_key` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `only_owned` tinyint(1) NOT NULL DEFAULT '0',
  `options` longtext COLLATE utf8_unicode_ci,
  `scope` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `locked_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `abilities_unique_index` (`name`,`entity_id`,`entity_type`,`only_owned`),
  KEY `lara_auth_abilities_locked_by_foreign` (`locked_by`),
  KEY `lara_auth_abilities_scope_index` (`scope`),
  CONSTRAINT `lara_auth_abilities_locked_by_foreign` FOREIGN KEY (`locked_by`) REFERENCES `lara_auth_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `lara_auth_has_abilities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ability_id` bigint(20) unsigned NOT NULL,
  `entity_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `entity_id` bigint(20) unsigned NOT NULL,
  `forbidden` tinyint(1) NOT NULL DEFAULT '0',
  `scope` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lara_auth_has_abilities_entity_type_entity_id_index` (`entity_type`,`entity_id`),
  KEY `lara_auth_has_abilities_ability_id_index` (`ability_id`),
  KEY `lara_auth_has_abilities_scope_index` (`scope`),
  CONSTRAINT `lara_auth_has_abilities_ability_id_foreign` FOREIGN KEY (`ability_id`) REFERENCES `lara_auth_abilities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `lara_auth_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `entity_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `entity_id` bigint(20) unsigned NOT NULL,
  `restricted_to_id` bigint(20) unsigned DEFAULT NULL,
  `restricted_to_type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `scope` int(11) DEFAULT NULL,
  KEY `lara_auth_has_roles_entity_type_entity_id_index` (`entity_type`,`entity_id`),
  KEY `lara_auth_has_roles_role_id_index` (`role_id`),
  KEY `lara_auth_has_roles_scope_index` (`scope`),
  CONSTRAINT `lara_auth_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `lara_auth_roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Users, roles and abilities (lara:v8:import-users). Password of every web user: "fixture".
-- id 3 collides with the superadmin of the essential seed on purpose: the v8 user replaces it.
-- Role editor has no users (skipped); role clerk does not exist in v10 (created);
-- user 99 no longer exists (orphan assignment); headertag has no v10 policy.

INSERT INTO `lara_auth_users` (`id`, `type`, `is_admin`, `name`, `firstname`, `middlename`, `lastname`, `username`, `email`, `password`, `user_language`, `api_token`, `created_at`, `updated_at`) VALUES
(1, 'api', 0, 'API', '', NULL, '', NULL, '', '', NULL, 'fixture-api-token', NULL, NULL),
(2, 'web', 1, 'Fixture Beheer', 'Fixture', NULL, 'Beheer', 'admin', 'admin@fixture.test', '$2y$10$0gsJGtpSCdYXR/oc9XoLvukGkXnU6pt323pLJA4V1uyCWtl45et1G', 'nl', NULL, '2021-01-01 11:00:00', '2024-01-01 10:00:00'),
(3, 'web', 0, 'Web Master', 'Web', 'de', 'Master', 'webmaster', 'webmaster@fixture.test', '$2y$10$0gsJGtpSCdYXR/oc9XoLvukGkXnU6pt323pLJA4V1uyCWtl45et1G', NULL, NULL, '2022-01-01 11:00:00', '2024-01-01 10:00:00'),
(4, 'web', 0, 'Mem Ber', 'Mem', NULL, 'Ber', 'member', 'member@fixture.test', '$2y$10$0gsJGtpSCdYXR/oc9XoLvukGkXnU6pt323pLJA4V1uyCWtl45et1G', 'en', NULL, '2022-01-01 11:00:00', '2024-01-01 10:00:00'),
(5, 'web', 0, 'Cler K', 'Cler', NULL, 'K', 'clerk', 'clerk@fixture.test', '$2y$10$0gsJGtpSCdYXR/oc9XoLvukGkXnU6pt323pLJA4V1uyCWtl45et1G', 'nl', NULL, '2022-01-01 11:00:00', '2024-01-01 10:00:00');

INSERT INTO `lara_auth_users_profiles` (`id`, `user_id`, `dark_mode`) VALUES (1, 3, 1);

INSERT INTO `lara_auth_roles` (`id`, `name`, `title`, `level`, `has_backend_access`) VALUES
(1, 'administrator', 'Administrator', 100, 1),
(2, 'webmaster', 'Webmaster', 90, 1),
(3, 'editor', 'Editor', 50, 1),
(4, 'member', 'Member', 10, 0),
(5, 'clerk', 'Clerk', 40, 1);

INSERT INTO `lara_auth_abilities` (`id`, `name`, `title`, `entity_key`) VALUES
(1, 'view', 'View', 'v8fxbook'), (2, 'create', 'Create', 'v8fxbook'), (3, 'update', 'Update', 'v8fxbook'), (4, 'delete', 'Delete', 'v8fxbook'),
(5, 'view', 'View', 'page'), (6, 'view', 'View', 'headertag'), (7, 'view', 'View', 'setting'), (8, 'update', 'Update', 'setting');

INSERT INTO `lara_auth_has_abilities` (`ability_id`, `entity_type`, `entity_id`, `forbidden`) VALUES
(1, 'lara_auth_roles', 1, 0), (2, 'lara_auth_roles', 1, 0), (3, 'lara_auth_roles', 1, 0), (4, 'lara_auth_roles', 1, 0),
(5, 'lara_auth_roles', 1, 0), (6, 'lara_auth_roles', 1, 0), (7, 'lara_auth_roles', 1, 0), (8, 'lara_auth_roles', 1, 0),
(1, 'lara_auth_roles', 2, 0), (3, 'lara_auth_roles', 2, 0), (5, 'lara_auth_roles', 2, 0),
(1, 'lara_auth_roles', 5, 0);

INSERT INTO `lara_auth_has_roles` (`role_id`, `entity_type`, `entity_id`) VALUES
(1, 'Lara\\Common\\Models\\User', 2),
(2, 'Lara\\Common\\Models\\User', 3),
(4, 'Lara\\Common\\Models\\User', 4),
(5, 'Lara\\Common\\Models\\User', 5),
(4, 'Lara\\Common\\Models\\User', 99);

CREATE TABLE `lara_content_pages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `language_parent` bigint(20) unsigned DEFAULT NULL,
  `language` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `template` text COLLATE utf8_unicode_ci,
  `ishome` tinyint(1) NOT NULL DEFAULT '0',
  `menuroute` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `slug_lock` tinyint(1) NOT NULL DEFAULT '0',
  `lead` text COLLATE utf8_unicode_ci,
  `body` text COLLATE utf8_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `publish` tinyint(1) NOT NULL DEFAULT '0',
  `publish_from` timestamp NULL DEFAULT NULL,
  `show_in_app` tinyint(1) NOT NULL DEFAULT '0',
  `locked_at` timestamp NULL DEFAULT NULL,
  `locked_by` bigint(20) unsigned DEFAULT NULL,
  `cgroup` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `position` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lara_content_pages_locked_by_foreign` (`locked_by`),
  KEY `lara_content_pages_user_id_foreign` (`user_id`),
  KEY `lara_content_pages_slug_index` (`slug`),
  KEY `lara_content_pages_position_index` (`position`),
  CONSTRAINT `lara_content_pages_locked_by_foreign` FOREIGN KEY (`locked_by`) REFERENCES `lara_auth_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lara_content_pages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `lara_auth_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `lara_blocks_ctas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `language_parent` bigint(20) unsigned DEFAULT NULL,
  `language` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `overlaytransp` text COLLATE utf8_unicode_ci,
  `overlaycolor` text COLLATE utf8_unicode_ci,
  `type` text COLLATE utf8_unicode_ci,
  `hook` text COLLATE utf8_unicode_ci,
  `linkurl` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `linktext` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `slug_lock` tinyint(1) NOT NULL DEFAULT '0',
  `body` text COLLATE utf8_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `publish` tinyint(1) NOT NULL DEFAULT '0',
  `publish_from` timestamp NULL DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `locked_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lara_blocks_ctas_locked_by_foreign` (`locked_by`),
  KEY `lara_blocks_ctas_user_id_foreign` (`user_id`),
  KEY `lara_blocks_ctas_slug_index` (`slug`),
  CONSTRAINT `lara_blocks_ctas_locked_by_foreign` FOREIGN KEY (`locked_by`) REFERENCES `lara_auth_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lara_blocks_ctas_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `lara_auth_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Content (lara:v8:import-content).
-- Core content types page and cta: they keep their v10 configuration; only their data is imported.
-- Page 19 collides with a page of the essential seed (updated); essential page 5 is removed by --fresh.
-- Page 1125 has a lead (→ body2, warning: template standard shows no extra body fields).
-- CTA 99 is live with the Bootstrap colour primary (→ brand); CTA 98 is unpublished.

INSERT INTO `lara_ent_entities` (`id`, `group_id`, `title`, `entity_model_class`, `entity_key`, `entity_controller`, `resource_routes`, `has_front_auth`, `menu_parent`, `menu_position`) VALUES
(1, 1, 'Pages', 'Lara\\Common\\Models\\Page', 'page', 'PagesController', 1, 0, 'root', 103),
(31, 2, 'Call-to-action', 'Lara\\Common\\Models\\Cta', 'cta', 'CtasController', 1, 0, 'blocks', 330);


CREATE TABLE `lara_content_v8fxbooks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `language_parent` bigint(20) unsigned DEFAULT NULL,
  `language` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `sticky` tinyint(1) NOT NULL DEFAULT '0',
  `kind` text,
  `note` text,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `geolocation` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `pages` int(10) unsigned DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `slug_lock` tinyint(1) NOT NULL DEFAULT '0',
  `lead` text,
  `body` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `publish` tinyint(1) NOT NULL DEFAULT '0',
  `publish_hide` tinyint(1) NOT NULL DEFAULT '0',
  `publish_from` timestamp NULL DEFAULT NULL,
  `publish_to` timestamp NULL DEFAULT NULL,
  `position` int(10) unsigned DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `locked_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `lara_content_v8fxevents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `v8fxbook_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `language_parent` bigint(20) unsigned DEFAULT NULL,
  `language` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `startdate` date DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `slug_lock` tinyint(1) NOT NULL DEFAULT '0',
  `body` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `publish` tinyint(1) NOT NULL DEFAULT '0',
  `publish_from` timestamp NULL DEFAULT NULL,
  `position` int(10) unsigned DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `locked_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `lara_form_v8fxforms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `comment` text,
  `agree` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `locked_by` bigint(20) unsigned DEFAULT NULL,
  `ipaddress` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Book 1: geolocation parts, an empty select value, a NULL position, a record lock.
-- Book 2: an English translation of book 1 by a user that no longer exists, in the trash.
INSERT INTO `lara_content_v8fxbooks` (`id`, `user_id`, `language_parent`, `language`, `title`, `subtitle`, `sticky`, `kind`, `note`, `address`, `city`, `geolocation`, `latitude`, `pages`, `slug`, `lead`, `body`, `created_at`, `updated_at`, `deleted_at`, `publish`, `publish_from`, `position`, `locked_at`, `locked_by`) VALUES
(1, 2, NULL, 'nl', 'Boek één', 'Ondertitel', 1, '', NULL, 'Grote Markt 1', 'Haarlem', 'manual', 52.38108000, 120, 'boek-een', '<p>Intro</p>', '<p>Tekst</p>', '2024-01-01 10:00:00', '2024-02-01 10:00:00', NULL, 1, '2024-01-01 10:00:00', NULL, '2024-02-01 10:00:00', 2),
(2, 77, 1, 'en', 'Book one', NULL, 0, 'poetry', 'A note', NULL, NULL, NULL, NULL, NULL, 'book-one', NULL, NULL, '2024-01-02 10:00:00', '2024-02-02 10:00:00', '2024-03-01 10:00:00', 0, NULL, 3, NULL, NULL);

-- Event 1 points to a book that does not exist (reported after the import).
INSERT INTO `lara_content_v8fxevents` (`id`, `v8fxbook_id`, `user_id`, `language`, `title`, `startdate`, `slug`, `created_at`, `updated_at`, `publish`, `position`) VALUES
(1, 999, 2, 'nl', 'Lezing', '2024-05-01', 'lezing', '2024-04-01 10:00:00', '2024-04-01 10:00:00', 1, 0);

-- The v8 form title is a copy of the name; v10 forms have no title.
INSERT INTO `lara_form_v8fxforms` (`id`, `title`, `name`, `email`, `comment`, `agree`, `created_at`, `updated_at`, `ipaddress`) VALUES
(1, 'Jan Jansen', 'Jan Jansen', 'jan@fixture.test', '', 1, '2024-06-01 10:00:00', '2024-06-01 10:00:00', '192.0.2.1');

INSERT INTO `lara_content_pages` (`id`, `user_id`, `language_parent`, `language`, `title`, `template`, `ishome`, `menuroute`, `slug`, `slug_lock`, `lead`, `body`, `created_at`, `updated_at`, `publish`, `publish_from`, `show_in_app`, `cgroup`, `position`) VALUES
(19, 2, NULL, 'nl', 'Home', 'standard', 1, '/', 'home', 0, NULL, '<p>Welkom</p>', '2023-01-01 10:00:00', '2024-01-01 10:00:00', 1, '2023-01-01 10:00:00', 0, 'page', 1),
(1125, 2, NULL, 'nl', 'Arbeidshygiëne', 'standard', 0, NULL, 'arbeidshygiene', 0, '<p>Intro</p>', '<p>Tekst</p>', '2023-01-01 10:00:00', '2024-01-01 10:00:00', 1, '2023-01-01 10:00:00', 0, 'page', NULL);

INSERT INTO `lara_blocks_ctas` (`id`, `user_id`, `language`, `title`, `overlaytransp`, `overlaycolor`, `type`, `hook`, `linkurl`, `linktext`, `slug`, `body`, `created_at`, `updated_at`, `deleted_at`, `publish`) VALUES
(99, 2, 'nl', 'Neem contact op', '80', 'primary', 'parallax', 'homeparallax1', '/contact', 'Contact', 'neem-contact-op', '<p>Bel ons</p>', '2023-01-01 10:00:00', '2024-01-01 10:00:00', NULL, 1),
(98, 2, 'en', 'Old demo', '', 'black', 'parallax', 'homeparalax2', '', '', 'old-demo', NULL, '2023-01-01 10:00:00', '2024-01-01 10:00:00', NULL, 0);

CREATE TABLE `lara_object_taxonomies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `slug_lock` tinyint(1) NOT NULL DEFAULT '0',
  `has_hierarchy` tinyint(1) NOT NULL DEFAULT '0',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `locked_by` bigint(20) unsigned DEFAULT NULL,
  `position` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lara_object_taxonomies_locked_by_foreign` (`locked_by`),
  KEY `lara_object_taxonomies_position_index` (`position`),
  CONSTRAINT `lara_object_taxonomies_locked_by_foreign` FOREIGN KEY (`locked_by`) REFERENCES `lara_auth_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `lara_object_tags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `language` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `language_parent` bigint(20) unsigned DEFAULT NULL,
  `taxonomy_id` bigint(20) unsigned NOT NULL,
  `entity_key` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `slug_lock` tinyint(1) NOT NULL DEFAULT '0',
  `lead` text COLLATE utf8_unicode_ci,
  `body` text COLLATE utf8_unicode_ci,
  `route` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `locked_by_admin` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `publish` tinyint(1) NOT NULL DEFAULT '0',
  `publish_from` timestamp NULL DEFAULT NULL,
  `publish_to` timestamp NULL DEFAULT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `lft` bigint(20) unsigned DEFAULT NULL,
  `rgt` bigint(20) unsigned DEFAULT NULL,
  `depth` bigint(20) unsigned DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `locked_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lara_object_tags_taxonomy_id_foreign` (`taxonomy_id`),
  KEY `lara_object_tags_locked_by_foreign` (`locked_by`),
  KEY `lara_object_tags_parent_id_index` (`parent_id`),
  KEY `lara_object_tags_lft_index` (`lft`),
  KEY `lara_object_tags_rgt_index` (`rgt`),
  KEY `lara_object_tags_depth_index` (`depth`),
  CONSTRAINT `lara_object_tags_locked_by_foreign` FOREIGN KEY (`locked_by`) REFERENCES `lara_auth_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lara_object_tags_taxonomy_id_foreign` FOREIGN KEY (`taxonomy_id`) REFERENCES `lara_object_taxonomies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `lara_object_taggables` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tag_id` bigint(20) unsigned NOT NULL,
  `entity_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `entity_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `lara_object_taggables_entity_type_entity_id_index` (`entity_type`,`entity_id`),
  KEY `lara_object_taggables_tag_id_index` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `lara_object_pageables` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` bigint(20) unsigned NOT NULL,
  `entity_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `entity_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `lara_object_pageables_entity_type_entity_id_index` (`entity_type`,`entity_id`),
  KEY `lara_object_pageables_page_id_index` (`page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `lara_object_seo` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entity_id` bigint(20) unsigned NOT NULL,
  `seo_focus` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `seo_title` text COLLATE utf8_unicode_ci,
  `seo_description` text COLLATE utf8_unicode_ci,
  `seo_keywords` text COLLATE utf8_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `lara_object_opengraph` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entity_id` bigint(20) unsigned NOT NULL,
  `og_title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `og_description` text COLLATE utf8_unicode_ci,
  `og_image` text COLLATE utf8_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `lara_object_layout` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entity_id` bigint(20) unsigned NOT NULL,
  `layout_key` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `layout_value` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `lara_object_related` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entity_key` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `object_id` bigint(20) unsigned NOT NULL,
  `related_entity_key` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `related_model_class` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `related_object_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `lara_object_sync` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entity_id` bigint(20) unsigned NOT NULL,
  `remote_url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `remote_suffix` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ent_key` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Objects (lara:v8:import-objects).
-- Tag 11 is a root with depth NULL (→ the column default); tag 12 has a publish_from date (reported).
-- Taggable 3 points to a book that does not exist (skipped). Pageable 1 links CTA 99 to page 19.
-- SEO: book 1 has SEO (keywords → JSON list) and opengraph; page 1125 only an og title (reported).
-- Layout: page 19 with a value the theme does not offer (reported). One related row (reported).

INSERT INTO `lara_object_taxonomies` (`id`, `title`, `slug`, `has_hierarchy`, `is_default`, `position`) VALUES
(1, 'Category', 'category', 1, 1, 1), (2, 'Tag', 'tag', 0, 0, 2);

INSERT INTO `lara_object_tags` (`id`, `language`, `taxonomy_id`, `entity_key`, `title`, `slug`, `publish`, `publish_from`, `parent_id`, `lft`, `rgt`, `depth`) VALUES
(11, 'nl', 1, 'v8fxbook', 'root', 'root-v8fxbook', 1, NULL, NULL, 1, 4, NULL),
(12, 'nl', 1, 'v8fxbook', 'Romans', 'romans', 1, '2024-01-01 10:00:00', 11, 2, 3, 1),
(13, 'nl', 1, 'larawidget', 'Home', 'home', 1, NULL, NULL, 1, 2, 0);

INSERT INTO `lara_object_taggables` (`id`, `tag_id`, `entity_type`, `entity_id`) VALUES
(1, 12, 'Eve\\Models\\V8fxbook', 1),
(2, 12, 'Eve\\Models\\V8fxbook', 2),
(3, 12, 'Eve\\Models\\V8fxbook', 999);

INSERT INTO `lara_object_pageables` (`id`, `page_id`, `entity_type`, `entity_id`) VALUES
(1, 19, 'Lara\\Common\\Models\\Cta', 99);

INSERT INTO `lara_object_seo` (`id`, `entity_type`, `entity_id`, `seo_focus`, `seo_title`, `seo_description`, `seo_keywords`, `created_at`, `updated_at`) VALUES
(1, 'Eve\\Models\\V8fxbook', 1, 'boek', 'Boek één | Fixture', 'Over het boek', 'boeken, lezen , ,romans', '2024-01-01 10:00:00', '2024-01-01 10:00:00'),
(2, 'Eve\\Models\\V8fxbook', 2, '', '', '', '', NULL, NULL);

INSERT INTO `lara_object_opengraph` (`id`, `entity_type`, `entity_id`, `og_title`, `og_description`, `og_image`) VALUES
(1, 'Eve\\Models\\V8fxbook', 1, '', '', 'og-boek.jpg'),
(2, 'Lara\\Common\\Models\\Page', 1125, 'Share me', '', '');

INSERT INTO `lara_object_layout` (`id`, `entity_type`, `entity_id`, `layout_key`, `layout_value`) VALUES
(1, 'Lara\\Common\\Models\\Page', 19, 'hero', 'hero_slider_70'),
(2, 'Lara\\Common\\Models\\Page', 19, 'pagetitle', 'hidden'),
(3, 'Lara\\Common\\Models\\Page', 19, 'content', 'no_such_layout');

INSERT INTO `lara_object_related` (`id`, `entity_key`, `object_id`, `related_entity_key`, `related_model_class`, `related_object_id`) VALUES
(1, 'v8fxbook', 1, 'v8fxevent', 'Eve\\Models\\V8fxevent', 1);

SET FOREIGN_KEY_CHECKS=1;
