import apiClient from './axios'

export const articleService = {
  /**
   * Lấy danh sách bài báo (Admin, có phân trang)
   * @param {Object} params { search, category_id, source_id, status, per_page, page }
   */
  async getArticles(params = {}) {
    const response = await apiClient.get('/admin/articles', { params })
    return response.data
  },

  /**
   * Lấy chi tiết một bài báo
   * @param {number|string} id
   */
  async getArticle(id) {
    const response = await apiClient.get(`/admin/articles/${id}`)
    return response.data
  },

  /**
   * Thêm bài báo mới (dùng FormData để hỗ trợ upload ảnh thumbnail)
   * @param {FormData} formData
   */
  async createArticle(formData) {
    const response = await apiClient.post('/admin/articles', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return response.data
  },

  /**
   * Cập nhật bài báo (dùng FormData, POST thay PUT để hỗ trợ file upload)
   * @param {number|string} id
   * @param {FormData} formData
   */
  async updateArticle(id, formData) {
    // Backend nhận POST /{id} vì multipart/form-data không hoạt động tốt với PUT
    const response = await apiClient.post(`/admin/articles/${id}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return response.data
  },

  /**
   * Bật/Tắt xuất bản bài báo (Đã xuất bản ↔ Bản nháp)
   * @param {number|string} id
   */
  async togglePublish(id) {
    const response = await apiClient.patch(`/admin/articles/${id}/toggle-publish`)
    return response.data
  },

  /**
   * Xóa bài báo
   * @param {number|string} id
   */
  async deleteArticle(id) {
    const response = await apiClient.delete(`/admin/articles/${id}`)
    return response.data
  },

  /**
   * Xóa nhiều bài báo cùng lúc
   * @param {Array<number|string>} ids
   */
  async bulkDeleteArticles(ids) {
    const response = await apiClient.post('/admin/articles/bulk-delete', { ids })
    return response.data
  },
}

export const sourceService = {
  /**
   * Lấy danh sách nguồn báo
   */
  async getSources() {
    const response = await apiClient.get('/admin/sources')
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

