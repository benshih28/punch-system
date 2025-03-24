import { useMediaQuery } from "@mui/material";
import { useState, useEffect } from "react";
import { Box, Paper, Typography, Button, TableContainer, Table, TableHead, TableRow, TableCell, TableBody, Checkbox, Dialog, DialogActions, DialogContent, TextField } from "@mui/material";
import AddCircleIcon from "@mui/icons-material/AddCircle";
import CheckCircleIcon from "@mui/icons-material/CheckCircle";
import { Link } from "react-router-dom";
import { permissionLabels } from "../constants/permissionLabels";
import API from "../api/axios";
function RolePermissionsPage() {
  const [permissions, setPermissions] = useState([]);;
  const [permissionGroups, setPermissionGroups] = useState({});
  const [openAddDialog, setOpenAddDialog] = useState(false);
  const [newPermissionName, setNewPermissionName] = useState("");
  const [selectedNewPermissions, setSelectedNewPermissions] = useState([]);


  const [openEditDialog, setOpenEditDialog] = useState(false);
  const [editPermissionName, setEditPermissionName] = useState("");
  const [editPermissionId, setEditPermissionId] = useState(null);
  const [selectedEditPermissions, setSelectedEditPermissions] = useState([]);

  useEffect(() => {
    API.get("/roles")
      .then((res) => {
        setPermissions(res.data); // 設定角色列表
      })
      .catch((err) => {
        console.error("取得角色列表失敗", err);
      });
  }, []);

  useEffect(() => {
    API.get("/permissions")
      .then((res) => {
        const grouped = res.data.reduce((acc, perm) => {
          const { category, name } = perm;

          if (!acc[category]) acc[category] = [];

          acc[category].push({
            id: name,
            name: permissionLabels[name] || name, // 對應中文，如果沒有就用原文
          });

          return acc;
        }, {});

        setPermissionGroups(grouped);
      })
      .catch((err) => {
        console.error("取得權限列表失敗", err);
      });
  }, []);

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
              {permissions.length > 0 ? (
                permissions.map((permission) => (
                  <TableRow key={permission.id}>
                    <TableCell>{permission.id}</TableCell>
                    <TableCell>{permission.name}</TableCell>
                    <TableCell>
                      <Button
                        variant="contained"
                        sx={{
                          backgroundColor: "#BCA28C",
                          color: "white",
                          fontWeight: "bold",
                          borderRadius: "10px",
                          px: 2,
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
