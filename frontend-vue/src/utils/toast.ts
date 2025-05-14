import { useToast } from "vue-toastification";
const toast = useToast();

export function showToast(message: string, type: "info" | "success" | "error" = "info") {
  toast[type](message);            // ex: toast.error('登入失敗')
}
