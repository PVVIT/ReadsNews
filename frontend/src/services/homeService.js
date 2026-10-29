import apiClient from './axios'

export const homeService = {
  /**
   * Lấy toàn bộ dữ liệu tổng hợp cho trang chủ (categories, hero, category_sections, popular, stats)
   */
  async getHomeData() {
    const response = await apiClient.get('/home')
    return response.data
  },

  /**
   * Lấy danh sách bài viết theo danh mục / tìm kiếm / phân trang
   * @param {Object} params { page, per_page, category_id, category_slug, search, sort }
   */
  async getArticles(params = {}) {
    const response = await apiClient.get('/articles', { params })
    return response.data
  },

  /**
   * Lấy chi tiết bài báo
   * @param {number|string} id
   */
  async getArticle(id) {
    const response = await apiClient.get(`/articles/${id}`)
    return response.data
  },

  /**
   * Lấy danh sách danh mục
   */
  async getCategories() {
    const response = await apiClient.get('/categories')
    return response.data
  },
}

