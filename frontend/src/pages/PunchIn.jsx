import { useNavigate } from "react-router-dom";
import { useAtom } from "jotai";
import { authAtom } from "../state/authAtom";
import { Button, Box, Typography } from "@mui/material";

function PunchIn() {
  const [, setAuth] = useAtom(authAtom); // 取得 Jotai 全域狀態管理
  const navigate = useNavigate();

  // 登出函式
  const handleLogout = () => {
    localStorage.removeItem("token"); // 清除 Token
    setAuth({ isAuthenticated: false, user: null }); // 更新狀態
    navigate("/login"); // 重新導向登入頁
  };

  return (
    <Box
      sx={{
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        justifyContent: "center",
        height: "100vh",
        textAlign: "center",
      }}
    >
      <Typography variant="h4" gutterBottom>
        Punch In Page
      </Typography>
      <Typography variant="body1" sx={{ mb: 3 }}>
        這是打卡頁面，你已經成功登入。
      </Typography>

      {/* 登出按鈕 */}
      <Button
        onClick={handleLogout}
        variant="contained"
        color="error"
        sx={{ padding: "10px 20px", borderRadius: "8px" }}
      >
        登出
      </Button>
    </Box>
  );
}

export default PunchIn;
