{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- ViaMagica implementation : © Christopher J. Burke <christophjburke@gmail.com>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------
-->
<div id="vmg_centering_container">

<div id="vmg_portalstockrow">
    <div id="vmg_portalstock"></div>
    <div id="vmg_token_container">
        <div id="vmg_tokenhelpimage"></div>
        <div id="vmg_tokenarea"></div>
        <div id="vmg_tokencount"></div>
        <div id="vmg_tokendiscard"></div>
    </div>
</div>

<div id="vmg_rewardarea_container">
    <div id="vmg_rewardarea"></div>
</div>

<div id="vmg_myarea_container">
    <div id="vmg_myportalactiverow">
        <h3 id="my_name" style="color:#{MY_COLOR}">{MY_AREA}</h3>
        <div id="vmg_myportalstock"></div>
        <div id="vmg_mygemzone"></div>
        <div id="vmg_myrewardstock"></div>
    </div>
    <div id="vmg_myportaldonerow">
        <div id="vmg_myportaldonestock"></div>
    </div>
</div>



<!-- THIS is a block for player areas that are programmitcally filled in by ...view.php -->
<!-- BEGIN player -->
<div class="vmg_playerarea_container" id="vmg_playerarea_container_{PLAYER_ID}">
    <div class="vmg_playerportalactiverow" id="vmg_playerportalactiverow_{PLAYER_ID}">
        <h3 class="vmg_playerareaname" id="vmg_playername_{PLAYER_ID}" style="color:#{PLAYER_COLOR}">{PLAYER_NAME}</h3>
        <div class="vmg_playerportalstock" id="vmg_playerportalstock_{PLAYER_ID}"></div>
        <div class="vmg_gemzone" id="vmg_gemzone_{PLAYER_ID}"></div>
        <div class="vmg_rewardstock" id="vmg_rewardstock_{PLAYER_ID}"></div>
    </div>
    <div class="vmg_playerportaldonerow" id="vmg_playerportaldonerow_{PLAYER_ID}">
        <div class="vmg_playerportaldonestock" id="vmg_playerportaldonestock_{PLAYER_ID}"></div>
    </div>
</div>
<!-- END player -->



<!-- Blocks for the overall_player_board -->
<!-- BEGIN playerboard -->
<div class="vmg_player_board_counters" id="vmg_player_board_counters_{PLAYER_ID}">
    <div class="vmg_portal_count" id="vmg_portal_count_{PLAYER_ID}"></div>
    <div class="vmg_portal_icon"></div>
    <div class="vmg_yellow_count" id="vmg_yellow_count_{PLAYER_ID}"></div>
    <div class="vmg_yellow_icon"></div>
    <div class="vmg_purple_count" id="vmg_purple_count_{PLAYER_ID}"></div>
    <div class="vmg_purple_icon"></div>
    <div class="vmg_green_count" id="vmg_green_count_{PLAYER_ID}"></div>
    <div class="vmg_green_icon"></div>
    <div class="vmg_blue_count" id="vmg_blue_count_{PLAYER_ID}"></div>
    <div class="vmg_blue_icon"></div>
</div>
<!-- END playerboard -->


</div>

<div id="vmg_card_spots">
</div>

<div id="vmg_gems">
</div>

<div id="vmg_tooltip_holderbars">
</div>

<div id="vmg_token_holder">
</div>

<!-- BEGIN playerincantatum -->
<div class="vmg_incantatum" id="vmg_incantatum_{PLAYER_ID}">INCANTATUM!</div>
<!-- END playerincantatum -->


<script type="text/javascript">

// Javascript HTML templates

// html template for gems
var jstpl_gems='<div class="vmg_gem" id="vmg_gem_${play_id}_${type_arg}"></div>';
// tpl for portal card spots
var jstpl_card_spot='<div class="vmg_cspot" id="vmg_cspot_${larg_use}_${cpos_use}"></div>';
//tpl for catcher player_name
var jstpl_catcher_name='<div id="vmg_tokencatcher" style="color:#${player_color}">${catcher_translate}${player_name}</div>';
// template for portal card tooltip
var jstpl_card_tooltip='<div class="vmg_card-tooltip-container"><div class="vmg_card-tooltip-text"><b>${type}</b> ${type_str}<hr> <b>${point}</b> ${point_str}<hr> <b>${effect}</b> ${effect_str}</div><div class="vmg_card-tooltip-image" style="background-position: ${backpos}"></div></div>';
// tpl for tooltip holder bar
var jstpl_tooltip_bar='<div class="vmg_card-tooltip-holderbar-${cardtype}" id="vmg_ttbar_${larg_use}_${cpos_use}"></div>';
// tpl for drawn tokens
var jstpl_token_discard='<div class="vmg_tokendiscard_items" id="vmg_tokendiscard_${type}_${id}" style="background-position:${backpos}%;width:${width}px;height:${height}px"></div>';

</script>  

{OVERALL_GAME_FOOTER}
