import { useState, useEffect } from "react";
import { useAtom } from "jotai";
import { authAtom } from "../state/authAtom";
import { useMediaQuery } from "@mui/material";
import { Navigate } from "react-router-dom";
import API from "../api/axios";
import { Link } from "react-router-dom";
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
  Dialog,
  DialogActions,
  DialogContent,
  TextField,
  FormControl,
  Select,
  MenuItem,
  Pagination,
  CircularProgress,
} from "@mui/material";
import ManageSearchIcon from "@mui/icons-material/ManageSearch";

function UserManagementPage() {
  const [authState] = useAtom(authAtom);
  const userPermissions = authState?.roles_permissions?.permissions || [];
  const hasManageUsersPermission = userPermissions.includes("manage_employees");

  if (!hasManageUsersPermission) {
    return <Navigate to="/404" replace />;
  }

  const [departments, setDepartments] = useState([]);
  const [positions, setPositions] = useState([]);
  const [roles, setRoles] = useState([]);
  const [employees, setEmployees] = useState([]);
  const [managers, setManagers] = useState([]);
  const [totalPages, setTotalPages] = useState(1);
  const [currentPage, setCurrentPage] = useState(1);
  const [department, setDepartment] = useState("");
  const [position, setPosition] = useState("");
  const [employeeId, setEmployeeId] = useState("");
  const [openAddDialog, setOpenAddDialog] = useState(false);
  const [newEmployeeName, setNewEmployeeName] = useState("");
  const [newEmployeeEmail, setNewEmployeeEmail] = useState("");
  const [newEmployeePassword, setNewEmployeePassword] = useState("");
  const [newEmployeePasswordConfirmation, setNewEmployeePasswordConfirmation] = useState("");
  const [newEmployeeGender, setNewEmployeeGender] = useState("");
  const [openReviewDialog, setOpenReviewDialog] = useState(false);
  const [reviewEmployee, setReviewEmployee] = useState(null);
  const [reviewStatus, setReviewStatus] = useState("");
  const [openAssignDialog, setOpenAssignDialog] = useState(false);
  const [assignEmployee, setAssignEmployee] = useState(null);
  const [assignDepartment, setAssignDepartment] = useState("");
  const [assignPosition, setAssignPosition] = useState("");
  const [assignManager, setAssignManager] = useState("");
  const [assignRole, setAssignRole] = useState("");
  const [assignHireDate, setAssignHireDate] = useState("");
  const [loading, setLoading] = useState(false);
  const [dataLoading, setDataLoading] = useState(true);

  useEffect(() => {
    Promise.all([
      API.get("/departments").then((res) => setDepartments(res.data.departments || [])),
      API.get("/positions").then((res) => setPositions(res.data.positions || [])),
      API.get("/roles").then((res) => {
        // 修正：直接檢查 res.data 是否為陣列
        const rolesData = Array.isArray(res.data) ? res.data : [];
        setRoles(rolesData);
      }),
      API.get("/employees/approved", { params: { per_page: 100 } }).then((res) =>
        setManagers(res.data.data || [])
      ),
    ])
      .catch((err) => {
        console.error("載入資料失敗", err);
        if (err.response?.status === 401) {
          alert("未授權，請重新登入");
        } else {
          alert("無法載入資料，請稍後再試");
        }
      })
      .finally(() => setDataLoading(false));
  }, []);

  useEffect(() => {
    const fetchEmployees = () => {
      setLoading(true);
      const params = {
        department_id: departments.find((dept) => dept.name === department)?.id || null,
        role_id: roles.find((role) => role.name === position)?.id || null,
        user_id: employeeId || null,
        page: currentPage,
        per_page: 10,
      };

      API.get("/employees", { params })
        .then((res) => {
          setEmployees(res.data.data);
          setTotalPages(res.data.meta.last_page);
        })
        .catch((err) => {
          console.error("取得員工列表失敗", err);
          if (err.response?.status === 401) {
            alert("未授權，請重新登入");
          } else if (err.response?.status === 403) {
            alert("您沒有權限執行此操作");
          } else if (err.response?.status === 500) {
            alert("伺服器發生錯誤，請聯繫管理員或稍後再試");
          } else {
            alert("無法載入員工列表，請稍後再試");
          }
        })
        .finally(() => setLoading(false));
    };

    fetchEmployees();
  }, [currentPage, department, position, employeeId, departments, roles]);

  const handleSearch = () => {
    setCurrentPage(1);
  };

  const handleAddEmployee = async () => {
    if (
      !newEmployeeName.trim() ||
      !newEmployeeEmail.trim() ||
      !newEmployeePassword.trim() ||
      !newEmployeePasswordConfirmation.trim() ||
      !newEmployeeGender
    ) {
      alert("請填寫所有必填欄位！");
      return;
    }

    if (newEmployeePassword !== newEmployeePasswordConfirmation) {
      alert("密碼與確認密碼不一致！");
      return;
    }

    try {
      const payload = {
        name: newEmployeeName,
        email: newEmployeeEmail,
        password: newEmployeePassword,
        password_confirmation: newEmployeePasswordConfirmation,
        gender: newEmployeeGender,
      };
      await API.post("/employees", payload);
      setCurrentPage(1);
      setOpenAddDialog(false);
      setNewEmployeeName("");
      setNewEmployeeEmail("");
      setNewEmployeePassword("");
      setNewEmployeePasswordConfirmation("");
      setNewEmployeeGender("");
    } catch (error) {
      console.error("新增員工失敗：", error);
      if (error.response?.status === 422) {
        alert("驗證失敗，請檢查輸入資料（例如電子郵件是否已存在）");
      } else if (error.response?.status === 403) {
        alert("您沒有權限執行此操作");
      } else {
        alert("新增員工失敗，請稍後再試");
      }
    }
  };

  const handleReviewOpen = (employee) => {
    setReviewEmployee(employee);
    setReviewStatus("");
    setOpenReviewDialog(true);
  };

  const handleReviewEmployee = async () => {
    if (!reviewStatus) {
      alert("請選擇審核狀態！");
      return;
    }

    try {
      await API.patch(`/employees/${reviewEmployee.id}/review`, { status: reviewStatus });
      setOpenReviewDialog(false);
    } catch (error) {
      console.error("審核員工失敗：", error);
      if (error.response?.status === 404) {
        alert("員工不存在，請重新整理頁面");
      } else if (error.response?.status === 422) {
        alert("驗證失敗，請檢查輸入資料");
      } else if (error.response?.status === 403) {
        alert("您沒有權限執行此操作");
      } else {
        alert("審核員工失敗，請稍後再試");
      }
    }
  };

  const handleAssignOpen = (employee) => {
    if (dataLoading) {
      alert("資料正在載入中，請稍後再試");
      return;
    }
    setAssignEmployee(employee);
    setAssignDepartment(employee.department || "");
    setAssignPosition(employee.position || "");
    setAssignManager(employee.manager_id || "");
    setAssignRole(employee.roles || "");
    setAssignHireDate("");
    setOpenAssignDialog(true);
  };

  const handleAssignEmployee = async () => {
    if (
      !assignDepartment ||
      !assignPosition ||
      !assignManager ||
      !assignRole ||
      !assignHireDate
    ) {
      alert("請填寫所有必填欄位！");
      return;
    }

    try {
      const payload = {
        department_id: departments.find((dept) => dept.name === assignDepartment).id,
        position_id: positions.find((pos) => pos.name === assignPosition).id,
        manager_id: assignManager,
        role_id: roles.find((role) => role.name === assignRole).id,
        hire_date: assignHireDate,
      };
      await API.patch(`/employees/${assignEmployee.id}/assign`, payload);
      setOpenAssignDialog(false);
    } catch (error) {
      console.error("指派員工詳情失敗：", error);
      if (error.response?.status === 400) {
        alert("無法指派，員工尚未通過審核");
      } else if (error.response?.status === 404) {
        alert("員工不存在，請重新整理頁面");
      } else if (error.response?.status === 422) {
        alert("驗證失敗，請檢查輸入資料");
      } else if (error.response?.status === 403) {
        alert("您沒有權限執行此操作");
      } else {
        alert("指派員工詳情失敗，請稍後再試");
      }
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm("確定要將此員工標記為離職嗎？")) {
      return;
    }

    try {
      await API.delete(`/employees/${id}`);
    } catch (error) {
      console.error("標記員工為離職失敗：", error);
      if (error.response?.status === 404) {
        alert("員工不存在，請重新整理頁面");
      } else if (error.response?.status === 403) {
        alert("您沒有權限執行此操作");
      } else {
        alert("標記員工為離職失敗，請稍後再試");
      }
    }
  };

  const handlePageChange = (event, value) => {
    setCurrentPage(value);
  };

  const isSmallScreen = useMediaQuery("(max-width: 600px)");
  const isMediumScreen = useMediaQuery("(max-width: 960px)");

  return (
    <Box
      sx={{
        width: "100%",
        minHeight: "100vh",
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        backgroundColor: "#ffffff",
        p: isSmallScreen ? 1 : 2,
      }}
    >
      <Box
        sx={{
          display: "flex",
          flexDirection: isSmallScreen ? "column" : "row",
          margin: isSmallScreen ? "20px 0px" : "60px 0px 40px",
          width: isSmallScreen ? "100%" : "90%",
          justifyContent: "space-between",
          alignItems: isSmallScreen ? "center" : "center",
          gap: isSmallScreen ? 1 : 0,
        }}
      >
        <Typography
          variant={isSmallScreen ? "h6" : "h4"}
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
              color: "#ba6262",
              fontWeight: "bold",
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

      <Box
        sx={{
          backgroundColor: "#D2E4F0",
          width: isSmallScreen ? "100%" : "90%",
          padding: "10px",
          borderRadius: "8px",
          display: "flex",
          flexWrap: "wrap",
          alignItems: "center",
          justifyContent: "center",
          gap: 2,
          mb: 2,
        }}
      >
        <Typography variant="body1" sx={{ whiteSpace: "nowrap" }}>
          選擇部門：
        </Typography>
        <Select
          value={department}
          onChange={(e) => setDepartment(e.target.value)}
          displayEmpty
          variant="outlined"
          size="small"
          sx={{ backgroundColor: "white", width: "130px" }}
        >
          <MenuItem value="">請選擇部門</MenuItem>
          {Array.isArray(departments) &&
            departments.map((dept) => (
              <MenuItem key={dept.id} value={dept.name}>
                {dept.name}
              </MenuItem>
            ))}
        </Select>

        <Typography variant="body1" sx={{ whiteSpace: "nowrap" }}>
          選擇職位：
        </Typography>
        <Select
          value={position}
          onChange={(e) => setPosition(e.target.value)}
          displayEmpty
          variant="outlined"
          size="small"
          sx={{ backgroundColor: "white", width: "130px" }}
        >
          <MenuItem value="">請選擇職位</MenuItem>
          {Array.isArray(positions) &&
            positions.map((pos) => (
              <MenuItem key={pos.id} value={pos.name}>
                {pos.name}
              </MenuItem>
            ))}
        </Select>

        <Box sx={{ display: "flex", alignItems: "center", gap: 1 }}>
          <Typography variant="body1" sx={{ whiteSpace: "nowrap" }}>
            員工編號：
          </Typography>
          <TextField
            variant="outlined"
            size="small"
            value={employeeId}
            onChange={(e) => setEmployeeId(e.target.value)}
            sx={{ backgroundColor: "white", width: "130px" }}
          />
        </Box>

        <Button
          variant="contained"
          sx={{
            backgroundColor: "#AB9681",
            color: "white",
            fontWeight: "bold",
            fontSize: "18px",
            borderRadius: "20px",
            padding: "2px 40px",
            justifyContent: "flex-start",
          }}
          startIcon={<ManageSearchIcon />}
          onClick={handleSearch}
        >
          查詢
        </Button>
      </Box>

      <Paper
        sx={{
          width: isSmallScreen ? "100%" : "90%",
          padding: isSmallScreen ? "10px" : "20px",
          boxShadow: "0px -4px 10px rgba(0, 0, 0, 0.3)",
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
            人員列表
          </Typography>
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

        <Dialog
          open={openAddDialog}
          onClose={() => setOpenAddDialog(false)}
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
              新增員工
            </Typography>
            <TextField
              label="姓名"
              variant="outlined"
              fullWidth
              value={newEmployeeName}
              onChange={(e) => setNewEmployeeName(e.target.value)}
              sx={{ backgroundColor: "white" }}
            />
            <TextField
              label="電子郵件"
              variant="outlined"
              fullWidth
              value={newEmployeeEmail}
              onChange={(e) => setNewEmployeeEmail(e.target.value)}
              sx={{ backgroundColor: "white" }}
            />
            <TextField
              label="密碼"
              type="password"
              variant="outlined"
              fullWidth
              value={newEmployeePassword}
              onChange={(e) => setNewEmployeePassword(e.target.value)}
              sx={{ backgroundColor: "white" }}
            />
            <TextField
              label="確認密碼"
              type="password"
              variant="outlined"
              fullWidth
              value={newEmployeePasswordConfirmation}
              onChange={(e) => setNewEmployeePasswordConfirmation(e.target.value)}
              sx={{ backgroundColor: "white" }}
            />
            <FormControl fullWidth sx={{ backgroundColor: "white" }}>
              <Select
                value={newEmployeeGender}
                onChange={(e) => setNewEmployeeGender(e.target.value)}
                displayEmpty
              >
                <MenuItem value="">請選擇性別</MenuItem>
                <MenuItem value="male">男</MenuItem>
                <MenuItem value="female">女</MenuItem>
              </Select>
            </FormControl>
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
              onClick={handleAddEmployee}
            >
              新增
            </Button>
          </DialogActions>
        </Dialog>

        <Dialog
          open={openReviewDialog}
          onClose={() => setOpenReviewDialog(false)}
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
              審核員工
            </Typography>
            <FormControl fullWidth sx={{ backgroundColor: "white" }}>
              <Select
                value={reviewStatus}
                onChange={(e) => setReviewStatus(e.target.value)}
                displayEmpty
              >
                <MenuItem value="">請選擇審核狀態</MenuItem>
                <MenuItem value="approved">批准</MenuItem>
                <MenuItem value="rejected">拒絕</MenuItem>
              </Select>
            </FormControl>
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
              onClick={handleReviewEmployee}
            >
              確認
            </Button>
          </DialogActions>
        </Dialog>

        <Dialog
          open={openAssignDialog}
          onClose={() => setOpenAssignDialog(false)}
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
              指派員工詳情
            </Typography>
            <Typography variant="body1">部門</Typography>
            <FormControl fullWidth sx={{ backgroundColor: "white" }}>
              <Select
                value={assignDepartment}
                onChange={(e) => setAssignDepartment(e.target.value)}
                displayEmpty
              >
                <MenuItem value="">請選擇部門</MenuItem>
                {Array.isArray(departments) &&
                  departments.map((dept) => (
                    <MenuItem key={dept.id} value={dept.name}>
                      {dept.name}
                    </MenuItem>
                  ))}
              </Select>
            </FormControl>
            <Typography variant="body1">職位</Typography>
            <FormControl fullWidth sx={{ backgroundColor: "white" }}>
              <Select
                value={assignPosition}
                onChange={(e) => setAssignPosition(e.target.value)}
                displayEmpty
              >
                <MenuItem value="">請選擇職位</MenuItem>
                {Array.isArray(positions) &&
                  positions.map((pos) => (
                    <MenuItem key={pos.id} value={pos.name}>
                      {pos.name}
                    </MenuItem>
                  ))}
              </Select>
            </FormControl>
            <Typography variant="body1">主管</Typography>
            <FormControl fullWidth sx={{ backgroundColor: "white" }}>
              <Select
                value={assignManager}
                onChange={(e) => setAssignManager(e.target.value)}
                displayEmpty
              >
                <MenuItem value="">請選擇主管</MenuItem>
                {Array.isArray(managers) &&
                  managers.map((mgr) => (
                    <MenuItem key={mgr.id} value={mgr.id}>
                      {mgr.employee_name} (ID: {mgr.id})
                    </MenuItem>
                  ))}
              </Select>
            </FormControl>
            <Typography variant="body1">角色</Typography>
            <FormControl fullWidth sx={{ backgroundColor: "white" }}>
              <Select
                value={assignRole}
                onChange={(e) => setAssignRole(e.target.value)}
                displayEmpty
              >
                <MenuItem value="">請選擇角色</MenuItem>
                {Array.isArray(roles) &&
                  roles.map((role) => (
                    <MenuItem key={role.id} value={role.name}>
                      {role.name}
                    </MenuItem>
                  ))}
              </Select>
            </FormControl>
            <Typography variant="body1">入職日期</Typography>
            <TextField
              type="date"
              variant="outlined"
              fullWidth
              value={assignHireDate}
              onChange={(e) => setAssignHireDate(e.target.value)}
              sx={{ backgroundColor: "white" }}
              InputLabelProps={{ shrink: true }}
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
              onClick={handleAssignEmployee}
            >
              指派
            </Button>
          </DialogActions>
        </Dialog>

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
                  部門
                </TableCell>
                <TableCell
                  sx={{
                    fontWeight: "bold",
                    fontSize: isSmallScreen ? "0.8rem" : "1rem",
                    minWidth: "80px",
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
                  員工姓名
                </TableCell>
                <TableCell
                  sx={{
                    fontWeight: "bold",
                    fontSize: isSmallScreen ? "0.8rem" : "1rem",
                    minWidth: "80px",
                  }}
                >
                  主管
                </TableCell>
                <TableCell
                  sx={{
                    fontWeight: "bold",
                    fontSize: isSmallScreen ? "0.8rem" : "1rem",
                    minWidth: "80px",
                  }}
                >
                  角色
                </TableCell>
                <TableCell
                  sx={{
                    fontWeight: "bold",
                    fontSize: isSmallScreen ? "0.8rem" : "1rem",
                    minWidth: "80px",
                  }}
                >
                  狀態
                </TableCell>
                <TableCell
                  sx={{
                    fontWeight: "bold",
                    fontSize: isSmallScreen ? "0.8rem" : "1rem",
                    minWidth: "150px",
                  }}
                >
                  操作
                </TableCell>
              </TableRow>
            </TableHead>

            <TableBody>
              {loading ? (
                <TableRow>
                  <TableCell colSpan={7} align="center">
                    <CircularProgress />
                  </TableCell>
                </TableRow>
              ) : employees.length > 0 ? (
                employees.map((emp) => (
                  <TableRow key={emp.id}>
                    <TableCell sx={{ fontSize: isSmallScreen ? "0.8rem" : "1rem" }}>
                      {emp.department || "--"}
                    </TableCell>
                    <TableCell sx={{ fontSize: isSmallScreen ? "0.8rem" : "1rem" }}>
                      {emp.position || "--"}
                    </TableCell>
                    <TableCell sx={{ fontSize: isSmallScreen ? "0.8rem" : "1rem" }}>
                      {emp.employee_name}
                    </TableCell>
                    <TableCell sx={{ fontSize: isSmallScreen ? "0.8rem" : "1rem" }}>
                      {managers.find((mgr) => mgr.id === emp.manager_id)?.employee_name || "--"}
                    </TableCell>
                    <TableCell sx={{ fontSize: isSmallScreen ? "0.8rem" : "1rem" }}>
                      {emp.roles || "--"}
                    </TableCell>
                    <TableCell sx={{ fontSize: isSmallScreen ? "0.8rem" : "1rem" }}>
                      {emp.status === "pending"
                        ? "待審核"
                        : emp.status === "approved"
                        ? "已批准"
                        : emp.status === "rejected"
                        ? "已拒絕"
                        : "已離職"}
                    </TableCell>
                    <TableCell>
                      {emp.status === "pending" && (
                        <Button
                          variant="contained"
                          sx={{
                            backgroundColor: "#BCA28C",
                            color: "white",
                            fontWeight: "bold",
                            borderRadius: "10px",
                            mr: 1,
                            px: isSmallScreen ? 1 : 2,
                            fontSize: isSmallScreen ? "0.7rem" : "0.875rem",
                          }}
                          onClick={() => handleReviewOpen(emp)}
                        >
                          審核
                        </Button>
                      )}
                      {emp.status === "approved" && (
                        <Button
                          variant="contained"
                          sx={{
                            backgroundColor: "#BCA28C",
                            color: "white",
                            fontWeight: "bold",
                            borderRadius: "10px",
                            mr: 1,
                            px: isSmallScreen ? 1 : 2,
                            fontSize: isSmallScreen ? "0.7rem" : "0.875rem",
                          }}
                          onClick={() => handleAssignOpen(emp)}
                        >
                          指派
                        </Button>
                      )}
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
                        onClick={() => handleDelete(emp.id)}
                      >
                        離職
                      </Button>
                    </TableCell>
                  </TableRow>
                ))
              ) : (
                <TableRow>
                  <TableCell colSpan={7} align="center">
                    尚無員工資料
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        </TableContainer>

        {employees.length > 0 && (
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
    </Box>
  );
}

export default UserManagementPage;