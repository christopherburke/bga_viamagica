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
 * gameoptions.inc.php
 *
 * ViaMagica game options description
 * 
 * In this file, you can define your game options (= game variants).
 *   
 * Note: If your game has no variant, you don't have to modify this file.
 *
 * Note²: All options defined in this file should have a corresponding "game state labels"
 *        with the same ID (see "initGameStateLabels" in viamagica.game.php)
 *
 * !! It is not a good idea to modify this file when a game is running !!
 *
 */

$game_options = array(
    100 => array(
        'name' => totranslate('Display drawn tokens?'),
        'values' => array(
            1 => array('name' => totranslate('Yes'),
                        'description' => totranslate('The drawn tokens will be shown')),
            2 => array('name' => totranslate('No'),
                        'description' => totranslate('The drawn tokens will not be shown'))
        ),
        'default' => 1
    )

);


