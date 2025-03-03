import { useState } from "react";
import { Link } from "react-router-dom"; // 只保留 Link 以進行頁面跳轉
import {
  Box,
  Paper,
  TextField,
  Button,
  IconButton,
  Typography,
  InputAdornment,
} from "@mui/material";
import { Visibility, VisibilityOff, Email, Lock } from "@mui/icons-material";

function LoginPage() {
  // 控制密碼是否可見
  const [showPassword, setShowPassword] = useState(false);

  return (
    <Box
      sx={{
        width: "100vw",
        height: "100vh",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        backgroundColor: "#ffffff",
      }}
    >
      <Paper
        elevation={0}
        sx={{
          maxWidth: 350,
          width: "100%",
          textAlign: "center",
          padding: "30px",
          borderRadius: "10px",
        }}
      >
        {/* Logo 圖片 */}
        <img
          src="src/image/logo.png"
          alt="Dacall Logo"
          style={{ width: 140, display: "block", margin: "0 auto 20px" }}
        />

        {/* 標題 */}
        <Typography variant="h5" fontWeight="bold" sx={{ mb: 2 }}>
          Sign in with Email
        </Typography>

        {/* Email 輸入框 */}
        <TextField
          fullWidth
          margin="normal"
          label="請輸入 Email"
          variant="outlined"
          InputProps={{
            startAdornment: (
              <InputAdornment position="start">
                <Email sx={{ color: "#757575" }} />
              </InputAdornment>
            ),
          }}
        />

        {/* 密碼輸入框 */}
        <TextField
          fullWidth
          margin="normal"
          label="請輸入密碼"
          variant="outlined"
          type={showPassword ? "text" : "password"} // 切換密碼可見性
          InputProps={{
            startAdornment: (
              <InputAdornment position="start">
                <Lock sx={{ color: "#757575" }} />
              </InputAdornment>
            ),
            endAdornment: (
              <InputAdornment position="end">
                <IconButton onClick={() => setShowPassword(!showPassword)} edge="end">
                  {showPassword ? <VisibilityOff /> : <Visibility />}
                </IconButton>
              </InputAdornment>
            ),
          }}
        />

        {/* 忘記密碼連結 */}
        <Box textAlign="right" sx={{ mb: 2 }}>
          <Link to="/ForgotPassword" style={{ fontSize: "14px", color: "#757575" }}>
            忘記密碼
          </Link>
        </Box>

        {/* 登入按鈕 */}
        <Button
          fullWidth
          sx={{
            backgroundColor: "#C3E6CB",
            color: "#000",
            fontWeight: "bold",
            padding: "12px",
            borderRadius: "20px",
            mb: 1,
            "&:hover": { backgroundColor: "#A5D6A7" },
            "&:active": { backgroundColor: "#81C784" },
          }}
        >
          登入
        </Button>

        {/* 註冊按鈕 */}
        <Button
          component={Link}
          to="/Register"
          fullWidth
          sx={{
            backgroundColor: "#E0E0E0",
            color: "#000",
            fontWeight: "bold",
            padding: "12px",
            borderRadius: "20px",
            "&:hover": { backgroundColor: "#BDBDBD" },
            "&:active": { backgroundColor: "#9E9E9E" },
          }}
        >
          註冊
        </Button>
      </Paper>
    </Box>
  );
}

export default LoginPage;