-- Přejmenování modulu webu skbl_core → club_core: identifikátory videí z webu
-- mají místo "skbl:node:123" nově tvar "club:node:123".
UPDATE `matches`
   SET `external_ref` = CONCAT('club:', SUBSTRING(`external_ref`, 6))
 WHERE `external_ref` LIKE 'skbl:%';

ALTER TABLE `matches`
  MODIFY COLUMN `external_ref` VARCHAR(64) NULL COMMENT 'video na klubovém webu, např. club:node:123';
