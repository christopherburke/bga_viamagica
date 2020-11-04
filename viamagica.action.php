<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * ViaMagica implementation : © Christopher J. Burke <christophjburke@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 * 
 * viamagica.action.php
 *
 * ViaMagica main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/viamagica/viamagica/myAction.html", ...)
 *
 */
  
  
  class action_viamagica extends APP_GameAction
  { 
    // Constructor: please do not modify
   	public function __default()
  	{
  	    if( self::isArg( 'notifwindow') )
  	    {
            $this->view = "common_notifwindow";
  	        $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
  	    }
  	    else
  	    {
            $this->view = "viamagica_viamagica";
            self::trace( "Complete reinitialization of board game" );
      }
  	} 
  	
    // Define your action entry points there
    // Choosing the 3 cards to keep at beginning of game
    // What is sent are actually the cards to discard
    public function chooseInitCards() {
      self::setAjaxMode();
      // Get card id number list string argument
      $card_ids_raw = self::getArg( "unselected_cards", AT_numberlist, true );
      if ( $card_ids_raw == '') {
        $card_ids = array();
      } else {
        $card_ids = explode( ',', $card_ids_raw );
      }
      $result = $this->game->chooseInitCards( $card_ids );
      self::ajaxResponse();
    }

    public function placeGem() {
      self::setAjaxMode();
      // Get card id number list string argument
      $spot_id = self::getArg( "spot_id", AT_alphanum, true );
      $gem_id = self::getArg( "gem_id", AT_alphanum, true);
      $normal_turn = self::getArg( "normal_turn", AT_posint, true);
      //self::error('spt: '.$spot_id.' '.$gem_id.' '.' nt: '.$normal_turn.'|');
      if ($normal_turn == 1)
      {
        //self::error('Normal turn');
        $result = $this->game->placeGem( $spot_id, $gem_id );
        //self::error('Normal turn done');
      } else {
        //self::error('Bonus gem place');
        $result = $this->game->placeGemEx($spot_id, $gem_id );
        //self::error('Bonus gem place done');
      }
      self::ajaxResponse();
    }
    
    public function completeCard() {
      self::setAjaxMode();
      $card_id = self::getArg( "card_id", AT_posint, true);
      $result = $this->game->chooseNewCard( $card_id );
      self::ajaxResponse();
    }

    public function resolvePortalCount() {
      self::setAjaxMode();
      $accept = self::getArg( "accept", AT_posint, true);
      $result = $this->game->resolvePortalCount( $accept );
      self::ajaxResponse();
    }

    public function newCardBonus() {
      self::setAjaxMode();
      $card_id = self::getArg( "card_id", AT_posint, true);
      $result = $this->game->chooseNewCardBonus( $card_id );
      self::ajaxResponse();
    }

    public function completePortalBonus() {
      self::setAjaxMode();
      $card_id = self::getArg( "card_id", AT_posint, true);
      $result = $this->game->completePortalBonus( $card_id );
      self::ajaxResponse();
    }

  }
  

