import apiClient from './axios'

export const bookmarkService = {
  /**
   * Lấy danh sách ID các bài báo đã bookmark của người dùng
   */
  async getBookmarkIds() {
    const response = await apiClient.get('/bookmarks/ids')
    return response.data
  },

  /**
   * Lấy danh sách bài báo đã bookmark (có phân trang và quan hệ chuyên mục, nguồn)
   * @param {Object} params { page, per_page }
   */
  async getBookmarks(params = {}) {
    const response = await apiClient.get('/bookmarks', { params })
    return response.data
  },

  /**
   * Bật/Tắt bookmark bài báo vào cơ sở dữ liệu
   * @param {number|string} articleId
   */
  async toggleBookmark(articleId) {
    const response = await apiClient.post(`/bookmarks/${articleId}`)
    return response.data
  },

  /**
   * Xóa bookmark bài báo
   * @param {number|string} articleId
   */
  async removeBookmark(articleId) {
    const response = await apiClient.delete(`/bookmarks/${articleId}`)
    return response.data
  },
}

