import axios, { AxiosError } from "axios";
import type { InternalAxiosRequestConfig } from "axios";
import router from "@/router";
import { showToast } from "@/utils/toast";
import { mapMessageToCode } from "@/utils/errorMapper";
import type { AppErrorCode } from "@/utils/errorMapper";

