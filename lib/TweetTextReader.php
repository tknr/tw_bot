<?php

/**
 */
class TweetTextReader
{

    /**
     *
     * @param string $filename
     *            @array $screen_name_array
     * @return string | NULL
     */
    public static function getPostRandomLine($filename, $screen_name_array)
    {
        if (! file_exists($filename)) {
            return null;
        }
        // @see ファイル内行のランダム表示 http://lcl.web5.jp/prog/fileline.php
        $text_array = file($filename);
        srand(time());
        shuffle($text_array);
        $text = $text_array[0];
        if (empty($text)) {
            return null;
        }
        return self::replacePostRandomLine($text, $screen_name_array);
    }

    /**
     *
     * @param string $src            
     * @return string
     */
    private static function replaceBase($src)
    {
        $src = str_replace('{hour}', intval(date('H')), $src);
        $src = str_replace('{minute}', intval(date('i')), $src);
        $src = str_replace('{second}', intval(date('s')), $src);
        
        srand(microtime() * 1000000);
        
        $src = str_replace('{rand}', rand(0, 100), $src);
        $src = preg_replace_callback("/\{rand(\d+)\-(\d+)\}/", function ($matches) {
            return rand($matches[1], $matches[2]);
        }, $src);
        return $src;
    }

    /**
     *
     * @param string $src            
     * @param array $screen_name_array            
     * @return string
     */
    private static function replacePostRandomLine($src, $screen_name_array)
    {
        foreach ($screen_name_array as $index => $screen_name) {
            $src = str_replace('{id' . $index . '}', '@' . $screen_name, $src);
        }
        $src = self::replaceBase($src);
        return $src;
    }

    /**
     *
     * @param string $filename            
     * @return NULL | string
     */
    public static function getLastMensionId($filename)
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
    public static function saveLastMensionId($filename, $mensionId)
    {
        return file_put_contents($filename, $mensionId);
    }

    /**
     *
     * @param string $filename            
     * @param object $mension            
     * @param array $screen_name_array            
     * @return string | NULL
     */
    public static function getReplyRandomLine($filename, $mension, $screen_name_array)
    {
        if (! file_exists($filename)) {
            return null;
        }
        // @see ファイル内行のランダム表示 http://lcl.web5.jp/prog/fileline.php
        $text_array = file($filename);
        srand(time());
        shuffle($text_array);
        $text = $text_array[0];
        if (empty($text)) {
            return null;
        }
        return '@' . $mension->user->screen_name . ' ' . self::replaceReplyRandomLine($text, $mension, $screen_name_array);
    }

    /**
     *
     * @param string $src            
     * @param object $mension            
     * @param array $screen_name_array            
     * @return string
     */
    private static function replaceReplyRandomLine($src, $mension, $screen_name_array)
    {
        $src = str_replace('{id0}', '@' . $mension->user->screen_name, $src);
        $src = str_replace('{id1}', '@' . $screen_name_array[0], $src);
        
        $src = self::replaceBase($src);
        return $src;
    }

    /**
     *
     * @param string $json_filename            
     * @param object $mension            
     * @param array $screen_name_array            
     * @return NULL|string
     */
    public static function getReplyPattern($json_filename, $mension, $screen_name_array)
    {
        if (! file_exists($json_filename)) {
            return null;
        }
        $json = json_decode($json_filename, true);
        $logger = Logger::getLogger('default');
        $logger->trace($json);
        foreach ($json['reply_pattern'] as $index => $reply_pattern) {
            if (preg_match('/' . $reply_pattern['regex'] . '/', $mension->text) === 1) {
                $reply_array = $reply_pattern['reply'];
                srand(time());
                shuffle($reply_array);
                return self::replacePostRandomLine($reply_array[0], $screen_name_array);
            }
        }
        return null;
    }
}