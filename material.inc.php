<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * akeruption implementation : © <Your name here> <Your email address here>
 * 
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * material.inc.php
 *
 * akeruption game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *   
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */

//trasnslatable text will go here for convenience
$this->resources = array(
    "straw" => clienttranslate('Straw'),
    "wood" => clienttranslate('Wood'),
    "stone" => clienttranslate('Stone')
);

//direction constants
$this->dirN = 0;
$this->dirNE = 1;
$this->dirSE = 2;
$this->dirS = 3;
$this->dirSW = 4;
$this->dirNW = 5;

$this->burnLevel1 = 50;
$this->burnLevel2 = 120;
$this->burnLevel3 = 200;
$this->maxBurnTemp = 290;

//tile states
$this->tileStateEmpty = "EMPTY";
$this->tileStateGrass = "GRASS";
$this->tileStateLava = "LAVA";

 //resource types
 //MUST MATCH JAVASCRIPT
 $this->resourceTypeStraw = "straw";
 $this->resourceTypeWood = "wood";
 $this->resourceTypeStone = "stone";

 //co-ordinates of the initial empty spaces on the board
 $this->board_spaces = array(
 array('x' => 0, 'y' => 7), array('x' => 0, 'y' => 8),
 array('x' => 1, 'y' => 6), array('x' => 1, 'y' => 7), array('x' => 1, 'y' => 8),
 array('x' => 2, 'y' => 3), array('x' => 2, 'y' => 4), array('x' => 2, 'y' => 5), array('x' => 2, 'y' => 6), 
 array('x' => 2, 'y' => 7), array('x' => 2, 'y' => 8), array('x' => 2, 'y' => 9), array('x' => 2, 'y' => 10),
 array('x' => 3, 'y' => 2), array('x' => 3, 'y' => 3), array('x' => 3, 'y' => 4), array('x' => 3, 'y' => 5), array('x' => 3, 'y' => 6),
 array('x' => 3, 'y' => 7), array('x' => 3, 'y' => 8), array('x' => 3, 'y' => 9), array('x' => 3, 'y' => 10),
 array('x' => 4, 'y' => 2), array('x' => 4, 'y' => 3), array('x' => 4, 'y' => 4),
 array('x' => 4, 'y' => 7), array('x' => 4, 'y' => 8), array('x' => 4, 'y' => 9),
 array('x' => 5, 'y' => 2), array('x' => 5, 'y' => 3), array('x' => 5, 'y' => 7), array('x' => 5, 'y' => 8),
 array('x' => 6, 'y' => 1), array('x' => 6, 'y' => 2), array('x' => 6, 'y' => 3),
 array('x' => 6, 'y' => 6), array('x' => 6, 'y' => 7), array('x' => 6, 'y' => 8),
 array('x' => 7, 'y' => 0), array('x' => 7, 'y' => 1), array('x' => 7, 'y' => 2), array('x' => 7, 'y' => 3),
 array('x' => 7, 'y' => 4), array('x' => 7, 'y' => 5), array('x' => 7, 'y' => 6), array('x' => 7, 'y' => 7), array('x' => 7, 'y' => 8),
 array('x' => 8, 'y' => 0), array('x' => 8, 'y' => 1), array('x' => 8, 'y' => 2), array('x' => 8, 'y' => 3),
 array('x' => 8, 'y' => 4), array('x' => 8, 'y' => 5), array('x' => 8, 'y' => 6), array('x' => 8, 'y' => 7),
 array('x' => 9, 'y' => 2), array('x' => 9, 'y' => 3), array('x' => 9, 'y' => 4),
 array('x' => 10, 'y' => 2), array('x' => 10, 'y' => 3),
);

//collectable resources on the board
$this->board_resources = array(
    array('x' => 5, 'y' => 3, 'res' => $this->resourceTypeStraw),
    array('x' => 7, 'y' => 3, 'res' => $this->resourceTypeStraw),
    array('x' => 7, 'y' => 5, 'res' => $this->resourceTypeStraw),
    array('x' => 5, 'y' => 7, 'res' => $this->resourceTypeStraw),
    array('x' => 3, 'y' => 7, 'res' => $this->resourceTypeStraw),
    array('x' => 3, 'y' => 5, 'res' => $this->resourceTypeStraw),
    array('x' => 3, 'y' => 3, 'res' => $this->resourceTypeWood),
    array('x' => 3, 'y' => 9, 'res' => $this->resourceTypeWood),
    array('x' => 9, 'y' => 3, 'res' => $this->resourceTypeWood),
    array('x' => 1, 'y' => 7, 'res' => $this->resourceTypeStone),
    array('x' => 7, 'y' => 1, 'res' => $this->resourceTypeStone),
    array('x' => 7, 'y' => 7, 'res' => $this->resourceTypeStone)
);


//tiles in the tile deck
  $this->tile_types = array(
	1 => array( 'type_id' => 1, 'lavaPoints' => array(3), 'isSourceTile' => false, 'count' => 1),
	2 => array( 'type_id' => 2, 'lavaPoints' => array(0,3), 'isSourceTile' => false, 'count' => 1),
	3 => array( 'type_id' => 3, 'lavaPoints' => array(3,4), 'isSourceTile' => false, 'count' => 2),
	4 => array( 'type_id' => 4, 'lavaPoints' => array(3,5), 'isSourceTile' => false, 'count' => 2),
    5 => array( 'type_id' => 5, 'lavaPoints' => array(0,2,4), 'isSourceTile' => false, 'count' => 3),
	6 => array( 'type_id' => 6, 'lavaPoints' => array(1,2,3), 'isSourceTile' => false, 'count' => 3),
    7 => array( 'type_id' => 7, 'lavaPoints' => array(0,1,2,3,4,5), 'isSourceTile' => true, 'count' => 3),
	8 => array( 'type_id' => 8, 'lavaPoints' => array(0,3,5), 'isSourceTile' => false, 'count' => 4),
	9 => array( 'type_id' => 9, 'lavaPoints' => array(0,1,3), 'isSourceTile' => false, 'count' => 4),
    10 => array( 'type_id' => 10, 'lavaPoints' => array(0,1,3,4), 'isSourceTile' => false, 'count' => 4),
    11 => array( 'type_id' => 11, 'lavaPoints' => array(0,1,2,3), 'isSourceTile' => false, 'count' => 4),
	12 => array( 'type_id' => 12, 'lavaPoints' => array(0,1,3,5), 'isSourceTile' => false, 'count' => 6),
    13 => array( 'type_id' => 13, 'lavaPoints' => array(0,1,2,3,5), 'isSourceTile' => false, 'count' => 6)
);

//cards in the action deck
$this->card_types = array(
    0 => array( 'type_id' => 0, 'name' => clienttranslate("Lava Flow"), 'wallType' => $this->resourceTypeStraw, 'count' => 3),
    1 => array( 'type_id' => 1, 'name' => clienttranslate("Reinforce"), 'wallType' => $this->resourceTypeStraw, 'count' => 6),
    2 => array( 'type_id' => 2, 'name' => clienttranslate("Relocate"), 'wallType' => $this->resourceTypeStraw, 'count' => 5),
    3 => array( 'type_id' => 3, 'name' => clienttranslate("Aftershock"), 'wallType' => $this->resourceTypeStone, 'count' => 5),
    4 => array( 'type_id' => 4, 'name' => clienttranslate("Rain"), 'wallType' => $this->resourceTypeWood, 'count' => 4),
    5 => array( 'type_id' => 5, 'name' => clienttranslate("Sinkhole"), 'wallType' => $this->resourceTypeWood, 'count' => 4),
    6 => array( 'type_id' => 6, 'name' => clienttranslate("Volcanic Bomb"), 'wallType' => $this->resourceTypeWood, 'count' => 4),
    7 => array( 'type_id' => 7, 'name' => clienttranslate("Quake"), 'wallType' => $this->resourceTypeStone, 'count' => 5),
);
