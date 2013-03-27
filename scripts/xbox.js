//The xbox object will contain everything we need for dealing with the backend
var xbox = {
	//URL for Games controller
	gamesUrl: "games.php",
	
	//URL for User controller
	userUrl: "user.php",
	
    //Voter Eligibility
    canVote: true,

    //Purchase Mode
    purchaseMode: false,

    //Contains setTimeout handler from xbox.render()
    renderToken: 0,  

    //Functions exclusively for dev & testing
    dev: {
        //Unlimited votes/add games, will call resetVote after every vote/add
        unlVotes: false,

        //Resets the user's vote for the day
        resetVote: function(cb) {
            cbFunc = function() { xbox.checkVote(cb); };
            $.post(xbox.userUrl, { action: 'resetVote' }, cbFunc);
        }
    },
	
	//Get current sorting criterion
	checkSort: function() {
		return $('#bsSort :radio:checked').attr('value');
	},

    //Get eligibility from server, set canVote, call callBack
    checkVote: function(callBack) {
        $.post(xbox.userUrl, { action: 'isEligible' }, function (data) {
            xbox.canVote = data.eligible;
            if (typeof callBack == 'function')
			{
                callBack();
            }
        });

        //NOTE: The 'Vote' buttons on each game are displayed using the CSS selector .canvotetrue:hover

        //Update .canvote[bool] class
        $('.game').removeClass('canvote'+!xbox.canVote).addClass('canvote'+xbox.canVote);
    },

    //Get All gams from server and render
    getAll: function () {
        $.post(xbox.gamesUrl, { action: 'getAll', sort: xbox.checkSort() }, function (data) { xbox.games = data.games; xbox.renderGames(); });
    },

    //Retrieve filtered list of games from server and render
    getFind: function (p,v) {
        $.post(xbox.gamesUrl, { action: 'find', sort: xbox.checkSort(), param: p, value: v }, function (data) { xbox.games = data.games; xbox.renderGames(); });
    },

    //Adds vote for a game
    addVote: function (id) {
        $.post(xbox.gamesUrl, { action: 'vote', id: id }, function (data) {
            xbox.json = data;
            //Resets vote if unlimited votes enabled
            if (xbox.dev.unlVotes)
			{
                fun = xbox.dev.resetVote;
            }
            else
			{
                fun = xbox.checkVote;
            }
            //Calls either checkVote or resetVote, then refreshes the current view using a callback
            fun(xbox.refresh);
        });
    },

    //Mark game as purchased
    purchase: function (id) {
        $.post(xbox.gamesUrl, { action: 'purchase', id: id}, function (data) {
            xbox.json = data;
            //Display "Purchased" text
            $(".game[data-id='game" + id + "']").removeClass('wantit').addClass('gotit');
            //Make Purchased Item Disappear if in Up for Vote View
            view = $('#bsView :radio:checked').attr('id')
            if (view == "rWantItGames")
			{
                $(".game[data-id='game" + id + "']").fadeOut(400, function () {
                        $(this).remove();
                    });
            }
        });
    },

    //Clears all games
    clearGames: function () {
        //NOTE: I would generally create a confirmation dialog, but didn't due to time constraints
        $.post(xbox.gamesUrl, { action: 'clearAll' }, function (data) {
            xbox.games = data.games;
            xbox.renderGames();
        });
    },

    //Function used in sorting game arrays by vote (numerically)
    arrSortVotes: function(a,b) {
        return parseInt(b.Votes) - parseInt(a.Votes);
    },

    //Function used in  sorting game arrays by title (alphabetically)
    arrSortTitle: function(a,b) {
        if (a.Title < b.Title)
        {
            return -1;
        }
        if (a.Title > b.Title)
        {
            return 1;
        }
        return 0;
    },

    //Reloads the currently selected view
    refresh: function()
    {
        switch($('#bsView :radio:checked').attr('id'))
        {
            case "rAllGames":
            {
                xbox.getAll();
                break;
            }
            case "rPurchaseGames":
            {
                xbox.getFind("Status", "gotit");
                break;
            }
            case "rWantItGames":
            {
                xbox.getFind("Status", "wantit");
                break;
            }
        }
    },

    //Handles all calls to xbox.render()
    renderGames: function (newGame) {
        //Cancels any previous render requests, preventing glitchy rendering from frequent calls
        clearTimeout(xbox.renderToken);
        cb = function () { xbox.render(newGame); };
        //Render view in half a second, or render new request
        xbox.renderToken = setTimeout(cb, 500);
    },

    //Renders the xbox.games array into dvGames using jQuery quicksand
    render: function (newGame) {

        /*
        NOTE: While typing in the Title name for a new game,
        the view will filter matching unpurchased Titles
        so you can vote on it instead. Figured that less than
        ten additional lines of code was worth the convenience.
        */

        //Sets filter selector and sets quicksand retain property
        if (newGame)
		{
            //Filter for New Games
            retain = true;
            flt = ".newgame .txTitle";
        }
        else
		{
            //Default Filter
            retain = false;
            flt = "#srch";
        }

        //Filter xbox.games if there are any characters in the searchbox
        var filter = $(flt).val().toLowerCase();
        var games = new Array();
        for (i=0; i < xbox.games.length; i++)
        {
            if ((xbox.games[i].Title || "").toLowerCase().indexOf(filter) >= 0)
            {
                //Add as long it's not [ new game mode + already purchased ]
                if (!(retain && xbox.games[i].Status == "gotit"))
				{
                    games[games.length] = xbox.games[i];
                }
            }
        }

        //Sort results
        games.sort(function (a, b) {
            var srtFun = xbox['arrSort'+xbox.checkSort()];
            return srtFun(a, b);
        });

        //ngx = "ngxtrue" when NewGame is Expanded
        var ngx = "";

        //Adds NewGame box first if eligible and not in purchase mode
        if (xbox.canVote && !xbox.purchaseMode)
		{
            //Add ngx class if NewGame is expanded so quicksand renders proper width
            if ($('.gameForm:visible').length>0)
            {
                ngx = " ngxtrue";
            }
            var ntxt = "<div data-id='gameNewGame' class='game newgame"+ngx+"'>";
            ntxt += $('#dvNewGame').html();
            ntxt += "</div>";
            $('#dvGhost').append(ntxt).hide();
        }

        //Add each game that passed through the filter & sorting
        for (i = 0; i < games.length; i++) {
            var game = games[i];
            var txt = "<div class='game " + game.Status + "' data-id='game" + game.Id +"'>"
            txt += "<div class='gameTitle'><h3>"+ game.Title + "</h3></div>";
//            txt += "<div class='gameStatus'>" + game.Status + "</div>";
            txt += "<div class='gameVotes'><span>&nbsp;&nbsp;&nbsp;&nbsp;" + game.Votes + "</span> <em>votes</em></div>";
            txt += "<div class='gameBtn'><button class='avote' data-ids='" + game.Id + "'>Vote</button>";
            txt += " <button class='apurchase' data-ids='"+game.Id+"'>Purchase</button><em>Purchased</em></div>";
            txt += "</div>";
            $('#dvGhost').append(txt).hide();
        }
        
        //Initialize Vote and Purchase buttons with jQuery UI + icons
        $('.avote').button({ icons: { primary: "ui-icon-check" } });
        $('.apurchase').button({ icons: { primary: "ui-icon-cart" } });

        //Use jQuery quicksand library to render games into dvGames
        $('#dvGames').quicksand($('#dvGhost .game'), {
            //Will reposition without updating div content when false (used to prevent newgame form from resetting)
            retainExisting: retain
            },
            //Callback function
            function () {
                //Resets dvGames width (converted to px by quicksand)
                $('#dvGames').css('width', '100%');
                //Empty dvGhost to prevent double appending of games in future rendering
                $('#dvGhost').html("");
                //Binds function to Vote Button
                $('.avote').on('click', function () {
                    $(this).fadeOut(200);
                    xbox.addVote($(this).attr('data-ids'));
                });
                //Binds function to Purchase Button
                $('.apurchase').on('click', function () {
                    xbox.purchase($(this).attr('data-ids'));
                });
                //Initializes new game form and focuses on txTitle if already visible
                xbox.initNewGame();
                if (ngx != "")
				{
                    $('.newgame .txTitle').focus();
                }
                //Update canVote from server
                xbox.checkVote();
            });
    },

    //Adds a new game to the SOAP service
    newGame: function () {
        //Cancel any rendering in progress
        clearTimeout(xbox.renderToken);

        tx = $('.newgame .txTitle').val();
        //VALIDATION: Check if the title is blank
        if (tx == "" || tx == null)
		{
            //ERROR: blank title
            $('.newgame .spErr').html("<label>&nbsp;</label>Please provide a Title").fadeIn();
            return false;
        }
        //Send add request to server
        $.post(xbox.gamesUrl, { action: 'add', title: tx }, function (data) {
            xbox.json = data;
            //If error exists, return error
            if (data.error)
			{
                $('.newgame .spErr').html("<label>Error:</label>" + data.error).fadeIn();
                return false;
            }
            //Successful Add, convert newgame box to show the newly added game
            game = data.games[0];
            $('.newgame .gameTitle h3').html(game.Title);
            $('.newgame .gameForm').fadeOut(200, function () { $('.newgame .gameForm').remove();  console.log('step2'); });
            $('.newgame').animate({ width: 150 },{
                queue: false,
                complete: function () {
                    txt = "<div class='gameVotes'><span>&nbsp;&nbsp;&nbsp;&nbsp;" + game.Votes + "</span> <em>votes</em></div>";
                    txt += "<div class='gameBtn'><button class='avote' data-ids='" + game.Id + "'>Vote</button>";
                    txt += " <button class='apurchase' data-ids='" + game.Id + "'>Purchase</button><em>Game Added</em></div>";
                    $('.newgame').append($(txt));
                    $('.newgame').attr('data-id', 'game' + game.Id);
                    $('.newgame').removeClass('newgame');
                    if (xbox.dev.unlVotes)
					{
                        //Reset canVote and refresh if unlimited votes
                        totxt = "xbox.dev.resetVote(xbox.refresh);";
                    }
                    else
					{
                        //Update canVote and refresh 
                        totxt = "xbox.checkVote(xbox.refresh);";
                    }
                    clearTimeout(xbox.renderToken);
                    setTimeout(totxt, 1500);
                }
            });
        });
    },

    //Initializes newgame form components
    initNewGame: function () {
        //Show Form on click
        $('.newgame').on('click', function () {
            //Unbind this function to prevent calling it when form is visible
            $('.newgame').unbind('click');
            //ReStyle
            $(this).css('cursor', 'default');
            //FadeOut
            $('.newgame .gamePlus').fadeOut();
            //Animate Expanding width
            $('.newgame').animate({ width: 330 }, 400, 'swing', function () {
                //Show Form
                $('.newgame .gameForm').fadeIn(400, function () { $('.txTitle').focus(); });
            });
        });

        //Add Game Button
        $('.newgame .ngAddGame').button({ icons: { primary: 'ui-icon-plus' } }).click(function (e) {
            e.stopPropagation(); //Prevents click from going up dom tree (calling click on .newgame div)
            //Add New Game
            xbox.newGame();
        });

        //Cancel Button
        $('.newgame .ngCancel').button({ icons: { primary: 'ui-icon-cancel' } }).click(function (e) {
            e.stopPropagation(); //Prevent .newgame from immediately reexpanding
            //Fade form out
            $('.newgame .gameForm').fadeOut(400, function () {
                //Fade in Plus sign
                $('.newgame .gamePlus').fadeIn();
                //Shrink .newgame
                $('.newgame').animate({ width: 150 }, 400, 'swing', function () {
                    //Rebind function to .newame click
                    $('.newgame').on('click', function () {
                        $('.newgame').unbind('click');
                        $(this).css('cursor', 'default');
                        $('.newgame .gamePlus').fadeOut();
                        $('.newgame').animate({ width: 330 }, 400, 'swing', function () {
                            $('.newgame .gameForm').fadeIn();
                        });
                    });
                });
            });
        });

        //Make Title a searchbox, bind newGame on enter press
        $(".newgame .txTitle").on('keyup', function (e) {
            if (e.keyCode == 13)
			{
                xbox.newGame();
                return false;
            }
            clearTimeout(xbox.renderToken);
            xbox.renderToken = setTimeout("xbox.renderGames(true);", 500);
        });
    }
};