import { useState } from "react"; // React Hook 用於管理元件的內部狀態
import { useForm } from "react-hook-form"; // React Hook Form 用於表單管理
import { useAtom } from "jotai"; // 從 Jotai 引入 `useAtom`，用來讀取 `authAtom`
import { authAtom } from "../state/authAtom"; // Jotai Atom 用於存儲身份驗證狀態
import API from "../api/axios"; // Axios 實例，用於發送 API 請求

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
  Checkbox,
  Dialog,
  DialogActions,
  DialogContent,
  TextField,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
} from "@mui/material";
import CheckCircleIcon from "@mui/icons-material/CheckCircle"; // ✅ 圖示
import AddCircleIcon from "@mui/icons-material/AddCircle"; // 圖示

function PositionManagement() {
  const [departments, setDepartments] = useState([
    { id: 1, name: "人事部" },
    { id: 2, name: "財務部" },
    { id: 3, name: "研發部" },
  ]);

  const [positions, setPositions] = useState([
    { id: 1, department: "人事部", name: "主管", selected: false },
  ]);

  const [openAddDialog, setOpenAddDialog] = useState(false); //控制新增 Dialog
  const [selectedDepartment, setSelectedDepartment] = useState("");
  const [newPosition, setNewPosition] = useState(""); // 存儲新職位名稱
  const [openEditDialog, setOpenEditDialog] = useState(false); // 控制編輯 Dialog
  const [editPosition, setEditPosition] = useState(null); // 當前編輯的職位
  const [editDepartment, setEditDepartment] = useState(""); // 存儲選擇的部門
  const [editName, setEditName] = useState(""); // 存儲職位名稱
  const [selectAll, setSelectAll] = useState(false); // 是否全選

  // 點擊「編輯」按鈕，開啟 Dialog 並設定值
  const handleEditOpen = (position) => {
    setEditPosition(position);
    setEditDepartment(position.department);
    setEditName(position.name);
    setOpenEditDialog(true);
  };

  // 點擊「保存」，更新職位資料
  const handleSaveEdit = () => {
    setPositions(
      positions.map((pos) =>
        pos.id === editPosition.id
          ? { ...pos, department: editDepartment, name: editName }
          : pos
      )
    );
    setOpenEditDialog(false);
  };

  // 點擊「刪除」，刪除該列職位資料
  const handleDelete = (id) => {
    setPositions(positions.filter((pos) => pos.id !== id));
  };

  const handleSelectAll = () => {
    const newSelectAll = !selectAll;
    setSelectAll(newSelectAll);
    setPositions(positions.map((pos) => ({ ...pos, selected: newSelectAll })));
  };

  const handleSelectOne = (id) => {
    const updatedPositions = positions.map((pos) =>
      pos.id === id ? { ...pos, selected: !pos.selected } : pos
    );

    setPositions(updatedPositions);

    // 如果所有項目都選取，則「全選」Checkbox 也應該被勾選，否則取消勾選
    const allSelected = updatedPositions.every((pos) => pos.selected);
    setSelectAll(allSelected);
  };

  const handleAddPosition = () => {
    if (!selectedDepartment || !newPosition.trim()) {
      alert("請選擇部門並輸入職位名稱！");
      return;
    }
    setPositions([
      ...positions,
      {
        id: positions.length + 1,
        department: selectedDepartment,
        name: newPosition,
        selected: false,
      },
    ]);
    setOpenAddDialog(false);
    setSelectedDepartment("");
    setNewPosition("");
  };

  return (
    <Box
      sx={{
        width: "100%", // 佔滿整個視口寬度
        height: "100%", // 佔滿整個視口高度
        display: "flex", // 啟用 Flexbox
        flexDirection: "column", // 讓內容垂直排列
        alignItems: "center",
        backgroundColor: "#ffffff", // 背景顏色
      }}
    >
      {/* 標題列 */}
      <Box
        sx={{
          display: "flex",
          margin: "60px 0px 40px",
          width: "90%",
          justifyContent: "space-between",
          alignItems: "center",
        }}
      >
        <Typography
          variant="h4"
          fontWeight={900}
          textAlign="center"
          sx={{ mb: 1 }}
        >
          部門管理 <span style={{ color: "#ba6262" }}>職位管理</span> 權限管理
          人員管理 人員歷程
        </Typography>
      </Box>

      {/* 表格容器 */}
      <Paper
        sx={{
          width: "90%",
          padding: "20px",
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
              px: 3,
              borderRadius: "10px",
            }}
            onClick={() => setOpenAddDialog(true)} // 點擊開啟 Dialog
          >
            新增
          </Button>
        </Box>

        {/* 新增職位 Dialog */}
        <Dialog open={openAddDialog} onClose={() => setOpenAddDialog(false)}>
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
            {/* 部門選擇下拉框 */}
            <FormControl fullWidth sx={{ backgroundColor: "white" }}>
              <InputLabel>請選擇部門</InputLabel>
              <Select
                value={selectedDepartment}
                onChange={(e) => setSelectedDepartment(e.target.value)}
              >
                {departments.map((dept) => (
                  <MenuItem key={dept.id} value={dept.name}>
                    {dept.name}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>

            {/* 職位名稱輸入框 */}
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
              <AddCircleIcon sx={{ mr: 1 }} /> 新增
            </Button>
          </DialogActions>
        </Dialog>

        {/* 📌 表格 */}
        <TableContainer>
          <Table>
            {/* 🔼 表頭 */}
            <TableHead>
              <TableRow sx={{ backgroundColor: "#F5F5F5" }}>
                <TableCell sx={{ width: "5%", textAlign: "center" }}>
                  <Checkbox checked={selectAll} onChange={handleSelectAll} />
                </TableCell>
                <TableCell
                  sx={{ width: "25%", fontWeight: "bold", textAlign: "left" }}
                >
                  部門
                </TableCell>
                <TableCell
                  sx={{ width: "25%", fontWeight: "bold", textAlign: "left" }}
                >
                  職位
                </TableCell>
                <TableCell
                  sx={{
                    width: "30%",
                    fontWeight: "bold",
                    textAlign: "center",
                  }}
                >
                  操作
                </TableCell>
              </TableRow>
            </TableHead>

            {/* 📌 表格內容 */}
            <TableBody>
              {positions.map((pos) => (
                <TableRow key={pos.id}>
                  <TableCell>
                    <Checkbox
                      checked={pos.selected}
                      onChange={() => handleSelectOne(pos.id)}
                    />
                  </TableCell>
                  <TableCell sx={{ textAlign: "left" }}>
                    {pos.department}
                  </TableCell>
                  <TableCell sx={{ textAlign: "left" }}>{pos.name}</TableCell>
                  <TableCell sx={{ textAlign: "center" }}>
                    <Button
                      variant="contained"
                      sx={{
                        backgroundColor: "#BCA28C",
                        color: "white",
                        fontWeight: "bold",
                        borderRadius: "10px",
                        mr: 1,
                        px: 2,
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
                        px: 2,
                      }}
                      onClick={() => handleDelete(pos.id)}
                    >
                      刪除
                    </Button>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </TableContainer>

        {/* 編輯 Dialog */}
        <Dialog open={openEditDialog} onClose={() => setOpenEditDialog(false)}>
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
            {/* 部門選擇 */}
            <FormControl fullWidth sx={{ backgroundColor: "white" }}>
              <Select
                value={editDepartment}
                onChange={(e) => setEditDepartment(e.target.value)}
              >
                {departments.map((dept) => (
                  <MenuItem key={dept.id} value={dept.name}>
                    {dept.name}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>

            {/* 職位名稱輸入框 */}
            <Typography variant="h6" sx={{ fontWeight: "bold" }}>
              職位
            </Typography>
            <TextField
              variant="outlined"
              placeholder="請輸入修改的職位名稱"
              fullWidth
              value={editName}
              onChange={(e) => setEditName(e.target.value)}
              sx={{ backgroundColor: "white" }}
            />
          </DialogContent>

          {/* 📌 按鈕 */}
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
              <CheckCircleIcon sx={{ mr: 1 }} /> 保存
            </Button>
          </DialogActions>
        </Dialog>
      </Paper>
    </Box>
  );
}

export default PositionManagement;
