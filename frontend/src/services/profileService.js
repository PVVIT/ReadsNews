import apiClient from './axios'

export const profileService = {
  /**
   * Lấy thông tin profile hiện tại
   */
  async getProfile() {
    const res = await apiClient.get('/profile')
    return res.data
  },

  /**
   * Cập nhật thông tin cá nhân
   * @param {Object} data { name, birth_date, gender, phone }
   */
  async updateProfile(data) {
    const res = await apiClient.put('/profile', data)
    return res.data
  },

  /**
   * Upload ảnh đại diện
   * @param {File} file
   */
  async updateAvatar(file) {
    const fd = new FormData()
    fd.append('avatar', file)
    const res = await apiClient.post('/profile/avatar', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return res.data
  },

  /**
   * Đổi mật khẩu
   * @param {Object} data { current_password, password, password_confirmation }
   */
  async changePassword(data) {
    const res = await apiClient.put('/profile/password', data)
    return res.data
  },
}

