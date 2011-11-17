#!/bin/bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd $DIR

JAR=jackrabbit-standalone-2.3.1-jackalope-SNAPSHOT.jar

if [ ! -f "$DIR/$JAR" ]; then
    wget http://s3-eu-west-1.amazonaws.com/patched-jackrabbit/$JAR
fi

java -jar $DIR/$JAR&

echo "Waiting until Jackrabbit is ready on port 8080"
while [[ -z `curl -s 'http://localhost:8080' ` ]]
do
        echo -n "."
        sleep 2s
done

echo "Jackrabbit is up"
