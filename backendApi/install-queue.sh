#!/bin/bash

# 安裝 forever（如果尚未安裝）
if ! command -v forever &> /dev/null
then
    echo "Installing forever..."
    npm install -g forever
fi

# 啟動 Laravel Queue Worker
echo "Starting Laravel Queue Worker..."
forever start -c "php" artisan queue:work --tries=3 --timeout=0 --sleep=5
