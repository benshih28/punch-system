```perl
punch-system/
│── backendapi/  # Laravel 後端 API
│── frontend/    # React 前端
│── docs/        # Swagger API 文件或其他文檔
│── scripts/     # 部署或自動化腳本
│── README.md
```


## 📂 目錄說明

### `backendapi/`
Laravel API 專案，負責處理打卡系統的後端邏輯，包括：
- 員工帳號與身份驗證
- 打卡管理（上班、下班、補登打卡、請假等）
- API 權限管理（使用 Spatie Laravel Permission）

### `frontend/`
React 前端專案，負責使用者介面開發，包括：
- 員工打卡操作
- 管理員後台（請假審核、補登打卡審核等）
- API 介接（使用後端 Laravel JWTtoken API）

### `docs/`
存放 Swagger API 文件或其他開發文檔，如：
- `swagger.json` / `swagger.yaml`（API 文件）
- `API_GUIDE.md`（API 使用指南）
- `DATABASE_SCHEMA.md`（資料庫設計）

### `scripts/`
存放自動化腳本，如：
- `deploy.sh`（部署腳本）
- `backup.sh`（資料庫備份腳本）

### `README.md`
專案說明文件，提供專案簡介、安裝步驟、開發指南等資訊。

---
