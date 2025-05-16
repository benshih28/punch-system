# Punch-System

**Tech Stack:** React × Laravel × WebSocket × (Optional) Vue 3  

本檔僅說明 **專案結構** 與 **目錄用途**；各子系統的安裝與啟動流程，請至對應資料夾內的 `README.md` 查看。

---

## 目錄結構

```text
punch-system/
├─ backendapi/                 # Laravel 11 (API-only) 
│  ├─ storage/api-docs/        # Swagger JSON (api-docs.json)
│  └─ README.md
├─ frontend/                   # React 19 SPA 
│  └─ README.md
├─ frontend-vue3/              # Vue 3  ★
│  └─ README.md
├─ websocket-server/           # Node.js WebSocket 服務 
│  └─ README.md
└─ README.md                   
```

## 📂 目錄說明

### `backendapi/`
Laravel API 專案，負責處理打卡系統的後端邏輯，包括：
- 員工帳號與身份驗證（JWT）
- 打卡管理（上班、下班、補登打卡、請假等）
- API 權限管理（Spatie Laravel Permission）
- Swagger JSON 產生後存放於 `storage/api-docs/api-docs.json`

### `frontend/`
React 前端專案，負責使用者介面開發，包括：
- 員工打卡操作
- 管理員後台（請假審核、補登打卡審核等）
- API 介接（使用後端 Laravel JWT Token API）

### `frontend-vue3/`（可選）
Vue 3 + Vite 前端專案，提供與 React 相同功能的替代 UI。

### `websocket-server/`
Node.js（socket.io）即時推播服務：
- 打卡成功／審核結果等通知
- 與 `backendapi/` 透過 Redis 或 HTTP 授權串接

### `README.md`
專案說明文件（目前這份），僅概述結構與各目錄用途。

---
https://login.live.com/login.srf?wa=wsignin1%2E0&rpsnv=175&ct=1747375117&rver=7%2E5%2E2146%2E0&wp=MBI%5FSSL&wreply=https%3A%2F%2Fonedrive%2Elive%2Ecom%2F%5Fforms%2Fdefault%2Easpx%3Fapr%3D1&lc=1028&id=250206&guests=1&wsucxt=1&cobrandid=11bd8083%2D87e0%2D41b5%2Dbb78%2D0bc43c8a8e8a&aadredir=1
