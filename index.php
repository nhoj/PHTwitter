<!DOCTYPE html>
<html>
<head>
    <title>Phone Halo Twitter Server</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap -->
    <link href="bootstrap-3.0.2/css/bootstrap.min.css" rel="stylesheet">

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
    <![endif]-->
    
    <style>
        #table-div {
            overflow: auto;
            cursor: pointer;
        }
        #statistics {
            
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Phone Halo Twitter Server</h1>
        
        <button type="button" class="btn btn-primary hidden" disabled="disabled" id="start-stream">Start Stream</button>&nbsp;
        <button type="button" class="btn btn-primary hidden" disabled="disabled" id="stop-stream">Stop Stream</button>&nbsp;
        <button type="button" class="btn btn-primary" id="fetch-tweets">Fetch Tweets</button>
        
        <div id="statistics">
            Tweets in database: <span id="num-tweets-db"></span> 
            <br />
            Tweets on this page: <span id="num-tweets-app"></span> 
        </div>
        
        <p id="first-p">&nbsp;</p>
        
        <div class="row">
            <div id="table-div" class="col-md-8 table-responsive">
                <table id="main-table" class="table table-condensed table-hover">
                    <thead>
                        <tr>
                            <th>&nbsp;</th>
                            <th>Date</th>
                            <th>Twitter User</th>
                            <th>Tweet</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="col-md-4">
                <p>
                    Choose message to send:<br />
                    <select class="form-control" id="messages"></select>
                </p>
                
                <p>
                    Choose account to send/reply:<br />
                    <select class="form-control" id="accounts"></select>
                </p>
                
                <p>&nbsp;</p>
                <button type="button" class="btn btn-primary" id="send-tweet">Send reply to selected tweets</button>
                <br />
                <br />
                <button type="button" class="btn btn-primary" id="delete-tweet">Delete selected tweets</button>
            </div>
        </div>
    </div>

    <div id="table-row-template" class="hidden">
        <table>
            <tr>
                <td><input class="tweet-checkbox" type="checkbox" /></td>
                <td class="timestamp"></td>
                <td class="tweet-username"></td>
                <td class="tweet-tweet"></td>
            </tr>
        </table>
    </div>

    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://code.jquery.com/jquery-1.10.1.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="bootstrap-3.0.2/js/bootstrap.min.js"></script>
    <script>
        // variables
        var limit = 10,
            start = 0;

        // ajax
        var startStream = function () {
                $.post("twitter-services.php", JSON.stringify({
                        "method": 1
                    }),
                    handleStartStream
                );
            },
            stopStream = function () {
                $.post("twitter-services.php", JSON.stringify({
                        "method": 2
                    }),
                    handleStopStream
                );
            },
            isStreaming = function () {
                $.post("twitter-services.php", JSON.stringify({
                        "method": 3
                    }),
                    handleIsStreaming
                );
            },
            fetchTweets = function (callback) {
                //console.log("fetching tweets", start, limit);
                $("#fetch-tweets").attr("disabled", "disabled");
                $.post("twitter-services.php", 
                    JSON.stringify({
                        "method": 5,
                        "start": start,
                        "limit": limit
                    }),
                    handleFetchedTweets
                );
            },
            sendTweet = function (callback) {
                var tweetIDs = []
                $("#main-table tbody tr.selected").each(function (index, element) {
                    tweetIDs.push($(element).attr("tweet-tweet-id"));
                });
                
                if (tweetIDs.length) {
                    $("#send-tweet, #delete-tweet").prop("disabled", true);
                    $.post("twitter-services.php", 
                        JSON.stringify({
                            "method": 6,
                            "tweetIDs": tweetIDs,
                            "message": $("#messages").find(":selected").text(),
                            "account": $("#accounts").find(":selected").text()
                        }),
                        handleSentTweets
                    );
                }
            },
            deleteTweets = function (callback) {
                var tweetIDs = []
                $("#main-table tbody tr.selected").each(function (index, element) {
                    tweetIDs.push($(element).attr("tweet-tweet-id"));
                });
                
                if (tweetIDs.length) {
                    $.post("twitter-services.php", 
                        JSON.stringify({
                            "method": 7,
                            "tweetIDs": tweetIDs
                        }),
                        handleDeletedTweets
                    );
                }
            };
    
        // callbacks
        var handleStartStream = function (data) {
                //console.log(data);

                $("#stop-stream").removeAttr("disabled", "");
                $("#start-stream").attr("disabled", "disabled");
            },
            handleStopStream = function (data) {
                //console.log(data);

                $("#start-stream").removeAttr("disabled", "");
                $("#stop-stream").attr("disabled", "disabled");
            },
            handleIsStreaming = function (data) {
                //console.log("here's the data: ", data);

                $("#start-stream, #stop-stream").removeClass("hidden");
                if (data.code === 1 && data.message) {
                    $("#stop-stream").removeAttr("disabled", "");
                    $("#start-stream").attr("disabled", "disabled");
                } else {
                    $("#start-stream").removeAttr("disabled", "");
                    $("#stop-stream").attr("disabled", "disabled");
                }
            },
            handleFetchedTweets = function (data) {
                var rows = [],
                    row,
                    i;
                for (i = 0; i < data.data.length; i++) {
                    row = $("#table-row-template tr")
                        .attr("tweet-user-id", data.data[i].id)
                        .attr("tweet-tweet-id", data.data[i].tweetID)
                        .find("td").each(function (index, element) {
                            if ($(element).hasClass("timestamp")) {
                                $(element).html(new Date(parseInt(data.data[i].timestamp, 10) * 1000));
                            } else if ($(element).hasClass("tweet-username")) {
                                $(element).html(data.data[i].username);
                            } else if ($(element).hasClass("tweet-tweet")) {
                                $(element).html(data.data[i].tweet);
                            } 
                        })
                        .parent();
                    rows.push(row.clone());
                }

                for (i = 0; i < rows.length; i++) {
                    rows[i].appendTo("#main-table tbody");
                    $("#main-table tbody")
                        .find("tr:last")
                        .click(function (event) {
                            //console.log("row clicked", event.target);
                            if (!$(event.target).is(":checkbox")) {
                                if ($(this).find("input").is(":checked")) {
                                    $(this).find("input").prop("checked", false);
                                } else {
                                    $(this).find("input").prop("checked", true);
                                }
                            }
                            if ($(this).hasClass("selected")) {
                                $(this).removeClass("selected");
                            } else {
                                $(this).addClass("selected");
                            }
                        });
                }
                
                if (rows.length) {
                    $("#fetch-tweets").text("Fetch More Tweets").removeAttr("disabled");
                    start += limit;
                } else {
                    $("#fetch-tweets").text("No More Tweets").prop("disabled", true);
                }
                
                numRowsApp();
            },
            handleSentTweets = function (data) {
                //console.log(data);
                var oldHandleFetchedTweets = handleFetchedTweets,
                    newHandleFetchedTweets = function (data) {
                        if (start < last) {
                            oldHandleFetchedTweets(data);
                            fetchTweets();
                        } else {
                            $("#send-tweet, #delete-tweet").prop("disabled", false);
                            $("#fetch-tweets").text("Fetch More Tweets").removeAttr("disabled");
                            handleFetchedTweets = oldHandleFetchedTweets;
                        }
                    };

                var last = start;
                
                $("#main-table tbody").empty();
                handleFetchedTweets = newHandleFetchedTweets;
                start = 0;
                fetchTweets();
            },
            handleDeletedTweets = function (data) {
                //console.log(data);
                start -= $("#main-table tbody tr.selected").length;
                $("#main-table tbody tr.selected").remove();
                numRowsApp();
            };
        
        
        /*
            Code execution
        */
        
        isStreaming();
        
        // get number of rows in the database
        var numRows = function () {
                $.post("twitter-services.php", 
                    JSON.stringify({
                        "method": 4
                    }),
                    function (data) {
                         //console.log("num rows", data);
                         $("#num-tweets-db").text(data.data);
                    }
                );
            };
        numRows();
        
        // get number of rows on the app
        var numRowsApp = function () {
                $("#num-tweets-app").text($("#main-table tbody tr").length);   
            };
        
        // get messages:
        $.ajax({
            "url":      "twitter/twitter-messages.txt",
            "dataType": "text",
            "success":  function (data) {
                            var messages = data.split("\n");
                            for (var i = 0; i < messages.length; i++) {
                                $("#messages").append("<option>" + messages[i] + "</option>");
                            }
                            //console.log(messages);
                        }
        });
        
        // get accounts
        $.post("twitter-services.php", 
            JSON.stringify({
                "method": 8
            }),
            function (data) {
                for (var i = 0; i < data.accounts.length; i++) {
                    $("#accounts").append("<option>" + data.accounts[i] + "</option>");
                }
            }
        );
        
        // get live number of rows every 10 seconds
        setInterval (numRows, 10000);
    
        $("#start-stream").click(startStream);
        $("#stop-stream").click(stopStream);
        $("#fetch-tweets").click(fetchTweets).trigger("click");
        $("#send-tweet").click(sendTweet);
        $("#delete-tweet").click(deleteTweets);
        
        var tableResize = function () {
            $("#table-div").height($(window).height() - ($("#first-p").height() + $("#first-p").position().top) - 20);
        };
        
        tableResize();
        $(window).resize(tableResize);
        
    </script>
</body>
</html>
