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
  * akeruption.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class akeruption extends Table
{
	function __construct( )
	{
        	
 
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();self::initGameStateLabels( array( 
        "activeTile" => 10,
        "gameOverTriggered" => 11,
        "gameOverPlayerId" => 12,
        "wallBuildX" => 13,
        "wallBuildY" => 14,
        "wallBuildDir" => 15,
        "cardPlayedID" => 16,
        "isCardEffect"=>17,
        "lastCardDiscarded"=>18,
            //    "my_first_global_variable" => 10,
            //    "my_second_global_variable" => 11,
            //      ...
            //    "my_first_game_variant" => 100,
            //    "my_second_game_variant" => 101,
            //      ...
        "exclude_rain_cards" => 100,
        ) );
        
        $this->tiles = self::getNew( "module.common.deck" );
        $this->tiles->init( "tiles" );

        $this->cards = self::getNew( "module.common.deck" );
        $this->cards->init( "cards" );
	}
	
    protected function getGameName( )
    {
		// Used for translations and stuff. Please do not modify.
        return "akeruption";
    }	

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() )
    {          
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        //$default_colors = array( "ff0000", "008000", "0000ff", "ffa500", "773300" );
        
        //set colours based on player number and board position
        //2p = orange, blue
        //3p = orange, green, purple
        //4p = yellow, green, purple, red
        //5p = orange, yellow, green, purple, red
        //6p = orange, yellow, blue, green, purple, red
        $orange = 'ffa500';
        $yellow = 'eeee00';
        $green = '00aa66';
        $blue = '0000ff';
        $purple = 'cc00cc';
        $red = 'ff0000';

        $colorPos = array($orange, $yellow, $green, $blue, $purple, $red);
        
        if (count($players) == 2)
        {
            $positions = array(0, 3);
        }
        else if (count($players) == 3)
        {
            $positions = array(0, 2, 4);
        }
        else if (count($players) == 4)
        {
            $positions = array(1, 2, 4, 5);
        }
        else if (count($players) == 5)
        {
            $positions = array(0, 1, 2, 4, 5);
        }
        else if (count($players) == 6)
        {
            $positions = array(0,1,2,3,4,5);
        }
        else
        {
            throw new feException("unrecognised player count");
        }
 
        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_position, player_canal, player_name, player_avatar, player_score, straw, wood, stone) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player )
        {
            $position = array_shift($positions);
            $values[] = "('".$player_id."','".$colorPos[$position]."','$position','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."', '".$this->maxBurnTemp."', '1','1', '1')";
        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        //self::reattributeColorsBasedOnPreferences( $players, array(  "ff0000", "008000", "0000ff", "ffa500", "773300" ) );
        self::reloadPlayersBasicInfos();
        
        /************ Start the game initialization *****/

        // Init global values with their initial values
        self::setGameStateInitialValue( 'gameOverTriggered', 0 );
        self::setGameStateInitialValue( 'gameOverPlayerId', 0 );
        
        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)
        
        self::initStat("player", "player_stonewalls", 0);
        self::initStat("player", "player_woodwalls", 0);
        self::initStat("player", "player_strawwalls", 0);
        self::initStat("player", "player_lavaconnections", 0);
        
        self::initStat("player", "player_tilesplaced", 0);
        self::initStat("player", "player_cardsplayed", 0);
        self::initStat("player", "player_cardstraded", 0);
        
        // Setup the initial game situation here
       
        //init deck of lava tiles. location_arg for 'board' will be 11y+x. Custom functions will be used to code/decode this.
        $tiles = array();
        foreach( $this->tile_types as $tileType)
        {
            $tile = array('type' => $tileType["type_id"], 'type_arg' => 0, 'nbr' => $tileType["count"]);
            array_push($tiles, $tile);
        }
        
        $this->tiles->createCards( $tiles, 'deck' );
        $this->tiles->shuffle( 'deck' );

        //move the lava source tiles to 'source' location
        $lavaSourceTiles = $this->tiles->getCardsOfType( 7 );
        
        foreach( $lavaSourceTiles as $tile)
        {
            $this->tiles->moveCard($tile["id"], 'source');
        }

        //load the deck of action cards. Deal 3 to each player.
        $excludeRainCards = $this->getGameStateValue('exclude_rain_cards')== 2;
        $cards = array();
        foreach( $this->card_types as $cardType)
        {
            if ($cardType["type_id"] == 4 && $excludeRainCards)
            {
                continue;
            }
            $card = array( 'type' => $cardType["type_id"], 'type_arg' => 0, 'nbr' => $cardType["count"]);
            array_push($cards, $card);
        }

        $this->cards->createCards( $cards, 'deck' );
        $this->cards->shuffle( 'deck' );

        foreach( $players as $player_id => $player )
        {
            $this->cards->pickCards( 3, 'deck', $player_id );
        }


        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array( 'players' => array() );
    
        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!
    
        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score, player_temp, player_position, straw, wood, stone FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );
        
        foreach( $result['players'] as $player_id => $player )
        {
            $result['players'][$player_id]['cardCount'] = $this->cards->countCardsInLocation('hand', $player_id);
            $result['players'][$player_id]['sourceCount'] = $this->tiles->countCardsInLocation('hand', $player_id);
        }

        $result['walls'] = $this -> getWalls();


        //Gather all information about current game situation (visible by player $current_player_id).
        $result['tileTypes'] = $this->tile_types;
        $result['hand'] = $this->cards->getCardsInLocation('hand', $current_player_id);
        $result['boardTiles'] = $this->tiles->getCardsInLocation('board');
        $result['lavaSourceTileCount'] = $this->tiles->countCardsInLocation('source');
        $result['tileCount'] = $this->tiles->countCardsInLocation('deck');
        $result['cardCount'] = $this->cards->countCardsInLocation('deck');
        $result['activePlayerId'] = self::getActivePlayerId();
        $result['activePlayerPos'] = $this->getPlayerVariable('player_position', self::getActivePlayerId());
        
        $lastCardDiscarded = $this->getGameStateValue('lastCardDiscarded');
        if ($lastCardDiscarded >0)
        {
            $result['lastCardDiscarded'] = $this->cards->getCard($lastCardDiscarded);
        }    
        
        $active_tile_id = $this->getGameStateValue('activeTile');
        if ($active_tile_id == 0)
        {
            $result['activeTile'] = null;
        }
        else
        {
            $result['activeTile'] = $this->tiles->getCard( $active_tile_id );
        }
  
        return $result;
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression()
    {
        //game progression is whichever is higher: percentage of tiles used, or highest player temp to max
        $deck = $this->tiles->countCardsInLocation("deck");
        $total = $deck + $this->tiles->countCardsInLocation("board");

        $tileCompletion= 100*(1 - ($deck/($total+1))); //+1 as tiles gone doesn't end game immediately

        //throw new feException();
        $sql = "select max(player_temp) as maxtemp from player";
        $result = self::getObjectFromDB($sql);
        $tempCompletion = (100*$result['maxtemp'])/($this->maxBurnTemp+10); //extra 10 as maxtemp doesnt end immediately

        return max($tileCompletion, $tempCompletion);
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    /*
        In this space, you can put any utility methods useful for your game logic
    */

    function canBuildWall(){
        $materials = $this->argWallMaterial();
        if ($materials[$this->resourceTypeStraw] + $materials[$this->resourceTypeWood] + $materials[$this->resourceTypeStone] == 0){
            return false;
        }
        return true;
    }

    function drawCard($player_id){

        $card = $this->cards->pickCard( "deck", self::getActivePlayerId());
        
        self::notifyAllPlayers("dealCard", "", array(
            'player_id' => self::getActivePlayerId(),
            "cardCount" => $this->cards->countCardsInLocation('deck')
        ));

        //send the new card to the player
        self::notifyPlayer( self::getActivePlayerId(), "drawCard", "", array( 
            "card" => $card,
            "cardCount" => $this->cards->countCardsInLocation('deck')
                )
        );
        
        self::notifyAllPlayers("resourceCount", "", array(
            'player_id' => self::getActivePlayerId(),
            'resourceType' => "card",
            'resourceCount' => $this->cards->countCardsInLocation('hand', self::getActivePlayerId())
        ));
        
        //if the action card deck is empty, reshuffle it
        if ($this->cards->countCardsInLocation("deck") == 0) {
            $this->cards->moveAllCardsInLocation("discard", "deck");
            $this->cards->shuffle("deck");
            $this->setGameStateValue("lastCardDiscarded", 0);
            
            self::notifyAllPlayers("reshuffle", clienttranslate("Action card deck is empty, discard pile reshuffled to form new deck"), array(
                'cardCount' => $this->cards->countCardsInLocation('deck')
            ) );
        }
    }

    function getRotationFromLocationInt($location)
    {
        return floor($location / (11*11) );
    }

    function getXfromLocationInt($location)
    {
        return $location % 11;
    }

    function getYfromLocationInt($location)
    {
        return floor( ($location % (11*11) ) / 11);
    }

    function getLocationAsInt($x, $y, $rotation)
    {
        return 11*11*$rotation + 11*$y + $x;
    }

    function moveCoords(&$x, &$y, $dir)
    {
        if ($dir == $this->dirS) {
            $y++;
        }
        if ($dir == $this->dirSW)
        {
            $x--;
            $y++;
        }
        if ($dir == $this->dirNW)
        {
            $x--;
        }
        if ($dir == $this->dirN)
        {
            $y--;
        }
        if ($dir == $this->dirNE)
        {
            $x++;
            $y--;
        }
        if ($dir == $this->dirSE)
        {
            $x++;
        }
    }

    function isSpaceUsed($x, $y)
    {
        $boardTiles = $this->tiles->getCardsInLocation('board');
        //var_dump($boardTiles);
        //die('ok');

        foreach ($boardTiles as $tileID => $tile)
        {
            $boardX = $this->getXfromLocationInt($tile["location_arg"]);
            $boardY = $this->getYfromLocationInt($tile["location_arg"]);

            if ($boardX == $x && $boardY == $y)
            {
                return true;
            }
        }
        return false;
    }

    function isSpaceConnected($x, $y)
    {
        /*if ($this->getConnectionState($x, $y - 1, $this->dirS) != $this->tileStateEmpty)
            return true;
        if ($this->getConnectionState($x + 1, $y - 1, $this->dirSW) != $this->tileStateEmpty)
            return true;
        if ($this->getConnectionState($x + 1, $y, $this->dirNW) != $this->tileStateEmpty)
            return true;
        if ($this->getConnectionState($x, $y +1, $this->dirN) != $this->tileStateEmpty)
            return true;
        if ($this->getConnectionState($x - 1, $y +1, $this->dirNE) != $this->tileStateEmpty)
            return true;
        if ($this->getConnectionState($x-1, $y, $this->dirSE) != $this->tileStateEmpty)
            return true;*/
        if ($this->getConnectionState($x, $y - 1, $this->dirS) == $this->tileStateLava)
            return true;
        if ($this->getConnectionState($x + 1, $y - 1, $this->dirSW) == $this->tileStateLava)
            return true;
        if ($this->getConnectionState($x + 1, $y, $this->dirNW) == $this->tileStateLava)
            return true;
        if ($this->getConnectionState($x, $y +1, $this->dirN) == $this->tileStateLava)
            return true;
        if ($this->getConnectionState($x - 1, $y +1, $this->dirNE) == $this->tileStateLava)
            return true;
        if ($this->getConnectionState($x-1, $y, $this->dirSE) == $this->tileStateLava)
            return true;
        return false;
    }

    //lava points must connect to other lava points or empty spaces
    //grass must connect to grass or empty spaces
    //at least one lava point must be connected
    function isTilePlacementValid($x, $y, $tileType, $rotation, $isSourceTile)
    {
        $lavapoints = $this->getLavaPoints($tileType, $rotation);

        //var_dump( $tileType );
        //var_dump( $lavapoints );
        //die('ok');

        //these direction names are relative from the conncted tile, NOT the current tile
        $north = $this->getConnectionState($x, $y +1, $this->dirN);
        $northEast = $this->getConnectionState($x - 1, $y +1, $this->dirNE);
        $southEast = $this->getConnectionState($x-1, $y, $this->dirSE);
        $south = $this->getConnectionState($x, $y - 1, $this->dirS);
        $southWest = $this->getConnectionState($x + 1, $y - 1, $this->dirSW);
        $northWest = $this->getConnectionState($x + 1, $y, $this->dirNW);
        //var_dump( $x );
        //var_dump( $y );
        //var_dump( $north );
        //var_dump( $northEast );
        //var_dump( $southEast );
        //var_dump( $south );
        //var_dump( $southWest );
        //var_dump( $northWest );
        //die('ok');

        if (in_array(3, $lavapoints) && $north == $this->tileStateGrass)
            return false;
        if (!in_array(3, $lavapoints) && $north == $this->tileStateLava)
            return false;

        
        if (in_array(4, $lavapoints) && $northEast == $this->tileStateGrass)
            return false;
        if (!in_array(4, $lavapoints) && $northEast == $this->tileStateLava)
            return false;

        
        if (in_array(5, $lavapoints) && $southEast == $this->tileStateGrass)
            return false;
        if (!in_array(5, $lavapoints) && $southEast == $this->tileStateLava)
            return false;

        
        if (in_array(0, $lavapoints) && $south == $this->tileStateGrass)
            return false;
        if (!in_array(0, $lavapoints) && $south == $this->tileStateLava)
            return false;

        
        if (in_array(1, $lavapoints) && $southWest == $this->tileStateGrass)
            return false;
        if (!in_array(1, $lavapoints) && $southWest == $this->tileStateLava)
            return false;

        
        if (in_array(2, $lavapoints) && $northWest == $this->tileStateGrass)
            return false;
        if (!in_array(2, $lavapoints) && $northWest == $this->tileStateLava)
            return false;

        //return true if at least one lava state connected, or this is a source tile
        return $isSourceTile || $north == $this->tileStateLava ||
            $northEast == $this->tileStateLava ||
            $southEast == $this->tileStateLava ||
            $south == $this->tileStateLava ||
            $southWest == $this->tileStateLava ||
            $northWest == $this->tileStateLava;
    }

    function getConnectionState($x, $y, $dir)
    {
        //these are off the board limit, always empty
        if ($x < 0 || $x > 10 || $y < 0 || $y > 10)
            return $this->tileStateEmpty;

        //hard coded central tiles
        if (($x == 4 && $y == 5) || ($x == 6 && $y == 4) || ($x == 5 && $y == 6))
            return $this->tileStateGrass;
        if (($x == 4 && $y == 6) || ($x == 5 && $y == 4) || ($x == 6 && $y == 5))
        {
            if ($x == 4 && $y == 6 && $dir == $this->dirNW)
                return $this->tileStateLava;
            if ($x == 4 && $y == 6 && $dir == $this->dirS)
                return $this->tileStateLava;
            if ($x == 5 && $y == 4 && $dir == $this->dirNW)
                return $this->tileStateLava;
            if ($x == 5 && $y == 4 && $dir == $this->dirNE)
                return $this->tileStateLava;
            if ($x == 6 && $y == 5 && $dir == $this->dirNE)
                return $this->tileStateLava;
            if ($x == 6 && $y == 5 && $dir == $this->dirS)
                return $this->tileStateLava;
            return $this->tileStateGrass;
        }

        $boardTiles = $this->tiles->getCardsInLocation('board');
        foreach($boardTiles as $tile)
        {
            $tileX = $this->getXfromLocationInt($tile['location_arg']);
            $tileY = $this->getYfromLocationInt($tile['location_arg']);
            $tileRotation = $this->getRotationFromLocationInt($tile['location_arg']);

            if ($x == $tileX && $y == $tileY)
            {
                $lavaPoints = $this->getLavaPoints($tile['type'], $tileRotation);
                
                if ($dir == $this->dirN && in_array(0, $lavaPoints))
                    return $this->tileStateLava;
                if ($dir == $this->dirNE && in_array(1, $lavaPoints))
                    return $this->tileStateLava;
                if ($dir == $this->dirSE && in_array(2, $lavaPoints))
                    return $this->tileStateLava;
                if ($dir == $this->dirS && in_array(3, $lavaPoints))
                    return $this->tileStateLava;
                if ($dir == $this->dirSW && in_array(4, $lavaPoints))
                    return $this->tileStateLava;
                if ($dir == $this->dirNW && in_array(5, $lavaPoints))
                    return $this->tileStateLava;

                return $this->tileStateGrass;
            }
        }
        return $this->tileStateEmpty;
    }

    function getLavaPoints($tileType, $rotation)
    {
        $lavapoints = $this->tile_types[$tileType]['lavaPoints'];

        //rotation is clockwise, add to each of the lava points and mod 6 as necessary
        for ($i = 0; $i < count($lavapoints); $i++) {
            $lavapoints[$i] = ($lavapoints[$i] + $rotation)%6;
        }

        return $lavapoints;
    }

    function getPlayerVariable($varName, $playerId)
    {
        $sql = "SELECT 0, ".$varName." FROM player where player_id = '".$playerId."'";
        $player = self::getCollectionFromDb( $sql );
        return $player[0][$varName];
    }

    function setPlayerVariable($varName, $playerId, $newValue)
    {
        $sql = "update player set ".$varName." = '".$newValue."' where player_id = '".$playerId."'";
        self::DbQuery( $sql );

        if ($varName = "player_temp") {
            $sql = "UPDATE player SET player_score = " . $this->maxBurnTemp . " - player_temp WHERE player_id=" . $playerId;
            self::DbQuery($sql);
        }
    }

    function getActiveTileValidRotations()
    {
        $active_tile_id = $this->getGameStateValue('activeTile');
        $tile = $this->tiles->getCard( $active_tile_id );

        $x = $this->getXfromLocationInt($tile['location_arg']);
        $y = $this->getYfromLocationInt($tile['location_arg']);
        $isSourceTile = $this->tile_types[$tile["type"]]["isSourceTile"] == "true";

        return $this->getValidRotations($tile, $x, $y, $isSourceTile);
    }

    function getValidRotations($tile, $x, $y, $isSourceTile)
    {
        $tileType = $tile['type'];

        $result = array();

        for ($rotation = 0; $rotation < 6; $rotation++)
        {
            if ($this->isTilePlacementValid($x, $y, $tileType, $rotation, $isSourceTile))
            {
                $result[] = $rotation;
            }
        }

        return $result;
    }

    //returns the tiles and directions which, if containing a lava flow, will damage this village
    function getDangerTiles($playerPosition)
    {
        //each position has 7 connections it can burn in
        if ($playerPosition == 0)
        {
            $dangerTiles = array(array(3,2,$this->dirNE), array(4, 2, $this->dirN), array(4, 2, $this->dirNE), array(5, 2, $this->dirN), array(6, 1, $this->dirNW), array(6, 1, $this->dirN), array(7, 0, $this->dirNW));
        }
        else if ($playerPosition == 1)
        {
            $dangerTiles = array(array(8,0,$this->dirSE), array(8, 1, $this->dirNE), array(8, 1, $this->dirSE), array(8, 2, $this->dirNE), array(9, 2, $this->dirN), array(9, 2, $this->dirNE), array(10, 2, $this->dirN));
        }
        else if ($playerPosition == 2)
        {
            $dangerTiles = array(array(8,7,$this->dirNE), array(8, 6, $this->dirSE), array(8, 6, $this->dirNE), array(8, 5, $this->dirSE), array(9, 4, $this->dirS), array(9, 4, $this->dirSE), array(10, 3, $this->dirS));
        }
        else if ($playerPosition == 3)
        {
            $dangerTiles = array(array(3,10,$this->dirSE), array(4, 9, $this->dirS), array(4, 9, $this->dirSE), array(5, 8, $this->dirS), array(6, 8, $this->dirSW), array(6, 8, $this->dirS), array(7, 8, $this->dirSW));
        }
        else if ($playerPosition == 4)
        {
            $dangerTiles = array(array(0,8,$this->dirS), array(1, 8, $this->dirSW), array(1, 8, $this->dirS), array(2, 8, $this->dirSW), array(2, 9, $this->dirNW), array(2, 9, $this->dirSW), array(2, 10, $this->dirNW));
        }
        else if ($playerPosition == 5)
        {
            $dangerTiles = array(array(0, 7, $this->dirN), array(1, 6, $this->dirNW), array(1, 6, $this->dirN), array(2, 5, $this->dirNW), array(2, 4, $this->dirSW), array(2, 4, $this->dirNW), array(2, 3, $this->dirSW));
        }
        else
        {
            throw new feException("unrecognised player position, or player not found");
        }

        return $dangerTiles;
    }

    function increasePlayerTemp($player_id, $damage)
    {
        $existingDamage = $this->getPlayerVariable('player_temp', $player_id);
        $existingDamage += $damage;
        if ($existingDamage >= $this->maxBurnTemp)
        {
            $existingDamage = $this->maxBurnTemp;
        }

        $this->setPlayerVariable('player_temp', $player_id, $existingDamage);
        $score = $this->getPlayerVariable('player_score', $player_id);

        self::notifyAllPlayers( "increaseTemp", clienttranslate( '${player_name} increases temperature by ${temp_change} degrees to ${temp_total}' ), array(
            'player_name' => $this->getPlayerVariable('player_name', $player_id),
            'player_id' => $player_id,
            'player_position' => $this->getPlayerVariable('player_position', $player_id),
            'temp_change' => $damage,
            'temp_total' => $existingDamage,
            'score' =>$score
        ) );
    }

    function decreasePlayerTemp($player_id, $cooling)
    {
        $existingDamage = $this->getPlayerVariable('player_temp', $player_id);
        $existingDamage = $existingDamage - $cooling;
        if ($existingDamage <= 0)
        {
            $existingDamage = 0;
        }

        $this->setPlayerVariable('player_temp', $player_id, $existingDamage);
        $score = $this->getPlayerVariable('player_score', $player_id);

        self::notifyAllPlayers( "increaseTemp", clienttranslate( '${player_name} reduces temperature by ${temp_change} degrees to ${temp_total}' ), array(
            'player_name' => $this->getPlayerVariable('player_name', $player_id),
            'player_id' => $player_id,
            'player_position' => $this->getPlayerVariable('player_position', $player_id),
            'temp_change' => -$cooling,
            'temp_total' => $existingDamage,
            'score' =>$score
        ) );
    }

    function getWalls()
    {
        $sql = "SELECT wall_id, wall_type, wall_x, wall_y, wall_rotation from walls";
        return self::getCollectionFromDb($sql);
    }

    function destroyWallsAt($x, $y)
    {
        $sql = "select wall_id, wall_type, wall_x, wall_y, wall_rotation from walls where wall_x=".$x." and wall_y=".$y;

        $walls = self::getCollectionFromDB($sql);

        foreach($walls as $wall) {
            self::notifyAllPlayers("wallStatus", clienttranslate('${wall_name} wall is destroyed'), array(
                'wall_name' => $this->resources[$wall['wall_type']],
                'wall_x' => $wall['wall_x'],
                'wall_y' => $wall['wall_y'],
                'wall_rotation' => $wall['wall_rotation']
            ));

            $sql = "delete from walls where wall_id = ".$wall['wall_id'];
            self::DbQuery( $sql );
        }
    }

    function checkWallDestroyed($wall, $includeActivePlayerName)
    {
        $wallStrength = bga_rand(1, 6);
        $lavaStrength = bga_rand(1, 6);
        $wallBonus = 0;
        if ($wall['wall_type'] == $this->resourceTypeWood) {
            $wallBonus = 1;
        }
        if ($wall['wall_type'] == $this->resourceTypeStone) {
            $wallBonus = 2;
        }

        if ($lavaStrength >= ($wallStrength + $wallBonus))
        {
            //wall destroyed
            $sql = "delete from walls where wall_id = ".$wall['wall_id'];
            self::DbQuery( $sql );

            if ($includeActivePlayerName) {
                self::notifyAllPlayers( "wallStatus", clienttranslate( '${player_name} ${wall_name} wall (${wall_strength}+${wall_bonus}) is destroyed by the lava flow (${lava_strength})' ), array(
                    'player_name' => self::getActivePlayerName(),
                    'wall_name' => $this->resources[$wall['wall_type']],
                    'wall_strength' => $wallStrength,
                    'wall_bonus' => $wallBonus,
                    'lava_strength' => $lavaStrength,
                    'wall_x' => $wall['wall_x'],
                    'wall_y' => $wall['wall_y'],
                    'wall_rotation' => $wall['wall_rotation']
                ) );
            }
            else{
                self::notifyAllPlayers( "wallStatus", clienttranslate( '${wall_name} wall (${wall_strength}+${wall_bonus}) is destroyed by the lava flow (${lava_strength})' ), array(
                    'wall_name' => $this->resources[$wall['wall_type']],
                    'wall_strength' => $wallStrength,
                    'wall_bonus' => $wallBonus,
                    'lava_strength' => $lavaStrength,
                    'wall_x' => $wall['wall_x'],
                    'wall_y' => $wall['wall_y'],
                    'wall_rotation' => $wall['wall_rotation']
                ) );
            }
            return true;
        }
        else
        {
            //wall holds
            if ($includeActivePlayerName){
                self::notifyAllPlayers( "wallStatus", clienttranslate( '${player_name} ${wall_name} wall withstands (${wall_strength}+${wall_bonus}) the lava flow (${lava_strength})' ), array(
                    'player_name' => self::getActivePlayerName(),
                    'wall_name' => $this->resources[$wall['wall_type']],
                    'wall_strength' => $wallStrength,
                    'wall_bonus' => $wallBonus,
                    'lava_strength' => $lavaStrength
                ) );
            }
            else{
                self::notifyAllPlayers( "wallStatus", clienttranslate( '${wall_name} wall withstands (${wall_strength}+${wall_bonus}) the lava flow (${lava_strength})' ), array(
                    'wall_name' => $this->resources[$wall['wall_type']],
                    'wall_strength' => $wallStrength,
                    'wall_bonus' => $wallBonus,
                    'lava_strength' => $lavaStrength
                ) );
            }
            return false;
        }
    }
    
    function processVillageConnections($x, $y, $startLavaPoints, $endLavaPoints)
    {
        $newVillageConnections = 0;
        $newLavaPoints = array();
        foreach ($endLavaPoints as $newLava)
        {
            if (!in_array($newLava, $startLavaPoints))
            {
                $newLavaPoints[] = $newLava;
            }
        }
        for($pos = 0; $pos < 6; $pos++)
        {
            $dangerTiles = $this->getDangerTiles($pos);
            foreach($dangerTiles as $dangerTile)
            {
                if ($dangerTile[0] == $x && $dangerTile[1] == $y && in_array($dangerTile[2], $newLavaPoints))
                {
                    $newVillageConnections++;
                }
            }
        }

        if ($newVillageConnections > 0) {

            self::notifyAllPlayers("drawsCard", clienttranslate('${player_name} makes ${count} new connections in contact with villages, and draws ${count} cards'), array(
                'player_name' => self::getActivePlayerName(),
                'count' => $newVillageConnections
            ));

            while ($newVillageConnections > 0) {
                $this->drawCard(self::getActivePlayerId());

                $newVillageConnections--;
            }
        }
    }
        

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in akeruption.action.php)
    */

    //when the active player puts down a tile, before rotating or confirming it
    function pass(){
        self::checkAction( "pass" );

        $this->gamestate->nextState('pass');
    }

    function cardPass(){
        self::checkAction( "pass" );

        //must not have more than three cards in hand
        $cardCount = $this->cards->countCardsInLocation("hand", self::getActivePlayerId());
        if ($cardCount >3 )
        {
            throw new feException("cannot pass with more than three cards in hand");
        }

        $this->gamestate->nextState('pass');
    }

    function playExtraTile(){
        self::checkAction( "playExtraTile" );

        $this->gamestate->nextState('playExtraTile');
    }

    function rotationTileSelected($tileID) {
        self::checkAction( "selectTile" );

        $args = $this->argRotateTile();
        $rotatable = $args["validTiles"];
        $isRotatable = false;
        foreach ($rotatable as $tile) {
            if ($tile['id'] == $tileID) {
                $isRotatable = true;
            }
        }

        if (!$isRotatable) {
            throw new feException("Invalid rotate tile choice");
        }

        $this->setGameStateValue('activeTile', $tileID);

        $this->gamestate->nextState('tileSelected');
    }
    
    function replaceableTileSelected($tileID) {
        self::checkAction( "selectTile" );

        $args = $this->argQuake();
        $validTiles = $args["validTiles"];
        $isValid = false;
        foreach ($validTiles as $validTile) {
            if ($validTile['id'] == $tileID) {
                $isValid = true;
            }
        }

        if (!$isValid) {
            throw new feException("Invalid replace tile choice");
        }

        //set the active tile x and y to the chosen tile x and y
        $activeTileID = $this->getGameStateValue('activeTile');
        $tile = $this->tiles->getCard($tileID);
        $x = $this->getXfromLocationInt($tile['location_arg']);
        $y = $this->getYfromLocationInt($tile['location_arg']);
        $location = $this->getLocationAsInt($x, $y, 0);
        $this->tiles->moveCard($activeTileID, "board_temp", $location);
        //$this->setGameStateValue('activeTile', $tileID);
        
        self::notifyPlayer( self::getActivePlayerId(), "tilePlaced", "", array(
            'tile' => $this->tiles->getCard( $activeTileID ),
        ) );

        $this->gamestate->nextState('tileSelected');
    }

    function removeTile($tileID) {
        self::checkAction( "destroyTile" );

        $args = $this->argDestroyTile();
        $removable = $args["validTiles"];
        $isRemovable = false;
        foreach ($removable as $tile) {
            if ($tile['id'] == $tileID) {
                $isRemovable = true;
            }
        }

        if (!$isRemovable) {
            throw new feException("Invalid remove tile choice");
        }

        $tile = $this->tiles->getCard($tileID);
        $x = $this->getXfromLocationInt($tile['location_arg']);
        $y = $this->getYfromLocationInt($tile['location_arg']);

        $this->tiles->moveCard($tileID, "discard");

        $tile = $this->tiles->getCard($tileID); //refresh tile data
        self::notifyAllPlayers("tileRemove", clienttranslate( '${player_name} destroys a tile' ), array(
            'player_id' => self::getActivePlayerId(),
            'player_name' => self::getActivePlayerName(),
            'tile' => $tile
        ) );

        //and destroy the walls too
        $this->destroyWallsAt($x, $y);

        $this->gamestate->nextState('tileDestroyed');
    }

    //lots of possible transitions, so give this it's own state
    function playCard($cardID) {
        self::checkAction( "playCard" );

        $this->setGameStateValue("cardPlayedID", $cardID);

        $this->gamestate->nextState('cardEffect');
    }

    function tradeCardForWall($cardID) {
        self::checkAction( "gainWall" );

        $card = $this->cards->getCard( $cardID );
        $cardType = $this->card_types[$card['type']];
        $resType = $cardType['wallType'];

        $resCount = $this->getPlayerVariable($resType, self::getActivePlayerId());
        $resCount++;
        $this->setPlayerVariable($resType, self::getActivePlayerId(), $resCount);

        $this->cards->moveCard($card["id"], 'discard');

        self::notifyAllPlayers("cardDiscard", clienttranslate( '${player_name} discards ${card_name}' ), array(
            'player_id' => self::getActivePlayerId(),
            'player_name' => self::getActivePlayerName(),
            'card_id' => $card['id'],
            'card_name' => $cardType["name"],
            'card_type' => $card["type"]
        ) );
        
        self::incStat(1, "player_cardstraded", self::getActivePlayerId());
        
        self::notifyAllPlayers("resourceCount", "", array(
            'player_id' => self::getActivePlayerId(),
            'resourceType' => "card",
            'resourceCount' => $this->cards->countCardsInLocation('hand', self::getActivePlayerId())
        ));
        
        $this->setGameStateValue("lastCardDiscarded", $card['id']);

        self::notifyAllPlayers( "wallGain", clienttranslate( '${player_name} gains 1 ${resource_name} wall' ), array(
            'player_id' => self::getActivePlayerId(),
            'player_name' => self::getActivePlayerName(),
            'resource_name' => $this->resources[$resType],
            'resource_id' => $resType
        ) );
        
        self::notifyAllPlayers("resourceCount", "", array(
            'player_id' => self::getActivePlayerId(),
            'resourceType' => $resType,
            'resourceCount' => $resCount
        ));
        
        $this->gamestate->nextState('gainWall');
    }

    function tradeCardsForTile($cardIDstr) {
        self::checkAction( "gainTile" );
        
        $tileCount = $this->tiles->countCardsInLocation("deck");
        if ($tileCount < 1)
        {
            throw new feException("There are no tiles left to trade for");
        }

        $cardIDs = explode(",", $cardIDstr);

        $cards = $this->cards->getCards( $cardIDs );

        $this->cards->moveCards($cardIDs, 'discard');

        foreach ($cards as $card)
        {
            self::notifyPlayer(self::getActivePlayerId(), "cardDiscard", "" , array(
                'player_id' => self::getActivePlayerId(),
                'card_id' => $card['id'],
                'card_type' => $card["type"]
            ) );
            
            self::incStat(1, "player_cardstraded", self::getActivePlayerId());
            
            self::notifyAllPlayers("resourceCount", "", array(
            'player_id' => self::getActivePlayerId(),
            'resourceType' => "card",
            'resourceCount' => $this->cards->countCardsInLocation('hand', self::getActivePlayerId())
        ));
            
            $this->setGameStateValue("lastCardDiscarded", $card['id']);
        }

        self::notifyAllPlayers( "cardsDiscard", clienttranslate( '${player_name} discards 2 cards to place a new lava tile' ), array(
            'player_id' => self::getActivePlayerId(),
            'player_name' => self::getActivePlayerName(),
        ) );

        $this->gamestate->nextState('gainTile');
    }

    function wallMaterialSelected($id){
        self::checkAction( "wallBuilt" );

        //build the wall
        // - validate the material chosen (location was already validated)
        // - update the game state database
        // - send a message to all players to draw the new wall & update player resource count
        // - make sure game setup js builds walls too
        // - also check if we can build an extra wall

        $playerID = self::getActivePlayerId();

        //validate resource chosen
        $resTypeArray = explode("_", $id); //id is button_[resourceType]
        $resType = $resTypeArray[1];
        $resCount = $this->getPlayerVariable($resType, $playerID);

        if ($resCount == 0) //shouldn't happen, unless player is cheating input
        {
            throw new feException("Insufficient ".$resType." Resources");
        }

        //update the game state database
        $resCount--;
        $this->setPlayerVariable($resType, $playerID, $resCount);

        //update walls table
        $wallX = $this->getGameStateValue('wallBuildX');
        $wallY = $this->getGameStateValue('wallBuildY');
        $wallRot = $this->getGameStateValue('wallBuildDir');
        $sql = "INSERT INTO walls (wall_type, wall_x, wall_y, wall_rotation) VALUES('".$resType."', '".$wallX."', '".$wallY."', '".$wallRot."')";
        self::DbQuery( $sql );

        //send the build wall info too
        self::notifyAllPlayers( "wallBuilt", clienttranslate( '${player_name} builds a ${wall_name} wall' ), array(
            'player_id' => $playerID,
            'player_name' => self::getActivePlayerName(),
            'wall_name' => $this->resources[$resType],
            'resource_count' => $resCount,
            'resource_id' => $resType,
            'wall_type' => $resType,
            'wall_x' => $wallX,
            'wall_y' => $wallY,
            'wall_rotation' => $wallRot
        ) );

        //if this was a card effect, go back to the action card stage before checking for extra walls etc
        if ($this->getGameStateValue("isCardEffect") == 1) {
            $this->setGameStateValue("isCardEffect", 0);
            $this->gamestate->nextState('wallCardEffectDone');
            return;
        }

        //don't allow extra wall build if no resources left
        if ($this->canBuildWall() && $this->getPlayerVariable('extra_wall_placed', $playerID) == 0 && $this->getPlayerVariable('player_temp', $playerID) >= $this->burnLevel1)
        {
            //self::notifyAllPlayers( "extraWall", clienttranslate( 'DEBUG ${player_name} may play an extra wall' ), array(
            //    'player_name' => self::getActivePlayerName()
            //) );
            $this->setPlayerVariable('extra_wall_placed', $playerID, 1);
            $this->gamestate->nextState( 'extraWallCheck' );
            return;
        }

        $this->gamestate->nextState('wallsDone');
    }
    
    function relocatePickWall($location){
        self::checkAction( "removeWall" );

        //get the valid build locations, check one matches
        $pieces = explode('_', $location);
        $x = $pieces[0];
        $y = $pieces[1];
        $rotation = $pieces[2];

        $buildWallData = $this->argRelocatePickWall();
        $spaces = $buildWallData['connectedEmptySpaces'];
        $directions = $buildWallData['connectedDirections'];

        $found = false;
        for ($i = 0; $i < count($spaces); $i++)
        {
            if ($spaces[$i]['x'] == $x && $spaces[$i]['y'] == $y && $directions[$i] == $rotation)
            {
                $found = true;
            }
        }

        if (!$found)
        {
            throw new feException("Invalid wall location passed");
        }

        //store info on the wall to be removed, then get player to pick new destination (or cancel)
        $newX = $x;
        $newY = $y;
        $this->moveCoords($newX, $newY, $rotation);
        //throw new feException("reg wall build at ".$newX.",".$newY." - ".($rotation+3)%6);
        $this->setGameStateValue('wallBuildX', $newX);
        $this->setGameStateValue('wallBuildY', $newY);
        $this->setGameStateValue('wallBuildDir', ($rotation+3)%6);

        $this->gamestate->nextState( 'wallSelected' );
    }
    
    function relocatePlaceWall($location){
        self::checkAction( "placeWall" );

        //get the valid build locations, check one matches
        $pieces = explode('_', $location);
        $x = $pieces[0];
        $y = $pieces[1];
        $rotation = $pieces[2];

        $buildWallData = $this->argRelocatePlaceWall();
        $spaces = $buildWallData['connectedEmptySpaces'];
        $directions = $buildWallData['connectedDirections'];

        $found = false;
        for ($i = 0; $i < count($spaces); $i++)
        {
            if ($spaces[$i]['x'] == $x && $spaces[$i]['y'] == $y && $directions[$i] == $rotation)
            {
                $found = true;
            }
        }

        if (!$found)
        {
            throw new feException("Invalid wall location passed");
        }

        //move the wall in the db, then on the board
        $oldX = $this->getGameStateValue('wallBuildX');
        $oldY = $this->getGameStateValue('wallBuildY');
        $oldDir = $this->getGameStateValue('wallBuildDir');
        $sql = "select wall_id, wall_type, wall_x, wall_y, wall_rotation from walls where wall_x=".$oldX." and wall_y=".$oldY." and wall_rotation=".$oldDir;
        $wallOld = self::getObjectFromDB($sql);
        if ($wallOld == null)
            throw new feException("wall not found: ".$sql);

        $this->moveCoords($x, $y, $rotation);
        $sql = "update walls set wall_x = ".$x.", wall_y =".$y.", wall_rotation = ".(($rotation+3)%6)." where wall_id = ".$wallOld['wall_id'];
        self::DbQuery( $sql );
        
        $sql = "select wall_id, wall_type, wall_x, wall_y, wall_rotation from walls where wall_id=".$wallOld['wall_id'];
        $wallNew = self::getObjectFromDB($sql);
        self::notifyAllPlayers( "moveWall", clienttranslate( '${wall_name} wall is moved' ), array(
            'player_id'=> self::getActivePlayerId(),
            'wall_name'=> $this->resources[$wallNew['wall_type']],
            'wall_type'=> $wallNew['wall_type'],
            'wallNew_x' => $wallNew['wall_x'],
            'wallNew_y' => $wallNew['wall_y'],
            'wallNew_rotation' => $wallNew['wall_rotation'],
            'wallOld_x' => $wallOld['wall_x'],
            'wallOld_y' => $wallOld['wall_y'],
            'wallOld_rotation' => $wallOld['wall_rotation']
        ) );
        

        $this->gamestate->nextState( 'wallPlaced' );
    }

    function wallDestroyClicked($location){
        self::checkAction( "destroyWall" );

        $pieces = explode('_', $location);
        $x = $pieces[0];
        $y = $pieces[1];
        $rotation = $pieces[2];

        $this->moveCoords($x, $y, $rotation);
        $rotation = ($rotation+3)%6;

        //make sure it exists
        $sql = "select wall_id, wall_type, wall_x, wall_y, wall_rotation from walls where wall_x=".$x." and wall_y=".$y." and wall_rotation=".$rotation;
        $wall = self::getObjectFromDB($sql);

        $sql = "delete from walls where wall_id = ".$wall['wall_id'];
        self::DbQuery( $sql );

        self::notifyAllPlayers( "wallStatus", clienttranslate( '${wall_name} wall is destroyed' ), array(
            'wall_name'=> $this->resources[$wall['wall_type']],
            'wall_x' => $wall['wall_x'],
            'wall_y' => $wall['wall_y'],
            'wall_rotation' => $wall['wall_rotation']
        ) );

        $this->gamestate->nextState( 'wallDestroyed' );
    }

    function wallArrowClicked($location){
        self::checkAction( "buildWall" );

        //get the valid build locations,check one matches. If so, give the player a choice of building materials (next state)
        $pieces = explode('_', $location);
        $x = $pieces[0];
        $y = $pieces[1];
        $rotation = $pieces[2];

        $buildWallData = $this->argPlayWall();
        $spaces = $buildWallData['connectedEmptySpaces'];
        $directions = $buildWallData['connectedDirections'];

        $found = false;
        for ($i = 0; $i < count($spaces); $i++)
        {
            if ($spaces[$i]['x'] == $x && $spaces[$i]['y'] == $y && $directions[$i] == $rotation)
            {
                $found = true;
            }
        }

        if (!$found)
        {
            throw new feException("Invalid wall build location passed");
        }

        //store target wall location. Allow player to pick wall material type
        $newX = $x;
        $newY = $y;
        $this->moveCoords($newX, $newY, $rotation);
        //throw new feException("reg wall build at ".$newX.",".$newY." - ".($rotation+3)%6);
        $this->setGameStateValue('wallBuildX', $newX);
        $this->setGameStateValue('wallBuildY', $newY);
        $this->setGameStateValue('wallBuildDir', ($rotation+3)%6);

        $this->gamestate->nextState( 'wallLocationChosen' );
    }

    function placeTile($x, $y)
    {
        self::checkAction( "playTile" );

        $tile_id = $this->getGameStateValue('activeTile');
        $this->tiles->moveCard( $tile_id, 'board_temp', $this->getLocationAsInt($x, $y, 0));

        //todo - when oppt fails to break a wall, would be nice to see where

        //move the tile in the deck, update the board, and add logic to the 'startup' routine to draw it in place too.

        self::notifyPlayer( self::getActivePlayerId(), "tilePlaced", "", array(
            'tile' => $this->tiles->getCard( $tile_id ),
        ) );

        //throw new feException("adding tile ".$tile_id." at ".$x." ".$y." not implemented");
        $this->gamestate->nextState( 'placeTile' );
    }
    
    function confirmReplaceTile($rotation)
    {
        self::checkAction("confirmTile");

        //validate the chosen rotation
        $validRotations = $this->getActiveTileValidRotations();
        if (!in_array($rotation, $validRotations))
        {
            throw new feException("Invalid rotation");
        }

        //update the database
        $tile_id = $this->getGameStateValue('activeTile');
        $tile = $this->tiles->getCard( $tile_id );
        $x = $this->getXfromLocationInt($tile['location_arg']);
        $y = $this->getYfromLocationInt($tile['location_arg']);
        $startRotation = $this->getRotationFromLocationInt($tile['location_arg']);
        
        $newLocation = $this->getLocationAsInt($x, $y, $rotation);
        
        //get points from ORIGINAL tile
        $allTiles = $this->tiles->getCardsInLocation("board");
        $replacedTile = null;
        foreach($allTiles as $existingTile)
        {
            $tileX = $this->getXfromLocationInt($existingTile['location_arg']);
            $tileY = $this->getYfromLocationInt($existingTile['location_arg']);
            if ($tileX == $x && $tileY == $y && $existingTile['id'] != $tile_id)
            {
                $replacedTile = $existingTile;
                break;
            }
        }
        if ($replacedTile == null)
        {
            throw new feException("Failed to get replaced tile");
        }
        $startLavaPoints = $this->getLavaPoints($replacedTile['type'], $this->getRotationFromLocationInt($replacedTile['location_arg']));  
        
        $endLavaPoints = $this->getLavaPoints($tile['type'], $rotation);
                

        $this->setGameStateValue('activeTile', 0); //clear it
        $this->tiles->moveCard( $tile_id, 'board', $newLocation);//move from board_temp to board      
        
        //move original tile to discard, notify players to destroy it
        $this->tiles->moveCard( $replacedTile['id'], 'discard');
        self::notifyAllPlayers("tileRemove", "", array(
            'tile' => $replacedTile
        ) );

        //send the rotation to other players
        $tile = $this->tiles->getCard( $tile_id );//get updated tile
        self::notifyAllPlayers( "tileConfirmed", clienttranslate( '${player_name} replaces a lava tile' ), array(
            'player_name' => self::getActivePlayerName(),
            'player_id' => self::getActivePlayerId(),
            'startRotation' => $startRotation,
            'tile' => $tile,
        ) );
        
        self::incStat(1, "player_tilesplaced", self::getActivePlayerId());

        //give out cards if new village connections were made
        //where did we add new lava points?
        $this->processVillageConnections($x, $y, $startLavaPoints, $endLavaPoints);
        

        //destroy any walls on the chosen tile.
        $this->destroyWallsAt($x, $y);

        $this->gamestate->nextState( 'tilesDone' );
    }

    function confirmRotateTile($rotation)
    {
        self::checkAction("confirmTile");

        //validate the chosen rotation
        $validRotations = $this->getActiveTileValidRotations();
        if (!in_array($rotation, $validRotations))
        {
            throw new feException("Invalid rotation");
        }

        //update the database
        $tile_id = $this->getGameStateValue('activeTile');
        $tile = $this->tiles->getCard( $tile_id );
        $x = $this->getXfromLocationInt($tile['location_arg']);
        $y = $this->getYfromLocationInt($tile['location_arg']);
        $startRotation = $this->getRotationFromLocationInt($tile['location_arg']);
        $startLavaPoints = $this->getLavaPoints($tile['type'], $startRotation);
        $endLavaPoints = $this->getLavaPoints($tile['type'], $rotation);
        $location = $this->getLocationAsInt($x, $y, $rotation);

        $this->setGameStateValue('activeTile', 0); //clear it
        $this->tiles->moveCard( $tile_id, 'board', $location);//move from board_temp to board

        //send the rotation to other players
        $tile = $this->tiles->getCard( $tile_id );//get updated tile
        self::notifyAllPlayers( "tileConfirmed", clienttranslate( '${player_name} places a lava tile' ), array(
            'player_name' => self::getActivePlayerName(),
            'player_id' => self::getActivePlayerId(),
            'startRotation' => $startRotation,
            'tile' => $tile,
        ) );
        
        //give out cards if new village connections were made
        //where did we add new lava points?
        $this->processVillageConnections($x, $y, $startLavaPoints, $endLavaPoints);
        
        //destroy any walls on the chosen tile
        $this->destroyWallsAt($x, $y);

        //throw new feException("implement this");

        $this->gamestate->nextState( 'tilesDone' );
    }

    function confirmTile($rotation)
    {
        self::checkAction( "confirmTile" );
        $player_id = self::getActivePlayerId();
        $tile_id = $this->getGameStateValue('activeTile');
        $tile = $this->tiles->getCard( $tile_id );
        $isSourceTile = $this->tile_types[$tile["type"]]["isSourceTile"] == "true";
        $x = $this->getXfromLocationInt($tile['location_arg']);
        $y = $this->getYfromLocationInt($tile['location_arg']);
        $location = $this->getLocationAsInt($x, $y, $rotation);
        $walls = $this->getWalls();

        $validRotations = $this->getActiveTileValidRotations();
        if (!in_array($rotation, $validRotations)) {
            throw new feException("Invalid rotation");
        }

        //if there are any lava connections unbocked, then any walls get destroyed
        $north = $this->getConnectionState($x, $y +1, $this->dirN);
        $northEast = $this->getConnectionState($x - 1, $y +1, $this->dirNE);
        $southEast = $this->getConnectionState($x-1, $y, $this->dirSE);
        $south = $this->getConnectionState($x, $y - 1, $this->dirS);
        $southWest = $this->getConnectionState($x + 1, $y - 1, $this->dirSW);
        $northWest = $this->getConnectionState($x + 1, $y, $this->dirNW);

        $lavaHasPath = false;
        $connectedWalls = array();
        if ($north == $this->tileStateLava) {
            $hasWall = false;
            foreach ($walls as $wall) {
                if ($wall['wall_x'] == $x && $wall['wall_y'] == $y+1 && $wall['wall_rotation'] == $this->dirN )
                {
                    $hasWall = true;
                    $connectedWalls[] = $wall;
                }
            }
            if (!$hasWall) {
                $lavaHasPath = true;
            }
        }

        if ($northEast == $this->tileStateLava) {
            $hasWall = false;
            foreach ($walls as $wall) {
                if ($wall['wall_x'] == $x-1 && $wall['wall_y'] == $y+1 && $wall['wall_rotation'] == $this->dirNE )
                {
                    $hasWall = true;
                    $connectedWalls[] = $wall;
                }
            }
            if (!$hasWall) {
                $lavaHasPath = true;
            }
        }

        if ($southEast == $this->tileStateLava) {
            $hasWall = false;
            foreach ($walls as $wall) {
                if ($wall['wall_x'] == $x-1 && $wall['wall_y'] == $y && $wall['wall_rotation'] == $this->dirSE )
                {
                    $hasWall = true;
                    $connectedWalls[] = $wall;
                }
            }
            if (!$hasWall) {
                $lavaHasPath = true;
            }
        }

        if ($south == $this->tileStateLava) {
            $hasWall = false;
            foreach ($walls as $wall) {
                if ($wall['wall_x'] == $x && $wall['wall_y'] == $y-1 && $wall['wall_rotation'] == $this->dirS )
                {
                    $hasWall = true;
                    $connectedWalls[] = $wall;
                }
            }
            if (!$hasWall) {
                $lavaHasPath = true;
            }
        }

        if ($southWest == $this->tileStateLava) {
            $hasWall = false;
            foreach ($walls as $wall) {
                if ($wall['wall_x'] == $x+1 && $wall['wall_y'] == $y-1 && $wall['wall_rotation'] == $this->dirSW )
                {
                    $hasWall = true;
                    $connectedWalls[] = $wall;
                }
            }
            if (!$hasWall) {
                $lavaHasPath = true;
            }
        }

        if ($northWest == $this->tileStateLava) {
            $hasWall = false;
            foreach ($walls as $wall) {
                if ($wall['wall_x'] == $x+1 && $wall['wall_y'] == $y && $wall['wall_rotation'] == $this->dirNW )
                {
                    $hasWall = true;
                    $connectedWalls[] = $wall;
                }
            }
            if (!$hasWall) {
                $lavaHasPath = true;
            }
        }

        //if the lava has an alternative path, or if this is a source tile, all walls get burnt.
        if ($lavaHasPath || $isSourceTile) {
            //all connected walls get destroyed
            foreach ($connectedWalls as $wall){
                //wall destroyed
                $sql = "delete from walls where wall_id = ".$wall['wall_id'];
                self::DbQuery( $sql );

                self::notifyAllPlayers( "wallStatus", clienttranslate( '${wall_name} wall is destroyed by the lava flow' ), array(
                    'wall_name' => $this->resources[$wall['wall_type']],
                    'wall_x' => $wall['wall_x'],
                    'wall_y' => $wall['wall_y'],
                    'wall_rotation' => $wall['wall_rotation']
                ) );
            }
        }
        else if (count($connectedWalls) > 0) {
            $burnt = false;
            foreach($connectedWalls as $wall) {
                $burnt = $burnt || $this->checkWallDestroyed($wall, false);
            }

            //all walls get rolled against. If ANY fail, they all get destroyed
            if (!$burnt) {
                //walls were successful!

                $active_tile_id = $this->getGameStateValue('activeTile');
                $tile = $this->tiles->getCard( $active_tile_id );
                $this->tiles->moveCard( $tile_id, 'hand', $player_id);//move from board_temp to board
                $sql = "INSERT INTO failedPlacements (placement_x, placement_y) VALUES('".$x."', '".$y."')";
                self::DbQuery( $sql );

                self::notifyAllPlayers( "tileFailed", clienttranslate( '${player_name} must choose a different location for the tile' ), array(
                    'player_id'=>self::getActivePlayerId(),
                    'player_name' => $this->getActivePlayerName(),
                    'activePlayerPos' => $this->getPlayerVariable('player_position', self::getActivePlayerId()),
                    'tile' => $tile
                ) );

                $this->gamestate->nextState( 'wallsDefended' );
                return;
            }
        }

        //placement was okay? move the tile. Clear any fails
        $this->setGameStateValue('activeTile', 0); //clear it
        $this->tiles->moveCard( $tile_id, 'board', $location);//move from board_temp to board
        $sql = "delete from failedPlacements";
        self::DbQuery( $sql );

        $tile = $this->tiles->getCard( $tile_id );
        
        //move the tile in the deck, update the board, and add logic to the 'startup' routine to draw it in place too.
        self::notifyAllPlayers( "tileConfirmed", clienttranslate( '${player_name} places a lava tile' ), array(
            'player_name' => self::getActivePlayerName(),
            'player_id' => $player_id,
            'tile' => $tile,
        ) );
        
        self::incStat(1, "player_tilesplaced", self::getActivePlayerId());
        
        self::notifyAllPlayers("resourceCount", "", array(
            'player_id' => self::getActivePlayerId(),
            'resourceType' => "source",
            'resourceCount' => $this->tiles->countCardsInLocation('hand', self::getActivePlayerId())
            ));

        //did the player collect any resources from this space?
        foreach($this->board_resources as $resource)
        {
            if ($x == $resource['x'] && $y == $resource['y'])
            {
                $resType = $resource['res'];
                $resource_name = $this->resources[$resType];
                $count = $this->getPlayerVariable($resType, $player_id);
                $count++;
                $this->setPlayerVariable($resType, $player_id, $count);

                self::notifyAllPlayers( "wallGain", clienttranslate( '${player_name} gains 1 ${resource_name} wall' ), array(
                    'player_id' => $player_id,
                    'player_name' => self::getActivePlayerName(),
                    'resource_name' => $resource_name,
                    'resource_id' => $resType,
                    'tileID' => $tile_id,
                    'x' =>$x,
                    'y'=>$y
                ) );
                
                self::notifyAllPlayers("resourceCount", "", array(
                    'player_id' => self::getActivePlayerId(),
                    'resourceType' => $resType,
                    'resourceCount' => $count
        ));
            }
        }

        //Gain cards when connecting new lava flow to ANY village (even unpopulated)
        //in order to check this, get the dangerTiles from EVERY position.
        //any which match the current tile and it's lava points will trigger a new path
        $lavapoints = $this->getLavaPoints($tile['type'], $rotation);
        
        $this->processVillageConnections($x, $y, array(), $lavapoints);

        
        //if this is an eruption tile, every other player advances 30 degress
        if ($this->tile_types[$tile["type"]]["isSourceTile"] == "true")
        {
            $damage = 30;
            $players = self::loadPlayersBasicInfos();
            foreach($players as $player)
            {
                if ($player["player_id"] != $player_id)
                {
                    $this->increasePlayerTemp($player["player_id"], $damage);
                }
            }            

            $this->gamestate->nextState( 'sourceTileDone' );
            return;
        }

        if ($this->getPlayerVariable('extra_tile_placed', $player_id) == 0 && $this->getPlayerVariable('player_temp', $player_id) >= $this->burnLevel3)
        {
            $this->setPlayerVariable('extra_tile_placed', $player_id, 1);
            $this->gamestate->nextState( 'extraTileCheck' );
            return;
        }        

        $this->gamestate->nextState( 'tilesDone' );
    }

    /*
    
    Example:

    function playCard( $card_id )
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'playCard' ); 
        
        $player_id = self::getActivePlayerId();
        
        // Add your game logic to play a card there 
        ...
        
        // Notify all players about the card played
        self::notifyAllPlayers( "cardPlayed", clienttranslate( '${player_name} played ${card_name}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_name,
            'card_id' => $card_id
        ) );
          
    }
    
    */

    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    function argDestroyWall()
    {
        $walls = $this->getWalls();
        $wallArrows = array();
        foreach($walls as $wall)
        {
            $newX = $wall['wall_x'];
            $newY = $wall['wall_y'];
            $this->moveCoords($newX, $newY, $wall['wall_rotation']);
            $wallArrows[] = array('x' => $newX, 'y' => $newY, 'dir' => ($wall['wall_rotation']+3)%6);
        }

        return array("wallArrows" => $wallArrows);
    }

    function argPlayTile()
    {
        //return a list of the valid locations that can be clicked
        $emptySpaces = $this->board_spaces;
        $connectedEmptySpaces = array();

        //remove all spaces with tiles on
        //send a list of spaces which are connected to something, for 'IsConnected' error
        for ($i = 0; $i< count($emptySpaces); $i++)
        {
            //extract this function??
            if ($this->isSpaceUsed($emptySpaces[$i]["x"], $emptySpaces[$i]["y"]))
            {
                array_splice($emptySpaces, $i, 1);
                $i--;
            }
            else if ($this-> isSpaceConnected($emptySpaces[$i]["x"], $emptySpaces[$i]["y"]))
            {
                //add to valid space list
                $connectedEmptySpaces[] = $emptySpaces[$i];
            }
        }

        $failedPlacements = self::getObjectListFromDB( "SELECT placement_id, placement_x, placement_y FROM failedPlacements" );

        return array("failedPlacements"=> $failedPlacements, "spaces" => $emptySpaces, "connectedEmptySpaces" => $connectedEmptySpaces);
    }

    function argPlaySourceTile()
    {
        //return a list of the valid locations that can be clicked
        $emptySpaces = $this->board_spaces;
        $connectedEmptySpaces = array();

        //remove all spaces with tiles on
        //send a list of spaces which are connected to something, for 'IsConnected' error
        for ($i = 0; $i< count($emptySpaces); $i++)
        {
            //extract this function??
            if ($this->isSpaceUsed($emptySpaces[$i]["x"], $emptySpaces[$i]["y"]))
            {
                array_splice($emptySpaces, $i, 1);
                $i--;
            }
            else //don't card if it's connected for lava source tiles
            {
                //add to valid space list?
                $connectedEmptySpaces[] = $emptySpaces[$i];
            }
        }

        $active_tile_id = $this->getGameStateValue('activeTile');
        $tile = $this->tiles->getCard( $active_tile_id );

        return array("tile" => $tile, "spaces" => $emptySpaces, "connectedEmptySpaces" => $connectedEmptySpaces);
    }

    function argWallMaterial(){
        $straw = $this->getPlayerVariable('straw', self::getActivePlayerId());
        $wood = $this->getPlayerVariable('wood', self::getActivePlayerId());
        $stone = $this->getPlayerVariable('stone', self::getActivePlayerId());

        return array("straw" => $straw, "wood"=> $wood, "stone" => $stone);
    }

    function argActionCards(){
        $tilesExist = $this->tiles->countCardsInLocation("board") > 0;
        $canAddTile = $this->tiles->countCardsInLocation("deck") > 0;
        return array("canBuildWall" => $this->canBuildWall(), "tilesExist"=>$tilesExist, "canAddTile"=>$canAddTile);
    }
    
    function argRelocatePlaceWall()
    {
        //return a list of the valid locations to place a wall. These are:
        //1)empty locations in the player's village
        
        //To more easily/efficiently do this, I'm actually going to return the spaces I want to display the arror in.
        
        $connectedEmptySpaces = array();
        $connectedDirections = array();

        $playerPosition = $this->getPlayerVariable('player_position', self::getActivePlayerId());
        $dangerTiles = $this->getDangerTiles($playerPosition);
        $walls = $this->getWalls();
                
        //every home space is valid so long as it has a wall
        
        foreach ($dangerTiles as $tile)
        {           
            $x = $tile[0];
            $y = $tile[1];
            $dir = $tile[2];

            $this->moveCoords($x, $y, $dir);
            $wallFound = false;
            foreach ($walls as $wall)
            {
                if ($wall['wall_x'] == $x && $wall['wall_y'] == $y && ($dir+3)%6 == $wall['wall_rotation'])
                {
                    $wallFound = true;                    
                }
            }
            if (!$wallFound) {
                $connectedEmptySpaces[] = array('x' => $tile[0], 'y' => $tile[1]);
                $connectedDirections[] = $dir;
            }
        }

        return array("connectedEmptySpaces" => $connectedEmptySpaces, "connectedDirections" => $connectedDirections);
    }
        
    
    function argRelocatePickWall()
    {
        //return a list of the valid locations to pick up a wall. These are:
        //1)any walls in the player's village
        
        //To more easily/efficiently do this, I'm actually going to return the spaces I want to display the arror in.
        
        $connectedEmptySpaces = array();
        $connectedDirections = array();

        $playerPosition = $this->getPlayerVariable('player_position', self::getActivePlayerId());
        $dangerTiles = $this->getDangerTiles($playerPosition);
        $walls = $this->getWalls();
                
        //every home space is valid so long as it has a wall
        
        foreach ($dangerTiles as $tile)
        {           
            $x = $tile[0];
            $y = $tile[1];
            $dir = $tile[2];

            $this->moveCoords($x, $y, $dir);
            foreach ($walls as $wall)
            {
                if ($wall['wall_x'] == $x && $wall['wall_y'] == $y && ($dir+3)%6 == $wall['wall_rotation'])
                {
                    $connectedEmptySpaces[] = array('x' => $tile[0], 'y' => $tile[1]);
                    $connectedDirections[] = $dir;
                }
            }
        }

        return array("connectedEmptySpaces" => $connectedEmptySpaces, "connectedDirections" => $connectedDirections);
    }

    function argPlayWall()
    {
        //return a list of the valid locations to play a wall. These are:
        //1)lava tiles open exits which do NOT touch the edge of the board (including villages)
        //2)any edge in the active player's village
        //obviously there must also not currently be a wall present in the space CHECK IF REPLACE?

        //To more easily/efficiently do this, I'm actually going to return the spaces I want to display the arror in, 
        //i.e, the empty space and direction which is connected to the lava tile. This is similar to the code for valid tile placements

        $emptySpaces = $this->board_spaces;
        $connectedEmptySpaces = array();
        $connectedDirections = array();

        //remove all spaces with tiles on
        for ($i = 0; $i< count($emptySpaces); $i++)
        {
            $x = $emptySpaces[$i]["x"];
            $y = $emptySpaces[$i]["y"];
            if ($this->isSpaceUsed($x, $y))
            {
                array_splice($emptySpaces, $i, 1);
                $i--;
            }
            else if ($this-> isSpaceConnected($x, $y))
            {
                //where is it connected by lava? we want all the directions
                $north = $this->getConnectionState($x, $y +1, $this->dirN);
                $northEast = $this->getConnectionState($x - 1, $y +1, $this->dirNE);
                $southEast = $this->getConnectionState($x-1, $y, $this->dirSE);
                $south = $this->getConnectionState($x, $y - 1, $this->dirS);
                $southWest = $this->getConnectionState($x + 1, $y - 1, $this->dirSW);
                $northWest = $this->getConnectionState($x + 1, $y, $this->dirNW);
                
                //add to results array
                if ($north == $this->tileStateLava)
                {
                    $connectedEmptySpaces[] = $emptySpaces[$i];
                    $connectedDirections[] = $this->dirS;
                }
                if ($northEast == $this->tileStateLava)
                {
                    $connectedEmptySpaces[] = $emptySpaces[$i];
                    $connectedDirections[] = $this->dirSW;
                }
                if ($southEast == $this->tileStateLava)
                {
                    $connectedEmptySpaces[] = $emptySpaces[$i];
                    $connectedDirections[] = $this->dirNW;
                }
                if ($south == $this->tileStateLava)
                {
                    $connectedEmptySpaces[] = $emptySpaces[$i];
                    $connectedDirections[] = $this->dirN;
                }
                if ($southWest == $this->tileStateLava)
                {
                    $connectedEmptySpaces[] = $emptySpaces[$i];
                    $connectedDirections[] = $this->dirNE;
                }
                if ($northWest == $this->tileStateLava)
                {
                    $connectedEmptySpaces[] = $emptySpaces[$i];
                    $connectedDirections[] = $this->dirSE;
                }
            }
        }

        //add home base thingies

        $playerPosition = $this->getPlayerVariable('player_position', self::getActivePlayerId());
        $dangerTiles = $this->getDangerTiles($playerPosition);
        //every home space is valid so long as it has no wall, even if lava is already connected
        foreach ($dangerTiles as $tile)
        {
            $connectedEmptySpaces[] = array('x' => $tile[0], 'y' => $tile[1]);
            $connectedDirections[] = $tile[2];
        }

        //exclude places where existing walls are (tricky, since I'm returning the connected ones...)
        $walls = $this->getWalls();

        for ($i = 0; $i< count($connectedEmptySpaces); $i++)
        {
            $x = $connectedEmptySpaces[$i]['x'];
            $y = $connectedEmptySpaces[$i]['y'];
            $dir = $connectedDirections[$i];

            $this->moveCoords($x, $y, $dir);
            foreach ($walls as $wall)
            {
                if ($wall['wall_x'] == $x && $wall['wall_y'] == $y && ($dir+3)%6 == $wall['wall_rotation'])
                {
                    array_splice($connectedEmptySpaces, $i, 1);
                    array_splice($connectedDirections, $i, 1);
                    $i--;
                }
            }
        }

        $isCardEffect = $this->getGameStateValue("isCardEffect");

        return array("canBuild"=> $this->canBuildWall(), "isCardEffect" => $isCardEffect, "connectedEmptySpaces" => $connectedEmptySpaces, "connectedDirections" => $connectedDirections);
    }

    function argConfirmTile()
    {
        $result = $this->argPlayTile();

        $result['validRotations'] = $this->getActiveTileValidRotations();

        return $result;
    }

    function argConfirmSourceTile()
    {
        $result = $this->argPlaySourceTile();

        $result['validRotations'] = $this->getActiveTileValidRotations();

        return $result;
    }

    function argConfirmRotateTile()
    {
        $result = $this->argRotateTile();

        $result['validRotations'] = $this->getActiveTileValidRotations();

        $active_tile_id = $this->getGameStateValue('activeTile');
        $result['tile'] = $this->tiles->getCard( $active_tile_id );
        $result['rotation'] = $this->getRotationFromLocationInt($result['tile']['location_arg']);

        return $result;
    }
    
    function argConfirmReplaceTile()
    {
        $result = $this->argQuake();

        $result['validRotations'] = $this->getActiveTileValidRotations();

        $active_tile_id = $this->getGameStateValue('activeTile');
        $result['tile'] = $this->tiles->getCard( $active_tile_id );
        $result['rotation'] = $this->getRotationFromLocationInt($result['tile']['location_arg']);

        return $result;
    }

    function argQuake()
    {
        //return all the tiles on the board where this tile has a valid placement
        $boardTiles = $this->tiles->getCardsInLocation("board");
        
        $allSpaces = array();
        $replaceableTiles = array();
        
        $active_tile_id = $this->getGameStateValue('activeTile');
        $tile = $this->tiles->getCard( $active_tile_id );
        
        $args = $this->argDestroyTile();
        $nodeConnections = $args["nodeConnections"];
                
        foreach($boardTiles as $existingTile)
        {
            $x = $this->getXfromLocationInt($existingTile["location_arg"]);
            $y = $this->getYfromLocationInt($existingTile["location_arg"]);
            
            //if the tile being replaced is an eruption tile, we must do pathfinding
            //if it cannot be replaced, skip and go to next tile
            //still add it to the spaces list so players get a message
            $isSourceTile = $this->tile_types[$existingTile["type"]]["isSourceTile"] == "true";
            
            if ($isSourceTile)
            {
                if (!$this->isReplaceable($nodeConnections, $existingTile['id']))
                {
                    $allSpaces[] = array('id'=>$existingTile['id'], 'x'=>$x, 'y'=>$y);
                    continue;
                }
            }
            
            $validRotations = $this->getValidRotations($tile, $x, $y, false);
            if (count($validRotations) > 0) {
                $replaceableTiles[] = array('id'=>$existingTile['id'], 'x'=>$x, 'y'=>$y);
            }
            $allSpaces[] = array('id'=>$existingTile['id'], 'x'=>$x, 'y'=>$y);
        }
        return array("allTiles"=> $allSpaces, "validTiles"=>$replaceableTiles);
    }

    function argRotateTile(){
        $boardTiles = $this->tiles->getCardsInLocation("board");
        $allSpaces = array();
        $rotatableSpaces = array();
        foreach($boardTiles as $tile)
        {
            $tileID = $tile['id'];
            $x = $this->getXfromLocationInt($tile['location_arg']);
            $y = $this->getYfromLocationInt($tile['location_arg']);

            $isSourceTile = $this->tile_types[$tile["type"]]["isSourceTile"] == "true";

            $allSpaces[] = array('id'=>$tileID, 'x'=>$x, 'y'=>$y);

            //tile can be rotated if there exists more than one valid rotation for it
            $validRotations = $this->getValidRotations($tile, $x, $y, $isSourceTile);
            if (count($validRotations) > 1) {
                $rotatableSpaces[] = array('id'=>$tileID, 'x'=>$x, 'y'=>$y);
            }
        }

        return array("allTiles"=>$allSpaces, "validTiles"=>$rotatableSpaces);
    }

    //return a list of all tiles on the board, can we get valid ones also?
    function argDestroyTile(){
        $boardTiles = $this->tiles->getCardsInLocation("board");
        $allSpaces = array(); //array: -> tileID, x, y, lavaPoints[], isConnectedToSource
        $removableSpaces = array();
        $network = array(); //2d array: x,y -> lavapoints[] and tileID
        $newNetwork = array(); //network of nodes: nodeID -> isConnectedToSource, connectedNodes[]
        foreach($boardTiles as $tile)
        {
            $tileID = $tile['id'];
            $x = $this->getXfromLocationInt($tile['location_arg']);
            $y = $this->getYfromLocationInt($tile['location_arg']);
            $rotation = $this->getRotationFromLocationInt($tile['location_arg']);
            $lavapoints = $this->getLavaPoints($tile['type'], $rotation);

            //is connected if its a source tile, or it is one of the middle sources
            $isConnectedToSource = $this->tile_types[$tile["type"]]["isSourceTile"] == "true";
            if (($x == 4 && $y == 4) || ($x == 6 && $y == 3) || ($x==3 && $y==6))
            {
                $isConnectedToSource = true;
            }
            if (($x == 4 && $y == 7) || ($x == 7 && $y == 4) || ($x==6 && $y==6))
            {
                $isConnectedToSource = true;
            }

            $allSpaces[] = array('id'=>$tileID, 'x'=>$x, 'y'=>$y, 'lavapoints'=>$lavapoints, 'isConnected'=>$isConnectedToSource);
            $network[$x][$y] = array('lavapoints'=>$lavapoints, 'id'=>$tileID);
        }

        //we have an array of all the tiles on the board and their connections
        //create a network of connected nodes 'newnetwork'
        for($i=0;$i<count($allSpaces);$i++)
        {
            $space = $allSpaces[$i];
            $tileID = $space['id'];
            $x = $space['x'];
            $y = $space['y'];
            $lavapoints = $space['lavapoints'];

            $newNetwork[$tileID] = array("isConnectedToSource"=>$space['isConnected'], "connectedNodes"=>array());
            //vertical node connections
            if (isset($network[$x][$y-1]) && in_array(0, $lavapoints) && in_array(3, $network[$x][$y-1]["lavapoints"]))
            {
                $newNetwork[$tileID]["connectedNodes"][] = $network[$x][$y-1]['id'];
            }
            if (isset($network[$x][$y+1]) && in_array(3, $lavapoints) && in_array(0, $network[$x][$y+1]["lavapoints"]))
            {
                $newNetwork[$tileID]["connectedNodes"][] = $network[$x][$y+1]['id'];
            }

            //x component diag
            if (isset($network[$x+1][$y]) && in_array(2, $lavapoints) && in_array(5, $network[$x+1][$y]["lavapoints"]))
            {
                $newNetwork[$tileID]["connectedNodes"][] = $network[$x+1][$y]['id'];
            }
            if (isset($network[$x-1][$y]) && in_array(5, $lavapoints) && in_array(2, $network[$x-1][$y]["lavapoints"]))
            {
                $newNetwork[$tileID]["connectedNodes"][] = $network[$x-1][$y]['id'];
            }

            //other diag
            if (isset($network[$x+1][$y-1]) && in_array(1, $lavapoints) && in_array(4, $network[$x+1][$y-1]["lavapoints"]))
            {
                $newNetwork[$tileID]["connectedNodes"][] = $network[$x+1][$y-1]['id'];
            }
            if (isset($network[$x-1][$y+1]) && in_array(4, $lavapoints) && in_array(1, $network[$x-1][$y+1]["lavapoints"]))
            {
                $newNetwork[$tileID]["connectedNodes"][] = $network[$x-1][$y+1]['id'];
            }
        }

        foreach($allSpaces as $space)
        {
            if ($this->isRemovable($newNetwork, $space['id']))
            {
                $removableSpaces[] = array('id'=>$space['id'], 'x'=>$space['x'], 'y'=>$space['y']);
            }
        }

        return array("allTiles"=>$allSpaces, "validTiles"=>$removableSpaces, "nodeConnections" =>$newNetwork );
    }

    function isRemovable($nodeNetwork, $removalNodeID)
    {
        //a node is removable if I can remove it and still trace all it's connected nodes to a source node
        $disconnectedNodes = $nodeNetwork[$removalNodeID]["connectedNodes"];

        unset($nodeNetwork[$removalNodeID]);

        $searchList = array();

        //here I will run a search. The terminating condition is:
        //no new nodes got connected
        foreach ($nodeNetwork as $node_id=>$node)
        {
            if ($node_id != $removalNodeID && $node["isConnectedToSource"])
            {
                $searchList[] = $node_id;
            }
        }

        //breadth first
        while(count($searchList)> 0)
        {
            //get the top element in the searchlist
            //get all the connected nodes
            //any which are NOT already connected, add to the search list
            $searchNode = array_shift ($searchList);
            $connectedNodes = $nodeNetwork[$searchNode]["connectedNodes"];

            //var_dump($connectedNodes);
            //die('ok');

            foreach ($connectedNodes as $node) //for every node connected to the search node
            {
                if ($node != $removalNodeID && !$nodeNetwork[$node]["isConnectedToSource"]) //if it isn't connected
                {
                    $nodeNetwork[$node]["isConnectedToSource"] = true; //it is now
                    $searchList[] = $node; // and it gets added to the search list
                }
            }
        }

        foreach ($disconnectedNodes as $node)
        {
            if (!$nodeNetwork[$node]["isConnectedToSource"])
            {
                return false;
            }
        }
        return true;
    }
    
    function isReplaceable($nodeNetwork, $replacedNodeID)
    {
        //a node is replaceable if I can set it's isConnectedToSource flag to false
        //and still trace it to a source node
        $nodeNetwork[$replacedNodeID]["isConnectedToSource"] = false;

        $searchList = array();

        //here I will run a search. The terminating condition is:
        //no new nodes got connected. I start the search with every node connected
        //to the source and attempt to fully serch their connection trees.
        foreach ($nodeNetwork as $node_id=>$node)
        {
            if ($node["isConnectedToSource"])
            {
                $searchList[] = $node_id;
            }
        }

        //breadth first
        while(count($searchList)> 0)
        {
            //get the top element in the searchlist
            //get all the connected nodes
            //any which are NOT already connected, add to the search list
            $searchNode = array_shift ($searchList);
            $connectedNodes = $nodeNetwork[$searchNode]["connectedNodes"];

            foreach ($connectedNodes as $node) //for every node connected to the search node
            {
                if (!$nodeNetwork[$node]["isConnectedToSource"]) //if it isn't connected
                {
                    $nodeNetwork[$node]["isConnectedToSource"] = true; //it is now
                    $searchList[] = $node; // and it gets added to the search list
                }
            }
        }

        //did our original node get connected?
        if (!$nodeNetwork[$replacedNodeID]["isConnectedToSource"])
        {
            return false;       
        }
        return true;
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */
    
    /*
    
    Example for game state "MyGameState":

    function stMyGameState()
    {
        // Do some stuff ...
        
        // (very often) go to another gamestate
        $this->gamestate->nextState( 'some_gamestate_transition' );
    }    
    */

    function stCardEffect(){

        $cardID = $this->getGameStateValue("cardPlayedID");
        $card = $this->cards->getCard($cardID);
        $player_id = self::getActivePlayerId();
        $cardType = $this->card_types[$card['type']];
        
        $this->giveExtraTime( $player_id );

        //discard the card
        $this->cards->moveCard($card["id"], 'discard');

        self::notifyAllPlayers( "cardDiscard", clienttranslate( '${player_name} plays ${card_name}' ), array(
            'player_id' => self::getActivePlayerId(),
            'player_name' => self::getActivePlayerName(),
            'card_id' => $card['id'],
            'card_name' => $cardType["name"],
            'card_type' => $card["type"],
        ) );
        
        self::incStat(1, "player_cardsplayed", self::getActivePlayerId());
        
        self::notifyAllPlayers("resourceCount", "", array(
            'player_id' => self::getActivePlayerId(),
            'resourceType' => "card",
            'resourceCount' => $this->cards->countCardsInLocation('hand', self::getActivePlayerId())
        ));
        
        $this->setGameStateValue("lastCardDiscarded", $card['id']);
        
        //throw new feException($cardType['id'])
        switch ($cardType['type_id']) {
            case 0://Lava Flow - place extra tile
                if (!$this->tiles->countCardsInLocation("deck") > 0) {
                    throw new feException("No tiles remaining");
                }
                $this->gamestate->nextstate('playTile');
                return;
                break;
            case 1: //Reinforce - build extra wall
                if (!$this->canBuildWall()) {
                    throw new feException("Not enough resources to build a wall");
                }
                $this->setGameStateValue("isCardEffect", 1);
                $this->gamestate->nextstate('playWall');
                return;
                break;
            case 2: //Relocate - rearrange village walls
                $this->gamestate->nextState('relocate');
                return;
                break;
            case 3://Aftershock - rotate a tile (destroys walls)
                if (!$this->tiles->countCardsInLocation("board") > 0) {
                    throw new feException("No tiles on the board");
                }
                $this->gamestate->nextState('aftershock');
                return;
                break;
            case 4://Rain - reduce 30 degrees
                $this->decreasePlayerTemp($player_id, 30);
                $this->gamestate->nextState('cardFinished');
                return;
                break;
            case 5://Sinkhole - destroy a tile (lava must still connect)
                if (!$this->tiles->countCardsInLocation("board") > 0) {
                    throw new feException("No tiles on the board");
                }
                $this->gamestate->nextState('sinkhole');
                return;
                break;
            case 6://Volcanic Bomb - destroy any wall on the board
                $this->gamestate->nextState('volcanicBomb');
                return;
                break;
            case 7://Quake - replace a lava tile
                if (!$this->tiles->countCardsInLocation("board") > 0) {
                    throw new feException("No tiles on the board");
                }
                $this->gamestate->nextState('quake');
                return;
                break;
        }

        throw new feException("unhandled card effect, should not happen");
    }

    function stAssessDamage()
    {
        self::notifyAllPlayers( "stAssessDamage", clienttranslate( '${player_name} assesses damage' ), array(
                'player_name' => self::getActivePlayerName()
            ) );

        $undefendedFlows = 0;

        //damage will depend on player position & therefore incoming connections. Walls are not yet accounted for.
        $activePlayerPosition = $this->getPlayerVariable('player_position', self::getActivePlayerId());

        $dangerTiles = $this->getDangerTiles($activePlayerPosition);
        $walls = $this->getWalls();
        $damagedWalls = array();

        foreach($dangerTiles as $tile)
        {
            if ($this->getConnectionState($tile[0], $tile[1], $tile[2]) == $this->tileStateLava)
            {
                //this part of your village is connected to lava.
                //check for and report all successful or failed walls
                //rand ( int $min , int $max )
                //if the wall fails, 10 degrees. If it holds, 0 degrees
                $blocked = false;
                $newX = $tile[0];
                $newY = $tile[1];
                $this->moveCoords($newX, $newY, $tile[2]);

                foreach($walls as $wall)
                {
                    if ($newX == $wall['wall_x'] && $newY == $wall['wall_y'] && ($tile[2]+3)%6 == $wall['wall_rotation'])
                    {
                        $blocked = true;
                        $damagedWalls[] = $wall;
                    }
                }

                if (!$blocked) {
                    $undefendedFlows ++;
                }
            }
        }

        //report all unblocked flows first
        self::notifyAllPlayers( "damageReport", clienttranslate( '${player_name} village has ${num_flows} undefended lava flows' ), array(
            'player_name' => self::getActivePlayerName(),
            'num_flows' => $undefendedFlows
        ) );
        $this->increasePlayerTemp(self::getActivePlayerId(), 20*$undefendedFlows);

        foreach($damagedWalls as $wall)
        {
            $wallBurnt = $this->checkWallDestroyed($wall, true);
            if ($wallBurnt)
            {
                $this->increasePlayerTemp(self::getActivePlayerId(), 10);
            }
        }

        $existingDamage = $this->getPlayerVariable('player_temp', self::getActivePlayerId());
        //eruption tiles trigger at 50, 120, 200
        //draw the tile to the player's 'hand', then we will play up to 1 tile from a players hand after the damage phase
        if ($existingDamage >= $this->burnLevel1 && $this->tiles->countCardsInLocation('source') == 3)
        {
            $this->tiles->pickCard( "source", self::getActivePlayerId());
            self::notifyAllPlayers( "increaseBurn", clienttranslate( '${player_name} passes the first burn level and must place a new lava source tile' ), array(
                'player_id' => self::getActivePlayerId(),
                'player_name' => self::getActivePlayerName(),
            ) );
            
            self::notifyAllPlayers("resourceCount", "", array(
            'player_id' => self::getActivePlayerId(),
            'resourceType' => "source",
            'resourceCount' => $this->tiles->countCardsInLocation('hand', self::getActivePlayerId())
            ));
        }
        if ($existingDamage >= $this->burnLevel2 && $this->tiles->countCardsInLocation('source') == 2)
        {
            $this->tiles->pickCard( "source", self::getActivePlayerId());
            self::notifyAllPlayers( "increaseBurn", clienttranslate( '${player_name} passes the second burn level and must place a new lava source tile' ), array(
                'player_id' => self::getActivePlayerId(),
                'player_name' => self::getActivePlayerName(),
            ) );
                       
            self::notifyAllPlayers("resourceCount", "", array(
            'player_id' => self::getActivePlayerId(),
            'resourceType' => "source",
            'resourceCount' => $this->tiles->countCardsInLocation('hand', self::getActivePlayerId())
            ));
        }
        if ($existingDamage >= $this->burnLevel3 && $this->tiles->countCardsInLocation('source') == 1)
        {
            $this->tiles->pickCard( "source", self::getActivePlayerId());
            self::notifyAllPlayers( "increaseBurn", clienttranslate( '${player_name} passes the third burn level and must place a new lava source tile' ), array(
                'player_id' => self::getActivePlayerId(),
                'player_name' => self::getActivePlayerName(),
            ) );
            
            self::notifyAllPlayers("resourceCount", "", array(
            'player_id' => self::getActivePlayerId(),
            'resourceType' => "source",
            'resourceCount' => $this->tiles->countCardsInLocation('hand', self::getActivePlayerId())
            ));
        }

        if ($existingDamage >= $this->burnLevel2)
        {
            self::notifyAllPlayers( "drawsCard", clienttranslate( '${player_name} is past the second burn level and draws an extra card' ), array(
                'player_name' => self::getActivePlayerName(),
            ) );

            $this->drawCard(self::getActivePlayerId());
        }

        //if this player has 1+ source tiles, he must play one
        $count = $this->tiles->countCardsInLocation('hand', self::getActivePlayerId());
        if ($count > 0)
        {
            //check if there is a valid placement
            $tile = $this->tiles->pickCard( "hand", self::getActivePlayerId());
            $this->setGameStateValue('activeTile', $tile['id']);
            
            $placements = $this->argPlaySourceTile();
                       
            $spaces = $placements["spaces"];
            $isPlayable = false;
            
            for ($i=0; $i<count($spaces) && !$isPlayable; $i++)
            {
                $space = $spaces[$i];

                $validRotations = $this->getValidRotations($tile, $space['x'], $space['y'], true);
                if (count($validRotations) > 0) {
                    $isPlayable = true;
                }
            }

            //var_dump($placements);
            //die('ok');
            if (!$isPlayable)
            {
                self::notifyAllPlayers( "noPlaceBurn", clienttranslate( '${player_name} has ${count} eruption tiles to place, but cannot place any. One is discarded' ), array(
                    'player_name' => self::getActivePlayerName(),
                    'count' => $count,
                ) );
                
                $this->tiles->moveCard($tile["id"], "discard");
                $this->setGameStateValue('activeTile', 0);
                
                self::notifyAllPlayers("resourceCount", "", array(
                    'player_id' => self::getActivePlayerId(),
                    'resourceType' => "source",
                    'resourceCount' => $this->tiles->countCardsInLocation('hand', self::getActivePlayerId())
                ));
            }
            else
            {
                self::notifyAllPlayers( "placeBurn", clienttranslate( '${player_name} has ${count} eruption tiles to place, and will place one now' ), array(
                    'player_name' => self::getActivePlayerName(),
                    'count' => $count,
                ) );
                
                $this->gamestate->nextState('placeNewSource');    
                return;
            }            
        }

        //next state
        $this->gamestate->nextState('drawTile');
    }

    function stDrawTile()
    {
        //are there any tiles left?
        if ($this->tiles->countCardsInLocation("deck") == 0)
        {
            self::notifyAllPlayers( "noTile", clienttranslate( 'No tiles left in the stack, ${player_name} cannot draw any lava tiles' ), array(
                'player_name' => self::getActivePlayerName(),
            ) );
            $this->gamestate->nextState('tileStackEmpty');
            return;
        }

        //otherwise draw again, till either a valid tile is found, or the deck is exhausted
        //then reshuffle failed tiles back into the deck and continue
        //TODO - find out what should happen if the only playable space(s) fail due to walls

        //get playable spaces on the board
        $tileData = $this->argPlayTile();
        $connectedSpaces = $tileData["connectedEmptySpaces"];

        $playableTileFound = false;
        while (!$playableTileFound && $this->tiles->countCardsInLocation("deck") > 0) {
            $tile = $this->tiles->pickCardForLocation("deck", "temp");

            for ($i=0; $i<count($connectedSpaces) && !$playableTileFound;$i++)
            {
                $space = $connectedSpaces[$i];

                $validRotations = $this->getValidRotations($tile, $space['x'], $space['y'], false);
                if (count($validRotations) > 0) {
                    $playableTileFound = true;
                }
            }

            if (!$playableTileFound)
            {
                $this->tiles->moveCard($tile["id"], "tempdiscard");
            }
        }

        if ($playableTileFound) {
            //give the temp card to the player, move all temp discard cards back to the deck, and shuffle
            $tile = $this->tiles->pickCard("temp", self::getActivePlayerId());
            $this->tiles->moveAllCardsInLocation("tempdiscard", "deck");
            $this->tiles->shuffle("deck");
        }
        else{
            //there are no valid tiles to play. Move all cards to discard and notify players. Skip playing tile
            $this->tiles->moveAllCardsInLocation("tempdiscard", "discard");

            self::notifyAllPlayers( "noTile", clienttranslate( 'There are no playable tiles left in the stack, ${player_name} cannot draw' ), array(
                'player_name' => self::getActivePlayerName()
            ) );

            $this->gamestate->nextState('tileStackEmpty');
            return;
        }

        $this->setGameStateValue('activeTile', $tile['id']);

        self::notifyAllPlayers( "drawTile", clienttranslate( '${player_name} draws a lava tile' ), array(
                'player_name' => self::getActivePlayerName(),
                'activePlayerPos' => $this->getPlayerVariable('player_position', self::getActivePlayerId()),
                'tile' => $tile,
                'tileCount' => $this->tiles->countCardsInLocation('deck')
            ) );

        //send the valid tile locations to the player
        self::notifyPlayer( self::getActivePlayerId(), "setTilesClickable", "", array( "tiles" => "" ) );

        //next state
        $this->gamestate->nextState('tilePicked');
    }

    function stQuakeDrawTile()
    {
        //are there any tiles left?
        if ($this->tiles->countCardsInLocation("deck") == 0)
        {
            self::notifyAllPlayers( "noTile", clienttranslate( 'No tiles left in the stack, ${player_name} cannot draw any lava tiles' ), array(
                'player_name' => self::getActivePlayerName(),
            ) );
            $this->gamestate->nextState('tileStackEmpty');
            return;
        }

        //draw a tile and see if it can be played on the board
        //if not, draw again, till either a valid tile is found, or the deck is exhausted
        //then reshuffle failed tiles back into the deck and continue

        //get playable spaces on the board
        $boardTiles = $this->tiles->getCardsInLocation("board");

        $playableTileFound = false;
        while (!$playableTileFound && $this->tiles->countCardsInLocation("deck") > 0) {
            $tile = $this->tiles->pickCardForLocation("deck", "temp");

            foreach($boardTiles as $existingTile)
            {
                $x = $this->getXfromLocationInt($existingTile["location_arg"]);
                $y = $this->getYfromLocationInt($existingTile["location_arg"]);
                $validRotations = $this->getValidRotations($tile, $x, $y, false);
                if (count($validRotations) > 0) {
                    $playableTileFound = true;
                    break;
                }
            }

            if (!$playableTileFound)
            {
                $this->tiles->moveCard($tile["id"], "tempdiscard");
            }
        }

        if ($playableTileFound) {
            //give the temp card to the player, move all temp discard cards back to the deck, and shuffle
            $tile = $this->tiles->pickCard("temp", self::getActivePlayerId());
            $this->tiles->moveAllCardsInLocation("tempdiscard", "deck");
            $this->tiles->shuffle("deck");
        }
        else{
            //there are no valid tiles to play. Quake is skipped
            $this->tiles->moveAllCardsInLocation("tempdiscard", "deck");
            $this->tiles->shuffle("deck");

            self::notifyAllPlayers( "noTile", clienttranslate( 'There are no tiles left in the stack which can replace any on the board, Quake has no effect' ), array(
            ) );

            $this->gamestate->nextState('tileStackEmpty');
            return;
        }

        $this->setGameStateValue('activeTile', $tile['id']);

        self::notifyAllPlayers( "drawTile", clienttranslate( '${player_name} draws a lava tile' ), array(
            'player_name' => self::getActivePlayerName(),
            'activePlayerPos' => $this->getPlayerVariable('player_position', self::getActivePlayerId()),
            'tile' => $tile,
            'tileCount' => $this->tiles->countCardsInLocation('deck')
        ) );

        //send the valid tile locations to the player
        //self::notifyPlayer( self::getActivePlayerId(), "setTilesClickable", "", array( "tiles" => "" ) );

        //next state
        $this->gamestate->nextState('tilePicked');
    }

    function stEndTurn()
    {
        $player_id = self::getActivePlayerId();

        $this->giveExtraTime( $player_id );
        $this->setPlayerVariable('extra_tile_placed', $player_id, 0);
        $this->setPlayerVariable('extra_wall_placed', $player_id, 0);

        //check if this player has reached the end of the burn zone. If so, it triggers the end of the game
        //--> remove all remaining lava tiles, and all other players take one more turn
        $existingDamage = $this->getPlayerVariable('player_temp', $player_id);
        
        if ($existingDamage >= $this->maxBurnTemp && $this->getGameStateValue('gameOverTriggered') == 0)
        {
            //set the game over flag & player id
            $this->setGameStateValue('gameOverTriggered', 1);
            $this->setGameStateValue('gameOverPlayerId', self::getActivePlayerId());

            //remove everything remaining in the tile stack
            $this->tiles->moveAllCardsInLocation("deck","discard");

            //notify the players
            self::notifyAllPlayers( "endGame", clienttranslate( '${player_name} finishes on the last space of the burn meter and triggers the end of the game. Each other player has one more turn' ), array(
                'player_name' => self::getActivePlayerName(),
            ) );
        }
        //check if the tile stack has been exhausted. This ends the game too
        elseif ($this->getGameStateValue('gameOverTriggered') == 0 && $this->tiles->countCardsInLocation("deck") == 0)
        {
            //set the game over flag & player id
            $this->setGameStateValue('gameOverTriggered', 1);
            
            //if we are ending the game from empty tile stack, set the game overplayer id on the NEXT end turn
            //$this->setGameStateValue('gameOverPlayerId', self::getActivePlayerId());

            //notify the players
            self::notifyAllPlayers( "endGame", clienttranslate( 'There are no tiles left in the deck. Each player now has one more turn' ), array(
            ) );
        }
        elseif ($this->getGameStateValue('gameOverTriggered') == 1 && $this->getGameStateValue('gameOverPlayerId') == 0)
        {
            //if we have trigged end game on a previous turn but not set a player
            //then do so now.
            $this->setGameStateValue('gameOverPlayerId', self::getActivePlayerId());     
        }

        //has game over been triggered? and is the next player the one who triggered it? If so go to end game scoring
        if ($this->getGameStateValue('gameOverTriggered') == 1 && $this->getPlayerAfter(self::getActivePlayerId()) == $this->getGameStateValue('gameOverPlayerId'))
        {
            $this->gamestate->nextState('finalScoring');
            return;
        }

        $this->activeNextPlayer();
        $this->gamestate->nextState('nextPlayer');
    }

    function stFinalScoring()
    {
        //todo - add statistics!
        
        //stone walls remaining
        //wood walls remaining
        //straw walls remaining
        //home village lava connections
        
        //tiles placed
        //cards played
        //cards discarded

        //winner is lowest final temperature
        //in the case of a draw, 3/2/1 pts for walls in supply & village, and -1 per lava point connecting the village

        $players = self::loadPlayersBasicInfos();

        foreach($players as $player) {
            
            $player_id = $player['player_id'];
            
            $stone = $this->getPlayerVariable("stone", $player_id);
            self::setStat($stone, "player_stonewalls", $player_id);
            
            $wood = $this->getPlayerVariable("wood", $player_id);
            self::setStat($wood, "player_woodwalls", $player_id);
            
            $straw = $this->getPlayerVariable("straw", $player_id);
            self::setStat($straw, "player_strawwalls", $player_id);
            
            //$sql = "UPDATE player SET player_score = ".$beaten." WHERE player_id=".$player['player_id'];
            $sql = "UPDATE player SET player_score = ".$this->maxBurnTemp." - player_temp WHERE player_id=" . $player['player_id'];
            self::DbQuery($sql);

            //draw scores here
            //3/2/1 pt per stone wall in village or stockpile, -1pt per lava flow in connection
            $sql = "UPDATE player SET player_score_aux = 3*stone + 2*wood + straw WHERE player_id=" . $player['player_id'];
            self::DbQuery($sql);

            //aux score - add village wall points and subtract lava flows
            $wallPoints = 0;
            $lavaConnections = 0;

            $playerPosition = $this->getPlayerVariable('player_position', $player['player_id']);
            $dangerTiles = $this->getDangerTiles($playerPosition);
            $walls = $this->getWalls();

            foreach($dangerTiles as $tile)
            {
                if ($this->getConnectionState($tile[0], $tile[1], $tile[2]) == $this->tileStateLava) {
                    $lavaConnections++;
                }

                $newX = $tile[0];
                $newY = $tile[1];
                $this->moveCoords($newX, $newY, $tile[2]);

                foreach($walls as $wall)
                {
                    //this wall blocks one of this player's danger spots, so it is in their village
                    if ($newX == $wall['wall_x'] && $newY == $wall['wall_y'] && ($tile[2]+3)%6 == $wall['wall_rotation'])
                    {
                        if ($wall['wall_type'] == $this->resourceTypeStone) {
                            $wallPoints +=3;
                            self::incStat(1, "player_stonewalls", $player['player_id']);
                        }
                        if ($wall['wall_type'] == $this->resourceTypeWood) {
                            $wallPoints +=2;
                            self::incStat(1, "player_woodwalls", $player['player_id']);
                        }
                        if ($wall['wall_type'] == $this->resourceTypeStraw) {
                            $wallPoints +=1;
                            self::incStat(1, "player_strawwalls", $player['player_id']);
                        }
                    }
                }
            }
            
            self::setStat($lavaConnections, "player_lavaconnections", $player_id);

            $sql = "UPDATE player SET player_score_aux = player_score_aux + ".$wallPoints." WHERE player_id=" . $player['player_id'];
            self::DbQuery($sql);

            $sql = "UPDATE player SET player_score_aux = player_score_aux - ".$lavaConnections." WHERE player_id=" . $player['player_id'];
            self::DbQuery($sql);
        }
        
        $this->gamestate->nextState('');
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
    */

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];
    	
        if ($state['type'] == "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] == "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $sql = "
                UPDATE  player
                SET     player_is_multiactive = 0
                WHERE   player_id = $active_player
            ";
            self::DbQuery( $sql );

            $this->gamestate->updateMultiactiveOrNextState( '' );
            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }
    
///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:
        
        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.
    
    */
    
    function upgradeTableDb( $from_version )
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345
        
        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            $sql = "ALTER TABLE xxxxxxx ....";
//            self::DbQuery( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            $sql = "CREATE TABLE xxxxxxx ....";
//            self::DbQuery( $sql );
//        }
//        // Please add your future database scheme changes here
//
//


    }    
}
