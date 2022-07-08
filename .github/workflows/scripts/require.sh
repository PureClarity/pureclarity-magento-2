#!/bin/bash

# Changes the composer require to use the current commit ref, rather than the last released version
cd $MAGENTO_ROOT
composer require $COMPOSER_NAME:dev-$GITHUB_SHA --no-update --no-interaction