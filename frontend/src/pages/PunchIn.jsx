import { useState, useEffect } from "react";
import { Button, Typography, Box, Container, Grid, CircularProgress } from "@mui/material";
import { Link } from "react-router-dom";
import AccessTimeIcon from "@mui/icons-material/AccessTime";
import HomeIcon from "@mui/icons-material/Home";
import { keyframes } from "@mui/system";
import API from "../api/axios";

// 定義 shake 動畫
const shake = keyframes`
  0% { transform: translateX(0); }
  20% { transform: translateX(-5px); }
  40% { transform: translateX(5px); }
  60% { transform: translateX(-5px); }
  80% { transform: translateX(5px); }
  100% { transform: translateX(0); }
`;

function PunchIn() {
  const [clockInTime, setClockInTime] = useState(null);
  const [clockOutTime, setClockOutTime] = useState(null);
  const [loadingClockIn, setLoadingClockIn] = useState(false);
  const [loadingClockOut, setLoadingClockOut] = useState(false);
  const [showClockInAnim, setShowClockInAnim] = useState(false);
  const [showClockOutAnim, setShowClockOutAnim] = useState(false);
  const [error, setError] = useState(null);

  // 從後端獲取打卡記錄
  useEffect(() => {
    const fetchPunchData = async () => {
      try {
        const response = await API.get("/punch-status");
        setClockInTime(response.data.clockInTime);
        setClockOutTime(response.data.clockOutTime);
      } catch (err) {
        setError("無法載入打卡資訊");
      }
    };
    fetchPunchData();
  }, []);

  // 上班打卡
  const handleClockIn = async () => {
    setLoadingClockIn(true);
    setError(null);
    try {
      const response = await API.post("/clock-in");
      setClockInTime(response.data.time);
      setShowClockInAnim(true);
      setTimeout(() => setShowClockInAnim(false), 500);
    } catch (err) {
      setError("打卡失敗，請稍後再試");
    } finally {
      setLoadingClockIn(false);
    }
  };

  // 下班打卡
  const handleClockOut = async () => {
    setLoadingClockOut(true);
    setError(null);
    try {
      const response = await API.post("/clock-out");
      setClockOutTime(response.data.time);
      setShowClockOutAnim(true);
      setTimeout(() => setShowClockOutAnim(false), 500);
    } catch (err) {
      setError("打卡失敗，請稍後再試");
    } finally {
      setLoadingClockOut(false);
    }
  };

  return (
    <Container maxWidth={false} sx={{ display: "flex", justifyContent: "center", alignItems: "center", height: "100vh", width: "100vw" }}>
      <Grid container spacing={4} alignItems="center" justifyContent="center" sx={{ width: "80%" }}>
        
        {/* 左側區塊 (Logo + 按鈕) */}
        <Grid item xs={12} md={4} textAlign="center">
          <img src="/src/image/logo.png" alt="Dacall Logo" style={{ maxWidth: 200, width: "100%", height: "auto" }} />
          <Box sx={{ mt: 3, display: "flex", flexDirection: "column", alignItems: "center", gap: 2 }}>
            <Button 
              variant="text" 
              onClick={handleClockIn} 
              disabled={loadingClockIn} 
              sx={{ width: 150, height: 40, backgroundColor: "#BDBDBD", color: "#FFF", "&:hover": { backgroundColor: "#9E9E9E"} }}
            >
              {loadingClockIn ? <CircularProgress size={20} color="inherit" /> : "上班打卡"}
            </Button>
            <Button 
              variant="text" 
              onClick={handleClockOut} 
              disabled={loadingClockOut} 
              sx={{ width: 150, height: 40, backgroundColor: "#BDBDBD", color: "#FFF", "&:hover": { backgroundColor: "#9E9E9E"} }}
            >
              {loadingClockOut ? <CircularProgress size={20} color="inherit" /> : "下班打卡"}
            </Button>
            <Button 
              variant="text" 
              component={Link} 
              to="/clock-reissue-history" 
              sx={{ width: 150, height: 40, backgroundColor: "#BDBDBD", color: "#FFF", "&:hover": { backgroundColor: "#9E9E9E"} }}
            >
              補打卡
            </Button>
            <Button 
              variant="text" 
              component={Link} 
              to="/clock-history" 
              sx={{ width: 150, height: 40, backgroundColor: "#BDBDBD", color: "#FFF", "&:hover": { backgroundColor: "#9E9E9E"} }}
            >
              查詢打卡紀錄
            </Button>
          </Box>
          {error && <Typography color="error" sx={{ mt: 2 }}>{error}</Typography>}
        </Grid>

        {/* 右側區塊 (打卡狀態框) */}
        <Grid item xs={12} md={4} sx={{ display: "flex", flexDirection: "column", alignItems: "center", gap: 2 }}>
          <Box 
            sx={{ 
              background: "#cce7fb", 
              borderRadius: "10px", 
              padding: "15px", 
              textAlign: "center", 
              width: "180px", 
              height: "100px", 
              display: "flex", 
              flexDirection: "column", 
              alignItems: "center", 
              justifyContent: "center", 
              boxShadow: "4px 4px 10px rgba(0, 0, 0, 0.2)", 
              animation: showClockInAnim ? `${shake} 0.5s ease-in-out` : "none" 
            }}
          >
            <AccessTimeIcon sx={{ fontSize: 30 }} />
            <Typography>上班</Typography>
            <Typography>{clockInTime || "尚未打卡"}</Typography>
          </Box>
          <Box 
            sx={{ 
              background: "#cce7fb", 
              borderRadius: "10px", 
              padding: "15px", 
              textAlign: "center", 
              width: "180px", 
              height: "100px", 
              display: "flex", 
              flexDirection: "column", 
              alignItems: "center", 
              justifyContent: "center", 
              boxShadow: "4px 4px 10px rgba(0, 0, 0, 0.2)", 
              animation: showClockOutAnim ? `${shake} 0.5s ease-in-out` : "none" 
            }}
          >
            <HomeIcon sx={{ fontSize: 30 }} />
            <Typography>下班</Typography>
            <Typography>{clockOutTime || "尚未打卡"}</Typography>
          </Box>
        </Grid>
      </Grid>
    </Container>
  );
}

export default PunchIn;
