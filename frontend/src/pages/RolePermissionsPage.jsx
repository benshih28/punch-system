import { useMediaQuery } from "@mui/material";
import { useState, useEffect } from "react";
import {
  Box,
  Paper,
  Typography,
  Button,
  TableContainer,
  Table,
  TableHead,
  TableRow,
  TableCell,
  TableBody,
  Checkbox,
  Dialog,
  DialogActions,
  DialogContent,
  TextField,
  Pagination,
} from "@mui/material";
import AddCircleIcon from "@mui/icons-material/AddCircle";
import { Link, Navigate } from "react-router-dom";
import { permissionLabels } from "../constants/permissionLabels";
import API from "../api/axios";
import { useAtom } from "jotai"; // 引入 useAtom 用於獲取 Jotai 狀態
import { authAtom } from "../state/authAtom"; // 引入 authAtom


// 角色權限管理頁面組件
function RolePermissionsPage() {

  // 從 Jotai 獲取身份驗證狀態
  const [authState] = useAtom(authAtom);

  // 檢查使用者權限
  const userPermissions = authState?.roles_permissions?.permissions || [];
  const hasManageRolesPermission = userPermissions.includes("manage_roles");

  // 如果沒有 manage_roles 權限，跳轉到 404 頁面
  if (!hasManageRolesPermission) {
    return <Navigate to="/404" replace />;
  }



  // 狀態管理
  const [permissions, setPermissions] = useState([]); // 儲存所有角色資料
  const [permissionGroups, setPermissionGroups] = useState({}); // 儲存權限分組資料
  const [openAddDialog, setOpenAddDialog] = useState(false); // 控制新增角色對話框的顯示
  const [newPermissionName, setNewPermissionName] = useState(""); // 新增角色的名稱
  const [selectedNewPermissions, setSelectedNewPermissions] = useState([]); // 新增角色時選擇的權限
  const [openEditDialog, setOpenEditDialog] = useState(false); // 控制編輯角色對話框的顯示
  const [editPermissionName, setEditPermissionName] = useState(""); // 編輯角色的名稱
  const [editPermissionId, setEditPermissionId] = useState(null); // 編輯角色的 ID
  const [editRoleName, setEditRoleName] = useState(null); // 編輯角色的名稱（用於 API 請求）
  const [selectedEditPermissions, setSelectedEditPermissions] = useState([]); // 編輯角色時選擇的權限
  const [currentPage, setCurrentPage] = useState(1); // 當前頁數（分頁用）
  const itemsPerPage = 10; // 每頁顯示的資料筆數

  // 分頁計算：計算當前頁的資料範圍和總頁數
  const indexOfLastItem = currentPage * itemsPerPage; // 當前頁的最後一筆資料索引
  const indexOfFirstItem = indexOfLastItem - itemsPerPage; // 當前頁的第一筆資料索引
  const currentPermissions = permissions.slice(indexOfFirstItem, indexOfLastItem); // 當前頁的資料
  const totalPages = Math.ceil(permissions.length / itemsPerPage); // 總頁數

  // 初始載入角色列表
  useEffect(() => {
    API.get("/roles") // 從後端獲取角色列表
      .then((res) => {
        setPermissions(res.data); // 設定角色資料
      })
      .catch((err) => {
        console.error("取得角色列表失敗", err); // 錯誤處理
      });
  }, []);

  // 初始載入權限列表並分組
  useEffect(() => {
    API.get("/permissions") // 從後端獲取權限列表
      .then((res) => {
        const grouped = res.data.reduce((acc, perm) => {
          const { category, name } = perm; // 解構權限的類別和名稱
          if (!acc[category]) acc[category] = []; // 如果該類別不存在，初始化為空陣列
          acc[category].push({
            id: name, // 權限 ID（使用名稱作為 ID）
            name: permissionLabels[name] || name, // 顯示名稱（使用中文標籤或原始名稱）
          });
          return acc;
        }, {});
        setPermissionGroups(grouped); // 設定權限分組資料
      })
      .catch((err) => {
        console.error("取得權限列表失敗", err); // 錯誤處理
      });
  }, []);

  // 處理新增角色
  const handleAddPermission = async () => {
    if (!newPermissionName.trim()) {
      alert("請輸入角色名稱！"); // 檢查角色名稱是否為空
      return;
    }

    try {
      const payload = {
        name: newPermissionName, // 角色名稱
        permissions: selectedNewPermissions, // 選擇的權限
      };

      await API.post("/roles", payload); // 發送 POST 請求新增角色

      const res = await API.get("/roles"); // 重新獲取角色列表以確保資料同步
      setPermissions(res.data); // 更新角色列表
      setCurrentPage(1); // 新增後回到第一頁

      // 關閉對話框並重置表單
      setOpenAddDialog(false);
      setNewPermissionName("");
      setSelectedNewPermissions([]);
    } catch (error) {
      console.error("建立角色失敗：", error); // 錯誤處理
      alert("建立角色失敗，請稍後再試");
    }
  };

  // 處理開啟編輯角色對話框
  const handleEditOpen = async (permission) => {
    try {
      const response = await API.get(`/roles/${permission.name}/permissions`); // 獲取該角色的最新權限
      setEditPermissionId(permission.id); // 儲存角色 ID（用於更新本地狀態）
      setEditRoleName(permission.name); // 儲存角色名稱（用於 API 請求）
      setEditPermissionName(permission.name); // 設定角色名稱（顯示用）
      setSelectedEditPermissions(response.data.permissions); // 設定權限
      setOpenEditDialog(true); // 開啟編輯對話框
    } catch (error) {
      console.error("取得角色權限失敗：", error); // 錯誤處理
      alert("無法載入角色權限，請稍後再試");
    }
  };


  // 處理儲存編輯後的角色權限和名稱
  const handleSaveEdit = async () => {
    try {
      const payload = {
        name: editPermissionName, // 新角色名稱
        permissions: selectedEditPermissions, // 更新的權限列表
      };
      await API.patch(`/roles/${editRoleName}/permissions`, payload); // 發送 PATCH 請求更新名稱和權限

      // 更新本地角色列表
      setPermissions(
        permissions.map((p) =>
          p.id === editPermissionId ? { ...p, name: editPermissionName, permissions: selectedEditPermissions } : p
        )
      );
      setOpenEditDialog(false); // 關閉編輯對話框
    } catch (error) {
      console.error("編輯角色權限和名稱失敗：", error); // 錯誤處理
      if (error.response?.status === 404) {
        alert("角色不存在，請重新整理頁面");
      } else if (error.response?.status === 403) {
        alert("您沒有權限執行此操作");
      } else if (error.response?.status === 422) {
        alert("角色名稱已存在，請使用其他名稱");
      } else {
        alert("編輯角色失敗，請稍後再試");
      }
    }
  };

  // 處理新增角色時的權限選擇（勾選/取消勾選）
  const handleToggleNewPermission = (id) => {
    setSelectedNewPermissions((prev) =>
      prev.includes(id) ? prev.filter((p) => p !== id) : [...prev, id] // 切換權限的勾選狀態
    );
  };

  // 處理編輯角色時的權限選擇（勾選/取消勾選）
  const handleToggleEditPermission = (id) => {
    setSelectedEditPermissions((prev) =>
      prev.includes(id) ? prev.filter((p) => p !== id) : [...prev, id] // 切換權限的勾選狀態
    );
  };

  // 處理分頁切換
  const handlePageChange = (event, value) => {
    setCurrentPage(value); // 更新當前頁數
  };

  // RWD 檢查：判斷螢幕尺寸
  const isSmallScreen = useMediaQuery("(max-width: 600px)"); // 小螢幕（手機）
  const isMediumScreen = useMediaQuery("(max-width: 960px)"); // 中螢幕（平板）

  return (
    // 外層容器：設置整體佈局，支援 RWD
    <Box sx={{ width: "100%", minHeight: "100vh", display: "flex", flexDirection: "column", alignItems: "center", backgroundColor: "#ffffff", p: isSmallScreen ? 1 : 2 }}>
      {/* 標題列：包含多個導航連結，支援 RWD */}
      <Box
        sx={{
          display: "flex",
          flexDirection: isSmallScreen ? "column" : "row", // 小螢幕時垂直排列
          margin: isSmallScreen ? "20px 0px" : "60px 0px 40px",
          width: isSmallScreen ? "100%" : "90%",
          justifyContent: "space-between",
          alignItems: isSmallScreen ? "center" : "center",
          gap: isSmallScreen ? 1 : 0,
        }}
      >
        <Typography
          variant={isSmallScreen ? "h6" : "h4"} // 小螢幕時縮小字體
          fontWeight={900}
          textAlign="center"
          sx={{ mb: isSmallScreen ? 1 : 0 }}
        >
          <Link to="/department/management" style={{ textDecoration: "none", color: "black", display: isSmallScreen ? "block" : "inline" }}>
            部門管理
          </Link>
          {isSmallScreen ? <br /> : " "}
          <Link to="/position/management" style={{ textDecoration: "none", color: "black", display: isSmallScreen ? "block" : "inline" }}>
            職位管理
          </Link>
          {isSmallScreen ? <br /> : " "}
          <Link to="/role/permissions" style={{ textDecoration: "none", color: "#ba6262", fontWeight: "bold", display: isSmallScreen ? "block" : "inline" }}>
            權限管理
          </Link>
          {isSmallScreen ? <br /> : " "}
          <Link to="/user/management" style={{ textDecoration: "none", color: "black", display: isSmallScreen ? "block" : "inline" }}>
            人員管理
          </Link>
          {isSmallScreen ? <br /> : " "}
          <Link to="/employee/history" style={{ textDecoration: "none", color: "black", display: isSmallScreen ? "block" : "inline" }}>
            人員歷程
          </Link>
        </Typography>
      </Box>

      {/* 角色列表容器 */}
      <Paper sx={{ width: isSmallScreen ? "100%" : "90%", padding: isSmallScreen ? "10px" : "20px", boxShadow: "0px -4px 10px rgba(0, 0, 0, 0.3)", borderRadius: "8px" }}>
        {/* 標題與新增按鈕 */}
        <Box sx={{ display: "flex", justifyContent: "space-between", alignItems: "center", mb: 2 }}>
          <Typography variant="h6" sx={{ fontWeight: "bold" }}>角色列表</Typography>
          <Button
            variant="contained"
            sx={{
              backgroundColor: "#4A4A4A",
              color: "white",
              fontWeight: "bold",
              px: isSmallScreen ? 2 : 3,
              borderRadius: "10px",
              fontSize: isSmallScreen ? "0.8rem" : "1rem",
            }}
            onClick={() => setOpenAddDialog(true)}
          >
            新增
          </Button>
        </Box>

        {/* 新增角色對話框 */}
        <Dialog open={openAddDialog} onClose={() => setOpenAddDialog(false)} fullWidth maxWidth={isSmallScreen ? "xs" : "md"}>
          <DialogContent sx={{ backgroundColor: "#D2E4F0", padding: "20px", maxHeight: "70vh", overflowY: "auto" }}>
            <Typography variant="h6" sx={{ fontWeight: "bold" }}>角色名稱</Typography>
            <TextField
              variant="outlined"
              placeholder="輸入新增的角色名稱"
              fullWidth
              value={newPermissionName}
              onChange={(e) => setNewPermissionName(e.target.value)}
              sx={{ backgroundColor: "white", mb: 2 }}
            />
            <Typography variant="h6" sx={{ fontWeight: "bold", mt: 2 }}>權限選擇：</Typography>
            {/* 權限分組顯示 */}
            {Object.entries(permissionGroups).map(([group, perms]) => (
              <Box key={group} sx={{ mt: 2 }}>
                <Typography variant="h6" sx={{ fontWeight: "bold", backgroundColor: "#A0C4FF", padding: "5px", borderRadius: "5px" }}>
                  {group}
                </Typography>
                <Box
                  sx={{
                    display: "grid",
                    gridTemplateColumns: isSmallScreen ? "1fr" : isMediumScreen ? "1fr 1fr" : "1fr 1fr 1fr",
                    gap: "10px",
                    mt: 1,
                  }}
                >
                  {perms.map(({ id, name }) => (
                    <Box key={`${group}-${id}`} sx={{ display: "flex", alignItems: "center" }}>
                      <Checkbox
                        checked={selectedNewPermissions.includes(id)}
                        onChange={() => handleToggleNewPermission(id)}
                      />
                      <Typography sx={{ fontSize: isSmallScreen ? "0.9rem" : "1rem" }}>{name}</Typography>
                    </Box>
                  ))}
                </Box>
              </Box>
            ))}
          </DialogContent>
          <DialogActions sx={{ backgroundColor: "#D2E4F0", padding: "10px", justifyContent: "center" }}>
            <Button
              variant="contained"
              sx={{ backgroundColor: "#BCA28C", color: "white", fontWeight: "bold", width: "80%" }}
              onClick={handleAddPermission}
            >
              <AddCircleIcon sx={{ mr: 1 }} /> 確認
            </Button>
          </DialogActions>
        </Dialog>

        {/* 角色表格：顯示當前頁的角色資料 */}
        <TableContainer sx={{ maxHeight: "400px", overflowX: "auto" }}>
          <Table stickyHeader>
            <TableHead>
              <TableRow>
                <TableCell sx={{ fontSize: isSmallScreen ? "0.8rem" : "1rem", minWidth: "80px" }}>角色 ID</TableCell>
                <TableCell sx={{ fontSize: isSmallScreen ? "0.8rem" : "1rem", minWidth: "120px" }}>角色名稱</TableCell>
                <TableCell sx={{ fontSize: isSmallScreen ? "0.8rem" : "1rem", minWidth: "100px" }}>操作</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {currentPermissions.length > 0 ? (
                currentPermissions.map((permission) => (
                  <TableRow key={permission.id}>
                    <TableCell sx={{ fontSize: isSmallScreen ? "0.8rem" : "1rem" }}>{permission.id}</TableCell>
                    <TableCell sx={{ fontSize: isSmallScreen ? "0.8rem" : "1rem" }}>{permission.name}</TableCell>
                    <TableCell>
                      <Button
                        variant="contained"
                        sx={{
                          backgroundColor: "#BCA28C",
                          color: "white",
                          fontWeight: "bold",
                          borderRadius: "10px",
                          px: isSmallScreen ? 1 : 2,
                          fontSize: isSmallScreen ? "0.7rem" : "0.875rem",
                        }}
                        onClick={() => handleEditOpen(permission)}
                      >
                        編輯
                      </Button>
                    </TableCell>
                  </TableRow>
                ))
              ) : (
                <TableRow>
                  <TableCell colSpan={3} align="center">
                    尚無角色資料
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        </TableContainer>

        {/* 分頁導航：顯示頁數選擇器 */}
        {permissions.length > 0 && (
          <Box sx={{ display: "flex", justifyContent: "center", mt: 2 }}>
            <Pagination
              count={totalPages}
              page={currentPage}
              onChange={handlePageChange}
              color="primary"
              size={isSmallScreen ? "small" : "medium"}
            />
          </Box>
        )}
      </Paper>

      {/* 編輯角色對話框 */}
      <Dialog open={openEditDialog} onClose={() => setOpenEditDialog(false)} fullWidth maxWidth={isSmallScreen ? "xs" : "md"}>
        <DialogContent sx={{ backgroundColor: "#D2E4F0", padding: "20px", maxHeight: "70vh", overflowY: "auto" }}>
          <Typography variant="h6" sx={{ fontWeight: "bold" }}>角色名稱</Typography>
          <TextField
            fullWidth
            value={editPermissionName}
            onChange={(e) => setEditPermissionName(e.target.value)} // 允許編輯角色名稱
            sx={{ backgroundColor: "white", mb: 2 }}
          />
          <Typography variant="h6" sx={{ fontWeight: "bold", mt: 2 }}>權限選擇：</Typography>
          {Object.entries(permissionGroups).map(([group, perms]) => (
            <Box key={group} sx={{ mt: 2 }}>
              <Typography variant="h6" sx={{ fontWeight: "bold", backgroundColor: "#A0C4FF", padding: "5px", borderRadius: "5px" }}>
                {group}
              </Typography>
              <Box
                sx={{
                  display: "grid",
                  gridTemplateColumns: isSmallScreen ? "1fr" : isMediumScreen ? "1fr 1fr" : "1fr 1fr 1fr",
                  gap: "10px",
                  mt: 1,
                }}
              >
                {perms.map(({ id, name }) => (
                  <Box key={`${group}-${id}`} sx={{ display: "flex", alignItems: "center" }}>
                    <Checkbox checked={selectedEditPermissions.includes(id)} onChange={() => handleToggleEditPermission(id)} />
                    <Typography sx={{ fontSize: isSmallScreen ? "0.9rem" : "1rem" }}>{name}</Typography>
                  </Box>
                ))}
              </Box>
            </Box>
          ))}
        </DialogContent>
        <DialogActions sx={{ backgroundColor: "#D2E4F0", padding: "10px", justifyContent: "center" }}>
          <Button
            variant="contained"
            sx={{ backgroundColor: "#BCA28C", color: "white", fontWeight: "bold", width: "80%" }}
            onClick={handleSaveEdit}
          >
            確認
          </Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
}

export default RolePermissionsPage;