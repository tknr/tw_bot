<?php
ini_set('display_errors', 1);
ini_set('timezone', 'Asia/Tokyo');
error_reporting(E_ALL);
setlocale(LC_ALL, 'ja_JP.UTF-8');

require_once (__DIR__ . '/config/config.php');
require_once (__DIR__ . '/vendor/autoload.php');
require_once (__DIR__ . '/lib/TweetTextReader.php');
require_once (__DIR__ . '/lib/TwitterBot.php');

Logger::configure(__DIR__ . '/config/log4php.xml');

$bot = new TwitterBot(CONSUMER_KEY, CONSUMER_SECRET, ACCESS_TOKEN, ACCESS_SECRET);
if (! $bot->isVerifyed()) {
    exit(1);
}
if ($bot->autoFollow() === false) {
    exit(1);
}
if (! $bot->replyMension()) {
    exit(1);
} elseif (! $bot->postRandom()) {
    exit(1);
}
exit(0);