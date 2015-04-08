<?php

/**
 * replaces time,random,etc.
 */
class TweetTextReplacer
{

    /**
     *
     * @var TweetTextReplacer
     */
    private static $_instance;

    /**
     * Singleton pattern
     *
     * @return TweetTextReplacer
     * @see http://www.doyouphp.jp/phpdp/phpdp_02-1-2_singleton.shtml
     */
    public static function getInstance()
    {
        if (! isset(self::$_instance)) {
            self::$_instance = new TweetTextReplacer();
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
     * replaces time,random,etc.
     *
     * @param string $src            
     * @return string
     */
    public function replace($src)
    {
        $src = str_replace('{hour}', intval(date('H')), $src);
        $src = str_replace('{minute}', intval(date('i')), $src);
        $src = str_replace('{second}', intval(date('s')), $src);
        
        $src = str_replace('{rand}', rand(0, 100), $src);
        
        $src = preg_replace_callback("/\{rand(\d+)\-(\d+)\}/", function ($matches) {
            return rand($matches[1], $matches[2]);
        }, $src);
        
        $src = preg_replace_callback("/\{hour_to_(\d+)\}/", function ($matches) {
            $h = intval($matches[1]);
            $h_now = intval(date('H'));
            $h_diff = $h_now - $h;
            if ($h_diff < 0) {
                $h_diff = 24 - $h_now + $h;
            }
            return $h_diff;
        }, $src);
        
        return $src;
    }
}