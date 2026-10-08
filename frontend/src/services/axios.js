import axios from 'axios'
import { auth } from '@/utils/auth'
import { toast } from 'vue3-toastify'

// Khởi tạo axios instance
const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  timeout: 15000,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

apiClient.interceptors.request.use(
  (config) => {
    const token = auth.getToken()
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

apiClient.interceptors.response.use(
  (response) => {
    const { config, data, status } = response

    const isMutation = ['post', 'put', 'patch', 'delete'].includes(config.method?.toLowerCase())
    if (config.notifySuccess || (isMutation && config.showSuccessToast !== false)) {
      const successMsg = data?.message || 'Thao tác thành công!'
      toast.success(successMsg)
    }

    return response
  },
  (error) => {
    if (error.response) {
      const { status, data } = error.response

      switch (status) {
        case 401: {
          const msg = data?.message || 'Sai thông tin đăng nhập: tài khoản hoặc mật khẩu không chính xác.'
          toast.error(msg)

          if (auth.isAuthenticated()) {
            auth.clearAuth()
          }
          break
        }

        case 403: {
          const msg = data?.message || 'Bạn không có quyền thực hiện thao tác này.'
          toast.warning(msg)
          break
        }

        case 404: {
          const msg = data?.message || 'Trang hoặc tài nguyên yêu cầu không tồn tại (404).'
          toast.error(msg)
          break
        }

        case 422: {
          let errorDetails = ''
          if (data?.errors && typeof data.errors === 'object') {
            const errorList = Object.values(data.errors).flat()
            errorDetails = errorList.join(' | ')
          }
          const msg = errorDetails || data?.message || 'Dữ liệu không hợp lệ (422).'
          toast.warning(msg)
          break
        }

        case 500: {
          const msg = data?.message || 'Máy chủ gặp sự cố, thao tác thất bại (500).'
          toast.error(msg)
          break
        }

        default: {
          const msg = data?.message || `Đã xảy ra lỗi không xác định (${status}).`
          toast.error(msg)
          break
        }
      }
    } else if (error.request) {
      toast.error('Không thể kết nối đến máy chủ. Vui lòng kiểm tra đường truyền mạng!')
    } else {
      toast.error(error.message || 'Đã xảy ra lỗi hệ thống.')
    }

    return Promise.reject(error)
  }
)

export default apiClient
