# tw_bot
tweet bot

## usage

### configure
#### twitter authentication
file : config/config.php  

set these value:  

CONSUMER_KEY  
CONSUMER_SECRET  
ACCESS_TOKEN  
ACCESS_SECRET  

ref :  
[Twitter Application Management](https://apps.twitter.com/)
[Application-only authentication | Twitter Developers](https://dev.twitter.com/node/254)

#### text data for post
folder : data/  

reply_pattern.json : reply words for mension which matches some specific word regex  
reply_random.txt : reply setting for mension ,if any specific word isn't in the mension  
post_random.txt : post setting if there aren't any new mention 

##### regex
{reply_to} : screen_name of mention ( in reply_pattern.json , reply_random.txt )
{id0} : screen_name at random in follower
{id1} : screen_name at random in follower
{hour} : hour ( of 24-hour clock )
{minute} : minute
{second} : second
{rand} : random number in 0..100
{rand<numeric x>-<numeric y>} random number in x..y ex. {rand1..108}

#### logging
file : config/log4php.xml  
see the reference :  
[Apache log4php - Loggers - Apache log4php](https://logging.apache.org/log4php/docs/loggers.html)

### execute
bash bot.sh

### update/upgrade library
execute  
bash install.sh  
with composer.  
ref : [Composer](https://getcomposer.org/)

## references
[EasyBotter - プログラミングができなくても作れるTwitter botの作り方](http://pha22.net/twitterbot/)  
[さよならロンリネス！彼女がいないバレンタインをITの力で解決する方法 | EXP – クリエイティブな事をはじめた(い)全ての人達へ](http://wp.me/p4kFZb-24)  
[PHP Live Regex](http://www.phpliveregex.com/)  
[TwitterOAuth PHP Library for the Twitter REST API](https://twitteroauth.com/)  
[50 行でできる簡単 & 安全な自動フォロー返し機能の実装方法 : プログラミング for ツイッタラー](http://twitterer.blog.jp/archives/1486582.html)
[PHP - TwitterOAuthの正しい使い方 - Qiita](http://qiita.com/rana_kualu/items/357a031c0453a3538ad3)

## todo
change config.php to config.ini and read with parse_ini_file()  
