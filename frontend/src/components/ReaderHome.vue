<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { auth } from '@/utils/auth'
import { authService } from '@/services/authService'
import { homeService } from '@/services/homeService'
import { toast } from 'vue3-toastify'
import ThreeBackground from './home/ThreeBackground.vue'

// --- State Dữ liệu thật ---
const categories = ref([
  { id: 'all', name: 'Trang chủ', slug: 'all' },
])
const homeData = ref(null)
const filterArticles = ref([])
const totalFilterArticles = ref(0)
const currentFilterPage = ref(1)
const hasMoreFilterPages = ref(false)

const isLoading = ref(true)
const isLoadingMore = ref(false)

// Trạng thái bộ lọc
const activeCategory = ref('all') // category_id or 'all'
const activeCategoryName = ref('Trang chủ')
const activeView = ref('home') // 'home' | 'latest' | 'popular' | 'saved' | 'category' | 'search'
const searchQuery = ref('')
const searchDebounceTimer = ref(null)

const savedIds = ref(readSavedIds())
const selectedArticle = ref(null)
const notice = ref('')
let noticeTimer = null

// --- Trình phát âm thanh SpeechSynthesis ---
const playingId = ref(null)
const activePlayingArticle = ref(null)
const isPaused = ref(false)
const speechRate = ref(1)
const playbackProgress = ref(0)
let progressInterval = null

// --- User Auth & Header Dropdown ---
const currentUser = computed(() => auth.getUser())
const showUserDropdown = ref(false)
const userDropdownRef = ref(null)

function toggleUserDropdown() {
  showUserDropdown.value = !showUserDropdown.value
}

function closeUserDropdown(e) {
  if (userDropdownRef.value && !userDropdownRef.value.contains(e.target)) {
    showUserDropdown.value = false
  }
}

function navigateTo(path) {
  showUserDropdown.value = false
  window.history.pushState(null, '', path)
  window.dispatchEvent(new PopStateEvent('popstate'))
}

const readerName = computed(() => {
  if (currentUser.value?.name) {
    return currentUser.value.name.split(' ').at(-1)
  }
  return 'bạn đọc'
})

// Ngày tháng & thời tiết hiển thị kiểu Báo Tuổi Trẻ
const currentDateStr = computed(() => {
  const now = new Date()
  const days = ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy']
  const dayName = days[now.getDay()]
  const date = now.getDate()
  const month = now.getMonth() + 1
  const year = now.getFullYear()
  return `${dayName}, ngày ${date}-${month}-${year}`
})

// Chuẩn hóa bài viết từ DB API sang format frontend
function normalizeArticle(item) {
  if (!item) return null
  const publishedDate = item.published_at ? new Date(item.published_at) : new Date(item.created_at || Date.now())
  const diffMinutes = Math.max(1, Math.round((Date.now() - publishedDate.getTime()) / (1000 * 60)))
  let timeStr = 'Vừa xong'
  if (diffMinutes < 60) {
    timeStr = `${diffMinutes} phút trước`
  } else if (diffMinutes < 1440) {
    timeStr = `${Math.floor(diffMinutes / 60)} giờ trước`
  } else {
    timeStr = `${Math.floor(diffMinutes / 1440)} ngày trước`
  }

  const wordCount = item.word_count || (item.content ? item.content.replace(/<[^>]*>/g, '').split(/\s+/).length : 300)
  const minutes = Math.max(1, Math.ceil(wordCount / 180))

  return {
    ...item,
    image: item.thumbnail || '/images/home/city.png',
    categoryName: item.category?.name || 'Thời sự',
    categorySlug: item.category?.slug || 'thoi-su',
    sourceName: item.source?.name || 'Tuổi Trẻ',
    time: timeStr,
    minutes,
    views: item.view_count || Math.floor(Math.random() * 2000 + 450),
    body: item.content || item.summary || '',
    cleanBody: (item.content || item.summary || '').replace(/<[^>]*>/g, ''),
    aiSummary: item.ai_summary || item.summary || '',
    commentsCount: Math.floor(Math.random() * 8 + 1),
    voice: 'Trợ lý AI Tiếng Việt (vi-VN)',
  }
}

// Gọi API lấy toàn bộ dữ liệu trang chủ
async function loadHomeData() {
  isLoading.value = true
  try {
    const res = await homeService.getHomeData()
    if (res.success && res.data) {
      const data = res.data

      categories.value = [
        { id: 'all', name: 'Trang chủ', slug: 'all' },
        ...(data.categories || []).map((c) => ({
          id: c.id,
          name: c.name,
          slug: c.slug,
        })),
      ]

      homeData.value = {
        hero: {
          main: normalizeArticle(data.hero?.main),
          sub: (data.hero?.sub || []).map(normalizeArticle),
          trending: (data.hero?.trending || []).map(normalizeArticle),
        },
        category_sections: (data.category_sections || []).map((sec) => ({
          category: sec.category,
          main: normalizeArticle(sec.main),
          items: (sec.items || []).map(normalizeArticle),
        })),
        popular: (data.popular || []).map(normalizeArticle),
        total_articles: data.total_articles || 0,
      }
    }
  } catch (error) {
    console.error('Lỗi tải dữ liệu trang chủ:', error)
    notify('Không thể kết nối đến máy chủ tin tức')
  } finally {
    isLoading.value = false
  }
}

// Gọi API lấy danh sách bài viết khi chọn 1 chuyên mục hoặc tìm kiếm
async function loadFilterArticles(page = 1, append = false) {
  if (page === 1) {
    isLoading.value = true
  } else {
    isLoadingMore.value = true
  }

  try {
    const params = {
      page,
      per_page: 15,
      sort: activeView.value === 'popular' ? 'popular' : 'latest',
    }

    if (activeCategory.value !== 'all') {
      params.category_id = activeCategory.value
    }

    if (searchQuery.value.trim()) {
      params.search = searchQuery.value.trim()
    }

    const res = await homeService.getArticles(params)
    const rawArticles = res.data || []
    const normalized = rawArticles.map(normalizeArticle)

    if (append) {
      filterArticles.value = [...filterArticles.value, ...normalized]
    } else {
      filterArticles.value = normalized
    }

    currentFilterPage.value = page
    if (res.meta) {
      totalFilterArticles.value = res.meta.total || normalized.length
      hasMoreFilterPages.value = res.meta.current_page < res.meta.last_page
    } else {
      hasMoreFilterPages.value = normalized.length >= 15
    }
  } catch (error) {
    console.error('Lỗi lọc bài viết:', error)
  } finally {
    isLoading.value = false
    isLoadingMore.value = false
  }
}

// Xử lý chuyển đổi danh mục
function setCategory(cat) {
  activeCategory.value = cat.id
  activeCategoryName.value = cat.name

  if (cat.id === 'all' && !searchQuery.value.trim() && activeView.value !== 'saved') {
    activeView.value = 'home'
    loadHomeData()
  } else {
    activeView.value = 'category'
    loadFilterArticles(1)
  }
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// Tải thêm bài viết trang kế
function loadMore() {
  if (!isLoadingMore.value && hasMoreFilterPages.value) {
    loadFilterArticles(currentFilterPage.value + 1, true)
  }
}

// Đổi chế độ xem
function setView(view) {
  activeView.value = view
  if (view === 'home') {
    activeCategory.value = 'all'
    activeCategoryName.value = 'Trang chủ'
    searchQuery.value = ''
    loadHomeData()
  } else if (view === 'saved') {
    // Chế độ xem bài viết đã lưu
  } else {
    loadFilterArticles(1)
  }
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// Debounce tìm kiếm
watch(searchQuery, (val) => {
  clearTimeout(searchDebounceTimer.value)
  searchDebounceTimer.value = setTimeout(() => {
    if (val.trim()) {
      activeView.value = 'search'
      loadFilterArticles(1)
    } else if (activeCategory.value === 'all') {
      activeView.value = 'home'
      loadHomeData()
    } else {
      loadFilterArticles(1)
    }
  }, 400)
})

// Tiêu đề chế độ xem hiện tại
const currentViewTitle = computed(() => {
  if (activeView.value === 'saved') return 'Bài viết đã lưu'
  if (searchQuery.value.trim()) return `Kết quả tìm kiếm: "${searchQuery.value}"`
  if (activeCategory.value !== 'all') return `Chuyên mục: ${activeCategoryName.value}`
  return 'Danh sách tin tức'
})

// LocalStorage lưu trữ bài viết
function readSavedIds() {
  try {
    return JSON.parse(localStorage.getItem('readsnews_saved') || '[]')
  } catch {
    return []
  }
}

watch(
  savedIds,
  (val) => {
    localStorage.setItem('readsnews_saved', JSON.stringify(val))
  },
  { deep: true },
)

function toggleSaved(article) {
  if (!article) return
  if (savedIds.value.includes(article.id)) {
    savedIds.value = savedIds.value.filter((id) => id !== article.id)
    notify('Đã xóa khỏi danh sách lưu trữ')
  } else {
    savedIds.value.push(article.id)
    notify('Đã lưu bài viết vào thư viện cá nhân ✨')
  }
}

function notify(msg) {
  notice.value = msg
  clearTimeout(noticeTimer)
  noticeTimer = setTimeout(() => {
    notice.value = ''
  }, 2800)
}

function scrollToTop() {
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// --- Xử lý đọc văn bản SpeechSynthesis ---
function toggleSpeech(article) {
  if (!article) return
  if (!('speechSynthesis' in window)) {
    return notify('Trình duyệt của bạn chưa hỗ trợ Web Speech API.')
  }

  if (playingId.value === article.id) {
    if (window.speechSynthesis.paused) {
      window.speechSynthesis.resume()
      isPaused.value = false
    } else if (window.speechSynthesis.speaking) {
      window.speechSynthesis.pause()
      isPaused.value = true
    } else {
      stopSpeech()
    }
    return
  }

  stopSpeech()
  activePlayingArticle.value = article
  playingId.value = article.id
  isPaused.value = false
  playbackProgress.value = 5

  const cleanBody = article.cleanBody || article.summary || ''
  const textToRead = `${article.title}. Tóm tắt. ${article.summary || ''}. Nội dung chi tiết. ${cleanBody.slice(0, 1500)}`
  const utterance = new SpeechSynthesisUtterance(textToRead)
  utterance.lang = 'vi-VN'
  utterance.rate = speechRate.value

  const estSeconds = Math.max(article.minutes * 50, 45)
  const step = 100 / estSeconds
  progressInterval = setInterval(() => {
    if (!isPaused.value && playbackProgress.value < 96) {
      playbackProgress.value = Math.min(playbackProgress.value + step, 96)
    }
  }, 1000)

  utterance.onend = () => {
    playbackProgress.value = 100
    setTimeout(() => {
      stopSpeech()
    }, 800)
  }

  utterance.onerror = () => {
    stopSpeech()
  }

  window.speechSynthesis.speak(utterance)
  notify(`Đang đọc: ${article.title.slice(0, 40)}...`)
}

function stopSpeech() {
  if ('speechSynthesis' in window) {
    window.speechSynthesis.cancel()
  }
  playingId.value = null
  isPaused.value = false
  clearInterval(progressInterval)
  playbackProgress.value = 0
}

function changeSpeed(rate) {
  speechRate.value = rate
  if (playingId.value && activePlayingArticle.value) {
    toggleSpeech(activePlayingArticle.value)
    toggleSpeech(activePlayingArticle.value)
  }
  notify(`Tốc độ đọc: ${rate}x`)
}

function playNextArticle() {
  const list = activeView.value === 'home' && homeData.value?.hero?.sub ? homeData.value.hero.sub : filterArticles.value
  if (!activePlayingArticle.value || list.length === 0) return
  const currentIndex = list.findIndex((a) => a.id === activePlayingArticle.value.id)
  if (currentIndex !== -1 && currentIndex + 1 < list.length) {
    toggleSpeech(list[currentIndex + 1])
  } else {
    notify('Đã đến bài viết cuối cùng')
  }
}

function playPrevArticle() {
  const list = activeView.value === 'home' && homeData.value?.hero?.sub ? homeData.value.hero.sub : filterArticles.value
  if (!activePlayingArticle.value || list.length === 0) return
  const currentIndex = list.findIndex((a) => a.id === activePlayingArticle.value.id)
  if (currentIndex > 0) {
    toggleSpeech(list[currentIndex - 1])
  } else {
    notify('Đây là bài viết đầu tiên')
  }
}

// Logout Modal
const showLogoutModal = ref(false)
const isLoggingOut = ref(false)

function handleLogout() {
  showLogoutModal.value = true
}

async function confirmLogout() {
  if (isLoggingOut.value) return
  isLoggingOut.value = true
  try {
    await authService.logout()
  } catch (e) {
    // Bỏ qua lỗi network
  }
  auth.clearAuth()
  toast.success('Đã đăng xuất tài khoản thành công!', { autoClose: 2000 })
  setTimeout(() => {
    showLogoutModal.value = false
    window.location.href = '/'
  }, 800)
}

onMounted(() => {
  window.addEventListener('click', closeUserDropdown)
  loadHomeData()
})

onBeforeUnmount(() => {
  window.removeEventListener('click', closeUserDropdown)
  stopSpeech()
  clearTimeout(noticeTimer)
  clearTimeout(searchDebounceTimer.value)
})
</script>

<template>
  <div class="relative min-h-screen bg-[#fcfcfd] text-slate-800 font-sans selection:bg-sky-500 selection:text-white overflow-x-hidden">
    <!-- 3D Three.js Background (Nhẹ nhàng trên nền sáng) -->
    <ThreeBackground :is-audio-playing="playingId !== null && !isPaused" />

    <!-- 1. TOP NAVBAR (Nền Trắng - Chữ Đen - Hover Xanh Da Trời Nhạt) -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-200 transition-all shadow-2xs">
      
      <!-- Top Bar: Logo, Search, User -->
      <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        
        <!-- Logo ReadVn -->
        <a href="/" @click.prevent="setView('home')" class="group flex shrink-0 items-center gap-3 cursor-pointer">
          <img
            src="/logo/Khuon-Ngang-removebg.png"
            alt="ReadVn"
            class="h-9 sm:h-10 w-auto object-contain transition-transform duration-200 group-hover:scale-102"
          />
        </a>

        <!-- Search Bar -->
        <div class="relative hidden sm:block max-w-md flex-1">
          <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5 text-slate-400">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8" />
              <path d="m21 21-4.3-4.3" />
            </svg>
          </div>
          <input
            v-model="searchQuery"
            type="search"
            class="w-full rounded-full border border-slate-300 bg-slate-50 py-1.5 ps-10 pe-11 text-sm text-slate-900 placeholder:text-slate-400 transition-all focus:border-sky-500 focus:bg-white focus:outline-hidden focus:ring-2 focus:ring-sky-500/20"
            placeholder="Tìm kiếm tin tức, thời sự, công nghệ..."
          />
          <kbd class="pointer-events-none absolute inset-y-0 end-0 my-auto me-2.5 flex h-5 items-center rounded border border-slate-300 bg-white px-1.5 text-[10px] font-semibold text-slate-400">
            ⌘K
          </kbd>
        </div>

        <!-- User Actions / Profile -->
        <div class="flex items-center gap-2.5 sm:gap-3">
          <template v-if="currentUser">
            <a
              v-if="currentUser.role === 'admin' || currentUser.role === 'editor'"
              href="/admin"
              class="hidden sm:inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs hover:border-sky-300 hover:bg-sky-50 hover:text-sky-600 transition-all"
            >
              <svg class="size-3.5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect width="7" height="9" x="3" y="3" rx="1" />
                <rect width="7" height="5" x="14" y="3" rx="1" />
                <rect width="7" height="9" x="14" y="12" rx="1" />
                <rect width="7" height="5" x="3" y="16" rx="1" />
              </svg>
              Quản trị CMS
            </a>

            <!-- Avatar & Dropdown Menu -->
            <div ref="userDropdownRef" class="relative">
              <button
                @click.stop="toggleUserDropdown"
                class="flex items-center gap-2 rounded-full border border-slate-200 bg-white p-1 pe-2.5 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-sky-50 hover:text-sky-600 focus:outline-none cursor-pointer"
              >
                <div class="relative size-7 shrink-0">
                  <img
                    v-if="currentUser.avatar_url || currentUser.avatar"
                    :src="currentUser.avatar_url || currentUser.avatar"
                    alt="Avatar"
                    class="size-7 rounded-full object-cover border border-slate-200"
                  />
                  <div
                    v-else
                    class="flex size-7 items-center justify-center rounded-full bg-gradient-to-br from-sky-500 to-blue-600 text-xs font-bold text-white shadow-2xs"
                  >
                    {{ currentUser.name?.slice(0, 1)?.toUpperCase() || 'U' }}
                  </div>
                </div>
                <span class="max-w-[100px] truncate text-slate-800 hidden md:inline">{{ readerName }}</span>
                <svg
                  class="size-3.5 text-slate-400 transition-transform"
                  :class="showUserDropdown ? 'rotate-180' : ''"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path d="m6 9 6 6 6-6" />
                </svg>
              </button>

              <Transition
                enter-active-class="transition duration-150 ease-out"
                enter-from-class="transform scale-95 opacity-0"
                enter-to-class="transform scale-100 opacity-100"
                leave-active-class="transition duration-100 ease-in"
                leave-from-class="transform scale-100 opacity-100"
                leave-to-class="transform scale-95 opacity-0"
              >
                <div
                  v-if="showUserDropdown"
                  class="absolute end-0 mt-2 w-64 rounded-2xl border border-slate-200 bg-white py-2 shadow-xl ring-1 ring-black/5 z-50"
                >
                  <div class="flex items-center gap-3 px-4 py-3 border-b border-slate-100">
                    <div class="size-11 shrink-0">
                      <img
                        v-if="currentUser.avatar_url || currentUser.avatar"
                        :src="currentUser.avatar_url || currentUser.avatar"
                        alt="Avatar"
                        class="size-11 rounded-full object-cover border border-slate-200"
                      />
                      <div
                        v-else
                        class="flex size-11 items-center justify-center rounded-full bg-gradient-to-br from-sky-500 to-blue-600 text-sm font-bold text-white shadow-xs"
                      >
                        {{ currentUser.name?.slice(0, 1)?.toUpperCase() || 'U' }}
                      </div>
                    </div>
                    <div class="min-w-0 flex-1">
                      <p class="truncate text-xs font-bold text-slate-900">{{ currentUser.name }}</p>
                      <p class="truncate text-[11px] text-slate-400 font-mono">{{ currentUser.email }}</p>
                    </div>
                  </div>

                  <div class="py-1">
                    <button
                      @click="navigateTo('/profile')"
                      class="flex w-full items-center gap-3 px-4 py-2 text-start text-xs font-medium text-slate-700 hover:bg-sky-50 hover:text-sky-600 transition cursor-pointer"
                    >
                      <svg class="size-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                        <circle cx="12" cy="7" r="4" />
                      </svg>
                      <span>Thông tin chung</span>
                    </button>

                    <button
                      @click="setView('saved'); showUserDropdown = false"
                      class="flex w-full items-center gap-3 px-4 py-2 text-start text-xs font-medium text-slate-700 hover:bg-sky-50 hover:text-sky-600 transition cursor-pointer"
                    >
                      <svg class="size-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z" />
                      </svg>
                      <span>Tin đã lưu ({{ savedIds.length }})</span>
                    </button>

                    <a
                      v-if="currentUser.role === 'admin' || currentUser.role === 'editor'"
                      href="/admin"
                      class="flex w-full items-center gap-3 px-4 py-2 text-start text-xs font-medium text-sky-700 hover:bg-sky-50 transition"
                    >
                      <svg class="size-4 text-sky-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect width="7" height="9" x="3" y="3" rx="1" />
                        <rect width="7" height="5" x="14" y="3" rx="1" />
                        <rect width="7" height="9" x="14" y="12" rx="1" />
                        <rect width="7" height="5" x="3" y="16" rx="1" />
                      </svg>
                      <span>Trang Quản trị CMS</span>
                    </a>
                  </div>

                  <div class="border-t border-slate-100 pt-1">
                    <button
                      @click="handleLogout"
                      class="flex w-full items-center gap-3 px-4 py-2 text-start text-xs font-semibold text-rose-600 hover:bg-rose-50 transition cursor-pointer"
                    >
                      <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                        <polyline points="16 17 21 12 16 7" />
                        <line x1="21" x2="9" y1="12" y2="12" />
                      </svg>
                      <span>Thoát</span>
                    </button>
                  </div>
                </div>
              </Transition>
            </div>
          </template>

          <template v-else>
            <a
              href="/login"
              class="inline-flex items-center gap-1.5 rounded-full border border-sky-400 bg-sky-500 px-4 py-1.5 text-xs font-bold text-white shadow-2xs hover:bg-sky-600 transition-all cursor-pointer"
            >
              Đăng nhập
            </a>
          </template>
        </div>
      </div>

      <!-- Navigation Bar Danh Mục Ngang -->
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 border-t border-slate-200">
        <nav class="flex items-center space-x-1 sm:space-x-2 overflow-x-auto py-1.5 scrollbar-none">
          <button
            v-for="cat in categories"
            :key="cat.id"
            @click="setCategory(cat)"
            class="shrink-0 px-3 py-1 rounded text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5 uppercase tracking-wide"
            :class="
              activeCategory === cat.id && activeView !== 'saved'
                ? 'text-sky-600 font-extrabold border-b-2 border-sky-500'
                : 'text-slate-700 hover:text-sky-600 hover:bg-sky-50/80'
            "
          >
            {{ cat.name }}
          </button>
        </nav>
      </div>

    </header>

    <!-- 2. MAIN CONTENT -->
    <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6 space-y-10">

      <!-- ======================================================== -->
      <!-- CHẾ ĐỘ 1: TRANG CHỦ THEO PHONG CÁCH BÁO TUỔI TRẺ (Hình mẫu 2) -->
      <!-- ======================================================== -->
      <template v-if="activeView === 'home' && !searchQuery.trim()">

        <!-- LOADING SKELETON -->
        <div v-if="isLoading" class="space-y-6 animate-pulse">
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 bg-white p-6 border border-slate-200 rounded-lg">
            <div class="lg:col-span-3 space-y-4">
              <div class="h-4 bg-slate-200 rounded w-3/4"></div>
              <div class="h-20 bg-slate-100 rounded"></div>
              <div class="h-32 bg-slate-100 rounded"></div>
            </div>
            <div class="lg:col-span-6 space-y-4">
              <div class="h-64 bg-slate-200 rounded"></div>
              <div class="h-28 bg-slate-100 rounded"></div>
            </div>
            <div class="lg:col-span-3 space-y-4">
              <div class="h-44 bg-slate-200 rounded"></div>
              <div class="h-44 bg-slate-200 rounded"></div>
            </div>
          </div>
        </div>

        <template v-else-if="homeData">
          
          <!-- KHỐI TIÊU ĐIỂM CHÍNH (CHUẨN BÁO TUỔI TRẺ: 3 CỘT KHÔNG CARD PHẲNG) -->
          <section class="bg-white border border-slate-200 rounded-lg p-5 sm:p-6 shadow-2xs">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

              <!-- ============================================== -->
              <!-- CỘT 1 (Trái - ~23%): Ngày tháng + Góc nhìn + List tin thời sự -->
              <!-- ============================================== -->
              <div class="lg:col-span-3 lg:border-e lg:border-slate-200 lg:pe-6 space-y-5">
                
                <!-- Ngày & Thời tiết -->
                <div class="border-b border-slate-100 pb-3 text-xs text-slate-500 font-medium space-y-0.5">
                  <p class="font-bold text-slate-700">{{ currentDateStr }}</p>
                  <p class="flex items-center gap-1.5 text-slate-600">
                    <span>TP. Hồ Chí Minh</span>
                    <span class="text-sky-600 font-bold">25° - 35°C</span>
                    <span>⛅</span>
                  </p>
                </div>

                <!-- Box Góc nhìn / Bình luận (Tác giả + Avatar tròn) -->
                <div class="bg-slate-50/80 rounded-lg p-3 border border-slate-200/80 space-y-2">
                  <div class="flex items-center gap-2.5">
                    <img
                      src="/images/home/work.png"
                      alt="Avatar tác giả"
                      class="size-8 rounded-full object-cover border border-slate-300"
                    />
                    <div class="min-w-0">
                      <span class="text-[10px] uppercase font-bold text-sky-600 tracking-wider block">Góc nhìn</span>
                      <p class="text-xs font-bold text-slate-800 truncate">Nguyễn Phước Thắng</p>
                    </div>
                  </div>
                  <p
                    @click="homeData.hero?.trending?.[0] && (selectedArticle = homeData.hero.trending[0])"
                    class="text-xs text-slate-700 line-clamp-3 leading-relaxed hover:text-sky-600 cursor-pointer font-medium"
                  >
                    "Lắng nghe tin tức bằng giọng nói AI: Xu hướng tiếp cận thông tin thời đại số cho độc giả bận rộn..."
                  </p>
                </div>

                <!-- Danh sách 5 tin vắn thời sự (Chỉ tiêu đề + số comment / thời gian) -->
                <div class="space-y-3.5 pt-1">
                  <div
                    v-for="(item, idx) in homeData.hero?.trending?.slice(0, 5)"
                    :key="item.id"
                    class="group border-b border-slate-100 pb-2.5 last:border-b-0 last:pb-0 space-y-1"
                  >
                    <h4
                      @click="selectedArticle = item"
                      class="text-xs sm:text-[13px] font-semibold text-slate-800 leading-snug line-clamp-3 group-hover:text-sky-600 transition-colors cursor-pointer"
                    >
                      {{ item.title }}
                      <span v-if="item.commentsCount" class="text-[11px] text-sky-600 font-mono inline-flex items-center gap-0.5 ms-1">
                        💬{{ item.commentsCount }}
                      </span>
                    </h4>
                  </div>
                </div>

              </div>

              <!-- ============================================== -->
              <!-- CỘT 2 (Giữa - ~54%): Tiêu điểm chính (ảnh to bên trái, chữ bên phải) + 3 tin ngang phía dưới -->
              <!-- ============================================== -->
              <div class="lg:col-span-6 lg:border-e lg:border-slate-200 lg:pe-6 space-y-6">
                
                <!-- Hàng trên: Tin tiêu điểm số 1 (Bố cục ngang ảnh trái, chữ phải chuẩn Tuổi Trẻ) -->
                <div v-if="homeData.hero?.main" class="group space-y-3">
                  <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                    
                    <!-- Ảnh to bên trái -->
                    <div class="sm:col-span-7 relative overflow-hidden rounded aspect-16/11 bg-slate-100">
                      <img
                        :src="homeData.hero.main.image"
                        :alt="homeData.hero.main.title"
                        class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-102 cursor-pointer"
                        @click="selectedArticle = homeData.hero.main"
                        @error="$event.target.src = '/images/home/city.png'"
                      />
                      <button
                        @click.stop="toggleSpeech(homeData.hero.main)"
                        class="absolute bottom-2.5 right-2.5 size-8 rounded-full bg-sky-500 hover:bg-sky-600 text-white flex items-center justify-center shadow-md transition-transform group-hover:scale-110 cursor-pointer"
                        title="Nghe tin AI"
                      >
                        <svg v-if="playingId === homeData.hero.main.id && !isPaused" class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <rect x="6" y="4" width="4" height="16" />
                          <rect x="14" y="4" width="4" height="16" />
                        </svg>
                        <svg v-else class="size-3.5 ps-0.5" viewBox="0 0 24 24" fill="currentColor">
                          <polygon points="5 3 19 12 5 21 5 3" />
                        </svg>
                      </button>
                    </div>

                    <!-- Tiêu đề và tóm tắt bên phải -->
                    <div class="sm:col-span-5 flex flex-col justify-between space-y-2">
                      <div class="space-y-2">
                        <h2
                          @click="selectedArticle = homeData.hero.main"
                          class="text-lg sm:text-xl font-bold text-slate-900 leading-snug hover:text-sky-600 transition-colors cursor-pointer"
                        >
                          {{ homeData.hero.main.title }}
                          <span class="text-xs text-sky-600 font-mono ms-1">💬{{ homeData.hero.main.commentsCount }}</span>
                        </h2>

                        <p class="text-xs text-slate-600 line-clamp-4 leading-relaxed">
                          {{ homeData.hero.main.summary }}
                        </p>
                      </div>

                      <div class="flex items-center gap-2 text-[11px] text-slate-400 font-mono pt-2">
                        <span class="text-sky-600 font-bold">{{ homeData.hero.main.sourceName }}</span>
                        <span>•</span>
                        <span>{{ homeData.hero.main.time }}</span>
                      </div>
                    </div>

                  </div>
                </div>

                <!-- Hàng dưới: 3 tin tiêu điểm phụ xếp ngang (3 cột đều nhau chuẩn Tuổi Trẻ) -->
                <div class="border-t border-slate-200 pt-5">
                  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <article
                      v-for="sub in homeData.hero?.sub?.slice(0, 3)"
                      :key="sub.id"
                      class="group flex flex-col justify-between space-y-2"
                    >
                      <div class="relative overflow-hidden rounded aspect-16/11 bg-slate-100">
                        <img
                          :src="sub.image"
                          :alt="sub.title"
                          class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-103 cursor-pointer"
                          @click="selectedArticle = sub"
                          @error="$event.target.src = '/images/home/city.png'"
                        />
                        <button
                          @click.stop="toggleSpeech(sub)"
                          class="absolute bottom-1.5 right-1.5 size-6 rounded-full bg-black/60 hover:bg-sky-500 text-white flex items-center justify-center transition cursor-pointer"
                          title="Nghe"
                        >
                          <svg class="size-2.5 ps-0.5" viewBox="0 0 24 24" fill="currentColor">
                            <polygon points="5 3 19 12 5 21 5 3" />
                          </svg>
                        </button>
                      </div>

                      <h3
                        @click="selectedArticle = sub"
                        class="text-xs sm:text-[13px] font-bold text-slate-900 leading-snug line-clamp-3 group-hover:text-sky-600 transition-colors cursor-pointer"
                      >
                        {{ sub.title }}
                        <span v-if="sub.commentsCount" class="text-[10px] text-sky-600 font-mono ms-0.5">💬{{ sub.commentsCount }}</span>
                      </h3>

                      <span class="text-[10px] text-slate-400 font-mono block">
                        {{ sub.time }}
                      </span>
                    </article>
                  </div>
                </div>

              </div>

              <!-- ============================================== -->
              <!-- CỘT 3 (Phải - ~23%): Banner quảng cáo / Chuyên đề sự kiện Tuổi Trẻ -->
              <!-- ============================================== -->
              <div class="lg:col-span-3 space-y-4">
                
                <!-- Banner 1: Đọc báo thông minh Voice AI -->
                <div class="rounded border border-sky-200 bg-gradient-to-br from-sky-50 to-blue-50/70 p-4 space-y-3 text-center">
                  <div class="size-10 rounded-full bg-sky-500 text-white mx-auto flex items-center justify-center shadow-sm">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z" />
                      <path d="M19 10v2a7 7 0 0 1-14 0v-2" />
                      <line x1="12" y1="19" x2="12" y2="22" />
                    </svg>
                  </div>
                  <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                    Trợ Lý Đọc Báo Bằng Giọng Nói AI
                  </h4>
                  <p class="text-[11px] text-slate-600 leading-relaxed">
                    Điều khiển rảnh tay: ra lệnh "Đọc tiếp", "Tạm dừng", "Bài tiếp theo" trực tiếp trên trình duyệt.
                  </p>
                  <button
                    @click="homeData.hero?.main && toggleSpeech(homeData.hero.main)"
                    class="w-full rounded bg-sky-500 hover:bg-sky-600 text-white text-xs font-bold py-1.5 transition cursor-pointer shadow-2xs"
                  >
                    Nghe thử tin tiêu điểm 🎧
                  </button>
                </div>

                <!-- Banner 2: Chuyên mục Diễn đàn số -->
                <div class="rounded border border-slate-200 bg-slate-50 p-4 space-y-2.5">
                  <span class="text-[10px] font-bold uppercase tracking-wider text-sky-600">Diễn đàn độc giả</span>
                  <h5 class="text-xs font-bold text-slate-800 leading-snug">
                    Bạn đọc hiến kế: Ứng dụng công nghệ nâng cao trải nghiệm báo chí số
                  </h5>
                  <p class="text-[11px] text-slate-500">
                    Gửi ý kiến đóng góp và bài viết của bạn về tòa soạn ReadsNews ngay hôm nay.
                  </p>
                  <button
                    @click="navigateTo('/profile')"
                    class="text-[11px] font-bold text-sky-600 hover:underline cursor-pointer"
                  >
                    Xem chi tiết →
                  </button>
                </div>

              </div>

            </div>
          </section>

          <!-- CÁC KHỐI CHUYÊN MỤC DƯỚI (Thời sự, Kinh tế, Công nghệ, Thể thao...) -->
          <section
            v-for="section in homeData.category_sections"
            :key="section.category.id"
            class="bg-white border border-slate-200 rounded-lg p-5 sm:p-6 space-y-5 shadow-2xs"
          >
            <!-- Header Chuyên Mục -->
            <div class="flex items-center justify-between border-b-2 border-sky-500 pb-2.5">
              <div class="flex items-center gap-2">
                <span class="h-4 w-1.5 bg-sky-500 rounded-full"></span>
                <h3 class="text-base sm:text-lg font-bold uppercase tracking-wide text-slate-900">
                  {{ section.category.name }}
                </h3>
              </div>

              <button
                @click="setCategory(section.category)"
                class="text-xs font-bold text-sky-600 hover:text-sky-500 transition cursor-pointer flex items-center gap-1"
              >
                <span>Xem tất cả</span>
                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <path d="m9 18 6-6-6-6" />
                </svg>
              </button>
            </div>

            <!-- 1 Tin To Bên Trái + 4 Tin Ngang Bên Phải -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
              
              <!-- Tin to bên trái -->
              <div v-if="section.main" class="lg:col-span-6 flex flex-col justify-between group space-y-3">
                <div class="relative overflow-hidden rounded aspect-16/10 bg-slate-100">
                  <img
                    :src="section.main.image"
                    :alt="section.main.title"
                    class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-102 cursor-pointer"
                    @click="selectedArticle = section.main"
                    @error="$event.target.src = '/images/home/city.png'"
                  />
                  <button
                    @click.stop="toggleSpeech(section.main)"
                    class="absolute bottom-2.5 right-2.5 size-8 rounded-full bg-sky-500 hover:bg-sky-600 text-white flex items-center justify-center shadow-md transition-transform group-hover:scale-110 cursor-pointer"
                  >
                    <svg class="size-3.5 ps-0.5" viewBox="0 0 24 24" fill="currentColor">
                      <polygon points="5 3 19 12 5 21 5 3" />
                    </svg>
                  </button>
                </div>

                <div class="space-y-1.5">
                  <h4
                    @click="selectedArticle = section.main"
                    class="text-base sm:text-lg font-bold text-slate-900 leading-snug group-hover:text-sky-600 transition-colors cursor-pointer"
                  >
                    {{ section.main.title }}
                  </h4>
                  <p class="text-xs text-slate-600 line-clamp-3 leading-relaxed">
                    {{ section.main.summary }}
                  </p>
                </div>

                <div class="flex items-center justify-between text-xs text-slate-400 font-mono pt-2 border-t border-slate-100">
                  <span>{{ section.main.time }} • {{ section.main.minutes }} phút đọc</span>
                  <button
                    @click="selectedArticle = section.main"
                    class="font-bold text-sky-600 hover:underline cursor-pointer"
                  >
                    Chi tiết →
                  </button>
                </div>
              </div>

              <!-- 4 Tin phụ bên phải -->
              <div class="lg:col-span-6 flex flex-col justify-between gap-3">
                <article
                  v-for="subItem in section.items"
                  :key="subItem.id"
                  class="group flex items-center gap-3 border-b border-slate-100 pb-2.5 last:border-b-0 last:pb-0"
                >
                  <img
                    :src="subItem.image"
                    :alt="subItem.title"
                    class="size-20 rounded object-cover shrink-0 border border-slate-200 cursor-pointer"
                    @click="selectedArticle = subItem"
                    @error="$event.target.src = '/images/home/city.png'"
                  />

                  <div class="min-w-0 flex-1 space-y-1">
                    <h5
                      @click="selectedArticle = subItem"
                      class="text-xs sm:text-sm font-bold text-slate-900 line-clamp-2 leading-snug group-hover:text-sky-600 transition cursor-pointer"
                    >
                      {{ subItem.title }}
                    </h5>

                    <div class="flex items-center gap-3 text-[11px] text-slate-400 font-mono">
                      <span>{{ subItem.time }}</span>
                      <button
                        @click="toggleSpeech(subItem)"
                        class="text-sky-600 hover:underline font-semibold cursor-pointer"
                      >
                        Nghe tin
                      </button>
                    </div>
                  </div>
                </article>
              </div>

            </div>
          </section>

          <!-- KHỐI TIN ĐỌC NHIỀU NHẤT (RANKING 01, 02, 03... TUỔI TRẺ STYLE) -->
          <section v-if="homeData.popular && homeData.popular.length > 0" class="bg-white border border-slate-200 rounded-lg p-5 sm:p-6 space-y-5 shadow-2xs">
            <div class="flex items-center justify-between border-b-2 border-sky-500 pb-2.5">
              <div class="flex items-center gap-2">
                <span class="h-4 w-1.5 bg-sky-500 rounded-full"></span>
                <h3 class="text-base sm:text-lg font-bold uppercase tracking-wide text-slate-900">
                  Đọc Nhiều Nhất
                </h3>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
              <article
                v-for="(pop, index) in homeData.popular"
                :key="pop.id"
                class="group flex items-start gap-3.5 p-3 rounded border border-slate-100 bg-slate-50/50 hover:bg-sky-50/30 hover:border-sky-200 transition"
              >
                <span class="text-2xl sm:text-3xl font-extrabold text-sky-400/40 group-hover:text-sky-500 transition-colors font-mono shrink-0">
                  0{{ index + 1 }}
                </span>

                <div class="min-w-0 flex-1 space-y-1">
                  <span class="text-[10px] text-sky-600 font-bold block">{{ pop.categoryName }}</span>
                  <h4
                    @click="selectedArticle = pop"
                    class="text-xs sm:text-sm font-bold text-slate-900 line-clamp-2 leading-snug group-hover:text-sky-600 transition cursor-pointer"
                  >
                    {{ pop.title }}
                  </h4>
                </div>
              </article>
            </div>
          </section>

        </template>
      </template>

      <!-- ======================================================== -->
      <!-- CHẾ ĐỘ 2: DANH SÁCH BÀI BÁO (KHI LỌC CHUYÊN MỤC HOẶC TÌM KIẾM) -->
      <!-- ======================================================== -->
      <template v-else>
        <section class="space-y-6">
          
          <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-4">
            <div class="flex items-center gap-3">
              <div class="size-2 rounded-full bg-sky-500 animate-ping"></div>
              <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                {{ currentViewTitle }}
              </h2>
              <span class="rounded bg-sky-50 border border-sky-200 px-2.5 py-0.5 text-xs font-mono text-sky-700">
                {{ totalFilterArticles }} bài báo
              </span>
            </div>

            <button
              @click="setView('home')"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded border border-slate-200 bg-white hover:bg-sky-50 hover:text-sky-600 hover:border-sky-300 text-xs font-bold text-slate-700 transition cursor-pointer"
            >
              ← Về trang chủ
            </button>
          </div>

          <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="n in 6" :key="n" class="animate-pulse rounded border border-slate-200 bg-white p-4 space-y-4">
              <div class="h-44 w-full bg-slate-100 rounded"></div>
              <div class="h-4 w-1/3 bg-slate-100 rounded"></div>
              <div class="h-6 w-full bg-slate-100 rounded"></div>
            </div>
          </div>

          <div v-else-if="filterArticles.length === 0" class="text-center py-16 space-y-3 bg-white rounded border border-slate-200">
            <div class="inline-flex size-14 items-center justify-center rounded-full bg-sky-50 border border-sky-200 text-sky-500 mb-2">
              <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8" />
                <path d="m21 21-4.3-4.3" />
              </svg>
            </div>
            <p class="text-lg font-semibold text-slate-800">Không tìm thấy bài viết nào</p>
            <p class="text-sm text-slate-400">Hãy thử tìm từ khóa khác hoặc chuyển sang chuyên mục khác.</p>
          </div>

          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <article
              v-for="item in filterArticles"
              :key="item.id"
              class="group flex flex-col justify-between overflow-hidden rounded border border-slate-200 bg-white transition-all duration-200 hover:border-sky-300 hover:shadow-sm"
            >
              <div class="relative overflow-hidden aspect-16/10 bg-slate-100">
                <img
                  :src="item.image"
                  :alt="item.title"
                  class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-102"
                  @error="$event.target.src = '/images/home/city.png'"
                />
                <span class="absolute top-2.5 left-2.5 rounded bg-white/90 border border-slate-200 px-2 py-0.5 text-[11px] font-semibold text-sky-700 backdrop-blur">
                  {{ item.categoryName }}
                </span>

                <button
                  @click.stop="toggleSpeech(item)"
                  class="absolute bottom-2.5 right-2.5 size-9 rounded-full bg-sky-500 hover:bg-sky-600 text-white flex items-center justify-center shadow-md transition-transform group-hover:scale-110 cursor-pointer"
                >
                  <svg class="size-4 ps-0.5" viewBox="0 0 24 24" fill="currentColor">
                    <polygon points="5 3 19 12 5 21 5 3" />
                  </svg>
                </button>
              </div>

              <div class="flex-1 p-4 flex flex-col justify-between space-y-2.5">
                <div class="space-y-1.5">
                  <div class="flex items-center gap-2 text-[11px] text-slate-400 font-mono">
                    <span class="text-sky-600">{{ item.sourceName }}</span>
                    <span>•</span>
                    <span>{{ item.time }}</span>
                  </div>

                  <h4
                    @click="selectedArticle = item"
                    class="font-bold text-slate-900 text-sm leading-snug line-clamp-2 group-hover:text-sky-600 transition-colors cursor-pointer"
                  >
                    {{ item.title }}
                  </h4>

                  <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">
                    {{ item.summary }}
                  </p>
                </div>

                <div class="flex items-center justify-between pt-2.5 border-t border-slate-100 text-xs">
                  <span class="text-slate-400 font-mono text-[11px]">{{ item.views }} lượt xem</span>

                  <div class="flex items-center gap-2">
                    <button
                      @click="toggleSaved(item)"
                      class="p-1 rounded text-slate-400 hover:text-sky-600 transition cursor-pointer"
                    >
                      <svg
                        class="size-4"
                        :fill="savedIds.includes(item.id) ? 'currentColor' : 'none'"
                        :class="savedIds.includes(item.id) ? 'text-sky-500' : ''"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                      >
                        <path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z" />
                      </svg>
                    </button>

                    <button
                      @click="selectedArticle = item"
                      class="font-semibold text-sky-600 hover:underline cursor-pointer"
                    >
                      Chi tiết →
                    </button>
                  </div>
                </div>
              </div>
            </article>
          </div>

          <div v-if="hasMoreFilterPages" class="text-center pt-6">
            <button
              @click="loadMore"
              :disabled="isLoadingMore"
              class="inline-flex items-center gap-2 rounded border border-slate-200 bg-white hover:bg-sky-50 hover:border-sky-300 text-slate-700 hover:text-sky-600 px-6 py-2 text-xs font-bold transition cursor-pointer disabled:opacity-50"
            >
              <svg v-if="isLoadingMore" class="animate-spin size-4 text-sky-500" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
              <span>{{ isLoadingMore ? 'Đang tải thêm tin...' : 'Tải thêm bài viết' }}</span>
            </button>
          </div>

        </section>
      </template>

    </main>

    <!-- 3. FOOTER BÁO CHÍ (Nền Trắng - Chữ Đen - Hover Xanh Da Trời Nhạt) -->
    <footer class="mt-16 bg-white border-t border-slate-200 text-slate-700">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10 space-y-8">
        
        <!-- Hàng 1: 5 Cột Danh Mục -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-6 text-xs sm:text-sm font-medium">
          <div class="space-y-2.5">
            <a href="#" @click.prevent="setView('home')" class="block text-slate-800 hover:text-sky-500 transition-colors">Chính trị</a>
            <a href="#" @click.prevent="setView('home')" class="block text-slate-800 hover:text-sky-500 transition-colors">Thời sự</a>
            <a href="#" @click.prevent="setView('home')" class="block text-slate-800 hover:text-sky-500 transition-colors">Thế giới</a>
            <a href="#" @click.prevent="setView('home')" class="block text-slate-800 hover:text-sky-500 transition-colors">Kinh tế</a>
          </div>

          <div class="space-y-2.5">
            <a href="#" @click.prevent="setView('home')" class="block text-slate-800 hover:text-sky-500 transition-colors">Đời sống</a>
            <a href="#" @click.prevent="setView('home')" class="block text-slate-800 hover:text-sky-500 transition-colors">Sức khoẻ</a>
            <a href="#" @click.prevent="setView('home')" class="block text-slate-800 hover:text-sky-500 transition-colors">Giới trẻ</a>
            <a href="#" @click.prevent="setView('home')" class="block text-slate-800 hover:text-sky-500 transition-colors">Giáo dục</a>
          </div>

          <div class="space-y-2.5">
            <a href="#" @click.prevent="setView('home')" class="block text-slate-800 hover:text-sky-500 transition-colors">Du lịch</a>
            <a href="#" @click.prevent="setView('home')" class="block text-slate-800 hover:text-sky-500 transition-colors">Văn hoá</a>
            <a href="#" @click.prevent="setView('home')" class="block text-slate-800 hover:text-sky-500 transition-colors">Giải trí</a>
            <a href="#" @click.prevent="setView('home')" class="block text-slate-800 hover:text-sky-500 transition-colors">Thể thao</a>
          </div>

          <div class="space-y-2.5">
            <a href="#" @click.prevent="setView('home')" class="block text-slate-800 hover:text-sky-500 transition-colors">Công nghệ</a>
            <a href="#" @click.prevent="setView('home')" class="block text-slate-800 hover:text-sky-500 transition-colors">Xe</a>
            <a href="#" @click.prevent="setView('home')" class="block text-slate-800 hover:text-sky-500 transition-colors">Thời trang trẻ</a>
            <a href="#" @click.prevent="setView('home')" class="block text-slate-800 hover:text-sky-500 transition-colors">Video & Audio</a>
          </div>

          <div class="space-y-2.5">
            <a href="#" @click.prevent="setView('home')" class="block text-slate-800 hover:text-sky-500 transition-colors">Bạn đọc</a>
            <a href="#" @click.prevent="setView('home')" class="block text-slate-800 hover:text-sky-500 transition-colors">Rao vặt</a>
            <a href="#" @click.prevent="setView('home')" class="block text-slate-800 hover:text-sky-500 transition-colors">Podcast AI</a>
          </div>
        </div>

        <!-- Hàng 2: Logo + Menu Ngang + Mạng Xã Hội -->
        <div class="pt-6 border-t border-slate-200 flex flex-col md:flex-row items-center justify-between gap-6">
          <div class="flex items-center gap-3">
            <img
              src="/logo/Khuon-Ngang-removebg.png"
              alt="ReadVn"
              class="h-8.5 w-auto object-contain"
            />
          </div>

          <div class="flex flex-wrap items-center justify-center gap-4 sm:gap-6 text-xs sm:text-sm font-semibold text-slate-800">
            <a href="#" @click.prevent class="hover:text-sky-500 transition-colors">Đặt báo</a>
            <a href="#" @click.prevent class="hover:text-sky-500 transition-colors">Quảng cáo</a>
            <a href="#" @click.prevent class="hover:text-sky-500 transition-colors">RSS</a>
            <a href="#" @click.prevent class="hover:text-sky-500 transition-colors">Tòa soạn</a>
            <a href="#" @click.prevent class="hover:text-sky-500 transition-colors">Chính sách bảo mật</a>
          </div>

          <div class="flex items-center gap-2.5 text-xs text-slate-600 font-medium">
            <span>Theo dõi báo trên:</span>
            <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" class="size-7 rounded-full border border-slate-300 flex items-center justify-center hover:border-sky-500 hover:bg-sky-50 hover:text-sky-600 transition-colors" title="Facebook">
              <span class="font-bold text-[11px]">f</span>
            </a>
            <a href="https://zalo.me" target="_blank" rel="noopener noreferrer" class="size-7 rounded-full border border-slate-300 flex items-center justify-center hover:border-sky-500 hover:bg-sky-50 hover:text-sky-600 transition-colors" title="Zalo">
              <span class="font-bold text-[9px]">Zalo</span>
            </a>
            <a href="https://youtube.com" target="_blank" rel="noopener noreferrer" class="size-7 rounded-full border border-slate-300 flex items-center justify-center hover:border-sky-500 hover:bg-sky-50 hover:text-sky-600 transition-colors" title="YouTube">
              <span class="font-bold text-[9px]">▶</span>
            </a>
          </div>
        </div>

        <!-- Hàng 3: Tòa Soạn, Giấy Phép, Bản Quyền -->
        <div class="pt-6 border-t border-slate-200 grid grid-cols-1 md:grid-cols-3 gap-6 text-[11px] sm:text-xs text-slate-500 leading-relaxed">
          <div class="space-y-1.5">
            <p><strong class="text-slate-700">Hotline:</strong> <span class="font-bold text-sky-600 ms-1">0906 645 777</span></p>
            <p><strong class="text-slate-700">Liên hệ quảng cáo:</strong> <span class="font-bold text-sky-600 ms-1">0908 780 404</span></p>
          </div>

          <div class="space-y-1">
            <p><strong class="text-slate-700">Tổng biên tập:</strong> Nguyễn Ngọc Toàn</p>
            <p>Phó tổng biên tập thường trực: Hải Thành</p>
            <p>Phó tổng biên tập: Lâm Hiếu Dũng</p>
            <p>Tổng thư ký tòa soạn: Đức Trung</p>
          </div>

          <div class="space-y-2.5">
            <p>
              Giấy phép xuất bản số 110/GP - BTTTT cấp ngày 24.3.2020.
              © 2003-2026 Bản quyền thuộc về ReadsNews. Cấm sao chép dưới mọi hình thức nếu không có sự chấp thuận bằng văn bản.
            </p>
            <div class="flex items-center gap-2">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-slate-900 text-white font-mono text-[10px] font-bold">
                <span class="text-rose-500">★</span> NCSC CƠ BẢN
              </span>
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded border border-slate-300 text-[10px] text-slate-600 font-medium">
                Tín Nhiệm Mạng
              </span>
            </div>
          </div>
        </div>

      </div>
    </footer>

    <!-- Nút Cuộn Lên Đầu Trang -->
    <button
      @click="scrollToTop"
      class="fixed bottom-24 right-5 z-40 size-10 rounded border border-slate-300 bg-white/95 text-slate-700 hover:text-sky-600 hover:border-sky-400 hover:bg-sky-50 shadow-sm flex flex-col items-center justify-center transition-all cursor-pointer group"
      title="Lên đầu trang"
    >
      <svg class="size-3.5 group-hover:-translate-y-0.5 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <path d="m18 15-6-6-6 6" />
      </svg>
      <span class="text-[8px] font-extrabold uppercase">TOP</span>
    </button>

    <!-- 4. ARTICLE DETAIL MODAL (Nền Trắng - Chữ Đen) -->
    <div
      v-if="selectedArticle"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
      @click.self="selectedArticle = null"
    >
      <div class="relative w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-xl border border-slate-200 bg-white p-6 sm:p-8 text-slate-800 shadow-2xl space-y-6">
        
        <button
          @click="selectedArticle = null"
          class="absolute top-5 right-5 p-2 rounded-full bg-slate-100 text-slate-500 hover:text-slate-900 hover:bg-slate-200 transition cursor-pointer"
        >
          <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18" />
            <line x1="6" y1="6" x2="18" y2="18" />
          </svg>
        </button>

        <div class="relative rounded overflow-hidden aspect-16/9 bg-slate-100">
          <img
            :src="selectedArticle.image"
            :alt="selectedArticle.title"
            class="w-full h-full object-cover"
            @error="$event.target.src = '/images/home/city.png'"
          />
          <span class="absolute bottom-3 left-3 rounded bg-sky-500 px-2.5 py-1 text-xs font-bold text-white shadow">
            {{ selectedArticle.categoryName }}
          </span>
        </div>

        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 font-mono">
          <span class="font-bold text-sky-600">{{ selectedArticle.sourceName }}</span>
          <span>•</span>
          <span>Tác giả: <strong class="text-slate-700">{{ selectedArticle.author || 'Tổng hợp' }}</strong></span>
          <span>•</span>
          <span>{{ selectedArticle.time }}</span>
          <span>•</span>
          <span>{{ selectedArticle.minutes }} phút đọc</span>
        </div>

        <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 leading-tight">
          {{ selectedArticle.title }}
        </h2>

        <div v-if="selectedArticle.aiSummary" class="rounded border border-sky-200 bg-sky-50/70 p-4 space-y-2">
          <div class="flex items-center gap-2 text-xs font-bold text-sky-800">
            <span class="size-2 rounded-full bg-sky-500 animate-ping"></span>
            Tóm tắt thông minh (AI Summary)
          </div>
          <p class="text-xs sm:text-sm text-slate-700 whitespace-pre-line leading-relaxed">
            {{ selectedArticle.aiSummary }}
          </p>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 p-3.5 rounded border border-slate-200 bg-slate-50">
          <button
            @click="toggleSpeech(selectedArticle)"
            class="inline-flex items-center gap-2 rounded bg-sky-500 hover:bg-sky-600 text-white px-4 py-2 text-xs font-bold shadow-sm transition cursor-pointer"
          >
            <svg v-if="playingId === selectedArticle.id && !isPaused" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="6" y="4" width="4" height="16" />
              <rect x="14" y="4" width="4" height="16" />
            </svg>
            <svg v-else class="size-4" viewBox="0 0 24 24" fill="currentColor">
              <polygon points="5 3 19 12 5 21 5 3" />
            </svg>
            <span>{{ playingId === selectedArticle.id && !isPaused ? 'Đang đọc thành tiếng...' : 'Nghe bài viết với AI' }}</span>
          </button>

          <button
            @click="toggleSaved(selectedArticle)"
            class="inline-flex items-center gap-1.5 rounded border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 hover:text-sky-600 transition cursor-pointer"
          >
            <svg
              class="size-4"
              :fill="savedIds.includes(selectedArticle.id) ? 'currentColor' : 'none'"
              :class="savedIds.includes(selectedArticle.id) ? 'text-sky-500' : ''"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="2"
            >
              <path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z" />
            </svg>
            <span>{{ savedIds.includes(selectedArticle.id) ? 'Đã lưu' : 'Lưu bài' }}</span>
          </button>
        </div>

        <div class="prose prose-slate max-w-none text-slate-700 text-sm sm:text-base leading-relaxed space-y-4">
          <div v-if="selectedArticle.content" v-html="selectedArticle.content"></div>
          <div v-else class="whitespace-pre-line">{{ selectedArticle.body }}</div>
        </div>

        <div class="pt-4 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500">
          <a
            v-if="selectedArticle.original_url"
            :href="selectedArticle.original_url"
            target="_blank"
            rel="noopener noreferrer"
            class="text-sky-600 hover:underline flex items-center gap-1"
          >
            Xem bài gốc trên {{ selectedArticle.sourceName }} ↗
          </a>
          <button
            @click="selectedArticle = null"
            class="px-4 py-1.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium cursor-pointer"
          >
            Đóng
          </button>
        </div>

      </div>
    </div>

    <!-- 5. PERSISTENT AUDIO PLAYER (Cố định ở đáy) -->
    <div
      v-if="activePlayingArticle"
      class="fixed bottom-0 inset-x-0 z-40 bg-white/95 border-t border-slate-200 backdrop-blur-md shadow-2xl p-2.5 sm:p-3.5 text-slate-800"
    >
      <div class="mx-auto max-w-7xl flex flex-col sm:flex-row items-center justify-between gap-3 sm:gap-4">
        
        <div class="flex items-center gap-3 w-full sm:w-auto min-w-0">
          <img
            :src="activePlayingArticle.image"
            alt="Thumb"
            class="size-10 sm:size-11 rounded object-cover border border-slate-200 shrink-0"
          />
          <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
              <span class="size-2 rounded-full bg-sky-500" :class="!isPaused ? 'animate-ping' : ''"></span>
              <p class="truncate text-xs sm:text-sm font-bold text-slate-900">{{ activePlayingArticle.title }}</p>
            </div>
            <p class="text-[11px] text-sky-600 font-mono">{{ activePlayingArticle.sourceName }} • {{ activePlayingArticle.voice }}</p>
          </div>
        </div>

        <div class="flex items-center gap-2.5 shrink-0">
          <button
            @click="playPrevArticle"
            class="p-1.5 text-slate-500 hover:text-sky-600 hover:bg-sky-50 rounded transition cursor-pointer"
            title="Bài trước"
          >
            <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polygon points="19 20 9 12 19 4 19 20" />
              <line x1="5" y1="19" x2="5" y2="5" />
            </svg>
          </button>

          <button
            @click="toggleSpeech(activePlayingArticle)"
            class="size-9 sm:size-10 rounded-full bg-sky-500 hover:bg-sky-600 text-white flex items-center justify-center shadow-md transition cursor-pointer"
          >
            <svg v-if="!isPaused" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <rect x="6" y="4" width="4" height="16" />
              <rect x="14" y="4" width="4" height="16" />
            </svg>
            <svg v-else class="size-4 ps-0.5" viewBox="0 0 24 24" fill="currentColor">
              <polygon points="5 3 19 12 5 21 5 3" />
            </svg>
          </button>

          <button
            @click="playNextArticle"
            class="p-1.5 text-slate-500 hover:text-sky-600 hover:bg-sky-50 rounded transition cursor-pointer"
            title="Bài tiếp theo"
          >
            <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polygon points="5 4 15 12 5 20 5 4" />
              <line x1="19" y1="5" x2="19" y2="19" />
            </svg>
          </button>

          <div class="flex items-center gap-0.5 bg-slate-100 border border-slate-200 p-0.5 rounded text-[11px] font-mono">
            <button
              v-for="rate in [0.75, 1, 1.25, 1.5]"
              :key="rate"
              @click="changeSpeed(rate)"
              class="px-1 py-0.5 rounded transition cursor-pointer"
              :class="speechRate === rate ? 'bg-sky-500 text-white font-bold' : 'text-slate-600 hover:text-sky-600'"
            >
              {{ rate }}x
            </button>
          </div>

          <button
            @click="stopSpeech"
            class="p-1.5 text-slate-400 hover:text-rose-500 hover:bg-rose-50 rounded transition cursor-pointer"
            title="Đóng trình phát"
          >
            <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18" />
              <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
          </button>
        </div>

      </div>

      <div class="w-full bg-slate-200 h-1 mt-2 rounded-full overflow-hidden">
        <div
          class="bg-gradient-to-r from-sky-400 to-sky-600 h-full transition-all duration-300"
          :style="{ width: `${playbackProgress}%` }"
        ></div>
      </div>
    </div>

    <!-- 6. TOAST NOTIFICATION -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="transform translate-y-4 opacity-0"
      enter-to-class="transform translate-y-0 opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="transform translate-y-0 opacity-100"
      leave-to-class="transform translate-y-4 opacity-0"
    >
      <div
        v-if="notice"
        class="fixed bottom-20 left-1/2 -translate-x-1/2 z-50 rounded-full border border-sky-200 bg-white px-5 py-2 text-xs font-semibold text-slate-800 shadow-xl flex items-center gap-2"
      >
        <span class="size-2 rounded-full bg-sky-500 animate-pulse"></span>
        <span>{{ notice }}</span>
      </div>
    </Transition>

    <!-- 7. LOGOUT CONFIRMATION MODAL -->
    <div
      v-if="showLogoutModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs"
      @click.self="!isLoggingOut && (showLogoutModal = false)"
    >
      <div class="w-full max-w-sm rounded-xl border border-slate-200 bg-white p-6 text-center shadow-2xl space-y-4">
        <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-rose-50 text-rose-500 border border-rose-100">
          <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
            <polyline points="16 17 21 12 16 7" />
            <line x1="21" x2="9" y1="12" y2="12" />
          </svg>
        </div>
        <div class="space-y-1">
          <h3 class="text-lg font-bold text-slate-900">Đăng xuất tài khoản?</h3>
          <p class="text-xs text-slate-500">Bạn có chắc muốn đăng xuất khỏi ReadsNews không?</p>
        </div>
        <div class="flex items-center gap-3 pt-2">
          <button
            @click="showLogoutModal = false"
            :disabled="isLoggingOut"
            class="flex-1 rounded border border-slate-200 bg-slate-100 py-2 text-xs font-bold text-slate-700 hover:bg-slate-200 transition cursor-pointer disabled:opacity-50"
          >
            Ở lại
          </button>
          <button
            @click="confirmLogout"
            :disabled="isLoggingOut"
            class="flex-1 inline-flex items-center justify-center gap-2 rounded bg-rose-600 hover:bg-rose-500 py-2 text-xs font-bold text-white shadow-sm transition cursor-pointer disabled:opacity-50"
          >
            <svg v-if="isLoggingOut" class="animate-spin size-3.5 text-white" viewBox="0 0 24 24" fill="none">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span>{{ isLoggingOut ? 'Đang thoát...' : 'Đăng xuất' }}</span>
          </button>
        </div>
      </div>
    </div>

  </div>
</template>
