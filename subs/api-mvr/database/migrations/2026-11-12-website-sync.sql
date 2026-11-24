-- Synchronizace videí z klubového webu (modul webu skbl_core, dnes club_core).
-- external_ref = stálý identifikátor videa na webu, např. "skbl:node:123".
ALTER TABLE `matches`
  ADD COLUMN `external_ref` VARCHAR(64) NULL COMMENT 'video na klubovém webu, např. skbl:node:123' AFTER `is_public`,
  ADD COLUMN `external_url` TEXT NULL COMMENT 'odkaz na video na webu' AFTER `external_ref`,
  ADD COLUMN `external_synced_at` DATETIME NULL AFTER `external_url`,
  ADD UNIQUE KEY `uq_matches_external_ref` (`external_ref`);
