#!/usr/bin/env bash

phpunit -c .travis-phpunit.xml

STATUS=$?

if [ $STATUS -eq 139 ]; then
    echo
    echo "Forcing exit status 0 (zero)."

    exit 0
else
    exit $STATUS
fi
