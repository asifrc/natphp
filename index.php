<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width" />
    <title>Xbox Game of the Week</title>
    <link rel="stylesheet" href="styles/jquery-ui.min.css" />
    <link rel="stylesheet" href="styles/global.css" />
    <script type="text/javascript" src="scripts/jquery-1.8.2.min.js"></script>
    <script type="text/javascript" src="scripts/jquery-ui-1.9.2.min.js"></script>
    <script type="text/javascript" src="scripts/jquery.quicksand.js"></script>
    <script type="text/javascript" src="scripts/xbox.js"></script>
    <script type="text/javascript" src="scripts/docReady.js"></script>
</head>
<body>
<!--Header-->
    <div class="main">
        <div class="header">
            <h1 align="center">Xbox Game of the Week</h1>
        </div>
        <div class="content">
<!--Content-->
            <!-- Menu Bar -->
            <div id="dvMenu">
                <!-- Search Textbox -->
                <div id="dvSearch">
                    <div><strong>Search</strong></div>
                    <div><input type="text" id="srch" placeholder=" Search Games.." /></div>
                </div>
                <!-- 'View' Filter -->
                <div class="radios" id="bsView">
                    <div><strong>View</strong></div>
                    <input type="radio" name="rView" id="rAllGames" checked="checked" /><label for="rAllGames">All Games</label>
                    <input type="radio" name="rView" id="rGotItGames" /><label for="rGotItGames">Owned</label>
                    <input type="radio" name="rView" id="rWantItGames" /><label for="rWantItGames">Up for Vote</label>
                </div>
                <!-- 'Sort By' Filter' -->
                <div class="radios" id="bsSort">
                    <div><strong>Sort</strong></div>
                    <input type="radio" name="rSort" id="rVotes" checked="checked" value="arrSortVote" /><label for="rVotes">By Votes</label>
                    <input type="radio" name="rSort" id="rAlpha" value="arrSortTitle" /><label for="rAlpha">Alphabetically</label>
                </div>
                <!-- Menu Button -->
                <div id="dvSett">
                    <div><strong>&nbsp;</strong></div>
                    <button id="btnSettings">Menu</button>
                    <!-- Menu -->
                    <ul id="mnuSett">
                        <li id="liNew"><a href="#"><span class="ui-icon ui-icon-plus"></span>New Game</a></li>
                        <li id="liPurchase"><a href="#"><span class="ui-icon ui-icon-cart"></span>Purchase Games</a></li>
                        <li id="liClear"><a href="#"><span class="ui-icon ui-icon-trash"></span>Clear All Games</a></li>
                        <li class="ui-state-disabled dis-override"><a href="#"><span class="ui-icon ui-icon-wrench"></span>Dev Options</a></li>
                        <li id="liReset"><a href="#"><span class="ui-icon ui-icon-refresh"></span>Reset Vote</a></li>
                        <li id="liUnl"><a href="#"><span class="unlicon"></span>Unlimited Votes</a></li>
                    </ul>
                </div>
                <!-- Exit Purchase Mode -->
                <div id="dvXPurchase"><div id="xpurchase">Exit Purchase Mode</div></div>
            </div>
            <!-- Games Container -->
            <div id="dvGames">
            
            </div>
            <!-- Invisible containter to stage games before calling jQuery Quicksand -->
            <div id="dvGhost">
            
            </div>
            <!-- dvNewGame is inserted into dvGames at runtime via xbox.renderGames if user can vote -->
            <div id="dvNewGame">
                <div class="gameTitle"><h3>Add New Game</h3></div>
                <div class="gamePlus"><span>+</span></div>
                <div class="gameForm">
                    <div class="ngError"><span class="spErr"><label>Error:</label>This is an error Message</span>&nbsp;</div>
                    <div class="ngTitle"><label>Title:</label><input type="text" class="txTitle" /></div>
                    <div class="ngBtns"><label>&nbsp;</label><div class="ngAddGame">Add Game</div><div class="ngCancel">Cancel</div></div>
                </div>
            </div>
<!--Footer-->
        </div>
        <div class="footer">
            Nerdery Assessment Test: PHP - Asif Choudhury
        </div>
    </div>
</body>
</html>