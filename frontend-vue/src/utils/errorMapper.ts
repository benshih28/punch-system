export const messageMap = {
  "認證失敗":                 "AUTH_FAILED",
  "伺服器錯誤，無法生成token": "TOKEN_CREATE_ERROR",
  "token已過期，但登出成功":   "TOKEN_EXPIRED_LOGOUT",
  "未提供token":             "TOKEN_MISSING",
  "登出失敗":               "LOGOUT_FAILED",
  "Email 未註冊":            "EMAIL_NOT_FOUND",
  "驗證失敗":               "REGISTER_VALIDATION_FAIL",
  "未授權，請提供有效的 Bearer Token": "UNAUTHORIZED",
  "部門已新增":             "DEPT_CREATED",
  "請求驗證錯誤":           "DEPT_VALIDATION_ERROR",
  "部門刪除成功":           "DEPT_DELETED",
  "找不到部門":             "DEPT_NOT_FOUND",
  "部門更新成功":           "DEPT_UPDATED",
  "沒有權限存取":           "FORBIDDEN",
  "伺服器錯誤":             "SERVER_ERROR",
} as const;

export type AppErrorCode = typeof messageMap[keyof typeof messageMap];

export const mapMessageToCode = (msg?: string) =>
  (msg && messageMap[msg as keyof typeof messageMap]) as AppErrorCode | undefined;
