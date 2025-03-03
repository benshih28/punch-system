import axios from "axios";

const api = axios.create({
  baseURL: "http://127.0.0.1:8000/api",
  withCredentials: true, // **允許攜帶 JWT Token**
  headers: {
    "Content-Type": "application/json",
    "Authorization": `Bearer ${localStorage.getItem("access_token")}`, // 讀取 JWT Token
  },
});

export default api;
