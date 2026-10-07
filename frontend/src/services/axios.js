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

// 1. Request Interceptor: Gắn token & header
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

// 2. Response Interceptor: Xử lý status 200, 401, 404, 422, 500
apiClient.interceptors.response.use(
  (response) => {
    const { config, data, status } = response

    // Tự động báo thành công cho các thao tác thay đổi dữ liệu (POST, PUT, PATCH, DELETE)
    // hoặc khi request có flag notifySuccess: true
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
          // 401: Sai thông tin đăng nhập hoặc chưa đăng nhập
          const msg = data?.message || 'Sai thông tin đăng nhập: tài khoản hoặc mật khẩu không chính xác.'
          toast.error(msg)

          // Nếu có token lưu nhưng bị 401, xóa auth để yêu cầu đăng nhập lại
          if (auth.isAuthenticated()) {
            auth.clearAuth()
          }
          break
        }

        case 403: {
          // 403: Không có quyền truy cập (Role không đủ)
          const msg = data?.message || 'Bạn không có quyền thực hiện thao tác này.'
          toast.warning(msg)
          break
        }

        case 404: {
          // 404: Trang hoặc tài nguyên không tồn tại
          const msg = data?.message || 'Trang hoặc tài nguyên yêu cầu không tồn tại (404).'
          toast.error(msg)
          break
        }

        case 422: {
          // 422: Dữ liệu không hợp lệ (Validation errors)
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
          // 500: Lỗi máy chủ, thao tác thất bại
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
