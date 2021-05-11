#!/bin/bash

php artisan cache:clear
php artisan config:cache
php artisan route:cache

php artisan opcache:clear
php artisan modelCache:clear
