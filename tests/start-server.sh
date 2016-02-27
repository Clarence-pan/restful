#!/bin/sh

cd `dirname $(realpath $0)`
php -S localhost:8888
