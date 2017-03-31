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
 * akeruption.action.php
 *
 * akeruption main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/akeruption/akeruption/myAction.html", ...)
 *
 */

class action_akeruption extends APP_GameAction
{
    // Constructor: please do not modify
   	public function __default()
    {
        if (self::isArg('notifwindow')) {
            $this->view = "common_notifwindow";
            $this->viewArgs['table'] = self::getArg("table", AT_posint, true);
        } else {
            $this->view = "akeruption_akeruption";
            self::trace("Complete reinitialization of board game");
        }
    }

    // defines your action entry points there

    public function pass()
    {
		self::setAjaxMode();     
        $this->game->pass();
		self::ajaxResponse();    
	}

    public function cardPass()
    {
        self::setAjaxMode();
        $this->game->cardPass();
        self::ajaxResponse();
    }

    public function playExtraTile()
    {
		self::setAjaxMode();     
        $this->game->playExtraTile();
		self::ajaxResponse();    
	}

    public function confirmTile()
    {
		self::setAjaxMode();     
        $rotation = self::getArg( "rotation", AT_posint, true );    
        $this->game->confirmTile($rotation);
		self::ajaxResponse();    
	}

    public function confirmRotateTile()
    {
        self::setAjaxMode();
        $rotation = self::getArg( "rotation", AT_posint, true );
        $this->game->confirmRotateTile($rotation);
        self::ajaxResponse();
    }
    
    public function confirmReplaceTile()
    {
        self::setAjaxMode();
        $rotation = self::getArg( "rotation", AT_posint, true );
        $this->game->confirmReplaceTile($rotation);
        self::ajaxResponse();
    }

    public function placeTile()
    {
		self::setAjaxMode();     
        $x = self::getArg( "x", AT_posint, true );    
        $y = self::getArg( "y", AT_posint, true );
        $this->game->placeTile($x, $y);
		self::ajaxResponse();    
	}

    public function playCard()
    {
        self::setAjaxMode();
        $cardID = self::getArg( "cardID", AT_posint, true );
        $this->game->playCard($cardID);
        self::ajaxResponse();
    }

    public function removeTile()
    {
        self::setAjaxMode();
        $tileID = self::getArg( "id", AT_posint, true );
        $this->game->removeTile($tileID);
        self::ajaxResponse();
    }

    public function selectRotationTile()
    {
        self::setAjaxMode();
        $tileID = self::getArg( "id", AT_posint, true );
        $this->game->rotationTileSelected($tileID);
        self::ajaxResponse();
    }
    
    public function replaceTile()
    {
        self::setAjaxMode();
        $tileID = self::getArg( "id", AT_posint, true );
        $this->game->replaceableTileSelected($tileID);
        self::ajaxResponse();
    }

	public function tradeCardForWall()
    {
        self::setAjaxMode();
        $cardID = self::getArg( "cardID", AT_posint, true );
        $this->game->tradeCardForWall($cardID);
        self::ajaxResponse();
    }

    public function tradeCardsForTile()
    {
        self::setAjaxMode();
        $cardIDstr = self::getArg( "cardIDstring", AT_numberlist, true );
        $this->game->tradeCardsForTile($cardIDstr);
        self::ajaxResponse();
    }

    public function wallArrowSelected()
    {
        self::setAjaxMode();
        $location = self::getArg( "location", AT_alphanum, true );
        $this->game->wallArrowClicked($location);
        self::ajaxResponse();
    }
    
    public function relocatePickWall()
    {
        self::setAjaxMode();
        $location = self::getArg( "location", AT_alphanum, true );
        $this->game->relocatePickWall($location);
        self::ajaxResponse();
    }
    
    public function relocatePlaceWall()
    {
        self::setAjaxMode();
        $location = self::getArg( "location", AT_alphanum, true );
        $this->game->relocatePlaceWall($location);
        self::ajaxResponse();
    }

    public function destroyWall()
    {
        self::setAjaxMode();
        $location = self::getArg( "location", AT_alphanum, true );
        $this->game->wallDestroyClicked($location);
        self::ajaxResponse();
    }

    public function wallMaterialSelected()
    {
        self::setAjaxMode();
        $id = self::getArg("buttonID", AT_alphanum, true);
        $this->game->wallMaterialSelected($id);
        self::ajaxResponse();
    }

    /*
    
    Example:
  	
    public function myAction()
    {
        self::setAjaxMode();     

        // Retrieve arguments
        // Note: these arguments correspond to what has been sent through the javascript "ajaxcall" method
        $arg1 = self::getArg( "myArgument1", AT_posint, true );
        $arg2 = self::getArg( "myArgument2", AT_posint, true );

        // Then, call the appropriate method in your game logic, like "playCard" or "myAction"
        $this->game->myAction( $arg1, $arg2 );

        self::ajaxResponse( );
    }
    
    */

  }
  

