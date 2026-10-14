import apiClient from './axios'

export const categoryService = {
  /**
   * Lấy danh sách danh mục (Admin)
   * @param {Object} params { search, status }
   */
  async getCategories(params = {}) {
    const response = await apiClient.get('/admin/categories', { params })
    return response.data
  },

  /**
   * Lấy chi tiết một danh mục
   * @param {number|string} id
   */
  async getCategory(id) {
    const response = await apiClient.get(`/admin/categories/${id}`)
    return response.data
  },

  /**
   * Tạo danh mục mới
   * @param {Object} data { parent_id, name, slug, description, sort_order, is_active }
   */
  async createCategory(data) {
    const response = await apiClient.post('/admin/categories', data)
    return response.data
  },

  /**
   * Cập nhật danh mục
   * @param {number|string} id
   * @param {Object} data
   */
  async updateCategory(id, data) {
    const response = await apiClient.put(`/admin/categories/${id}`, data)
    return response.data
  },

  /**
   * Bật/Tắt trạng thái hiển thị danh mục
   * @param {number|string} id
   */
  async toggleStatus(id) {
    const response = await apiClient.patch(`/admin/categories/${id}/toggle-status`)
    return response.data
  },

  /**
   * Xóa danh mục
   * @param {number|string} id
   */
  async deleteCategory(id) {
    const response = await apiClient.delete(`/admin/categories/${id}`)
    return response.data
  },

  /**
   * Lấy danh mục public cho trang đọc tin
   */
  async getPublicCategories() {
    const response = await apiClient.get('/categories')
    return response.data
  },
}

