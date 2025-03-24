import { useMediaQuery } from "@mui/material";
import { useState } from "react";
import { Box, Paper, Typography, Button, TableContainer, Table, TableHead, TableRow, TableCell, TableBody, Checkbox, Dialog, DialogActions, DialogContent, TextField } from "@mui/material";
import AddCircleIcon from "@mui/icons-material/AddCircle";
import CheckCircleIcon from "@mui/icons-material/CheckCircle";
import { Link } from "react-router-dom";

function RolePermissionsPage() {
  const [permissions, setPermissions] = useState([
    { id: 1, name: "人事主管", permissions: [] },
    { id: 2, name: "財務主管", permissions: [] },
    { id: 3, name: "系統管理員", permissions: [] },
  ]);

  const [openAddDialog, setOpenAddDialog] = useState(false);
  const [newPermissionName, setNewPermissionName] = useState("");
  const [selectedNewPermissions, setSelectedNewPermissions] = useState([]);


  const [openEditDialog, setOpenEditDialog] = useState(false);
  const [editPermissionName, setEditPermissionName] = useState("");
  const [editPermissionId, setEditPermissionId] = useState(null);
  const [selectedEditPermissions, setSelectedEditPermissions] = useState([]);

  // 分類權限
  const permissionGroups = {
    "基本考勤權限": [
      { id: "punch_in", name: "上班打卡" },
      { id: "punch_out", name: "下班打卡" },
      { id: "request_correction", name: "申請補打卡" },
      { id: "view_corrections", name: "查詢個人補登打卡紀錄" },
      { id: "view_attendance", name: "查詢個人打卡紀錄" },
      { id: "approve_correction", name: "審核補打卡" },
      { id: "view_all_corrections", name: "查詢所有補登打卡紀錄" },
    ],
    "請假管理": [
      { id: "request_leave", name: "申請請假" },
      { id: "view_leave_records", name: "查詢請假餘額" },
      { id: "approve_leave", name: "審核請假" },
      { id: "delete_leave", name: "刪除請假" },
      { id: "view_department_leave_records", name: "查詢部門請假紀錄" },
      { id: "approve_department_leave", name: "核准/駁回部門請假" },
      { id: "update_leave", name: "更新請假資料" },
    ],
    "角色與權限管理": [
      { id: "manage_roles", name: "管理角色與權限" },
      { id: "view_roles", name: "查詢角色" },
      { id: "view_permissions", name: "查詢權限" },
    ],
    "員工與組織管理": [
      { id: "manage_employees", name: "管理員工" },
      { id: "register_employee", name: "註冊員工" },
      { id: "review_employee", name: "審核員工" },
      { id: "assign_employee_details", name: "分配/變更部門、職位、主管、角色" },
      { id: "delete_employee", name: "刪除員工" },
    ],
    "部門與職位管理 (HR)": [
      { id: "manage_departments", name: "管理部門" },
      { id: "manage_positions", name: "管理職位" },
      { id: "view_manager", name: "查詢主管" },
      { id: "view_subordinates", name: "查詢自己管理的員工" },
    ],
  };

  // 新增角色
  const handleAddPermission = () => {
    if (!newPermissionName.trim()) {
      alert("請輸入角色名稱！");
      return;
    }
    const newId = permissions.length > 0 ? permissions[permissions.length - 1].id + 1 : 1;
    const newPermission = { id: newId, name: newPermissionName, permissions: selectedNewPermissions };
    setPermissions([...permissions, newPermission]);
    setOpenAddDialog(false);
    setNewPermissionName("");
    setSelectedNewPermissions([]);
  };

  // 編輯角色
  const handleEditOpen = (permission) => {
    setEditPermissionId(permission.id);
    setEditPermissionName(permission.name);
    setSelectedEditPermissions(permission.permissions);
    setOpenEditDialog(true);
  };

  // 儲存角色編輯
  const handleSaveEdit = () => {
    setPermissions(
      permissions.map((p) =>
        p.id === editPermissionId ? { ...p, name: editPermissionName, permissions: selectedEditPermissions } : p
      )
    );
    setOpenEditDialog(false);
  };

  // 選擇權限（新增）
  const handleToggleNewPermission = (id) => {
    setSelectedNewPermissions((prev) =>
      prev.includes(id) ? prev.filter((p) => p !== id) : [...prev, id]
    );
  };

  // 選擇權限（編輯）
  const handleToggleEditPermission = (id) => {
    setSelectedEditPermissions((prev) =>
      prev.includes(id) ? prev.filter((p) => p !== id) : [...prev, id]
    );
  };


  const allPermissions = Object.values(permissionGroups).flat();
  const isSmallScreen = useMediaQuery("(max-width: 600px)"); // 手機螢幕
  const isMediumScreen = useMediaQuery("(max-width: 960px)"); // 平板


  return (
    <Box sx={{ width: "100%", height: "100%", display: "flex", flexDirection: "column", alignItems: "center", backgroundColor: "#ffffff" }}>
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
          <Link to="/department/management" style={{ textDecoration: "none", color: "black" }}>
            部門管理
          </Link>
          &nbsp;
          <Link to="/position/management" style={{ textDecoration: "none", color: "black" }}>
            職位管理
          </Link>
          &nbsp;
          <Link to="/role/permissions" style={{ textDecoration: "none", color: "#ba6262", fontWeight: "bold" }}>
            權限管理
          </Link>
          &nbsp;
          <Link to="/user/management" style={{ textDecoration: "none", color: "black" }}>
            人員管理
          </Link>
          &nbsp;
          <Link to="/employee/history" style={{ textDecoration: "none", color: "black" }}>
            人員歷程
          </Link>

        </Typography>
      </Box>

      {/* 角色列表 */}
      <Paper sx={{ width: "90%", padding: "20px", boxShadow: "0px -4px 10px rgba(0, 0, 0, 0.3)", borderRadius: "8px" }}>
        <Box sx={{ display: "flex", justifyContent: "space-between", alignItems: "center", mb: 2 }}>
          <Typography variant="h6" sx={{ fontWeight: "bold" }}>角色列表</Typography>
          <Button variant="contained" sx={{ backgroundColor: "#4A4A4A", color: "white", fontWeight: "bold", px: 3, borderRadius: "10px" }} onClick={() => setOpenAddDialog(true)}>新增</Button>
        </Box>


        {/* 🆕 新增角色 Dialog */}
        <Dialog open={openAddDialog} onClose={() => setOpenAddDialog(false)}>
          <DialogContent sx={{ backgroundColor: "#D2E4F0", padding: "20px" }}>
            <Typography variant="h6" sx={{ fontWeight: "bold" }}>角色名稱</Typography>
            <TextField
              variant="outlined"
              placeholder="輸入新增的角色名稱"
              fullWidth
              value={newPermissionName}
              onChange={(e) => setNewPermissionName(e.target.value)}
              sx={{ backgroundColor: "white" }}
            />

            <Typography variant="h6" sx={{ fontWeight: "bold", mt: 2 }}>權限選擇：</Typography>

            {Object.entries(permissionGroups).map(([group, perms]) => (
              <Box key={group} sx={{ mt: 2 }}>
                <Typography variant="h6" sx={{ fontWeight: "bold", backgroundColor: "#A0C4FF", padding: "5px", borderRadius: "5px" }}>{group}</Typography>

                {/* RWD: 自適應顯示數量 */}
                <Box
                  sx={{
                    display: "grid",
                    gridTemplateColumns: isSmallScreen ? "1fr" : isMediumScreen ? "1fr 1fr" : "1fr 1fr 1fr",
                    gap: "10px",
                    mt: 1,
                  }}
                >
                  {perms.map(({ id, name }) => (
                    <Box key={id} sx={{ display: "flex", alignItems: "center" }}>
                      <Checkbox
                        checked={selectedNewPermissions.includes(id)}
                        onChange={() => handleToggleNewPermission(id)}
                      />
                      <Typography>{name}</Typography>
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


        <TableContainer>
          <Table>
            <TableHead>
              <TableRow>
                <TableCell>角色 ID</TableCell>
                <TableCell>角色名稱</TableCell>
                <TableCell>操作</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {permissions.map((permission) => (
                <TableRow key={permission.id}>
                  <TableCell>{permission.id}</TableCell>
                  <TableCell>{permission.name}</TableCell>
                  <TableCell>
                    <Button variant="contained" sx={{ backgroundColor: "#BCA28C", color: "white", fontWeight: "bold", borderRadius: "10px", px: 2 }} onClick={() => handleEditOpen(permission)}>編輯</Button>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </TableContainer>
      </Paper>

      {/* 編輯角色 Dialog */}
      <Dialog open={openEditDialog} onClose={() => setOpenEditDialog(false)}>
        <DialogContent sx={{ backgroundColor: "#D2E4F0", padding: "20px" }}>
          <Typography variant="h6" sx={{ fontWeight: "bold" }}>角色名稱</Typography>
          <TextField fullWidth value={editPermissionName} onChange={(e) => setEditPermissionName(e.target.value)} sx={{ backgroundColor: "white" }} />
          <Typography variant="h6" sx={{ fontWeight: "bold", mt: 2 }}>權限選擇：</Typography>

          {Object.entries(permissionGroups).map(([group, perms]) => (
            <Box key={group} sx={{ mt: 2 }}>
              <Typography variant="h6" sx={{ fontWeight: "bold", backgroundColor: "#A0C4FF", padding: "5px", borderRadius: "5px" }}>
                {group}
              </Typography>

              {/* RWD 設計 - 自動調整每行顯示數量 */}
              <Box
                sx={{
                  display: "grid",
                  gridTemplateColumns: isSmallScreen ? "1fr" : isMediumScreen ? "1fr 1fr" : "1fr 1fr 1fr",
                  gap: "10px",
                  mt: 1,
                }}
              >
                {perms.map(({ id, name }) => (
                  <Box key={id} sx={{ display: "flex", alignItems: "center" }}>
                    <Checkbox checked={selectedEditPermissions.includes(id)} onChange={() => handleToggleEditPermission(id)} />
                    <Typography>{name}</Typography>
                  </Box>
                ))}
              </Box>
            </Box>
          ))}
        </DialogContent>
      </Dialog>
    </Box>
  );
}

export default RolePermissionsPage;
