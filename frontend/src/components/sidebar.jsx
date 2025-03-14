import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { useAtom } from "jotai";
import { authAtom } from "../state/authAtom";
import {
  Drawer,
  List,
  ListItemButton,
  ListItemIcon,
  ListItemText,
  Collapse,
  Divider,
  Box,
  IconButton,
} from "@mui/material";
import CloseIcon from "@mui/icons-material/Close";
import AccountCircleIcon from "@mui/icons-material/AccountCircle"; // 🔹 個人帳戶管理
import AccessTimeIcon from "@mui/icons-material/AccessTime";
import AssignmentIcon from "@mui/icons-material/Assignment";
import PeopleIcon from "@mui/icons-material/People";
import EventNoteIcon from "@mui/icons-material/EventNote"; // 🔹 請假及查詢紀錄
import ExpandLess from "@mui/icons-material/ExpandLess";
import ExpandMore from "@mui/icons-material/ExpandMore";
import LogoutIcon from "@mui/icons-material/Logout";

function Sidebar({ isOpen, toggleSidebar }) {
  const [auth, setAuth] = useAtom(authAtom);
  const navigate = useNavigate();
  const [openMenus, setOpenMenus] = useState({});

  const toggleMenu = (menu) => {
    setOpenMenus((prev) => ({
      ...prev,
      [menu]: !prev[menu],
    }));
  };

  // 登出函式
  const handleLogout = () => {
    localStorage.removeItem("token"); // 清除 Token
    setAuth({ isAuthenticated: false, user: null }); // 更新狀態
    navigate("/login"); // 重新導向登入頁
  };

  return (
    <Drawer anchor="left" open={isOpen} onClose={toggleSidebar}>
      <Box sx={{ width: 250 }}>
        {/* 側邊欄標題 & 關閉按鈕 */}
        <Box
          sx={{
            display: "flex",
            alignItems: "center",
            padding: 2,
            justifyContent: "space-between",
          }}
        >
          <strong>功能選單</strong>
          <IconButton onClick={toggleSidebar}>
            <CloseIcon />
          </IconButton>
        </Box>

        <Divider />

        {/* 選單列表 */}
        <List>
          {/* 🔹 個人帳戶管理 */}
          <ListItemButton component={Link} to="/profile">
            <ListItemIcon>
              <AccountCircleIcon />
            </ListItemIcon>
            <ListItemText primary="個人帳戶管理" />
          </ListItemButton>

          {/* 🔹 打卡選單 (可展開) */}
          <ListItemButton onClick={() => toggleMenu("clock")}>
            <ListItemIcon>
              <AccessTimeIcon />
            </ListItemIcon>
            <ListItemText primary="打卡" />
            {openMenus["clock"] ? <ExpandLess /> : <ExpandMore />}
          </ListItemButton>
          <Collapse in={openMenus["clock"]} timeout="auto" unmountOnExit>
            <List component="div" disablePadding>
              <ListItemButton component={Link} to="/punchin" sx={{ pl: 4 }}>
                <ListItemText primary="打卡及補打卡" />
              </ListItemButton>
              <ListItemButton
                component={Link}
                to="/clock-history"
                sx={{ pl: 4 }}
              >
                <ListItemText primary="查詢打卡紀錄" />
              </ListItemButton>
              <ListItemButton
                component={Link}
                to="/clock-reissue-history"
                sx={{ pl: 4 }}
              >
                <ListItemText primary="查詢補打卡紀錄" />
              </ListItemButton>
            </List>
          </Collapse>

          {/* 🔹 請假及查詢紀錄 */}
          <ListItemButton component={Link} to="/leave-and-inquiry-records">
            <ListItemIcon>
              <EventNoteIcon />
            </ListItemIcon>
            <ListItemText primary="請假及查詢紀錄" />
          </ListItemButton>

          {/* 🔹 簽核系統 (可展開) */}
          <ListItemButton onClick={() => toggleMenu("approval")}>
            <ListItemIcon>
              <AssignmentIcon />
            </ListItemIcon>
            <ListItemText primary="簽核系統" />
            {openMenus["approval"] ? <ExpandLess /> : <ExpandMore />}
          </ListItemButton>
          <Collapse in={openMenus["approval"]} timeout="auto" unmountOnExit>
            <List component="div" disablePadding>
              <ListItemButton
                component={Link}
                to="/approve-leave"
                sx={{ pl: 4 }}
              >
                <ListItemText primary="假單審核" />
              </ListItemButton>
              <ListItemButton
                component={Link}
                to="/approve-clock-reissue"
                sx={{ pl: 4 }}
              >
                <ListItemText primary="補打卡審核" />
              </ListItemButton>
            </List>
          </Collapse>

          {/* 🔹 權限管理 (可展開) */}
          <ListItemButton onClick={() => toggleMenu("permissions")}>
            <ListItemIcon>
              <PeopleIcon />
            </ListItemIcon>
            <ListItemText primary="權限管理" />
            {openMenus["permissions"] ? <ExpandLess /> : <ExpandMore />}
          </ListItemButton>
          <Collapse in={openMenus["permissions"]} timeout="auto" unmountOnExit>
            <List component="div" disablePadding>
              <ListItemButton
                component={Link}
                to="/user-management"
                sx={{ pl: 4 }}
              >
                <ListItemText primary="人員管理" />
              </ListItemButton>
              <ListItemButton
                component={Link}
                to="/role-permissions"
                sx={{ pl: 4 }}
              >
                <ListItemText primary="權限修改" />
              </ListItemButton>
            </List>
          </Collapse>

          <Divider />

          {/* 🔹 登出按鈕 */}
          <ListItemButton onClick={handleLogout}>
            <ListItemIcon>
              <LogoutIcon />
            </ListItemIcon>
            <ListItemText primary="登出" />
          </ListItemButton>
        </List>
      </Box>
    </Drawer>
  );
}

export default Sidebar;
