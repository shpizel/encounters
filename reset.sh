#!/bin/sh

DIR=$(dirname $0)
sudo rm -fr $DIR/app/cache $DIR/app/logs
sudo php $DIR/app/console cache:warmup
php $DIR/app/console assetic:dump
sudo chmod -R 777 $DIR/app/cache $DIR/app/logs
