import { useState, useEffect } from "react"; // React Hook 用於管理元件的內部狀態和副作用
import { useAtom } from "jotai"; // 從 Jotai 引入 `useAtom`，用來讀取 `authAtom`
import { authAtom } from "../state/authAtom"; // Jotai Atom 用於存儲身份驗證狀態
import { useMediaQuery } from "@mui/material"; // 用於檢查螢幕尺寸，實現 RWD
import { Navigate } from "react-router-dom"; // 用於權限檢查失敗時跳轉
import API from "../api/axios"; // Axios 實例，用於發送 API 請求
import { Link } from "react-router-dom";

// **Material UI 元件**
import {
  Box, // 佈局容器 (類似 div)
  Paper, // 用於包裝內容，提供陰影與邊框效果
  Typography, // 文字標題
  Button,
  TableContainer,
  Table,
  TableHead,
  TableRow,
  TableCell,
  TableBody,
  TableSortLabel,
  Dialog,
  DialogActions,
  DialogContent,
  TextField,
  Pagination, // 用於分頁功能
} from "@mui/material";

function DepartmentManagementPage() {
  // **Jotai - 全局狀態管理**
  const [authState] = useAtom(authAtom); // 從 Jotai 獲取身份驗證狀態

  // 檢查使用者權限
  const userPermissions = authState?.roles_permissions?.permissions || [];
  const hasManageDepartmentsPermission = userPermissions.includes("manage_departments");

  // 如果沒有 manage_departments 權限，跳轉到 404 頁面
  if (!hasManageDepartmentsPermission) {
    return <Navigate to="/404" replace />;
  }

  // **狀態管理**
  const [departments, setDepartments] = useState([]); // 儲存部門資料
  const [openEditDialog, setOpenEditDialog] = useState(false); // 控制編輯部門對話框
  const [editDepartment, setEditDepartment] = useState(null); // 當前編輯的部門
  const [editName, setEditName] = useState(""); // 編輯部門名稱
  const [openAddDialog, setOpenAddDialog] = useState(false); // 控制新增部門對話框
  const [newDepartmentName, setNewDepartmentName] = useState(""); // 新增部門名稱
  const [orderBy, setOrderBy] = useState("id"); // 排序欄位 (預設為 ID)
  const [order, setOrder] = useState("asc"); // 排序方式 (asc = 升序, desc = 降序)
  const [currentPage, setCurrentPage] = useState(1); // 當前頁數（分頁用）
  const itemsPerPage = 10; // 每頁顯示的資料筆數

  // 分頁計算：計算當前頁的資料範圍和總頁數
  const indexOfLastItem = currentPage * itemsPerPage;
  const indexOfFirstItem = indexOfLastItem - itemsPerPage;
  const currentDepartments = departments.slice(indexOfFirstItem, indexOfLastItem);
  const totalPages = Math.ceil(departments.length / itemsPerPage);

  // 初始載入部門列表
  useEffect(() => {
    API.get("/departments")
      .then((res) => {
        setDepartments(res.data.departments); // 直接使用後端返回的部門資料
      })
      .catch((err) => {
        console.error("取得部門列表失敗", err);
        if (err.response?.status === 401) {
          alert("未授權，請重新登入");
        } else {
          alert("無法載入部門列表，請稍後再試");
        }
      });
  }, []);

  // 點擊「編輯」按鈕
  const handleEditOpen = (dept) => {
    setEditDepartment(dept);
    setEditName(dept.name); // 設定預設名稱
    setOpenEditDialog(true); // 開啟對話框
  };

  // 點擊「保存」，更新部門名稱
  const handleSave = async () => {
    try {
      await API.patch(`/departments/${editDepartment.id}`, { name: editName });

      // 更新本地部門列表
      setDepartments(
        departments.map((dept) =>
          dept.id === editDepartment.id
            ? { ...dept, name: editName, updated_at: new Date().toISOString() }
            : dept
        )
      );
      setOpenEditDialog(false); // 關閉對話框
    } catch (error) {
      console.error("編輯部門失敗：", error);
      if (error.response?.status === 404) {
        alert("部門不存在，請重新整理頁面");
      } else if (error.response?.status === 422) {
        alert("部門名稱已存在，請使用其他名稱");
      } else if (error.response?.status === 403) {
        alert("您沒有權限執行此操作");
      } else {
        alert("編輯部門失敗，請稍後再試");
      }
    }
  };

  // 點擊「刪除」，刪除部門
  const handleDelete = async (id) => {
    try {
      await API.delete(`/departments/${id}`);
      setDepartments(departments.filter((dept) => dept.id !== id)); // 更新本地列表
    } catch (error) {
      console.error("刪除部門失敗：", error);
      if (error.response?.status === 404) {
        alert("部門不存在，請重新整理頁面");
      } else if (error.response?.status === 403) {
        alert("您沒有權限執行此操作");
      } else {
        alert("刪除部門失敗，請稍後再試");
      }
    }
  };

  // 排序函式
  const handleSort = (column) => {
    if (orderBy === column) {
      setOrder(order === "asc" ? "desc" : "asc"); // 如果點擊同一欄，切換排序
    } else {
      setOrderBy(column);
      setOrder("asc"); // 點擊新欄位，預設從小到大排序
    }
  };

  // 根據排序條件處理部門列表
  const sortedDepartments = [...currentDepartments].sort((a, b) => {
    let valA = a[orderBy];
    let valB = b[orderBy];

    // 日期欄位需要轉換為時間戳記
    if (orderBy === "created_at" || orderBy === "updated_at") {
      valA = new Date(valA).getTime();
      valB = new Date(valB).getTime();
    }

    if (valA < valB) return order === "asc" ? -1 : 1;
    if (valA > valB) return order === "asc" ? 1 : -1;
    return 0;
  });

  // 新增部門
  const handleAddDepartment = async () => {
    if (!newDepartmentName.trim()) {
      alert("請輸入部門名稱！");
      return;
    }

    try {
      const payload = {
        name: newDepartmentName,
      };
      await API.post("/departments", payload);

      // 重新獲取部門列表
      const res = await API.get("/departments");
      setDepartments(res.data.departments);
      setCurrentPage(1); // 新增後回到第一頁

      // 關閉對話框並清空輸入框
      setOpenAddDialog(false);
      setNewDepartmentName("");
    } catch (error) {
      console.error("新增部門失敗：", error);
      if (error.response?.status === 422) {
        alert("部門名稱已存在，請使用其他名稱");
      } else if (error.response?.status === 403) {
        alert("您沒有權限執行此操作");
      } else {
        alert("新增部門失敗，請稍後再試");
      }
    }
  };

  // 處理分頁切換
  const handlePageChange = (event, value) => {
    setCurrentPage(value);
  };

  // RWD 檢查：判斷螢幕尺寸
  const isSmallScreen = useMediaQuery("(max-width: 600px)");
  const isMediumScreen = useMediaQuery("(max-width: 960px)");

  return (
    <Box
      sx={{
        width: "100%", // 佔滿整個視口寬度
        minHeight: "100vh", // 至少填滿視窗高度
        display: "flex", // 啟用 Flexbox
        flexDirection: "column", // 讓內容垂直排列
        alignItems: "center",
        backgroundColor: "#ffffff", // 背景顏色
        p: isSmallScreen ? 1 : 2, // 小螢幕時減少內距
      }}
    >
      {/* 標題列 - RWD 調整 */}
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
          <Link
            to="/department/management"
            style={{
              textDecoration: "none",
              color: "#ba6262",
              fontWeight: "bold",
              display: isSmallScreen ? "block" : "inline",
            }}
          >
            部門管理
          </Link>
          {isSmallScreen ? <br /> : " "}
          <Link
            to="/position/management"
            style={{
              textDecoration: "none",
              color: "black",
              display: isSmallScreen ? "block" : "inline",
            }}
          >
            職位管理
          </Link>
          {isSmallScreen ? <br /> : " "}
          <Link
            to="/role/permissions"
            style={{
              textDecoration: "none",
              color: "black",
              display: isSmallScreen ? "block" : "inline",
            }}
          >
            權限管理
          </Link>
          {isSmallScreen ? <br /> : " "}
          <Link
            to="/user/management"
            style={{
              textDecoration: "none",
              color: "black",
              display: isSmallScreen ? "block" : "inline",
            }}
          >
            人員管理
          </Link>
          {isSmallScreen ? <br /> : " "}
          <Link
            to="/employee/history"
            style={{
              textDecoration: "none",
              color: "black",
              display: isSmallScreen ? "block" : "inline",
            }}
          >
            人員歷程
          </Link>
        </Typography>
      </Box>

      {/* 部門列表容器 */}
      <Paper
        sx={{
          width: isSmallScreen ? "100%" : "90%", // 小螢幕時佔滿寬度
          padding: isSmallScreen ? "10px" : "20px", // 小螢幕時減少內距
          boxShadow: "0px -4px 10px rgba(0, 0, 0, 0.3)", // 上方陰影
          borderRadius: "8px",
        }}
      >
        <Box
          sx={{
            display: "flex",
            justifyContent: "space-between",
            alignItems: "center",
            mb: 2,
          }}
        >
          <Typography variant="h6" sx={{ fontWeight: "bold" }}>
            部門列表
          </Typography>
          <Button
            variant="contained"
            sx={{
              backgroundColor: "#4A4A4A",
              color: "white",
              fontWeight: "bold",
              px: isSmallScreen ? 2 : 3, // 小螢幕時減少按鈕內距
              borderRadius: "10px",
              fontSize: isSmallScreen ? "0.8rem" : "1rem", // 小螢幕時縮小字體
            }}
            onClick={() => setOpenAddDialog(true)} // 點擊開啟對話框
          >
            新增
          </Button>
        </Box>

        {/* 新增部門對話框 */}
        <Dialog
          open={openAddDialog}
          onClose={() => setOpenAddDialog(false)}
          fullWidth
          maxWidth={isSmallScreen ? "xs" : "sm"} // 小螢幕時縮小對話框
        >
          <DialogContent
            sx={{
              backgroundColor: "#D2E4F0",
              padding: "20px",
              display: "flex",
              flexDirection: "column",
              gap: 2,
            }}
          >
            <Typography variant="h6" sx={{ fontWeight: "bold" }}>
              部門
            </Typography>
            <TextField
              variant="outlined"
              placeholder="輸入新增的部門名稱"
              fullWidth
              value={newDepartmentName}
              onChange={(e) => setNewDepartmentName(e.target.value)}
              sx={{ backgroundColor: "white" }}
            />
          </DialogContent>

          <DialogActions
            sx={{
              justifyContent: "center",
              backgroundColor: "#D2E4F0",
              padding: "10px",
            }}
          >
            <Button
              variant="contained"
              sx={{
                backgroundColor: "#BCA28C",
                color: "white",
                fontWeight: "bold",
                width: "80%",
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
                marginBottom: "5px",
              }}
              onClick={handleAddDepartment}
            >
              新增
            </Button>
          </DialogActions>
        </Dialog>

        {/* 部門表格 - RWD 調整 */}
        <TableContainer sx={{ maxHeight: "400px", overflowX: "auto" }}>
          <Table stickyHeader>
            <TableHead>
              <TableRow>
                {["id", "name", "created_at", "updated_at"].map((column) => (
                  <TableCell
                    key={column}
                    sx={{
                      fontWeight: "bold",
                      fontSize: isSmallScreen ? "0.8rem" : "1rem",
                      minWidth: column === "id" ? "80px" : "120px",
                    }}
                  >
                    <TableSortLabel
                      active={orderBy === column}
                      direction={orderBy === column ? order : "asc"}
                      onClick={() => handleSort(column)}
                    >
                      {column === "id"
                        ? "部門 ID"
                        : column === "name"
                        ? "部門"
                        : column === "created_at"
                        ? "建立時間"
                        : "更新時間"}
                    </TableSortLabel>
                  </TableCell>
                ))}
                <TableCell
                  sx={{
                    fontWeight: "bold",
                    fontSize: isSmallScreen ? "0.8rem" : "1rem",
                    minWidth: "100px",
                  }}
                >
                  操作
                </TableCell>
              </TableRow>
            </TableHead>

            <TableBody>
              {sortedDepartments.length > 0 ? (
                sortedDepartments.map((dept) => (
                  <TableRow key={dept.id}>
                    <TableCell sx={{ fontSize: isSmallScreen ? "0.8rem" : "1rem" }}>
                      {dept.id}
                    </TableCell>
                    <TableCell sx={{ fontSize: isSmallScreen ? "0.8rem" : "1rem" }}>
                      {dept.name}
                    </TableCell>
                    <TableCell sx={{ fontSize: isSmallScreen ? "0.8rem" : "1rem" }}>
                      {new Date(dept.created_at).toLocaleDateString("zh-TW")}
                    </TableCell>
                    <TableCell sx={{ fontSize: isSmallScreen ? "0.8rem" : "1rem" }}>
                      {new Date(dept.updated_at).toLocaleDateString("zh-TW")}
                    </TableCell>
                    <TableCell>
                      <Button
                        variant="contained"
                        sx={{
                          backgroundColor: "#BCA28C",
                          color: "white",
                          fontWeight: "bold",
                          borderRadius: "10px",
                          mr: 1,
                          px: isSmallScreen ? 1 : 2, // 小螢幕時減少按鈕內距
                          fontSize: isSmallScreen ? "0.7rem" : "0.875rem", // 小螢幕時縮小字體
                        }}
                        onClick={() => handleEditOpen(dept)}
                      >
                        編輯
                      </Button>
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
                        onClick={() => handleDelete(dept.id)}
                      >
                        刪除
                      </Button>
                    </TableCell>
                  </TableRow>
                ))
              ) : (
                <TableRow>
                  <TableCell colSpan={4} align="center">
                    尚無部門資料
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        </TableContainer>

        {/* 分頁導航 */}
        {departments.length > 0 && (
          <Box sx={{ display: "flex", justifyContent: "center", mt: 2 }}>
            <Pagination
              count={totalPages}
              page={currentPage}
              onChange={handlePageChange}
              color="primary"
              size={isSmallScreen ? "small" : "medium"} // 小螢幕時縮小分頁按鈕
            />
          </Box>
        )}
      </Paper>

      {/* 編輯部門對話框 */}
      <Dialog
        open={openEditDialog}
        onClose={() => setOpenEditDialog(false)}
        fullWidth
        maxWidth={isSmallScreen ? "xs" : "sm"}
      >
        <DialogContent
          sx={{
            backgroundColor: "#D2E4F0",
            padding: "20px",
            display: "flex",
            flexDirection: "column",
            gap: 2,
          }}
        >
          <Typography variant="h6" sx={{ fontWeight: "bold" }}>
            部門
          </Typography>
          <TextField
            variant="outlined"
            placeholder="輸入修改的部門名稱"
            fullWidth
            value={editName}
            onChange={(e) => setEditName(e.target.value)}
            sx={{ backgroundColor: "white" }}
          />
        </DialogContent>

        <DialogActions
          sx={{
            justifyContent: "center",
            backgroundColor: "#D2E4F0",
            padding: "10px",
          }}
        >
          <Button
            variant="contained"
            sx={{
              backgroundColor: "#BCA28C",
              color: "white",
              fontWeight: "bold",
              width: "80%",
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
              marginBottom: "5px",
            }}
            onClick={handleSave}
          >
            保存
          </Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
}

export default DepartmentManagementPage;