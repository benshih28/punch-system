import { Navigate } from "react-router-dom"; // 用來做頁面導向 (重定向)
import { useAtomValue } from "jotai"; // 改用 `useAtomValue`，避免不必要的 re-render
import { isAuthenticatedAtom } from "../state/authAtom"; // 改用 `isAuthenticatedAtom`，不直接讀取 `authAtom`

import PropTypes from "prop-types";

/**
 * `ProtectedRoute` 是一個保護頁面的元件
 * 只有當使用者已登入時，才能訪問 `children` (受保護的內容)
 * 否則，會被重定向到 `/login`
 *
 * @param {Object} props - React 組件的屬性
 * @param {React.ReactNode} props.children - 受保護的子元件 (例如 Dashboard)
 * @returns {JSX.Element} - 若已登入，顯示 `children`，否則跳轉到 `/login`
 */
const ProtectedRoute = ({ children }) => {
  // ✅ 改用 `isAuthenticatedAtom` 來判斷是否登入，避免 `localStorage` 操作
  const isAuthenticated = useAtomValue(isAuthenticatedAtom);

  /**
   * 根據登入狀態決定要顯示什麼：
   * - ✅ 如果已登入 (isAuthenticated 為 true)，顯示 `children` (受保護頁面)
   * - ❌ 如果未登入 (isAuthenticated 為 false)，導向 `/login` (透過 `<Navigate to="/login" replace />`)
   */
  return isAuthenticated ? children : <Navigate to="/login" replace />;
};

// ✅ PropTypes 檢查，確保 `children` 傳入的是有效的 React 元件
ProtectedRoute.propTypes = {
  children: PropTypes.node.isRequired,
};

export default ProtectedRoute; // 匯出元件，讓其他頁面使用
