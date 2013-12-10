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
</head>
<body>
    <div class="container">
        <h1>Phone Halo Twitter Server</h1>
        
        <button type="button" class="btn btn-primary hidden" disabled="disabled" id="start-stream">Start Stream</button>
        <button type="button" class="btn btn-primary hidden" disabled="disabled" id="stop-stream">Stop Stream</button>
        <button type="button" class="btn btn-primary" id="fetch-tweets">Fetch Tweets</button>

        <div class="row">
            <div class="col-md-8 table-responsive">
                <table id="main-table" class="table table-condensed table-hover">

                </table>
            </div>
            <div class="col-md-4">
                <select class="form-control" id="messages"></select>
                <select class="form-control" id="accounts"></select>

                <button type="button" class="btn btn-primary" id="send-tweet">Send reply to selected tweets</button>
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
                $("#main-table tr.selected").each(function (index, element) {
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
                $("#main-table tr.selected").each(function (index, element) {
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
                    rows[i].appendTo("#main-table");
                    $("#main-table")
                        .find("tr:last")
                        .find("input")
                        .click(function () {
                            if ($(this).is(":checked")) {
                                $(this).parents("tr").addClass("selected");
                                //console.log("checked", $(this).parents("tr"));
                            } else {
                                $(this).parents("tr").removeClass("selected");
                                //console.log("unchecked");
                            }
                        });
                }
                
                if (rows.length) {
                    $("#fetch-tweets").text("Fetch More Tweets").removeAttr("disabled");
                    start += limit;
                } else {
                    $("#fetch-tweets").text("No More Tweets").prop("disabled", true);
                }
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
                
                $("#main-table").empty();
                handleFetchedTweets = newHandleFetchedTweets;
                start = 0;
                fetchTweets();
            },
            handleDeletedTweets = function (data) {
                //console.log(data);
                start -= $("#main-table tr.selected").length;
                $("#main-table tr.selected").remove();
            };

        isStreaming();
        
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
    
        $("#start-stream").click(startStream);
        $("#stop-stream").click(stopStream);
        $("#fetch-tweets").click(fetchTweets);
        $("#send-tweet").click(sendTweet);
        $("#delete-tweet").click(deleteTweets);
    </script>
</body>
</html>
