import apiClient from './axios'

export const authService = {
  /**
   * Đăng ký tài khoản mới
   * @param {Object} data { name, email, password, password_confirmation, role }
   */
  async register(data) {
    const response = await apiClient.post('/auth/register', data)
    return response.data
  },

  /**
   * Đăng nhập tài khoản
   * @param {Object} data { email, password }
   */
  async login(data) {
    const response = await apiClient.post('/auth/login', data)
    return response.data
  },

  /**
   * Yêu cầu gửi email đặt lại mật khẩu
   * @param {Object} data { email }
   */
  async forgotPassword(data) {
    const response = await apiClient.post('/auth/forgot-password', data)
    return response.data
  },

  /**
   * Đặt lại mật khẩu mới
   * @param {Object} data { email, token, password, password_confirmation }
   */
  async resetPassword(data) {
    const response = await apiClient.post('/auth/reset-password', data)
    return response.data
  },

  /**
   * Lấy thông tin tài khoản hiện tại
   */
  async getMe() {
    const response = await apiClient.get('/auth/me')
    return response.data
  },

  /**
   * Đăng xuất
   */
  async logout() {
    const response = await apiClient.post('/auth/logout')
    return response.data
  },
}

