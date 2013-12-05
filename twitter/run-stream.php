<?php
require 'OAuth/tmhOAuth.php';
require 'OAuth/tmhUtilities.php';

class TwitterStream {
    public $db;

    function user_exists ($json) {
        $found = FALSE;
        $result = $this->db->query("SELECT * FROM tweet where id = " . $json->user->id_str);

        if (!$result->num_rows) {
            for ($i = 0; $i < count($json->entities->user_mentions); $i++) {
                $result = $this->db->query("SELECT * FROM tweet where id = " . $json->entities->user_mentions[$i]->id_str);
                if ($result->num_rows) {
                    return TRUE;
                }
            }
            return FALSE;
        } else {
            return TRUE;
        }
    }

    function add_user ($json) {
        $value = preg_replace( '/[^[:print:]]/', '',$json->text);
        $value = preg_replace('/[^a-zA-Z0-9_ %\[\]\.\(\)%&@#-]/s', '', $value);
        $value = str_replace('"', "'", $value);
        $value = $this->db->real_escape_string($value);
        $username = $this->db->real_escape_string($json->user->screen_name);
        $timestamp = new DateTime($json->created_at);
        $timestamp = $timestamp->getTimestamp(); 
        $this->db->query("INSERT INTO tweet (timestamp, id, username, tweetID, tweet) VALUES (".$timestamp.", '".$json->user->id_str."', '".$username."', '".$json->id_str."', '".$value."')"); 
    }
    
    function processData ($json) {
        if (!$this->user_exists($json)) {
            $this->add_user($json);
        }
    }

    function my_streaming_callback($data, $length, $metrics) {
        $continue = true;

        $json = json_decode($data); 

        if (isset($json->text)) {

            $this->processData($json);

            $text = "date: $json->created_at\n"."user: ".$json->user->id_str." : ".$json->user->screen_name."\n";
            
            /*
                user_mentions:
                each array is an object: screen_name, id_str
            */
            $mentions = json_encode($json->entities->user_mentions);
            $text .= "mentions: ".$mentions."\n";

            if ($json->retweeted) {
                $retweeted = 1;
            } else {
                $retweeted = 0;
            }

            $text .= "retweeted: $retweeted\n";
            $text .= "retweet count: ".$json->retweet_count."\n";
            $text .= "favorited: ".$json->favorite_count."\n";
            $text .= "replied to: ".$json->in_reply_to_user_id_str." : ".$json->in_reply_to_screen_name."\n";
            $text .= "message: $json->text\n\n";

            //file_put_contents("raw-tweets.log", $data."\n", FILE_APPEND);

        } else {
            //echo "RAW: ".$data."\n\n"; 
            $text = "RAW: ".$data."\n\n";
        }

        $continue = file_exists(dirname(__FILE__) . '/STOP');
        if ($continue) {
            unlink(dirname(__FILE__) . '/STOP');
        }

		return $continue;
    }

    
    public function start () {
        $this->db = new mysqli('localhost:8889', 'root', 'root', 'PHTwitter');
        
        $twitterOAuth = file_get_contents("twitter-account.txt");
        $twitterOAuthArray = explode("\n", $twitterOAuth);
        foreach ($twitterOAuthArray as $index => $item) {
            $twitterOAuthArray[$index] = end(explode(":", $item));
        }

        $tmhOAuth = new tmhOAuth(array(
                                        'consumer_key'    => $twitterOAuthArray[0],
                                        'consumer_secret' => $twitterOAuthArray[1],
                                        'user_token'      => $twitterOAuthArray[2],
                                        'user_secret'     => $twitterOAuthArray[3],
                                ));

        $method = 'https://stream.twitter.com/1.1/statuses/filter.json';
        
        $searchTerms = file_get_contents("twitter-search-terms.txt");
        $searchTerms = array_shift(explode("\n", $searchTerms));
        
		$params = array('track' => $searchTerms);
        
        $tmhOAuth->streaming_request('POST', $method, $params, array($this, 'my_streaming_callback'));
        
        // output any response we get back AFTER the Stream has stopped -- or it errors
        tmhUtilities::pr($tmhOAuth);

        $this->db->close();
    }
}


$x = new TwitterStream();
$x->start();

?>
