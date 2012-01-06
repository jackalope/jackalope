#!/bin/bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

git submodule update --init --recursive

mysql -e 'create database IF NOT EXISTS phpcr_tests;' -u root

pyrus install phpunit/DBUnit

php $DIR/generate_doctrine_dbal_fixture.php

php $DIR/vendor/vendors_doctrine_dbal.php
