import { useState } from "react";
import { useNavigate, Link } from "react-router-dom";
import { useForm } from "react-hook-form";
import { atom, useAtom } from "jotai";
import API from "../api/axios";
import {
  Box,
  Paper,
  TextField,
  Button,
  IconButton,
  Typography,
  InputAdornment,
  CircularProgress,
} from "@mui/material";
import { Visibility, VisibilityOff, Email, Lock } from "@mui/icons-material";

// 定義 Jotai Atom 來儲存登入狀態
const authAtom = atom(null);

function LoginPage() {
  const { register, handleSubmit, setError, formState: { errors } } = useForm();
  const [, setAuth] = useAtom(authAtom);
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  // 處理表單提交
  const onSubmit = async (data) => {
    setLoading(true);
    try {
      const response = await API.post("/login", data);
      localStorage.setItem("token", response.data.token);
      setAuth(response.data.user);
      navigate("/PunchIn");
    } catch (error) {
      if (error.response && error.response.data) {
        setError("email", { message: error.response.data.message || "登入失敗" });
      } else {
        setError("email", { message: "無法連線至伺服器" });
      }
    } finally {
      setLoading(false);
    }
  };

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
        {/* Logo */}
        <img
          src="src/image/logo.png"
          alt="Dacall Logo"
          style={{ width: 140, display: "block", margin: "0 auto 20px" }}
        />

        {/* 標題 */}
        <Typography variant="h5" fontWeight="bold" sx={{ mb: 2 }}>
          Sign in with Email
        </Typography>

        {/* 登入表單 */}
        <form onSubmit={handleSubmit(onSubmit)}>
          {/* Email 輸入框 */}
          <TextField
            fullWidth
            margin="normal"
            label="請輸入 Email"
            variant="outlined"
            {...register("email", { required: "Email 為必填", pattern: { value: /^\S+@\S+$/i, message: "Email 格式錯誤" } })}
            error={!!errors.email}
            helperText={errors.email?.message}
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
            type={showPassword ? "text" : "password"}
            {...register("password", { required: "密碼為必填", minLength: { value: 8, message: "密碼至少需 8 碼" } })}
            error={!!errors.password}
            helperText={errors.password?.message}
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

          {/* 忘記密碼 */}
          <Box textAlign="right" sx={{ mb: 2 }}>
            <Link to="/ForgotPassword" style={{ fontSize: "14px", color: "#757575" }}>
              忘記密碼
            </Link>
          </Box>

          {/* 登入按鈕（淡綠色） */}
          <Button
            type="submit"
            fullWidth
            disabled={loading}
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
            {loading ? <CircularProgress size={24} /> : "登入"}
          </Button>

          {/* 註冊按鈕（淺灰色） */}
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
        </form>
      </Paper>
    </Box>
  );
}

export default LoginPage;
