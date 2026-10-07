import { ref } from 'vue'

const currentUser = ref(JSON.parse(localStorage.getItem('auth_user') || 'null'))
const authToken = ref(localStorage.getItem('auth_token') || null)

export const auth = {
  // Trạng thái reactive
  user: currentUser,
  token: authToken,

  /**
   * Lưu thông tin đăng nhập
   */
  setAuth(user, token = null) {
    currentUser.value = user
    localStorage.setItem('auth_user', JSON.stringify(user))

    if (token) {
      authToken.value = token
      localStorage.setItem('auth_token', token)
    }
  },

  /**
   * Xóa thông tin đăng nhập (đăng xuất)
   */
  clearAuth() {
    currentUser.value = null
    authToken.value = null
    localStorage.removeItem('auth_user')
    localStorage.removeItem('auth_token')
  },

  /**
   * Lấy user hiện tại
   */
  getUser() {
    return currentUser.value
  },

  /**
   * Lấy token
   */
  getToken() {
    return authToken.value
  },

  /**
   * Kiểm tra đã đăng nhập chưa
   */
  isAuthenticated() {
    return !!currentUser.value
  },

  /**
   * Lấy role hiện tại của user
   */
  getRole() {
    return currentUser.value?.role || null
  },

  /**
   * Kiểm tra xem user có phải Admin không
   */
  isAdmin() {
    return currentUser.value?.role === 'admin'
  },

  /**
   * Kiểm tra xem user có phải Editor không
   */
  isEditor() {
    return currentUser.value?.role === 'editor'
  },

  /**
   * Kiểm tra xem user có phải User thông thường không
   */
  isUser() {
    return currentUser.value?.role === 'user'
  },

  /**
   * Kiểm tra user có thuộc danh sách quyền chỉ định hay không.
   * Ví dụ: auth.hasRole('admin') hoặc auth.hasRole(['admin', 'editor'])
   */
  hasRole(roles) {
    if (!currentUser.value) return false

    const userRole = currentUser.value.role
    if (Array.isArray(roles)) {
      return roles.includes(userRole)
    }
    return userRole === roles
  },
}

/**
 * Vue Directive v-role để ẩn/hiện element dựa trên quyền
 * Sử dụng:
 * <button v-role="'admin'">Xóa bài viết</button>
 * <button v-role="['admin', 'editor']">Sửa bài viết</button>
 */
export const vRole = {
  mounted(el, binding) {
    const requiredRoles = binding.value
    if (!auth.hasRole(requiredRoles)) {
      el.parentNode?.removeChild(el)
    }
  },
  updated(el, binding) {
    const requiredRoles = binding.value
    if (!auth.hasRole(requiredRoles)) {
      el.parentNode?.removeChild(el)
    }
  },
}

