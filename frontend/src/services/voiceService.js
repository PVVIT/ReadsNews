import apiClient from './axios'

export const voiceService = {
  /**
   * Lấy danh sách giọng nói AI (Admin, có phân trang hoặc all)
   * @param {Object} params { search, provider, gender, status, per_page, page, all }
   */
  async getVoices(params = {}) {
    const response = await apiClient.get('/admin/voices', { params })
    return response.data
  },

  /**
   * Lấy chi tiết một giọng nói AI
   * @param {number|string} id
   */
  async getVoice(id) {
    const response = await apiClient.get(`/admin/voices/${id}`)
    return response.data
  },

  /**
   * Thêm giọng nói AI mới
   * @param {Object} data { provider, code, name, language, gender, is_active }
   */
  async createVoice(data) {
    const response = await apiClient.post('/admin/voices', data)
    return response.data
  },

  /**
   * Cập nhật thông tin giọng nói AI
   * @param {number|string} id
   * @param {Object} data
   */
  async updateVoice(id, data) {
    const response = await apiClient.put(`/admin/voices/${id}`, data)
    return response.data
  },

  /**
   * Bật/Tắt trạng thái hoạt động của giọng nói
   * @param {number|string} id
   */
  async toggleStatus(id) {
    const response = await apiClient.patch(`/admin/voices/${id}/toggle-status`)
    return response.data
  },

  /**
   * Xóa một giọng nói
   * @param {number|string} id
   */
  async deleteVoice(id) {
    const response = await apiClient.delete(`/admin/voices/${id}`)
    return response.data
  },

  /**
   * Xóa nhiều giọng nói cùng lúc
   * @param {Array<number|string>} ids
   */
  async bulkDeleteVoices(ids) {
    const response = await apiClient.post('/admin/voices/bulk-delete', { ids })
    return response.data
  },
}

export default voiceService

