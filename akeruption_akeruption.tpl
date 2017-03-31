{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- akeruption implementation : © <Your name here> <Your email address here>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------

-->
<div id="board">
    <!-- BEGIN tile -->
    <div id="tile_{X}_{Y}" class="tile" style="position:absolute; top: {YPOS}px; left: {XPOS}px;">
        <div id="tile_clickable_{X}_{Y}" class="tileClickable"></div>
    </div>
    <!-- END tile -->
    <div id="tileStack">
        <div id="tileCount"></div>                
    </div>
</div>

<div id="spacer">
</div>
    
<div id="privatePlayerCards">
    <h2 id="txtHand"></h2>
    <div class="whiteblock">
        <div id="playerHand">
        </div>
    </div>
</div>
    
<div id="cardStack">
    <div id="cardDraw" class="cardBack">
        <div id="cardCount"></div>     
    </div>
    <div id="cardDiscard"></div>
</div>
    
<div id="eruptionTiles">
    <div class="eruptionTileHolder">
        <div id="eruptionTile3" class="eruptionTile"></div>
    </div>
    <div class="eruptionTileHolder">
        <div id="eruptionTile2" class="eruptionTile"></div>
    </div>
    <div class="eruptionTileHolder">
        <div id="eruptionTile1" class="eruptionTile"></div>
    </div>
</div>

<script type="text/javascript">

// Javascript HTML templates
var jstpl_player_iconsA = '<div class="iconholder" style="margin-top:5px;"><div id="strawicon_${id}" class="strawicon wallicon"></div><div id="strawcount_p${id}" class="wallcount">0</div><div id="woodicon_${id}" class="woodicon wallicon"></div><div class="wallcount" id="woodcount_p${id}">0</div><div id="stoneicon_${id}" class="stoneicon wallicon"></div><div class="wallcount" id="stonecount_p${id}">0</div></div>';
var jstpl_player_iconsB = '<div class="iconholder"><div class="cardicon"></div><div class="wallcount" id="cardcount_p${id}">0</div><div class="sourceicon"></div><div id="sourcecount_p${id}" class="wallcount">0</div></div>';

var jstpl_cardBack = '<div class="cardBack" id="${id}" style="position: absolute;"></div>';
var jstpl_playerToken = '<div class="playerToken" id="playerToken_${player_id}" style="position:absolute; background-position:-${x}px -${y}px; z-index: ${z};">';
var jstpl_lavaTile = '<div class="lava" data-type="${type}" id="lava_${tile_id}" style="position:absolute; height: ${height}px; width: ${width}px; background-position:-${x}px -${y}px;z-index:${z}; transform: rotate(${rotation}deg)"></div>';
var jstpl_rotator = '<div class="rotator" id="rotator1"><div class="anticlockwiseArrow"></div><div class="clockwiseArrow"></div></div>';
var jstpl_wallArrow = '<div id="${id}" class="testArrow" style="top: ${x}px; left: ${y}px; transform: rotate(${rotation}deg);"></div>';
var jstpl_wall = '<div id="${id}" class="wall wallType_${type}" style="top: ${y}px; left: ${x}px; transform: rotate(${rotation}deg);"></div>';
var jstpl_actionCard = '<div class="card" id="card_${card_id}" style="position:absolute; background-position:-${x}px -${y}px;z-index:${z};"></div>';


</script>

{OVERALL_GAME_FOOTER}
