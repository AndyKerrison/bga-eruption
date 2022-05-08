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
 * states.inc.php
 *
 * akeruption game states description
 *
 */

/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: self::checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!

$machinestates = array(

    // The initial state. Please do not modify.
    1 => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array( "" => 10 )
    ),
    
    //Player turn order:
    //1) Assess damage
    //2) Draw & place a lava tile
    //3) Play action card(s)
    //4) Build a wall

    //endgame is triggered when one village burns up completely, or the lava tiles run out. At this point, each other player has one more turn.

    10 => array(
        "name" => "assessDamage",
        "type" => "game",
        "action" => "stAssessDamage",
        "updateGameProgression" => true,
        "transitions" => array( "placeNewSource" => 15, "drawTile" => 20, "zombiePass" => 70)
    ),

    15 => array(
        "name" => "playSourceTile",
        "description" => clienttranslate('${actplayer} must play a lava source tile'),
        "descriptionmyturn" => clienttranslate('${you} must play a lava source tile'),
        "args" => "argPlaySourceTile",
        "type" => "activeplayer",
        "updateGameProgression" => true,
        "possibleactions" => array( "playTile" ),
        "transitions" => array( "placeTile" => 16, "zombiePass" => 70)
    ),

    16 => array(
        "name" => "confirmSourceTile",
        "description" => clienttranslate('${actplayer} is placing a tile'),
        "descriptionmyturn" => clienttranslate('Use the arrows to position your tile and confirm, or pick a different location'),
        "args" => "argConfirmSourceTile",
        "type" => "activeplayer",
        "possibleactions" => array( "playTile", "confirmTile" ),
        "transitions" => array( "placeTile" => 16, "sourceTileDone" => 20, "zombiePass" => 70)
    ),

    20 => array(
        "name" => "drawTile",
        "type" => "game",
        "action" => "stDrawTile",
        "updateGameProgression" => true,
        "transitions" => array( "tilePicked" => 21, "tileStackEmpty" => 30 )
    ),

    21 => array(
        "name" => "playTile",
        "description" => clienttranslate('${actplayer} must play a lava tile'),
        "descriptionmyturn" => clienttranslate('${you} must play a lava tile'),
        "args" => "argPlayTile",
        "type" => "activeplayer",
        "possibleactions" => array( "playTile" ),
        "transitions" => array( "placeTile" => 22, "tileBlocked" => 20, "zombiePass" => 70)
    ),

    22 => array(
        "name" => "confirmTile",
        "description" => clienttranslate('${actplayer} is placing a tile'),
        "descriptionmyturn" => clienttranslate('Use the arrows to position your tile and confirm, or pick a different location'),
        "args" => "argConfirmTile",
        "type" => "activeplayer",
        "updateGameProgression" => true,
        "possibleactions" => array( "playTile", "confirmTile" ),
        "transitions" => array("placeTile" => 22, "tileBlocked" => 20, "tilesDone" => 30, "extraTileCheck" => 25, "wallsDefended"=>21, "zombiePass" => 70 )
    ),

    25 => array(
        "name" => "extraTileCheck",
        "description" => clienttranslate('${actplayer} may play an extra lava tile or pass'),
        "descriptionmyturn" => clienttranslate('${you} may play an extra lava tile or pass'),
        "type" => "activeplayer",
        "possibleactions" => array( "playExtraTile", "pass" ),
        "transitions" => array( "playExtraTile" => 20, "pass" => 30, "zombiePass" => 70)
    ),

    30 => array(
        "name" => "playActionCards",
        "description" => clienttranslate('${actplayer} can play action cards'),
        "descriptionmyturn" => clienttranslate('${you} can play action cards'),
        "type" => "activeplayer",
        "args" => "argActionCards",
        "updateGameProgression" => true,
        "possibleactions" => array( "playCard", "gainWall", "gainTile", "pass" ),
        "transitions" => array( "pass" => 35, "gainWall" => 30, "cardEffect"=>32, "gainTile"=>20, "zombiePass" => 70 )
    ),

    32 => array(
        "name" => "actionCardEffect",
        "type" => "game",
        "action" => "stCardEffect",
        "updateGameProgression" => true,
        "transitions" => array( "cardFinished" => 30, "playTile"=>20, "playWall"=>35, "aftershock"=> 56, "volcanicBomb"=> 53, "sinkhole"=>55, "quake"=>60, "relocate"=>65 )
    ),

    35 => array(
        "name" => "playWalls",
        "description" => clienttranslate('${actplayer} may build a wall'),
        "descriptionmyturn" => clienttranslate('${you} may build a wall'),
        "args" => "argPlayWall",
        "type" => "activeplayer",
        "possibleactions" => array( "buildWall", "pass" ),
        "transitions" => array( "wallLocationChosen" => 36, "pass" => 70, "zombiePass" => 70 )
    ),

    36 => array(
        "name" => "chooseWallMaterial",
        "description" => clienttranslate('${actplayer} must choose the material to build with'),
        "descriptionmyturn" => clienttranslate('${you} must choose the material to build with'),
        "args" => "argWallMaterial",
        "type" => "activeplayer",
        "possibleactions" => array( "wallBuilt", "pass" ),
        "transitions" => array( "wallCardEffectDone" => 30, "wallsDone" => 70, "pass" => 35, "extraWallCheck"=> 35, "zombiePass" => 70 )//'pass' here is a cancel, so go back to picking wall
    ),

    53 => array(
        "name" => "volcanicBomb",
        "description" => clienttranslate('Volcanic Bomb - ${actplayer} can destroy a wall'),
        "descriptionmyturn" => clienttranslate('Volcanic Bomb - ${you} can destroy a wall'),
        "args" => "argDestroyWall",
        "type" => "activeplayer",
        "possibleactions" => array( "destroyWall", "pass" ),
        "transitions" => array( "wallDestroyed" => 30, "pass" => 30, "zombiePass" => 70 )
    ),

    55 => array(
        "name" => "sinkhole",
        "description" => clienttranslate('Sinkhole - ${actplayer} can destroy a tile'),
        "descriptionmyturn" => clienttranslate('Sinkhole - ${you} can destroy a tile'),
        "args" => "argDestroyTile",
        "type" => "activeplayer",
        "possibleactions" => array( "destroyTile" ),
        "transitions" => array( "tileDestroyed" => 30, "zombiePass" => 70 )
    ),

    56 => array(
        "name" => "aftershock",
        "description" => clienttranslate('Aftershock - ${actplayer} can rotate a tile'),
        "descriptionmyturn" => clienttranslate('Aftershock - ${you} can rotate a tile'),
        "args" => "argRotateTile",
        "type" => "activeplayer",
        "possibleactions" => array( "selectTile", "pass" ),
        "transitions" => array( "tileSelected" => 57, "pass" => 30, "zombiePass" => 70 )
    ),

    57 => array(
        "name" => "confirmRotateTile",
        "description" => clienttranslate('${actplayer} is rotating a tile'),
        "descriptionmyturn" => clienttranslate('Use the arrows to position your tile and confirm, or pick a different location'),
        "args" => "argConfirmRotateTile",
        "type" => "activeplayer",
        "updateGameProgression" => true,
        "possibleactions" => array( "selectTile", "confirmTile" ),
        "transitions" => array("tileSelected" => 57, "tilesDone" => 30, "zombiePass" => 70 )
    ),

    60 => array(
        "name" => "quakeDrawTile",
        "type" => "game",
        "action" => "stQuakeDrawTile",
        "updateGameProgression" => true,
        "transitions" => array( "tilePicked" => 61, "tileStackEmpty" => 30 )
    ),

    61 => array(
        "name" => "quake",
        "description" => clienttranslate('Quake - ${actplayer} can replace a tile'),
        "descriptionmyturn" => clienttranslate('Quake - ${you} can replace a tile'),
        "args" => "argQuake",
        "type" => "activeplayer",
        "possibleactions" => array( "selectTile", "pass" ),
        "transitions" => array( "tileSelected" => 62, "zombiePass" => 70 )
    ),
    
    62 => array(
        "name" => "confirmReplaceTile",
        "description" => clienttranslate('${actplayer} is replacing a tile'),
        "descriptionmyturn" => clienttranslate('Use the arrows to position your tile and confirm, or pick a different location'),
        "args" => "argConfirmReplaceTile",
        "type" => "activeplayer",
        "updateGameProgression" => true,
        "possibleactions" => array( "selectTile", "confirmTile" ),
        "transitions" => array("tileSelected" => 62, "tilesDone" => 30, "zombiePass" => 70 )
    ),
    
    65 => array(
        "name" => "relocatePickWall",
        "description" => clienttranslate('${actplayer} can earrange the walls in their village'),
        "descriptionmyturn" => clienttranslate('${you} can pick a wall to move'),
        "args" => "argRelocatePickWall",
        "type" => "activeplayer",
        "possibleactions" => array( "removeWall", "pass" ),
        "transitions" => array( "wallSelected" => 66, "pass" => 30, "zombiePass" => 70 )
    ),
    
    66 => array(
        "name" => "relocatePlaceWall",
        "description" => clienttranslate('${actplayer} is rearranging the walls in their village'),
        "descriptionmyturn" => clienttranslate('${you} must choose the destination for this wall'),
        "args" => "argRelocatePlaceWall",
        "type" => "activeplayer",
        "possibleactions" => array( "placeWall", "pass" ),
        "transitions" => array( "wallPlaced" => 65, "pass" => 65, "zombiePass" => 70 )
    ),

    70 => array(
        "name" => "endTurn",
        "type" => "game",
        "action" => "stEndTurn",
        "updateGameProgression" => true,
        "transitions" => array( "nextPlayer" => 10, "finalScoring" => 80 )
    ),

    80 => array(
        "name" => "finalScoring",
        "type" => "game",
        "action" => "stFinalScoring",
        "updateGameProgression" => true,
        "transitions" => array( "" => 99 )
    ),
    
/*
    Examples:
    
    2 => array(
        "name" => "nextPlayer",
        "description" => '',
        "type" => "game",
        "action" => "stNextPlayer",
        "updateGameProgression" => true,   
        "transitions" => array( "endGame" => 99, "nextPlayer" => 10 )
    ),
    
    10 => array(
        "name" => "playerTurn",
        "description" => clienttranslate('${actplayer} must play a card or pass'),
        "descriptionmyturn" => clienttranslate('${you} must play a card or pass'),
        "type" => "activeplayer",
        "possibleactions" => array( "playCard", "pass" ),
        "transitions" => array( "playCard" => 2, "pass" => 2 )
    ), 

*/    
   
    // Final state.
    // Please do not modify.
    99 => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);


