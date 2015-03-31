<?php
use Abraham\TwitterOAuth\TwitterOAuth;

class TwitterBot
{

    /**
     *
     * @var TwitterOAuth
     */
    private $tw;

    /**
     *
     * @var Logger
     */
    private $logger;

    /**
     *
     * @var boolean
     */
    private $autoFollowBack;

    /**
     *
     * @param string $consumer_key            
     * @param string $consumer_secret            
     * @param string $access_token            
     * @param string $access_secret            
     * @param boolean $autoFollowBack            
     */
    function __construct($consumer_key, $consumer_secret, $access_token, $access_secret, $autoFollowBack = true)
    {
        $this->tw = new TwitterOAuth($consumer_key, $consumer_secret, $access_token, $access_secret);
        $this->autoFollowBack = $autoFollowBack;
        $this->logger = Logger::getLogger('default');
    }

    /**
     *
     * @return boolean
     */
    public function exec()
    {
        if (! $this->verifyAccount()) {
            return false;
        }
        
        $followers = $this->getFollowers();
        $friends = $this->getFriends();
        $screen_name_array = $this->getScreenNameArray($friends);
        if ($this->autoFollowBack) {
            $this->autoFollow($followers, $friends);
        }
        if ($this->replyMension($screen_name_array)) {
            return true;
        }
        if ($this->postRandom($friends, $screen_name_array)) {
            return true;
        }
        return false;
    }

    /**
     *
     * @return boolean
     */
    private function verifyAccount()
    {
        $account = $this->tw->get('account/verify_credentials');
        if (empty($account)) {
            $this->logger->error('account not found');
            return false;
        }
        if (isset($account->errors)) {
            $this->logger->error($account);
            return false;
        }
        return true;
    }

    /**
     *
     * @return Ambigous <\Abraham\TwitterOAuth\array, \Abraham\TwitterOAuth\object>
     */
    private function getFollowers()
    {
        $followers = $this->tw->get('followers/ids', array(
            'cursor' => - 1
        ));
        $this->logger->trace('followers:' . count($followers->ids));
        return $followers;
    }

    /**
     *
     * @return Ambigous <\Abraham\TwitterOAuth\array, \Abraham\TwitterOAuth\object>
     */
    private function getFriends()
    {
        $friends = $this->tw->get('friends/ids', array(
            'cursor' => - 1
        ));
        $this->logger->trace('friends:' . count($friends->ids));
        return $friends;
    }

    /**
     *
     * @param
     *            Ambigous <\Abraham\TwitterOAuth\array, \Abraham\TwitterOAuth\object> $followers
     * @param
     *            Ambigous <\Abraham\TwitterOAuth\array, \Abraham\TwitterOAuth\object> $friends
     */
    private function autoFollow($followers, $friends, $max_error_count = 5)
    {
        $count_error = 0;
        // Twitter API v1.1 で自動フォロー返し機能を実装する : プログラミング for ツイッタラー http://twitterer.blog.jp/archives/1482724.html
        foreach ($followers->ids as $index => $id) {
            if (empty($friends->ids) or ! in_array($id, $friends->ids)) {
                $followed = $this->tw->post('friendships/create', array(
                    'user_id' => $id
                ));
                if (isset($followed->errors)) {
                    $this->logger->error($followed);
                    if ($followed->errors[0]->code == 161) {
                        return;
                    }
                }
                $count_error ++;
                if ($count_error >= $max_error_count) {
                    break;
                }
            }
        }
    }

    /**
     *
     * @param array $screen_name_array            
     * @return boolean
     */
    private function replyMension($screen_name_array)
    {
        $_option = array(
            'count' => 1
        );
        $last_mension_id = TweetTextReader::getLastMensionId(FILE_LAST_MENSION_ID);
        if ($last_mension_id != null) {
            $_option['since_id'] = $last_mension_id;
        }
        $this->logger->trace($_option);
        $mentions = $this->tw->get('statuses/mentions_timeline', $_option);
        if (isset($mentions->errors)) {
            $this->logger->error($mentions);
            return false;
        }
        if (isset($mentions[0])) {
            $mension = $mentions[0];
            $this->logger->trace($mension);
            $text = TweetTextReader::getReplyPattern(JSON_REPLY_PATTERN, $mension, $screen_name_array);
            $this->logger->trace($text);
            if ($text == null) {
                $text = TweetTextReader::getReplyRandomLine(FILE_REPLY_RANDOM, $mension, $screen_name_array);
                $this->logger->trace($text);
                if (is_null($text)) {
                    $this->logger->error('text is blank');
                    return false;
                }
            }
            
            $last_mension_id = $mension->id;
            $statuses = $this->tw->post('statuses/update', array(
                'status' => $text,
                'in_reply_to_status_id' => $last_mension_id
            ));
            if (isset($statuses->errors)) {
                $this->logger->error($statuses);
                return false;
            }
            $this->logger->trace('id_str:' . $statuses->id_str);
            if (TweetTextReader::saveLastMensionId(FILE_LAST_MENSION_ID, $last_mension_id) === FALSE) {
                $this->logger->error('saveLastMensionId failed:' . $last_mension_id);
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     *
     * @param
     *            Ambigous <\Abraham\TwitterOAuth\array, \Abraham\TwitterOAuth\object> $friends
     * @param array $screen_name_array            
     * @return boolean
     */
    private function postRandom($friends, $screen_name_array)
    {
        $text = TweetTextReader::getPostRandomLine(FILE_POST_RANDOM, $screen_name_array);
        
        $this->logger->trace($text);
        if (is_null($text)) {
            $this->logger->error('text is blank');
            return false;
        }
        $statuses = $this->tw->post('statuses/update', [
            'status' => $text
        ]);
        if (isset($statuses->errors)) {
            $this->logger->error($statuses);
            return false;
        }
        $this->logger->trace('id_str:' . $statuses->id_str);
        return true;
    }

    /**
     *
     * @param
     *            Ambigous <\Abraham\TwitterOAuth\array, \Abraham\TwitterOAuth\object> $friends
     * @return Ambigous <multitype:string , multitype:NULL >
     */
    private function getScreenNameArray($friends)
    {
        $screen_name_array = array();
        {
            $screen_name_count = 0;
            if (! empty($friends->ids)) {

                $rand_user_ids = array_rand($friends->ids, count($friends->ids));
                srand(time());
                shuffle($rand_user_ids);
                // $logger->trace($rand_user_ids);
                foreach ($rand_user_ids as $index => $user_id) {
                    $user_info = $this->tw->get('users/show', [
                        'user_id' => $user_id
                    ]);
                    if (isset($user_info->errors)) {
                        $this->logger->error($user_info);
                        continue;
                    }
                    
                    // $logger->trace($user_info);
                    if (is_array($user_info)) {
                        $user_info = $user_info[0];
                    }
                    $screen_name_array[] = $user_info->screen_name;
                    
                    $screen_name_count ++;
                    if ($screen_name_count >= 2) {
                        break;
                    }
                }
            }
        }
        $this->logger->trace($screen_name_array);
        if (count($screen_name_array) < 1) {
            $this->logger->warn('cannot get screen_name array');
            $screen_name_array = array(
                '',
                ''
            );
        }
        return $screen_name_array;
    }
}