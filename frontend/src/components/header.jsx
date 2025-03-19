import { useState } from "react";
import { AppBar, Toolbar, IconButton, Box } from "@mui/material";
import MenuIcon from "@mui/icons-material/Menu";
import { useNavigate } from "react-router-dom";
import Sidebar from "./sidebar"; // 引入側邊欄
import NotificationPanel from "./notificationPanel"; // 確保路徑正確

function Header() {
  const navigate = useNavigate();
  const [sidebarOpen, setSidebarOpen] = useState(false); // 控制側邊欄開關

  return (
    <>
      <AppBar
        position="sticky"
        color="default"
        sx={{ boxShadow: 1, top: 0, zIndex: 1100 }}
      >
        <Toolbar
          sx={{
            display: "flex",
            justifyContent: "space-between",
            alignItems: "center",
          }}
        >
          {/* 左側漢堡選單 (控制 Sidebar 開啟) */}
          <IconButton
            edge="start"
            color="inherit"
            onClick={() => setSidebarOpen(true)}
          >
            <MenuIcon />
          </IconButton>

          {/* LOGO 置中（可點擊跳轉到 /punchin 頁面） */}
          <Box sx={{ flexGrow: 1, textAlign: "center" }}>
            <img
              src="/logo.png"
              alt="header-logo"
              style={{ width: "50px", height: "auto", cursor: "pointer" }}
              onClick={() => navigate("/punchin")}
            />
          </Box>

          {/* 右側頭像（通知欄） */}
          <Box sx={{ display: "flex", alignItems: "center" }}>
            <NotificationPanel />
          </Box>
        </Toolbar>
      </AppBar>

      {/* 側邊欄 (Material UI `Drawer`) */}
      <Sidebar
        isOpen={sidebarOpen}
        toggleSidebar={() => setSidebarOpen(!sidebarOpen)}
      />
    </>
  );
}

export default Header;
