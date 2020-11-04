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
 * stats.inc.php
 *
 * ViaMagica game statistics description
 *
 */


$stats_type = array(

    // Statistics global to table
    "table" => array(

        "total_tokens_drawn" => array("id"=> 10,
                    "name" => totranslate("Total number of tokens drawn"),
                    "type" => "int" ),
        "air_tokens_drawn" => array("id"=> 11,
                    "name" => totranslate("Number of air tokens drawn"),
                    "type" => "int" ),
        "water_tokens_drawn" => array("id"=> 12,
                    "name" => totranslate("Number of water tokens drawn"),
                    "type" => "int" ),
        "earth_tokens_drawn" => array("id"=> 13,
                    "name" => totranslate("Number of earth tokens drawn"),
                    "type" => "int" ),
        "life_tokens_drawn" => array("id"=> 14,
                    "name" => totranslate("Number of life tokens drawn"),
                    "type" => "int" ),
        "fire_tokens_drawn" => array("id"=> 15,
                    "name" => totranslate("Number of fire tokens drawn"),
                    "type" => "int" ),
        "shadow_tokens_drawn" => array("id"=> 16,
                    "name" => totranslate("Number of shadow tokens drawn"),
                    "type" => "int" ),
        "wildcard_tokens_drawn" => array("id"=> 17,
                    "name" => totranslate("Number of wildcard tokens drawn"),
                    "type" => "int" ),
        "total_cards_avail" => array("id"=>20,
                    "name" => totranslate("Total number of portal cards drawn"),
                    "type" => "int"),
        "green_cards_avail" => array("id"=>21,
                    "name" => totranslate("Number of green portal cards drawn"),
                    "type" => "int"),
        "yellow_cards_avail" => array("id"=>22,
                    "name" => totranslate("Number of yellow portal cards drawn"),
                    "type" => "int"),
        "purple_cards_avail" => array("id"=>23,
                    "name" => totranslate("Number of purple portal cards drawn"),
                    "type" => "int"),
        "blue_cards_avail" => array("id"=>24,
                    "name" => totranslate("Number of blue portal cards drawn"),
                    "type" => "int"),
    ),
    
    // Statistics existing for each player
    "player" => array(
        "total_crys_play" => array("id"=> 10,
                    "name" => totranslate("Total number of crystals played"),
                    "type" => "int" ),
        "air_crys_play" => array("id"=> 11,
                    "name" => totranslate("Number of air crystals played"),
                    "type" => "int" ),
        "water_crys_play" => array("id"=> 12,
                    "name" => totranslate("Number of water crystals played"),
                    "type" => "int" ),
        "earth_crys_play" => array("id"=> 13,
                    "name" => totranslate("Number of earth crystals played"),
                    "type" => "int" ),
        "life_crys_play" => array("id"=> 14,
                    "name" => totranslate("Number of life crystals played"),
                    "type" => "int" ),
        "fire_crys_play" => array("id"=> 15,
                    "name" => totranslate("Number of fire crystals played"),
                    "type" => "int" ),
        "shadow_crys_play" => array("id"=> 16,
                    "name" => totranslate("Number of shadow crystals played"),
                    "type" => "int" ),
        "pass_crys_play" => array("id"=> 17,
                    "name" => totranslate("Number of times player passed on playing crystal"),
                    "type" => "int" ),
        "total_cards_open" => array("id"=>20,
                    "name" => totranslate("Total number of portal cards opened"),
                    "type" => "int"),
        "green_cards_open" => array("id"=>21,
                    "name" => totranslate("Number of green portal cards opened"),
                    "type" => "int"),
        "yellow_cards_open" => array("id"=>22,
                    "name" => totranslate("Number of yellow portal cards opened"),
                    "type" => "int"),
        "purple_cards_open" => array("id"=>23,
                    "name" => totranslate("Number of purple portal cards opened"),
                    "type" => "int"),
        "blue_cards_open" => array("id"=>24,
                    "name" => totranslate("Number of blue portal cards opened"),
                    "type" => "int"),
        "total_card_points" => array("id"=>30,
                    "name" => totranslate("Total points scored from portal cards"),
                    "type" => "int"),
        "green_card_points" => array("id"=>31,
                    "name" => totranslate("Points scored from green portal cards"),
                    "type" => "int"),
        "yellow_card_points" => array("id"=>32,
                    "name" => totranslate("Points scored from yellow portal cards"),
                    "type" => "int"),
        "purple_card_points" => array("id"=>33,
                    "name" => totranslate("Points scored from purple portal cards"),
                    "type" => "int"),
        "blue_card_points" => array("id"=>34,
                    "name" => totranslate("Points scored from blue portal cards"),
                    "type" => "int"),

    

    )

);
