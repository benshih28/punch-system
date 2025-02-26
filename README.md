laravel clone下來後要在專案的bash
```bash
composer install

```

生成 APP_KEY

```bash
php artisan key:generate

```

專案使用 JWT 驗證
```bash
php artisan jwt:secret

```

執行資料庫遷移
```bash
php artisan migrate

```

確認沒問題就照版本控制筆記的作法開新分支
