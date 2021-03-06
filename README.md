Phone Halo Twitter
==================


INSTALL SERVER
--------------
  - You may put this folder anywhere it is convenient to you.
    - Make sure this folder and all of its contents are readable and writable to all users.
    	- On the Finder window, right click on this folder and choose `Get Info`.
        - On the sharings permission, make sure all users have read and write access.
        - For every user, select `Apply to enclosed items` on the settings drop down below it.
  - Download [MAMP (free, non-pro version)](http://www.mamp.info/en/index.html)
  - Install MAMP.
  - Run MAMP and click on the `Preferences` button and select the `Apache` tab.
  - Select this folder as the Document Root.
  - Start the servers (click on `Start Servers`).
  - Open Terminal and go to this folder. Type the following to run the setup:
	
```        
php setup.php
```

SETUP TWITTER
-------------

  - Obtain the Consumer Key, Consumer Secret, User Token, and User Secret of your Twitter account.
	- Go to [Twitter Dev](https://dev.twitter.com) and sign in
    - Create an application. Make sure the `Application Type` access is __Read and Write__ (stream and sending tweets, respectively). 
    - The wizard will automatically generate the four keys mentioned above.
  - Fill out the file `twitter/streaming-twitter-account.txt` with the corresponding keys.
  - Fill out the file `twitter/twitter-accounts.txt` with the corresponding keys. These accounts are used to reply to tweets
    - Can have more than one account. Just copy and paste the 5 keys and fill out the necessary information
    - `account_name` is the name of the account to be shown on the web app.


RUN THE WEB APP
---------------
- Just type the following on a web browser

```
localhost:8888
```


USING THE WEB APP
-----------------
* Streaming (fetching tweets)
    - Initially, streaming tweets are turned off and the database is empty.
    - To start streaming, click on the `Start Stream` button.
    - __NOTE:__ Streaming continues in the server even when you exit the app or close the browser.

* Fetch Tweets
    - Tweets will be displayed on the screen from latest to the earliest tweets. It fetches 10 rows at a time.
    - __NOTE:__     
    	- If streaming is ongoing, there may be tweets that will be newer to the latest tweet on the screen. 
        - If you prefer to get the latest everytime, just refresh the page and click the `Fetch Tweets` again.

* Reply to tweets
    - To reply to tweets, select any of the tweets on the screen using the checkboxes or simply clicking the table row.
    - Select which message to send to the selected tweets on the dropdown box on the right.
    - Click `Send reply to selected tweets`
    - NOTE:     
    	- Replied tweets are automatically erased in the screen and the list refreshes to the latest tweets.
    
* Deleting Tweets
    - This is used to delete false positive tweets
    - Select tweets using the checkboxes, then click on `Delete selected tweets`.
    

MISCELLANEOUS
=============


EDIT TWITTER REPLY MESSAGES
---------------------------
- Twitter reply messages are found in `twitter/twitter-messages.txt`
- (One line is one message)


EDIT SEARCH TERMS
-----------------
- Search terms are found in `twitter/twitter-search-terms.txt`
- Only contains one line. 
- Multiple search terms are separated by commas


CAVEATS
=======

* If you keep the streaming active, you may run out of disk space. You may also be banned from Twitter for some time.
* Replying to a tweet only removes it on the screen and NOT on the database. 
    - This is to check for duplicate users
    - So, eventually, it needs to remove the "old" data in the database.... NOT YET IMPLEMENTED. SUBJECT FOR DISCUSSION.
