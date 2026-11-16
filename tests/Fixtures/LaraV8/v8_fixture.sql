-- Lara CMS v8.2.46 fixture for the lara:v8:* tests.
-- Schema: the v8 entity configuration tables, from a v8.2.46 dump (only the users foreign key removed).

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

SET FOREIGN_KEY_CHECKS=1;
