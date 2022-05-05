/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * akeruption implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * akeruption.js
 *
 * akeruption user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */
dojo.require("dojox.fx.ext-dojo.complex");
define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
        "ebg/stock",
    "ebg/counter"
],
function (dojo, declare) {

    // The following code to determine the transform style property name
    // is adapted from:
    // http://www.zachstronaut.com/posts/2009/02/17/animate-css-transforms-firefox-webkit.html
    
    window.onresize = function(event) {
        var div = document.getElementById("spacer");
        console.log(div.offsetWidth);
        if (div.offsetWidth < 260)
        {
            if (!dojo.hasClass("privatePlayerCards", "marginLeft5Clear")){
                dojo.addClass("privatePlayerCards", "marginLeft5Clear");
            }
        }
        else {
            if (dojo.hasClass("privatePlayerCards", "marginLeft5Clear")){
                dojo.removeClass("privatePlayerCards", "marginLeft5Clear");
            }
        }        
    };

    var transform;
    dojo.forEach(
        ['transform', 'WebkitTransform', 'msTransform',
         'MozTransform', 'OTransform'],
        function (name) {
            if (typeof dojo.body().style[name] != 'undefined') {
                transform = name;
            }
        });

    return declare("bgagame.akeruption", ebg.core.gamegui, {
        constructor: function(){
            console.log('akeruption constructor');
              
            // Here, you can init the global variables of your user interface
            // Example:
            // this.myGlobalValue = 0;
            this.boardWidth = 734;
            this.tileHeight = 61; //also in css .tile
            this.tileWidth = 70; //also in css .tile
            this.wallWidth = 30;
            this.wallHeight = 10;
            this.tokenWidth = 24;
            this.tilesPerRowInImg = 7;
            this.boardTiles = null;
            this.tileTypes = null;
            this.activeTile = null;
            this.activeTileRotationState = 0;
            this.cardWidth = 115;
            this.cardHeight = 175;
            this.cardsPerRowInImg = 4;
            this.cardTypeCount = 8;

            this.TYPE_EMPTY = 0;
            this.TYPE_LAVA = 1;
            this.TYPE_GRASS = 2;

            this.scores = [];
            this.scoresLoaded = false;
            
            this.nexttilezindex = 0;
        },

        /*
            setup:
            
            This method must set up the game user interface according to current game situation specified
            in parameters.
            
            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)
            
            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */
        
        setup: function( gamedatas )
        {
            console.log("Starting game setup");
            console.log("trade cards tweak added");
            console.log(gamedatas);

            this.errorTileConnection = _("New tile must connect to an existing lava trail");
            this.errorTrailOrGrassMatch = _("Lava trails and grass edges must match on all sides");
            this.errorTileAlreadyFailed = _("You cannot attempt to place the same tile here again");
            this.errorCardRequired = _("You must select exactly one card for this action");
            this.error2CardsRequired = _("You must select exactly two cards for this action");
            this.errorCardNumber = _("You may not finish your turn with more than 3 cards");
            this.errorBuildWall =_("You do not have any walls to build");
            this.errorNotRemovable = _("This tile cannot be removed, as not all tiles will be connected to a lava source");
            this.errorRotate = _("There are no valid rotations for this tile");
            this.errorReplace = _("There is no valid placament for your tile here");
            this.errorBoardEmpty = _("There are no tiles on the board");
            this.errorStackEmpty = _("There are no tiles left");

            this.strHand = _('Your Action Cards');
            this.strStraw = _('Straw');
            this.strWood = _('Wood');
            this.strStone = _('Stone');
            this.strConfirmTile = _('Confirm Placement');
            this.strPass = _('Pass');
            this.strCancel = _('Cancel');
            this.strPlayTile = _('Play Tile');
            this.strGainWall = _('Trade card for a wall');
            this.strGainTile = _('Trade 2 cards for a lava tile');
            this.strCardEffect = _('Play card effect');
            this.strFinished = _('Finished');

            this.strAftershock = _('Allows a player to rotate any tile on the board to any position. The tile must remain in its current location. No tiles may violate standard tile placement rules due to the rotation of the tile. Any walls on the tile are immediately discarded. Can instead be traded for 1 stone wall');
            this.strQuake = _('Allows a player to draw a new Lava Tile and immediately replace any existing tile on the board. The new tile must follow standard tile placement rules. The old tile, along with any walls built on it, is discarded from the board. If no replacements can be made, the tile is placed randomly within the stack and another tile is drawn. Can instead be traded for 1 stone wall');
			
			this.strRain = _('Allows a player to immediately cool down his own village by 30 degrees on the Burn Meter. Can instead be traded for 1 wood wall');
			this.strSinkhole = _('Allows a player to discard any existing tile from the board, along with any walls built on it. No tiles may violate standard tile placement rules due to the removal of the tile. Can instead be traded for 1 wood wall');
			this.strVolcanicBomb = _('Allows a player to discard any wall that is currently on the board, belonging to any player. Can instead be traded for 1 wood wall');
			
            this.strLavaFlow = _('Allows a player to draw and place a new Lava Tile following standard tile placement rules. Can instead be traded for 1 straw wall');
            this.strRelocate = _('Allows a player to move one or more walls in his village to any other location within the village. After doing so, no more than one wall may exist in any one location. Can instead be traded for 1 straw wall');
            this.strReinforce = _('Allows a player to immediately build a wall from his stockpile, in addition to the one wall normally allowed at the end of the turn. Can instead be traded for 1 straw wall');
                        
            this.strStrawTooltip = _("Straw walls have no bonus when defending against a lava flow. Each straw wall in your village or supply is worth 1pt in a tiebreaker");
            this.strWoodTooltip = _("Wood walls have a +1 bonus when defending against a lava flow. Each wood wall in your village or supply is worth 2pts in a tiebreaker");
            this.strStoneTooltip = _("Stone walls have a +2 bonus when defending against a lava flow. Each stone wall in your village or supply is worth 3pts in a tiebreaker");

            // Player hand
            this.playerHand = new ebg.stock();
            this.playerHand.create(this, $('playerHand'), this.cardWidth, this.cardHeight);
            this.playerHand.image_items_per_row = this.cardsPerRowInImg;
            this.playerHand.setSelectionMode(0);
            this.playerHand.onItemCreate = dojo.hitch( this, 'setupNewCard' );

            // Create cards types:
            for (var index = 0; index < this.cardTypeCount; index++) {
                var card_type_id = index;
                this.playerHand.addItemType( card_type_id, card_type_id, g_gamethemeurl+'img/actioncards.png', card_type_id );
            }


            //Cards in player's hand
            console.log(gamedatas.hand);
            for (var i in gamedatas.hand) {
                var card = gamedatas.hand[i];
                this.playerHand.addToStockWithId(card.type, card.id);
            }


            //for reference
            this.tileTypes = gamedatas.tileTypes;
            this.activeTile = gamedatas.activeTile;

            $('txtHand').innerHTML = this.strHand;
            
            // Setting up main board
            this.boardTiles = gamedatas.boardTiles;
            for( var tile_id in gamedatas.boardTiles )
            {
                var tile = gamedatas.boardTiles[tile_id];
                var x = this.getXfromLocationInt(tile['location_arg']);
                var y = this.getYfromLocationInt(tile['location_arg']);
                var rotation = this.getRotationFromLocationInt(tile['location_arg']);
                this.placeTile(tile, x, y, rotation);
            }

            //draw walls on initial setup
            for (var wall_id in gamedatas.walls) {
                var wall = gamedatas.walls[wall_id];
                this.placeWall(wall['wall_type'], wall['wall_x'], wall['wall_y'], wall['wall_rotation'], false, 0);
            }

            //new lava source tiles
            this.sourceCount = parseInt(gamedatas.lavaSourceTileCount);
            if (this.sourceCount < 3) {
                dojo.destroy('eruptionTile1');                
            }
            if (this.sourceCount < 2) {
                dojo.destroy('eruptionTile2');                
            }
            if (this.sourceCount < 1) {
                dojo.destroy('eruptionTile3');                
            }
            
            //for (var tile_id in gamedatas.lavaSourceTiles) {
            //    this.placeTileAt(gamedatas.lavaSourceTiles[tile_id], 'eruptionTile' + sourceCount, 0);
            //    sourceCount++;
            //}
            
            //card count
            this.setCardsRemaining(gamedatas.cardCount);
            
            if (this.gamedatas.lastCardDiscarded != null)
            {
                this.placeActionCard(this.gamedatas.lastCardDiscarded["id"], this.gamedatas.lastCardDiscarded["type"], "cardDiscard");
            }
            
            //tile count & next tile (if there is one)
            this.setTilesRemaining(gamedatas.tileCount);

            //todo - should logic like this go server side? and use this.isCurrentPlayerActive()
            //ONLY if this is the active player, draw active tile from board_temp (+rotator) if it exists
            if (this.activeTile != null) {
                if (this.activeTile['location'] == "board_temp" && this.player_id == gamedatas.activePlayerId) {
                    x = this.getXfromLocationInt(this.activeTile['location_arg']);
                    y = this.getYfromLocationInt(this.activeTile['location_arg']);
                    this.placeTile(this.activeTile, x, y, 0);
                } else if (this.activeTile['location'] != "board") {
                    var nextTile = gamedatas.activeTile;
                    if (nextTile != null && nextTile != '') {
                        this.placeNextTile(nextTile, gamedatas.activePlayerPos, false);
                    }
                }
            }

            // Setting up player boards
            //var offset = 280.0;
            this.players = gamedatas.players;
            console.log("players");
            console.log(gamedatas.players);
            for (var player_id in gamedatas.players) {

                var player = gamedatas.players[player_id];

                var player_board_div = $('player_board_' + player_id);
                dojo.place(this.format_block('jstpl_player_iconsA', player), player_board_div);
                dojo.place(this.format_block('jstpl_player_iconsB', player), player_board_div);
                
                this.setPlayerResourceCount(player_id, "straw", player['straw']);
                this.setPlayerResourceCount(player_id, "wood", player['wood']);
                this.setPlayerResourceCount(player_id, "stone", player['stone']);
                this.setPlayerResourceCount(player_id, "card", player['cardCount']);
                this.setPlayerResourceCount(player_id, "source", player['sourceCount']);
                
                this.addTooltip("strawicon_"+player_id, this.strStrawTooltip, '');
                this.addTooltip("woodicon_"+player_id, this.strWoodTooltip, '');
                this.addTooltip("stoneicon_"+player_id, this.strStoneTooltip, '');
                               
                var position = player['player_position'];
                //todo - better coding for position to colour conv.. reorder image?
                //img is red, orange, yellow, green, blue, purple
                //$colorPos = array($orange, $yellow, $green, $blue, $purple, $red);
                var xOffset = 0;
                if (position == 0)
                    xOffset = this.tokenWidth * 1;
                if (position == 1)
                    xOffset = this.tokenWidth * 2;
                if (position == 2)
                    xOffset = this.tokenWidth * 3;
                if (position == 3)
                    xOffset = this.tokenWidth * 4;
                if (position == 4)
                    xOffset = this.tokenWidth * 5;
                if (position == 5)
                    xOffset = this.tokenWidth * 0;
                
                dojo.place(
                    this.format_block('jstpl_playerToken', {
                        x: xOffset,
                        y: 0,
                        z: player['player_position'],
                        player_id: player_id,
                    }), 'board');

                this.setPlayerTemp(player_id, player['player_position'], player['player_temp'], false, player['player_score']);
            }

            dojo.query('.tileClickable').connect('onclick', this, 'onTileClicked');
            
            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            console.log( "Ending game setup" );
        },
        
        getTooltip: function(card_type_id){
            var tooltip = "";
            
            switch(card_type_id) {
                case "0":
                    tooltip = this.strLavaFlow;
                    break;
                case "1":
                    tooltip = this.strReinforce;
                    break;
                case "2":
                    tooltip = this.strRelocate;
                    break;
                case "3":
                    tooltip = this.strAftershock;
                    break;
                case "4":
                    tooltip = this.strRain;
                    break;
                case "5":
                    tooltip = this.strSinkhole;
                    break;
                case "6":
                    tooltip = this.strVolcanicBomb;
                    break;
                case "7":
                    tooltip = this.strQuake;
                    break;
                default:
                    tooltip = "not implemented";
            }
            return tooltip;
        },

        setupNewCard: function( card_div, card_type_id, card_id )
        {
            var tooltip = this.getTooltip(card_type_id);

            // Add a special tooltip on the card:
            this.addTooltip( card_div.id, '', tooltip);
        },

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, state )
        {
            console.log('Entering state: ' + stateName);
            console.log(state);
            this.stateName = stateName;

            if (!this.scoresLoaded) {
                for (var player_id in this.players) {
                    if (this.scoreCtrl[player_id] != null) {
                        this.scoreCtrl[player_id].setValue(this.scores[player_id]);
                    }
                }
                this.scoresLoaded = true;
            }

           
            switch( stateName )
            {
                case 'confirmTile':
                case 'confirmSourceTile':
                    if (this.isCurrentPlayerActive()) {
                        console.log("confirmTile state entered");
                        console.log(state.args);
                        this.validRotations = state.args["validRotations"];
                        this.addRotatorToTile(this.activeTile);
                        //no break here - the confirmTile action also allows tiles to be clickable
                    }
                case 'playTile':
                case 'playSourceTile':
                    if (this.isCurrentPlayerActive()) {
                        console.log("setting valid tiles");
                        //tile_clickable_x_y
                        console.log(state.args);
                        var boardSpaces = state.args["spaces"];
                        this.connectedEmptySpaces = state.args["connectedEmptySpaces"];
                        for (var i = 0; i < boardSpaces.length; i++) {
                            dojo.addClass("tile_clickable_" + boardSpaces[i].x + "_" + boardSpaces[i].y, "active");
                        }

                        this.failedPlacements = state.args["failedPlacements"];

                        //if we just entered 'playTile', then reset rotation state
                        if (stateName == 'playTile' || stateName == 'playSourceTile') {
                            this.activeTileRotationState = 0;
                        }

                        if (stateName == 'playSourceTile') {
                            this.activeTile = state.args["tile"];
                        }
                    }

                    break;
                case 'playWalls':
                    if (this.isCurrentPlayerActive()) {
                        console.log(state.args);
                        if (state.args.canBuild) {
                            for (var i = 0; i < state.args.connectedEmptySpaces.length; i++) {
                                this.addWallArrow(state.args.connectedEmptySpaces[i].x, state.args.connectedEmptySpaces[i].y, state.args.connectedDirections[i]);
                            }
                        }
                    }
                    break;
                case 'relocatePickWall' :
                case 'relocatePlaceWall' :
                    if (this.isCurrentPlayerActive()){
                        for (var i = 0; i < state.args.connectedEmptySpaces.length; i++) {
                            this.addWallArrow(state.args.connectedEmptySpaces[i].x, state.args.connectedEmptySpaces[i].y, state.args.connectedDirections[i]);
                        }
                    }
                case 'playActionCards':
                    if( this.isCurrentPlayerActive() ) {
                        this.canAddTile = state.args.canAddTile;
                        this.tilesExist = state.args.tilesExist;
                        this.canBuildWall = state.args.canBuildWall;
                        this.playerHand.setSelectionMode(2);
                    }
                    break;
                case 'confirmReplaceTile':
                case 'confirmRotateTile':
                {
                    if( this.isCurrentPlayerActive() ) {
                        this.activeTile = state.args["tile"];
                        this.activeTileRotationState = 0;
                        if (stateName == 'confirmRotateTile'){
                            this.aftershockTileOriginalRotationState = state.args["rotation"];                                                  
                            this.activeTileRotationState = state.args["rotation"]; 
                        }
                        this.validRotations = state.args["validRotations"];
                        this.addRotatorToTile(this.activeTile);
                    }
                    //no break here, when confirming an aftershock rotate tile, you can still pick a different one
                }
                case 'quake':
                case 'aftershock':
                case 'sinkhole':{
                    //make all the tiles clickable
                    if( this.isCurrentPlayerActive() ) {
                        this.stateConnections=[];
                        var boardSpaces = state.args["allTiles"];
                        this.validTiles = state.args["validTiles"];
                        for (var i = 0; i < boardSpaces.length; i++) {
                            dojo.addClass("lava_" + boardSpaces[i].id, "active");
                            this.stateConnections.push(dojo.connect($("lava_" + boardSpaces[i].id), 'onclick', this, 'onLavaClicked'));
                        }
                    }
                    break;
                }
                case 'volcanicBomb':{
                    if( this.isCurrentPlayerActive() ) {
                        console.log(state.args);

                        for (var i=0; i< state.args.wallArrows.length; i++) {
                            this.addWallArrow(state.args.wallArrows[i].x, state.args.wallArrows[i].y, state.args.wallArrows[i].dir);
                        }
                    }
                    break;
                }
           
            case 'dummmy':
                break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log('Leaving state: ' + stateName);

            //cleanup, destroy any temporary objects and classes which may be left over from some states
            if (this.stateConnections != null) {
                for (var i = 0; i < this.stateConnections.length; i++) {
                    this.stateConnections[i].remove();
                }
            }

            dojo.query(".clickableWall").removeClass("clickableWall");

            dojo.destroy('rotator1');

            dojo.query(".testArrow").forEach(function(node, index, nodelist){
            //    // for each node in the array returned by dojo.query,
            //    // execute the following code
                dojo.destroy(node);
            });

            dojo.query(".activeTile").forEach(function (node) {
                dojo.removeClass(node.id, "activeTile");
            });

            dojo.query(".tileClickable").forEach(function (node) {
                dojo.removeClass(node.id, "active");
            });

            this.playerHand.setSelectionMode(0);
            
            switch( stateName )
            {
                case 'dummmy':
                    break;
            }               
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName );
            console.log( 'onUpdateActionButtons: ');
            console.log(args);

            if( this.isCurrentPlayerActive() )
            {            
                switch( stateName )
                {
                    case 'confirmTile':
                    case 'confirmSourceTile':
                    case 'confirmRotateTile':
                    case 'confirmReplaceTile':
                        this.addActionButton('button_confirm', this.strConfirmTile, 'onTileConfirmed');
                        break;
                    case 'playActionCards':
                        var items = this.playerHand.getAllItems();
                        if (items.length > 1) {
                            this.addActionButton('button_get_tile', this.strGainTile, 'onCardsGainTile');
                        }
                        if (items.length > 0) {
                            this.addActionButton('button_get_wall', this.strGainWall, 'onCardGainWall');
                            this.addActionButton('button_do_action', this.strCardEffect, 'onCardDoAction');
                        }
                        this.addActionButton('button_pass', this.strPass, 'onCardPass');
                        break;
                        break;
                    case 'playWalls':
                        //cannot pass if you're building a wall from a card effect
                        if (args.isCardEffect != 1) {
                            this.addActionButton('button_pass', this.strPass, 'onPass');
                        }
                        break;
                    case 'extraTileCheck':
                        this.addActionButton('button_play_tile', this.strPlayTile, 'onPlayExtraTile');
                        this.addActionButton('button_pass', this.strPass, 'onPass');
                        break;
                    case 'chooseWallMaterial':
                        if (parseInt(args.straw) > 0) {
                            this.addActionButton('button_straw', this.strStraw, 'onChooseWallMaterial');
                        }
                        if (parseInt(args.wood) > 0) {
                            this.addActionButton('button_wood', this.strWood, 'onChooseWallMaterial');
                        }
                        if (parseInt(args.stone) > 0) {
                            this.addActionButton('button_stone', this.strStone, 'onChooseWallMaterial');
                        }
                        this.addActionButton('button_pass', this.strCancel, 'onPass');
                        break;
                    case 'aftershock':
                        this.addActionButton('button_pass', this.strPass, 'onPass');
                        break;
                    case 'volcanicBomb':
                        this.addActionButton('button_pass', this.strPass, 'onPass');
                        break;
                    case 'relocatePickWall':
                        this.addActionButton('button_pass', this.strFinished, 'onPass');
                        break;                  
                    case 'relocatePlaceWall':
                        this.addActionButton('button_pass', this.strCancel, 'onPass');
                        break;                  
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods
        
        /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */
        setPlayerResourceCount: function (player_id, resource, count)
        {
            $(resource +'count_p'+player_id).innerHTML = count;
        },
        setPlayerTemp: function(player_id, player_position, temp, isUpdate, score) {
            var radius = 0.45*this.boardWidth;
            //TODO - base the offset on number of players sharing the space?
            //TODO - but think of effect on z index?
            var angle = 15 + (( parseFloat(player_position) * 0.9 + parseFloat(temp)) * (360 / 320));

            var x1 = radius * Math.sin(angle * -(Math.PI / 180));
            var y1 = radius * Math.cos(angle * -(Math.PI / 180));

            if (!isUpdate) {
            //    alert(player_id + "," + player_position + "," + temp + "," + x1 + "," + y1);
                this.placeOnObjectPos('playerToken_' + player_id, 'board', x1, y1);
                this.slideToObjectPos('playerToken_' + player_id, 'board', 0.48*this.boardWidth+x1, 0.48*this.boardWidth+y1, 1000, 0).play();
            } else {
            //    alert(player_id + "," + player_position + "," + temp + "," + x1 + "," + y1);
            //    this.ToObjectPos('playerToken_' + player_id, 'board', 0, 0, 1000, 0).play();
                this.slideToObjectPos('playerToken_' + player_id, 'board', 0.48*this.boardWidth+x1, 0.48*this.boardWidth+y1, 1000, 0).play();
            }

            this.scores[player_id] = score;

            if (this.scoreCtrl[player_id] != null) {
                this.scoreCtrl[player_id].setValue(this.scores[player_id]);
            }
        },
        addRotatorToTile: function(tile) {
            console.log("add rotator");
            console.log(tile);
            var tileID = "lava_" + tile['id'];
            
            dojo.addClass(tileID, "activeTile");

            dojo.place(this.format_block('jstpl_rotator', { }), "board");
            this.slideToObjectPos("rotator1", dojo.byId(tileID).parentNode.id, -29, 60, 0, 0).play();
            dojo.query('.anticlockwiseArrow').connect('onclick', this, 'onRotateLeft');
            dojo.query('.clockwiseArrow').connect('onclick', this, 'onRotateRight');
        },
        setTilesRemaining: function(count) {
            $('tileCount').innerHTML = count;
        },
        setCardsRemaining: function(count) {
            $('cardCount').innerHTML = count;
        },

        getRotationFromLocationInt: function(location) {
            return Math.floor(location / (11*11) );
        },
        getXfromLocationInt: function(location)
        {
            return location % 11;
        },
        getYfromLocationInt: function (location) {
            return Math.floor((location % (11*11) ) / 11);
        },
        
        placeActionCard: function(card_id, type, location)
        {
            var y = 0;
            if (type >= this.cardsPerRowInImg)
            {
                type -= this.cardsPerRowInImg;
                y += this.cardHeight;
            }
            
            dojo.place(
                    this.format_block('jstpl_actionCard', {
                        card_id : card_id,
                        x: this.cardWidth*type,
                        y: this.cardHeight*y,
                        z: 100
                    }), location);
                    
            this.addTooltip("card_" + card_id, "", this.getTooltip(type));
        },

        placeTile: function (tile, x, y, rotation) {

            this.placeTileAt(tile, "tile_" + x + "_" + y, rotation);
        },

        placeTileAt: function (tile, divID, rotation) {
            var xImgOffset = (tile['type']-1) % this.tilesPerRowInImg;
            var yImgOffset = Math.floor((tile['type']-1) / this.tilesPerRowInImg);
            dojo.place(
                this.format_block('jstpl_lavaTile', {
                    height: this.tileHeight,
                    width: this.tileWidth,
                    x: this.tileWidth * xImgOffset,
                    y: this.tileHeight * yImgOffset,
                    rotation: rotation*60,
                    z: this.nexttilezindex,
                    tile_id: tile['id'],
                    type: tile['type']
                }), divID);
            this.nexttilezindex++;
        },
        
        placeNextTile: function (tile, position, doSlide) {
            
            //place it if it doesn't exist
             var existingTile = dojo.byId("lava_"+tile['id']);
             
             if (existingTile == null) {
                var xImgOffset = (tile['type']-1) % this.tilesPerRowInImg;
                var yImgOffset = Math.floor((tile['type']-1) / this.tilesPerRowInImg);
                dojo.place(
                    this.format_block('jstpl_lavaTile', {
                        height: this.tileHeight,
                        width: this.tileWidth,
                        x: this.tileWidth * xImgOffset,
                        y: this.tileHeight * yImgOffset,
                        rotation: 0*60,
                        z: this.nexttilezindex,
                        tile_id: tile['id'],
                        type: tile['type']
                    }), "board");
                this.nexttilezindex++;
            }
            
            var target_x = 0;
            var target_y = 0;
            if (position == 1)
            {
                target_x = this.boardWidth*0.33;
                target_y = -this.boardWidth*0.19;
            }
            if (position == 2)
            {
                target_x = this.boardWidth*0.33;
                target_y = this.boardWidth*0.19;
            }
            if (position == 4)
            {
                target_x = -this.boardWidth*0.33;
                target_y = this.boardWidth*0.19;
            }
            if (position == 5)
            {
                target_x = -this.boardWidth*0.33;
                target_y = -this.boardWidth*0.19;
            }
            if (position == 0)
            {
                target_y = -this.boardWidth*0.38;
            }
            if (position == 3)
            {
                target_y = this.boardWidth*0.38;
            }
            
            if (doSlide)
            {
                this.slideToObjectPos("lava_"+tile['id'], 'board', target_x+this.boardWidth/2-this.tileWidth/2, target_y+this.boardWidth/2-this.tileHeight/2).play();
            }
            else
            {
                this.placeOnObjectPos("lava_"+tile['id'], "board", target_x, target_y );
                this.slideToObjectPos("lava_"+tile['id'], 'board', target_x+this.boardWidth/2-this.tileWidth/2, target_y+this.boardWidth/2-this.tileHeight/2,0,0).play();
            }
        },

        placeWall: function (type, x, y, rotation, isFromPlayerPanel, playerID) {
            console.log("placeWall: " + type + " - " + x + " - " + y + " - " + rotation)

            var wallID = "wall_" + x + "_" + y + "_" + rotation;

            var target;
            if (isFromPlayerPanel) {
                target = "overall_player_board_" + playerID;
            }
            else
            {
                target = "tile_" + x + "_" + y;
            }

            dojo.place(
                this.format_block('jstpl_wall', {
                    id: wallID,
                    type: type,
                    x: 0,
                    y: 0,
                    rotation: rotation * 60
                }), target);

            var xOffset = 0;
            var yOffset = 0;

            var xSlideOffset = this.tileWidth/2;
            var ySlideOffset = this.tileHeight/2;

            if(rotation ==0) {
                yOffset = -27;
                xSlideOffset -= this.wallWidth/2;
                ySlideOffset -= -this.wallHeight/2+10;
            }
            if(rotation ==1)
            {
                xOffset = 22;
                yOffset = -14;
                xSlideOffset -= (this.wallWidth*0.3);
                ySlideOffset -= (this.wallWidth*0.5);
            }
            if(rotation ==2)
            {
                xOffset = 24;
                yOffset = 14;
                xSlideOffset -= (this.wallWidth*0.3);
                ySlideOffset -= (this.wallWidth*0.5);
            }
            if(rotation ==3) {
                yOffset = 27;
                xSlideOffset -= this.wallWidth/2;
                ySlideOffset -= -this.wallHeight/2+10;
            }
            if(rotation ==4) {
                xOffset = -24;
                yOffset = 14;
                xSlideOffset -= (this.wallWidth*0.3);
                ySlideOffset -= (this.wallWidth*0.5);
            }
            if(rotation ==5) {
                xOffset = -24;
                yOffset = -14;
                xSlideOffset -= (this.wallWidth*0.3);
                ySlideOffset -= (this.wallWidth*0.5);
            }

            if (isFromPlayerPanel)
            {
                //alert("adding");
                this.placeOnObjectPos( wallID, target, xOffset, yOffset);
                //alert("moving");
                this.attachToNewParent( wallID, "tile_"+x+"_"+y );
                this.slideToObjectPos( wallID, "tile_"+x+"_"+y, xSlideOffset+xOffset, ySlideOffset+ yOffset, 1000, 1000).play();
            }
            else
            {
                this.placeOnObjectPos( wallID, "tile_"+x+"_"+y, xOffset, yOffset);
            }

        },

        addWallArrow: function (x, y, dir) {
            console.log(x + "," + y + "," + dir);
            var rotation = 0;
            var xOffset = 0;
            var yOffset = 0;
            //todo - replace these constants
            if(dir ==3) {
                rotation = 0;
                yOffset = 21;
            }
            if(dir ==4) {
                rotation = 1;
                xOffset = -18;
                yOffset = 12;
            }
            if(dir ==5) {
                rotation = 2;
                xOffset = -18;
                yOffset = -12;
            }
            if(dir ==0) {
                rotation = 3;
                yOffset = -21;
            }
            if(dir ==1)
            {
                rotation =4;
                xOffset = 18;
                yOffset = -12;
            }
            if(dir ==2)
            {
                rotation = 5;
                xOffset = 18;
                yOffset = 12;
            }

            var xPos = this.getXfromLocationInt(x);
            var yPos = this.getYfromLocationInt(y);
            var arrowID = x+"_"+y+"_"+((rotation+3)%6);
            console.log(arrowID);
            dojo.place(
                this.format_block('jstpl_wallArrow', {
                    id:arrowID,
                    x: xPos,
                    y: yPos,
                    rotation: rotation*60
                }), "tile_"+x+"_"+y);

            this.placeOnObjectPos( arrowID, "tile_"+x+"_"+y, xOffset, yOffset);

            dojo.connect( $(arrowID), 'onclick', this, 'onClickWallArrow' );
        },

        ///////////////////////////////////////////////////
        //// Player's action
        
        /*
        
            Here, you are defining methods to handle player's action (ex: results of mouse click on 
            game objects).
            
            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server
        
        */

        onCardsGainTile: function(evt){
            
            var items = this.playerHand.getSelectedItems();
            if(items.length != 2)
            {
                this.showMessage(this.error2CardsRequired, 'error');
                return;
            }
            
            if (!this.canAddTile)
            {
                this.showMessage(this.errorStackEmpty, 'error');
                return;
            }

            var cardIdArray = [];
            for(var i=0; i<items.length;i++)
            {
                cardIdArray.push(items[i].id);
            }

            console.log(cardIdArray.join());

            this.ajaxcall("/akeruption/akeruption/tradeCardsForTile.html",
                {
                    lock: true,
                    cardIDstring: cardIdArray.join()
                },
                this,
                function (result) { },
                function (is_error) { }
            );
        },

        onCardGainWall: function(evt){

            var items = this.playerHand.getSelectedItems();
            if(items.length != 1)
            {
                this.showMessage(this.errorCardRequired, 'error');
                return;
            }

            console.log(items[0]);
            this.ajaxcall("/akeruption/akeruption/tradeCardForWall.html",
                {
                    lock: true,
                    cardID: items[0].id,
                },
                this,
                function (result) { },
                function (is_error) { }
            );
        },

        onCardDoAction: function(evt){

            var items = this.playerHand.getSelectedItems();
                
            if(items.length != 1)
            {
                this.showMessage(this.errorCardRequired, 'error');
                return;
            }
            
            if (!this.canAddTile && (items[0].type == 0 || items[0].type == 7))
            {
                this.showMessage(this.errorStackEmpty, 'error');
                return;
            }
            
            if (!this.tilesExist)
            {
                if (items[0].type == 3 || items[0].type == 5 || items[0].type == 7)
                {
                    this.showMessage(this.errorBoardEmpty, 'error');
                    return
                }              
            }

            if (items[0].type == 1 && !this.canBuildWall)
            {
                this.showMessage(this.errorBuildWall, 'error');
                return;
            }

            this.ajaxcall("/akeruption/akeruption/playCard.html",
                {
                    lock: true,
                    cardID: items[0].id,
                },
                this,
                function (result) { },
                function (is_error) { }
            );
        },

        onCardPass: function(evt){
            console.log("PASS (B)");

            var items = this.playerHand.getAllItems();
            if(items.length > 3)
            {
                this.showMessage(this.errorCardNumber, 'error');
                return;
            }

            this.ajaxcall("/akeruption/akeruption/cardPass.html",
                {
                    lock: true,
                },
                this,
                function (result) { },
                function (is_error) { }
            );
        },

        onChooseWallMaterial: function(evt)
        {
            console.log("CHOOSE");
            console.log(evt);

            dojo.stopEvent(evt);

            this.ajaxcall("/akeruption/akeruption/wallMaterialSelected.html",
                {
                    lock: true,
                    buttonID: evt.target.id,
                },
                this,
                function (result) { },
                function (is_error) { }
            );
        },


        onClickWallArrow: function(evt)
        {
            dojo.stopEvent(evt);

            if (this.stateName == "volcanicBomb")
            {
                this.ajaxcall("/akeruption/akeruption/destroyWall.html",
                    {
                        lock: true,
                        location: evt.target.id,
                    },
                    this,
                    function (result) { },
                    function (is_error) { }
                );
            }
            else if (this.stateName == "relocatePickWall")
            {
                this.ajaxcall("/akeruption/akeruption/relocatePickWall.html",
                    {
                        lock: true,
                        location: evt.target.id,
                    },
                    this,
                    function (result) { },
                    function (is_error) { }
                );
            }
            else if (this.stateName == "relocatePlaceWall")
            {
                this.ajaxcall("/akeruption/akeruption/relocatePlaceWall.html",
                    {
                        lock: true,
                        location: evt.target.id,
                    },
                    this,
                    function (result) { },
                    function (is_error) { }
                );
            }
            else{
                this.ajaxcall("/akeruption/akeruption/wallArrowSelected.html",
                    {
                        lock: true,
                        location: evt.target.id,
                    },
                    this,
                    function (result) { },
                    function (is_error) { }
                );
            }
        },

        onRotateLeft: function (evt) {

            // Stop this event propagation
            dojo.stopEvent(evt);

            console.log("rotate left");

            dojo.animateProperty({
                node: dojo.query('.activeTile')[0].id,
                duration: 500,
                properties: {
                    transform: { start: 'rotate(' + (this.activeTileRotationState) * 60 + 'deg)', end: 'rotate(' + (this.activeTileRotationState - 1) * 60 + 'deg)' }
                }
            }).play();

            this.activeTileRotationState--;
            if (this.activeTileRotationState < 0)
                this.activeTileRotationState += 6;
        },

        onRotateRight: function (evt) {

            // Stop this event propagation
            dojo.stopEvent(evt);

            console.log("rotate right");
            console.log(dojo.query('.activeTile')[0].id);

            dojo.animateProperty({
                node: dojo.query('.activeTile')[0].id,
                duration: 500,
                properties: {
                    transform: { start: 'rotate(' + (this.activeTileRotationState) * 60 + 'deg)', end: 'rotate(' + (this.activeTileRotationState + 1) * 60 + 'deg)' }
                }
            }).play();

            this.activeTileRotationState++;
            if (this.activeTileRotationState > 5)
                this.activeTileRotationState -= 6;
        },

        onPass: function (evt) {

            // Stop this event propagation
            dojo.stopEvent(evt);

            this.ajaxcall("/akeruption/akeruption/pass.html",
                {
                    lock: true,
                },
                this,
                function (result) { },
                function (is_error) { }
            );
        },

        onPlayExtraTile: function (evt) {

            // Stop this event propagation
            dojo.stopEvent(evt);

            this.ajaxcall("/akeruption/akeruption/playExtraTile.html",
                {
                    lock: true,
                },
                this,
                function (result) { },
                function (is_error) { }
            );
        },

        onLavaClicked: function(evt) {

            // Stop this event propagation
            dojo.stopEvent(evt);

            console.log("lava clicked");

            if (!dojo.hasClass(evt.currentTarget.id, 'active')) {
                // This is not a possible move => the click does nothing
                return;
            }

            // Get the clicked id
            // Note: id format is "lava_id"
            var coords = evt.currentTarget.id.split('_');
            var id = parseInt(coords[1]);

            var isValid = false;
            for (var i = 0; i < this.validTiles.length && !isValid; i++) {
                if (id == this.validTiles[i].id)
                    isValid = true;
            }

            if (this.stateName == "aftershock" || this.stateName == "confirmRotateTile") {
                //set this tile as the one to be rotated. Allow used to pick a different tile
                //which means being able to reset any rotations made on the UI

                if (!isValid) {
                    this.showMessage(this.errorRotate, 'error');
                    return;
                }

                //revert this tile back to it's original rotation
                if (this.stateName == "confirmRotateTile")
                {
                    //aftershockTileOriginalRotationState
                    dojo.animateProperty({
                        node: dojo.query('.activeTile')[0].id,
                        duration: 500,
                        properties: {
                            transform: { start: 'rotate(' + (this.activeTileRotationState) * 60 + 'deg)', end: 'rotate(' + (this.aftershockTileOriginalRotationState) * 60 + 'deg)' }
                        }
                    }).play();

                    dojo.destroy('rotator1');
                }

                this.ajaxcall("/akeruption/akeruption/selectRotationTile.html",
                    {
                        lock: true,
                        id: id
                    },
                    this,
                    function (result) { },
                    function (is_error) { }
                );
            }
            
            if (this.stateName == "quake" || this.stateName == "confirmReplaceTile")
            {
                if (!isValid) {
                    this.showMessage(this.errorReplace, 'error');
                    return;
                }
                
                this.ajaxcall("/akeruption/akeruption/replaceTile.html",
                    {
                        lock: true,
                        id: id
                    },
                    this,
                    function (result) { },
                    function (is_error) { }
                );
            }

            if (this.stateName == "sinkhole")
            {
                if (!isValid) {
                    this.showMessage(this.errorNotRemovable, 'error');
                    return;
                }

                this.ajaxcall("/akeruption/akeruption/removeTile.html",
                    {
                        lock: true,
                        id: id
                    },
                    this,
                    function (result) { },
                    function (is_error) { }
                );
            }
        },

        onTileClicked: function (evt) {

            // Stop this event propagation
            dojo.stopEvent(evt);

            console.log("tile clicked");

            if (!dojo.hasClass(evt.currentTarget.id, 'active')) {
                // This is not a possible move => the click does nothing
                return;
            }


            // Get the cliqued square x and y
            // Note: id format is "tile_clickable_x_y"
            var coords = evt.currentTarget.id.split('_');
            var x = parseInt(coords[2]);
            var y = parseInt(coords[3]);

            console.log("got xy");


            //if this tile connected? If not, throw error
            var connected = false;
            for (var i = 0; i < this.connectedEmptySpaces.length && !connected; i++) {
                if (x == this.connectedEmptySpaces[i].x && y == this.connectedEmptySpaces[i].y)
                    connected = true;
            }

            console.log("connection checked");

            if (!connected) {
                this.showMessage(this.errorTileConnection, 'error');
                return;
            }

            console.log("checking failed placements");

            //have we previously failed to place a tile here?
            //todo - validate this server side also
            if (this.failedPlacements != null) {
                for (var i = 0; i < this.failedPlacements.length; i++) {
                    if (x == this.failedPlacements[i].placement_x && y == this.failedPlacements[i].placement_y) {
                        this.showMessage(this.errorTileAlreadyFailed, 'error');
                        return;
                    }
                }
            }

            //move the active tile to the specified location 
            var tile = this.activeTile;
            console.log(tile);

            this.ajaxcall("/akeruption/akeruption/placeTile.html",
                {
                    lock: true,
                    x: x,
                    y: y,
                },
                this,
                function (result) { },
                function (is_error) { }
            );
        },

        onTileConfirmed: function (evt) {

            // Stop this event propagation
            dojo.stopEvent(evt);

            console.log("tile confirming");
            //this.showMessage("Not yet implemented", 'error');
            //return;

            //validate the current tile rotation against the list of valid rotations
            //if validation fais:
            console.log(this.validRotations);
            
            if (this.validRotations.indexOf(this.activeTileRotationState) == -1) {
                this.showMessage(this.errorTrailOrGrassMatch, 'error');
                return;
            }
            
            if (this.stateName == "confirmReplaceTile") {                
                this.ajaxcall("/akeruption/akeruption/confirmReplaceTile.html",
                    {
                        lock: true,
                        rotation: this.activeTileRotationState
                    },
                    this,
                    function (result) { },
                    function (is_error) { }
                );
            }

            //aftershock
            if (this.stateName == "confirmRotateTile") {
                this.ajaxcall("/akeruption/akeruption/confirmRotateTile.html",
                    {
                        lock: true,
                        rotation: this.activeTileRotationState
                    },
                    this,
                    function (result) { },
                    function (is_error) { }
                );
            }

            if (this.stateName == "confirmTile" || this.stateName == "confirmSourceTile") {
                this.ajaxcall("/akeruption/akeruption/confirmTile.html",
                    {
                        lock: true,
                        rotation: this.activeTileRotationState
                    },
                    this,
                    function (result) { },
                    function (is_error) { }
                );
            }
        },
        
        /* Example:
        
        onMyMethodToCall1: function( evt )
        {
            console.log( 'onMyMethodToCall1' );
            
            // Preventing default browser reaction
            dojo.stopEvent( evt );

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'myAction' ) )
            {   return; }

            this.ajaxcall( "/akeruption/akeruption/myAction.html", { 
                                                                    lock: true, 
                                                                    myArgument1: arg1, 
                                                                    myArgument2: arg2,
                                                                    ...
                                                                 }, 
                         this, function( result ) {
                            
                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)
                            
                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );        
        },        
        
        */

        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your akeruption.game.php file.
        
        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );
            
            // Here, associate your game notifications with local methods
            
            // Example 1: standard notification handling
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            
            // Example 2: standard notification handling + tell the user interface to wait
            //            during 3 seconds after calling the method in order to let the players
            //            see what is happening in the game.
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
            //

            this.notifqueue.setSynchronous('stAssessDamage', 1000);
            this.notifqueue.setSynchronous('damageReport', 1000);
            
            dojo.subscribe('increaseBurn', this, "notif_increaseBurn");
            this.notifqueue.setSynchronous('increaseBurn', 1000);

            dojo.subscribe('wallStatus', this, "notif_wallStatus");
            this.notifqueue.setSynchronous('wallStatus', 1000);
            
            dojo.subscribe('moveWall', this, "notif_moveWall");
            this.notifqueue.setSynchronous('moveWall', 1000);

            dojo.subscribe('tileConfirmed', this, "notif_tileConfirmed");
            this.notifqueue.setSynchronous('tileConfirmed', 500); //delay after placing tile so everyone can keep up

            dojo.subscribe('tilePlaced', this, "notif_tilePlaced");
            this.notifqueue.setSynchronous('tilePlaced', 500); //delay after placing tile so rotator appears at the right time
            //
            dojo.subscribe('tileFailed', this, "notif_tileFailed");
            this.notifqueue.setSynchronous('tileFailed', 500); //small delay after drawing a tile

            dojo.subscribe('drawTile', this, "notif_drawTile");
            this.notifqueue.setSynchronous('drawTile', 500); //small delay after drawing a tile

            dojo.subscribe('drawCard', this, "notif_drawCard");
            this.notifqueue.setSynchronous('drawCard', 500);
            dojo.subscribe('dealCard', this, "notif_dealCard");
            this.notifqueue.setSynchronous('dealCard', 500);

            dojo.subscribe('increaseTemp', this, "notif_damage");
            this.notifqueue.setSynchronous('increaseTemp', 1000);

            dojo.subscribe('wallBuilt', this, "notif_wallBuilt");
            this.notifqueue.setSynchronous('wallBuilt', 1000);

            dojo.subscribe('wallGain', this, "notif_wallGain");
            this.notifqueue.setSynchronous('wallGain', 1500);
            
            //should arrive as animations finish
            dojo.subscribe('resourceCount', this, "notif_resourceCount");
            this.notifqueue.setSynchronous('resourceCount', 500);

            dojo.subscribe('cardDiscard', this, "notif_cardDiscard");
            this.notifqueue.setSynchronous('cardDiscard', 1000);

            dojo.subscribe('tileRemove', this, "notif_tileRemove");
            this.notifqueue.setSynchronous('tileRemove', 1000);
            
            dojo.subscribe('reshuffle', this, "notif_reshuffle");
            this.notifqueue.setSynchronous('reshuffle', 1000);

            dojo.subscribe('endGame', this, "notif_endGame");
            this.notifqueue.setSynchronous('endGame', 1000);
        },

        //from this point and below, you can write your game notifications handling methods
        notif_endGame: function (notif) {
            console.log(notif);
            this.setTilesRemaining(0);
        },

        notif_reshuffle: function(notif){
            //remove all cards from discard pile, and update card count
            dojo.empty("cardDiscard");
            this.setCardsRemaining(notif.args.cardCount);
        },
        
        notif_dealCard: function(notif){
            //this could be the notification to all players (in which case, send card to player
            //panel if this is not the active player
            //or the player notification, in which case send the card to the player
            
            //for this player
            dojo.place(
                    this.format_block('jstpl_cardBack', {
                        id: "card_temp"
                    }), "cardDraw");      
        
            if (this.player_id != notif.args.player_id) //for everyone BUT this player
            {
                this.slideToObjectAndDestroy("card_temp", "overall_player_board_"+notif.args.player_id, 1000, 500);
            }            
            else
            {
                this.slideToObjectAndDestroy("card_temp", "playerHand", 1000, 0);
            }
            
            this.setCardsRemaining(notif.args.cardCount);
        },

        notif_drawCard: function(notif){
            
            this.playerHand.addToStockWithId(notif.args.card.type, notif.args.card.id);
                      
            this.setCardsRemaining(notif.args.cardCount);
        },

        notif_cardDiscard: function(notif){
            console.log(notif);
                       
            var location = "overall_player_board_"+notif.args.player_id;
            
            if (notif.args.player_id == this.player_id) {
                if (dojo.byId("playerHand_item_"+notif.args.card_id) != null) {
                    location = "playerHand_item_"+notif.args.card_id;
                }
            }
            
            this.placeActionCard(notif.args.card_id, notif.args.card_type, location);
                               
            this.attachToNewParent("card_"+notif.args.card_id, "cardDiscard");
            
            if (notif.args.player_id == this.player_id) {
                this.playerHand.removeFromStockById(notif.args.card_id);
            }
            
            this.slideToObject("card_"+notif.args.card_id, "cardDiscard", 500, 1000).play();
        },
        
        notif_resourceCount: function(notif)
        {
            this.setPlayerResourceCount(notif.args.player_id, notif.args.resourceType, notif.args.resourceCount);
        },

        notif_tileRemove: function (notif) {
            console.log(notif);
            var tileID = "lava_" + notif.args.tile['id'];
            this.fadeOutAndDestroy(tileID);
        },

        notif_damage: function (notif) {
            console.log(notif);
            this.setPlayerTemp(notif.args.player_id, notif.args.player_position, notif.args.temp_total, true, notif.args.score);
        },

        notif_wallGain: function (notif) {
            console.log(notif);
            
            if (notif.args.tileID != null)
            {                
                dojo.place(
                        this.format_block('jstpl_wall', {
                            id: "wall_temp",
                            type: notif.args.resource_id,
                            x: this.tileWidth/2-this.wallWidth/2,
                            y: this.tileHeight/2-this.wallHeight/2,
                            rotation: 0
                }), "tile_"+notif.args.x+"_"+notif.args.y);
                
                 this.slideToObjectAndDestroy("wall_temp", "overall_player_board_"+notif.args.player_id, 1000, 500);
            }
        },

        notif_wallBuilt: function (notif) {
            console.log(notif);
            this.setPlayerResourceCount(notif.args.player_id, notif.args.resource_id, notif.args.resource_count);
            this.placeWall(notif.args.wall_type, notif.args.wall_x, notif.args.wall_y, notif.args.wall_rotation, true, notif.args.player_id);
        },

        notif_wallStatus: function (notif) {
            console.log(notif);
            if (notif.args.wall_x != null)
            {
                var wallID = "wall_" + notif.args.wall_x + "_" + notif.args.wall_y + "_" + notif.args.wall_rotation;
                console.log(wallID);
                this.fadeOutAndDestroy(wallID);
            }
        },
        
        notif_moveWall: function (notif) {
            console.log(notif);
            var oldWallID = "wall_" + notif.args.wallOld_x + "_" + notif.args.wallOld_y + "_" + notif.args.wallOld_rotation;
            var newWallID = "wall_" + notif.args.wallNew_x + "_" + notif.args.wallNew_y + "_" + notif.args.wallNew_rotation;
            this.fadeOutAndDestroy(oldWallID);
            this.placeWall(notif.args.wall_type, notif.args.wallNew_x, notif.args.wallNew_y, notif.args.wallNew_rotation, false, notif.args.player_id);
        },
        
        notif_increaseBurn: function (notif) {
            
            console.log("increaseBurn");
            console.log(notif);
            this.sourceCount--;
            var eruptionID = "eruptionTile" + (3-this.sourceCount);
                        
            var destination = 'overall_player_board_'+notif.args.player_id;
            console.log(eruptionID);
            console.log(destination);
            //this.attachToNewParent(eruptionID, destination);
            this.slideToObjectAndDestroy(eruptionID, destination, 1000, 500);
            console.log("done!");
        },

        //for this player only
        notif_tilePlaced: function (notif) {
            dojo.destroy('rotator1');
            var tile = notif.args.tile;
            //move the active tile to the specified location 
            var x = this.getXfromLocationInt(tile['location_arg']);
            var y = this.getYfromLocationInt(tile['location_arg']);
            var tileID = "lava_" + tile['id'];
            var destination = "tile_" + x + "_" + y;

            //if the tile does not exist, place it on player board
            var existingTile = dojo.byId(tileID);
            if (existingTile == null) {
                this.placeTileAt(tile, 'overall_player_board_'+this.player_id, 0);
            }

            console.log("moving " + tileID + " to " + destination);
            this.attachToNewParent(tileID, destination);
            this.slideToObject(tileID, destination, 750, 0).play();
        },
        //for other players only?
        notif_tileConfirmed: function (notif) {
            
            //for the BGA replay functionality to work, for the active player
            //we basically want to seamlessly set the rotation of this tile at the desired location
            //otherwise, replay function will not have rotation
            
            if (notif.args.player_id != this.player_id) {
                //dojo.destroy('rotator1');
                var tile = notif.args.tile;
                var x = this.getXfromLocationInt(tile['location_arg']);
                var y = this.getYfromLocationInt(tile['location_arg']);
                var startRotation = 0;
                if (notif.args.startRotation != null)
                {
                    startRotation = notif.args.startRotation;
                }
                var rotation = this.getRotationFromLocationInt(tile['location_arg']);
                var tileID = "lava_" + tile['id'];
                var destination = "tile_" + x + "_" + y;

                //if the tile does not exist, place it on player board
                var existingTile = dojo.byId(tileID);
                if (existingTile == null) {
                    this.placeTileAt(tile, 'overall_player_board_'+notif.args.player_id, 0);
                }

                console.log("moving " + tileID + " to " + destination);
                console.log(startRotation);
                console.log(rotation);
                this.attachToNewParent(tileID, destination);
                this.slideToObject(tileID, destination, 1000, 500).play();

                dojo.animateProperty({
                    node: tileID,
                    duration: 500,
                    properties: {
                        transform: { start: 'rotate('+startRotation * 60+'deg)', end: 'rotate(' + rotation * 60 + 'deg)' }
                    }
                }).play();
            }
            else
            {
                var tile = notif.args.tile;
                var tileID = "lava_" + tile['id'];
                var startRotation = 0; //this.activeTileRotationState;
                var rotation = this.getRotationFromLocationInt(tile['location_arg']);
               
                dojo.animateProperty({
                    node: tileID,
                    duration: 0,
                    properties: {
                        transform: { start: 'rotate('+startRotation * 60+'deg)', end: 'rotate(' + rotation * 60 + 'deg)' }
                    }
                }).play(); 
            }
        },

        notif_drawTile: function (notif) {
            this.activeTile = notif.args.tile;
            this.setTilesRemaining(notif.args.tileCount);
            var tile = notif.args.tile;
            var tileID = "lava_" + tile['id'];
            //alert("placing");
            this.placeTileAt(tile, 'tileStack', 0);
            //alert("attaching");
            this.attachToNewParent(tileID, 'board');
            //alert("sliding");
            this.placeNextTile(tile, notif.args.activePlayerPos, true);
        },
        notif_tileFailed: function (notif) {
            if (notif.args.player_id != this.player_id)
                return;

            this.activeTile = notif.args.tile;
            var tile = notif.args.tile;
            var tileID = "lava_" + tile['id'];

            //if the tile does not exist, place it
            var existingTile = dojo.byId(tileID);

            //either way, move it back 
            this.attachToNewParent(tileID, 'board');
            //alert("sliding");
            this.placeNextTile(tile, notif.args.activePlayerPos, true);

            //reset the rotation, since 'playTile' state will reset the js tracking it.
            dojo.animateProperty({
                node: dojo.query('.activeTile')[0].id,
                duration: 500,
                properties: {
                    transform: { start: 'rotate(' + (this.activeTileRotationState) * 60 + 'deg)', end: 'rotate(' + (0) * 60 + 'deg)' }
                }
            }).play();
        }
   });             
});
