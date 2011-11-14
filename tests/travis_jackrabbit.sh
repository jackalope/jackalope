#!/bin/bash

git submodule update --init --recursive
wget http://s3-eu-west-1.amazonaws.com/patched-jackrabbit/jackrabbit-standalone-2.3.1-jackalope-SNAPSHOT.jar
java -jar jackrabbit-standalone-2.3.1-jackalope-SNAPSHOT.jar&

echo "Waiting until Jackrabbit is ready on port 8080"
while [[ -z `curl -s 'http://localhost:8080' ` ]]
do
        echo -n "."
        sleep 2s
done

echo "Jackrabbit is up"
