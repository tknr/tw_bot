#!/bin/sh
mkdir -p log
chmod -R 777 log

rm -f composer.lock
rm -Rf output
rm -Rf vendor

php -r "readfile('https://getcomposer.org/installer');" | php
php composer.phar install
find ./ -name .svn | xargs rm -Rf
#find ./ -name .git | xargs rm -Rf
find ./ -name .hg | xargs rm -Rf

