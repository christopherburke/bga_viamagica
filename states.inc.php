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
 * states.inc.php
 *
 * ViaMagica game states description
 *
 */
 
$machinestates = array(

    // The initial state. Please do not modify.
    1 => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array( "" => 2 )
    ),
    
    // Note: ID=2 => your first state

    2 => array(
        "name" => "chooseInitCards",
        "type" => "multipleactiveplayer",
    	"description" => clienttranslate('Others are choosing starting portal cards.'),
    	"descriptionmyturn" => clienttranslate('Select 3 portal cards in your player area to keep and then press Done button.'),
    	"possibleactions" => array( "chooseInitCards" ),
        "transitions" => array( "initCardsChosen" => 10, "forceEndGame" => 99, "zombiePass" => 10 ),
        "action" => 'stMultiPlayerInit'
    ),

    10 => array(
        "name" => "drawToken",
        "type" => "game",
        "action" => "stDrawToken",
        "updateGameProgression" => false,   
        "transitions" => array( "tokenDrawn" => 20)
    ),

    20 => array(
        "name" => "placeGem",
        "type" => "multipleactiveplayer",
        "description" => clienttranslate('Others are choosing crystals'),
        "descriptionmyturn" => clienttranslate('Select both a crystal and an Animus space'),
        "args" => "argAvailGemPlaces",
        "possibleactions" => array("placeGem"),
        "updateGameProgression" => false,   
        "transitions" => array( "gemPlaced" => 30, "noGemsToPlace" => 10, "zombiePass" => 30 ),
        "action" => 'stMultiPlayerInitFlexible'
    ),

    30 => array(
        "name" => "checkForCompCards",
        "type" => "game",
        "action" => "stCheckForCompCards",
        "updateGameProgression" => false,
        "transitions" => array( "compCardsDone_NoDone" => 40, "compCardsDone_HasDone" => 40)
    ),

    40 => array(
        "name" => "dispatchCompCards",
        "type" => "game",
        "action" => "stDispatchCompCards",
        "updateGameProgression" => false,
        "transitions" => array( "resolveBonus" => 51, "noCardsLeft" => 95)
    ),

    51 => array(
        "name" => "removeGems",
        "type" => "game",
        "action" => "stRemoveGems",
        "updateGameProgression" => false,
        "transitions" => array( "resolveBonus" => 53)
    ),

    53 => array(
        "name" => "resolveBonus",
        "type" => "game",
        "action" => "stResolveBonus",
        "updateGameProgression" => false,
        "transitions" => array( "activatePortal" => 45, "dispatchExPlayGem" => 55, "chooseNewCardBonus" => 60, "completePortalBonus" => 65)
    ),

    55 => array(
        "name" => "dispatchExPlayGem",
        "type" => "game",
        "action" => "stDispatchExPlayGem",
        "updateGameProgression" => false,
        "transitions" => array( "exPlayGem" => 56, "doneDispatchExPlayGem" => 45 )
    ),

    56 => array(
        "name" => "exPlayGem",
        "type" => "activeplayer",
        "description" => clienttranslate('Portal card bonus: ${actplayer} is placing extra crystals'),
        "descriptionmyturn" => clienttranslate('Select both a crystal and an Animus space. This is crystal ${gem_count} of ${gem_strt_count} from portal card bonus.'),
        "args" => "argAvailGemPlacesEx",
        "possibleactions" => array('placeGem', 'exPlayGem'),
        "transitions" => array( "donePlacingGemEx" => 55, "zombiePass" => 55)
    ),

    60 => array(
        "name" => "chooseNewCardBonus",
        "type" => "activeplayer",
        "description" => clienttranslate('Portal card bonus: ${actplayer} is adding a portal card to their play area'),
        "descriptionmyturn" => clienttranslate('Portal card bonus: Choose new card to add to your playing area'),
        "args" => "argCardInfo",
        "possibleactions" => array('chooseNewCardBonus'),
        "transitions" => array( "doneChoosingNewCardBonus" => 45, "zombiePass" => 45)
    ),

    65 => array(
        "name" => "completePortalBonus",
        "type" => "activeplayer",
        "description" => clienttranslate('Portal card bonus: ${actplayer} is opening a bonus portal'),
        "descriptionmyturn" => clienttranslate('Portal card bonus: Choose another portal to open in your play area'),
        "possibleactions" => array('completePortalBonus'),
        "transitions" => array( "doneCompletePortalBonus" => 45, "zombiePass" => 45)
    ),

    45 => array(
        "name" => "completeCard",
        "type" => "activeplayer",
        "description" => clienttranslate('${actplayer} is resolving completed portal card'),
        "descriptionmyturn" => clienttranslate('Choose new portal card to replace your completed portal card'),
        "args" => "argCardInfo",
        "possibleactions" => array("completeCard"),
        "updateGameProgression" => true,
        "transitions" => array( "completedCard" => 50, "zombiePass" => 50) 
    ),

    50 => array(
        "name" => "scoreAdjustRules",
        "type" => "game",
        "action" => "stScoreAdjustRules",
        "updateGameProgression" => false,
        "transitions" => array( "checkCompleteCards" => 30, "resolvePortalCount" => 52)
    ),

    52 => array(
        "name" => "resolvePortalCount",
        "type" => "activeplayer",
        "description" => clienttranslate('${actplayer} decides whether to claim the ${n_card} portal card reward'),
        "descriptionmyturn" => clienttranslate('Do you want to claim the reward for opening ${n_card} portal cards'),
        "args" => "argPortalCount",
        "possibleactions" => array("resolvePortalCount"),
        "transitions" => array( "checkCompleteCards" => 30, "zombiePass" => 53)
    ),


    95 => array(
        "name" => "checkEndGame",
        "type" => "game",
        "action" => "stCheckEndGame",
        "updateGameProgression" => true,
        "transitions" => array( "endGame" => 99, "gameNotEnded" => 10)
    ),
    
    //97 => array(
    //    "name" => "endGameScoring",
    //    "type" => "game",
    //    "action" => "stEndGameScoring",
    //    "updateGameProgression" => true,
    //    "transitions" => array( "endGame" => 99)
    //),

    // Final state.
    // Please do not modify (and do not overload action/args methods).
    99 => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);



