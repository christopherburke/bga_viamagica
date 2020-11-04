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
  * viamagica.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class ViaMagica extends Table
{
	function __construct( )
	{
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();
        
        // Using global variables to store objective cards that are complete for every player
        // There are max 6 players
        // Store player id and card id for current card to resolve
        // Store the current Animus Catcher player id
        // Store gem marker moves here as temp storage so they can't be spied on until state is done
        //    these are p#_gemn, p#_sptn, & p#_cid
        // Store choosing initial cards the cards at p#_cid, p#_cid1, and p#_cid2 are chosen for discard
        // Store portal card bonus gem placements with gem_id, gem_type, gem_count, gem_strt_count
        self::initGameStateLabels( array( 
            "p1_id" => 10,
            "p1_cid" => 11,
            "p2_id" => 12,
            "p2_cid" => 13,
            "p3_id" => 14,
            "p3_cid" => 15,
            "p4_id" => 16,
            "p4_cid" => 17,
            "p5_id" => 18,
            "p5_cid" => 19,
            "p6_id" => 20,
            "p6_cid" => 21,
            "res_id" => 22,
            "res_cid" => 23,
            "cur_catcher" => 24,
            "new_catcher" => 25,
            "p1_gemn" => 26,
            "p2_gemn" => 27,
            "p3_gemn" => 28,
            "p4_gemn" => 29,
            "p5_gemn" => 30,
            "p6_gemn" => 31,
            "p1_sptn" => 32,
            "p2_sptn" => 33,
            "p3_sptn" => 34,
            "p4_sptn" => 35,
            "p5_sptn" => 36,
            "p6_sptn" => 37,
            "p1_cid1" => 38,
            "p2_cid1" => 39,
            "p3_cid1" => 40,
            "p4_cid1" => 41,
            "p5_cid1" => 42,
            "p6_cid1" => 43,
            "p1_cid2" => 44,
            "p2_cid2" => 45,
            "p3_cid2" => 46,
            "p4_cid2" => 47,
            "p5_cid2" => 48,
            "p6_cid2" => 49,
            "gem_id" => 50,
            "gem_type" => 51,
            "gem_count" => 52,
            "gem_strt_count" => 53,
            "orig_n_player" => 54,
            "open_portal_round_strt" => 55,
            "show_drawn_tokens" => 100
        ) );
        
        // Initialize the databases
        $this->anitokens = self::getNew( "module.common.deck" );
        $this->anitokens->init( "anitokens" );
        $this->portcards = self::getNew( "module.common.deck" );
        $this->portcards->init( "portcards ");
        // portal card discards are always reshuffled back to deck
        $this->portcards->autoreshuffle = true;
        $this->gems = self::getNew( "module.common.deck" );
        $this->gems->init( "gems" );
	}
	
    protected function getGameName( )
    {
		// Used for translations and stuff. Please do not modify.
        return "viamagica";
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
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];
 
        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
        self::reloadPlayersBasicInfos();
                
        /************ Start the game initialization *****/

        
        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)
        self::initStat ('table',"total_tokens_drawn",0);
        self::initStat ('table',"air_tokens_drawn",0);
        self::initStat ('table',"water_tokens_drawn",0);
        self::initStat ('table',"earth_tokens_drawn",0);
        self::initStat ('table',"life_tokens_drawn",0);
        self::initStat ('table',"fire_tokens_drawn",0);
        self::initStat ('table',"shadow_tokens_drawn",0);
        self::initStat ('table',"wildcard_tokens_drawn" ,0);
        self::initStat ('table',"total_cards_avail",0);
        self::initStat ('table',"green_cards_avail",0);
        self::initStat ('table',"yellow_cards_avail",0);
        self::initStat ('table',"purple_cards_avail",0);
        self::initStat ('table',"blue_cards_avail",0);

        self::initStat ('player',"total_crys_play",0);
        self::initStat ('player',"air_crys_play",0);
        self::initStat ('player',"water_crys_play",0);
        self::initStat ('player',"earth_crys_play",0);
        self::initStat ('player',"life_crys_play",0);
        self::initStat ('player',"fire_crys_play",0);
        self::initStat ('player',"shadow_crys_play",0);
        self::initStat ('player',"pass_crys_play",0);
        self::initStat ('player',"total_cards_open",0);
        self::initStat ('player',"green_cards_open",0);
        self::initStat ('player',"yellow_cards_open",0);
        self::initStat ('player',"purple_cards_open",0);
        self::initStat ('player',"blue_cards_open",0);
        self::initStat ('player',"total_card_points",0);
        self::initStat ('player',"green_card_points",0);
        self::initStat ('player',"yellow_card_points",0);
        self::initStat ('player',"purple_card_points",0);
        self::initStat ('player',"blue_card_points",0);

        // Setup the initial game situation here

        // Add the animus tokens to the DB
        // The token names and number of each token is defined in materials $this->token_types 
        // DB Name 'anitokens'
        //  type - [string] name
        //  type_arg - [int] token id 1-7; 1=Air, 2=Water, 3=Earth, 4=Life, 5=Fire, 6=Shadow, 7=Wildcard
        // location - [string] 'deck'=In bag, 'display'=On display, 'discard'=Discarded
        // location_arg not used
	    $tokens = array();
	    foreach ( $this->token_types as $token_id => $token_values ) {
	        $tokens [] = array('type' => $token_values['name'], 'type_arg' => $token_id, 'nbr' => $token_values['number']);
	    }      
	    // creat Deck and Shuffle Animus Tokens
        $this->anitokens->createCards( $tokens , 'deck');
        $this->anitokens->shuffle('deck');
       
        // Add the portal cards to the DB
        // There are 40 unique cards each card has 2 copies
        // The card properties are defined in materials $this->port_cards
        // To keep track of cards, each card shows up twice in materials
        // DB Name 'portcards'
        //   card_id [int] 1-80 Each card has unique running number
        //   type - [int] 1-40 one of the 40 unique card numbers
        //   type_arg - [int]  unused other than sorting in deck and order cards were added to players done location
        //   location - [str] 'deck'=face down stack; 'portalstock'=unassigned cards that can be selected
        //                      'hand'=players active area; 'done'=players opened/done portals
        //   location_arg - [int] player_id if 'hand' or 'done' location otherwise not used
        $cards = array();
        foreach ($this->port_cards as $cid => $card_values )
        {
            $cards = array();
            $cards [] = array('type' => $card_values['card_type'], 'type_arg' => $cid, 'nbr' => 1);
            // Create Deck of Portal Cards one at a time to make sure they get card_id in this order
            $this->portcards->createCards( $cards, 'deck' );
        }
        // Shuffle cards
        $this->portcards->shuffle('deck');
        // Add 5 cards to purchase area
        $this->portcards->pickCardsForLocation(5, 'deck', 'portalstock');
        // Add 6 cards to every player's area
        // Get player information
        $players = self::loadPlayersBasicInfos();
        foreach ($players as $player_id => $player){
            $cards = $this->portcards->pickCards(6, 'deck', $player_id);
        }
        // Fix the situation where a player is dealt the same type of card swap with purchase area
        // **TEST by forcing duplicate type card in hand
        //$this->TEST_fix_duplicate_cards_dealt();
        $this->fix_duplicate_cards_dealt($this->portcards);

        // Add these 5 portal card to the stats
        self::setStat(5, 'total_cards_avail');
        $portCardData = $this->portcards->getCardsInLocation('portalstock');
        $portCardIds = array_column($portCardData, 'id');
        $color_map = array('green'=>'green_cards_avail', 'yellow'=>'yellow_cards_avail', 
                            'purple'=>'purple_cards_avail', 'blue'=>'blue_cards_avail');
        foreach ($portCardIds as $ii=>$card_id) {
            $card_color = $this->port_cards[$card_id]['card_color'];
            self::incStat(1, $color_map[$card_color]);
        }        


        // Create Deck for gems
        // Each player starts with 7 gems
        // DB name 'gems'
        // type - [int] given as player_id to identify owner
        // type_arg - [int] enumerates the players gems
        // location - [int] 0=in player zone; #>=1 is the card_id where it is located
        // location_arg - [int] # is spot position on the card
        foreach ($players as $player_id => $player){
            for ($ii=0; $ii<7; $ii++) {
                $markers  = array(array('type' => $player_id, 'type_arg' => $ii, 
                                'nbr' => 1));
                $this->gems->createCards($markers, 0, $ii);
            }
        }

        // Initialize the global variables 
        $cnt = 0;
        foreach ($players as $player_id => $player){
            $pnum = $cnt +1;
            //self::error('Setting '.$pnum.' to id '.$player_id);
            self::setGameStateInitialValue( 'p'.$pnum.'_id', $player_id);
            self::setGameStateValue( 'p'.$pnum.'_id', $player_id);
            self::setGameStateInitialValue( 'p'.$pnum.'_cid', 0);
            self::setGameStateValue( 'p'.$pnum.'_cid', 0);
            self::setGameStateInitialValue( 'p'.$pnum.'_gemn', -1);
            self::setGameStateValue( 'p'.$pnum.'_gemn', -1);
            self::setGameStateInitialValue( 'p'.$pnum.'_sptn', -1);
            self::setGameStateValue( 'p'.$pnum.'_sptn', -1);
            self::setGameStateInitialValue( 'p'.$pnum.'_cid1', -1);
            self::setGameStateValue( 'p'.$pnum.'_cid1', -1);
            self::setGameStateInitialValue( 'p'.$pnum.'_cid2', -1);
            self::setGameStateValue( 'p'.$pnum.'_cid2', -1);
            $cnt++;
        }
        self::setGameStateInitialValue( 'res_id', 0);
        self::setGameStateValue( 'res_id', 0);
        self::setGameStateInitialValue( 'res_cid', 0);
        self::setGameStateValue( 'res_cid', 0);
        self::setGameStateInitialValue( 'cur_catcher', 0);
        self::setGameStateInitialValue( 'new_catcher', 0);
        // Set the initial Catcher to the first player_id
        $player_order = $this->getNextPlayerTable();
        self::setGameStateValue( 'cur_catcher', $player_order[0]);
        self::setGameStateValue( 'new_catcher', 0);
        self::setGameStateInitialValue( 'gem_id', 0);
        self::setGameStateValue( 'gem_id', 0);
        self::setGameStateInitialValue( 'gem_type', 0);
        self::setGameStateValue( 'gem_type', 0);
        self::setGameStateInitialValue( 'gem_count', 0);
        self::setGameStateValue( 'gem_count', 0);
        self::setGameStateInitialValue( 'gem_strt_count', 0);
        self::setGameStateValue( 'gem_strt_count', 0);
        self::setGameStateInitialValue( 'orig_n_player', count($players));
        self::setGameStateValue( 'orig_n_player' , count($players));
        self::setGameStateInitialValue( 'open_portal_round_strt', 1);
        self::setGameStateValue( 'open_portal_round_strt', 1);
        // TEST new catcher scenario
        //self::setGameStateValue( 'new_catcher', 1);

        // ***START TESTING OF INTERFACE
        // These deck calls are only to populate the stock and zones on the interface for testing
        // Make sure these are commented out before starting an actual game
        // Put a token in display
        //$this->anitokens->pickCardForLocation( 'deck', 'display' );
        // Move several gems to cards in DB
        // Get information about portal cards in the players' active areas
        //$cards_out = self::getObjectListFromDB( "SELECT card_id, card_type_arg, card_location_arg FROM portcards WHERE card_location = 'hand'");
        //$debug_str = 'gem db keys: '.implode('|',array_keys($cards_out[0]));
        //$usegem = array();
        //foreach ($players as $player_id => $player)
        //{
        //    $usegem[$player_id] = 0;
        //}
        //foreach ($cards_out as $idx => $cardData)
        //{
        //    $player_id = $cardData['card_location_arg'];
        //    $card_id = $cardData['card_id'];
        //    //$this->debug_with_badsql('pid: '.$player_id.' cid: '.$card_id);
        //    $curgem = $this->gems->getCardsOfType($player_id, $usegem[$player_id]);
        //    $usegem[$player_id]++;
        //    foreach($curgem as $jdx => $tmp)
        //    {
        //        //$debug_str = 'gem db keys: '.$jdx.' '.implode('|',array_keys($tmp));
        //        //$this->debug_with_badsql( $debug_str );
        //        $this->gems->moveCard($tmp['id'], $card_id, 0);
        //    }
        //    //$this->gems->moveCard($curgem, $card_id, 0);
        //}


        // ***END TESTING OF INTERFACE


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
        $result = array();
    
        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!
        $current_stateData = $this->gamestate->state();

        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score, player_color FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );
  
        // Gather all information about current game situation (visible by player $current_player_id).
 	    // Get information about token in display
        $result['tokenDisplay'] = self::getObjectListFromDB( "SELECT card_type, card_type_arg, card_id 
                                                                FROM anitokens
                                                                WHERE card_location = 'display'" );

        // Here is the option whether to show the already drawn tokens
        if ($this->gamestate->table_globals[100] == 1)
        {
            $result['tokenDiscard'] = self::getObjectListFromDB( "SELECT card_type, card_type_arg, card_id
                                                                FROM anitokens
                                                                WHERE card_location = 'discard'");
            $result['showTokenDiscardOption'] = 1;
        } else {
            $result['showTokenDiscardOption'] = 0;
        }
        // Get information about number of tokens in deck
        $tmp = self::getObjectListFromDB( "SELECT card_type FROM anitokens WHERE card_location = 'deck'" );
        $result['tokenDeckCount'] = count($tmp);
        // Catcher name
        $players = self::loadPlayersBasicInfos();
        $result['catchername'] = $players[self::getGameStateValue('cur_catcher')]['player_name'];
        $result['catcherid'] = self::getGameStateValue('cur_catcher');
        // Get information about portal cards in the purchase area
        $result['portalstock'] = self::getObjectListFromDB( "SELECT card_id, card_location, card_location_arg
                                                                FROM portcards
                                                                WHERE card_location = 'portalstock'");
        // Get information about portal cards in the players' active areas
        // If we are in the initial choose starting card state we have to see if we need to fill in 
        //  uncommited information from global variables
        $pcData = self::getObjectListFromDB( "SELECT card_id, card_location, card_location_arg
                                    FROM portcards
                                    WHERE card_location = 'hand'");
        if ($current_stateData['name'] == 'chooseInitCards')
        {
            $pcData = $this->filterOutInitDiscards($pcData, $current_player_id);
        }
        $result['playeractivearea'] = $pcData;

        // Get information about done portal cards
        $doneCards = self::getObjectListFromDB( "SELECT card_id, card_location, card_location_arg, card_type_arg
                                    FROM portcards
                                    WHERE card_location = 'done'");
        $result['playerdonearea'] = $doneCards;
        // Get information about gems
        $gemDBData = self::getObjectListFromDB( "SELECT card_type, card_type_arg, card_location, card_location_arg FROM gems");
        if ($current_stateData['name'] == 'placeGem')
        {
            // Check to see if player has submitted gem position info that is not committed
            $gemDBData = $this->uncommitedGemFill($gemDBData, $current_player_id);
        }
        //foreach ($gemDBData as $ii=>$data)
        //{
        //    self::error('pid: '.$data['card_type'].' itr: '.$data['card_type_arg'].' cid: '.$data['card_location'].' spt: '.$data['card_location_arg'].'|');
        //}
        $result['gemData'] = $gemDBData;
        // Get information about rewards
        $result['rewardData'] = self::getObjectListFromDB( "SELECT bonus_type_id, player, val1 FROM rewards");
        
        // load the tooltip data for cards
        $toolTipData = array();
        foreach ($this->port_cards as $ii=>$data)
        {
            $toolTipData[$ii] = array('card_type_str'=>$data['card_type_str'], 
                                    'card_point_str'=>$data['card_point_str'],
                                'card_effect_str'=>$data['card_effect_str']);
        }
        $result['toolTipData'] = $toolTipData;

        // load the tooltip data for rewards
        $rewardToolTipData = array();
        for ($ii=30; $ii<=39; $ii++) 
        {
            $type = $this->bonus_data[$ii]['card_bonus_type'];
            $val1 = $this->bonus_data[$ii]['val1'];
            $val2 = $this->bonus_data[$ii]['val2'];
            $tooltip_str = $this->bonus_data[$ii]['tooltip_str'];
            $rewardToolTipData[$type][$val1] = $tooltip_str;
        }
        $result['rewardToolTipData'] = $rewardToolTipData;

        // give info about card if we are mid resolving one
        $cardResolveData = array();
        $cardResolveData['res_id'] = self::getGameStateValue('res_id');
        $cardResolveData['res_cid'] = self::getGameStateValue('res_cid');
        $result['cardResolveData'] = $cardResolveData;

        // Get player counts of portals open and their color
        if (count($doneCards) > 0)
        {
            $portal_Data = $this->calculate_EOG_PortalBonus($doneCards);
            //self::error('port counts '.implode('|', array_keys($portal_Data[1])).'|');
            $result['portalCounts'] = $portal_Data[1];
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
        // compute and return the game progression
        //self::error('Entering getGameProgression');
        // Get information about done portal cards 
        $compData = self::getObjectListFromDB( "SELECT card_id, card_location, card_location_arg
                                        FROM portcards
                                        WHERE card_location = 'done'");
        $cardPIDs = array_column($compData, 'card_location_arg');
        $cardCounts = array_values(array_count_values($cardPIDs));
        if (count($cardCounts) > 0)
        {
            $maxCardCounts = max($cardCounts);
        } else {
            $maxCardCounts = 0.0;
        }
        // If there are 7 cards the game is 100% complete

        return intval(round(floatval($maxCardCounts)/7.0 * 100.0));
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    /*
        In this space, you can put any utility methods useful for your game logic
    */
    function fix_duplicate_cards_dealt($cards)
    {
        $players = self::loadPlayersBasicInfos();
        // Cards in purchase area
        $purchase_cards = $cards->getCardsInLocation('portalstock');
        $purchase_cards_types = array_column($purchase_cards, 'type');
        $purchase_cards_ids = array_column($purchase_cards, 'id');
//        $debug_str = 'var db values: '.implode('|',array_values($purchase_cards_types)).' '.implode('|',array_values($purchase_cards_ids));
//        $this->debug_with_badsql($debug_str);
        foreach ($players as $player_id => $player){
            $players_cards = $cards->getCardsInLocation('hand', $player_id);
            $players_cards_types = array_column($players_cards, 'type');
            $players_cards_ids = array_column($players_cards, 'id');
//            $debug_str = 'var db values: '.implode('|',array_values($players_cards_types)).' '.implode('|',array_values($players_cards_ids));
//            $this->debug_with_badsql($debug_str);
            // Go through cards trying to find if there are any duplicates
            while(!(empty($players_cards_types)) && count($players_cards_types)>=1)
            {
                $curtype = array_pop($players_cards_types);
                $curid = array_pop($players_cards_ids);
                if (in_array($curtype, $players_cards_types)) // Duplicate detected in players hand
                {
//                    $debug_str = 'var db values: '.implode('|',array_values($players_cards_types)).' '.$curtype;
//                    $this->debug_with_badsql($debug_str);
                    // Look for replacement from portal stock
                    $foundReplace = false;
                    $idx = 0;
                    while(!$foundReplace) {
                        if (!in_array($purchase_cards_types[$idx], $players_cards_types)) { // Found replacement type from purchase area that is not in players hand 
                            $foundReplace = true;
                            // Swap cards
                            $cards->moveCard($purchase_cards_ids[$idx], 'hand', $player_id);
                            $cards->moveCard($curid, 'portalstock');
                            // Update cards in purchase area
                            $purchase_cards = $cards->getCardsInLocation('portalstock');
                            $purchase_cards_types = array_column($purchase_cards, 'type');
                            $purchase_cards_ids = array_column($purchase_cards, 'id');                    
                        } else {
                            $idx++;
                        }

                    }
                }

            }
        }

    }

    // Return Token that is in tokenDisplay
    function getTokenDisplay()
    {
        $result = self::getObjectFromDB( "SELECT card_type, card_type_arg, card_id 
				  			    FROM anitokens
							    WHERE card_location = 'display'" );
        return $result;
    }
    //Return the token type that is currently in display
    function getCurrentToken()
    {
        //self::error('Entering getCurrentToken');
        $tokenData = $this->getTokenDisplay();
        //self::error('Got Token Data');
        //See if there is token present
        if (isset($tokenData)) {
            return $tokenData['card_type_arg'];
        } else {
            return 0;
        }
    }

    // Find the users index using player_id in the global game state values
    function findGameStateIdx($player_id)
    {
        // Find this plyer in the global card list
        $hasplayer = false;
        $cnt = 1;
        while (!$hasplayer && $cnt<7)
        {
            //self::error('Global value pid: '.self::getGameStateValue('p'.$cnt.'_id').' want id: '.$player_id.'|');
            if (self::getGameStateValue('p'.$cnt.'_id') == intval($player_id))
            {
                //self::error('Found global entry|');
                $hasplayer = true;
            } else {
                //self::error('Ids did not agree|');
                $cnt++;
            }
        }
        // if a spectator is playing then we need to protect against this
        if (!$hasplayer)
        {
            $cnt=1;
        }
        return $cnt;
    }

    // Reset an entry in global variables
    function resetGameState($entry_str, $value)
    {
        for ($ii=1; $ii<=6 ;$ii++)
        {
            self::setGameStateValue('p'.$ii.$entry_str, $value);
        }
    }
    // Get an array filled with each players global variable type
    function getPlayerArrayFromGameState($entry_str, $nplayers)
    {
        $tmp = [];
        for ($ii=1; $ii<=$nplayers; $ii++)
        {
            $tmp [] = self::getGameStateValue('p'.$ii.$entry_str);
        }
        return $tmp;
    }
    // Fill in gem information if this player has uncommitted gem placement information
    // This protects users move from being visible on client side to other players until state is done
    function uncommitedGemFill($gemDBData, $player_id)
    {
        //self::error('Entering uncommitedGemFill');
        $idx = $this->findGameStateIdx($player_id);
        $gemnkey = 'p'.$idx.'_gemn';
        $gemnval = self::getGameStateValue($gemnkey);
        if ($gemnval != -1)
        {
            // player has gem position info that needs filling in
            $cidkey = 'p'.$idx.'_cid';
            $sptkey = 'p'.$idx.'_sptn';
            $cur_cid = self::getGameStateValue($cidkey);
            $cur_sptn = self::getGameStateValue($sptkey);
            //self::error('PID: '.$player_id.' Gemn: '.$gemnval.' CID: '.$cur_cid.' Sptn: '.$cur_sptn.' filled into gemDB');
            // Iterate through gem DB until we found this marker for player
            foreach ($gemDBData as $ii => $gemData)
            {
                // card_type is the player id; card_type_arg is the gems number which needs to match $gemnval
                if (($gemData['card_type'] == $player_id) && ($gemData['card_type_arg'] == $gemnval))
                {
                    // Found gem marker update position info
                    //self::error('Changing Gem Location');
                    $gemDBData[$ii]['card_location'] = $cur_cid;
                    $gemDBData[$ii]['card_location_arg'] = $cur_sptn;
                    break;
                }
            }
        }
        return $gemDBData;
    }
    // During the choose initial cards we need to protect the data from other users
    // by placing the cards discarded in global variables.  Only filter out cards 
    // for a player that has already decided and is inactive.
    function filterOutInitDiscards($pcData, $player_id)
    {
        //self::error('Entering filterOutInitDiscards');
        $idx = $this->findGameStateIdx($player_id);
        $cid1key = 'p'.$idx.'_cid1';
        $cid1 = self::getGameStateValue($cid1key);
        // Check if there is a card discard stored here
        if ($cid1 != -1)
        {
            // Cards to discard are stored here
            $cidkey = 'p'.$idx.'_cid';
            $cid = self::getGameStateValue($cidkey);
            $cid2key = 'p'.$idx.'_cid2';
            $cid2 = self::getGameStateValue($cid2key);
            //self::error('PID: '.$player_id.' CID: '.$cid.' CID1: '.$cid1.' CID2: '.$cid2.' need removing from active cards');
            $discards = [$cid, $cid1, $cid2];
            // iterate through cards and change location for this players cards that 
            foreach ($pcData as $ii => $data)
            {
                //self::error('Cid: '.$data['card_id'].' loc: '.$data['card_location_arg'].'|');
                // player_id is stored in card_location_arg; reset this to -1 such that the js won't pick it up as beloging to player
                if (in_array($data['card_id'], $discards) && ($data['card_location_arg'] == $player_id))
                {
                    // Found card to discard
                    //self::error('Removing cid: '.$data['card_id']);
                    $pcData[$ii]['card_location_arg'] = -1;
                }
            }
        }
        return $pcData;
    }

    // Return which Gem places are available on portal cards for all players
    // allows to return results for a player_id and forcing a token type
    function getAvailGemPlaces($filter_player_id=NULL, $force_token_type=NULL)
    {
        if (is_null($force_token_type)) // default behavior is check token display
        {
            $tokenData = self::getTokenDisplay();
            $token_array = array($tokenData['card_type_arg']);
        } else { // override the token display with specified type
            $token_array = array($force_token_type);
        }
        // If it is the Joker tokenall tokens can be played on
        if ($token_array[0]==7)
        {
            $token_array = array(1,2,3,4,5,6);
        }

        //self::error('TOKEN_ARRAY Keys: '.implode("|",array_keys($tokenData)));
        //self::error('TOKEN_ARRAY VALUES: '.implode("|", array_values($tokenData)));
        
        // get object cards in the players active area
        if (is_null($filter_player_id))  // default is return results for all players
        {
            $objcardData = self::getObjectListFromDB( "SELECT card_id, card_location, card_location_arg
                                                    FROM portcards
                                                    WHERE card_location = 'hand'" );
        } else {  // filter to a single player_id
            $objcardData = self::getObjectListFromDB( "SELECT card_id, card_location, card_location_arg
                                                    FROM portcards
                                                    WHERE card_location = 'hand' AND card_location_arg = '".$filter_player_id."'" );
        }

        // Handle the potential for a player to have a wild card bonus on portal cards
        // This is only done if we are in the default placeGem state with $filter_player_id==null and $force_token_type==null
        // default is to have the same $token_array for all players
        $players = self::loadPlayersBasicInfos();
        $players_token_array = array();
        foreach ($players as $player_id => $player)
        {
            $players_token_array[$player_id] = $token_array;
        }
        if ($filter_player_id == null && $force_token_type == null)
        {  // Each player can have a differnet $token_array in there are wild card bonuses
            // Query the rewards DB for bonus types == 8
            $bonusData = self::getObjectListFromDB( "SELECT player, val1
                                                    FROM rewards
                                                    WHERE bonus_type_id = 8" );
            foreach ($bonusData as $ii => $data)
            {
                // Get player id
                $pid = $data['player'];
                $wild_type = $data['val1'];
                if (in_array($wild_type, $players_token_array[$pid]))  // The wild bonus matches the token convert the players token array to wild
                {
                    $players_token_array[$pid] = array(1,2,3,4,5,6);
                }
            }
        } 
        //self::error('obj card DB Length: '.count($objcardData));
        // get gem types on cards and store in dict array
        $objcardDataDict = array();
        if (!empty($objcardData))
        {
            foreach ($objcardData as $idx => $Data)
            {
                $card_id = $Data['card_id'];
                $player_id = $Data['card_location_arg'];
                $cardPosTypes = array($this->port_cards[$card_id]['card_tk_1'], 
                            $this->port_cards[$card_id]['card_tk_2'], 
                            $this->port_cards[$card_id]['card_tk_3'],
                            $this->port_cards[$card_id]['card_tk_4'],
                            $this->port_cards[$card_id]['card_tk_5'],
                            $this->port_cards[$card_id]['card_tk_6']);
                foreach ($cardPosTypes as $pos => $typ)
                {
                    if (in_array($typ, $players_token_array[$player_id]))
                    {   
                        //self::error('Pos Avail: '.$card_id.' Pos: '.$pos.' PID: '.$player_id );
                        $objcardDataDict[] = array('cid' => $card_id, 'pos'=>$pos, 'pid'=>$player_id);
                    }
                }
            }
        }

        // Get gem data
        $gemData = self::getObjectListFromDB( "SELECT card_type, card_type_arg, card_location, card_location_arg FROM gems");

        // Remove from card spots that are already occupied by gems
        $gemCIDs = array_column($gemData, 'card_location');
        $gemCSpots = array_column($gemData, 'card_location_arg');
        $gemStrs = self::build_gemStr($gemCIDs, $gemCSpots);
        $objCIDs = array_column($objcardDataDict, 'cid');
        $objCSpots = array_column($objcardDataDict, 'pos');
        $objStrs = self::build_gemStr($objCIDs, $objCSpots);

        // Below unset() is unsafe for sending to the js side
        // if the first element is removed then the length of the array becomes undefined on the js side.
        // setup a new fresh array and only keep the good ones 
        $newobjcardDataDict = [];
        for ($ii=0; $ii<count($objStrs); $ii++){
            if (in_array($objStrs[$ii],$gemStrs)) {
                //self::error('Gem occupying: '.$objStrs[$ii]);
                //unset($objcardDataDict[$ii]);
            } else {
                $newobjcardDataDict[] = $objcardDataDict[$ii];
            }
        }

        // We also have to filter out spots on a completed card during the bonus gem placement
        // We've already remove the gems from the completed card so they look empty and available, but they shouldn't
        $look_for_player_id = self::getGameStateValue('res_id');
        $look_for_cid = self::getGameStateValue('res_cid');
        $newobjcardDataDict2 = $newobjcardDataDict;
        if ($look_for_player_id > 0 & $look_for_cid > 0)
        {
            $objCIDs = array_column($newobjcardDataDict, 'cid');
            $newobjcardDataDict2 = [];
            for ($ii=0; $ii<count($objCIDs); $ii++){
                if ($objCIDs[$ii] == $look_for_cid) {
                    //self::error('Gem occupying: '.$objCIDs[$ii]);
                } else {
                    $newobjcardDataDict2[] = $newobjcardDataDict[$ii];
                }
            }
    
        }
        //self::error('NcardsOrig: '.count($objcardDataDict).' NCardsAfterOccupied: '.count($newobjcardDataDict2));
        return $newobjcardDataDict2;
    }

    function build_gemStr($cids, $cspots)
    {
        $strs = array();
        for ($ii=0; $ii<count($cids); $ii++){
            $strs [] = $cids[$ii].'_'.$cspots[$ii];
        }
        return $strs;
    }

    function getPlayerGemCount()
    {
        //self::error('Entering getPlayerGemCount');
        // Get gem data
        $gemData = self::getObjectListFromDB( "SELECT card_type, card_type_arg, card_location, card_location_arg FROM gems");
        $gemPlayerIds = array_column($gemData, 'card_type');
        $gemPlayerCounts = array_count_values($gemPlayerIds);
        return $gemPlayerCounts;
    }

    function parseId($instr)
    {
        $strarr = explode('_', $instr);
        return array($strarr[2], $strarr[3]);
    }

    // Get the locations/card_ids for gems in DB
    function get_gem_card_locations($pid_array, $gemn_array)
    {
        //self::error('Entering get_gem_card_location');
        $cid_array = array();
        foreach ($pid_array as $ii=>$data)
        {
            if ($gemn_array[$ii] != -1)
            {
                $gemMarker = $this->gems->getCardsOfType($pid_array[$ii], $gemn_array[$ii]);
                $gemMarker = array_values($gemMarker);
                $cid_array [] = $gemMarker[0]['location'];
            } else {
                $cid_array [] = 0;
            }
        }
        return $cid_array;
    }

    function gather_card_info($card_id)
    {
        //self::error('Entering gather_card_info');
        // Get the  bonus number of card
        $card_bonus = $this->port_cards[$card_id]['card_bonus'];
        $card_bonus_type = -1;
        if ($card_bonus > 0)
        {
            // Get the bonus_type of card
            $card_bonus_type = $this->bonus_data[$card_bonus]['card_bonus_type'];
        }
        // Card color
        $card_color = $this->port_cards[$card_id]['card_color'];
        // Portal color converted to integer type
        $portal_name_to_id['green'] = 1;
        $portal_name_to_id['yellow'] = 2;
        $portal_name_to_id['purple'] = 3;
        $portal_name_to_id['blue'] = 4;
        $portal_type_id = $portal_name_to_id[$card_color];
        // Animus type integer for each spot
        $card_animus_array = array(
            $this->port_cards[$card_id]['card_tk_1'],
            $this->port_cards[$card_id]['card_tk_2'],
            $this->port_cards[$card_id]['card_tk_3'],
            $this->port_cards[$card_id]['card_tk_4'],
            $this->port_cards[$card_id]['card_tk_5'],
            $this->port_cards[$card_id]['card_tk_6'],
        );
        // Portal bonus info
        $portal_bonus_type = 0;
        $portal_each_score = 0;
        if ($card_bonus_type == 1)
        {
            $portal_bonus_type = $this->bonus_data[$card_bonus]['val1']; // Color type this card rewards a bonus for
            $portal_each_score = $this->bonus_data[$card_bonus]['val2']; // Each card of this type scores this number of points
        }
        // Animus bonus info
        $animus_bonus_type = 0;
        $animus_each_score = 0;
        if ($card_bonus_type == 2)
        {
            $animus_bonus_type = $this->bonus_data[$card_bonus]['val1']; // Animus type this card rewards a bonus for
            $animus_each_score = $this->bonus_data[$card_bonus]['val2'];
        }

        return array('card_bonus_type'=>$card_bonus_type, 
                        'card_color'=>$card_color,
                        'portal_type_id'=>$portal_type_id,
                        'animus_array'=>$card_animus_array,
                        'portal_bonus_type'=>$portal_bonus_type,
                        'portal_each_score'=>$portal_each_score,
                        'animus_bonus_type'=>$animus_bonus_type,
                        'animus_each_score'=>$animus_each_score);
    }

    // Moved away from scoring end of game points only at end of game
    // This function wil calculate the contribution of current $card_id to score
    // Including past and current green cards
    function delta_EOG_scoring($doneCards, $new_card_id)
    {
        //self::error('Entering delta_EOG_scoring');
        // Gather information about current card
        $newCardData = $this->gather_card_info($new_card_id);
        $portal_score = 0;
        $animus_score = 0;
        // go through previous cards and see if this card matches any green bonus
        $card_id_list = array_column($doneCards, 'card_id');
        foreach ($card_id_list as $ii=>$card_id)
        {
            // Ignore the card just finished
            if ($card_id != $new_card_id)
            {
                $curCardData = $this->gather_card_info($card_id);
                if ($curCardData['portal_bonus_type'] == $newCardData['portal_type_id'])
                { 
                    //self::error('Found previous card that gives portal type bonus prev id: '.$card_id.' new card id: '.$new_card_id.'|');
                    $portal_score = $portal_score + $curCardData['portal_each_score'];
                }
                // Look for animus bonus
                if ($curCardData['animus_bonus_type'] > 0)
                {
                    for ($ii=0; $ii<6; $ii++)
                    {
                        if ($curCardData['animus_bonus_type'] == $newCardData['animus_array'][$ii])
                        {
                            //self::error('found previous card that gives animus type bonus prev id: '.$card_id.' new card id: '.$new_card_id.'|');
                            $animus_score = $animus_score + $curCardData['animus_each_score'];
                        }
                    }
                }
            }
        }

        // See if this card is a green bonus and need to retroactive get points
        // try portal bonus type first
        if ($newCardData['card_bonus_type'] == 1)
        {
            //self::error('Cid: '.$new_card_id.' potentially gives portal bonus');
            foreach ($card_id_list as $ii=>$card_id)
            {
                $curCardData = $this->gather_card_info($card_id);
                if ($curCardData['portal_type_id'] == $newCardData['portal_bonus_type'])
                {
                    //self::error('new card id: '.$new_card_id.' gives portal bonus for previous card id: '.$card_id.'|');
                    $portal_score = $portal_score + $newCardData['portal_each_score'];
                }                
            }
        }
        // do animus bonus
        if ($newCardData['card_bonus_type'] == 2)
        {
            //self::error('Cid: '.$new_card_id.' potentially gives animus bonus');
            foreach ($card_id_list as $ii=>$card_id)
            {
                $curCardData = $this->gather_card_info($card_id);
                for ($ii=0; $ii<6; $ii++)
                {
                    if ($curCardData['animus_array'][$ii] == $newCardData['animus_bonus_type'])
                    {
                        //self::error('new card id: '.$new_card_id.' gives animus bonus for previous card id: '.$card_id.'|');
                        $animus_score = $animus_score + $newCardData['animus_each_score'];
                    }
                }                
            }
        }


        return array($portal_score, $animus_score);
    }

    // Score the end of game cards that give bonuses for having portals of a certain color
    // This function is only used to get portal counts for rewards still need it even though we don't need 
    //  it for the EOG scoring anymore
    function calculate_EOG_PortalBonus($doneCards)
    {
        //self::error('Entering calculate_EOG_PortalBonus');
        // This is for cards with bonus type == 1
        $card_id_list = array_column($doneCards, 'card_id');
        $player_id_list = array_column($doneCards, 'card_location_arg');
        $bonus_type_list = array();
        $portal_type_list = array();
        // Go through cards to get their bonus type and portal type
        $portal_name_to_id['green'] = 1;
        $portal_name_to_id['yellow'] = 2;
        $portal_name_to_id['purple'] = 3;
        $portal_name_to_id['blue'] = 4;
        foreach ($card_id_list as $ii=>$card_id)
        {
            $card_bonus = $this->port_cards[$card_id]['card_bonus'];
            $card_bonus_type = 0;
            if ($card_bonus > 0)
            {
                $card_bonus_type = $this->bonus_data[$card_bonus]['card_bonus_type'];
            }
            $bonus_type_list[] = $card_bonus_type;
            $card_color = $this->port_cards[$card_id]['card_color'];
            $portal_type_list[] = $portal_name_to_id[$card_color];
            //self::error('ii: '.$ii.' pid: '.$player_id_list[$ii].' cid: '.$card_id.' btyp: '.$card_bonus_type.' clr: '.$card_color.' ptyp: '.$portal_name_to_id[$card_color].'|');
        }
        //self::error('cid: '.implode('|', $card_id_list).'|');
        //self::error('pid: '.implode('|', $player_id_list).'|');
        //self::error('btyp: '.implode('|', $bonus_type_list).'|');
        //self::error('ptyp: '.implode('|', $portal_type_list).'|');
        $player_list = array_values(array_unique($player_id_list));
        //self::error(implode('|',array_keys($player_list)).' '.implode('|',array_values($player_list)).'|');
        $nPlayers = count($player_list);
        // Initialize a 2 d array with first key is player_id; second key is portal type id
        $portal_count_arrays = array();
        $portal_score = array();
        // We need a list of all players not just one with done cards
        // this causes problems in other areas if the arrays don't include all player ids
        $players = self::loadPlayersBasicInfos();
        foreach ($players as $player_id => $player)
        {
            $portal_score[$player_id] = 0;
            for ($jj=1; $jj<=4; $jj++)
            {
                $portal_count_arrays[$player_id][$jj] = 0;
            }
        }
        // Go through cards and count portal types
        foreach ($card_id_list as $ii=>$card_id)
        {
            $portal_count_arrays[$player_id_list[$ii]][$portal_type_list[$ii]]++;
        }
        // DEBUG portal card types
        //for ($ii=0; $ii<$nPlayers; $ii++)
        //{
        //    for ($jj=1; $jj<=4; $jj++)
        //    {
        //        $pid = $player_list[$ii];
                //self::error('ii: '.$ii.' jj: '.$jj.' pid: '.$pid.' cnt: '.$portal_count_arrays[$pid][$jj].'|');
        //    }
        //}
        // Go through cards and find the player's cards with bonus type 1 and multiply the number of the correct type with the multiplier
        foreach ($card_id_list as $ii=>$card_id)
        {
            if ($bonus_type_list[$ii] == 1)
            {
                $card_bonus = $this->port_cards[$card_id]['card_bonus'];
                $bonus_color_type = $this->bonus_data[$card_bonus]['val1']; // Color type this card rewards a bonus for
                $pid = $player_id_list[$ii];
                // Get number of cards of this color type the player had
                $bonus_type_count = $portal_count_arrays[$pid][$bonus_color_type]; 
                $bonus_value = $this->bonus_data[$card_bonus]['val2'];
                $score = $bonus_type_count * $bonus_value;
                $portal_score[$pid] = $portal_score[$pid] + $score;
                //self::error('ii :'.$ii.' pid: '.$pid.' cid: '.$card_id.' crdbonus: '.$card_bonus.' ctyp: '.$bonus_color_type.' crdcnt: '.$bonus_type_count.' valeach: '.$bonus_value.' score: '.$score.'|');
            }
        }
        foreach ($player_list as $ii=>$player_id)
        {
            //self::error('pid: '.$player_id.' sumscore: '.$portal_score[$player_id].'|');
        }
        return array($portal_score, $portal_count_arrays);

    }

    // Calculate bonus score for cards that multiply the number of animus types
    function calculate_EOG_AnimusBonus($doneCards)
    {
        //self::error('Entering calculate_EOG_AnimusBonus');
        // This is for cards with bonus type == 2
        $card_id_list = array_column($doneCards, 'card_id');
        $player_id_list = array_column($doneCards, 'card_location_arg');
        $bonus_type_list = array();
        $animus_array_list = array();
        foreach ($card_id_list as $ii=>$card_id)
        {
            $card_bonus = $this->port_cards[$card_id]['card_bonus'];
            $card_bonus_type = 0;
            if ($card_bonus > 0)
            {
                $card_bonus_type = $this->bonus_data[$card_bonus]['card_bonus_type'];
            }
            $bonus_type_list[] = $card_bonus_type;
            $card_animus_array = array(
                $this->port_cards[$card_id]['card_tk_1'],
                $this->port_cards[$card_id]['card_tk_2'],
                $this->port_cards[$card_id]['card_tk_3'],
                $this->port_cards[$card_id]['card_tk_4'],
                $this->port_cards[$card_id]['card_tk_5'],
                $this->port_cards[$card_id]['card_tk_6'],
            );
            $animus_array_list[] = $card_animus_array;
            //self::error('ii: '.$ii.' pid: '.$player_id_list[$ii].' cid: '.$card_id.' btyp: '.$card_bonus_type.' clr: '.$card_color.' ptyp: '.$portal_name_to_id[$card_color].'|');
        }
        //self::error('cid: '.implode('|', $card_id_list).'|');
        //self::error('pid: '.implode('|', $player_id_list).'|');
        //self::error('btyp: '.implode('|', $bonus_type_list).'|');
        //self::error('ptyp: '.implode('|', $portal_type_list).'|');
        $player_list = array_values(array_unique($player_id_list));
        //self::error(implode('|',array_keys($player_list)).' '.implode('|',array_values($player_list)).'|');
        $nPlayers = count($player_list);
        // Initialize a 2 d array with first key is player_id; second key is animus type id
        $animus_count_arrays = array();
        $animus_score = array();
        $players = self::loadPlayersBasicInfos();
        foreach ($players as $player_id => $player)
        {
            $animus_score[$player_id] = 0;
            for ($jj=1; $jj<=4; $jj++)
            {
                $animus_count_arrays[$player_id][$jj] = 0;
            }
        }
        // Go through cards and count animus types
        foreach ($card_id_list as $ii=>$card_id)
        {
            $card_animus_array = $animus_array_list[$ii];
            for ($kk=0; $kk<6; $kk++)
            {
                if ($card_animus_array[$kk] > 0)
                {
                    $animus_count_arrays[$player_id_list[$ii]][$card_animus_array[$kk]]++;
                }
            }
        }
        // DEBUG animus types
        for ($ii=0; $ii<$nPlayers; $ii++)
        {
            for ($jj=1; $jj<=6; $jj++)
            {
                $pid = $player_list[$ii];
                //self::error('ii: '.$ii.' jj: '.$jj.' pid: '.$pid.' cnt: '.$animus_count_arrays[$pid][$jj].'|');
            }
        }
        // Go through cards and find the player's cards with bonus type 2 and multiply the number of the correct type with the multiplier
        foreach ($card_id_list as $ii=>$card_id)
        {
            if ($bonus_type_list[$ii] == 2)
            {
                $card_bonus = $this->port_cards[$card_id]['card_bonus'];
                $bonus_animus_type = $this->bonus_data[$card_bonus]['val1']; // Animus type this card rewards a bonus for
                $pid = $player_id_list[$ii];
                // Get number of animus types the player had
                $bonus_type_count = $animus_count_arrays[$pid][$bonus_animus_type]; 
                $bonus_value = $this->bonus_data[$card_bonus]['val2'];
                $score = $bonus_type_count * $bonus_value;
                $animus_score[$pid] = $animus_score[$pid] + $score;
                //self::error('ii :'.$ii.' pid: '.$pid.' cid: '.$card_id.' crdbonus: '.$card_bonus.' ctyp: '.$bonus_animus_type.' crdcnt: '.$bonus_type_count.' valeach: '.$bonus_value.' score: '.$score.'|');
            }
        }
        foreach ($player_list as $ii=>$player_id)
        {
            //self::error('pid: '.$player_id.' sumscore: '.$animus_score[$player_id].'|');
        }
        return array($animus_score, $animus_count_arrays);

    }

    function token_stat_name($token_type)
    {
        $token_names = array(1=>'air_tokens_drawn', 
                            2=>'water_tokens_drawn',
                            3=>'earth_tokens_drawn',
                            4=>'life_tokens_drawn',
                            5=>'fire_tokens_drawn',
                            6=>'shadow_tokens_drawn',
                            7=>'wildcard_tokens_drawn');
        return $token_names[$token_type];
    }

    function isZombie($player_id) {
        return self::getUniqueValueFromDB("SELECT player_zombie FROM player WHERE player_id=".$player_id);
    }

    // TEST for fix_duplicate_cards_dealt
    // Create duplicate cards in players hand
    function TEST_fix_duplicate_cards_dealt()
    {
        //self::error('Entering TEST_fix_duplicate_cards_dealt');
        $players = self::loadPlayersBasicInfos();
        foreach ($players as $player_id => $player)
        {
            $players_cards = $this->portcards->getCardsInLocation('hand', $player_id);
            $picked_cards = array_rand($players_cards, 2);
            // get type of first card
            $idx = $picked_cards[0];
            $curtyp = $players_cards[$idx]['type'];
            // Find this type in deck
            $indeck_card = $this->portcards->getCardsOfTypeInLocation($curtyp, null, 'deck');
            if (count($indeck_card)>0) {
                // discard second card
                $idx = $picked_cards[1];
                $this->portcards->moveCard($players_cards[$idx]['id'], 'discard');
                // add indeck_card to hand
                $want_card = array_pop($indeck_card);
                $this->portcards->moveCard($want_card['id'], 'hand', $player_id);
            }
        }
    }

    // DEBUG debug the global variables by printing them out
    function DEBUG_global_variables()
    {
        for ($ii=1; $ii<=6; $ii++)
        {
            self::error('GLOBAL SETUP '.'p'.$ii.'_id is '.self::getGameStateValue('p'.$ii.'_id'));
        }
    }
    function DEBUG_with_badsql($debug_str)
    {
        $sql = "INSERT INTO foo VALUES ('".$debug_str."');";
        self::DbQuery( $sql );       
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in viamagica.action.php)
    */

    // Handle the initial card selection
    function chooseInitCards( $card_ids ) {
        //self::error('Entering chooseInitCards');
        self::checkAction( "chooseInitCards" );

        // TEST START -Do some game Testing here
        //self::error('TEST TEST calculate_EOG_PortalBonus');
        //$pcData = self::getObjectListFromDB( "SELECT card_id, card_location, card_location_arg
        //                            FROM portcards
        //                            WHERE card_location = 'hand'");
        //$this->calculate_EOG_PortalBonus($pcData);
        //$this->calculate_EOG_AnimusBonus($pcData);
        //$this->gamestate->nextState( 'forceEndGame' );
        //$spots = $this->getAvailGemPlaces(null, 1);
        //foreach ($spots as $ii => $data) 
        //{
        //    self::error('Spots cid: '.$data['cid'].' pos: '.$data['pos'].' pid: '.$data['pid'].'|');
        //}
        //$spots = $this->getAvailGemPlaces($this->getCurrentPlayerId(), 1);
        //foreach ($spots as $ii => $data) 
        //{
        //    self::error('Spots cid: '.$data['cid'].' pos: '.$data['pos'].' pid: '.$data['pid'].'|');
        //}
        // TEST END

        $player_id = $this->getCurrentPlayerId();
        // Make sure there are three cards
        if (count($card_ids) == 3) {
            // Put 3 discards into global variables that will be committed after last player has chosen
            $idx = $this->findGameStateIdx($player_id);
            self::setGameStateValue('p'.$idx.'_cid', $card_ids[0]);
            self::setGameStateValue('p'.$idx.'_cid1', $card_ids[1]);
            self::setGameStateValue('p'.$idx.'_cid2', $card_ids[2]);
            self::notifyPlayer($player_id, "initCardsChosen", clienttranslate( 'Done choosing initial portal cards'), 
                        array(
                            'player_name' => self::getCurrentPlayerName(),
                            'card_ids' => $card_ids,
                            'player_id' => $player_id
                        ));
            //self::error('Notified player of discarded cards');
            // Check to see if this is the last player to submit results
            if (count($this->gamestate->getActivePlayerList()) == 1)
            {
                $nplayers = self::getGameStateValue( 'orig_n_player' );
                $cid_array = $this->getPlayerArrayFromGameState('_cid', $nplayers);
                $pid_array = $this->getPlayerArrayFromGameState('_id', $nplayers);
                $cid1_array = $this->getPlayerArrayFromGameState('_cid1', $nplayers);
                $cid2_array = $this->getPlayerArrayFromGameState('_cid2', $nplayers);
                $cid_array = array_merge($cid_array, $cid1_array, $cid2_array);
                $pid_array = array_merge($pid_array, $pid_array, $pid_array);
                // Notify all players to update other players discards
                self::notifyAllPlayers("allPlayersInitCardsChosen", clienttranslate( 'All players have finished choosing initial portal cards'),
                            array(
                                'card_ids' => $cid_array,
                                'player_ids' => $pid_array
                            ));
                //self::error('Notified all players that discarding cards is finished');
                // Now actually commit to DB the discards
                $this->portcards->moveCards($cid_array, 'discard');
                // Reset the global values to their empty state
                self::resetGameState('_cid', 0);
                self::resetGameState('_cid1', -1);
                self::resetGameState('_cid2', -1);

                // TEST TEST
                // This is a good place to setup and arbitrary play state
                // ALSO THE player that is the catcher has to go last for choosing cards
                // One should reload after choosing cards to see this new state
                // $playerData = self::loadPlayersBasicInfos();
                // $playerIdList = array_keys($playerData);
                // $playerUse = $playerIdList[0];
                // $active_player = $this->getCurrentPlayerId();
                // if ($active_player == $playerUse)
                // {
                //     // Add 4 more gems to first player
                //     // Query how many gems player currently has
                //     $gemDBData = self::getObjectListFromDB( "SELECT card_type, card_type_arg, card_location, card_location_arg FROM gems WHERE card_type = ".$playerUse);
                //     $nGems = count($gemDBData); // gems are zero-based counting
                //     for ($ii=0; $ii<4; $ii++) {
                //         $markers  = array(array('type' => $playerUse, 'type_arg' => $ii+$nGems, 
                //                                     'nbr' => 1));
                //         $this->gems->createCards($markers, 0, $ii+$nGems);
                //     }
                //     // Discard current active cards for player
                //     $this->portcards->moveAllCardsInLocation('hand', 'discard', $active_player);
                //     // card_type 23 = Rearrange Crystals
                //     // 21 = add single gem only 3 spots
                //     // 9 = eog air animus score only 3 spots
                //     // 12 = open/complete another portal
                //     // 11 = place 2 life animus
                //     // 15 = place 2 shadow animus
                //     // 26 = purple tree
                //     // This is good test of a rearrange resulting in two completed portals
                //     //$active_type_want = array(23, 21, 9, 1);
                //     // Testing open complete another portal
                //     $active_type_want = array(12, 15, 26);
                //     for ($jj=0; $jj<count($active_type_want); $jj++)
                //     {
                //         $type_want = $active_type_want[$jj];
                //         $cardData = $this->portcards->getCardsOfType($type_want);
                //         $cardIDs = array_column($cardData, 'id');
                //         $cardLocs = array_column($cardData, 'location');
                //         $foundAvail = -1;
                //         if ($cardLocs[0] == 'deck' | $cardLocs[0] == 'discard')
                //         {
                //             $foundAvail = 0;
                //         }
                //         if ($foundAvail == -1 & ($cardLocs[1] == 'deck' | $cardLocs[1] == 'discard'))
                //         {
                //             $foundAvail = 1;
                //         }
                //         if (!$foundAvail == -1)
                //         {
                //             $this->portcards->moveCard($cardIDs[$foundAvail], 'hand', $active_player);
                //         }
                //     }
                //     $done_type_want = array(2, 3, 4, 5, 6, 7);
                //     for ($jj=0; $jj<count($done_type_want); $jj++)
                //     {
                //         $type_want = $done_type_want[$jj];
                //         $cardData = $this->portcards->getCardsOfType($type_want);
                //         $cardIDs = array_column($cardData, 'id');
                //         $cardLocs = array_column($cardData, 'location');
                //         $foundAvail = -1;
                //         if ($cardLocs[0] == 'deck' | $cardLocs[0] == 'discard')
                //         {
                //             $foundAvail = 0;
                //         }
                //         if ($foundAvail == -1 & ($cardLocs[1] == 'deck' | $cardLocs[1] == 'discard'))
                //         {
                //             $foundAvail = 1;
                //         }
                //         if (!$foundAvail == -1)
                //         {
                //             $this->portcards->moveCard($cardIDs[$foundAvail], 'done', $active_player);
                //         }
                //     }
                // }
            }
            $this->gamestate->setPlayerNonMultiactive($player_id, 'initCardsChosen');
        }
    }

    // Handle a gem placement
    function placeGem( $spot_id, $gem_id ) {
        $this->gamestate->checkPossibleAction( "placeGem" );
        $spotData = $this->parseId($spot_id);
        $gemData = $this->parseId($gem_id);
        //self::error('placeGem: '.$gem_id.' '.$spot_id);
        $player_id = $this->getCurrentPlayerId();

        // Check if we received the no operation call for this player
        if (!($spot_id == 'vmg_NOOP_0_0' && $gem_id == 'vmg_NOOP_0_0'))
        {
            // Process normal got valid gem
            // Make sure the gem is owned by this player
            if ($gemData[0] == $player_id)
            {
                // Check to make sure the card and spot match token in display
                $cid = $spotData[0];
                $pos = $spotData[1];
                // Type of spot from card database
                $spot_type = $this->port_cards[$cid]['card_tk_'.($pos+1)]; // +1 because spots on cards have zero-based positions and in materials 1-based
                $token_type = self::getCurrentToken();
                // Check if player has a wildcard token bonus portal
                $bonusData = self::getObjectListFromDB( "SELECT player, val1
                                                            FROM rewards
                                                            WHERE bonus_type_id = 8 AND player = ".$player_id );
                foreach ($bonusData as $ii => $data) {
                    if ($token_type == $data['val1']) // If the bonus type matches the token convert the spot type to match the token type
                    {
                        $spot_type = $token_type;
                    }
                }                
                //self::error('Spot type: '.$spot_type.' token type: '.$token_type);
                if ($spot_type != -1) // The spot location was never playable??
                {
                    if ($spot_type == $token_type || $token_type == 7)
                    {
                        // Pass all checks actually move gem to new location in global variables not DB yet
                        // Get card_id of gem marker
                        //self::error('Finding Gem Marker');
                        $gemMarker = $this->gems->getCardsOfType($player_id, $gemData[1]);
                        // Remove the keys to provide the values only
                        $gemMarker = array_values($gemMarker);
                        //self::error('GemMarker Keys: '.implode('|',array_keys($gemMarker[0])));
                        //self::error('GemMarker Values: '.implode('|', array_values($gemMarker[0])));
                        // Get old gem location
                        $old_cid = $gemMarker[0]['location'];
                        $old_arg = $gemMarker[0]['location_arg'];
                        $gemn = $gemData[1];
                        // Store new gem location in global variables
                        //self::error('Storing gem pid: '.$player_id.' iter: '.$gemn.' cid: '.$cid.' spt: '.$pos.'|');
                        $idx = $this->findGameStateIdx($player_id);
                        //self::error('Storing gem pid: '.$player_id.' idx: '.$idx.' iter: '.$gemn.' cid: '.$cid.' spt: '.$pos.'|');
                        self::setGameStateValue('p'.$idx.'_gemn', $gemn);
                        self::setGameStateValue('p'.$idx.'_cid', $cid);
                        self::setGameStateValue('p'.$idx.'_sptn', $pos);

                        // We allow changing of mind until last player has played
                        $isPlayerActive = self::getUniqueValueFromDB("SELECT player_is_multiactive FROM player WHERE player_id = $player_id");
                        if ($isPlayerActive)
                        {
                            self::notifyPlayer($player_id, "gemPlaced", clienttranslate( 'You placed a crystal.'), 
                            array(
                                'player_name' => self::getCurrentPlayerName(),
                                'spot_id' => $spot_id,
                                'gem_id' => $gem_id,
                                'player_id' => $player_id,
                                'card_id' => $cid,
                                'pos_id' => $pos,
                                'type_arg' => $gemn,
                                'old_cid' => $old_cid,
                                'old_arg' => $old_arg
                            ));
                        } else {
                            self::notifyPlayer($player_id, "gemPlaced", clienttranslate( 'Different crystal placement recorded'), 
                            array(
                                'player_name' => self::getCurrentPlayerName(),
                                'spot_id' => $spot_id,
                                'gem_id' => $gem_id,
                                'player_id' => $player_id,
                                'card_id' => $cid,
                                'pos_id' => $pos,
                                'type_arg' => $gemn,
                                'old_cid' => $old_cid,
                                'old_arg' => $old_arg
                            ));
                        }
                        $this->determine_placeGem_NextState($player_id);
                    }    
                } else {
                    //self::error('SPOT TYPES DID NOT AGREE?! '.'Spot type: '.$spot_type.' token type: '.$token_type);
                }
            }
        } else {
            // Got the noop call for player not placing any gems
            $idx = $this->findGameStateIdx($player_id);
            //self::error('Storing gem pid: '.$player_id.' idx: '.$idx.' iter: '.$gemn.' cid: '.$cid.' spt: '.$pos.'|');
            // Still record noop in case this is a pass while others are playing
            self::setGameStateValue('p'.$idx.'_gemn', -1);
            self::setGameStateValue('p'.$idx.'_cid', 0);
            self::setGameStateValue('p'.$idx.'_sptn', -1);

            self::notifyPlayer($player_id, "gemPlaced", clienttranslate( 'You skipped crystal placement'), 
            array(
                'player_name' => self::getCurrentPlayerName(),
                'spot_id' => 'vmg_NOOP_0_0',
                'gem_id' => 'vmg_NOOP_0_0',
                'player_id' => $player_id,
                'card_id' => 0,
                'pos_id' => 0,
                'type_arg' => 0,
                'old_cid' => 0,
                'old_arg' => 0
            ));
            $this->determine_placeGem_NextState($player_id);

        }
    }
    // This function determines whether all players have completed placeGem
    // It will then call all the updates and commit to DB if all players
    function determine_placeGem_NextState($player_id)
    {
        //self::error('Entering determine_placeGem_NextState');
        // Check to see if this is the last active player to submit results
        $isPlayerActive = self::getUniqueValueFromDB("SELECT player_is_multiactive FROM player WHERE player_id = $player_id");
        if ($isPlayerActive && count($this->gamestate->getActivePlayerList()) == 1)
        {
            //self::error('Starting DB gem commits');
            // Last person has placed Gem
            // Commit to DB all gem placements and notify all players
            // so the client side can show placements for other players
            $nplayers = self::getGameStateValue('orig_n_player');
            $cid_array = $this->getPlayerArrayFromGameState('_cid', $nplayers);
            $pid_array = $this->getPlayerArrayFromGameState('_id', $nplayers);
            $gemn_array = $this->getPlayerArrayFromGameState('_gemn', $nplayers);
            $sptn_array = $this->getPlayerArrayFromGameState('_sptn', $nplayers);
            $old_cid_array = $this->get_gem_card_locations($pid_array, $gemn_array);
            // Notify all players to update other players gems
            self::notifyAllPlayers("allPlayersPlacedGems", clienttranslate( 'All players have finished placing crystals'),
                        array(
                            'card_ids' => $cid_array,
                            'player_ids' => $pid_array,
                            'gemns' => $gemn_array,
                            'sptns' => $sptn_array,
                            'old_card_ids' => $old_cid_array
                        ));
            //self::error('Notified all players that placing gems is finished');
            // Now actually commit to DB the gem placements
            // Get card_id of gems
            for ($ii=0; $ii<$nplayers; $ii++)
            {
                if ($gemn_array[$ii] != -1) { // Protect against passing on gem placement
                    $gemMarker = $this->gems->getCardsOfType($pid_array[$ii], $gemn_array[$ii]);
                    $gemMarker = array_values($gemMarker);
                    // Move Card
                    $this->gems->moveCard($gemMarker[0]['id'], $cid_array[$ii], $sptn_array[$ii]);
                    // Update players stats
                    self::incStat(1, 'total_crys_play', $pid_array[$ii]);
                    // What kind of animus was gem played on
                    $ani_stat_map = array(1=>'air_crys_play', 2=>'water_crys_play', 3=>'earth_crys_play',
                                        4=>'life_crys_play', 5=>'fire_crys_play', 6=>'shadow_crys_play');
                    $anitype = $this->port_cards[$cid_array[$ii]]['card_tk_'.($sptn_array[$ii]+1)];
                    self::incStat(1, $ani_stat_map[$anitype] ,$pid_array[$ii]);

                } else {
                    // Record pass for this player in stats
                    self::incStat(1, 'pass_crys_play', $pid_array[$ii]);
                    self::incStat(1, 'total_crys_play', $pid_array[$ii]);
                }
            }

            // Reset the global values to their empty state
            self::resetGameState('_cid', 0);
            self::resetGameState('_gemn', -1);
            self::resetGameState('_sptn', -1);
            $this->gamestate->setAllPlayersNonMultiactive( 'gemPlaced' );
            return;
        }
        if ($isPlayerActive)
        {
            //self::error('Done and done with gemplacement');
            $this->gamestate->setPlayerNonMultiactive($player_id, 'gemPlaced');
        }
    }

    // Handle gem placement for portal card bonuses
    function placeGemEx( $spot_id, $gem_id ) {
        //self::error('Entering placeGemEx');
        self::checkAction( "exPlayGem" );
        $spotData = $this->parseId($spot_id);
        $gemData = $this->parseId($gem_id);
        //self::error('placeGemEx: '.$gem_id.' '.$spot_id);
        $player_id = $this->getCurrentPlayerId();

        // Check if we received the no operation call for this player
        if (!($spot_id == 'vmg_NOOP_0_0' && $gem_id == 'vmg_NOOP_0_0'))
        {
            // Process normal got valid gem
            // Make sure the gem is owned by this player
            if ($gemData[0] == $player_id)
            {
                // Check to make sure the card and spot match the type of bonus spots
                $cid = $spotData[0];
                $pos = $spotData[1];
                // Type of spot from card database
                $spot_type = $this->port_cards[$cid]['card_tk_'.($pos+1)]; // +1 because spots on cards have zero-based positions and in materials 1-based
                $token_type = self::getGameStateValue( 'gem_type' );
                //self::error('Spot type: '.$spot_type.' token type: '.$token_type);
                if ($spot_type != -1) // The spot location was never playable??
                {
                    if ($spot_type == $token_type || $token_type == 7)
                    {
                        // Pass all checks actually move gem to new location in DB
                        // Get card_id of gem marker
                        //self::error('Finding Gem Marker');
                        $gemMarker = $this->gems->getCardsOfType($player_id, $gemData[1]);
                        // Remove the keys to provide the values only
                        $gemMarker = array_values($gemMarker);
                        //self::error('GemMarker Keys: '.implode('|',array_keys($gemMarker[0])));
                        //self::error('GemMarker Values: '.implode('|', array_values($gemMarker[0])));
                        // Get old gem location
                        $old_cid = $gemMarker[0]['location'];
                        $old_arg = $gemMarker[0]['location_arg'];
                        $gemn = $gemData[1];
                        // Store new gem location in DB
                        $this->gems->moveCard($gemMarker[0]['id'], $cid, $pos);
                        // Update players stats
                        self::incStat(1, 'total_crys_play', $player_id);
                        // What kind of animus was gem played on
                        $ani_stat_map = array(1=>'air_crys_play', 2=>'water_crys_play', 3=>'earth_crys_play',
                                        4=>'life_crys_play', 5=>'fire_crys_play', 6=>'shadow_crys_play');
                        $anitype = $this->port_cards[$cid]['card_tk_'.($pos+1)];
                        self::incStat(1, $ani_stat_map[$anitype] ,$player_id);

                        self::notifyPlayer($player_id, "gemPlaced", clienttranslate( 'You placed a crystal.'), 
                        array(
                            'player_name' => self::getCurrentPlayerName(),
                            'spot_id' => $spot_id,
                            'gem_id' => $gem_id,
                            'player_id' => $player_id,
                            'card_id' => $cid,
                            'pos_id' => $pos,
                            'type_arg' => $gemn,
                            'old_cid' => $old_cid,
                            'old_arg' => $old_arg
                        ));
                        // Also need to notify other players to move gem properly
                        self::notifyAllPlayers("allPlayersPlacedGems", clienttranslate( '${player_name} placed bonus crystal'),
                        array(
                            'card_ids' => array($cid),
                            'player_ids' => array($player_id),
                            'gemns' => array($gemn),
                            'sptns' => array($pos),
                            'old_card_ids' => array($old_cid),
                            'player_name' => self::getCurrentPlayerName()
                        ));

                        // Reduce bonus gem count available
                        $curcnt = self::getGameStateValue( 'gem_count' );
                        self::setGameStateValue( 'gem_count', $curcnt - 1);
                        $this->gamestate->nextState('donePlacingGemEx');
                    }    
                } else {
                    //self::error('SPOT TYPES DID NOT AGREE?! '.'Spot type: '.$spot_type.' token type: '.$token_type);
                }
            }
        } else {
            // Got the noop call for player not placing any gems
            // Record pass for this player in stats
            self::incStat(1, 'pass_crys_play', $player_id);
            self::incStat(1, 'total_crys_play', $player_id);
            
            self::notifyPlayer($player_id, "gemPlaced", clienttranslate( 'You skipped crystal placement'), 
            array(
                'player_name' => self::getCurrentPlayerName(),
                'spot_id' => 'vmg_NOOP_0_0',
                'gem_id' => 'vmg_NOOP_0_0',
                'player_id' => $player_id,
                'card_id' => 0,
                'pos_id' => 0,
                'type_arg' => 0,
                'old_cid' => 0,
                'old_arg' => 0
            ));
            // Reduce bonus gem count available
            $curcnt = self::getGameStateValue( 'gem_count' );
            self::setGameStateValue( 'gem_count', $curcnt - 1);
            $this->gamestate->nextState('donePlacingGemEx');

        }
    }

    // Handle choosing a new portal card
    function chooseNewCard( $card_id ) {
        //self::error('chooseNewCard Entry '.$card_id.'|');
        self::checkAction( "completeCard" );
        $player_id = $this->getActivePlayerId();
        // Verify that the new card is not already owned by player
        // Get players card ids that are not allowed
        $outData  = $this->argCardInfo();
        if (!(in_array( $card_id, $outData['avoid_card_ids'])))
        {

            // Zero out the global completed card values
            // Do this in the scoring and rules adjusting 
            //self::setGameStateValue('res_id', 0);
            //self::setGameStateValue( 'res_cid', 0);

            // Move the new chosen card to the player's active play area
            $this->portcards->moveCard( $card_id, 'hand', $player_id);
            // Draw a new card for the portal stock card area
            $drawncard = $this->portcards->pickCardForLocation( 'deck', 'portalstock');
            //self::error('Drew Card '.implode('|',array_keys($drawncard)));
            // Add card to stats
            self::incStat(1, 'total_cards_avail');
            $color_map = array('green'=>'green_cards_avail', 'yellow'=>'yellow_cards_avail', 
                            'purple'=>'purple_cards_avail', 'blue'=>'blue_cards_avail');
            $card_color = $this->port_cards[$drawncard['id']]['card_color'];
            self::incStat(1, $color_map[$card_color]);        

            // Now need to move gems off card back to players gem zone location
            // First get the information about gems before moving them
            //$gemSentHome = $this->gems->getCardsInLocation($old_card_id);
            // Now move them.  moveAllCardsInLocation doesn't return info about cards moved
            // Hence we do he query beforehand
            //$this->gems->moveAllCardsInLocation($old_card_id, 0);


            // Move the completed c
            self::notifyAllPlayers( "newCardChosen", clienttranslate( '${player_name} chose a new card.'), 
                            array(
                                'player_name' => self::getActivePlayerName(),
                                'player_id' => $player_id,
                                'new_card_id' => $card_id,
                                'new_card_for_portalstock_id' => $drawncard['id']
                            ));
            $this->gamestate->nextState( 'completedCard' );
        }
    }
    
    // Handle choosing whether to accept the open portal count reward
    function resolvePortalCount ($accept)
    {
        //self::error('Entering resolvePortalCount action');
        self::checkAction('resolvePortalCount');
        $player_id = $this->getActivePlayerId();
        $players = $this->loadPlayersBasicInfos();

        if ($accept == 1) 
        {   // Yes take reward
            // Verify eligibility
            // Find cards for player
            $doneCards = self::getObjectListFromDB( "SELECT card_id, card_location, card_location_arg
                                FROM portcards
                                WHERE card_location = 'done' AND card_location_arg = ".$player_id);
            $nDone = count($doneCards);
            // Get the rewards of type 10 from DB
            $rewardsGiven = self::getObjectListFromDB( "SELECT player, val1 FROM rewards WHERE bonus_type_id = 10");
            $player_id_array = array_values(array_column($rewardsGiven, 'player'));
            $portal_count_array = array_values(array_column($rewardsGiven, 'val1'));
            // has this player claimed a reward yet?
            if (!in_array($player_id, $player_id_array)) {
                // has this portal count been claimed yet?
                if (!in_array($nDone, $portal_count_array)) {
                    // Add reward to DB
                    self::dbQuery( "INSERT INTO rewards (bonus_type_id, player, val1) VALUES (10,".$player_id.",".$nDone.")");
                    $reward_vp = array(2=>2, 3=>4, 4=>6, 5=>8, 6=>10);
                    $add_vp = $reward_vp[$nDone];
                    // Adjust players score
                    self::DbQuery( "UPDATE player SET player_score=player_score+".$add_vp." WHERE player_id='".$player_id."'");
                    self::notifyAllPlayers( 'resolvePortalCount', clienttranslate( '${player_name} claims reward for having ${nDone} open portals'), array(
                        'player_name' => $players[$player_id]['player_name'],
                        'add_vp' => $add_vp,
                        'player_id' => $player_id,
                        'nDone' => $nDone
                    ));
            

                }
            }
        }
        $this->gamestate->nextState( 'checkCompleteCards' );
    }

    // Handle choosing an additional portal card due to the completed portal card bonus
    function chooseNewCardBonus( $card_id ) {
        //self::error('chooseNewCardBonus Entry '.$card_id.'|');
        self::checkAction( "chooseNewCardBonus" );
        $player_id = $this->getActivePlayerId();
        // Verify that the new card is not already owned by player
        // Get players card ids that are not allowed
        $outData  = $this->argCardInfo();
        if (!(in_array( $card_id, $outData['avoid_card_ids'])))
        {    
            // Move the new chosen card to the player's active play area
            $this->portcards->moveCard( $card_id, 'hand', $player_id);
            // Draw a new card for the portal stock card area
            $drawncard = $this->portcards->pickCardForLocation( 'deck', 'portalstock');
            //self::error('Drew Card '.implode('|',array_keys($drawncard)));
            // Add card to stats
            self::incStat(1, 'total_cards_avail');
            $color_map = array('green'=>'green_cards_avail', 'yellow'=>'yellow_cards_avail', 
                            'purple'=>'purple_cards_avail', 'blue'=>'blue_cards_avail');
            $card_color = $this->port_cards[$drawncard['id']]['card_color'];
            self::incStat(1, $color_map[$card_color]);        


            self::notifyAllPlayers( "newCardBonusChosen", clienttranslate( '${player_name} chose a new card.'), 
                                array(
                                    'player_name' => self::getActivePlayerName(),
                                    'player_id' => $player_id,
                                    'new_card_id' => $card_id,
                                    'new_card_for_portalstock_id' => $drawncard['id']
                                ));
            $this->gamestate->nextState( 'doneChoosingNewCardBonus' );
        }
    }
        
    // Handle choosing an additional portal card to complete due to bonus
    function completePortalBonus( $card_id ) {
        //self::error('completePortalBonus Entry '.$card_id.'|');
        self::checkAction( "completePortalBonus" );
        $player_id = $this->getActivePlayerId();
        // Verify that the chosen card is players active area
        $activeCards = self::getObjectListFromDB( "SELECT card_id, card_location, card_location_arg
                                                    FROM portcards
                                                    WHERE card_location = 'hand' AND card_location_arg = ".$player_id);
        $active_ids = array_values(array_column($activeCards, 'card_id'));
        if (in_array( $card_id, $active_ids))
        {    
            // No need to move or notify players
            // though do notify player so we can set the card with transparency
            self::notifyPlayer($player_id, "completePortalBonusChosen", clienttranslate( 'Done choosing bonus portal to open'),
                array(
                    'card_id' => $card_id
                ));
            // the card is getting added to the completed card overflow in rewards database
            // the card will be picked up by the 'checkForCompCards' state and followed through to resolve
            self::DbQuery( "INSERT INTO rewards (bonus_type_id, player, val1) VALUES (99,".$player_id.",".$card_id.")");
            $this->gamestate->nextState( 'doneCompletePortalBonus' );
        }
    }
    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */
    // Argument for the placeGem function to
    // provide the animus spots that are playable and other information 
    function argAvailGemPlaces()
    {
        //self::error('argAvailGemPlaces');
        $return_item = $this->getAvailGemPlaces(null, null);
        //self::error('got Available Gem Places');
        $return_gemcount = $this->getPlayerGemCount();
        //self::error('Got Player Gem Count');

        return array(
            'possiblePlaces' => $return_item,
            'currentToken' => $this->getCurrentToken(),
            'playerGemNumber' => $return_gemcount
        );
    }
    // Similar to argAvailGemPlaces, but specifically tailored for the portal card bonus placement of gems
    //  rather than the normal turn process
    function argAvailGemPlacesEx()
    {
        //self::error('argAvailGemPlacesEx');
        $pid = self::getGameStateValue( 'gem_id' );
        $ani_type = self::getGameStateValue( 'gem_type' );
        $return_item = $this->getAvailGemPlaces($pid, $ani_type);
        //self::error('Got Available Gem places');
        $return_gemcount = $this->getPlayerGemCount();
        //self::error('Got Player Gem Count');
        $gem_strt_count = self::getGameStateValue( 'gem_strt_count' );
        $gem_count = self::getGameStateValue( 'gem_count' );
        $cur_gemn = $gem_strt_count - $gem_count + 1;

        return array(
            'possiblePlaces' => $return_item,
            'currentToken' => $ani_type,
            'playerGemNumber' => $return_gemcount,
            'gem_count' => $cur_gemn,
            'gem_strt_count' => self::getGameStateValue( 'gem_strt_count')
        );
    }
    function argCardInfo()
    {
        //self::error('Entering argCardInfo');
        $player_id = $this->getActivePlayerId();
        // Get players card ids so that they don't pick a duplicate
        $pcData = self::getObjectListFromDB( "SELECT card_id, card_type
                                    FROM portcards
                                    WHERE (card_location = 'hand' OR card_location = 'done')  AND (card_location_arg = ".$player_id.")");
        $pcData = array_column($pcData, 'card_id');
        $pcData = array_values($pcData);
        //self::error('Players cards owned, N: '.count($pcData).' '.implode('|', $pcData).'|');
        // We actually need all card_ids for cards that match the card type in players hand
        $allCardId = array_keys($this->port_cards);
        $allCardType = array_column($this->port_cards, 'card_type');
        $avoid_cids = array();
        foreach ($pcData as $ii => $data)
        {
            $want_type = $this->port_cards[$data]['card_type'];
            $want_keys = array_keys($allCardType, $want_type);
            foreach($want_keys as $jj=>$data2)
            {
                $avoid_cids[] = $allCardId[$data2];
            }
        }
        return array(
            'card_id' => self::getGameStateValue('res_cid'),
            'player_id' => $player_id,
            'avoid_card_ids' => $avoid_cids
        );
    }

    function argPortalCount()
    {
        // Number of cards done by player
        //self::error('Entering argPortalCount');
        $player_id = $this->getActivePlayerId();
        $doneCards = self::getObjectListFromDB( "SELECT card_id, card_location, card_location_arg
                                    FROM portcards
                                    WHERE card_location = 'done' AND card_location_arg = ".$player_id);
        return array(
            'n_card' => count($doneCards),
            'player_id' => $player_id
        );
    }
//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */
        
    // Go to Multipleactiveplayer mode for all players
    function stMultiPlayerInit()
    {

        $this->gamestate->setAllPlayersMultiactive();
    }
    // This leads placeGem state because not all players have
    //  an available location to place a gem also deals with
    //   situation that no gems are available
    function stMultiPlayerInitFlexible() {
        //self::error('Entering st_MultiPlayerInitFlexible');
        // Get data for the available Gem places
        $availGemData = $this->getAvailGemPlaces(null, null);
        //self::error('Found N Gem areas avail: '.count($availGemData).'|');
        if (count($availGemData)!=0)
        {
            // Reduce this to the players that have space
            $gemPlayerIds = array_unique(array_column($availGemData, 'pid'));
            foreach ($gemPlayerIds as $idx=>$player_id)
            {
                self::giveExtraTime($player_id);
            }
        } else {
            $gemPlayerIds = array();
        }
        //self::error('N Players '.count($gemPlayerIds).'|');
        // Use this list to say who is active
        // transition noGemsToPlace is called if list of playerids is empty
        $this->gamestate->setPlayersMultiactive($gemPlayerIds, 'noGemsToPlace');
    }

    function stDrawToken()
    {
        //self::error('stDrawToken Start');
        $tokenData = $this->getTokenDisplay(); //Query DB for token in tokenDisplay area
        //self::error($tokenData['card_type'].' '.self::getGameStateValue( 'new_catcher' ));
        $players = $this->loadPlayersBasicInfos();
        // See if we need to get a new catcher (i.e., last draw was a wildcard token)
        if (intval(self::getGameStateValue( 'new_catcher' )) == 1)
        {
            //self::error('New Catcher needed');
            // Get next player
            $next_player = $this->getPlayerAfter(self::getGameStateValue( 'cur_catcher' ));
            self::setGameStateValue( 'cur_catcher', $next_player);
            self::setGameStateValue( 'new_catcher', 0);
            // Then notify all players of new catcher
	        self::notifyAllPlayers( "newCatcher", clienttranslate( '${player_name} is the new catcher of Animus tokens'), array(
                'player_id' => $next_player,
                'player_name' => $players[$next_player]['player_name']
            ));
        
        }
        
        //See if there is token present
        $old_token_type_arg = 0;
        if (isset($tokenData)) {
            $old_token_type_arg = $tokenData['card_type_arg'];
	        // If token in the tokenDisplay is the wild token we need to reshuffle before drawing new tile
	        if ($tokenData['card_type_arg'] == 7)
            {
	            $this->anitokens->moveAllCardsInLocation(null, 'deck');
                $this->anitokens->shuffle('deck');
                // Move the top token to tokenDisplay location
                $tokenData = $this->anitokens->pickCardForLocation('deck', 'display');
            } else {
                // Move tokenDisplay token to discard
                $this->anitokens->pickCardForLocation('display', 'discard');
	            // Move the top token to tokenDisplay location
	            $tokenData = $this->anitokens->pickCardForLocation('deck', 'display');
            }
        } else {
 	        // Move the top token to tokenDisplay location
            $tokenData = $this->anitokens->pickCardForLocation('deck', 'display');           
        }
        // For debugging purposes discard token on display and draw the wild token
        // DEBUGGING THIS CODE ALWAYS GIVDES WILD TOKEN COMMENT OUT
        //$this->anitokens->pickCardForLocation('display', 'discard');
        //$tokenSave = $this->anitokens->getCardsOfType('Wildcard');
        //$tokenSave_id = array_column($tokenSave, 'id');
        //$this->anitokens->moveCard($tokenSave_id[0], 'display');
        //$tokenData['type'] = 'Wildcard';
        //$tokenData['type_arg'] = 7;
        //$tokenData['id'] = $tokenSave_id[0];
        // END CODE TO ALWAYS GIVE WILD TOKEN
        if ($tokenData['type_arg'] == 7)
        {
            // Just drew wildcard token Will need new catcher next turn
            //self::error('Wildcard Drawn!!');
            self::setGameStateValue( 'new_catcher', 1);

        }
        // Record token drawn stats
        self::incStat(1, 'total_tokens_drawn');
        self::incStat(1, $this->token_stat_name($tokenData['type_arg']));
	    // Then notify all players of result of event
	    self::notifyAllPlayers( "tokenDrawn", clienttranslate( '${player_name} drew ${card_type} Animus token'), array(
				'card_type' => $tokenData['type'],
				'card_type_arg' => $tokenData['type_arg'], 
                'card_id' => $tokenData['id'], 
                'num_deck' => $this->anitokens->countCardInLocation('deck'),
                'player_name' => $players[self::getGameStateValue('cur_catcher')]['player_name'],
                'player_id' => self::getGameStateValue('cur_catcher'),
                'old_card_type_arg' => $old_token_type_arg
            ));


	    // Go to next game state
	    $this->gamestate->nextState( 'tokenDrawn' );
        
    }

    // Look for completed portal crds
    function stCheckForCompCards() {
        //self::error('Entering Check for Completed Cards');
        // get portal cards in the players active area
        $objcardData = self::getObjectListFromDB( "SELECT card_id, card_location, card_location_arg
                                        FROM portcards
                                        WHERE card_location = 'hand'" );
        //self::error('obj card DB Length: '.count($objcardData));
        // get Animus types on cards and store in dict array
        $objcardDataDict = array();
        if (!empty($objcardData))
        {
            foreach ($objcardData as $idx => $Data)
            {
                // Get data for each $card_id via the data kept in materials.inc.php
                $card_id = $Data['card_id'];
                $player_id = $Data['card_location_arg'];
                // Get which type each spot is on card
                $cardPosTypes = array($this->port_cards[$card_id]['card_tk_1'], 
                                        $this->port_cards[$card_id]['card_tk_2'], 
                                        $this->port_cards[$card_id]['card_tk_3'],
                                        $this->port_cards[$card_id]['card_tk_4'],
                                        $this->port_cards[$card_id]['card_tk_5'],
                                        $this->port_cards[$card_id]['card_tk_6']);
                //self::error('cid: '.$card_id.' '.$cardPosTypes[0].' '.$cardPosTypes[1].' '.$cardPosTypes[2].' '.$cardPosTypes[3].' '.$cardPosTypes[4].' '.$cardPosTypes[5]);
                // Go through each spot and only record it if is not == -1
                foreach ($cardPosTypes as $pos => $typ)
                {
                    //self::error('cid: '.$card_id.' '.$pos.' '.$typ.'|');
                    if ($typ != -1)
                    {   
                        // Build dictionary of all positions available on card
                        $objcardDataDict[] = array('cid' => $card_id, 'pos'=>$pos, 'pid'=>$player_id);
                    }
                }
            }
        }
        //self::error('Found '.count($objcardDataDict).' available spots on cards');
        // Get gem data for all users 
        $gemData = self::getObjectListFromDB( "SELECT card_type, card_type_arg, card_location, card_location_arg
                                                    FROM gems
                                                    WHERE card_location > 0");
        //self::error('Found '.count($gemData).' Gems on cards|');
        // Build arrays for each column of data for gems and card spots
        $gemCIDs = array_column($gemData, 'card_location');
        $gemCSpots = array_column($gemData, 'card_location_arg');
        $gemPIDs = array_column($gemData, 'card_type');
    
        $objCIDs = array_column($objcardDataDict, 'cid');
        $objCSpots = array_column($objcardDataDict, 'pos');
        $objPIDs = array_column($objcardDataDict, 'pid');
        // Get the unique card ids present
        $uniqObjCIDs = array_unique($objCIDs);
        // Do the count by value for for obj card spots
        $objcardSpotCount = array_count_values($objCIDs);
        // Do the count by value for the gem markers
        $gemSpotCount = array_count_values($gemCIDs);
    
        $doneCID = array();
        foreach ($uniqObjCIDs as $idx => $curCID)
        {
            // How many spots on card
            $sposAvail = $objcardSpotCount[$curCID];
            // How many markers
            if (array_key_exists($curCID, $gemSpotCount)) 
            {
                $gemAvail = $gemSpotCount[$curCID];
            } else {
                $gemAvail = 0;
            }
            //self::error('Card '.$curCID.' Nspots: '.$sposAvail.' NGems: '.$gemAvail.'|');
            if ($sposAvail == $gemAvail) {
                // get player id for the card
                $key_idx = array_search($curCID, $gemCIDs);
                $player_id = $gemPIDs[$key_idx];
                //self::error('Card Completed!! '.$curCID.' '.$player_id.'|');
                $doneCID[] = array('cid' => $curCID, 'pid' => $player_id);
            }
        }
        // Look for completed cards in the overflow type of the rewards DB
        $overFlowComp = self::getObjectListFromDB( "SELECT player, val1 FROM rewards WHERE bonus_type_id = 99");
        // Add these cards to the $doneCID array if they aren't in there already
        $origDoneCID = array_column($doneCID, 'cid');
        foreach ($overFlowComp as $idx => $data) {
            if (!in_array($data['val1'], $origDoneCID))
            {
                //self::error('Overflow completed!! '.$data['val1'].' '.$data['player'].'|');
                $doneCID[] = array('cid' => $data['val1'], 'pid' => $data['player']);
            }
        }
        // Now delete the entries from the overflow in rewards DB.  They will be added back in the next 
        // loop if there is still overflow
        if (count($overFlowComp)>0)
        {
            self::DbQuery( "DELETE FROM rewards WHERE bonus_type_id = 99" );
        }

        if (count($doneCID) > 0)  // Has Cards done
        {
            // keep track of players that have completed cards to detect overflow
            $overflowDoneCID = array();
            $playerDoneCount = array();
            $playerDonePIDs = array();
            $players = self::loadPlayersBasicInfos();
            foreach ($players as $player_id => $data) 
            {
                $playerDoneCount[$player_id] = 0;
            }
            // Load the completed cards into the global variables
            foreach ($doneCID as $idx => $data)
            {
                if ($playerDoneCount[$data['pid']] == 0)
                {
                    // Find this player in the global card list
                    $idx = $this->findGameStateIdx($data['pid']);
                    // Add card into global completed card list
                    self::setGameStateValue('p'.$idx.'_cid', intval($data['cid']));
                    //self::error('Card recorded in Global '.$idx.' '.self::getGameStateValue('p'.$idx.'_id').' '.self::getGameStateValue('p'.$idx.'_cid'));
                    $playerDoneCount[$data['pid']]++;
                    $playerDonePIDs[] = $data['pid'];
                } else {
                    //self::error('OverFlow Done Cards '.$data['cid'].' pid: '.$data['pid'].'|');
                    $overflowDoneCID[] = array('cid' => $data['cid'], 'pid' => $data['pid']);
                }
            }
            if (count($overflowDoneCID)>0)
            {
                // If there is overflow put them in database
                $sql = "INSERT INTO rewards (bonus_type_id, player, val1) VALUES ";
                $values = array();
                foreach( $overflowDoneCID as $idx => $data )
                {
                    $values[] = "(99,".$data['pid'].",".$data['cid'].")";
                }
                $sql .= implode( $values, ',' );
                self::DbQuery( $sql );
            }
            $playerDonePIDs = array_unique($playerDonePIDs);
            $nPlayers = count($players);
            // Current catcher
            // TODO set a flag such that this block showing all players finishing portals once
            if (self::getGameStateValue('open_portal_round_strt') == 1)
            {
                $catch_id = self::getGameStateValue( 'cur_catcher' );
                $cnt = 1;
                while($cnt<=$nPlayers)
                {
                    if (in_array($catch_id, $playerDonePIDs))
                    {
                        self::notifyAllPlayers( "completedCard", clienttranslate( '${player_name} calls INCANTATUM!'), array(
                            'player_name' => $players[$catch_id]['player_name'],
                            'player_id' => $catch_id,
                            'all_player_ids' => $playerDonePIDs
                        ));    
                    }
                    $cnt++;
                    $catch_id = self::getPlayerAfter($catch_id);
                }
                self::setGameStateValue( 'open_portal_round_strt', 0);
            }
    
            // Go to card dispatcher for resolving each card
            $this->gamestate->nextState( 'compCardsDone_HasDone' );
        } else {
            if (self::getGameStateValue('open_portal_round_strt') == 1)
            {
                // Go to next game state no cards are completed
                self::notifyAllPlayers( "noCompletedCard", clienttranslate( 'No portal cards completed this round'), array(
                        'nCards' => 0
                ));
            }
            $this->gamestate->nextState( 'compCardsDone_NoDone' );
        }
    
    }
    // Dispatch single player active states to resolve each card
    function stDispatchCompCards()
    {
        // Resolving cards in order starting with the current catcher
        //self::error('Entering stDispatchCompCards');
        // Get player information
        $players = self::loadPlayersBasicInfos();
        $nPlayers = count($players);
        // Current catcher
        $catch_id = self::getGameStateValue( 'cur_catcher' );
        $cur_play_id = $catch_id;
        $foundCard = false;
        $cnt = 1;
        while(!$foundCard && $cnt<=$nPlayers)
        {
            // Get players index into global variables
            $idx = $this->findGameStateIdx($cur_play_id);
            $curcid = self::getGameStateValue('p'.$idx.'_cid');
            //self::error('dispatch comp cards '.$cnt.' '.$curcid.' '.$cur_play_id.'|');
            if ($curcid > 0)
            {
                $foundCard = true;
            } else {
                $cnt++;
                $cur_play_id = self::getPlayerAfter($cur_play_id);
            }
        }
        // If we get through global variables and don't find a card then we 
        // are done resolving cards go back to drawing a token
        //self::error('stDispatchCompCards cnt: '.$cnt.' nplayer: '.$nPlayers.'|');
        if ($cnt>$nPlayers)
        {
            //self::error('stDispathCompCards: Empty of cards');
            self::notifyAllPlayers( 'doneResolvingCards', clienttranslate( 'Done resolving objective cards'), array(
                'nCards' => 0
            ));
            self::setGameStateValue( 'open_portal_round_strt', 1);
            $this->gamestate->nextState( 'noCardsLeft' );
        } else {
            //self::error('Found card to dispatch');
            self::notifyAllPlayers( 'hasCardToResolve', clienttranslate( 'Resolving Card'), array(
                'nCards' => 1
            ));
            // Found card to dispatch
            // Load card data in global variables
            self::setGameStateValue('res_id', self::getGameStateValue('p'.$idx.'_id'));
            self::setGameStateValue('res_cid', self::getGameStateValue('p'.$idx.'_cid'));
            //self::error('Dispatching '.self::getGameStateValue('res_id').' '.self::getGameStateValue('res_cid'));
            // Remove card from global variabes
            self::setGameStateValue('p'.$idx.'_cid', 0);
            // set the active player based on global variable
            $player_id = self::getGameStateValue('res_id');
            //self::error('stSetCardPlayer set active '.$player_id);
            $this->gamestate->changeActivePlayer($player_id);
            self::giveExtraTime($player_id);
            //self::error('Going to remove gems');
            // Goto resolve card state
            $this->gamestate->nextState( 'resolveBonus' );
        }
    }
    // This function will adjust score based on card and also dispach
    // any actions that will be needed to update the rules 
    function stScoreAdjustRules()
    {
        //self::error('Entering stScoreAdjustRules');
        // Player with completed card
        $player_id = self::getGameStateValue('res_id');
        // Card to that is completed
        $card_id = self::getGameStateValue('res_cid');
        // Protect all of this from zombie player
        if (!$this->isZombie($player_id))
        {
            // Victory point value of card from materials
            $card_obj_info = $this->port_cards[$card_id];
            $card_vp = $card_obj_info['card_vp'];
            // End of game points from card
            $doneCards = self::getObjectListFromDB( "SELECT card_id, card_location, card_location_arg
                                                        FROM portcards
                                                        WHERE card_location = 'done' AND card_location_arg = '".$player_id."'");
            $eog_vp_results = $this->delta_EOG_scoring($doneCards, $card_id);
            // Add portal and animus bonus points
            $total_vp = 0;
            $portal_vp = $eog_vp_results[0];
            $animus_vp = $eog_vp_results[1];
            $total_vp = $card_vp + $portal_vp + $animus_vp;

            // update DB with score
            self::DbQuery( "UPDATE player SET player_score=player_score+".$total_vp." WHERE player_id='".$player_id."'");
            //self::error('Player: '.$player_id.' vp: '.$card_vp.' Bonus Number: '.$card_obj_info['card_bonus']);
            // update stats for score from card
            self::incStat($card_vp, 'total_card_points', $player_id);
            // Get color of card
            $color_map = array('green'=>'green_card_points', 'yellow'=>'yellow_card_points', 
                    'purple'=>'purple_card_points', 'blue'=>'blue_card_points');
            $card_color = $card_obj_info['card_color'];
            self::incStat($card_vp, $color_map[$card_color], $player_id);
            self::incStat($total_vp, 'total_card_points', $player_id);
            self::incStat($portal_vp + $animus_vp, 'green_card_points', $player_id);        

                    
            $players = self::loadPlayersBasicInfos();
            self::notifyAllPlayers( 'scoreAdjust', clienttranslate( '${player_name} scores ${total_vp} total points; ${card_vp} from card, ${portal_vp} from portal bonus, and ${animus_vp} from animus bonus'), array(
                'player_name' => $players[$player_id]['player_name'],
                'card_vp' => $card_vp,
                'player_id' => $player_id,
                'total_vp' => $total_vp,
                'portal_vp' => $portal_vp,
                'animus_vp' => $animus_vp
            ));
            // Zero out the global completed card values
            self::setGameStateValue('res_id', 0);
            self::setGameStateValue( 'res_cid', 0);

            // Here is where we score rewards for 3 of akind portals or one of each
            // Get information about done portal cards for this player
            //self::error('See if anyone has 3 of a kind reward');
            $doneCards = self::getObjectListFromDB( "SELECT card_id, card_location, card_location_arg
                                    FROM portcards
                                    WHERE card_location = 'done' AND card_location_arg = ".$player_id);
            $endOfGame_PortalBonus_Data = $this->calculate_EOG_PortalBonus($doneCards);
            $portal_count_arrays = $endOfGame_PortalBonus_Data[1]; // portal data is second item in returned array
            //self::error('Got portal counts for player');
            $reward_ids = array(0, 32, 30, 31, 33, 34); // data for each reward in $this->bonus_data in material.inc.php 
                                                // Also converts the integer color type to the correct entry in bonus data
                                                // as they aren't in the correct order in the bonus_data
            $reward_txt = array(
            '',
            clienttranslate('${player_name} opens three green portals and receives a ${add_vp} VP bonus'),
            clienttranslate('${player_name} opens three yellow portals and receives a ${add_vp} VP bonus'),
            clienttranslate('${player_name} opens three purple portals and receives a ${add_vp} VP bonus'),
            clienttranslate('${player_name} opens three blue portals and receives a ${add_vp} VP bonus'),
            clienttranslate('${player_name} opens at least one portal of each color and receives a ${add_vp} VP bonus')     
            ); 
            // Go through each portal color and see if the player has 3
            // To simplify this loop add a 5th element to $portal_count_array
            $portal_count_arrays[$player_id][5] = 0;
            for ($ii = 1; $ii<=5; $ii++) {
                if ($portal_count_arrays[$player_id][$ii] == 3 || ($ii == 5 && $portal_count_arrays[$player_id][1]>=1 && $portal_count_arrays[$player_id][2]>=1 && $portal_count_arrays[$player_id][3]>=1 && $portal_count_arrays[$player_id][4]>=1)) {
                    //self::error('Potential reward');
                    // Player has 3 of one kind or one of each see if the reward was given away yet
                    $rewardsGiven = self::getObjectListFromDB( "SELECT player FROM rewards WHERE bonus_type_id = 9 AND val1 = ".$ii);
                    if (count($rewardsGiven) == 0)
                    {  // Reward has not been given yet reward it to this player
                        // Add to DB
                        //self::error('Reward is awarded');
                        self::DbQuery( "INSERT INTO rewards (bonus_type_id, player, val1) VALUES (9,".$player_id.",".$ii.")");
                        $add_vp = $this->bonus_data[$reward_ids[$ii]]['val2'];
                        // Add to player's score in DB
                        self::DbQuery( "UPDATE player SET player_score=player_score+".$add_vp." WHERE player_id='".$player_id."'");
                        // Notify players
                        self::notifyAllPlayers( 'rewardPlayerType9', $reward_txt[$ii], array(
                            'player_name' => $players[$player_id]['player_name'],
                            'add_vp' => $add_vp,
                            'player_id' => $player_id,
                            'type' => $ii
                        ));
            
                    }
                }
            }

            // See if we need to ask player if the want the portal count reward
            // or move on to resolving bonus
            $doneCards = self::getObjectListFromDB( "SELECT card_id, card_location, card_location_arg
                                    FROM portcards
                                    WHERE card_location = 'done' AND card_location_arg = ".$player_id);
            $nDoneCards = count($doneCards);
            if ($nDoneCards >=2 && $nDoneCards <=6)
            {
                // Get the rewards of type 10 from DB
                $rewardsGiven = self::getObjectListFromDB( "SELECT player, val1 FROM rewards WHERE bonus_type_id = 10");
                $player_id_array = array_values(array_column($rewardsGiven, 'player'));
                $portal_count_array = array_values(array_column($rewardsGiven, 'val1'));
                // has this player claimed a reward yet?
                if (!in_array($player_id, $player_id_array)) {
                    // has this portal count been claimed yet?
                    if (!in_array($nDoneCards, $portal_count_array)) {
                        if ($nDoneCards == 6) // Reward the last portal count reward automatically
                        {
                            // Add reward to DB
                            self::dbQuery( "INSERT INTO rewards (bonus_type_id, player, val1) VALUES (10,".$player_id.",".$nDoneCards.")");
                            // Adjust players score
                            self::DbQuery( "UPDATE player SET player_score=player_score+10 WHERE player_id='".$player_id."'");
                            self::notifyAllPlayers( 'resolvePortalCount', clienttranslate( '${player_name} claims reward for having ${nDone} open portals'), array(
                                'player_name' => $players[$player_id]['player_name'],
                                'add_vp' => 10,
                                'player_id' => $player_id,
                                'nDone' => $nDoneCards
                            ));

                        } else {
                            // Ask player if they want the reward
                            $this->gamestate->changeActivePlayer($player_id);
                            $this->gamestate->nextState( 'resolvePortalCount' );
                            return;
                        }
                    }
                }
            }
        }
        // Done assigning automatic awards
        // Go to card bonus resolving state
        $this->gamestate->nextState ( 'checkCompleteCards' );

    }

    // Remove Gems from an opened/completed portal
    function stRemoveGems()
    {
        //self::error('Entering stRemoveGems');
        // Player with completed card
        $player_id = self::getGameStateValue('res_id');
        // Card to that is completed
        $card_id = self::getGameStateValue('res_cid');
        // Protect all of this from zombie player
        if (!$this->isZombie($player_id))
        {
            // Now need to move gems off card back to players gem zone location
            // First get the information about gems before moving them
            $gemSentHome = $this->gems->getCardsInLocation($card_id);
            // Now move them.  moveAllCardsInLocation doesn't return info about cards moved
            // Hence we do he query beforehand
            $this->gems->moveAllCardsInLocation($card_id, 0);

            // Move the players completed card to their completed card area
            // The completed card is stored in global variable
            $old_card_id = self::getGameStateValue('res_cid');
            $n_done = $this->portcards->countCardInLocation('done', $player_id);
            $this->portcards->moveCard($old_card_id, 'done', $player_id);
            // Set type_arg (order done) for this card
            $thisn = $n_done + 1;
            self::DbQuery( "UPDATE portcards SET card_type_arg=".$thisn." WHERE card_id='".$old_card_id."'");
            // Record portal card opening in stats
            self::incStat(1, 'total_cards_open', $player_id);
            // Get color of card
            $color_map = array('green'=>'green_cards_open', 'yellow'=>'yellow_cards_open', 
            'purple'=>'purple_cards_open', 'blue'=>'blue_cards_open');
            $card_color = $this->port_cards[$old_card_id]['card_color'];
            $card_color_opened = $card_color;
            //self::error('Open stats: '.$card_color.' '.implode('|', array_keys($color_map)).' '.implode('|', array_values($color_map)).'|');
            self::incStat(1, $color_map[$card_color], $player_id);        

            // Move the gems of player off card
            self::notifyAllPlayers( "removeGems", clienttranslate( '${player_name} is removing crystals from opened portal.'), 
                            array(
                                'player_name' => self::getActivePlayerName(),
                                'card_id' => $card_id,
                                'player_id' => $player_id,
                                'gem_home_array' => array_values($gemSentHome),
                                'card_color' => $card_color_opened
                            ));

        }
        // Go to card bonus resolving state
        $this->gamestate->nextState ( 'resolveBonus' );
    }

    function stResolveBonus()
    {
        //self::error('Entering stResolveBonus');
        // Player with completed card
        $player_id = self::getGameStateValue('res_id');
        // Card to that is completed
        $card_id = self::getGameStateValue('res_cid');
        // Protect all of this from zombie player
        if (!$this->isZombie($player_id))
        {
            // card info from material.inc.php
            $card_obj_info = $this->port_cards[$card_id];
            // Player information
            $players = self::loadPlayersBasicInfos();
        
            // Here is where we resolve portal card bonuses
            $card_bonus_id = $card_obj_info['card_bonus'];
            if ($card_bonus_id > 0)
            { 
                $card_bonus_data = $this->bonus_data[$card_bonus_id];
                $card_bonus_type = $card_bonus_data['card_bonus_type'];
                //self::error('Card Bonus Id '.$card_bonus_id.' type: '.$card_bonus_type);
                switch ($card_bonus_type) 
                {   // 8 == Wild card type
                    case 8:
                        //self::error('Wild card portal bonus');
                        $wild_type = $card_bonus_data['val1'];  // animus type that is wild for player
                        // Insert this wild type bonus into reward database for player
                        self::DbQuery("INSERT INTO rewards (bonus_type_id, player, val1) VALUES (8,".$player_id.",".$wild_type.")");
                        self::notifyAllPlayers( 'wildAnimus', clienttranslate( 'Portal card bonus: ${animus_name} Animus type is now a wild card for ${player_name}'), array(
                            'player_name' => $players[$player_id]['player_name'],
                            'animus_name' => $this->token_types[$wild_type]['name']
                        ));        
                        break;

                    // 6 == Extra Gems to zone
                    case 6:
                        //self::error('Extra Gems to zone');
                        $gem_n = $card_bonus_data['val1']; // Number of gems to add to players gem zone
                        // Query how many gems player currently has
                        $gemDBData = self::getObjectListFromDB( "SELECT card_type, card_type_arg, card_location, card_location_arg FROM gems WHERE card_type = ".$player_id);
                        $nGems = count($gemDBData); // gems are zero-based counting
                        //self::error('Bonus N: '.$gem_n.' PrevN: '.$nGems.'|');
                        for ($ii=0; $ii<$gem_n; $ii++) {
                            $markers  = array(array('type' => $player_id, 'type_arg' => $ii+$nGems, 
                                            'nbr' => 1));
                            $this->gems->createCards($markers, 0, $ii+$nGems);
                        }
                        $plural_str = '';
                        if ($nGems>1) {
                            $plural_str = 's';
                        }
                        self::notifyAllPlayers( 'addGems', clienttranslate( 'Portal card bonus: ${player_name} gets a bonus ${gem_cnt} crystal${plural_str} '), array(
                            'player_name' => $players[$player_id]['player_name'],
                            'gem_cnt' => $gem_n,
                            'plural_str' => $plural_str,
                            'player_id' => $player_id,
                            'prev_gemn' => $nGems
                        ));        
                        break;
                    // 3 == Extra gem placement on animus type
                    case 3:
                        //self::error('Extra Gem Placement');
                        $gem_type = $card_bonus_data['val1']; // Type of animus to play on
                        $gem_count = $card_bonus_data['val2']; // number of extra gems to play
                        //self::error('anitype: '.$gem_type.' cnt: '.$gem_count.'|');
                        // Setup the global variables to deal with extra gem placement
                        self::setGameStateValue( 'gem_id', $player_id);
                        self::setGameStateValue( 'gem_type', $gem_type);
                        self::setGameStateValue( 'gem_count', $gem_count);
                        self::setGameStateValue( 'gem_strt_count', $gem_count);
                        // Announcing portal card bonus
                        self::notifyAllPlayers( 'announceBonusGems', clienttranslate( 'Portal card bonus: ${player_name} gets bonus crystals to play'), array(
                            'player_name' => $players[$player_id]['player_name']
                        ));
                        $this->gamestate->nextState( 'dispatchExPlayGem' );
                        return;
                    // 7 == Rearrange all gems on cards
                    case 7:
                        //self::error('resolving Rearrange Gems');
                        $gem_type = 7; // Gems can be placed anywhere
                        // Need to get the number of gems this player has on cards
                        $gemDBData = self::getObjectListFromDB( "SELECT card_id, card_type, card_type_arg, card_location, card_location_arg FROM gems WHERE card_type = ".$player_id." AND card_location > 0");
                        $gem_count = count($gemDBData);
                        if ($gem_count == 0) 
                        {  // Catch situation where the player has no tokens left on cards
                            self::notifyPlayer( $player_id, 'noGemsForRearrange', clienttranslate( 'Portal card bonus: You do not have any crystals left on portal cards and have no crystals to rearrange'), array(
                                'player_name' => $players[$player_id]['player_name']
                            ));
                            break;
                        } else {
                            // Has gems to place
                            // First remove the gems from portal card the player's zone
                            $card_id_list = array_values(array_column($gemDBData, 'card_id'));
                            $card_type_args = array_values(array_column($gemDBData, 'card_type_arg'));
                            $this->gems->moveCards($card_id_list, 0);
                            //self::error('anitype: '.$gem_type.' cnt: '.$gem_count.'|');
                            // Setup the global variables to deal with extra gem placement
                            self::setGameStateValue( 'gem_id', $player_id);
                            self::setGameStateValue( 'gem_type', $gem_type);
                            self::setGameStateValue( 'gem_count', $gem_count);
                            self::setGameStateValue( 'gem_strt_count', $gem_count);
                            // Announcing portal card bonus
                            self::notifyAllPlayers( 'announceRearrangeGems', clienttranslate( 'Portal card bonus: ${player_name} is rearranging crystals'), array(
                                'player_name' => $players[$player_id]['player_name'],
                                'card_id_list' => $card_id_list,
                                'card_type_args' => $card_type_args,
                                'player_id' => $player_id
                            ));
                            $this->gamestate->nextState( 'dispatchExPlayGem' );
                            return;
                        }
                    // 5 == add an extra portal to your hand
                    case 5: 
                        //self::error('Resolve bonus to add a portal to your hand');
                        $this->gamestate->changeActivePlayer($player_id);
                        $this->gamestate->nextState('chooseNewCardBonus');
                        return;
                    // 4 == Complete a portal
                    case 4:
                        //self::error('Resolve bonus to complete portal');
                        $this->gamestate->changeActivePlayer($player_id);
                        $this->gamestate->nextState('completePortalBonus');
                        return;
                    default:
                        //self::error('Card Bonus Type: '.$card_bonus_type.' not implemented yet');
                        break;
                }
            }
        }

        $this->gamestate->nextState( 'activatePortal' );

    }

    // Check whether the game has ended
    function stCheckEndGame() 
    {
        //self::error('Entering stCheckEndGame');
        // Count number of completed cards for each player
        $players = self::loadPlayersBasicInfos();
        // Get completed cards
        $compData = self::getObjectListFromDB( "SELECT card_id, card_location, card_location_arg
                                    FROM portcards
                                    WHERE card_location = 'done'");
        $cardPIDs = array_column($compData, 'card_location_arg');
        $cardCountAssocArray = array_count_values($cardPIDs);
        $cardCounts = array_values($cardCountAssocArray);
        if (count($cardCounts) > 0)
        {
            $maxCardCounts = max($cardCounts);
            # Update DB with tie breaker which is number of done portals
            foreach ($cardCountAssocArray as $player_id=>$n_open_portal)
            {
                // update DB  with tie breaker score
                //self::error('Player: '.$player_id.' open portals: '.$n_open_portal.'|');
                self::DbQuery( "UPDATE player SET player_score_aux=".$n_open_portal." WHERE player_id='".$player_id."'");
            }
        } else {
            $maxCardCounts = 0;
        }
        //self::error('Max Completed Cards: '.$maxCardCounts.'|');
        if ($maxCardCounts >= 7) 
        {
            // TEST TEST TIE BREAKER
            // SET Everyoens score to 1
            //foreach ($players as $player_id=>$player_data)
            //{
            //    self::DbQuery("UPDATE player SET player_score=1 WHERE player_id='".$player_id."'");
            //}
            // TEST END TIE BREAKER
            $gameEnded = true;
            $this->gamestate->nextState( 'endGame' );
        } else {
            $gameEnded = false;
            $this->gamestate->nextState( 'gameNotEnded' );
        }
    } 
    // End Game Scoring bonuses
    // Now defunct scoring green card bonus in realtime of game
    // function stEndGameScoring()
    // {
    //     //self::error('Entering stEndGameScoring');
    //     // Get information about done portal cards 
    //     $doneCards = self::getObjectListFromDB( "SELECT card_id, card_location, card_location_arg
    //                                 FROM portcards
    //                                 WHERE card_location = 'done'");

    //     // Player Info
    //     $players = self::loadPlayersBasicInfos();
    //     //$player_id_list = array_keys($players);
    //     $endOfGame_PortalBonus_Data = $this->calculate_EOG_PortalBonus($doneCards);
    //     $endOfGame_AnimusBonus_Data = $this->calculate_EOG_AnimusBonus($doneCards);
    //     // For each player add the portal and animus bonus to score and report
    //     foreach ($players as $player_id=>$player_data)
    //     {
    //         // Protect against scoring for zombie
    //         if (!$this->isZombie($player_id))
    //         {
    //             $portal_score = $endOfGame_PortalBonus_Data[0][$player_id];
    //             $animus_score = $endOfGame_AnimusBonus_Data[0][$player_id];
    //             $add_score = $portal_score + $animus_score;
    //             $player_portal_count = array_values($endOfGame_PortalBonus_Data[1][$player_id]);
    //             $n_open_portal = array_sum($player_portal_count);
    //             // update DB with score
    //             self::DbQuery( "UPDATE player SET player_score=player_score+".$add_score." WHERE player_id='".$player_id."'");
    //             //self::error('Player: '.$player_id.' vp: '.$add_score);
    //             // update DB  with tie breaker score
    //             self::DbQuery( "UPDATE player SET player_score_aux=".$n_open_portal." WHERE player_id='".$player_id."'");
    //             // update stats for score from green cards
    //             self::incStat($add_score, 'total_card_points', $player_id);
    //             self::incStat($add_score, 'green_card_points', $player_id);        


    //             self::notifyAllPlayers( 'eogBonusScoring', clienttranslate( '${player_name} scores ${portal_score} points for the Portal Card bonus and scores ${animus_score} points for the Animus bonus'), array(
    //                 'player_name' => $player_data['player_name'],
    //                 'portal_score' => $portal_score,
    //                 'animus_score' => $animus_score,
    //                 'player_id' => $player_id
    //             ));
    //         }
    //     }
    //     $this->gamestate->nextState( 'endGame' );

    // }

    // This function will dispatch extra gem placements from the bonus type = 3 
    // stScoreAdjustRules looks for bonus type 3 cards and if it finds them fills
    // out the global variables 'gem_id'=pid; 'gem_type'=animus type for bonus placements; 'gem_count'= number of bonus gems
    // This fundtion will call state exPlayGem gem_count times so the player can place 
    function stDispatchExPlayGem()
    {
        //self::error('Entering stDispatchExPlayGem');
        $pid = self::getGameStateValue( 'gem_id' );
        $cnt = self::getGameStateValue( 'gem_count' );
        $ani_type = self::getGameStateValue( 'gem_type' );
        if ($cnt > 0) {  // Still has gems to play
            //self::error('Checking if there are possible places to play');
            $return_item = $this->getAvailGemPlaces($pid, $ani_type);
            if (count($return_item)>0)
            {
                //self::error('stDispatchExPlayGem set active '.$pid);
                $this->gamestate->changeActivePlayer($pid);
                $this->gamestate->nextState('exPlayGem');
            } else {
                // No places to play
                // Zero out the bonus gem placement globals
                self::setGameStateValue( 'gem_id', 0);
                self::setGameStateValue( 'gem_type', 0);
                self::setGameStateValue( 'gem_count', 0);
                self::setGameStateValue( 'gem_strt_count', 0);
                self::notifyPlayer($pid, "noPlaceToPlay", clienttranslate( 'There are no animus locations available to play the bonus received from the portal card'), 
                            array(
                                    'player_name' => self::getCurrentPlayerName()
                ));
                $this->gamestate->nextState("doneDispatchExPlayGem");
            }
        } else {
            // Zero out the bonus gem placement globals
            self::setGameStateValue( 'gem_id', 0);
            self::setGameStateValue( 'gem_type', 0);
            self::setGameStateValue( 'gem_count', 0);
            self::setGameStateValue( 'gem_strt_count', 0);
            $this->gamestate->nextState("doneDispatchExPlayGem");
        }
    }
//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
        
        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message. 
    */

    function zombieTurn( $state, $active_player )
    {
        $statename = $state['name'];
        // Deactivate pending plays, remove gems from cards back to zone, etc.
        // General cleanup after player, so they won't activate anymore actions.  Essentially
        // pass on all gem placements, so the game will just continue on without them.
        // Gem placement puts things in the global variables zeros those out for this player
        //self::error('Running Zombie pid: '.$active_player.'|');
        $idx = $this->findGameStateIdx($active_player);        
        self::setGameStateValue( 'p'.$idx.'_cid', 0);
        self::setGameStateValue( 'p'.$idx.'_gemn', -1);
        self::setGameStateValue( 'p'.$idx.'_sptn', -1);
        self::setGameStateValue( 'p'.$idx.'_cid1', -1);
        self::setGameStateValue( 'p'.$idx.'_cid2', -1);
        // Remove any completed cards in overflow reward database
        self::DbQuery( "DELETE FROM rewards WHERE bonus_type_id = 99 AND player = '".$active_player."'" );
        // Remove any gems on cards and move them to zone
        $gemData = $this->gems->getCardsOfType($active_player);
        $gemData_id = array_column($gemData, 'id');
        $this->gems->moveCards($gemData_id, 0, 0);
        //self::error('Done zombie cleanup');

        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                case "exPlayGem":
                    // Zero out the bonus gem placement globals
                    self::setGameStateValue( 'gem_id', 0);
                    self::setGameStateValue( 'gem_type', 0);
                    self::setGameStateValue( 'gem_count', 0);
                    self::setGameStateValue( 'gem_strt_count', 0);
                    $this->gamestate->nextState("zombiePass");
                    break;

                default:
                    //self::error('Active zombie state: '.$statename.'|');
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            //self::error('Multiactive zombie state: '.$statename.'|');
            switch ($statename)
            {
                case "chooseInitCards":
                    // In order to handle zombie correctly here we need to mimic the behavior
                    // of chooseInitCards.  This is needed because
                    // other players choices are not written to DB yet, but kept in global variables
                    // this is all handled in chooseInitCards

                    // The client JS side assumes to receive 3 cards to discard for all players
                    // so select three random cards from zombie player
                    $pcardData = $this->portcards->getPlayerHand($active_player);
                    $pcardData_id = array_column($pcardData, 'id');

                    // set  the global card ids for the zombie player
                    self::setGameStateValue('p'.$idx.'_cid', $pcardData_id[0]);
                    self::setGameStateValue('p'.$idx.'_cid1', $pcardData_id[1]);
                    self::setGameStateValue('p'.$idx.'_cid2', $pcardData_id[2]);

                    // Check to see if this is the last player to submit results
                    //self::error('Zombie active n players '.count($this->gamestate->getActivePlayerList()).'|');

                    if (count($this->gamestate->getActivePlayerList()) == 1)
                    {
                        $nplayers = self::getGameStateValue('orig_n_player');
                        $cid_array = $this->getPlayerArrayFromGameState('_cid', $nplayers);
                        $pid_array = $this->getPlayerArrayFromGameState('_id', $nplayers);
                        $cid1_array = $this->getPlayerArrayFromGameState('_cid1', $nplayers);
                        $cid2_array = $this->getPlayerArrayFromGameState('_cid2', $nplayers);
                        $cid_array = array_merge($cid_array, $cid1_array, $cid2_array);
                        $pid_array = array_merge($pid_array, $pid_array, $pid_array);
                        // Notify all players to update other players discards
                        self::notifyAllPlayers("allPlayersInitCardsChosen", clienttranslate( 'All players have finished choosing initial portal cards'),
                            array(
                                'card_ids' => $cid_array,
                                'player_ids' => $pid_array
                            ));
                        //self::error('Notified all players that discarding cards is finished');
                        // Now actually commit to DB the discards
                        $this->portcards->moveCards($cid_array, 'discard');
                        // Reset the global values to their empty state
                        self::resetGameState('_cid', 0);
                        self::resetGameState('_cid1', -1);
                        self::resetGameState('_cid2', -1);
                        $this->gamestate->setPlayerNonMultiactive( $active_player, 'zombiePass' );
                        break;
                    }

                case "placeGem":
                    // Similar issue to chooseInitcards ,but not nearly as complicated just call
                    $this->determine_placeGem_NextState($active_player);
                    break;
                default: 
                    $this->gamestate->setPlayerNonMultiactive( $active_player, 'zombiePass' );
                    break;
            }
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
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        // Please add your future database scheme changes here
//
//


    }    
}