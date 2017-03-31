
-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- akeruption implementation : © <Your name here> <Your email address here>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- dbmodel.sql

-- This is the file where you are describing the database schema of your game
-- Basically, you just have to export from PhpMyAdmin your table structure and copy/paste
-- this export here.
-- Note that the database itself and the standard tables ("global", "stats", "gamelog" and "player") are
-- already created and must not be created here

-- Note: The database schema is created from this file when the game starts. If you modify this file,
--       you have to restart a game to see your changes in database.

-- Example 1: create a standard "card" table to be used with the "Deck" tools (see example game "hearts"):

-- CREATE TABLE IF NOT EXISTS `card` (
--   `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
--   `card_type` varchar(16) NOT NULL,
--   `card_type_arg` int(11) NOT NULL,
--   `card_location` varchar(16) NOT NULL,
--   `card_location_arg` int(11) NOT NULL,
--   PRIMARY KEY (`card_id`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `tiles` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(16) NOT NULL,
  `card_type_arg` int(11) NOT NULL,
  `card_location` varchar(16) NOT NULL,
  `card_location_arg` int(11) NOT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `cards` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(16) NOT NULL,
  `card_type_arg` int(11) NOT NULL,
  `card_location` varchar(16) NOT NULL,
  `card_location_arg` int(11) NOT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `walls` (
  `wall_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `wall_type` varchar(5) NOT NULL,
  `wall_x` int(10) NOT NULL,
  `wall_y` int(10) NOT NULL,
  `wall_rotation` int(10) NOT NULL,
  PRIMARY KEY (`wall_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `failedPlacements` (
  `placement_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `placement_x` int(10) NOT NULL,
  `placement_y` int(10) NOT NULL,
  PRIMARY KEY (`placement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- Example 2: add a custom field to the standard "player" table
-- ALTER TABLE `player` ADD `player_my_custom_field` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `player_position` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `player_temp` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `extra_tile_placed` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `extra_wall_placed` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `straw` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `wood` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `stone` INT UNSIGNED NOT NULL DEFAULT '0';
