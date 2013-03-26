$(document).ready(function () {
    //Initialize all jquery ui buttons
    $('button').button();
    //Initialize .radios as jquery ui buttonsets
    $('.radios').buttonset();

//Search TextBox
    $("#srch").on('keyup', function () {
        xbox.renderGames();
    });

//View: All, Purchased, Up for Vote
    $("[for='rAllGames']").click(function() {
        xbox.getAll();
    });
    $("[for='rGotItGames']").click(function() {
        xbox.getFind("Status", "gotit");
    });
    $("[for='rWantItGames']").click(function() {
        xbox.getFind("Status", "wantit");
    });

//Sort: By Vote, Alphabetically
    $("[name='rSort']").click(function () {
        xbox.renderGames();
    });

//Settings Menu
    //Settings Button
    $('#btnSettings').button({
        icons: {
            primary: "ui-icon-gear",
            secondary: "ui-icon-triangle-1-s"
        },
        text: false
    }).click(function (e) {
        //Prevent slideup from auto triggering
        e.stopPropagation();
        //Check if user can vote/add new games
        if (!xbox.canVote)
		{
            $('#liNew').addClass('ui-state-disabled');
        }
        else
		{
            $('#liNew').removeClass('ui-state-disabled');
        }
        //Set Check if Unlimited Votes True
        if (xbox.dev.unlVotes)
		{
            $('.unlicon').addClass('ui-icon ui-icon-check');
        }
        else
		{
            $('.unlicon').removeClass('ui-icon ui-icon-check');
        }
        $('#mnuSett').slideDown(200);
        $('#btnSettings').button('disable').addClass("ui-state-active");
    });

    //Settings Menu
    $('#mnuSett').menu().position({
        //Align to right side of settings button
        my: 'right top',
        at: 'right bottom',
        of: $('#btnSettings')
    });

    //SlideUp Settings Menu on clickout
    $(document).on('click', function () {
        $("#mnuSett:visible").slideUp(200);
        $("#btnSettings.ui-state-active").button('enable').removeClass("ui-state-active");
    });

//Menu: New Game
    $('#liNew').click(function () {
        //If canVote
        if (xbox.canVote)
		{
            //Check if new game is already visible
            if ($('.newgame:visible').length > 0)
			{
                //Check if already showing form
                if ($('.newgame:visible .gameForm:visible').length > 0)
				{
                    //Simply set focus to txTitle
                    $('.txTitle').focus();
                }
                else
				{
                    //Assume newgame is initialized and show form
                    $('.newgame').unbind('click');
                    $(this).css('cursor', 'default');
                    $('.newgame .gamePlus').fadeOut();
                    $('.newgame').animate({ width: 330 }, 400, 'swing', function () {
                        $('.newgame .gameForm').fadeIn(400, function () { $('.txTitle').focus(); });
                    });
                }
            }
            else
			{
                //Assume you were in purchase mode, as New Game menu button is inaccessible when canVote==false
                //Exit Purchase Mode
                xbox.purchaseMode = false;
                $('#dvGames').removeClass('purchaseMode');
                $('#dvXPurchase').fadeOut();

                //Add newgame to view
                var ntxt = "<div data-id='gameNewGame' class='game newgame ngxfalse'>";
                ntxt += $('#dvNewGame').html();
                ntxt += "</div>";
                $('#dvGames').prepend($(ntxt));
                //Initialize new game
                xbox.initNewGame();
                //Expand New Game
                $('.newgame').unbind('click');
                $(this).css('cursor', 'default');
                $('.newgame .gamePlus').fadeOut();
                $('.newgame').animate({ width: 330 }, 400, 'swing', function () {
                    $('.newgame .gameForm').fadeIn(400, function () { $('.txTitle').focus(); });
                });
            }
        }
    });
//Menu: Purchase
    $('#liPurchase').click(function () {
        xbox.purchaseMode = true;
        $('#dvXPurchase').fadeIn();
        $('#dvGames').addClass('purchaseMode');
        $('#rWantItGames').click();
        xbox.getFind('Status', 'wantit');
    });
    //Exit Purchase Mode
    $('#xpurchase').button({ icons: { primary: "ui-icon-close" } }).click(function () {
        xbox.purchaseMode = false;
        $('#dvGames').removeClass('purchaseMode');
        $('#dvXPurchase').fadeOut();
        xbox.refresh();
    });

//Menu: Clear All
    $('#liClear').click(function () {
        //Usually would confirm here, but I'm avoiding dialogs for the sake of time
        xbox.clearGames();
    });
//Menu: Reset Vote
    $('#liReset').click(function () {
        xbox.dev.resetVote(xbox.refresh);
    });
//Menu: Unlimited Voting
    $('#liUnl').click(function () {
        //Toggle unlimited votes
        xbox.dev.unlVotes =  !xbox.dev.unlVotes;
        xbox.dev.resetVote(xbox.refresh);
    });

//Finally, Load All Games
    xbox.checkVote(xbox.getAll);
});