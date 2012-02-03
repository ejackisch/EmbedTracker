CREATE TABLE `wikiEmbedTrackerStats` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `article_title` varchar(255) NOT NULL,
  `referer` varchar(255) NOT NULL,
  `first_accessed` bigint(20) NOT NULL,
  `last_accessed` bigint(20) DEFAULT NULL,
  `hits` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `article_title` (`article_title`),
  KEY `referer` (`referer`)
);