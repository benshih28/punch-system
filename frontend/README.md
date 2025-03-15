# React + Vite


執行
```bash
npm run dev 
```
或
```bash
npm i 
```
會自動下載套件並執行專案
 - react-router-dom
 - axios 
 - mui mui/x Date tanStack-table 
 - jotai-immer 
 - react-hook-form


 # 安裝 Laravel Queue Worker
 (使用 Laravel Queue 來處理 寄送郵件)
1. 請先全域安裝 Forever：
npm install -g forever

2. 啟動 Queue Worker
forever start -c "php" artisan queue:work --tries=3 --timeout=0 --sleep=5