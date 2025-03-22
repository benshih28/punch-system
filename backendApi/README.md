# Laravel api_only

XAMPP的php.ini拿掉註解
```bash
extension=sodium
```

 設定 .env 檔案
```bash
cp .env.example .env
```

laravel clone下來後要在專案的bash
```bash
composer install

```

---

## 已安裝過composer install
```bash
php artisan serve
npm run queue      # 啟動 queue（背景）
npm run stop-queue # 停掉 queue（安全）
```



---

composer install已合併以下指令
1.生成 APP_KEY
```bash
php artisan key:generate
```
2.專案使用 JWT 驗證
```bash
php artisan jwt:secret
```
3.執行資料庫遷移
```bash
php artisan migrate
```
4.生成 人資部&人資主管  權限表 admin(有全權限)
```bash
php artisan make:seeder --class=DepartmentAndPositionSeeder
php artisan make:seeder --class=PermissionsSeeder
php artisan make:seeder --class=RolesSeeder

```

5.開啟自動監聽
```bash
npm run queue 

```
確認沒問題就照版本控制筆記的作法開新分支