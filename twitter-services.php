<?php
require 'twitter/OAuth/tmhOAuth.php';                                                                                    
require 'twitter/OAuth/tmhUtilities.php';

class TwitterServices {

    function startStream () {
        $descriptorspec = array(
            0 => array("pipe", "r"),  								
            1 => array("pipe", "w"),  								
            2 => array("file", "/tmp/twitter-error.txt", "a")
        );
                
        $process = proc_open("php run-stream.php &", $descriptorspec, $pipes, dirname(__FILE__)."/twitter");
        if (is_resource($process)) {
            fwrite($pipes[0], '<?php print_r($_ENV); ?>');
            fclose($pipes[0]);
            fclose($pipes[1]);
            $return_value = proc_close($process);
            $messages .= "twitter stream started";
        } else {
            $messages .= "twitter stream not started";
        }

    }

    function stopStream () {
        file_put_contents(dirname(__FILE__)."/twitter/STOP", "");
    }

    function isStreaming () {
        exec("ps aux | grep 'run-stream.php'", $output);
        if (count($output) > 1) {
            foreach($output as $value) {
                if (strpos($value, "grep") === FALSE) {
                    return TRUE;
                }
            }
        }

        return FALSE;
    }

}

////////////////////////////////////////////////////////////////////////////////

header('Content-type: application/json');

$twitter = new TwitterServices();
$p =  file_get_contents('php://input');
$post = json_decode($p);

switch ($post->method) {
    // start stream
    case 1:
        $twitter->startStream();
        echo json_encode(array(
                    "code" => 1,
                    "message" => "stream started"
                ));
        break;

    // stop stream
    case 2:
        $twitter->stopStream();
        echo json_encode(array(
                    "code" => 1,
                    "message" => "stream stopped"
                ));
        break;

    // is streaming?
    case 3:
        $result = $twitter->isStreaming();
        echo json_encode(array(
                    "code" => 1,
                    "message" => $result
                ));
        break;

    // get number of rows
    case 4:
        $db = new mysqli('localhost', 'root', 'root', 'PHTwitter');
        $result = $db->query("SELECT COUNT(*) AS rows FROM tweet");

        $arr = array();
        while ($res = $result->fetch_array(MYSQLI_ASSOC)) {
            $arr[] = $res;
        }

        echo json_encode(array(
                    "code" => 1,
                    "data" => intval($arr[0]["rows"])
                ));

        $db->close();
        break;
        
    // get tweets
    case 5:
        $db = new mysqli('localhost', 'root', 'root', 'PHTwitter');
        $q = "SELECT * FROM tweet WHERE deleted = 0 ORDER BY timestamp DESC LIMIT ".$post->start.", ".$post->limit;
        $result = $db->query($q);

        $arr = array();
        while ($res = $result->fetch_array(MYSQLI_ASSOC)) {
            $res["tweet"] = preg_replace('/[^a-zA-Z0-9_ %\[\]\.\(\)%&@#-]/s', '', $res["tweet"]);
            $arr[] = $res;
        }

        echo json_encode(array(
                    "code" => 1,
                    "query" => $q,
                    "data" => $arr
                ));
        
        $db->close();
        break;

    // send reply tweets
    case 6:
        $db = new mysqli('localhost', 'root', 'root', 'PHTwitter');
        
        $twitterOAuth = file_get_contents("twitter/twitter-account.txt");
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
        
        $method = "https://api.twitter.com/1.1/statuses/update.json";                                                
        
        for ($i = 0; $i < count($post->tweetIDs); $i++) {  
            $q = "SELECT username FROM tweet where tweetID='".$post->tweetIDs[$i]."'";
            $result = $db->query($q);

            $res = $result->fetch_array(MYSQLI_ASSOC);
            $status = "@".$res["username"]." ".$post->message; 
            
            $params = array('status' => $status,
                            'in_reply_to_status_id' => $post->tweetIDs[$i]);
        
            $tmhOAuth->request('POST', $method, $params);
            
            $db->query("UPDATE tweet SET deleted = 1 WHERE tweetID='".$post->tweetIDs[$i]."'"); 
        }
        
        echo json_encode(array(
                    "code" => 1
                ));

        $db->close();
        break;

    // delete
    case 7:
        $db = new mysqli('localhost', 'root', 'root', 'PHTwitter');
        
        for ($i = 0; $i < count($post->tweetIDs); $i++) {  
            $db->query("DELETE FROM tweet WHERE tweetID='".$post->tweetIDs[$i]."'");    
        }
        
        echo json_encode(array(
                    "code" => 1,
                    "query" => $q,
                    "message" => $arr,
                    "statuses" => $arr2

                ));
        
        $db->close();
        break;
}

?>
