<?php

require dirname(__DIR__) . '/bootstrap.php';

$connection = \Cake\Datasource\ConnectionManager::get('default');

$tables = $connection->getSchemaCollection()->listTables();
if (!in_array('judging_assignments', $tables, true)) {
    $connection->execute(<<<'SQL'
CREATE TABLE `judging_assignments` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `conventionseason_id` bigint NOT NULL,
  `event_id` bigint NOT NULL,
  `judge1_user_id` bigint DEFAULT NULL,
  `judge2_user_id` bigint DEFAULT NULL,
  `judge3_user_id` bigint DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_season_event` (`conventionseason_id`, `event_id`),
  KEY `event_id` (`event_id`),
  KEY `judge1_user_id` (`judge1_user_id`),
  KEY `judge2_user_id` (`judge2_user_id`),
  KEY `judge3_user_id` (`judge3_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
}

echo "Migration completed successfully.\n";