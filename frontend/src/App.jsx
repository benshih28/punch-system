import {
  BrowserRouter as Router,
  Route,
  Routes,
  Navigate,
} from "react-router-dom";
import {  useAtomValue } from "jotai";
import { isAuthenticatedAtom } from "./state/authAtom";
// import Header from "./components/header";
// import Footer from "./components/footer";
import LoginPage from "./pages/LoginPage";
// import Register from "./pages/register";
import Punchin from "./pages/punchin";
// import ApproveLeave from "./pages/approveLeave";
import ProtectedRoute from "./components/protectedRoute";

// 先預留這些路由
const ProfilePage = () => <div>個人帳戶管理頁面 (尚未建立)</div>;
const ClockHistoryPage = () => <div>查詢打卡紀錄頁面 (尚未建立)</div>;
const ClockReissueHistoryPage = () => <div>查詢補打卡紀錄頁面 (尚未建立)</div>;
const LeaveRecordsPage = () => <div>請假及查詢紀錄頁面 (尚未建立)</div>;
const ApproveClockReissuePage = () => <div>補打卡審核頁面 (尚未建立)</div>;
const ApproveLeavePage = () => <div>假單審查審核頁面 (尚未建立)</div>;
const UserManagementPage = () => <div>人員管理頁面 (尚未建立)</div>;
const RolePermissionsPage = () => <div>權限修改頁面 (尚未建立)</div>;

/**
 * 受保護頁面的 Layout（包含 Header & Footer）
 */
const ProtectedLayout = ({ children }) => (
  <>
    {/* <Header /> */}
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
              {/* <Register /> */}
              {/* <Footer /> */}
            </>
          }
        />

        {/* ✅ 已登入後的所有頁面（確保 Header 只出現在登入後的頁面） */}
        <Route
          path="*"
          element={
            isAuthenticated ? ( // ✅ 改為用 `isAuthenticatedAtom`
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
            ) : (
              <>
                <Navigate to="/login" />
                {/* <Footer /> */}
              </>
            )
          }
        />
      </Routes>
    </Router>
  );
}

export default App;
