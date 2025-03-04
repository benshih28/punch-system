import { BrowserRouter as Router, Routes, Route, Navigate } from "react-router-dom";
import LoginPage from "./pages/LoginPage"; // 引入登入頁面
import PunchIn from "./pages/punchIn";

function App() {
  return (
    <Router>
      <Routes>
        {/* 預設導向至登入頁面 */}
        <Route path="/" element={<Navigate replace to="/login" />} />
        
        {/* 登入頁面 */}
        <Route path="/login" element={<LoginPage />} />
        <Route path="/punchin" element={<PunchIn />} />
      </Routes>
    </Router>
  );
}

export default App;