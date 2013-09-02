CREATE TABLE IF NOT EXISTS `logging` (
  `source_ip` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `remote_url` varchar(360) COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;