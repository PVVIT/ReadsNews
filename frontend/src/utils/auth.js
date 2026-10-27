import { ref } from 'vue'

function getInitialUser() {
  const local = localStorage.getItem('auth_user')
  if (local) {
    try {
      return JSON.parse(local)
    } catch (_) {}
  }
  const session = sessionStorage.getItem('auth_user')
  if (session) {
    try {
      return JSON.parse(session)
    } catch (_) {}
  }
  return null
}

function getInitialToken() {
  return localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token') || null
}

const currentUser = ref(getInitialUser())
const authToken = ref(getInitialToken())

export const auth = {
  // Trạng thái reactive
  user: currentUser,
  token: authToken,

  /**
   * Lưu thông tin đăng nhập
   * @param {Object} user Thông tin tài khoản người dùng
   * @param {String|null} token Mã JWT / Bearer Token
   * @param {Boolean} remember Cờ ghi nhớ đăng nhập (true: lưu vào localStorage lâu dài, false: lưu tạm vào sessionStorage)
   */
  setAuth(user, token = null, remember = true) {
    currentUser.value = user
    if (token) {
      authToken.value = token
    }

    if (remember) {
      localStorage.setItem('auth_user', JSON.stringify(user))
      if (token) {
        localStorage.setItem('auth_token', token)
      }
      // Dọn dẹp session cũ nếu có
      sessionStorage.removeItem('auth_user')
      sessionStorage.removeItem('auth_token')
    } else {
      sessionStorage.setItem('auth_user', JSON.stringify(user))
      if (token) {
        sessionStorage.setItem('auth_token', token)
      }
      // Xóa trong localStorage để đảm bảo tài khoản chỉ lưu tạm trong phiên duyệt web
      localStorage.removeItem('auth_user')
      localStorage.removeItem('auth_token')
    }
  },

  /**
   * Xóa thông tin đăng nhập (đăng xuất khỏi cả localStorage và sessionStorage)
   */
  clearAuth() {
    currentUser.value = null
    authToken.value = null
    localStorage.removeItem('auth_user')
    localStorage.removeItem('auth_token')
    sessionStorage.removeItem('auth_user')
    sessionStorage.removeItem('auth_token')
  },

  /**
   * Lấy user hiện tại
   */
  getUser() {
    if (!currentUser.value) {
      currentUser.value = getInitialUser()
    }
    return currentUser.value
  },

  /**
   * Lấy token
   */
  getToken() {
    if (!authToken.value) {
      authToken.value = getInitialToken()
    }
    return authToken.value
  },

  /**
   * Kiểm tra đã đăng nhập chưa
   */
  isAuthenticated() {
    return !!this.getUser()
  },

  /**
   * Lấy role hiện tại của user
   */
  getRole() {
    return this.getUser()?.role || null
  },

  /**
   * Kiểm tra xem user có phải Admin không
   */
  isAdmin() {
    return this.getUser()?.role === 'admin'
  },

  /**
   * Kiểm tra xem user có phải Editor không
   */
  isEditor() {
    return this.getUser()?.role === 'editor'
  },

  /**
   * Kiểm tra xem user có phải User thông thường không
   */
  isUser() {
    return this.getUser()?.role === 'user'
  },

  /**
   * Kiểm tra user có thuộc danh sách quyền chỉ định hay không.
   * Ví dụ: auth.hasRole('admin') hoặc auth.hasRole(['admin', 'editor'])
   */
  hasRole(roles) {
    const user = this.getUser()
    if (!user) return false

    const userRole = user.role
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
