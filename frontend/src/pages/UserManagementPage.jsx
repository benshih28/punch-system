import { useState, useEffect, useCallback, memo } from "react";
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

// 格式化日期的輔助函數：將 ISO 8601 格式轉為 yyyy-MM-dd
const formatDateToYYYYMMDD = (isoDate) => {
  if (!isoDate) return ""; // 如果日期為空，返回空字符串
  const date = new Date(isoDate);
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0"); // 月份從 0 開始，需加 1
  const day = String(date.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
};

const EmployeeRow = memo(
  ({ emp, isSmallScreen, onReviewOpen, onAssignOpen, onDelete }) => {
    console.log(`Rendering EmployeeRow for ${emp.employee_name}`);

    return (
      <TableRow key={emp.id}>
        <TableCell sx={{ fontSize: isSmallScreen ? "0.8rem" : "1rem" }}>
          {emp.department !== "-" ? emp.department : "無部門"}
        </TableCell>
        <TableCell sx={{ fontSize: isSmallScreen ? "0.8rem" : "1rem" }}>
          {emp.position !== "-" ? emp.position : "無職位"}
        </TableCell>
        <TableCell sx={{ fontSize: isSmallScreen ? "0.8rem" : "1rem" }}>
          {emp.employee_name}
        </TableCell>
        <TableCell sx={{ fontSize: isSmallScreen ? "0.8rem" : "1rem" }}>
          {emp.manager_name !== "-" ? emp.manager_name : "無主管"}
        </TableCell>
        <TableCell sx={{ fontSize: isSmallScreen ? "0.8rem" : "1rem" }}>
          {emp.roles !== "-" ? emp.roles : "無角色"}
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
              onClick={() => onReviewOpen(emp)}
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
              onClick={() => onAssignOpen(emp)}
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
            onClick={() => onDelete(emp.id)}
          >
            離職
          </Button>
        </TableCell>
      </TableRow>
    );
  },
  (prevProps, nextProps) => {
    const isEmpEqual =
      prevProps.emp.id === nextProps.emp.id &&
      prevProps.emp.department === nextProps.emp.department &&
      prevProps.emp.position === nextProps.emp.position &&
      prevProps.emp.employee_name === nextProps.emp.employee_name &&
      prevProps.emp.manager_name === nextProps.emp.manager_name &&
      prevProps.emp.roles === nextProps.emp.roles &&
      prevProps.emp.status === nextProps.emp.status &&
      prevProps.emp.hire_date === nextProps.emp.hire_date;

    return (
      isEmpEqual &&
      prevProps.isSmallScreen === nextProps.isSmallScreen &&
      prevProps.onReviewOpen === nextProps.onReviewOpen &&
      prevProps.onAssignOpen === nextProps.onAssignOpen &&
      prevProps.onDelete === nextProps.onDelete
    );
  }
);

function UserManagementPage() {
  const [authState] = useAtom(authAtom);
  const userPermissions = authState?.roles_permissions?.permissions || [];
  const hasManageEmployeesPermission = userPermissions.includes("manage_employees");

  if (!hasManageEmployeesPermission) {
    return <Navigate to="/404" replace />;
  }

  const [departments, setDepartments] = useState([]);
  const [roles, setRoles] = useState([]);
  const [employees, setEmployees] = useState([]);
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
  const [filteredPositions, setFilteredPositions] = useState([]);
  const [filteredPositionsForDialog, setFilteredPositionsForDialog] = useState([]);

  const fetchEmployees = () => {
    setLoading(true);
    const params = {
      department_id: departments.find((dept) => dept.name === department)?.id || null,
      position_id: filteredPositions.find((pos) => pos.name === position)?.id || null, // 修正為 position_id
      user_id: employeeId || null,
      page: currentPage,
      per_page: 10,
    };

    console.log("Fetching employees with params:", params);

    API.get("/employees", { params })
      .then((res) => {
        console.log("Employees data:", res.data.data);
        console.log("Total pages:", res.data.meta.last_page);
        const ids = res.data.data.map((emp) => emp.id);
        const uniqueIds = new Set(ids);
        if (ids.length !== uniqueIds.size) {
          console.warn("發現重複的 emp.id:", ids);
        }
        setEmployees(res.data.data);
        setTotalPages(res.data.meta.last_page || 1); // 確保 totalPages 有預設值
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

  useEffect(() => {
    Promise.all([
      API.get("/departments").then((res) => setDepartments(res.data.departments || [])),
      API.get("/roles").then((res) => {
        const rolesData = Array.isArray(res.data) ? res.data : [];
        setRoles(rolesData);
      }),
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
    if (department) {
      const dept = departments.find((d) => d.name === department);
      if (dept) {
        API.get(`/positions/by/department/${dept.id}`)
          .then((res) => {
            console.log("Filtered positions for query:", res.data.positions);
            setFilteredPositions(res.data.positions || []);
            if (position && !res.data.positions.some((p) => p.name === position)) {
              setPosition("");
            }
          })
          .catch((err) => {
            console.error("載入職位失敗", err);
            setFilteredPositions([]);
            setPosition("");
          });
      }
    } else {
      setFilteredPositions([]);
      setPosition("");
    }
  }, [department, departments]);

  useEffect(() => {
    if (assignDepartment) {
      const dept = departments.find((d) => d.name === assignDepartment);
      if (dept) {
        API.get(`/positions/by/department/${dept.id}`)
          .then((res) => {
            console.log("Filtered positions for dialog:", res.data.positions);
            setFilteredPositionsForDialog(res.data.positions || []);
            if (assignPosition && !res.data.positions.some((p) => p.name === assignPosition)) {
              setAssignPosition("");
            }
          })
          .catch((err) => {
            console.error("載入職位失敗", err);
            setFilteredPositionsForDialog([]);
            setAssignPosition("");
          });
      }
    } else {
      setFilteredPositionsForDialog([]);
      setAssignPosition("");
    }
  }, [assignDepartment, departments]);

  useEffect(() => {
    if (!dataLoading) {
      fetchEmployees();
    }
  }, [currentPage, department, position, employeeId, departments, roles, dataLoading]);

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
      setDepartment("");
      setPosition("");
      setEmployeeId("");
      fetchEmployees();
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

  const handleReviewOpen = useCallback((employee) => {
    setReviewEmployee(employee);
    setReviewStatus("");
    setOpenReviewDialog(true);
  }, []);

  const handleReviewEmployee = async () => {
    if (!reviewStatus) {
      alert("請選擇審核狀態！");
      return;
    }

    try {
      await API.patch(`/employees/${reviewEmployee.id}/review`, { status: reviewStatus });
      setEmployees((prevEmployees) =>
        prevEmployees.map((emp) =>
          emp.id === reviewEmployee.id ? { ...emp, status: reviewStatus } : emp
        )
      );
      setOpenReviewDialog(false);
    } catch (error) {
      console.error("審核員工失敗：", error);
      if (error.response?.status === 404) {
        alert("員工不存在，請重新整理頁面");
        fetchEmployees();
      } else if (error.response?.status === 422) {
        alert("驗證失敗，請檢查輸入資料");
      } else if (error.response?.status === 403) {
        alert("您沒有權限執行此操作");
      } else {
        alert("審核員工失敗，請稍後再試");
        fetchEmployees();
      }
    }
  };

  const handleAssignOpen = useCallback((employee) => {
    if (dataLoading) {
      alert("資料正在載入中，請稍後再試");
      return;
    }
    setAssignEmployee(employee);

    const deptValue =
      employee.department && employee.department !== "-" && departments.find((dept) => dept.name === employee.department)
        ? employee.department
        : "";
    setAssignDepartment(deptValue);
    console.log("assignDepartment:", deptValue, "Available departments:", departments.map((d) => d.name));

    // 根據部門動態獲取職位
    if (deptValue) {
      const dept = departments.find((d) => d.name === deptValue);
      if (dept) {
        API.get(`/positions/by/department/${dept.id}`)
          .then((res) => {
            console.log("Filtered positions for dialog (on open):", res.data.positions);
            setFilteredPositionsForDialog(res.data.positions || []);
            const posValue =
              employee.position && employee.position !== "-" && res.data.positions.find((pos) => pos.name === employee.position)
                ? employee.position
                : "";
            setAssignPosition(posValue);
          })
          .catch((err) => {
            console.error("載入職位失敗", err);
            setFilteredPositionsForDialog([]);
            setAssignPosition("");
          });
      }
    } else {
      setFilteredPositionsForDialog([]);
      setAssignPosition("");
    }

    const managerValue =
      employee.manager_id && employees.find((emp) => emp.id === employee.manager_id && emp.status === "approved")
        ? employee.manager_id
        : "";
    setAssignManager(managerValue);
    console.log(
      "assignManager:",
      managerValue,
      "Available managers:",
      employees.filter((emp) => emp.status === "approved").map((emp) => emp.id)
    );

    const firstRole =
      employee.roles && employee.roles !== "-" ? employee.roles.split(", ")[0] : "";
    const roleValue = firstRole && roles.find((role) => role.name.toLowerCase() === firstRole.toLowerCase())
      ? roles.find((role) => role.name.toLowerCase() === firstRole.toLowerCase()).name
      : "";
    setAssignRole(roleValue);
    console.log("assignRole:", roleValue, "Available roles:", roles.map((r) => r.name));

    // 格式化 hire_date 為 yyyy-MM-dd
    const formattedHireDate = formatDateToYYYYMMDD(employee.hire_date);
    setAssignHireDate(formattedHireDate);
    console.log("assignHireDate:", formattedHireDate);

    setOpenAssignDialog(true);
  }, [dataLoading, departments, roles, employees]);

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
        department_id: departments.find((dept) => dept.name === assignDepartment)?.id || null,
        position_id: filteredPositionsForDialog.find((pos) => pos.name === assignPosition)?.id || null,
        manager_id: assignManager,
        role_id: roles.find((role) => role.name === assignRole)?.id || null,
        hire_date: assignHireDate,
      };
      console.log("Assign payload:", payload); // 確認發送的資料

      const response = await API.patch(`/employees/${assignEmployee.id}/assign`, payload);
      console.log("API response:", response.data); // 確認後端回應

      const updatedEmployee = response.data;
      if (updatedEmployee && updatedEmployee.id) {
        // 如果後端返回了完整的員工資料，直接使用
        setEmployees((prevEmployees) =>
          prevEmployees.map((emp) =>
            emp.id === assignEmployee.id ? { ...emp, ...updatedEmployee } : emp
          )
        );
      } else {
        // 手動更新所有欄位
        const newManager = employees.find((e) => e.id === assignManager);
        const newDepartment = departments.find((dept) => dept.id === payload.department_id);
        const newPosition = filteredPositionsForDialog.find((pos) => pos.id === payload.position_id);
        const newRole = roles.find((role) => role.id === payload.role_id);

        console.log("New manager:", newManager);
        console.log("New department:", newDepartment);
        console.log("New position:", newPosition);
        console.log("New role:", newRole);

        if (!newManager) {
          console.error("Cannot find manager with ID:", assignManager);
          alert("找不到選擇的主管，請重新整理頁面");
          return;
        }
        if (!newDepartment) {
          console.error("Cannot find department with ID:", payload.department_id);
          alert("找不到選擇的部門，請重新整理頁面");
          return;
        }
        if (!newPosition) {
          console.error("Cannot find position with ID:", payload.position_id);
          alert("找不到選擇的職位，請重新整理頁面");
          return;
        }
        if (!newRole) {
          console.error("Cannot find role with ID:", payload.role_id);
          alert("找不到選擇的角色，請重新整理頁面");
          return;
        }

        setEmployees((prevEmployees) =>
          prevEmployees.map((emp) =>
            emp.id === assignEmployee.id
              ? {
                ...emp,
                department: newDepartment.name || "-",
                position: newPosition.name || "-",
                manager_id: assignManager,
                manager_name: newManager.employee_name || "-",
                roles: newRole.name || "-",
                hire_date: assignHireDate,
              }
              : emp
          )
        );
      }

      setOpenAssignDialog(false);
    } catch (error) {
      console.error("指派員工詳情失敗：", error);
      if (error.response?.status === 400) {
        alert("無法指派，員工尚未通過審核");
      } else if (error.response?.status === 404) {
        alert("員工不存在，請重新整理頁面");
        fetchEmployees();
      } else if (error.response?.status === 422) {
        alert("驗證失敗，請檢查輸入資料");
      } else if (error.response?.status === 403) {
        alert("您沒有權限執行此操作");
      } else {
        alert("指派員工詳情失敗，請稍後再試");
        fetchEmployees();
      }
    }
  };

  const handleDelete = useCallback(async (id) => {
    if (!window.confirm("確定要將此員工標記為離職嗎？")) {
      return;
    }

    try {
      await API.delete(`/employees/${id}`);
      fetchEmployees();
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
  }, []);

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
          disabled={!department} // 如果未選擇部門，則禁用
          sx={{ backgroundColor: "white", width: "130px" }}
        >
          <MenuItem value="">{department ? "請選擇職位" : "請先選擇部門"}</MenuItem>
          {Array.isArray(filteredPositions) &&
            filteredPositions.map((pos) => (
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
          onClose={() => {
            setOpenAssignDialog(false);
          }}
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
                disabled={!assignDepartment}
              >
                <MenuItem value="">{assignDepartment ? "請選擇職位" : "請先選擇部門"}</MenuItem>
                {filteredPositionsForDialog.map((pos) => (
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
                {Array.isArray(employees) &&
                  employees
                    .filter((emp) => emp.status === "approved")
                    .map((emp) => (
                      <MenuItem key={emp.id} value={emp.id}>
                        {emp.employee_name} (ID: {emp.id})
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
                <TableRow key="loading">
                  <TableCell colSpan={7} align="center">
                    <CircularProgress />
                  </TableCell>
                </TableRow>
              ) : employees.length > 0 ? (
                employees.map((emp) => (
                  <EmployeeRow
                    key={emp.id}
                    emp={emp}
                    isSmallScreen={isSmallScreen}
                    onReviewOpen={handleReviewOpen}
                    onAssignOpen={handleAssignOpen}
                    onDelete={handleDelete}
                  />
                ))
              ) : (
                <TableRow key="no-data">
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