<?php
/*
 * settings
 */
{
    /*
     * twitter account setting
     */
    define('CONSUMER_KEY', 'XXXXXXXX'); // コンシューマキー
    define('CONSUMER_SECRET', 'XXXXXXXX'); // コンシューマシークレット
    define('ACCESS_TOKEN', 'XXXXXXXX-XXXXXXXX'); // アクセストークン
    define('ACCESS_SECRET', 'XXXXXXXX'); // アクセスシークレット
}
{
    /*
     * file setting
     */
    define('FILE_LAST_MENSION_ID', __DIR__ . '/../log/last_mension_id.txt');
    define('FILE_REPLY_RANDOM', __DIR__ . '/../data/reply_random.txt');
    define('FILE_POST_RANDOM', __DIR__ . '/../data/post_random.txt');
    define('JSON_REPLY_PATTERN', __DIR__ . '/../data/reply_pattern.json');
}