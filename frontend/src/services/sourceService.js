import apiClient from './axios'

export const sourceService = {
  /**
   * Lấy danh sách nguồn báo (Admin, có phân trang hoặc all)
   * @param {Object} params { search, category_id, status, per_page, page, all }
   */
  async getSources(params = {}) {
    const response = await apiClient.get('/admin/sources', { params })
    return response.data
  },

  /**
   * Lấy chi tiết một nguồn báo
   * @param {number|string} id
   */
  async getSource(id) {
    const response = await apiClient.get(`/admin/sources/${id}`)
    return response.data
  },

  /**
   * Thêm nguồn báo mới
   * @param {Object} data { name, slug, website_url, rss_url, category_id, is_active }
   */
  async createSource(data) {
    const response = await apiClient.post('/admin/sources', data)
    return response.data
  },

  /**
   * Cập nhật nguồn báo
   * @param {number|string} id
   * @param {Object} data
   */
  async updateSource(id, data) {
    const response = await apiClient.put(`/admin/sources/${id}`, data)
    return response.data
  },

  /**
   * Bật/Tắt trạng thái hoạt động của nguồn báo
   * @param {number|string} id
   */
  async toggleStatus(id) {
    const response = await apiClient.patch(`/admin/sources/${id}/toggle-status`)
    return response.data
  },

  /**
   * Xóa một nguồn báo
   * @param {number|string} id
   */
  async deleteSource(id) {
    const response = await apiClient.delete(`/admin/sources/${id}`)
    return response.data
  },

  /**
   * Xóa nhiều nguồn báo cùng lúc
   * @param {Array<number|string>} ids
   */
  async bulkDeleteSources(ids) {
    const response = await apiClient.post('/admin/sources/bulk-delete', { ids })
    return response.data
  },

  /**
   * Kích hoạt crawl RSS cho một nguồn báo
   * @param {number|string} id
   */
  async crawlRss(id) {
    const response = await apiClient.post(`/admin/sources/${id}/crawl`)
    return response.data
  },
}

export default sourceService

