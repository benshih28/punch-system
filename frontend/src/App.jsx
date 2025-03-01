import { BrowserRouter as Router, Routes, Route, Navigate } from "react-router-dom";
import { useAtom } from "jotai";
import LoginPage from "./pages/LoginPage";
import PunchIn from "./pages/PunchIn";
import { authAtom } from "./state/authAtom"; // 建立 Auth 狀態管理

// 受保護的路由 (登入後才可訪問)
const ProtectedRoute = ({ element }) => {
  const [auth] = useAtom(authAtom);
  const isAuthenticated = auth || localStorage.getItem("token");

  return isAuthenticated ? element : <Navigate to="/login" replace />;
};

function App() {
  return (
    <Router>
      <Routes>
        <Route path="/" element={<Navigate replace to="/login" />} />
        <Route path="/login" element={<LoginPage />} />
        <Route path="/PunchIn" element={<ProtectedRoute element={<PunchIn />} />} />
      </Routes>
    </Router>
  );
}

export default App;
