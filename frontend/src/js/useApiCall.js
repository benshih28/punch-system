import { useAtom } from 'jotai';
import { apiResponsesAtom, updateApiResponse, authAtom } from './atoms';
import api from './axios';

export const useApiCall = () => {
  const [apiResponses, setApiResponses] = useAtom(apiResponsesAtom);
  const [auth, setAuth] = useAtom(authAtom);

  const callApi = async (apiId, url, method = 'get', data = null) => {
    try {
      const response = await api({ url, method, data });
      const responseData = {
        statusCode: response.status,
        data: response.data,
      };

      // 如果是登入 API，更新 authAtom
      if (apiId === 'login' && response.status === 200) {
        setAuth({
          access_token: response.data.access_token,
          user: response.data.user,
        });
      }

      setApiResponses(updateApiResponse(apiId, responseData));
      return response.data;
    } catch (error) {
      const errorData = {
        statusCode: error.response?.status || 500,
        data: error.response?.data || { message: 'Unknown error' },
      };
      setApiResponses(updateApiResponse(apiId, errorData));
      throw error;
    }
  };

  return { apiResponses, auth, callApi };
};