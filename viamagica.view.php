<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * ViaMagica implementation : © Christopher J. Burke <christophjburke@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * viamagica.view.php
 *
 * This is your "view" file.
 *
 * The method "build_page" below is called each time the game interface is displayed to a player, ie:
 * _ when the game starts
 * _ when a player refreshes the game page (F5)
 *
 * "build_page" method allows you to dynamically modify the HTML generated for the game interface. In
 * particular, you can set here the values of variables elements defined in viamagica_viamagica.tpl (elements
 * like {MY_VARIABLE_ELEMENT}), and insert HTML block elements (also defined in your HTML template file)
 *
 * Note: if the HTML of your game interface is always the same, you don't have to place anything here.
 *
 */
  
  require_once( APP_BASE_PATH."view/common/game.view.php" );
  
  class view_viamagica_viamagica extends game_view
  {
    function getGameName() {
        return "viamagica";
    }    
  	function build_page( $viewArgs )
  	{		
  	    // Get players & players number
        $players = $this->game->loadPlayersBasicInfos();
        $players_nbr = count( $players );
        global $g_user;
        $current_player_id = $g_user->get_id();

        /*********** Place your code below:  ************/
        // This will programmtically iterate the player areas based on number of players
        $template = self::getGameName() . "_" . self::getGameName();
        $this->page->begin_block($template, "player");
        foreach ( $players as $player_id => $info ) {
          if ($player_id != $current_player_id) {
            $this->page->insert_block("player", array( "PLAYER_ID" => $player_id,
              "PLAYER_NAME" => $players[$player_id]['player_name'],
              "PLAYER_COLOR" => $players[$player_id]['player_color']));
          } else {
            $usecolor = $players[$player_id]['player_color'];
          }
        }

        $this->page->begin_block($template, "playerboard");
        foreach ( $players as $player_id => $info ) {
          $this->page->insert_block("playerboard", array( "PLAYER_ID" => $player_id));
        }

        $this->page->begin_block($template, "playerincantatum");
        foreach ( $players as $player_id => $info ) {
          $this->page->insert_block("playerincantatum", array( "PLAYER_ID" => $player_id));
        }

        // Translate text for myarea
        $this->tpl['MY_AREA'] = self::_("My Crystallization Area");
        if (isset($usecolor))
        {
          $this->tpl['MY_COLOR'] = $usecolor;
        } else {
          $this->tpl['MY_COLOR'] = 'black';
        }

        /*********** Do not change anything below this line  ************/
  	}
  }
  

