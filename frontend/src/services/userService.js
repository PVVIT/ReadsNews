import apiClient from './axios'

export const userService = {
  /**
   * Lấy danh sách người dùng từ hệ thống (Admin)
   * @param {Object} params { search, role, status }
   */
  async getUsers(params = {}) {
    const response = await apiClient.get('/admin/users', { params })
    return response.data
  },

  /**
   * Thêm người dùng mới từ admin
   * @param {Object} data { name, email, password, role, is_active }
   */
  async createUser(data) {
    const response = await apiClient.post('/admin/users', data)
    return response.data
  },

  /**
   * Khóa hoặc Mở khóa trạng thái tài khoản người dùng
   * @param {number|string} id
   */
  async toggleStatus(id) {
    const response = await apiClient.patch(`/admin/users/${id}/toggle-status`)
    return response.data
  },

  /**
   * Cập nhật vai trò người dùng (user, editor, admin)
   * @param {number|string} id
   * @param {string} role
   */
  async updateRole(id, role) {
    const response = await apiClient.patch(`/admin/users/${id}/role`, { role })
    return response.data
  },

  /**
   * Xóa tài khoản người dùng
   * @param {number|string} id
   */
  async deleteUser(id) {
    const response = await apiClient.delete(`/admin/users/${id}`)
    return response.data
  },
}

