import {
  BrowserRouter as Router,
  Route,
  Routes,
  Navigate,
} from "react-router-dom";
import { useAtomValue } from "jotai";
import { isAuthenticatedAtom } from "./state/authAtom";
import Header from "./components/header";
import PropTypes from 'prop-types';
// import Footer from "./components/footer";
import LoginPage from "./pages/LoginPage";
import RegisterPage from "./pages/RegisterPage";
// import Punchin from "./pages/punchin";
// import ApproveLeave from "./pages/approveLeave";
import ProtectedRoute from "./components/protectedRoute";
import ForgotPassword from "./pages/ForgotPasswordPage";
import ApproveClockReissuePage from "./pages/ApproveClockReissuePage";
import ClockReissueHistoryPage from "./pages/ClockReissueHistoryPage";
import LeavePolicy from "./components/LeavePolicy";

// 先預留這些路由
const Punchin = () => <div>個人打卡頁面 (尚未建立)</div>;
const ProfilePage = () => <div>個人帳戶管理頁面 (尚未建立)</div>;
const ClockHistoryPage = () => <div>查詢打卡紀錄頁面 (尚未建立)</div>;
const LeaveRecordsPage = () => <div>請假及查詢紀錄頁面 (尚未建立)</div>;
const ApproveLeavePage = () => <div>假單審查審核頁面 (尚未建立)</div>;
const UserManagementPage = () => <div>人員管理頁面 (尚未建立)</div>;
const RolePermissionsPage = () => <div>權限修改頁面 (尚未建立)</div>;

/**
 * 受保護頁面的 Layout（包含 Header & Footer）
 */
const ProtectedLayout = ({ children }) => (
  <>
    <Header />
    <main>{children}</main>
    {/* <Footer /> */}
  </>
);

function App() {
  // ✅ 透過 Jotai 讀取 `isAuthenticatedAtom`，用於判斷使用者是否已登入
  const isAuthenticated = useAtomValue(isAuthenticatedAtom);

  return (
    <Router>
      <Routes>
        {/* ✅ 未登入時顯示 LoginPage，並包含 Footer */}
        <Route
          path="/login"
          element={
            <>
              <LoginPage />
              {/* <Footer /> */}
            </>
          }
        />

        {/* ✅ 註冊頁面，不需要登入即可訪問 */}
        <Route
          path="/register"
          element={
            <>
              <RegisterPage />
              {/* <Footer /> */}
            </>
          }
        />

        {/* 忘記密碼頁面，不需要登入 */}
        <Route path="/forgot/password" element={<ForgotPassword />} />
        
        {/* 此頁面為請假規則頁面，會放在請假彈出框的裡面，由內部連結跳轉頁面 (此路由會刪掉) */}
        <Route path="/leave-policy" element={<LeavePolicy />} />

        {/* ✅ 已登入後的所有頁面（確保 Header 只出現在登入後的頁面） */}
        <Route
          path="*"
          element={
              <ProtectedRoute>
                <ProtectedLayout>
                  <Routes>
                    <Route path="/punchin" element={<Punchin />} />
                    <Route path="/profile" element={<ProfilePage />} />
                    <Route
                      path="/clock-history"
                      element={<ClockHistoryPage />}
                    />
                    <Route
                      path="/clock-reissue-history"
                      element={<ClockReissueHistoryPage />}
                    />
                    <Route
                      path="/leave-and-inquiry-records"
                      element={<LeaveRecordsPage />}
                    />
                    <Route
                      path="/approve-leave"
                      element={<ApproveLeavePage />}
                    />
                    <Route
                      path="/approve-clock-reissue"
                      element={<ApproveClockReissuePage />}
                    />
                    <Route
                      path="/user-management"
                      element={<UserManagementPage />}
                    />
                    <Route
                      path="/role-permissions"
                      element={<RolePermissionsPage />}
                    />
                    <Route path="*" element={<Navigate to="/punchin" />} />
                  </Routes>
                </ProtectedLayout>
              </ProtectedRoute>
            }
          />

          {/* 未登入時的默認跳轉 */}
          <Route path="*" element={!isAuthenticated && <Navigate to="/login" replace />} />
            </Routes>
          </Router>
  );
}
// ✅ 使用 PropTypes 規範受保護頁面的 Layout
ProtectedLayout.propTypes = {
  children: PropTypes.node.isRequired,
};

export default App;
