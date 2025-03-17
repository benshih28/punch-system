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
    TableSortLabel,
    Dialog,
    DialogActions,
    DialogContent,
    TextField,
} from "@mui/material";
import CheckCircleIcon from "@mui/icons-material/CheckCircle"; // ✅ 圖示

function DepartmentManagement() {

    const [departments, setDepartments] = useState([
        { id: 1, name: "人資部", created_at: "2024/01/02", updated_at: "2024/01/02", selected: false },
        { id: 2, name: "財務部", created_at: "2024/02/10", updated_at: "2024/02/12", selected: false },
        { id: 3, name: "研發部", created_at: "2024/03/15", updated_at: "2024/03/18", selected: false },
    ]);

    const [openEditDialog, setOpenEditDialog] = useState(false); // 控制 Dialog
    const [editDepartment, setEditDepartment] = useState(null); // 當前編輯部門
    const [editName, setEditName] = useState(""); // 編輯名稱
    const [openAddDialog, setOpenAddDialog] = useState(false); // 控制新增 Dialog
    const [newDepartmentName, setNewDepartmentName] = useState(""); // 存儲新部門名稱

    // 點擊「編輯」按鈕
    const handleEditOpen = (dept) => {
        setEditDepartment(dept);
        setEditName(dept.name); // 設定預設名稱
        setOpenEditDialog(true); // 開啟 Dialog
    };

    // 點擊「保存」，更新部門名稱
    const handleSave = () => {
        setDepartments(departments.map(dept =>
            dept.id === editDepartment.id ? { ...dept, name: editName, updated_at: new Date().toISOString().split("T")[0] } : dept
        ));
        setOpenEditDialog(false); // 關閉 Dialog
    };

    // 點擊「刪除」，過濾掉該筆資料
    const handleDelete = (id) => {
        setDepartments(departments.filter(dept => dept.id !== id));
    };

    const [orderBy, setOrderBy] = useState("id"); // 排序欄位 (預設為 ID)
    const [order, setOrder] = useState("asc"); // 排序方式 (asc = 升序, desc = 降序)
    const [selectAll, setSelectAll] = useState(false); // 是否全選


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
    const sortedDepartments = [...departments].sort((a, b) => {
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

    // 全選 / 取消全選
    const handleSelectAll = () => {
        const newSelectAll = !selectAll;
        setSelectAll(newSelectAll);
        setDepartments(departments.map(dept => ({ ...dept, selected: newSelectAll })));
    };

    // 個別選擇
    const handleSelectOne = (id) => {
        const updatedDepartments = departments.map(dept =>
            dept.id === id ? { ...dept, selected: !dept.selected } : dept
        );
        setDepartments(updatedDepartments);

        // 如果有任何一個沒選，就取消 `全選`，如果全部選了就勾選 `全選`
        const allSelected = updatedDepartments.every(dept => dept.selected);
        setSelectAll(allSelected);
    };

    const handleAddDepartment = () => {
        if (!newDepartmentName.trim()) {
            alert("請輸入部門名稱！");
            return;
        }

        // 產生新的 ID（根據最後一筆資料的 ID +1）
        const newId = departments.length > 0 ? departments[departments.length - 1].id + 1 : 1;

        // 新的部門物件
        const newDepartment = {
            id: newId,
            name: newDepartmentName,
            created_at: new Date().toISOString().split("T")[0], // 取得當前日期
            updated_at: new Date().toISOString().split("T")[0],
            selected: false,
        };

        // 更新 `departments`
        setDepartments([...departments, newDepartment]);

        // 關閉 Dialog 並清空輸入框
        setOpenAddDialog(false);
        setNewDepartmentName("");
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
            <Box sx={{ display: "flex", margin: "60px 0px 40px", width: "90%", justifyContent: "space-between", alignItems: "center" }}>
                <Typography
                    variant="h4"
                    fontWeight={900}
                    textAlign="center"
                    sx={{ mb: 1 }}
                >
                    <span style={{ color: "#ba6262" }}>部門管理</span> 職位管理 權限管理 人員管理 人員歷程
                </Typography>
            </Box>

            {/* 表格容器 */}
            <Paper
                sx={{
                    width: "90%", padding: "20px", boxShadow: "0px -4px 10px rgba(0, 0, 0, 0.3)", // 上方陰影
                    borderRadius: "8px"
                }}>
                <Box sx={{ display: "flex", justifyContent: "space-between", alignItems: "center", mb: 2 }}>
                    <Typography variant="h6" sx={{ fontWeight: "bold" }}>
                        部門列表
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
                <Dialog open={openAddDialog} onClose={() => setOpenAddDialog(false)}>
                    <DialogContent sx={{ backgroundColor: "#D2E4F0", padding: "20px", display: "flex", flexDirection: "column", gap: 2 }}>
                        <Typography variant="h6" sx={{ fontWeight: "bold" }}>部門</Typography>
                        <TextField
                            variant="outlined"
                            placeholder="輸入新增的部門名稱"
                            fullWidth
                            value={newDepartmentName}
                            onChange={(e) => setNewDepartmentName(e.target.value)}
                            sx={{ backgroundColor: "white" }}
                        />
                    </DialogContent>

                    <DialogActions sx={{ justifyContent: "center", backgroundColor: "#D2E4F0", padding: "10px" }}>
                        <Button
                            variant="contained"
                            sx={{
                                backgroundColor: "#BCA28C",
                                color: "white",
                                fontWeight: "bold",
                                width: "80%",
                                display: "flex",
                                alignItems: "center",
                                justifyContent: "center"
                            }}
                            onClick={handleAddDepartment}
                        >
                            <CheckCircleIcon sx={{ mr: 1 }} /> 新增
                        </Button>
                    </DialogActions>
                </Dialog>

                <TableContainer>
                    <Table>
                        {/* 表頭 */}
                        <TableHead>
                            <TableRow>
                                <TableCell>
                                    <Checkbox checked={selectAll} onChange={handleSelectAll} />
                                </TableCell>
                                {["id", "name", "created_at", "updated_at"].map((column) => (
                                    <TableCell key={column} sx={{ fontWeight: "bold" }}>
                                        <TableSortLabel
                                            active={orderBy === column} // 如果是當前排序欄位則顯示排序狀態
                                            direction={orderBy === column ? order : "asc"} // 當前排序方向
                                            onClick={() => handleSort(column)} // 點擊時切換排序
                                        >
                                            {column === "id" ? "部門ID" :
                                                column === "name" ? "部門" :
                                                    column === "created_at" ? "建立時間" :
                                                        "更新時間"}
                                        </TableSortLabel>
                                    </TableCell>
                                ))}
                                <TableCell sx={{ fontWeight: "bold" }}>操作</TableCell>
                            </TableRow>
                        </TableHead>

                        {/* 表格內容 */}
                        <TableBody>
                            {sortedDepartments.map((dept) => (
                                <TableRow key={dept.id}>
                                    <TableCell>
                                        <Checkbox checked={dept.selected} onChange={() => handleSelectOne(dept.id)} />
                                    </TableCell>
                                    <TableCell>{dept.id}</TableCell>
                                    <TableCell>{dept.name}</TableCell>
                                    <TableCell>{dept.created_at}</TableCell>
                                    <TableCell>{dept.updated_at}</TableCell>
                                    <TableCell>
                                        <Button variant="contained" sx={{ backgroundColor: "#BCA28C", color: "white", fontWeight: "bold", borderRadius: "10px", mr: 1, px: 2 }}
                                            onClick={() => handleEditOpen(dept)}>
                                            編輯
                                        </Button>
                                        <Button variant="contained" sx={{ backgroundColor: "#BCA28C", color: "white", fontWeight: "bold", borderRadius: "10px", px: 2 }}
                                            onClick={() => handleDelete(dept.id)}>
                                            刪除
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </TableContainer>
            </Paper>
            {/* ✅ 編輯 Dialog */}
            <Dialog open={openEditDialog} onClose={() => setOpenEditDialog(false)}>
                <DialogContent sx={{ backgroundColor: "#D2E4F0", padding: "20px", display: "flex", flexDirection: "column", gap: 2 }}>
                    <Typography variant="h6" sx={{ fontWeight: "bold" }}>部門</Typography>
                    <TextField
                        variant="outlined"
                        placeholder="輸入修改的部門名稱"
                        fullWidth
                        value={editName}
                        onChange={(e) => setEditName(e.target.value)}
                        sx={{ backgroundColor: "white" }}
                    />
                </DialogContent>

                <DialogActions sx={{ justifyContent: "center", backgroundColor: "#D2E4F0", padding: "10px" }}>
                    <Button
                        variant="contained"
                        sx={{
                            backgroundColor: "#BCA28C",
                            color: "white",
                            fontWeight: "bold",
                            width: "80%",
                            display: "flex",
                            alignItems: "center",
                            justifyContent: "center"
                        }}
                        onClick={handleSave}
                    >
                        <CheckCircleIcon sx={{ mr: 1 }} /> 保存
                    </Button>
                </DialogActions>
            </Dialog>

            {/* 頁尾 */}
            <Box
                sx={{
                    width: "100%",
                    mt: "auto",
                    textAlign: "center",
                    position: "absolute", // 讓頁尾固定在底部
                    bottom: 0, // 設定在底部
                    overflow: "hidden", // ✅ 隱藏滾動條
                }}
            >
                <hr style={{ width: "100%", marginBottom: "10px" }} />
                <Typography sx={{ fontSize: "20px", fontWeight: "bold" }}>
                    聯絡我們
                </Typography>
            </Box>
        </Box>
    );
}

export default DepartmentManagement;