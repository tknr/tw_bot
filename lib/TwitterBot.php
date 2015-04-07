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
     * @var Ambigous <\Abraham\TwitterOAuth\array, \Abraham\TwitterOAuth\object>
     */
    private $followers;

    /**
     *
     * @var Ambigous <\Abraham\TwitterOAuth\array, \Abraham\TwitterOAuth\object>
     */
    private $friends;

    /**
     *
     * @var array
     */
    private $screen_name_array;

    /**
     *
     * @var boolean
     */
    private $is_verifyed;

    /**
     *
     * @param string $consumer_key            
     * @param string $consumer_secret            
     * @param string $access_token            
     * @param string $access_secret            
     */
    function __construct($consumer_key, $consumer_secret, $access_token, $access_secret)
    {
        srand(time());
        $this->tw = new TwitterOAuth($consumer_key, $consumer_secret, $access_token, $access_secret);
        $this->logger = Logger::getLogger('default');
        $this->is_verifyed = false;
        $this->init();
    }

    /**
     */
    private function init()
    {
        $this->is_verifyed = $this->verifyAccount();
        if (! $this->is_verifyed) {
            return;
        }
        $this->followers = $this->getFollowers();
        $this->friends = $this->getFriends();
        $this->screen_name_array = $this->getScreenNameArray($this->friends);
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
        if (! $this->isVerifyed()) {
            return false;
        }
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
        if (! $this->isVerifyed()) {
            return false;
        }
        $friends = $this->tw->get('friends/ids', array(
            'cursor' => - 1
        ));
        $this->logger->trace('friends:' . count($friends->ids));
        return $friends;
    }

    /**
     *
     * @param number $max_error_count            
     * @return boolean|number
     * @see Twitter API v1.1 で自動フォロー返し機能を実装する : プログラミング for ツイッタラー http://twitterer.blog.jp/archives/1482724.html
     */
    public function autoFollow($max_error_count = 5)
    {
        if (! $this->isVerifyed()) {
            return false;
        }
        $count_error = 0;
        $count_follow = 0;
        foreach ($this->followers->ids as $index => $id) {
            if (empty($this->friends->ids) or ! in_array($id, $this->friends->ids)) {
                $followed = $this->tw->post('friendships/create', array(
                    'user_id' => $id
                ));
                if (isset($followed->errors)) {
                    $this->logger->error($followed);
                    if ($followed->errors[0]->code == 161) {
                        return $count_follow;
                    }
                    $count_error ++;
                    if ($count_error >= $max_error_count) {
                        break;
                    }
                    continue;
                }
                $count_follow ++;
            }
        }
        return $count_follow;
    }

    /**
     *
     * @return boolean
     */
    public function replyMension()
    {
        if (! $this->isVerifyed()) {
            return false;
        }
        $_option = array(
            'count' => 1
        );
        $last_mension_id = TweetTextReader::getInstance()->getLastMensionId(FILE_LAST_MENSION_ID);
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
            $text = TweetTextReader::getInstance()->getReplyPattern(JSON_REPLY_PATTERN, $mension, $this->screen_name_array);
            $this->logger->trace($text);
            if ($text == null) {
                $text = TweetTextReader::getInstance()->getReplyRandomLine(FILE_REPLY_RANDOM, $mension, $this->screen_name_array);
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
            if (TweetTextReader::getInstance()->saveLastMensionId(FILE_LAST_MENSION_ID, $last_mension_id) === FALSE) {
                $this->logger->error('saveLastMensionId failed:' . $last_mension_id);
                return false;
            }
            return true;
        }
        return true;
    }

    /**
     *
     * @return boolean
     */
    public function postRandom()
    {
        if (! $this->isVerifyed()) {
            return false;
        }
        $text = TweetTextReader::getInstance()->getPostRandomLine(FILE_POST_RANDOM, $this->screen_name_array);
        
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
     * @return Ambigous <multitype:string , multitype:NULL >
     */
    private function getScreenNameArray()
    {
        if (! $this->isVerifyed()) {
            return false;
        }
        $screen_name_array = array();
        {
            $screen_name_count = 0;
            if (! empty($this->followers->ids)) {
                
                $rand_user_ids = array_rand($this->followers->ids, count($this->followers->ids));
                
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

    /**
     *
     * @return boolean
     */
    public function isVerifyed()
    {
        return $this->is_verifyed;
    }
}