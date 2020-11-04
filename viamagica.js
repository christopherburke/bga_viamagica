/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * ViaMagica implementation : © Christopher J. Burke <christophjburke@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * viamagica.js
 *
 * ViaMagica user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock",
    "ebg/zone"
],
function (dojo, declare) {
    return declare("bgagame.viamagica", ebg.core.gamegui, {
        constructor: function(){
            //console.log('viamagica constructor');
              
            // Here, you can init the global variables of your user interface
            this.tokensz = 101;
            this.tokenszdiscard = 30;
            this.portcardwidth = 123;
            this.portcardheight = 154;
            this.gemsz = 20;
            this.rewardwid = 103;
            this.rewardhgt = 30;
            this.max_width = 1360;
            this.max_token_width = 400;
            this.max_reward_width = 550;
            // Store some client state arguments
            this.clientStateArg = [];
            this.clientStateArg2 = [];
            this.clientStateArg3 = [];
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
            //console.log( "Starting game setup" );
            
            // Setting up player boards
            this.player_colors = [];
            for( var player_id in gamedatas.players )
            {
                var player = gamedatas.players[player_id];
                this.player_colors[player_id] = player['player_color'];         
            }
            // Set up your game interface here, according to "gamedatas"

            // Here is where we do the calculations needed to scale the graphics
            // to the screen width.  Things will scale up to a maximum width
            // The maximum width is added because otherwise as screens get big, the height of cards
            // also get big and one never really gains more information.
            // Get the main container width
            this.maindimen = dojo.position('game_play_area');
            this.mainwidth = Math.min(this.maindimen.w, this.max_width)
            // Portal Card display width
            this.pcw = this.mainwidth*0.714/5.4;
            // Portal Card display height
            this.pch = this.pcw * 1.252;
            this.cardrowheight = this.pch*1.08+'px'
            // Active portal cards have larger size with 5 cards across mainwidth
            this.pcwactive = this.mainwidth/5.2;
            this.pchactive = this.pcwactive*1.252;
            this.activerowheight = this.pchactive*1.15;
            // Need to add the name height
            var namedimen = dojo.position('my_name');
            //console.log('name stats');
            //console.log(namedimen);
            this.activerowheight = this.activerowheight + namedimen.h;
            // Done portal cards have smaller size with 6 cards across mainwidth
            this.pcwdone = this.mainwidth/6.5;
            this.pchdone = this.pcwdone*1.252;
            this.donerowheight = this.pchdone*1.08;

            //console.log('main dimen: ',this.mainwidth, ' ', this.pcw,'x',this.pch);
            // Size the height of the stock portal row
            dojo.setStyle('vmg_portalstockrow', 'height', this.cardrowheight);
            // set height of token area
            var tokenwidth = Math.min(this.max_token_width, this.mainwidth*0.28);

            dojo.setStyle('vmg_token_container', 'height', tokenwidth*0.375*1.421+'px');
            this.usetokensz = Math.min(tokenwidth*0.25, this.tokensz);
            this.tokefraction = this.usetokensz/this.tokensz;
            // sizing the reward cards and areas
            this.userewardw = Math.min(this.mainwidth, this.max_reward_width);
            this.userewardw = this.userewardw/5.4;
            this.userewardh = this.userewardw*0.2912;
            // resize the card tooltips
            dojo.query('.vmg_card-tooltip-holderbar-stock').style('width', this.pcw);
            dojo.query('.vmg_card-tooltip-holderbar-stock').style('height', this.pch*0.7);
            dojo.query('.vmg_card-tooltip-holderbar-active').style('width', this.pcwactive);
            dojo.query('.vmg_card-tooltip-holderbar-active').style('height', this.pchactive*0.7);
            dojo.query('.vmg_card-tooltip-holderbar-done').style('width', this.pcwdone);
            dojo.query('.vmg_card-tooltip-holderbar-done').style('height', this.pchdone*0.7);



	        // Setup the Animus tokens using the Stock class
	        this.tokenDisplay = new ebg.stock(); // New stock object for the tokendisplay area
            this.tokenDisplay.create( this, $('vmg_tokenarea'), this.usetokensz, this.usetokensz );
            this.tokenDisplay.item_margin = 0;
	        // There are 7 Animus token types for the tokenDisplay
	        for (var ii = 0; ii < 7; ii++) { 
		        this.tokenDisplay.addItemType( ii+1, ii+1, g_gamethemeurl + 'img/Tokens_101px.png', ii);
	        } 
	        // If there is a Animus token in the tokenDisplay from DB show it
	        if (gamedatas.tokenDisplay.length > 0)
	        {
                //console.log( "Has token in tokenDisplay" );
                var token_type = gamedatas.tokenDisplay[0].card_type_arg;
                this.tokenDisplay.addToStockWithId( token_type , 1, 'vmg_tokenhelpimage');
                curDiv = this.tokenDisplay.getItemDivId(1);
                document.getElementById(curDiv).style.backgroundSize = '700%';
                dojo.addClass( curDiv, 'vmg_tokenround');
            }
            this.tokenDisplay.setSelectionMode(0);
            var node = dojo.byId('vmg_tokenarea');
            this.addTooltip( node.id, _('Current Animus Token'), '' );
            // HTML tooltip to token reference image
            var air_str = _('6 Air');
            var water_str = _('5 Water');
            var earth_str = _('4 Earth');
            var life_str = _('3 Life');
            var fire_str = _('2 Fire');
            var shadow_str = _('1 Shadow');
            var wild_str = _('2 Wild');
            this.addTooltipHtml($('vmg_tokenhelpimage'), '<div class="vmg_refcard-tooltip-container"><div class="vmg_refcard-tooltip-text"><b>Animus Token Distribution:</b> <br>'+air_str+'<br>'+water_str+'<br>'+earth_str+'<br>'+life_str+'<br>'+fire_str+'<br>'+shadow_str+'<br>'+wild_str+'<div class="vmg_refcard-tooltip-image"></div></div></div>');

            // Add the token counter object that keeps track of the number of tokens in the bag
            this.tokenCounter = new ebg.counter();
            this.tokenCounter.create( $('vmg_tokencount') );
            var node = dojo.byId('vmg_tokencount');
            this.addTooltip(node.id, _('Number of Animus tokens left to draw'), '');
            //console.log( "Token Count" );
            //console.log( gamedatas.tokenDeckCount );
            // Set the token count to number of cards in token deck location
            this.tokenCounter.setValue(gamedatas.tokenDeckCount);
            // Save catcher name
            this.catcher_name = gamedatas.catchername;
            this.catcher_id = gamedatas.catcherid;
            var str_trans = _("Catcher:");
            dojo.place( this.format_block( 'jstpl_catcher_name', {
                player_name: this.catcher_name,
                catcher_translate: str_trans,
                player_color: this.player_colors[this.catcher_id]
            }), 'vmg_portalstockrow');
            node = dojo.byId('vmg_tokencatcher');
            this.addTooltip(node.id, _('Player that draws tokens and breaks ties in carrying out open portal bonus actions. Catcher changes to next player in turn order after drawing the wild token.'), '');

            // Add the token discard stock area
            // Check option first
            this.showTokenDiscard = 1;
            if (gamedatas.showTokenDiscardOption == 0)
            {
                this.showTokenDiscard = 0;
            }
            if (this.showTokenDiscard == 1)
            {
/*                 this.tokenDiscard = new ebg.stock(); // New stock object for the tokendisplay area
                this.tokenDiscard.create( this, $('vmg_tokendiscard'), this.tokenszdiscard*this.tokefraction, this.tokenszdiscard*this.tokefraction );
                this.tokenDiscard.item_margin = 0;
                // There are 7 Animus token types for the tokenDisplay
                for (var ii = 0; ii < 7; ii++) { 
                    this.tokenDiscard.addItemType( ii+1, ii+1, g_gamethemeurl + 'img/Tokens_30px.png', ii);
                } 
                // If there is a Animus token in the tokenDiscard from DB show them
                if (gamedatas.tokenDiscard.length > 0)
                {
                    for (var ii in gamedatas.tokenDiscard)
                    {
                        var token_type = gamedatas.tokenDiscard[ii].card_type_arg;
                        var token_id = gamedatas.tokenDiscard[ii].card_id;
                        this.tokenDiscard.addToStockWithId( token_type , token_id, 'vmg_tokenarea');
                        curDiv = this.tokenDiscard.getItemDivId(token_id);
                        document.getElementById(curDiv).style.backgroundSize = '700%';
                        dojo.addClass( curDiv, 'vmg_tokenround');

                    }
                }
                this.tokenDiscard.setSelectionMode(0);
 */
                this.tokenDiscard = new ebg.zone(); // New zone object for the tokendisplay area
                this.tokenDiscard.create( this, $('vmg_tokendiscard'), this.tokenszdiscard*this.tokefraction, this.tokenszdiscard*this.tokefraction );
                this.tokenDiscard.setPattern('ellipticalfit');
                //console.log('tokenDiscard');
                //console.log(this.tokenDiscard);
                // If there is a Animus token in the tokenDiscard from DB show them
                if (gamedatas.tokenDiscard.length > 0)
                {
                    for (var ii in gamedatas.tokenDiscard)
                    {
                        var token_type = gamedatas.tokenDiscard[ii].card_type_arg;
                        var token_id = gamedatas.tokenDiscard[ii].card_id;
                        var token_backpos = (parseInt(token_type, 10)-1)*(-100);
                        // Generate template object for token
                        dojo.place( this.format_block( 'jstpl_token_discard', {
                            type: token_type,
                            id: token_id,
                            backpos: token_backpos,
                            width: this.tokenszdiscard*this.tokefraction,
                            height: this.tokenszdiscard*this.tokefraction
                        }), 'vmg_token_holder');
                        var elId = this.buildTokenId( token_type, token_id);
                        //console.log(elId);
                        this.tokenDiscard.placeInZone( elId);
                    }
                }
                var node = dojo.byId('vmg_tokendiscard');
                this.addTooltip( node.id, _('Previously Drawn Animus Tokens'), '' );
            }
            // End token discard area

            //Setup the tooltip data
            this.toolTipData = [];
            //console.log('Tool Tip Data loading');
            for (var ii in gamedatas.toolTipData)
            {
                var curData = gamedatas.toolTipData[ii];
                //console.log(curData);
                this.toolTipData[ii] = {type_str:curData.card_type_str, 
                                        point_str:curData.card_point_str,
                                        effect_str:curData.card_effect_str};
            }
            // Setup the reward tooltip data
            this.rewardToolTipData = [];
            for (var ii in gamedatas.rewardToolTipData)
            {
                this.rewardToolTipData[ii] = [];
                curData = gamedatas.rewardToolTipData[ii];
                for (var jj in curData)
                {
                    curStr = curData[jj];
                    this.rewardToolTipData[ii][jj] = curStr;
                }
            }
            //console.log(this.rewardToolTipData);

            // Setup the portal card stock selection area and show cards there using the Stock Class
            this.portCards = this.setup_and_show_portal_cards(gamedatas.portalstock, 'vmg_portalstock', 0, false, this.pcw, this.pch, 'stock');
            this.portCards.setSelectionMode(0);

            // Make portal card stock regions for  active and done portal card areas
            this.otherPlayerActiveCards = [];
            this.otherPlayerDoneCards = [];
            this.otherPlayerIds = []; // This array stores the player id order for setting up these stock objects
            this.otherIdxFromPlayerIds = []; // othe direction
            var otherPlayerNumberActiveCards = [];
            var otherPlayerNumberDoneCards = [];
            var cnt = 0;
            for( var player_id in gamedatas.players )
            {   
                if (this.player_id == player_id){
                    // Active cards
                    this.currentPlayerActiveCards = this.setup_and_show_portal_cards(gamedatas.playeractivearea, 
                        'vmg_myportalstock', player_id, true, this.pcwactive, this.pchactive, 'active') ; 
                    this.currentPlayerActiveCards.setSelectionMode(0);
                    var currentPlayerNumberActiveCards = this.currentPlayerActiveCards.count();
                    //console.log('N Cards: '+player_id+' '+currentPlayerNumberActiveCards+' '+this.currentPlayerActiveCards.count());
                    
                    // Done Cards
                    this.currentPlayerDoneCards = this.setup_and_show_portal_cards(gamedatas.playerdonearea,
                        'vmg_myportaldonestock', player_id, false, this.pcwdone, this.pchdone, 'done');
                    this.currentPlayerDoneCards.setSelectionMode(0);
                    var currentPlayerNumberDoneCards = this.currentPlayerDoneCards.count();
                    // Set the vmg_myarea_container height
                    // Expand players done region if has > 6 cards
                    dojo.setStyle('vmg_myportalactiverow', 'height', this.activerowheight+'px');
                    if (currentPlayerNumberDoneCards > 6) 
                    {
                        dojo.setStyle('vmg_myportaldonerow', 'height', (2.0*this.donerowheight)+'px');
                        dojo.setStyle('vmg_myarea_container', 'height', (this.activerowheight+2.0*this.donerowheight)+'px');
                    } else {
                        dojo.setStyle('vmg_myportaldonerow', 'height', this.donerowheight+'px');
                        dojo.setStyle('vmg_myarea_container', 'height', (this.activerowheight+this.donerowheight)+'px');
                    }
                } else {
                    //Active cards
                    this.otherPlayerActiveCards.push( this.setup_and_show_portal_cards(gamedatas.playeractivearea, 
                    'vmg_playerportalstock_'+player_id, player_id, true, this.pcwactive, this.pchactive, 'active') );
                    //completed cards
                    this.otherPlayerDoneCards.push(this.setup_and_show_portal_cards(gamedatas.playerdonearea, 
                        'vmg_playerportaldonestock_'+player_id, player_id, false, this.pcwdone, this.pchdone, 'done'));
                    this.otherPlayerIds.push(player_id);
                    this.otherIdxFromPlayerIds[player_id] = cnt;
                    otherPlayerNumberActiveCards[player_id] = this.otherPlayerActiveCards[cnt].count();
                    //console.log('N Cards: '+player_id+' '+otherPlayerNumberActiveCards[player_id]);
                    otherPlayerNumberDoneCards[player_id] = this.otherPlayerDoneCards[cnt].count();
                    // Expand players done region if has > 6 cards
                    dojo.setStyle('vmg_playerportalactiverow_'+player_id, 'height', this.activerowheight+'px');
                    if (otherPlayerNumberDoneCards[player_id] > 6) 
                    {
                        dojo.setStyle('vmg_playerportaldonerow_'+player_id, 'height', (2.0*this.donerowheight)+'px');
                        dojo.setStyle('vmg_playerarea_container_'+player_id, 'height', (this.activerowheight+2.0*this.donerowheight)+'px');
                    } else {
                        dojo.setStyle('vmg_playerportaldonerow_'+player_id, 'height', this.donerowheight+'px');
                        dojo.setStyle('vmg_playerarea_container_'+player_id, 'height', (this.activerowheight+this.donerowheight)+'px');
                    }
                     cnt++;
                }
            }
            // Other players portal cards are not selectable
            for ( var arg in this.otherPlayerActiveCards) 
            {
                this.otherPlayerActiveCards[arg].setSelectionMode(0);
                this.otherPlayerDoneCards[arg].setSelectionMode(0);
            }

            // See if we are mid card resolving to add opacity to card
            //this.resolveCardData = gamedatas.cardResolveData;
            //console.log(this.resolveCardData);
            //if (this.resolveCardData.res_id > 0 & this.resolveCardData.res_cid > 0)
            //{
                // Have a card we need to add opacity to
            //    actor_id = this.resolveCardData.res_id;
            //    card_id = parseInt(this.resolveCardData.res_cid);
            //    if (this.player_id == actor_id)
            //    {
            //        var div = this.currentPlayerActiveCards.getItemDivId(card_id);
            //        dojo.setStyle(div, 'opacity', '0.3');
            //    } else {
            //        var idx = this.otherIdxFromPlayerIds[actor_id];
            //        // set opened/completed card with transparency
            //        var div = this.otherPlayerActiveCards[idx].getItemDivId(card_id);
            //        dojo.setStyle(div, 'opacity', '0.3');
            //    }
    
            //}

            // Setup the gem zones for all players
            this.otherPlayerGemZone = [];

            for (var player_id in gamedatas.players )
            {
                if (this.player_id == player_id){
                    this.currentPlayerGemZone = this.setup_and_show_gem_zone(gamedatas.gemData, 
                        'vmg_mygemzone', player_id) ;
                } else {
                    this.otherPlayerGemZone.push( this.setup_and_show_gem_zone(gamedatas.gemData, 
                    'vmg_gemzone_'+player_id, player_id) );
                }               
            }
           
            // Setup the reward cards
            this.rewardDisplay = this.setup_and_show_rewards(gamedatas.rewardData, 'vmg_rewardarea', 0);
            this.rewardDisplay.setSelectionMode(0);
            // Make reward stock regions for all players
            this.otherPlayerRewards = [];
            cnt = 0;
            for( var player_id in gamedatas.players )
            {   
                if (this.player_id == player_id){
                    this.currentPlayerRewards = this.setup_and_show_rewards(gamedatas.rewardData, 
                        'vmg_myrewardstock', player_id) ;
                    this.currentPlayerRewards.setSelectionMode(0);
                } else {
                    this.otherPlayerRewards.push( this.setup_and_show_rewards(gamedatas.rewardData, 
                    'vmg_rewardstock_'+player_id, player_id) );
                    this.otherPlayerRewards[cnt].setSelectionMode(0);
                    cnt++;
                }
            }

            // Setup the players portal and types counters for their score area
            //console.log('Counter Setup');
            //console.log(gamedatas.portalCounts);
            this.portalCounters = [];
            this.yellowCounters = [];
            this.purpleCounters = [];
            this.greenCounters = [];
            this.blueCounters = [];
            for (var player_id in gamedatas.players)
            {
                var curDiv = $('overall_player_board_'+player_id);
                // Make their score board higher 
                var curHght = dojo.getStyle(curDiv, 'height');
                dojo.setStyle(curDiv, curHght+33.0);
                // Place the counters panel onto score board
                dojo.place('vmg_player_board_counters_'+player_id,curDiv,'last');
                dojo.place('vmg_incantatum_'+player_id, curDiv, 'last');
                dojo.setStyle('vmg_incantatum_'+player_id, 'display', 'none');
                var portDone = 0;
                this.portalCounters[player_id] = new ebg.counter();
                this.portalCounters[player_id].create( $('vmg_portal_count_'+player_id) );

                this.yellowCounters[player_id] = new ebg.counter();
                this.yellowCounters[player_id].create( $('vmg_yellow_count_'+player_id) );
                if ('portalCounts' in gamedatas )
                {
                    var curCnt = gamedatas.portalCounts[player_id][2];
                } else {
                    var curCnt = 0;
                }
                this.yellowCounters[player_id].setValue(curCnt);
                portDone = portDone + curCnt;

                this.purpleCounters[player_id] = new ebg.counter();
                this.purpleCounters[player_id].create( $('vmg_purple_count_'+player_id) );
                if ('portalCounts' in gamedatas)
                {
                    var curCnt = gamedatas.portalCounts[player_id][3];
                } else {
                    var curCnt = 0;
                }
                this.purpleCounters[player_id].setValue(curCnt);
                portDone = portDone + curCnt;

                this.greenCounters[player_id] = new ebg.counter();
                this.greenCounters[player_id].create( $('vmg_green_count_'+player_id) );
                if ('portalCounts' in gamedatas)
                {
                    var curCnt = gamedatas.portalCounts[player_id][1];
                } else {
                    var curCnt = 0;
                }
                this.greenCounters[player_id].setValue(curCnt);
                portDone = portDone + curCnt;

                this.blueCounters[player_id] = new ebg.counter();
                this.blueCounters[player_id].create( $('vmg_blue_count_'+player_id) );
                if ('portalCounts' in gamedatas)
                {
                    var curCnt = gamedatas.portalCounts[player_id][4];
                } else {
                    var curCnt = 0;
                }
                this.blueCounters[player_id].setValue(curCnt);
                portDone = portDone + curCnt;

                this.portalCounters[player_id].setValue(portDone);
            }
            // Add tooltips for the open portal counters
            this.addTooltipToClass( 'vmg_portal_icon', '', _('Number of open portals'));
            this.addTooltipToClass( 'vmg_yellow_icon', '', _('Number of yellow type open portals'));
            this.addTooltipToClass( 'vmg_purple_icon', '', _('Number of purple type open portals'));
            this.addTooltipToClass( 'vmg_green_icon', '', _('Number of green type open portals'));
            this.addTooltipToClass( 'vmg_blue_icon', '', _('Number of blue type open portals'));
            
            // Add callback for a screen change support
            //this.mutationObserver = new MutationObserver(this.screenChangeCallback);
            // connect observer to the 'ebd-body' element
            //this.mutationObserver.observe(document.getElementById('ebd-body'), {attributes: true});

            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            //console.log( "Ending game setup" );
        },
       

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            //console.log( 'Entering state: '+stateName );
            //console.log(this.getActivePlayers());
            //console.log('Above Active Players');
            //console.log(this.gamedatas.gamestate.type);
            //console.log('Above Type');
            //console.log(this.gamedatas.gamestate.multiactive);
            //console.log('Above Player List');
            switch( stateName )
            {
            case 'chooseInitCards':
                // Need to reset portal cards in active area to be smaller version to fit all 6 across
                for( var player_id in this.gamedatas.players )
                {
                    if (this.player_id == player_id){
                        //console.log('Current Player ', player_id);
                        this.currentPlayerActiveCards.removeAll();
                        this.currentPlayerActiveCards = this.setup_and_show_portal_cards(this.gamedatas.playeractivearea, 
                            'vmg_myportalstock', player_id, true, this.pcwdone, this.pchdone, 'done') ; 
                    } else {
                        var idx = this.otherIdxFromPlayerIds[player_id];
                        //console.log('Other Player ', player_id, ' idx: ', idx);
                        this.otherPlayerActiveCards[idx].removeAll();
                        this.otherPlayerActiveCards[idx] = this.setup_and_show_portal_cards(this.gamedatas.playeractivearea, 
                            'vmg_playerportalstock_'+player_id, player_id, true, this.pcwdone, this.pchdone, 'done') ;
                        this.otherPlayerActiveCards[idx].setSelectionMode(0); // Turn off cards being selectable
                    }
    
                }
                if (this.isCurrentPlayerActive()) // if player reloads game after making choice this is recalled unless using this if
                {
                    this.currentPlayerActiveCards.setSelectionMode(2); // Allow players cards to be selectable
                }

                break;

            case 'placeGem':
                //console.log(args.args.playerGemNumber);
                //console.log('N possplaces: '+args.args.possiblePlaces.length);
                //console.log(args.args.possiblePlaces);
                // Seems to be a bug put placeGem is a multipleactiveplayer state
                // Sometimes only some players are active, but there is at least one player active
                // in php some players are active via $this->gamestate->setPlayersMultiactive(player_ids
                // This seems to create a condition where onEnteringState is called with nobody listed as active
                // Catch this scenario and 
                if (this.gamedatas.gamestate.type == "multipleactiveplayer" && this.gamedatas.gamestate.multiactive.length == 0)
                {
                    // I need to recalculate if any places can be played by this.player_id
                    //console.log('Empty Active player list detected!');
                    possPlaces = args.args.possiblePlaces;
                    var foundAny = false;
                    for (var idx in possPlaces)
                    {
                        pid = possPlaces[idx]['pid'];
                        if (this.player_id == pid)
                        {
                            foundAny = true;
                            break;
                        }
                    }
                    //console.log('Find any? '+foundAny);
                    if (foundAny)
                    {
                        //console.log('Update Game Places for Player');
                        this.updatePossibleGemPlaces( args.args.possiblePlaces );
                        this.addClickToGems( args.args.playerGemNumber );
                        this.clientStateArg = [];
                        this.clientStateArg2 = [];
                        this.clientStateArg3 = [];
                    } 

                } else {
                    // We would get here on a user reload during this action and as far as I can tell
                    // The active players left will be correctly populated in this case, thus we can use
                    // this.isCurrentPlayerActive() to see if we should setup things for player
                    //console.log('Found an active players list');
                    if (this.isCurrentPlayerActive()) // Multiplayer active state protect against this if reload after submission
                    {
                        //console.log('Update Game Places for Player');
                        this.updatePossibleGemPlaces( args.args.possiblePlaces );
                        this.addClickToGems( args.args.playerGemNumber );
                        this.clientStateArg = [];
                        this.clientStateArg2 = [];
                        this.clientStateArg3 = [];
                    }
                }
                break;

            case 'completeCard':
                //console.log(args);
                if (this.player_id == args.args.player_id)
                {
                    this.portCards.setSelectionMode(1);
                    this.avoid_card_ids = args.args.avoid_card_ids;
                    //console.log(this.avoid_card_ids);
                }
                break; 

            case 'exPlayGem':
                //console.log(args);
                this.updatePossibleGemPlaces( args.args.possiblePlaces);
                this.addClickToGems( args.args.playerGemNumber);
                this.clientStateArg = [];
                this.clientStateArg2 = [];
                this.clientStateArg3 = [];
                break;

            case 'chooseNewCardBonus':
                //console.log(args);
                if (this.player_id == args.args.player_id)
                {
                    this.portCards.setSelectionMode(1);
                    this.avoid_card_ids = args.args.avoid_card_ids;
                    //console.log(this.avoid_card_ids);
                }
                break; 
            
            case 'completePortalBonus':
                if (this.isCurrentPlayerActive())
                {
                    this.currentPlayerActiveCards.setSelectionMode(1);
                }
                break;
            case 'dummmy':
                break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            //console.log( 'Leaving state: '+stateName );
            
            switch( stateName )
            {
            case 'chooseInitCards':
                // Cards are reset to normal size in the notification functions

                break;

            case 'completeCard':
                this.portCards.unselectAll();
                this.portCards.setSelectionMode(0);
                break;
            case 'chooseNewCardBonus':
                this.portCards.unselectAll();
                this.portCards.setSelectionMode(0);
                break;
            case 'completePortalBonus':
                if (!this.isSpectator)
                {
                    this.currentPlayerActiveCards.unselectAll();
                    this.currentPlayerActiveCards.setSelectionMode(0);
                }
                break;
            case 'exPlayGem':
                dojo.destroy('vmg_placegem_pass_button');
                dojo.destroy('vmg_placegem_reset_button');
                dojo.destroy('vmg_placegem_confirm_button');
                this.placeGemCleanup();              
                break;
            case 'placeGem':
                dojo.destroy('vmg_placegem_pass_button');
                dojo.destroy('vmg_placegem_reset_button');
                dojo.destroy('vmg_placegem_confirm_button');
            case 'dummmy':
                break;
            }               
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function( stateName, args )
        {
            //console.log( 'onUpdateActionButtons: '+stateName );
            //console.log(this.getActivePlayers());
            //console.log('Above Active Players');
                      
            if( this.isCurrentPlayerActive() )
            {            
                switch( stateName )
                {
                    case 'chooseInitCards':
                        this.addActionButton( 'chooseInitCards_Done_Button', _('Done'), 'select_chooseInitCards_done_button');
                        break;

                    case 'placeGem':
                        // Add pass button to not place gem
                        this.addActionButton( 'vmg_placegem_pass_button', _('Pass'), 'select_placegem_pass_button');
                        // Add reset button to reset partial choices
                        this.addActionButton( 'vmg_placegem_reset_button', _('Reset Selection'), 'select_placegem_reset_button');
                        break;
                    case 'completeCard':
                        this.addActionButton( 'newPortCard_Done_Button', _('Done'), 'select_newPortCard_done_button');
                        break;
                    case 'exPlayGem':
                        // Add pass button to not place gem
                        this.addActionButton( 'vmg_placegem_pass_button', _('Pass'), 'select_placegem_pass_button');
                        this.addActionButton( 'vmg_placegem_reset_button', _('Reset Choice'), 'select_placegem_reset_button');
                        break;
                    case 'resolvePortalCount':
                        this.addActionButton( 'resolveportalcount_yes_button', _('Yes'), 'select_resolvePortalCount_yes_button', null, false, 'blue');
                        this.addActionButton( 'resolveportalcount_no_button', _('No'), 'select_resolvePortalCount_no_button', null, false, 'red');
                        break;
                    case 'chooseNewCardBonus':
                        this.addActionButton( 'newCardBonus_Done_Button', _('Done'), 'select_newCardBonus_done_button');
                        break;
                    case 'completePortalBonus':
                        this.addActionButton( 'completePortalBonus_Done_Button', _('Done'), 'select_completePortalBonus_done_button');
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
        setup_and_show_portal_cards : function(card_array, loc_css_id, player_id, addspots, cw, ch, card_type)
        {
            //console.log('setup_and_show_portal_cards '+loc_css_id+' '+player_id);
            var cards = new ebg.stock(); // New stock object for the objective cards
            cards.create( this, $(loc_css_id), cw, ch);
            cards.setSelectionAppearance('class');  // use class .stockitem_selected to set appearance
            // In the portal card sprite there are 6 per row with 7 rows for 41 cards
            cards.image_items_per_row = 6;
            // There are 41 types of portal cards (40 +1card back) and each card is used twice for 81 cards
            for (var ii = 0; ii < 81; ii++) {
                // need to map the card id to sprite position
                if (ii == 0)
                {
                    jj = 0; //background card just once
                } else {
                    jj = Math.floor((ii + 1)/2);
                }
                //console.log('Card Map ii: '+ii+' jj: '+jj);
                cards.addItemType( ii, ii, g_gamethemeurl + 'img/Cards_340px.jpg', jj);
            }
            // Now play cards in area if there are any
            // player_id == 0 is the main area not a player
            var use_from = $('vmg_tokenhelpimage');
            for (var ii = 0; ii < card_array.length; ii++)
            {
                if (card_array[ii].card_location_arg == player_id) 
                {
                    cards.addToStockWithId(card_array[ii].card_id, card_array[ii].card_id, use_from);
                    // update background size to resize images
                    var divid = cards.getItemDivId(card_array[ii].card_id);
                    document.getElementById(divid).style.backgroundSize = '600%';
                    if (addspots) 
                    {
                        this.addSpotsToCard(cards, card_array[ii].card_id);
                        // Cards with spots (i.e., players hand) have much longer delay for tool tip
                        // This is no longer the case the tooltip now only covers the lower 1/4 of card rather than full card
                        this.addCardToolTip(cards, card_array[ii].card_id, 500, card_type);

                    } else
                    {
                        this.addCardToolTip(cards, card_array[ii].card_id, 200, card_type);
                    }
                }
            }
            return cards;
        },

        //Generate the element id for card spot
        buildSpotId: function( card_id, pos)
        {
            return 'vmg_cspot_'+card_id+'_'+pos;
        },

        // Function to add elements on the card spots that are dispayed
        addSpotsToCard: function(stock_obj, card_id) {
            //console.log('addSpotsToCard '+card_id);
            // Add 6 spots element to non visible card_spots div
            for (var ii = 0; ii < 6; ii++) {
                dojo.place( this.format_block( 'jstpl_card_spot', {
                larg_use: card_id,
                cpos_use: ii
            }), 'vmg_card_spots');
                // Get the divid of card_id 
                var div = stock_obj.getItemDivId(card_id);
                var spot_id = this.buildSpotId( card_id, ii);
                var yoff = this.pchactive/2.0 * (-0.92);
                var xoff = ii*this.pcwactive/6.3 - this.pcwactive/2.0*0.82;
                // For small cards, offset odd card spots
                if (this.pcwactive < 115.0)
                {
                    if (ii % 2 == 0) {
                        yoff = this.pchactive/2.0 * (-0.77);
                    }
                }
                this.placeOnObjectPos( spot_id, div, xoff, yoff);
                this.attachToNewParent( spot_id, div);
            }
        },

        // Generate the element id for a gem 
        buildGemId: function( player_id, iter_id)
        {
            return 'vmg_gem_'+player_id+'_'+iter_id;
        },

        // Generate element id for a discard token
        buildTokenId: function(type, iter_id)
        {
            return 'vmg_tokendiscard_'+type+'_'+iter_id;
        },

        // Here is a function to initially place gems in player's area
        setup_and_show_gem_zone: function(markerData, loc_css_id, player) {
            //console.log('setup_and_show_gem_zone '+loc_css_id+' '+player);
            var mrkrs = new ebg.zone(); // New zone object
            mrkrs.create( this, loc_css_id, this.gemsz, this.gemsz);
            mrkrs.setPattern('grid');
            for( var idx in markerData ) {
                var gmarker = markerData[idx];
                // Initially place gem in zone
                if (gmarker['card_type'] == player) {  // card_type holds the owner player_id

                    dojo.place( this.format_block( 'jstpl_gems', {
                        play_id: player,
                        type_arg: gmarker['card_type_arg']
                    }), 'vmg_gems');
                    var gem_id = this.buildGemId( player, gmarker['card_type_arg']);
                    //console.log(gem_id);
                    //console.log(dojo.position(gem_id));
                    mrkrs.placeInZone( gem_id);
                    //console.log(dojo.position(gem_id));
                    // Now see if gem is actually on card
                    if (gmarker['card_location'] !=0 ) {
                        //console.log('Gem not in zone loc: '+gmarker['card_location']);
                        this.move_gem_zone_to_card(mrkrs, gmarker);
                    }
                }
            }
            return mrkrs;

        },

        // Move gem from players zone to card
        move_gem_zone_to_card: function (zone_obj, gemData)
        {
            //console.log('Entering move_gem_zone_to_card');
            // Get gem data
            var cid = gemData['card_location'];
            var pos = gemData['card_location_arg'];
            var pid = gemData['card_type'];
            var iter = gemData['card_type_arg'];                
            var gem_id = this.buildGemId( pid, iter);
            var spot_id = this.buildSpotId( cid, pos);
            ////console.log(gem_id+' '+spot_id);
            // Need to rejigger the positioning of the gem on card
            offy = 0;
            offx = 2;
            // Remove gem from zone
            zone_obj.removeFromZone( gem_id, true, gem_id);
            // Remake the gem div on top of spot_id from java template
            dojo.place( this.format_block( 'jstpl_gems', {
                play_id: pid,
                type_arg: iter
            }), spot_id);
            // Get the divid of spot_id
            div = $(spot_id);
            // Rejigger position
            this.placeOnObjectPos( gem_id, div, offx, offy);
            this.attachToNewParent( gem_id, div);

        },

        // Move a gem from one card to another card
        move_gem_card_to_card: function (gem_id, spot_id, player_id, iter)
        {
            //console.log('Entering move_gem_card_to_card '+gem_id+' '+spot_id+' '+player_id+' '+iter);
            offx = 2;
            offy = 0;
            dojo.destroy(gem_id);
            // Remake the gem marker div with java template
            dojo.place( this.format_block( 'jstpl_gems', {
                play_id: player_id,
                type_arg: iter
            }), spot_id);    
            // Get the divid of spot_id
            div = $(spot_id);
            this.placeOnObjectPos( gem_id, div, offx, offy);
            this.attachToNewParent( gem_id, div);
        },

        // Move a gem from card back to zone
        move_gem_card_to_zone( gemData, player_id, zone_obj)
        {
            //console.log('Entering move_gem_card_to_zone');
            gemid = this.buildGemId(gemData['type'], gemData['type_arg']);
            //console.log(gemid);
            //console.log(player_id);
            myEl = dojo.byId(gemid);
            //console.log('Orig');
            //console.log(dojo.position(gemid));
            //console.log(myEl.parentNode);
            dojo.destroy(gemid);
            dojo.destroy(gemid);
            dojo.place( this.format_block( 'jstpl_gems', {
                play_id: player_id,
                type_arg: gemData['type_arg']
            }), 'vmg_gems');
            myEl = dojo.byId(gemid);
            //console.log('After recreate');
            //console.log(dojo.position(gemid));
            //console.log(myEl.parentNode.id);
            //console.log(myEl.parentNode);

            zone_obj.placeInZone(gemid);
            myEl = dojo.byId(gemid);
            //console.log('After placeInZone');
            //console.log(dojo.position(gemid));
            //console.log(myEl.parentNode);


        },

        updatePossibleGemPlaces: function( possiblePlaces )
        {
            // Remove css of possiblelegplace
            //console.log('Updating possible places');
            dojo.query( '.vmg_possiblegemplace' ).remove( 'vmg_possiblegemplace' );
            //console.log (possiblePlaces)
            for (var idx in possiblePlaces)
            {
                cid = possiblePlaces[idx]['cid'];
                pos = possiblePlaces[idx]['pos'];
                pid = possiblePlaces[idx]['pid'];
                //console.log('Gem loc: '+cid+' pos: '+pos+' player: '+pid);
                if (this.player_id == pid)
                {
                    spot_id = this.buildSpotId(cid, pos);
                    dojo.addClass( spot_id, 'vmg_possiblegemplace');
                }
            }
            this.addTooltipToClass( 'vmg_possiblegemplace', '', _('Place crystal here'));
            //this.addEventToClass( 'vmg_possiblegemplace', 'onmouseover', 'gemPlaceOnMouseOver');
            //this.addEventToClass( 'vmg_possiblegemplace', 'onmouseout', 'gemPlaceOnMouseOut');
            //this.addEventToClass( 'vmg_possiblegemplace', 'onmousedown', 'acceptGemPlacement');
            this.addEventToClass( 'vmg_possiblegemplace', 'onclick', 'clickAnimus');
        },

        addClickToGems: function( numGemData )
        {
            // The number of gem counters is in numGemData with player_id as key
            numGem = numGemData[this.player_id];
            //console.log('Add Click to Gems: '+numGem);
 
            dojo.query( '.vmg_gemreadyselect').remove( 'vmg_gemreadyselect');
            for (var ii=0; ii<numGem; ii++) {
                elid = this.buildGemId(this.player_id, ii);
                // Add class to modify
                dojo.addClass(elid, 'vmg_gemreadyselect');
            }
            this.addEventToClass( 'vmg_gemreadyselect', 'onclick', 'clickGem');
        },
        // Handle event cleanup from placeGem action
        placeGemCleanup: function ()
        {
            //console.log('gemPlaced cleanup');
            //this.disconnectClass( 'vmg_possiblegemplace', 'onmouseover');
            //this.disconnectClass( 'vmg_possiblegemplace', 'onmouseout');
            this.disconnectClass( 'vmg_possiblegemplace', 'onclick');
            this.disconnectClass( 'vmg_gemreadyselect', 'onclick');
            dojo.query( '.vmg_possiblegemplace' ).removeClass( 'vmg_possiblegemplace' );
            dojo.query( '.vmg_animusselected' ).removeClass( 'vmg_animusselected' );
            dojo.query( '.vmg_gemreadyselect' ).removeClass( 'vmg_gemreadyselect' );
            dojo.query( '.vmg_gemselected' ).removeClass( 'vmg_gemselected' );
            //dojo.query( '.vmg_cspot' ).style('backgroundColor', 'transparent');
            this.clientStateArg = [];
            this.clientStateArg2 = [];
            this.clientStateArg3 = [];
            return;
        },
        // Event Handling removes work done by addEventToClass
        disconnectClass: function(class_name, event_name) {
            //console.log('this connections1');
            //console.log(this.connections);
            var new_connections = [];
            var list = dojo.query("." + class_name);
            //console.log('Trying disconnect: '+class_name+' '+event_name);
            //console.log(list);
            for (var i = 0; i < this.connections.length; i++) {
                var conn = this.connections[i];
                var foundit = false
                for (var j = 0; j < list.length; j++) {
                    var xtmp = list[j];
                    if (conn.element == xtmp && conn.event == event_name) {
                        // Found element with event in connection list disconnect event
                        //console.log('Disconnecting '+conn.event);
                        //console.log(conn.element);
                        dojo.disconnect(conn.handle);
                        foundit = true;
                    }
                }
                if (!foundit)
                {
                    new_connections.push(conn);
                }
            }
            this.connections = new_connections;
            return;
        },

        setup_and_show_rewards : function(card_array, loc_css_id, player_id)
        {
            //console.log('setup_and_show_rewards '+loc_css_id+' '+player_id);
            var cards = new ebg.stock(); // New stock object for the objective cards
            cards.create( this, $(loc_css_id), this.userewardw, this.userewardh);
            // In the reward sprite there are 1 per row with 10 rows
            cards.image_items_per_row = 1;
            // There are 10 cards
            for (var ii = 0; ii < 10; ii++) {
                cards.addItemType( ii, ii, g_gamethemeurl + 'img/Rewards_30px.png', ii);
            }
            // Now play rewards in area if there are any
            // player_id == 0 is the main area not a player
            var use_from = $('vmg_rewardarea');
            // For the portal color rewards we need to map the color type as given in material.inc
            //   to their location in the sprite for the reward type 9.  For reward type 10 map #portals to sprite location
            var sprite_map = {9:{1:2, 2:0, 3:1, 4:3, 5:4},
                            10:{2:5, 3:6, 4:7, 5:8, 6:9}};
            var reward_awarded = {9:{1:0, 2:0, 3:0, 4:0, 5:0},
            10:{2:0, 3:0, 4:0, 5:0, 6:0}};
            // These are rewards for player
            if (player_id != 0)
            {
                for (var ii = 0; ii < card_array.length; ii++)
                {
                    if (card_array[ii].player == player_id) 
                    {
                        // Check type of bonus reward card
                        var bonus_type = card_array[ii].bonus_type_id;
                        if (bonus_type == 9 || bonus_type == 10)
                        {
                            var card_id  = card_array[ii].val1;
                            var loc = sprite_map[bonus_type][card_id];
                            cards.addToStockWithId(loc, loc, use_from);
                            // Get div to add tooltip
                            curDiv = cards.getItemDivId(loc);
                            document.getElementById(curDiv).style.backgroundSize = '100%';
                            this.addTooltip( curDiv, this.rewardToolTipData[bonus_type][card_id], '' );

                        }
                    }
                }
            } else {
                // If reward card is not assigned to player show it in rewardarea
                // show all reward cards but set opacity low
                // Note only rewards actually awarded to players are in the card_array  
                for (var ii = 0; ii < card_array.length; ii++)
                {
                    var bonus_type = card_array[ii].bonus_type_id;
                    if (bonus_type == 9 || bonus_type == 10)
                    {
                        var card_id = card_array[ii].val1;
                        reward_awarded[bonus_type][card_id] = 1;
                    }
                }
                for (var ii = 9; ii<11; ii++) {
                    var curmap = sprite_map[ii];
                    var curawarded = reward_awarded[ii];
                    for (const jj in curmap)
                    {
                        var loc = curmap[jj];
                        var awarded = curawarded[jj];
                        cards.addToStockWithId(loc, loc, use_from);
                        // Get div to add tooltip
                        curDiv = cards.getItemDivId(loc);
                        this.addTooltip( curDiv, this.rewardToolTipData[ii][jj], '' );
                        document.getElementById(curDiv).style.backgroundSize = '100%';                        
                        if (awarded == 1)
                        {
                            // Make opacity low
                            curDiv = cards.getItemDivId(loc);
                            dojo.setStyle(curDiv, 'opacity', '0.3');
                        }
                    }
                }
            }
            return cards;
        },
        // Redisplays cards with overlap and ones on top all on top
        overlapCards: function(cards)
        {
            cards.setOverlap(65,25);
            //console.log('Cards Overlapped');
            var card_list = cards.getAllItems();
            for (var ii in card_list)
            {
                curDiv = cards.getItemDivId(card_list[ii].id);
                if (ii%2 == 0) {
                    //console.log('ii: '+ii+' z=1 '+curDiv);
                    dojo.addClass($(curDiv), "vmg_downz");
                    //dojo.style($(curDiv), "zIndex", "1");
                    //document.getElementById(curDiv).style.zIndex = "2";
                    //console.log(document.getElementById(curDiv).style.zIndex);
                } else {
                    //console.log('ii: '+ii+' z=2 '+curDiv);
                    dojo.addClass($(curDiv), "vmg_upz");
                    //dojo.style($(curDiv), "zIndex", "2");
                    //document.getElementById(curDiv).style.zIndex = "3";
                    //console.log(document.getElementById(curDiv).style.zIndex);
                }
            }
        },

        //Generate the element id for tooltip holder bar
        buildTTBarId: function( card_id, pos)
        {
            return 'vmg_ttbar_'+card_id+'_'+pos;
        },

        addCardToolTip: function(cards, card_id, delay, card_type)
        {
            //console.log('Adding Card Tool Tip '+card_id);
            // Get the div of current card
            curDiv = cards.getItemDivId(card_id);
            // Add tool tip holder bar at bottom of card
            dojo.place( this.format_block( 'jstpl_tooltip_bar', {
                larg_use: card_id,
                cpos_use: 0,
                cardtype: card_type
            }), 'vmg_tooltip_holderbars');
            var bar_id = this.buildTTBarId( card_id, 0);
            switch( card_type )
            {
                case 'stock':
                    var usew = this.pcw;
                    var useh = this.pch*0.7;
                    var useyoff = this.pch*0.1;
                    break;
                case 'active':
                    var usew = this.pcwactive;
                    var useh = this.pchactive*0.7;
                    var useyoff = this.pchactive*0.1;
                    break;
                case 'done':
                    var usew = this.pcwdone;
                    var useh = this.pchdone*0.7;
                    var useyoff = this.pchdone*0.1;
                    break;
            }
            dojo.setStyle(bar_id, 'width', usew+'px');
            dojo.setStyle(bar_id, 'height', useh+'px');
 
            this.placeOnObjectPos( bar_id, curDiv, 0, useyoff);
            this.attachToNewParent( bar_id, curDiv);
           
            // Get the background position information 
            backPos = dojo.style(curDiv, 'backgroundPosition');
            // Add tooltip info
            this.addTooltipHtml(bar_id, this.format_block('jstpl_card_tooltip', {
                backpos: backPos,
                type: _('Type:'),
                type_str: this.toolTipData[card_id].type_str,
                point: _('Point:'),
                point_str: this.toolTipData[card_id].point_str,
                effect: _('Portal Effect:'),
                effect_str: this.toolTipData[card_id].effect_str
            }), delay);

        },

        // handle screen change callback
        /*
        screenChangeCallback: function(mutationsList, observer)
        {
            //console.log('Mutations:', mutationsList);
            //console.log('Observer: ', observer);
            for( var idx in mutationsList)
            {
                if (mutationsList[idx].attributeName === 'class')
                {
                    var classNames = mutationsList[idx].target.classList;
                    if (classNames.contains("desktop_version"))
                    {
                        document.documentElement.style.setProperty('--totwidth', 'calc(98vw - 240px)');
                        console.log('desktop');
                    }
                    if (classNames.contains("mobile_version"))
                    {
                        document.documentElement.style.setProperty('--totwidth', '99vw');
                        console.log('mobile');
                    }
                    console.log('resize');
                    console.log(dojo.position('vmg_centering_container'));
                }
            }
        },
        */

        ///////////////////////////////////////////////////
        //// Player's action
        
        /*
        
            Here, you are defining methods to handle player's action (ex: results of mouse click on 
            game objects).
            
            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server
        
        */
        // Define what happends when the choosing initial 3 portal cards done button is selected
        select_chooseInitCards_done_button: function (evt) {
            dojo.stopEvent( evt );
            //console.log( 'select_chooseInitCards_done_button' );
            if (this.checkAction( 'chooseInitCards' )) {
                // Get unselected cards
                var cards = this.currentPlayerActiveCards.getUnselectedItems();
                if (cards.length == 3) { // There are 6 cards initially make sure there are 3 unselected 
                    // Build unselected card ids as a string list
                    var send_ids = [];
                    for( var idx in cards ) {
                        send_ids.push(cards[idx].id);
                    }
                    this.ajaxcall( "/viamagica/viamagica/chooseInitCards.html", {
                                lock: true,
                                unselected_cards: send_ids.join()}, 
                                 this, function( result ) {} );
                } else {
                    this.showMessage(_('Select 3 Cards'), 'info' );
                    return; // Did not pass having 3 cards selected test
                }
            } else {
                return; // Did not pass action test
            }
        },

        gemPlaceOnMouseOver: function ( evt )
        {
            dojo.stopEvent( evt );
            dojo.style(evt.currentTarget, 'backgroundColor', 'orange');
        },
        gemPlaceOnMouseOut: function ( evt )
        {
            dojo.stopEvent( evt );
            dojo.style(evt.currentTarget, 'backgroundColor', 'transparent');
        },
        // Highlight Gem marker and save the marker selected into global variabl
        clickGem: function ( evt )
        {
            //console.log('Click Gem');
            dojo.stopEvent( evt );
            dojo.query( '.vmg_gemselected').removeClass( 'vmg_gemselected' );
            dojo.addClass(evt.currentTarget, 'vmg_gemselected');
            this.clientStateArg = dojo.attr(evt.currentTarget, 'id');
            //console.log('Gem Selected id: '+this.clientStateArg);
            this.acceptGemPlacement( evt );
        },

        // Highlight animus and save the spot selected into global variabl
        clickAnimus: function ( evt )
        {
            dojo.stopEvent( evt );
            //console.log('Click Animus');
            dojo.query( '.vmg_animusselected').removeClass( 'vmg_animusselected' );
            dojo.addClass(evt.currentTarget, 'vmg_animusselected');
            this.clientStateArg2 = dojo.attr(evt.currentTarget, 'id');
            //console.log('Animus Selected id: '+this.clientStateArg2);
            this.acceptGemPlacement( evt );
        },
        // Finalize the portal card location selected for gem placement and send it to server
        acceptGemPlacement: function ( evt )
        {
            dojo.stopEvent( evt );
            //console.log('acceptGemPlacement');
            var normal_turn = 1;
            if (this.checkPossibleActions( 'exPlayGem'))
            {
                //console.log('This is exPlayGem');
                normal_turn = 0;
            }
            if (this.checkPossibleActions( 'placeGem' )  || this.checkPossibleActions( 'exPlayGem' ))
            {
                // Make sure that a gem was selected before calling server for this obj card spot
                if (this.clientStateArg !== 'undefined' && this.clientStateArg.length > 0 && this.clientStateArg2 !== 'undefined' && this.clientStateArg2.length > 0)
                {
                    // Build the gem marker and spot selected string for ajax send
                    //spot_id = dojo.attr(evt.currentTarget, 'id');
                    spot_id = this.clientStateArg2;
                    gem_id = this.clientStateArg;
                    sameCardOK = this.clientStateArg3;
                    // Catch situation where the spot_id and gem_id are on same card
                    // This indicates that the selection could be mistakenly valid.  In this situation
                    // add a confirmation button.
                    //console.log(spot_id+' '+gem_id);
                    myEl = dojo.byId(gem_id);
                    //console.log(myEl.parentNode.id);
                    spot_id_splt = spot_id.split('_');
                    gem_loc_id_splt = myEl.parentNode.id.split('_');
                    //console.log(gem_loc_id_splt);
                    //console.log(gem_loc_id_splt.length)
                    // If gem is in gemzone then gem_id_splt has length 2 'vmg_mygemzone'
                    // we only care if gem parentnode is a card spot with id 'vmg_cspot_##_#
                    if (gem_loc_id_splt.length > 2)
                    {
                        // Get CID number of card
                        spot_cid = spot_id_splt[2];
                        gem_cid = gem_loc_id_splt[2];
                        if (spot_cid == gem_cid && sameCardOK.length == 0)
                        {
                            //console.log('Inner Card Placement Ask Confirmation!');
                            this.gamedatas.gamestate.descriptionmyturn = _('Confirm that you want to place crystal on a spot of the same portal card');
                            this.gamedatas.gamestate.description = _('Confirm that you want to place crystal on a spot of the same portal card');
                            this.updatePageTitle();
                            dojo.destroy('vmg_placegem_confirm_button');
                            dojo.destroy('vmg_placegem_confirm_button');
                            // Remake action button 
                            dojo.place(this.format_block("jstpl_action_button", {
                                    id: 'vmg_placegem_confirm_button',
                                    label: _('Confirm'),
                                    addclass: 'bgabutton bgabutton_blue'
                            }), 'page-title');
                            dojo.connect($('vmg_placegem_confirm_button'), "onclick", this, 'select_placegem_confirm_button');

                            dojo.destroy('vmg_placegem_pass_button');
                            dojo.destroy('vmg_placegem_pass_button');
                            // Remake action button 
                            dojo.place(this.format_block("jstpl_action_button", {
                                    id: 'vmg_placegem_pass_button',
                                    label: _('Pass'),
                                    addclass: 'bgabutton bgabutton_blue'
                            }), 'page-title');
                            dojo.connect($('vmg_placegem_pass_button'), "onclick", this, 'select_placegem_pass_button');
                            dojo.destroy('vmg_placegem_reset_button');
                            dojo.destroy('vmg_placegem_reset_button');
                            // Remake action button 
                            dojo.place(this.format_block("jstpl_action_button", {
                                    id: 'vmg_placegem_reset_button',
                                    label: _('Reset Selection'),
                                    addclass: 'bgabutton bgabutton_blue'
                            }), 'page-title');
                            dojo.connect($('vmg_placegem_reset_button'), "onclick", this, 'select_placegem_reset_button');
                            return;
                        } else {
                            //console.log('Ajax call for Gem spot');
                            this.ajaxcall( "/viamagica/viamagica/placeGem.html",
                            {
                                lock: true,
                                spot_id: spot_id,
                                gem_id: gem_id,
                                normal_turn: normal_turn
                            }, this, function( result ) {} );
                        }
                    } else {
                        //console.log('Ajax call for Gem spot');
                        this.ajaxcall( "/viamagica/viamagica/placeGem.html",
                        {
                            lock: true,
                            spot_id: spot_id,
                            gem_id: gem_id,
                            normal_turn: normal_turn
                        }, this, function( result ) {} );                        
                    }
                } else {
                    //this.showMessage(_('Select a crystal to place before spot on portal card'), 'info');
                    return; // Did not pass making sure a gem and animus spot was selected
                }
            }
        },

        // Player passes on placing gem call special no_op ajax call
        select_placegem_pass_button: function( evt )
        {
            //console.log('Entering select_placegem_pass_button')
            var normal_turn = 1;
            if (this.checkPossibleActions( 'exPlayGem'))
            {
                normal_turn = 0;
            }
 
            if (this.checkPossibleActions( 'placeGem' ) || this.checkPossibleActions( 'exPlayGem' ))
            {
                dojo.stopEvent( evt );
                this.ajaxcall( "/viamagica/viamagica/placeGem.html",
                    {
                        lock: true,
                        spot_id: 'vmg_NOOP_0_0',
                        gem_id: 'vmg_NOOP_0_0',
                        normal_turn: normal_turn
                    }, this, function( result ) {} );
            }
            dojo.destroy('vmg_placegem_confirm_button');
            dojo.destroy('vmg_placegem_pass_button');
            dojo.destroy('vmg_placegem_reset_button');
        },
        // Player confirms on rearranging gems on same card.  Likely a false move 
        select_placegem_confirm_button: function( evt )
        {
            //console.log('Entering select_placegem_confirm_button')
            dojo.stopEvent( evt );
            this.clientStateArg3.push(1);
            this.acceptGemPlacement( evt );
            dojo.destroy('vmg_placegem_confirm_button');
            dojo.destroy('vmg_placegem_pass_button');
            dojo.destroy('vmg_placegem_reset_button');
        },

        // reset the current gem or animus selections
        select_placegem_reset_button: function( evt )
        {
            //console.log('Entering select_placegem_reset_button')
            this.clientStateArg = [];
            this.clientStateArg2 = [];
            this.clientStateArg3 = [];
            dojo.query( '.vmg_animusselected').removeClass( 'vmg_animusselected' );
            dojo.query( '.vmg_gemselected').removeClass( 'vmg_gemselected');
            dojo.destroy('vmg_placegem_confirm_button');
        },

        // The next portal card is seleced
        select_newPortCard_done_button: function( evt )
        {
            dojo.stopEvent( evt );
            //console.log( 'select_newPortCard_done_buton' );
            if (this.checkAction( 'completeCard'))
            {
                // Get Selected card
                var cards = this.portCards.getSelectedItems();
                //console.log('Selecting New Card');
                //console.log(cards);
                if (cards.length == 1)  // Must have selected a card
                {

                    // Also check to make sure player does not own card already
                    if (!(this.avoid_card_ids.includes(parseInt(cards[0].id, 10))))
                    {
                        //console.log('ajax call in select_newPortCard_done_button');
                        // Do ajax call with card id of card selected
                        this.ajaxcall( "/viamagica/viamagica/completeCard.html",
                        {
                            lock: true,
                            card_id: parseInt(cards[0].id, 10)
                        }, this, function( result ) {} );
                    } else {
                        this.showMessage(_('You cannot choose a duplicate card.  Choose a different card.'), 'info');
                    }
                } else {
                    this.showMessage(_('Select 1 Card'), 'info' );
                    return; // Did not pass having 1 card selected test                   
                }
            }
        },
        select_resolvePortalCount_yes_button: function ( evt )
        {
            dojo.stopEvent( evt );
            //console.log( 'select_resolvePortalCount_yes_button');
            if (this.checkAction ('resolvePortalCount'))
            {
                this.ajaxcall( "/viamagica/viamagica/resolvePortalCount.html",
                {
                    lock: true,
                    accept: 1
                }, this, function( result ) {} );
            }
        },
        select_resolvePortalCount_no_button: function ( evt )
        {
            dojo.stopEvent( evt );
            //console.log( 'select_resolvePortalCount_no_button');
            if (this.checkAction ('resolvePortalCount'))
            {
                this.ajaxcall( "/viamagica/viamagica/resolvePortalCount.html",
                {
                    lock: true,
                    accept: 0
                }, this, function( result ) {} );
            }
        },

        // The next portal card is seleced
        select_newCardBonus_done_button: function( evt )
        {
            dojo.stopEvent( evt );
            //console.log( 'select_newCardBonus_done_buton' );
            if (this.checkAction( 'chooseNewCardBonus'))
            {
                // Get Selected card
                var cards = this.portCards.getSelectedItems();
                //console.log('Selecting New Card');
                //console.log(cards);
                if (cards.length == 1)  // Must have selected a card
                {

                    // Also check to make sure player does not own card already
                    if (!(this.avoid_card_ids.includes(parseInt(cards[0].id, 10))))
                    {
                        //console.log('ajax call in select_newCardBonus_done_button');
                        // Do ajax call with card id of card selected
                        this.ajaxcall( "/viamagica/viamagica/newCardBonus.html",
                        {
                            lock: true,
                            card_id: parseInt(cards[0].id, 10)
                        }, this, function( result ) {} );
                    } else {
                        this.showMessage(_('You cannot choose a duplicate card.  Choose a different card.'), 'info');
                    }
                } else {
                    this.showMessage(_('Select 1 Card'), 'info' );
                    return; // Did not pass having 1 card selected test                   
                }
            }
        },
        // A bonus portal is opened/completed
        select_completePortalBonus_done_button: function( evt )
        {
            dojo.stopEvent( evt );
            //console.log( 'select_completePortalBonus_done_buton' );
            if (this.checkAction( 'completePortalBonus'))
            {
                // Get Selected card
                var cards = this.currentPlayerActiveCards.getSelectedItems();
                //console.log('Selecting New Card');
                //console.log(cards);
                if (cards.length == 1)  // Must have selected a card
                {
                    // Do ajax call with card id of card selected
                    this.ajaxcall( "/viamagica/viamagica/completePortalBonus.html",
                    {
                            lock: true,
                            card_id: parseInt(cards[0].id, 10)
                    }, this, function( result ) {} );
                } else {
                    this.showMessage(_('Select 1 Card'), 'info' );
                    return; // Did not pass having 1 card selected test                   
                }
            }
        },
         
         
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your viamagica.game.php file.
        
        */
        setupNotifications: function()
        {
            //console.log( 'notifications subscriptions setup' );
            
            dojo.subscribe( 'initCardsChosen', this, "notif_initCardsChosen");
            dojo.subscribe( 'allPlayersInitCardsChosen', this, "notif_allPlayersInitCardsChosen");
            dojo.subscribe( 'newCatcher', this, "notif_newCatcher");
            dojo.subscribe( 'tokenDrawn', this, "notif_tokenDrawn");
            this.notifqueue.setSynchronous( 'tokenDrawn', 1000 );
            dojo.subscribe( 'gemPlaced', this, 'notif_gemPlaced');
            dojo.subscribe( 'allPlayersPlacedGems', this, 'notif_allPlayersPlacedGems');
            dojo.subscribe( 'completedCard', this, "notif_completedCard");
            dojo.subscribe( "noCompletedCard", this, "notif_noCompletedCard");
            dojo.subscribe( "newCardChosen", this, "notif_newCardChosen");
            dojo.subscribe( 'scoreAdjust', this, 'notif_scoreAdjust');
            this.notifqueue.setSynchronous( 'scoreAdjust', 2000 );
            dojo.subscribe( 'eogBonusScoring', this, 'notif_eogBonusScoring');
            this.notifqueue.setSynchronous( 'eogBonusScoring', 2000 );
            dojo.subscribe( 'wildAnimus', this, 'notif_wildAnimus');
            dojo.subscribe( 'addGems', this, 'notif_addGems');
            dojo.subscribe( 'noPlaceToPlay', this, 'notif_noPlaceToPlay');
            this.notifqueue.setSynchronous( 'noPlaceToPlay', 1000 );
            dojo.subscribe( 'noGemsForRearrange', this, 'notif_noGemsForRearrange');
            dojo.subscribe( 'announceRearrangeGems', this, 'notif_announceRearrangeGems');
            dojo.subscribe( 'rewardPlayerType9', this, 'notif_rewardPlayerType9');
            this.notifqueue.setSynchronous( 'rewardPlayerType9', 1000);
            dojo.subscribe( 'resolvePortalCount', this, 'notif_resolvePortalCount');
            this.notifqueue.setSynchronous( 'resolvePortalCount', 1000);
            dojo.subscribe( 'newCardBonusChosen', this, 'notif_newCardBonusChosen');
            dojo.subscribe( 'removeGems', this, 'notif_removeGems');
            dojo.subscribe( 'completePortalBonusChosen', this, 'notif_completePortalBonusChosen');
        },  
        
        // TODO: from this point and below, you can write your game notifications handling methods
        // Move the nonchosen initial cards to the discard
        notif_initCardsChosen: function (notif) {
            //console.log('notif_initCardsChosen');
            discards = notif.args.card_ids;
            if (this.player_id == notif.args.player_id) {   // handle results from current player
                for (var card_id in discards) {
                    this.currentPlayerActiveCards.removeFromStockById(discards[card_id], $('vmg_tokenhelpimage'));
                }

                // Now resize the cards to normal size
                var allCards = this.currentPlayerActiveCards.getAllItems();
                // repackage allCards into format expected for setup_and_show_portal_cards
                var wantCards = [];
                for (var idx in allCards)
                {
                    wantCards.push({card_location_arg:this.player_id, card_id:allCards[idx].id});
                }
                //console.log('All Cards');
                //console.log(allCards);
                //console.log(wantCards);
                this.currentPlayerActiveCards.removeAll();
                this.currentPlayerActiveCards = this.setup_and_show_portal_cards(wantCards, 
                    'vmg_myportalstock', this.player_id, true, this.pcwactive, this.pchactive, 'active') ;
                this.currentPlayerActiveCards.setSelectionMode(0); // Turn off cards being selectable
                this.currentPlayerActiveCards.unselectAll();
    
            }

        },

        // This is called when all players have finished so we can show other players' selections
        notif_allPlayersInitCardsChosen: function (notif) 
        {
            //console.log('notif_allPlayersInitCardsChosen');
            discards = notif.args.card_ids;
            pids = notif.args.player_ids;
            // iterate through cards
            for (var ii in discards)
            {
                // only move cards for other players
                if (this.player_id != pids[ii])
                {
                    this.otherPlayerActiveCards[this.otherIdxFromPlayerIds[pids[ii]]].removeFromStockById(discards[ii], $('vmg_tokenhelpimage'));
                }
            }
            // All cards have been discarded now go through players to  reset the cards back to normal size
            for( var player_id in this.gamedatas.players )
            {
                if (this.player_id != player_id){
                    var idx = this.otherIdxFromPlayerIds[player_id];
                    //console.log('Other Player ', player_id, ' idx: ', idx);
                    var allCards = this.otherPlayerActiveCards[idx].getAllItems();
                    // repackage allCards into format expected for setup_and_show_portal_cards
                    var wantCards = [];
                    for (var ii in allCards)
                    {
                        wantCards.push({card_location_arg:player_id, card_id:allCards[ii].id});
                    }    
                    this.otherPlayerActiveCards[idx].removeAll();
                    this.otherPlayerActiveCards[idx] = this.setup_and_show_portal_cards(wantCards, 
                        'vmg_playerportalstock_'+player_id, player_id, true, this.pcwactive, this.pchactive, 'active') ;
                    this.otherPlayerActiveCards[idx].setSelectionMode(0); // Turn off cards being selectable
                    this.otherPlayerActiveCards[idx].unselectAll();
                }
            }
        },
        // New animus catcher is announced
        notif_newCatcher: function (notif)
        {
            //console.log('New Catcher Announced');
            this.catcher_name = notif.args.player_name;
            this.catcher_id = notif.args.player_id;
            dojo.destroy('vmg_tokencatcher');
            var str_trans = _("Catcher:");
            dojo.place( this.format_block( 'jstpl_catcher_name', {
                player_name: this.catcher_name,
                catcher_translate: str_trans,
                player_color: this.player_colors[this.catcher_id]
            }), 'vmg_portalstockrow');

        },
         //Perfom client side actions for a new token being drawn
        notif_tokenDrawn: function ( notif )
        {
            //console.log('notif_tokenDrawn: ' + notif.args.card_type_arg + ' ' + notif.args.card_id);
            //console.log('token Drawn');
            //console.log(notif);
            // Add token to #tokenDisplay via tokenDisplay stock object
            if ((notif.args.old_card_type_arg > 0) & (this.showTokenDiscard == 1)) {
                var token_type = notif.args.old_card_type_arg;
                var token_id = notif.args.card_id;
                var token_backpos = (parseInt(token_type, 10)-1)*(-100);
                //console.log('backpos');
                //console.log(token_backpos);
                // Generate template object for token
                dojo.place( this.format_block( 'jstpl_token_discard', {
                    type: token_type,
                    id: token_id,
                    backpos: token_backpos,
                    width: this.tokenszdiscard*this.tokefraction,
                    height: this.tokenszdiscard*this.tokefraction
                }), 'vmg_token_holder');
                var elId = this.buildTokenId( token_type, token_id);
                //console.log(elId);
                this.tokenDiscard.placeInZone( elId);
            }
            this.tokenDisplay.removeAll();
            this.tokenDisplay.addToStockWithId( notif.args.card_type_arg, notif.args.card_id);
            var div = this.tokenDisplay.getItemDivId(notif.args.card_id);
            document.getElementById(div).style.backgroundSize = '700%';

            dojo.addClass( div, 'vmg_tokendrawnanimation');
            dojo.addClass( div, 'vmg_tokenround')
            // Update token counter
            this.tokenCounter.setValue(notif.args.num_deck);
            // If last token was wild card remove all tokens from discard
            if ((notif.args.old_card_type_arg == 7) & (this.showTokenDiscard == 1))
            {
                this.tokenDiscard.removeAll();
            }
        },
        // Move the chosen gem to the chosen spot on portal card
        notif_gemPlaced: function (notif) 
        {
            //console.log('notif_gemPlaced');
            spot_id = notif.args.spot_id;
            gem_id = notif.args.gem_id;
            old_cid = notif.args.old_cid;
            cid = notif.args.card_id;
            pos = notif.args.pos_id;
            gemn = notif.args.type_arg;
            this.gamedatas.gamestate.description = _('Crystal placement Recorded. While others are choosing, you can change your placement.');
            dojo.destroy('vmg_placegem_pass_button');
            // Remake action button 
            dojo.place(this.format_block("jstpl_action_button", {
                    id: 'vmg_placegem_pass_button',
                    label: _('Pass'),
                    addclass: 'bgabutton bgabutton_blue'
            }), 'page-title');
            dojo.connect($('vmg_placegem_pass_button'), "onclick", this, 'select_placegem_pass_button');
            dojo.destroy('vmg_placegem_reset_button');
            // Remake action button 
            dojo.place(this.format_block("jstpl_action_button", {
                    id: 'vmg_placegem_reset_button',
                    label: _('Reset Selection'),
                    addclass: 'bgabutton bgabutton_blue'
            }), 'page-title');
            dojo.connect($('vmg_placegem_reset_button'), "onclick", this, 'select_placegem_reset_button');
            if (gem_id == 'vmg_NOOP_0_0')
            {
                dojo.query( '.vmg_animusselected').removeClass( 'vmg_animusselected' );
                dojo.query( '.vmg_gemselected').removeClass( 'vmg_gemselected');
                this.clientStateArg = [];
                this.clientStateArg2 = [];
                this.clientStateArg3 = [];
            }            
            // Something weird happens with the generalactions id
            // It shrinks to zero size?
            //div = $('generalactions');
            //dojo.setStyle(div, 'width', '100px');
            //console.log(div);
            //console.log('placegem_pass_button');
            //this.placeOnObjectPos('placegem_pass_button', div, 0, 0);
            //this.attachToNewParent( 'placegem_pass_button', div);
            //console.log('After');
            //myEl = dojo.byId('placegem_pass_button');
            //console.log(dojo.position('placegem_pass_button'));
            //console.log(myEl.parentNode);

            // For now try not updating placement until everyone is finished
            // Will this allow a redo?
            // if (this.player_id == notif.args.player_id) {   // handle results from current player
            //     if (!(spot_id == 'vmg_NOOP_0_0'))  // Ensure player is actually making a gem placement
            //     {
            //         if (old_cid == 0)  // gem was in players zone to start with
            //         {
            //             var gemData = [];
            //             gemData['card_location'] = cid;
            //             gemData['card_location_arg'] = pos;
            //             gemData['card_type'] = this.player_id;
            //             gemData['card_type_arg'] = gemn;
            //             //console.log('Register z2c pid: '+this.player_id+' iter: '+gemn+' cid: '+cid+' pos: '+pos);                
            //             this.move_gem_zone_to_card(this.currentPlayerGemZone, gemData)                    
            //         } else {
            //             //console.log('Register c2c pid: '+this.player_id+' gid: '+gem_id+' sid: '+spot_id+' itr: '+gemn);
            //             this.move_gem_card_to_card(gem_id, spot_id, this.player_id, gemn)
            //         }
            //     }
            //     this.placeGemCleanup();
            // }                
        },

        // All players have completed choosing gems to place  now show results for players
        notif_allPlayersPlacedGems: function (notif)
        {
            //console.log('Entering notif_allPlayersPlacedGems');
            new_cid_ids = notif.args.card_ids;
            old_cid_ids = notif.args.old_card_ids;
            player_ids = notif.args.player_ids;
            gemns = notif.args.gemns;
            sptns = notif.args.sptns;
            //console.log(notif);
            for (var ii in new_cid_ids)
            {
//                if (this.player_id != player_ids[ii] && gemns[ii]!= -1) // Only move gems if they are other players
                if (gemns[ii]!= -1)
                {
                    if (old_cid_ids[ii] == 0) // Gem starts in a players zone
                    {
                        if (this.player_id == player_ids[ii])
                        {
                            zone_obj = this.currentPlayerGemZone;
                        } else {
                            idx = this.otherIdxFromPlayerIds[player_ids[ii]];
                            zone_obj = this.otherPlayerGemZone[idx];
                        }
                        var gemData = [];
                        gemData['card_location'] = new_cid_ids[ii];
                        gemData['card_location_arg'] = sptns[ii];
                        gemData['card_type'] = player_ids[ii];
                        gemData['card_type_arg'] = gemns[ii];
                        this.move_gem_zone_to_card(zone_obj, gemData);
                    } else {
                        gem_id = this.buildGemId( player_ids[ii], gemns[ii]);
                        spot_id = this.buildSpotId( new_cid_ids[ii], sptns[ii]);
                        this.move_gem_card_to_card(gem_id, spot_id, player_ids[ii], gemns[ii]);
                    }
                }
            }
            dojo.destroy('vmg_placegem_pass_button');
            dojo.destroy('vmg_placegem_reset_button');
            dojo.destroy('vmg_placegem_confirm_button');
            this.placeGemCleanup();
        },
        // Handle check that an objective card was completed
        notif_completedCard: function (notif)
        {
            //console.log('Card completed');
            var pid = notif.args.player_id;
            var elId = 'vmg_player_board_counters_'+pid;
            // Get the node id for portal icon in player board for this player
            // It is of class vmg_portal_icon
            var portId = document.getElementById(elId).querySelectorAll(".vmg_portal_icon");
            dojo.addClass(portId[0], 'vmg_spin_portal');
            // Add the Incantatum text
            dojo.setStyle('vmg_incantatum_'+pid, 'display', 'block');
            dojo.addClass('vmg_incantatum_'+pid, 'vmg_text-blur-out');
        },        
        // No card completed this round
        notif_noCompletedCard: function(notif)
        {
            //console.log('No Cards Completed');
        },

        // Handle new card being chosen
        notif_newCardChosen: function(notif)
        {
            //console.log( 'newCardChosen' );
            actor_id = notif.args.player_id;
            //old_card_id = parseInt(notif.args.old_card_id, 10);
            card_id = parseInt(notif.args.new_card_id, 10);
            drawn_id = parseInt(notif.args.new_card_for_portalstock_id, 10);
            //card_color = notif.args.card_color;
            //gemToSendHome = notif.args.gem_home_array;
            //console.log(notif.args);
            // Move players completed card to completed area and new card into area
            //use_to = $('overall_player_board_'+actor_id);
            if (this.player_id == actor_id)
            {
                //console.log('newCardChosen is this player '+old_card_id+' '+card_id);
                div_new = this.portCards.getItemDivId(card_id);
                this.currentPlayerActiveCards.addToStockWithId(card_id, card_id, div_new);
                var curDiv = this.currentPlayerActiveCards.getItemDivId(card_id);
                document.getElementById(curDiv).style.backgroundSize = '600%';
                this.portCards.removeFromStockById(card_id);
                // Add tooltip
                this.addCardToolTip(this.currentPlayerActiveCards, card_id, 500, 'active');
                this.addSpotsToCard(this.currentPlayerActiveCards, card_id);
                

            } else {
                var idx = this.otherIdxFromPlayerIds[actor_id];
                //console.log('newCardChosen is other player');
                div_new = this.portCards.getItemDivId(card_id);
                this.otherPlayerActiveCards[idx].addToStockWithId(card_id, card_id, div_new);
                var curDiv = this.otherPlayerActiveCards[idx].getItemDivId(card_id);
                document.getElementById(curDiv).style.backgroundSize = '600%';
                this.portCards.removeFromStockById(card_id);
                this.addCardToolTip(this.otherPlayerActiveCards[idx], card_id, 500, 'active');
                this.addSpotsToCard(this.otherPlayerActiveCards[idx], card_id);

            }
            // Add drawn card to the portal stock area 
            this.portCards.addToStockWithId(drawn_id, drawn_id, $('vmg_tokenarea'));
            var curDiv = this.portCards.getItemDivId(drawn_id);
            document.getElementById(curDiv).style.backgroundSize = '600%';
        // Add tooltip
            this.addCardToolTip(this.portCards, drawn_id, 200, 'stock');
            // Turn off portal icon ping
            var elId = 'vmg_player_board_counters_'+actor_id;
            // Get the node id for portal icon in player board for this player
            // It is of class vmg_portal_icon
            var portId = document.getElementById(elId).querySelectorAll(".vmg_portal_icon");
            dojo.removeClass(portId[0], 'vmg_spin_portal');
            dojo.removeClass('vmg_incantatum_'+actor_id, 'vmg_text-blur-out');
            dojo.setStyle('vmg_incantatum_'+actor_id, 'display', 'none');
            //this.addSpotsToCard(this.portCards, drawn_id);
        },
        notif_scoreAdjust: function(notif)
        {
            //console.log('Scoring Completed Card');
            //console.log(notif.args);
            this.scoreCtrl[notif.args.player_id].incValue(notif.args.total_vp);
            //console.log('I finished score Ctrl');
        },
        notif_eogBonusScoring: function(notif)
        {
            //console.log('End Of Game Bonus scoring');
            //console.log(notif.args);
            this.scoreCtrl[notif.args.player_id].incValue(notif.args.portal_score + notif.args.animus_score);
            // No longer overlapping cards.  The player done area expands during game instead
            //this.overlapCards(this.currentPlayerDoneCards);
            //for (var cards in this.otherPlayerDoneCards) {
            //    this.overlapCards(this.otherPlayerDoneCards[cards]);
            //}
            
        },
        notif_wildAnimus: function(notif)
        {
            //console.log('Wild animus portal bonus awarded');
            //console.log(notif.args);
        },
        notif_addGems: function(notif)
        {
            //console.log('Entering notif_addGems');
            //console.log(notif.args);
            nGems = notif.args.gem_cnt;
            nPrevGems = notif.args.prev_gemn;
            player_id = notif.args.player_id;
            // Get players gemzone
            if (this.player_id == player_id) {
                zone_obj = this.currentPlayerGemZone;
            } else {
                zone_obj = this.otherPlayerGemZone[this.otherIdxFromPlayerIds[player_id]];
            }
            for (var ii=nPrevGems; ii<nPrevGems+nGems; ii++) // Gem count is zero-based
            {
                //console.log('ii: '+ii+' pid: '+player_id);
                dojo.place( this.format_block( 'jstpl_gems', {
                    play_id: player_id,
                    type_arg: ii
                }), 'vmg_gems');
                var gem_id = this.buildGemId( player_id, ii);
                //console.log(gem_id);
                //console.log(dojo.position(gem_id));
                zone_obj.placeInZone( gem_id );
                //console.log('Done adding Gem');
            }
        },
        notif_noPlaceToPlay: function(notif)
        {
            //console.log('Entering noPlaceToPlay');
        },
        notif_noGemsForRearrange: function(notif)
        {
            //console.log('Entering noGemsForRearrange');
        },
        notif_announceRearrangeGems: function(notif)
        {
            //console.log('Entering announceRearrangeGems');
            //console.log(notif.args);
            card_id_list = notif.args.card_id_list;
            card_type_args = notif.args.card_type_args;
            pid = notif.args.player_id;
            gemToSendHome = [];
            for (var jj=0; jj<card_id_list.length; jj++){
                var cur_gem_data = {type: pid, type_arg: card_type_args[jj]};
                gemToSendHome.push(cur_gem_data);
            }
            if (this.player_id == pid)
            {
                //console.log('Rearrange Gems is this player');
                // move gem markers
                for (var jj=0; jj<gemToSendHome.length; jj++) 
                {
                    this.move_gem_card_to_zone(gemToSendHome[jj], this.player_id, this.currentPlayerGemZone);
                }
            } else {
                var idx = this.otherIdxFromPlayerIds[pid];
                //console.log('Rearrange Gems is other player');
                // now move gem markers
                for (var jj=0; jj<gemToSendHome.length; jj++) 
                {
                    this.move_gem_card_to_zone(gemToSendHome[jj], pid, this.otherPlayerGemZone[idx]);
                }
            }
        },
        notif_rewardPlayerType9: function(notif)
        {
            //console.log('3 of a kind portal rewarded');
            //console.log(notif.args);
            this.scoreCtrl[notif.args.player_id].incValue(notif.args.add_vp);
            actor_id = notif.args.player_id;
            loc = notif.args.type;
            var sprite_map = {1:2, 2:0, 3:1, 4:3, 5:4};
            div_new = this.rewardDisplay.getItemDivId(sprite_map[loc]);
            if (this.player_id == actor_id)
            {
                this.currentPlayerRewards.addToStockWithId(sprite_map[loc], sprite_map[loc], div_new);
                dojo.setStyle(div_new, 'opacity', '0.3');
                // Get div to add tooltip
                curDiv = this.currentPlayerRewards.getItemDivId(sprite_map[loc]);
                document.getElementById(curDiv).style.backgroundSize = '100%';
                this.addTooltip( curDiv, this.rewardToolTipData[9][loc], '' );

            } else {
                var idx = this.otherIdxFromPlayerIds[actor_id];
                this.otherPlayerRewards[idx].addToStockWithId(sprite_map[loc], sprite_map[loc], div_new);
                dojo.setStyle(div_new, 'opacity', '0.3');
                // Get div to add tooltip
                curDiv = this.otherPlayerRewards[idx].getItemDivId(sprite_map[loc]);
                document.getElementById(curDiv).style.backgroundSize = '100%';
                this.addTooltip( curDiv, this.rewardToolTipData[9][loc], '' );
            }            
        },
        notif_resolvePortalCount: function(notif)
        {
            //console.log('notif_resolvePortalCount');
            this.scoreCtrl[notif.args.player_id].incValue(notif.args.add_vp);
            actor_id = notif.args.player_id;
            loc = notif.args.nDone;
            var sprite_map = {2:5, 3:6, 4:7, 5:8, 6:9};
            div_new = this.rewardDisplay.getItemDivId(sprite_map[loc]);
            if (this.player_id == actor_id)
            {
                this.currentPlayerRewards.addToStockWithId(sprite_map[loc], sprite_map[loc], div_new);
                dojo.setStyle(div_new, 'opacity', '0.3');
                // Get div to add tooltip
                curDiv = this.currentPlayerRewards.getItemDivId(sprite_map[loc]);
                document.getElementById(curDiv).style.backgroundSize = '100%';
                this.addTooltip( curDiv, this.rewardToolTipData[10][loc], '' );
            } else {
                var idx = this.otherIdxFromPlayerIds[actor_id];
                this.otherPlayerRewards[idx].addToStockWithId(sprite_map[loc], sprite_map[loc], div_new);
                dojo.setStyle(div_new, 'opacity', '0.3');
                // Get div to add tooltip
                curDiv = this.otherPlayerRewards[idx].getItemDivId(sprite_map[loc]);
                document.getElementById(curDiv).style.backgroundSize = '100%';
                this.addTooltip( curDiv, this.rewardToolTipData[9][loc], '' );
            }
        },
        notif_newCardBonusChosen: function(notif)
        {
            //console.log('notif_newCardBonusChosen');
            actor_id = notif.args.player_id;
            card_id = parseInt(notif.args.new_card_id, 10);
            drawn_id = parseInt(notif.args.new_card_for_portalstock_id, 10);
            //console.log(notif.args);
           if (this.player_id == actor_id)
            {
                div_new = this.portCards.getItemDivId(card_id);
                this.currentPlayerActiveCards.addToStockWithId(card_id, card_id, div_new);
                curDiv = this.currentPlayerActiveCards.getItemDivId(card_id);
                document.getElementById(curDiv).style.backgroundSize = '600%';
                this.portCards.removeFromStockById(card_id);
                // add tooltip
                this.addCardToolTip(this.currentPlayerActiveCards, card_id, 500, 'active');                
                this.addSpotsToCard(this.currentPlayerActiveCards, card_id);
                // Move players gem zone over to the right
                dojo.setStyle('vmg_mygemzone', 'right', '230px');
           } else {
                var idx = this.otherIdxFromPlayerIds[actor_id];
                //console.log('newCardBonusChosen is other player');
                div_new = this.portCards.getItemDivId(card_id);
                this.otherPlayerActiveCards[idx].addToStockWithId(card_id, card_id, div_new);
                curDiv = this.otherPlayerActiveCards[idx].getItemDivId(card_id);
                document.getElementById(curDiv).style.backgroundSize = '600%';
                this.portCards.removeFromStockById(card_id);
                // add tooltip
                this.addCardToolTip(this.otherPlayerActiveCards[idx], card_id, 500, 'active');
                this.addSpotsToCard(this.otherPlayerActiveCards[idx], card_id);
                // Move players gem zone over to the right
                //dojo.setStyle('vmg_gemzone_'+actor_id, 'right', '230px');
            }
            // Add drawn card to the portal stock area 
            this.portCards.addToStockWithId(drawn_id, drawn_id, $('vmg_tokenarea'));
            curDiv = this.portCards.getItemDivId(drawn_id);
            document.getElementById(curDiv).style.backgroundSize = '600%';
            // Add tooltip
            this.addCardToolTip(this.portCards, drawn_id, 200, 'stock');
            
            //this.addSpotsToCard(this.portCards, drawn_id);
        },

        notif_removeGems: function(notif)
        {
            actor_id = notif.args.player_id;
            card_id = parseInt(notif.args.card_id, 10);
            gemToSendHome = notif.args.gem_home_array;
            card_color = notif.args.card_color;
            //console.log('RemoveGems');
            //console.log(notif.args);
            // Move Gems
            if (gemToSendHome.length > 0)
            {
                if (this.player_id == actor_id)
                {
                    // move gem markers
                    for (var jj=0; jj<gemToSendHome.length; jj++) 
                    {
                        this.move_gem_card_to_zone(gemToSendHome[jj], this.player_id, this.currentPlayerGemZone);
                    }    
                } else {
                    // move gem markers
                    var idx = this.otherIdxFromPlayerIds[actor_id];
                    for (var jj=0; jj<gemToSendHome.length; jj++) 
                    {
                        this.move_gem_card_to_zone(gemToSendHome[jj], actor_id, this.otherPlayerGemZone[idx]);
                    }    
                }
            }

            // Move card to done area
            if (this.player_id == actor_id)
            {
                // move card to done area
                div_old = this.currentPlayerActiveCards.getItemDivId(card_id);
                this.currentPlayerDoneCards.addToStockWithId(card_id, card_id, div_old);
                curDiv = this.currentPlayerDoneCards.getItemDivId(card_id);
                document.getElementById(curDiv).style.backgroundSize = '600%';
                this.addCardToolTip(this.currentPlayerDoneCards, card_id, 500, 'done');
                this.currentPlayerActiveCards.removeFromStockById(card_id);
                var currentPlayerNumberDoneCards = this.currentPlayerDoneCards.count();
                // Expand players done region if has > 6 cards
                if (currentPlayerNumberDoneCards > 6) 
                {
                    dojo.setStyle('vmg_myportaldonerow', 'height', (2.0*this.donerowheight)+'px');
                    dojo.setStyle('vmg_myarea_container', 'height', (this.activerowheight+2.0*this.donerowheight)+'px');
                }
            } else {
                div_old = this.otherPlayerActiveCards[idx].getItemDivId(card_id);
                this.otherPlayerDoneCards[idx].addToStockWithId(card_id, card_id, div_old);
                curDiv = this.otherPlayerDoneCards[idx].getItemDivId(card_id);
                document.getElementById(curDiv).style.backgroundSize = '600%';
                this.addCardToolTip(this.otherPlayerDoneCards[idx], card_id, 500, 'done');
                this.otherPlayerActiveCards[idx].removeFromStockById(card_id);
                var otherPlayerNumberDoneCards = this.otherPlayerDoneCards[idx].count();
                // Expand players done region if has > 6 cards
                if (otherPlayerNumberDoneCards > 6) 
                {
                    dojo.setStyle('vmg_playerportaldonerow_'+actor_id, 'height', (2.0*this.donerowheight)+'px');
                    dojo.setStyle('vmg_playerarea_container_'+actor_id, 'height', (this.activerowheight+2.0*this.donerowheight)+'px');
                }
            }
            // Update players portal counters
            this.portalCounters[actor_id].incValue(1);
            switch( card_color )
            {
                case 'yellow':
                    this.yellowCounters[actor_id].incValue(1);
                    break;
                case 'purple':
                    this.purpleCounters[actor_id].incValue(1);
                    break;
                case 'green':
                    this.greenCounters[actor_id].incValue(1);
                    break;
                case 'blue':
                    this.blueCounters[actor_id].incValue(1);
                    break;
            }
            
        },

        notif_completePortalBonusChosen: function(notif)
        {
            //console.log('entering completeportalbonuschosen');
            card_id = parseInt(notif.args.card_id, 10);
            var div = this.currentPlayerActiveCards.getItemDivId(card_id);
            dojo.setStyle(div, 'opacity', '0.3');
        }
    });             
});
