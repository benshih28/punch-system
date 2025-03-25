import { useState } from "react";
import PropTypes from "prop-types";
import { Link, useNavigate } from "react-router-dom";
import { useAtom, useSetAtom } from "jotai";
import { authAtom, logoutAtom } from "../state/authAtom";
import API from "../api/axios";
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
import ArrowRightIcon from "@mui/icons-material/ArrowRight";

function Sidebar({ isOpen, toggleSidebar }) {
  const [auth, setAuth] = useAtom(authAtom); // 讀取全局狀態
  const navigate = useNavigate();
  const [openMenus, setOpenMenus] = useState({});
  const logout = useSetAtom(logoutAtom);
  const toggleMenu = (menu) => {
    setOpenMenus((prev) => ({
      ...prev,
      [menu]: !prev[menu],
    }));
  };

  const sidebarItems = [
    {
      label: "個人帳戶管理",
      icon: <AccountCircleIcon />,
      to: "/user/update/profile",
      requiredPermissions: [], // 所有人都能用
    },
    {
      label: "打卡",
      icon: <AccessTimeIcon />,
      children: [
        {
          label: "打卡及補打卡",
          to: "/punchin",
          requiredPermissions: ["punch_in"],
        },
        {
          label: "查詢打卡紀錄",
          to: "/clock/history",
          requiredPermissions: ["view_attendance"],
        },
        {
          label: "查詢補打卡紀錄",
          to: "/clock/reissue/history",
          requiredPermissions: ["view_corrections"],
        },
      ],
    },
    {
      label: "請假及紀錄查詢",
      icon: <EventNoteIcon />,
      to: "/leave/and/inquiry/records",
      requiredPermissions: ["view_leave_records"],
    },
    {
      label: "簽核系統",
      icon: <AssignmentIcon />,
      children: [
        {
          label: "假單審核",
          to: "/approve/leave",
          requiredPermissions: ["approve_leave"],
        },
        {
          label: "補打卡審核",
          to: "/approve/clock/reissue",
          requiredPermissions: ["approve_correction"],
        },
      ],
    },
    {
      label: "權限管理",
      icon: <PeopleIcon />,
      children: [
        {
          label: "部門管理",
          to: "/department/management",
          requiredPermissions: ["manage_departments"],
        },
        {
          label: "職位管理",
          to: "/position/management",
          requiredPermissions: ["manage_positions"],
        },
        {
          label: "人員管理",
          to: "/user/management",
          requiredPermissions: ["manage_employees"],
        },
        {
          label: "權限修改",
          to: "/role/permissions",
          requiredPermissions: ["manage_roles"],
        },
      ],
    },
  ];

  const hasPermission = (userPermissions, requiredPermissions) => {
    if (!requiredPermissions || requiredPermissions.length === 0) return true;
    return requiredPermissions.some((perm) => userPermissions.includes(perm));
  };

  // 登出函式
  const handleLogout = async () => {
    try {
      await API.post("/logout"); // ✅ 如果 API 需要登出請求
    } catch (error) {
      console.warn("登出失敗，可能已經登出:", error);
    }

    logout(); // 使用 `logoutAtom`，清除 `authAtom` 和 localStorage

    navigate("/login"); // 導向登入頁
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
          {sidebarItems.map((item, index) => {
            const hasChild = item.children && item.children.length > 0;
            const show = hasPermission(auth?.roles_permissions?.permissions || [], item.requiredPermissions);

            if (!show && !hasChild) return null;

            // 有子選單的
            if (hasChild) {
              const visibleChildren = item.children.filter((child) =>
                hasPermission(auth?.roles_permissions?.permissions || [], child.requiredPermissions)
              );

              if (visibleChildren.length === 0) return null;

              return (
                <Box key={index}>
                  {/* 主選單按鈕 */}
                  <ListItemButton onClick={() => toggleMenu(item.label)}>
                    <ListItemIcon>{item.icon}</ListItemIcon>
                    <ListItemText primary={item.label} />
                    {openMenus[item.label] ? <ExpandLess /> : <ExpandMore />}
                  </ListItemButton>

                  {/* 子選單 */}
                  <Collapse in={openMenus[item.label]} timeout="auto" unmountOnExit>
                    <List component="div" disablePadding>
                      {visibleChildren.map((child, i) => (
                        <ListItemButton
                          key={i}
                          component={Link}
                          to={child.to}
                          onClick={toggleSidebar}
                          sx={{ pl: 5 }} // 加一點縮排
                        >
                          {/* 🔹 子選單小圖示 */}
                          <ListItemIcon sx={{ minWidth: 30 }}>
                            <ArrowRightIcon fontSize="small" />
                          </ListItemIcon>

                          <ListItemText primary={child.label} />
                        </ListItemButton>
                      ))}
                    </List>
                  </Collapse>
                </Box>
              );
            }

            // 沒子選單的
            return (
              <ListItemButton key={index} component={Link} to={item.to}  onClick={toggleSidebar}>
                <ListItemIcon>{item.icon}</ListItemIcon>
                <ListItemText primary={item.label} />
              </ListItemButton>
            );
          })}

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
// 🔹 側邊欄組件的 PropTypes
Sidebar.propTypes = {
  isOpen: PropTypes.bool.isRequired,
  toggleSidebar: PropTypes.func.isRequired,
};

export default Sidebar;
