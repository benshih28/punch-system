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
  Dialog,
  DialogActions,
  DialogContent,
  TextField,
  FormControl,
  Select,
  MenuItem,
  Pagination, // 用於分頁功能
} from "@mui/material";

function PositionManagementPage() {
  // **Jotai - 全局狀態管理**
  const [authState] = useAtom(authAtom); // 從 Jotai 獲取身份驗證狀態

  // 檢查使用者權限
  const userPermissions = authState?.roles_permissions?.permissions || [];
  const hasManagePositionsPermission = userPermissions.includes("manage_positions");

  // 如果沒有 manage_positions 權限，跳轉到 404 頁面
  if (!hasManagePositionsPermission) {
    return <Navigate to="/404" replace />;
  }

  // **狀態管理**
  const [departments, setDepartments] = useState([]); // 儲存部門資料
  const [positions, setPositions] = useState([]); // 儲存職位資料
  const [openAddDialog, setOpenAddDialog] = useState(false); // 控制新增對話框
  const [selectedDepartment, setSelectedDepartment] = useState(""); // 新增職位時選擇的部門
  const [newPosition, setNewPosition] = useState(""); // 新增職位名稱
  const [openEditDialog, setOpenEditDialog] = useState(false); // 控制編輯對話框
  const [editPosition, setEditPosition] = useState(null); // 當前編輯的職位
  const [editDepartment, setEditDepartment] = useState(""); // 編輯時選擇的部門
  const [editName, setEditName] = useState(""); // 編輯職位名稱
  const [currentPage, setCurrentPage] = useState(1); // 當前頁數（分頁用）
  const itemsPerPage = 10; // 每頁顯示的資料筆數

  // 分頁計算：計算當前頁的資料範圍和總頁數
  const indexOfLastItem = currentPage * itemsPerPage;
  const indexOfFirstItem = indexOfLastItem - itemsPerPage;
  const currentPositions = positions.slice(indexOfFirstItem, indexOfLastItem);
  const totalPages = Math.ceil(positions.length / itemsPerPage);

  // 初始載入部門和職位列表
  useEffect(() => {
    // 獲取部門列表
    API.get("/departments")
      .then((res) => {
        setDepartments(res.data.departments);
      })
      .catch((err) => {
        console.error("取得部門列表失敗", err);
        if (err.response?.status === 401) {
          alert("未授權，請重新登入");
        } else {
          alert("無法載入部門列表，請稍後再試");
        }
      });

    // 獲取職位列表
    API.get("/positions")
      .then((res) => {
        setPositions(res.data.positions);
      })
      .catch((err) => {
        console.error("取得職位列表失敗", err);
        if (err.response?.status === 401) {
          alert("未授權，請重新登入");
        } else {
          alert("無法載入職位列表，請稍後再試");
        }
      });
  }, []);

  // 點擊「編輯」按鈕
  const handleEditOpen = (position) => {
    setEditPosition(position);
    setEditDepartment(position.department?.name || ""); // 部門可能為 null
    setEditName(position.name);
    setOpenEditDialog(true);
  };

  // 點擊「保存」，更新職位資料
  const handleSaveEdit = async () => {
    const department = departments.find((dept) => dept.name === editDepartment);
    const newDepartmentId = department ? department.id : null;
    const originalDepartmentId = editPosition.department ? editPosition.department.id : null;

    if (editName === editPosition.name && newDepartmentId === originalDepartmentId) {
        alert("未做任何修改");
        setOpenEditDialog(false);
        return;
    }

    try {
        const payload = {
            name: editName,
            department_id: newDepartmentId,
        };
        await API.patch(`/positions/${editPosition.id}`, payload);

        const res = await API.get("/positions");
        setPositions(res.data.positions);
        setOpenEditDialog(false);
    } catch (error) {
        console.error("編輯職位失敗：", error);
        if (error.response?.status === 404) {
            alert("職位不存在，請重新整理頁面");
        } else if (error.response?.status === 422) {
            alert("職位名稱已存在，請使用其他名稱");
        } else if (error.response?.status === 403) {
            alert("您沒有權限執行此操作");
        } else {
            alert("編輯職位失敗，請稍後再試");
        }
    }
};

  // 點擊「刪除」，刪除職位
  const handleDelete = async (id) => {
    try {
      await API.delete(`/positions/${id}`);
      setPositions(positions.filter((pos) => pos.id !== id));
    } catch (error) {
      console.error("刪除職位失敗：", error);
      if (error.response?.status === 404) {
        alert("職位不存在，請重新整理頁面");
      } else if (error.response?.status === 403) {
        alert("您沒有權限執行此操作");
      } else {
        alert("刪除職位失敗，請稍後再試");
      }
    }
  };

  // 新增職位
  const handleAddPosition = async () => {
    if (!newPosition.trim()) {
      alert("請輸入職位名稱！");
      return;
    }

    try {
      const department = departments.find((dept) => dept.name === selectedDepartment);
      const payload = {
        name: newPosition,
        department_id: department ? department.id : null, // 如果未選擇部門，設為 null
      };
      await API.post("/positions", payload);

      // 重新獲取職位列表
      const res = await API.get("/positions");
      setPositions(res.data.positions);
      setCurrentPage(1); // 新增後回到第一頁

      // 關閉對話框並清空輸入框
      setOpenAddDialog(false);
      setSelectedDepartment("");
      setNewPosition("");
    } catch (error) {
      console.error("新增職位失敗：", error);
      if (error.response?.status === 422) {
        alert("職位名稱已存在，請使用其他名稱");
      } else if (error.response?.status === 403) {
        alert("您沒有權限執行此操作");
      } else {
        alert("新增職位失敗，請稍後再試");
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
              color: "black",
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
              color: "#ba6262",
              fontWeight: "bold",
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

      {/* 職位列表容器 */}
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
            職位列表
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

        {/* 新增職位對話框 */}
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
            <FormControl fullWidth sx={{ backgroundColor: "white" }}>
              <Select
                value={selectedDepartment}
                onChange={(e) => setSelectedDepartment(e.target.value)}
                displayEmpty
              >
                <MenuItem value="">無部門</MenuItem>
                {departments.map((dept) => (
                  <MenuItem key={dept.id} value={dept.name}>
                    {dept.name}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>

            <Typography variant="h6" sx={{ fontWeight: "bold" }}>
              職位
            </Typography>
            <TextField
              variant="outlined"
              placeholder="輸入新增的職位名稱"
              fullWidth
              value={newPosition}
              onChange={(e) => setNewPosition(e.target.value)}
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
              onClick={handleAddPosition}
            >
              新增
            </Button>
          </DialogActions>
        </Dialog>

        {/* 職位表格 - RWD 調整 */}
        <TableContainer sx={{ maxHeight: "400px", overflowX: "auto" }}>
          <Table stickyHeader>
            <TableHead>
              <TableRow>
                <TableCell
                  sx={{
                    fontWeight: "bold",
                    fontSize: isSmallScreen ? "0.8rem" : "1rem",
                    minWidth: "80px",
                  }}
                >
                  職位 ID
                </TableCell>
                <TableCell
                  sx={{
                    fontWeight: "bold",
                    fontSize: isSmallScreen ? "0.8rem" : "1rem",
                    minWidth: "120px",
                  }}
                >
                  部門
                </TableCell>
                <TableCell
                  sx={{
                    fontWeight: "bold",
                    fontSize: isSmallScreen ? "0.8rem" : "1rem",
                    minWidth: "120px",
                  }}
                >
                  職位
                </TableCell>
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
              {currentPositions.length > 0 ? (
                currentPositions.map((pos) => (
                  <TableRow key={pos.id}>
                    <TableCell sx={{ fontSize: isSmallScreen ? "0.8rem" : "1rem" }}>
                      {pos.id}
                    </TableCell>
                    <TableCell sx={{ fontSize: isSmallScreen ? "0.8rem" : "1rem" }}>
                      {pos.department?.name || "無部門"}
                    </TableCell>
                    <TableCell sx={{ fontSize: isSmallScreen ? "0.8rem" : "1rem" }}>
                      {pos.name}
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
                        onClick={() => handleEditOpen(pos)}
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
                        onClick={() => handleDelete(pos.id)}
                      >
                        刪除
                      </Button>
                    </TableCell>
                  </TableRow>
                ))
              ) : (
                <TableRow>
                  <TableCell colSpan={4} align="center">
                    尚無職位資料
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        </TableContainer>

        {/* 分頁導航 */}
        {positions.length > 0 && (
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

      {/* 編輯職位對話框 */}
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
          <FormControl fullWidth sx={{ backgroundColor: "white" }}>
            <Select
              value={editDepartment}
              onChange={(e) => setEditDepartment(e.target.value)}
              displayEmpty
            >
              <MenuItem value="">無部門</MenuItem>
              {departments.map((dept) => (
                <MenuItem key={dept.id} value={dept.name}>
                  {dept.name}
                </MenuItem>
              ))}
            </Select>
          </FormControl>

          <Typography variant="h6" sx={{ fontWeight: "bold" }}>
            職位
          </Typography>
          <TextField
            variant="outlined"
            placeholder="輸入修改的職位名稱"
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
            onClick={handleSaveEdit}
          >
            保存
          </Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
}

export default PositionManagementPage;