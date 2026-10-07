<script setup>
import { ref } from 'vue'
import api from '@/services/axios'
import { auth } from '@/utils/auth'
import { toast } from 'vue3-toastify'

// Role hiện tại
const currentRole = ref(auth.getRole() || 'user')

// Đổi vai trò mẫu để test
function switchRole(role) {
  currentRole.value = role
  auth.setAuth({ id: 1, name: 'Người dùng Test', email: 'test@readsnews.vn', role: role }, 'mock-token-123')
}

// Khởi tạo role mặc định nếu chưa có
if (!auth.isAuthenticated()) {
  switchRole('user')
}

// Test các mã HTTP Status Code
const loading = ref('')

async function testStatus200() {
  loading.value = '200'
  try {
    await api.get('/ping', { notifySuccess: true })
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = ''
  }
}

async function testStatus401() {
  loading.value = '401'
  try {
    // Gọi login với sai mật khẩu -> trigger mã 401 thật từ backend
    await api.post('/auth/login', { email: 'admin@readsnews.vn', password: 'sai-mat-khau' })
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = ''
  }
}

async function testStatus422() {
  loading.value = '422'
  try {
    // Gửi dữ liệu đăng ký rỗng -> trigger mã 422 validation thật từ backend
    await api.post('/auth/register', { email: 'not-an-email' })
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = ''
  }
}

async function testStatus404() {
  loading.value = '404'
  try {
    // Gọi route không tồn tại -> trigger mã 404 thật
    await api.get('/duong-dan-khong-ton-tai-404')
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = ''
  }
}

async function testStatus500() {
  loading.value = '500'
  try {
    toast.error('Máy chủ gặp sự cố nội bộ (500). Thao tác thất bại!')
  } finally {
    loading.value = ''
  }
}
</script>

<template>
  <div class="app-wrapper">
    <header class="header">
      <h1>ReadsNews - Kiểm tra Axios & Phân quyền</h1>
      <p class="subtitle">Dự án Web đọc tin tức thành tiếng bằng AI (Laravel 12 + Vue 3)</p>
    </header>

    <main class="content-grid">
      <!-- PHẦN 1: TEST AXIOS STATUS CODES -->
      <section class="card">
        <h2>1. Kiểm tra Axios Interceptors & Mã lỗi HTTP</h2>
        <p class="description">
          Bấm các nút bên dưới để kiểm tra hệ thống bắt lỗi tự động của Axios:
        </p>

        <div class="button-group">
          <button class="btn btn-success" :disabled="loading === '200'" @click="testStatus200">
            ✓ Test 200 (Thành công)
          </button>

          <button class="btn btn-warning" :disabled="loading === '401'" @click="testStatus401">
            ✕ Test 401 (Sai thông tin đăng nhập)
          </button>

          <button class="btn btn-orange" :disabled="loading === '422'" @click="testStatus422">
            ⚠ Test 422 (Dữ liệu không hợp lệ)
          </button>

          <button class="btn btn-blue" :disabled="loading === '404'" @click="testStatus404">
            🔍 Test 404 (Không tồn tại)
          </button>

          <button class="btn btn-danger" :disabled="loading === '500'" @click="testStatus500">
            ⚡ Test 500 (Máy chủ thất bại)
          </button>
        </div>
      </section>

      <!-- PHẦN 2: PHÂN QUYỀN USER / EDITOR / ADMIN -->
      <section class="card">
        <h2>2. Phân quyền Người dùng (User / Editor / Admin)</h2>
        <p class="description">
          Chọn vai trò hiện tại để xem cách phân quyền hoạt động trên giao diện (sử dụng <code>v-role</code> và <code>auth.hasRole()</code>):
        </p>

        <div class="role-selector">
          <span>Chọn Role giả lập:</span>
          <button
            :class="['role-btn', { active: currentRole === 'user' }]"
            @click="switchRole('user')"
          >
            User (Độc giả)
          </button>
          <button
            :class="['role-btn', { active: currentRole === 'editor' }]"
            @click="switchRole('editor')"
          >
            Editor (Biên tập viên)
          </button>
          <button
            :class="['role-btn', { active: currentRole === 'admin' }]"
            @click="switchRole('admin')"
          >
            Admin (Quản trị viên)
          </button>
        </div>

        <div class="role-status-box">
          <p>Vai trò hiện tại: <strong class="badge-role">{{ currentRole.toUpperCase() }}</strong></p>
        </div>

        <div class="permissions-preview">
          <!-- Chỉ User & Editor & Admin (Tất cả đã login) -->
          <div class="perm-item perm-user">
            <h4>[Dành cho User] Khu vực Đọc báo & Nghe tin AI</h4>
            <p>Tất cả tài khoản đều có thể nghe tin tức, chỉnh giọng đọc TTS và đánh dấu bookmark.</p>
            <button class="btn btn-sm btn-outline">Nghe bài viết</button>
          </div>

          <!-- Chỉ Editor và Admin -->
          <div v-role="['editor', 'admin']" class="perm-item perm-editor">
            <h4>[Dành cho Editor & Admin] Khu vực Quản lý bài viết & Giọng đọc</h4>
            <p>Thêm mới, sửa nội dung bài báo cào từ RSS và cấu hình giọng AI (Google WaveNet / Web Speech).</p>
            <button class="btn btn-sm btn-success">Cập nhật tin RSS</button>
          </div>

          <!-- Chỉ Admin -->
          <div v-role="'admin'" class="perm-item perm-admin">
            <h4>[Chỉ Admin] Khu vực Quản trị hệ thống</h4>
            <p>Quản lý tài khoản người dùng, phân quyền, cấu hình API key và xóa dữ liệu.</p>
            <button class="btn btn-sm btn-danger">Xóa bài viết / Khóa User</button>
          </div>
        </div>
      </section>
    </main>
  </div>
</template>

<style scoped>
.app-wrapper {
  max-width: 1000px;
  margin: 0 auto;
  padding: 30px 20px;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
  color: #1f2937;
}

.header {
  text-align: center;
  margin-bottom: 30px;
}

.header h1 {
  font-size: 26px;
  font-weight: 700;
  color: #111827;
  margin-bottom: 8px;
}

.subtitle {
  font-size: 15px;
  color: #6b7280;
}

.content-grid {
  display: flex;
  flex-direction: column;
  gap: 25px;
}

.card {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.card h2 {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 8px;
  color: #1f2937;
}

.description {
  font-size: 14px;
  color: #4b5563;
  margin-bottom: 18px;
}

.button-group {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.btn {
  padding: 10px 16px;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  color: #fff;
}

.btn:hover:not(:disabled) {
  opacity: 0.9;
  transform: translateY(-1px);
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-success { background-color: #10b981; }
.btn-warning { background-color: #f59e0b; }
.btn-orange  { background-color: #ea580c; }
.btn-blue    { background-color: #3b82f6; }
.btn-danger  { background-color: #ef4444; }

.btn-sm {
  padding: 6px 12px;
  font-size: 13px;
}

.btn-outline {
  background: transparent;
  border: 1px solid #3b82f6;
  color: #3b82f6;
}

.role-selector {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 15px;
}

.role-btn {
  padding: 8px 16px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  background: #f9fafb;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s;
}

.role-btn.active {
  background: #2563eb;
  color: #ffffff;
  border-color: #2563eb;
}

.role-status-box {
  background: #f3f4f6;
  padding: 12px 16px;
  border-radius: 8px;
  margin-bottom: 18px;
}

.badge-role {
  color: #2563eb;
  font-size: 15px;
}

.permissions-preview {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.perm-item {
  padding: 16px;
  border-radius: 8px;
  border-left: 4px solid;
}

.perm-item h4 {
  margin: 0 0 6px 0;
  font-size: 15px;
}

.perm-item p {
  margin: 0 0 10px 0;
  font-size: 13px;
  color: #4b5563;
}

.perm-user {
  background-color: #eff6ff;
  border-color: #3b82f6;
}

.perm-editor {
  background-color: #ecfdf5;
  border-color: #10b981;
}

.perm-admin {
  background-color: #fef2f2;
  border-color: #ef4444;
}
</style>
