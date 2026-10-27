<script setup>
import { computed, onMounted, ref } from 'vue'
import { auth } from '@/utils/auth'
import { profileService } from '@/services/profileService'

// ─── State ────────────────────────────────────────────────────────────────────
const activeTab = ref('info')      // 'info' | 'password'
const isLoading = ref(true)
const isSaving = ref(false)
const isLoggingOut = ref(false)
const flashMessage = ref({ type: '', text: '' })

// Thông tin profile từ API
const profile = ref({
  id: null,
  name: '',
  email: '',
  role: '',
  avatar: null,
  avatar_url: null,
  birth_date: null,
  gender: null,
  phone: null,
  created_at: null,
})

// ─── Inline edit state ────────────────────────────────────────────────────────
const editingField = ref(null)   // null | 'name' | 'birth_date' | 'gender' | 'phone' | 'avatar'
const editValue = ref('')

// Avatar upload
const avatarInputRef = ref(null)
const avatarPreview = ref(null)
const avatarFile = ref(null)

// Đổi mật khẩu
const passwordForm = ref({ current_password: '', password: '', password_confirmation: '' })
const passwordErrors = ref({})
const isChangingPassword = ref(false)

// ─── Computed ─────────────────────────────────────────────────────────────────
const displayAvatar = computed(() => avatarPreview.value || profile.value.avatar_url || null)

const genderLabel = computed(() => {
  const map = { male: 'Nam', female: 'Nữ', other: 'Khác' }
  return profile.value.gender ? map[profile.value.gender] : 'Không chia sẻ'
})

const formattedJoinDate = computed(() => {
  if (!profile.value.created_at) return ''
  const d = new Date(profile.value.created_at)
  return `Tham gia từ ${d.getMonth() + 1}/${d.getFullYear()}`
})

const formattedBirthDate = computed(() => {
  if (!profile.value.birth_date) return 'Chưa có thông tin'
  const parts = profile.value.birth_date.split('-')
  if (parts.length === 3) {
    return `${parts[2]}/${parts[1]}/${parts[0]}`
  }
  return profile.value.birth_date
})

// ─── Fetch profile ─────────────────────────────────────────────────────────────
async function fetchProfile() {
  isLoading.value = true
  try {
    const res = await profileService.getProfile()
    if (res?.success && res.data) {
      profile.value = { ...profile.value, ...res.data }
      const currentUser = auth.getUser() || {}
      auth.setAuth(
        { ...currentUser, ...res.data },
        null,
        !!localStorage.getItem('auth_token')
      )
    }
  } catch (err) {
    notify('error', 'Không thể tải thông tin tài khoản.')
    console.error('Lỗi khi tải profile:', err)
  } finally {
    isLoading.value = false
  }
}

onMounted(fetchProfile)

// ─── Flash notify ─────────────────────────────────────────────────────────────
let flashTimer = null
function notify(type, text) {
  flashMessage.value = { type, text }
  clearTimeout(flashTimer)
  flashTimer = setTimeout(() => { flashMessage.value = { type: '', text: '' } }, 3500)
}

// ─── Inline editing ───────────────────────────────────────────────────────────
function startEdit(field) {
  editingField.value = field
  if (field === 'name')       editValue.value = profile.value.name || ''
  if (field === 'birth_date') editValue.value = profile.value.birth_date || ''
  if (field === 'gender')     editValue.value = profile.value.gender || ''
  if (field === 'phone')      editValue.value = profile.value.phone || ''
}

function cancelEdit() {
  editingField.value = null
  editValue.value = ''
  avatarPreview.value = null
  avatarFile.value = null
}

async function saveField(field) {
  if (isSaving.value) return
  isSaving.value = true
  try {
    const payload = {
      name:       field === 'name'       ? editValue.value.trim() : profile.value.name,
      birth_date: field === 'birth_date' ? (editValue.value || null) : profile.value.birth_date,
      gender:     field === 'gender'     ? (editValue.value || null) : profile.value.gender,
      phone:      field === 'phone'      ? (editValue.value.trim() || null) : profile.value.phone,
    }
    const res = await profileService.updateProfile(payload)
    if (res?.success && res.data) {
      profile.value = { ...profile.value, ...res.data }
      const currentUser = auth.getUser() || {}
      auth.setAuth(
        { ...currentUser, ...res.data },
        null,
        !!localStorage.getItem('auth_token')
      )
      editingField.value = null
      notify('success', 'Cập nhật thông tin thành công!')
    }
  } catch (err) {
    const msg = err.response?.data?.message || 'Có lỗi xảy ra khi cập nhật.'
    notify('error', msg)
  } finally {
    isSaving.value = false
  }
}

// ─── Avatar ───────────────────────────────────────────────────────────────────
function triggerAvatarPick() {
  avatarInputRef.value?.click()
}

function onAvatarPicked(e) {
  const file = e.target.files[0]
  if (!file) return
  if (!file.type.startsWith('image/')) {
    notify('error', 'Vui lòng chọn định dạng file ảnh hợp lệ (JPG, PNG, WebP).')
    return
  }
  if (file.size > 2 * 1024 * 1024) {
    notify('error', 'Dung lượng ảnh tối đa là 2MB.')
    return
  }
  avatarFile.value = file
  avatarPreview.value = URL.createObjectURL(file)
  editingField.value = 'avatar'
}

async function saveAvatar() {
  if (!avatarFile.value || isSaving.value) return
  isSaving.value = true
  try {
    const res = await profileService.updateAvatar(avatarFile.value)
    if (res?.success && res.data) {
      profile.value.avatar = res.data.avatar
      profile.value.avatar_url = res.data.avatar_url
      avatarPreview.value = null
      avatarFile.value = null
      editingField.value = null
      const currentUser = auth.getUser() || {}
      auth.setAuth(
        { ...currentUser, avatar: res.data.avatar, avatar_url: res.data.avatar_url },
        null,
        !!localStorage.getItem('auth_token')
      )
      notify('success', 'Cập nhật ảnh đại diện thành công!')
    }
  } catch (err) {
    notify('error', err.response?.data?.message || 'Không thể tải ảnh đại diện lên.')
  } finally {
    isSaving.value = false
  }
}

// ─── Đổi mật khẩu ────────────────────────────────────────────────────────────
async function submitChangePassword() {
  passwordErrors.value = {}
  let hasError = false

  if (!passwordForm.value.current_password) {
    passwordErrors.value.current_password = 'Vui lòng nhập mật khẩu hiện tại.'
    hasError = true
  }
  if (!passwordForm.value.password) {
    passwordErrors.value.password = 'Vui lòng nhập mật khẩu mới.'
    hasError = true
  } else if (passwordForm.value.password.length < 6) {
    passwordErrors.value.password = 'Mật khẩu phải có ít nhất 6 ký tự.'
    hasError = true
  }
  if (!passwordForm.value.password_confirmation) {
    passwordErrors.value.password_confirmation = 'Vui lòng xác nhận mật khẩu mới.'
    hasError = true
  } else if (passwordForm.value.password !== passwordForm.value.password_confirmation) {
    passwordErrors.value.password_confirmation = 'Xác nhận mật khẩu không trùng khớp.'
    hasError = true
  }

  if (hasError) return

  isChangingPassword.value = true
  try {
    const res = await profileService.changePassword(passwordForm.value)
    if (res?.success) {
      notify('success', 'Đổi mật khẩu thành công!')
      passwordForm.value = { current_password: '', password: '', password_confirmation: '' }
    }
  } catch (err) {
    const errors = err.response?.data?.errors || {}
    passwordErrors.value = Object.fromEntries(
      Object.entries(errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v])
    )
    if (err.response?.data?.message && !Object.keys(errors).length) {
      notify('error', err.response.data.message)
    }
  } finally {
    isChangingPassword.value = false
  }
}

// ─── Đăng xuất ────────────────────────────────────────────────────────────────
function handleLogout() {
  if (isLoggingOut.value) return
  isLoggingOut.value = true
  notify('success', 'Đang đăng xuất khỏi hệ thống...')
  setTimeout(() => {
    auth.clearAuth()
    window.location.href = '/'
  }, 600)
}

// ─── Điều hướng về trang chủ / quản trị ───────────────────────────────────────
function goHome() {
  window.history.pushState(null, '', '/')
  window.dispatchEvent(new PopStateEvent('popstate'))
}

function goAdmin() {
  window.history.pushState(null, '', '/admin')
  window.dispatchEvent(new PopStateEvent('popstate'))
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
    <!-- Header đồng bộ hệ thống -->
    <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur-sm shadow-xs">
      <div class="mx-auto flex h-16 max-w-5xl items-center justify-between px-4 sm:px-6">
        <div class="flex items-center gap-4">
          <button @click="goHome"
            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50 hover:text-sky-600 cursor-pointer">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
              stroke-linecap="round" stroke-linejoin="round">
              <path d="m15 18-6-6 6-6" />
            </svg>
            <span>Trang đọc báo</span>
          </button>
          <button v-if="auth.hasRole(['admin', 'editor'])" @click="goAdmin"
            class="inline-flex items-center gap-1.5 rounded-xl border border-sky-100 bg-sky-50 px-3 py-1.5 text-xs font-bold text-sky-700 transition hover:bg-sky-100 cursor-pointer">
            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect width="18" height="18" x="3" y="3" rx="2" />
              <path d="M3 9h18M9 21V9" />
            </svg>
            <span>Trang quản trị</span>
          </button>
        </div>

        <div class="flex items-center gap-2">
          <img src="/logo/Khuon-Tron-removebg.png" alt="ReadVn" class="size-7 object-contain" />
          <span class="font-display text-sm font-extrabold tracking-tight text-slate-900">
            Read<span class="text-orange-500">Vn</span> <span class="text-slate-400 font-medium">Tài khoản</span>
          </span>
        </div>
      </div>
    </header>

    <!-- Flash Toast Message -->
    <Transition name="slide-down">
      <div v-if="flashMessage.text" :class="[
          'fixed top-5 left-1/2 z-50 -translate-x-1/2 flex items-center gap-2.5 rounded-2xl border px-5 py-3 text-sm font-semibold shadow-xl backdrop-blur-md',
          flashMessage.type === 'success'
            ? 'border-emerald-200 bg-white/95 text-emerald-800 shadow-emerald-500/10'
            : 'border-rose-200 bg-white/95 text-rose-800 shadow-rose-500/10'
        ]">
        <span v-if="flashMessage.type === 'success'" class="flex size-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
          <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <polyline points="20 6 9 17 4 12" />
          </svg>
        </span>
        <span v-else class="flex size-6 items-center justify-center rounded-full bg-rose-100 text-rose-600">
          <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="8" x2="12" y2="12" />
            <line x1="12" y1="16" x2="12.01" y2="16" />
          </svg>
        </span>
        <span>{{ flashMessage.text }}</span>
      </div>
    </Transition>

    <!-- Skeleton Loading -->
    <div v-if="isLoading" class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
      <div class="flex flex-col gap-6 md:flex-row md:items-start animate-pulse">
        <div class="w-full md:w-60 shrink-0 space-y-4">
          <div class="rounded-2xl border border-slate-200/70 bg-white p-6 shadow-xs flex flex-col items-center">
            <div class="size-20 rounded-full bg-slate-200 mb-3"></div>
            <div class="h-4 w-32 bg-slate-200 rounded mb-2"></div>
            <div class="h-3 w-24 bg-slate-100 rounded"></div>
          </div>
          <div class="rounded-2xl border border-slate-200/70 bg-white p-2 shadow-xs space-y-2">
            <div class="h-10 bg-slate-100 rounded-xl"></div>
            <div class="h-10 bg-slate-100 rounded-xl"></div>
            <div class="h-10 bg-slate-100 rounded-xl"></div>
          </div>
        </div>

        <div class="flex-1 space-y-6">
          <div class="rounded-2xl border border-slate-200/70 bg-white p-6 shadow-xs space-y-4">
            <div class="h-5 w-44 bg-slate-200 rounded"></div>
            <div class="h-16 bg-slate-50 rounded-xl"></div>
            <div class="h-16 bg-slate-50 rounded-xl"></div>
            <div class="h-16 bg-slate-50 rounded-xl"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Container -->
    <main v-else class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
      <div class="flex flex-col gap-6 md:flex-row md:items-start">

        <!-- ─── SIDEBAR TRÁI (THEO MINH HỌA) ─── -->
        <aside class="w-full md:w-64 shrink-0 space-y-4">
          <!-- Card User Card -->
          <div class="rounded-2xl border border-slate-200/80 bg-white p-6 text-center shadow-xs">
            <div class="relative mx-auto mb-3.5 size-20">
              <img v-if="displayAvatar" :src="displayAvatar" alt="Avatar"
                class="size-20 rounded-full object-cover border-2 border-white shadow-md ring-2 ring-slate-100" />
              <div v-else
                class="flex size-20 items-center justify-center rounded-full bg-gradient-to-br from-sky-500 to-indigo-600 text-2xl font-black text-white shadow-md ring-2 ring-slate-100">
                {{ profile.name?.slice(0, 1)?.toUpperCase() || 'U' }}
              </div>
            </div>

            <h1 class="truncate text-base font-bold text-slate-900" :title="profile.name">{{ profile.name }}</h1>
            <p class="mt-1 text-xs text-slate-400">{{ formattedJoinDate }}</p>

            <div class="mt-3 inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-bold"
              :class="profile.role === 'admin' ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-200/60' : (profile.role === 'editor' ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200/60' : 'bg-slate-100 text-slate-600')">
              <span class="size-1.5 rounded-full" :class="profile.role === 'admin' ? 'bg-amber-500' : (profile.role === 'editor' ? 'bg-indigo-500' : 'bg-slate-400')"></span>
              {{ profile.role === 'admin' ? 'Quản trị viên' : profile.role === 'editor' ? 'Biên tập viên' : 'Độc giả' }}
            </div>
          </div>

          <!-- Menu điều hướng -->
          <nav class="rounded-2xl border border-slate-200/80 bg-white p-2 shadow-xs space-y-1" aria-label="Menu cá nhân">
            <button @click="activeTab = 'info'" :class="[
                'flex w-full items-center gap-3 rounded-xl px-3.5 py-2.5 text-start text-xs font-semibold transition cursor-pointer',
                activeTab === 'info'
                  ? 'bg-slate-900 text-white shadow-xs'
                  : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900'
              ]">
              <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
              </svg>
              <span>Thông tin chung</span>
            </button>

            <button @click="activeTab = 'password'" :class="[
                'flex w-full items-center gap-3 rounded-xl px-3.5 py-2.5 text-start text-xs font-semibold transition cursor-pointer',
                activeTab === 'password'
                  ? 'bg-slate-900 text-white shadow-xs'
                  : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900'
              ]">
              <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
              </svg>
              <span>Đổi mật khẩu</span>
            </button>

            <button @click="handleLogout" :disabled="isLoggingOut"
              class="flex w-full items-center gap-3 rounded-xl px-3.5 py-2.5 text-start text-xs font-semibold text-rose-600 transition hover:bg-rose-50 disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer">
              <svg v-if="isLoggingOut" class="size-4 animate-spin text-rose-600" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
              </svg>
              <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                <polyline points="16 17 21 12 16 7" />
                <line x1="21" y1="12" x2="9" y2="12" />
              </svg>
              <span>Thoát</span>
            </button>
          </nav>

          <!-- Box Hỗ trợ (giống minh họa) -->
          <div class="rounded-2xl border border-slate-200/80 bg-white p-4 text-[11px] text-slate-500 shadow-xs">
            <p class="font-semibold text-slate-700 mb-1">Cần hỗ trợ?</p>
            <p>Liên hệ bộ phận chăm sóc độc giả qua email:</p>
            <a href="mailto:support@readsnews.vn" class="mt-1 block font-medium text-sky-600 hover:underline">
              support@readsnews.vn
            </a>
          </div>
        </aside>

        <!-- ─── NỘI DUNG CHÍNH (THEO MINH HỌA) ─── -->
        <section class="flex-1 space-y-6">

          <!-- TAB 1: THÔNG TIN TÀI KHOẢN & CÁ NHÂN -->
          <div v-if="activeTab === 'info'" class="space-y-6">

            <!-- KHỐI 1: THÔNG TIN TÀI KHOẢN -->
            <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
              <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-4">
                <h2 class="font-display text-base font-bold text-slate-900">Thông tin tài khoản</h2>
              </div>

              <div class="divide-y divide-slate-100">
                <!-- Hàng 1: Ảnh đại diện -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-6">
                  <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Ảnh đại diện</span>
                    <div class="mt-2.5 flex items-center gap-4">
                      <div class="relative size-14 shrink-0">
                        <img v-if="displayAvatar" :src="displayAvatar" alt="Avatar Preview"
                          class="size-14 rounded-full object-cover border-2 border-slate-100 shadow-xs" />
                        <div v-else
                          class="flex size-14 items-center justify-center rounded-full bg-slate-100 text-lg font-bold text-slate-600 border border-slate-200">
                          {{ profile.name?.slice(0, 1)?.toUpperCase() || 'U' }}
                        </div>
                      </div>

                      <div v-if="editingField === 'avatar'" class="flex items-center gap-2">
                        <button @click="saveAvatar" :disabled="isSaving"
                          class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-sky-600 px-3 text-xs font-bold text-white shadow-xs transition hover:bg-sky-700 disabled:opacity-60 cursor-pointer">
                          {{ isSaving ? 'Đang lưu...' : 'Lưu ảnh mới' }}
                        </button>
                        <button @click="cancelEdit"
                          class="h-8 rounded-lg border border-slate-200 px-3 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                          Hủy
                        </button>
                      </div>
                      <p v-else class="text-xs text-slate-500">Định dạng JPG, PNG hoặc WebP tối đa 2MB.</p>
                    </div>
                  </div>

                  <div>
                    <input ref="avatarInputRef" type="file" accept="image/jpeg,image/png,image/webp,image/jpg" class="hidden" @change="onAvatarPicked" />
                    <button @click="triggerAvatarPick"
                      class="text-xs font-semibold text-sky-600 hover:text-sky-700 hover:underline cursor-pointer">
                      Thay ảnh đại diện
                    </button>
                  </div>
                </div>

                <!-- Hàng 2: Họ tên -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-6">
                  <div class="flex-1 min-w-0">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Họ tên</span>
                    
                    <div v-if="editingField === 'name'" class="mt-2 flex max-w-md items-center gap-2">
                      <input v-model="editValue" type="text" maxlength="100" placeholder="Nhập họ và tên"
                        class="flex-1 rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-800 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100" />
                      <button @click="saveField('name')" :disabled="isSaving"
                        class="h-9 rounded-xl bg-sky-600 px-3.5 text-xs font-bold text-white transition hover:bg-sky-700 disabled:opacity-60 cursor-pointer">
                        {{ isSaving ? 'Lưu...' : 'Lưu' }}
                      </button>
                      <button @click="cancelEdit"
                        class="h-9 rounded-xl border border-slate-200 px-3 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                        Hủy
                      </button>
                    </div>
                    <p v-else class="mt-1 text-sm font-semibold text-slate-800">{{ profile.name }}</p>
                  </div>

                  <button v-if="editingField !== 'name'" @click="startEdit('name')"
                    class="text-xs font-semibold text-sky-600 hover:text-sky-700 hover:underline cursor-pointer">
                    Thay đổi
                  </button>
                </div>

                <!-- Hàng 3: Email -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-6">
                  <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Email</span>
                    <p class="mt-1 text-sm font-medium text-slate-800 font-mono">{{ profile.email }}</p>
                  </div>
                  <span class="text-xs text-slate-400 italic">Cố định</span>
                </div>
              </div>
            </div>

            <!-- KHỐI 2: THÔNG TIN CÁ NHÂN (THEO MINH HỌA) -->
            <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
              <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-4">
                <h2 class="font-display text-base font-bold text-slate-900">Thông tin cá nhân</h2>
              </div>

              <div class="divide-y divide-slate-100">
                <!-- Hàng 1: Ngày sinh -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-6">
                  <div class="flex-1 min-w-0">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Ngày sinh</span>

                    <div v-if="editingField === 'birth_date'" class="mt-2 flex max-w-md items-center gap-2">
                      <input v-model="editValue" type="date"
                        class="flex-1 rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-800 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100" />
                      <button @click="saveField('birth_date')" :disabled="isSaving"
                        class="h-9 rounded-xl bg-sky-600 px-3.5 text-xs font-bold text-white transition hover:bg-sky-700 disabled:opacity-60 cursor-pointer">
                        {{ isSaving ? 'Lưu...' : 'Lưu' }}
                      </button>
                      <button @click="cancelEdit"
                        class="h-9 rounded-xl border border-slate-200 px-3 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                        Hủy
                      </button>
                    </div>
                    <p v-else class="mt-1 text-sm" :class="profile.birth_date ? 'font-medium text-slate-800' : 'text-slate-400 italic'">
                      {{ formattedBirthDate }}
                    </p>
                  </div>

                  <button v-if="editingField !== 'birth_date'" @click="startEdit('birth_date')"
                    class="text-xs font-semibold text-sky-600 hover:text-sky-700 hover:underline cursor-pointer">
                    Thay đổi
                  </button>
                </div>

                <!-- Hàng 2: Giới tính -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-6">
                  <div class="flex-1 min-w-0">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Giới tính</span>

                    <div v-if="editingField === 'gender'" class="mt-2 flex max-w-md items-center gap-2">
                      <select v-model="editValue"
                        class="flex-1 rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-800 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                        <option value="">Không chia sẻ</option>
                        <option value="male">Nam</option>
                        <option value="female">Nữ</option>
                        <option value="other">Khác</option>
                      </select>
                      <button @click="saveField('gender')" :disabled="isSaving"
                        class="h-9 rounded-xl bg-sky-600 px-3.5 text-xs font-bold text-white transition hover:bg-sky-700 disabled:opacity-60 cursor-pointer">
                        {{ isSaving ? 'Lưu...' : 'Lưu' }}
                      </button>
                      <button @click="cancelEdit"
                        class="h-9 rounded-xl border border-slate-200 px-3 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                        Hủy
                      </button>
                    </div>
                    <p v-else class="mt-1 text-sm" :class="profile.gender ? 'font-medium text-slate-800' : 'text-slate-400 italic'">
                      {{ genderLabel }}
                    </p>
                  </div>

                  <button v-if="editingField !== 'gender'" @click="startEdit('gender')"
                    class="text-xs font-semibold text-sky-600 hover:text-sky-700 hover:underline cursor-pointer">
                    Thay đổi
                  </button>
                </div>

                <!-- Hàng 3: Số điện thoại cá nhân -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-6">
                  <div class="flex-1 min-w-0">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Số điện thoại cá nhân</span>

                    <div v-if="editingField === 'phone'" class="mt-2 flex max-w-md items-center gap-2">
                      <input v-model="editValue" type="tel" maxlength="20" placeholder="VD: 0901234567"
                        class="flex-1 rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-800 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100" />
                      <button @click="saveField('phone')" :disabled="isSaving"
                        class="h-9 rounded-xl bg-sky-600 px-3.5 text-xs font-bold text-white transition hover:bg-sky-700 disabled:opacity-60 cursor-pointer">
                        {{ isSaving ? 'Lưu...' : 'Lưu' }}
                      </button>
                      <button @click="cancelEdit"
                        class="h-9 rounded-xl border border-slate-200 px-3 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                        Hủy
                      </button>
                    </div>
                    <p v-else class="mt-1 text-sm" :class="profile.phone ? 'font-medium text-slate-800' : 'text-slate-400 italic'">
                      {{ profile.phone || 'Chưa có thông tin' }}
                    </p>
                  </div>

                  <button v-if="editingField !== 'phone'" @click="startEdit('phone')"
                    class="text-xs font-semibold text-sky-600 hover:text-sky-700 hover:underline cursor-pointer">
                    Thay đổi
                  </button>
                </div>
              </div>
            </div>

          </div>

          <!-- TAB 2: ĐỔI MẬT KHẨU -->
          <div v-else-if="activeTab === 'password'">
            <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
              <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-4">
                <h2 class="font-display text-base font-bold text-slate-900">Đổi mật khẩu tài khoản</h2>
                <p class="mt-0.5 text-xs text-slate-500">Mật khẩu mới yêu cầu tối thiểu 6 ký tự để đảm bảo an toàn.</p>
              </div>

              <form @submit.prevent="submitChangePassword" novalidate class="p-6 space-y-4 max-w-xl">
                <!-- Mật khẩu hiện tại -->
                <div>
                  <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">
                    Mật khẩu hiện tại
                  </label>
                  <input v-model="passwordForm.current_password" type="password" placeholder="Nhập mật khẩu hiện tại"
                    :class="[
                      'w-full rounded-xl border px-3.5 py-2.5 text-xs font-medium text-slate-800 transition focus:outline-none focus:ring-2',
                      passwordErrors.current_password
                        ? 'border-rose-400 bg-rose-50/20 focus:border-rose-500 focus:ring-rose-100'
                        : 'border-slate-300 bg-white focus:border-sky-500 focus:ring-sky-100'
                    ]" />
                  <p v-if="passwordErrors.current_password" class="mt-1.5 text-xs text-rose-500 font-medium">
                    {{ passwordErrors.current_password }}
                  </p>
                </div>

                <!-- Mật khẩu mới -->
                <div>
                  <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">
                    Mật khẩu mới
                  </label>
                  <input v-model="passwordForm.password" type="password" placeholder="Tối thiểu 6 ký tự"
                    :class="[
                      'w-full rounded-xl border px-3.5 py-2.5 text-xs font-medium text-slate-800 transition focus:outline-none focus:ring-2',
                      passwordErrors.password
                        ? 'border-rose-400 bg-rose-50/20 focus:border-rose-500 focus:ring-rose-100'
                        : 'border-slate-300 bg-white focus:border-sky-500 focus:ring-sky-100'
                    ]" />
                  <p v-if="passwordErrors.password" class="mt-1.5 text-xs text-rose-500 font-medium">
                    {{ passwordErrors.password }}
                  </p>
                </div>

                <!-- Xác nhận mật khẩu mới -->
                <div>
                  <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">
                    Xác nhận mật khẩu mới
                  </label>
                  <input v-model="passwordForm.password_confirmation" type="password" placeholder="Nhập lại mật khẩu mới"
                    :class="[
                      'w-full rounded-xl border px-3.5 py-2.5 text-xs font-medium text-slate-800 transition focus:outline-none focus:ring-2',
                      passwordErrors.password_confirmation
                        ? 'border-rose-400 bg-rose-50/20 focus:border-rose-500 focus:ring-rose-100'
                        : 'border-slate-300 bg-white focus:border-sky-500 focus:ring-sky-100'
                    ]" />
                  <p v-if="passwordErrors.password_confirmation" class="mt-1.5 text-xs text-rose-500 font-medium">
                    {{ passwordErrors.password_confirmation }}
                  </p>
                </div>

                <div class="pt-2">
                  <button type="submit" :disabled="isChangingPassword"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-sky-600 px-5 text-xs font-bold text-white shadow-xs transition hover:bg-sky-700 active:scale-98 disabled:opacity-60 cursor-pointer">
                    <svg v-if="isChangingPassword" class="size-4 animate-spin" viewBox="0 0 24 24" fill="none">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                    <span>{{ isChangingPassword ? 'Đang cập nhật...' : 'Cập nhật mật khẩu' }}</span>
                  </button>
                </div>
              </form>
            </div>
          </div>

        </section>
      </div>
    </main>
  </div>
</template>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.25s ease-out;
}
.slide-down-enter-from {
  opacity: 0;
  transform: translate(-50%, -10px);
}
.slide-down-leave-to {
  opacity: 0;
  transform: translate(-50%, -6px);
}
</style>

