import React, { useState }  from "react";
import { useForm } from "react-hook-form";
import { Box, Button, Container, TextField, Typography, Dialog, DialogActions, DialogContent, DialogContentText, DialogTitle } from "@mui/material";
import { useNavigate } from "react-router-dom";
import CheckCircleIcon from "@mui/icons-material/CheckCircle";
import api from "../api/axios";

function ForgotPassword() {
  const { register, handleSubmit, formState: { errors },} = useForm();
  const navigate = useNavigate();
  // 控制 Dialog 顯示
  const [openDialog, setOpenDialog] = useState(false);

  const onSubmit = async(data) => {
    // console.log("Email submitted:", data.email);
    try {
        // 發送 API 請求到
        await api.post("/forgot/password", { email: data.email });  
        // 顯示成功彈跳視窗
        setOpenDialog(true);
    } catch (error) {
      window.alert("發送失敗：" + (error.response?.data?.message || "請稍後再試"));
    }
  };

  
  return (
    <Container
      sx={{
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        justifyContent: "center",
        height: "100vh",
      }}
    >
         {/* 彈跳視窗 Dialog */}
      <Dialog open={openDialog} onClose={() => setOpenDialog(false)}>
        <DialogTitle>郵件已發送！</DialogTitle>
        <DialogContent>
          <DialogContentText>
            重設密碼的郵件已發送，請至您的信箱查看，並點擊郵件中的連結完成登入。
          </DialogContentText>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setOpenDialog(false)} color="primary">
            關閉
          </Button>
        </DialogActions>
      </Dialog>
      <Box
        component="img"
        src="/logo.png"
        alt="Dacall Logo"
        sx={{ width: 220, mb: 2 }}
      />
      <Box
        sx={{
          background: "linear-gradient(to bottom, #dbeafe, #ffffff)",
          padding: 4,
          borderRadius: 2,
          boxShadow: 3,
          textAlign: "center",
          width: 350,
        }}
      >
        <Typography variant="h5" fontWeight="bold" mb={2}>
          修改密碼
        </Typography>
        <form onSubmit={handleSubmit(onSubmit)}>
          <TextField
            fullWidth
            label="Email"
            type="email"
            variant="filled"
            {...register("email", {
              required: "請輸入 Email",
              pattern: {
                value: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
                message: "Email 格式不正確",
              },
            })}
            error={!!errors.email}
            helperText={errors.email?.message}
            margin="normal"
          />
          <Button
            type="submit"
            variant="contained"
            fullWidth
            sx={{
              mt: 3,
              backgroundColor: "#A48C72",
              borderRadius: 999,
              padding: "12px 0",
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
              gap: 1,
            }}
          >
            <CheckCircleIcon sx={{ fontSize: 20 }} /> 送出
          </Button>
        </form>
        <Button
          variant="text"
          fullWidth
          sx={{ mt: 2, color: "#8B5E3B" }}
          onClick={() => navigate("/login")}
        >
          返回登入
        </Button>
      </Box>
    </Container>
  );
}

export default ForgotPassword;