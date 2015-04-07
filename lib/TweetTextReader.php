<?php

/**
 */
class TweetTextReader
{

    /**
     *
     * @var TweetTextReader
     */
    private static $_instance;

    /**
     * Singleton pattern
     *
     * @return TweetTextReader
     * @see http://www.doyouphp.jp/phpdp/phpdp_02-1-2_singleton.shtml
     */
    public static function getInstance()
    {
        if (! isset(self::$_instance)) {
            self::$_instance = new TweetTextReader();
        }
        
        return self::$_instance;
    }

    /**
     */
    function __construct()
    {
        srand(time());
    }

    /**
     *
     * @param string $src            
     * @param array $screen_name_array            
     * @param object $mension            
     * @return string
     */
    private function replace($src, $screen_name_array, $mension = null)
    {
        if ($mension != null) {
            $src = str_replace('{reply_to}', '@' . $mension->user->screen_name, $src);
        } else {
            $src = str_replace('{reply_to}', '@' . $screen_name_array[0], $src);
        }
        foreach ($screen_name_array as $index => $screen_name) {
            $src = str_replace('{id' . $index . '}', '@' . $screen_name, $src);
        }
        
        $src = str_replace('{hour}', intval(date('H')), $src);
        $src = str_replace('{minute}', intval(date('i')), $src);
        $src = str_replace('{second}', intval(date('s')), $src);
        
        $src = str_replace('{rand}', rand(0, 100), $src);
        $src = preg_replace_callback("/\{rand(\d+)\-(\d+)\}/", function ($matches) {
            return rand($matches[1], $matches[2]);
        }, $src);
        return $src;
    }

    /**
     *
     * @param string $filename            
     * @return NULL|string
     * @see ファイル内行のランダム表示 http://lcl.web5.jp/prog/fileline.php
     */
    private function getRandomLineFromTextFile($filename)
    {
        if (! file_exists($filename)) {
            return null;
        }
        $text_array = file($filename);
        $text_array = array_values(array_filter($text_array));
        
        $text = $text_array[array_rand($text_array)];
        if (empty($text)) {
            return null;
        }
        return $text;
    }

    /**
     *
     * @param string $filename            
     * @return NULL | string
     */
    public function getLastMensionId($filename)
    {
        if (! file_exists($filename)) {
            return null;
        }
        $mension_id = file_get_contents($filename);
        return trim($mension_id);
    }

    /**
     *
     * @param string $filename            
     * @param string $mensionId            
     * @return Ambigous <boolean, number>
     */
    public function saveLastMensionId($filename, $mensionId)
    {
        return file_put_contents($filename, $mensionId);
    }

    /**
     *
     * @param string $filename            
     * @param array $screen_name_array            
     * @return string | NULL
     */
    public function getPostRandomLine($filename, $screen_name_array)
    {
        $text = $this->getRandomLineFromTextFile($filename);
        if ($text == null) {
            return null;
        }
        return $this->replace($text, $screen_name_array);
    }

    /**
     *
     * @param string $filename            
     * @param object $mension            
     * @param array $screen_name_array            
     * @return string | NULL
     */
    public function getReplyRandomLine($filename, $mension, $screen_name_array)
    {
        $text = $this->getRandomLineFromTextFile($filename);
        if ($text == null) {
            return null;
        }
        return '@' . $mension->user->screen_name . ' ' . self::replace($text, $screen_name_array, $mension);
    }

    /**
     *
     * @param string $json_filename            
     * @param object $mension            
     * @param array $screen_name_array            
     * @return NULL|string
     */
    public function getReplyPattern($json_filename, $mension, $screen_name_array)
    {
        if (! file_exists($json_filename)) {
            return null;
        }
        $json = json_decode($json_filename, true);
        $logger = Logger::getLogger('default');
        $logger->trace($json);
        foreach ($json['reply_pattern'] as $reply_pattern) {
            if (preg_match('/' . $reply_pattern['regex'] . '/', $mension->text) === 1) {
                $reply_array = $reply_pattern['reply'];
                $reply_array = array_values(array_filter($reply_array));
                
                $text = $reply_array[array_rand($reply_array)];
                return $this->replace($text, $screen_name_array);
            }
        }
        return null;
    }
}