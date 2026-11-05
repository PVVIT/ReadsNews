<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { auth } from '@/utils/auth'
import { authService } from '@/services/authService'
import { homeService } from '@/services/homeService'
import { bookmarkService } from '@/services/bookmarkService'
import { ttsService } from '@/services/ttsService'
import { toast } from 'vue3-toastify'
import ThreeBackground from '@/components/home/ThreeBackground.vue'

const props = defineProps({
  slug: {
    type: String,
    required: true,
  },
})

// --- State Bài viết ---
const article = ref(null)
const relatedArticles = ref([])
const popularArticles = ref([])
const categories = ref([])
const isLoading = ref(true)
const errorMessage = ref('')

// Trạng thái cỡ chữ (font size adjustment)
const fontSizeLevel = ref('base') // 'sm' | 'base' | 'lg' | 'xl'
// Chuẩn hóa nội dung các đoạn văn bản (đảm bảo mỗi đoạn bọc trong <p> và có khoảng cách rộng rãi)
const formattedContentHtml = computed(() => {
  if (!article.value) return ''
  const raw = article.value.content || article.value.body || article.value.summary || ''

  // Nếu bài viết đã có sẵn các thẻ HTML <p>
  if (raw.includes('<p') || raw.includes('</p>')) {
    return raw
  }

  // Nếu là văn bản thuần: tách thành các đoạn theo xuống dòng
  const paragraphs = raw
    .split(/\r?\n+/)
    .map((p) => p.trim())
    .filter(Boolean)

  if (paragraphs.length > 0) {
    return paragraphs.map((p) => `<p>${p}</p>`).join('')
  }

  return `<p>${raw}</p>`
})

const fontSizeClasses = computed(() => {
  if (fontSizeLevel.value === 'sm') return 'text-[15px] leading-relaxed'
  if (fontSizeLevel.value === 'lg') return 'text-[18px] leading-loose'
  if (fontSizeLevel.value === 'xl') return 'text-[20px] leading-loose'
  return 'text-[16.5px] leading-relaxed'
})

// Trạng thái âm thanh Edge TTS
const isSpeaking = ref(false)
const isPaused = ref(false)
const speechRate = ref(1)
const playbackProgress = ref(0)
let progressInterval = null
const ttsAudio = ref(null)    // Audio instance hiện tại
const ttsLoading = ref(false) // Đang tải audio từ TTS server

// Lưu bài viết
const savedIds = ref([])
const isSaved = computed(() => {
  return article.value ? savedIds.value.includes(article.value.id) : false
})

// Thông báo toast nhỏ
const notice = ref('')
let noticeTimer = null

// Bình luận
const comments = ref([
  {
    id: 1,
    author: 'Trần Văn Minh',
    time: '25 phút trước',
    content: 'Bài viết rất kịp thời và phân tích sâu sắc. Công nghệ giọng nói AI hỗ trợ đọc tin quả thực mang lại tiện ích lớn cho độc giả.',
    likes: 6,
  },
  {
    id: 2,
    author: 'Nguyễn Thị Thu Hà',
    time: '1 giờ trước',
    content: 'Giao diện thoáng và đọc rất dễ chịu, thích tính năng nghe đọc báo bằng giọng nói tiếng Việt mượt mà này.',
    likes: 4,
  },
])
const newCommentAuthor = ref('')
const newCommentContent = ref('')
const isSubmittingComment = ref(false)

// User info & Header Dropdown
const currentUser = computed(() => auth.getUser())
const showUserDropdown = ref(false)
const userDropdownRef = ref(null)
const showLogoutModal = ref(false)
const isLoggingOut = ref(false)
const searchQuery = ref('')

function toggleUserDropdown() {
  showUserDropdown.value = !showUserDropdown.value
}

function closeUserDropdown(e) {
  if (userDropdownRef.value && !userDropdownRef.value.contains(e.target)) {
    showUserDropdown.value = false
  }
}

const readerName = computed(() => {
  if (currentUser.value?.name) {
    return currentUser.value.name.split(' ').at(-1)
  }
  return 'bạn đọc'
})

// Định dạng ngày giờ chuẩn báo chí (vd: Thứ năm, 05/11/2026 - 08:30 GMT+7)
const formattedPublishDate = computed(() => {
  if (!article.value) return ''
  const dateStr = article.value.published_at || article.value.created_at
  if (!dateStr) return 'Mới cập nhật'
  const d = new Date(dateStr)
  const days = ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy']
  const dayName = days[d.getDay()]
  const date = String(d.getDate()).padStart(2, '0')
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const year = d.getFullYear()
  const hours = String(d.getHours()).padStart(2, '0')
  const minutes = String(d.getMinutes()).padStart(2, '0')
  return `${dayName}, ${date}/${month}/${year} - ${hours}:${minutes} GMT+7`
})

// Điều hướng trang
function navigateTo(path) {
  showUserDropdown.value = false
  window.history.pushState(null, '', path)
  window.dispatchEvent(new PopStateEvent('popstate'))
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function navigateToArticle(item) {
  if (!item) return
  stopSpeech()
  const slug = item.slug || 'bai-viet'
  const url = `/${slug}-${item.id}.htm`
  window.history.pushState(null, '', url)
  window.dispatchEvent(new PopStateEvent('popstate'))
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function handleSearch() {
  if (!searchQuery.value.trim()) return
  window.history.pushState(null, '', `/?q=${encodeURIComponent(searchQuery.value.trim())}`)
  window.dispatchEvent(new PopStateEvent('popstate'))
}

// Tải thông tin chi tiết bài báo từ API
async function loadArticleDetail() {
  isLoading.value = true
  errorMessage.value = ''
  stopSpeech()

  try {
    const res = await homeService.getArticle(props.slug)
    if (res.success && res.data) {
      const data = res.data

      const wordCount = data.word_count || (data.content ? data.content.replace(/<[^>]*>/g, '').split(/\s+/).length : 400)
      const minutes = Math.max(1, Math.ceil(wordCount / 180))

      article.value = {
        ...data,
        image: data.thumbnail || '/images/home/city.png',
        categoryName: data.category?.name || 'Thời sự',
        categorySlug: data.category?.slug || 'thoi-su',
        sourceName: data.source?.name || 'Tuổi Trẻ Online',
        minutes,
        body: data.content || data.summary || '',
        cleanBody: (data.content || data.summary || '').replace(/<[^>]*>/g, ''),
        aiSummary: data.ai_summary || data.summary || '',
      }

      // Cập nhật tiêu đề trang (Document Title)
      document.title = `${article.value.title} - ReadsNews`

      relatedArticles.value = (res.related || []).map((item) => ({
        ...item,
        image: item.thumbnail || '/images/home/city.png',
        categoryName: item.category?.name || 'Tin tức',
        time: item.published_at ? new Date(item.published_at).toLocaleDateString('vi-VN') : 'Vừa xong',
      }))

      popularArticles.value = (res.popular || []).map((item) => ({
        ...item,
        categoryName: item.category?.name || 'Tin tức',
      }))
    } else {
      errorMessage.value = res.message || 'Không tìm thấy bài viết yêu cầu.'
    }
  } catch (error) {
    console.error('Lỗi tải bài viết:', error)
    errorMessage.value = 'Không thể tải nội dung bài viết. Vui lòng kiểm tra kết nối mạng.'
  } finally {
    isLoading.value = false
  }
}

// Tải danh mục cho Navbar
async function loadCategories() {
  try {
    const res = await homeService.getCategories()
    if (res.success && res.data) {
      categories.value = [
        { id: 'all', name: 'Trang chủ', slug: 'all' },
        ...res.data.map((c) => ({
          id: c.id,
          name: c.name,
          slug: c.slug,
        })),
      ]
    }
  } catch (e) {
    // Không làm gián đoạn
  }
}

// Lưu trữ bài viết vào database
async function loadBookmarkIds() {
  if (!currentUser.value) {
    savedIds.value = []
    return
  }
  try {
    const res = await bookmarkService.getBookmarkIds()
    if (res && res.success && Array.isArray(res.data)) {
      savedIds.value = res.data
    }
  } catch (err) {
    console.error('Lỗi tải danh sách ID đã lưu:', err)
  }
}

async function toggleSavedArticle() {
  if (!article.value) return
  if (!currentUser.value) {
    toast.info('Vui lòng đăng nhập để lưu bài viết vào tài khoản!', { autoClose: 3000 })
    return
  }
  try {
    const res = await bookmarkService.toggleBookmark(article.value.id)
    if (res && res.success) {
      if (res.is_bookmarked) {
        if (!savedIds.value.includes(article.value.id)) {
          savedIds.value.push(article.value.id)
        }
      } else {
        savedIds.value = savedIds.value.filter((i) => i !== article.value.id)
      }
    }
  } catch (err) {
    console.error('Lỗi lưu bài viết:', err)
  }
}

function copyArticleLink() {
  navigator.clipboard.writeText(window.location.href)
  notify('Đã sao chép liên kết bài viết vào bộ nhớ đệm 📋')
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

// --- Trình phát âm thanh Edge TTS ---
async function toggleSpeech() {
  if (!article.value) return

  // Đang phát → pause/resume
  if (isSpeaking.value) {
    if (ttsAudio.value) {
      if (isPaused.value) {
        ttsAudio.value.play()
        isPaused.value = false
      } else {
        ttsAudio.value.pause()
        isPaused.value = true
      }
    }
    return
  }

  stopSpeech()
  isSpeaking.value = true
  isPaused.value = false
  playbackProgress.value = 5
  ttsLoading.value = true
  notify(`Đang tải giọng đọc AI...`)

  try {
    const cleanBody = article.value.cleanBody || article.value.summary || ''
    const text = `${article.value.title}. Tóm tắt. ${article.value.summary || ''}. Nội dung chi tiết. ${cleanBody.slice(0, 1600)}`
    const url = await ttsService.synthesize(text, { rate: speechRate.value })

    // Kiểm tra user đã bấm dừng trong lúc load chưa
    if (!isSpeaking.value) {
      URL.revokeObjectURL(url)
      return
    }

    const audio = new Audio(url)
    ttsAudio.value = audio

    audio.addEventListener('timeupdate', () => {
      if (audio.duration) {
        playbackProgress.value = (audio.currentTime / audio.duration) * 100
      }
    })

    audio.addEventListener('ended', () => {
      playbackProgress.value = 100
      setTimeout(() => stopSpeech(), 800)
      URL.revokeObjectURL(url)
    })

    audio.addEventListener('error', () => {
      stopSpeech()
      URL.revokeObjectURL(url)
      notify('Không thể phát giọng đọc. Vui lòng thử lại.')
    })

    await audio.play()
    notify(`Đang phát giọng đọc AI: ${article.value.title.slice(0, 35)}...`)
  } catch (err) {
    console.error('TTS error:', err)
    notify('Lỗi tải giọng đọc. Hãy chắc TTS server đang chạy (port 3001).')
    stopSpeech()
  } finally {
    ttsLoading.value = false
  }
}

function stopSpeech() {
  if (ttsAudio.value) {
    ttsAudio.value.pause()
    ttsAudio.value.src = ''
    ttsAudio.value = null
  }
  isSpeaking.value = false
  isPaused.value = false
  clearInterval(progressInterval)
  playbackProgress.value = 0
}

async function changeSpeed(rate) {
  speechRate.value = rate
  if (isSpeaking.value) {
    stopSpeech()
    await toggleSpeech()
  }
  notify(`Tốc độ đọc: ${rate}x`)
}

// Gửi bình luận
function submitComment() {
  if (!newCommentContent.value.trim()) return
  isSubmittingComment.value = true

  setTimeout(() => {
    comments.value.unshift({
      id: Date.now(),
      author: newCommentAuthor.value.trim() || currentUser.value?.name || 'Bạn đọc',
      time: 'Vừa xong',
      content: newCommentContent.value.trim(),
      likes: 0,
    })
    newCommentContent.value = ''
    newCommentAuthor.value = ''
    isSubmittingComment.value = false
    notify('Cảm ơn bạn! Bình luận của bạn đã được đăng thành công.')
  }, 400)
}

function handleLogout() {
  showLogoutModal.value = true
}

async function confirmLogout() {
  if (isLoggingOut.value) return
  isLoggingOut.value = true
  try {
    await authService.logout()
  } catch (e) {
    // Bỏ qua lỗi mạng
  }
  auth.clearAuth()
  toast.success('Đã đăng xuất tài khoản thành công!', { autoClose: 2000 })
  setTimeout(() => {
    showLogoutModal.value = false
    window.location.href = '/'
  }, 800)
}

watch(
  () => props.slug,
  () => {
    loadArticleDetail()
  },
)

watch(currentUser, (newUser) => {
  if (newUser) {
    loadBookmarkIds()
  } else {
    savedIds.value = []
  }
})

onMounted(() => {
  window.addEventListener('click', closeUserDropdown)
  loadCategories()
  loadArticleDetail()
  if (currentUser.value) {
    loadBookmarkIds()
  }
})

onBeforeUnmount(() => {
  window.removeEventListener('click', closeUserDropdown)
  stopSpeech()
  clearTimeout(noticeTimer)
})
</script>

<template>
  <div class="relative min-h-screen bg-[#fcfcfd] text-slate-800 font-sans selection:bg-sky-500 selection:text-white overflow-x-hidden">
    <!-- 3D Three.js Background -->
    <ThreeBackground :is-audio-playing="isSpeaking && !isPaused" />

    <!-- 1. TOP NAVBAR (Nền Trắng - Chữ Đen - Hover Xanh Da Trời Nhạt) -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-200 transition-all shadow-2xs">
      <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        
        <!-- Logo ReadVn -->
        <a href="/" @click.prevent="navigateTo('/')" class="group flex shrink-0 items-center gap-3 cursor-pointer">
          <img
            src="/logo/Khuon-Ngang-removebg.png"
            alt="ReadVn"
            class="h-9 sm:h-10 w-auto object-contain transition-transform duration-200 group-hover:scale-102"
          />
        </a>

        <!-- Search Bar -->
        <form @submit.prevent="handleSearch" class="relative hidden sm:block max-w-md flex-1">
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
            ↵
          </kbd>
        </form>

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

            <!-- Avatar Dropdown -->
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
                    <div class="size-10 shrink-0">
                      <img
                        v-if="currentUser.avatar_url || currentUser.avatar"
                        :src="currentUser.avatar_url || currentUser.avatar"
                        alt="Avatar"
                        class="size-10 rounded-full object-cover border border-slate-200"
                      />
                      <div
                        v-else
                        class="flex size-10 items-center justify-center rounded-full bg-gradient-to-br from-sky-500 to-blue-600 text-xs font-bold text-white"
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
                      @click="navigateTo('/?view=saved')"
                      class="flex w-full items-center gap-3 px-4 py-2 text-start text-xs font-medium text-slate-700 hover:bg-sky-50 hover:text-sky-600 transition cursor-pointer"
                    >
                      <svg class="size-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z" />
                      </svg>
                      <span>Tin đã lưu ({{ savedIds.length }})</span>
                    </button>
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

      <!-- Categories Bar -->
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 border-t border-slate-200">
        <nav class="flex items-center space-x-1 sm:space-x-2 overflow-x-auto py-1.5 scrollbar-none">
          <button
            v-for="cat in categories"
            :key="cat.id"
            @click="navigateTo(cat.id === 'all' ? '/' : `/?category=${cat.id}`)"
            class="shrink-0 px-3 py-1 rounded text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5 uppercase tracking-wide text-slate-700 hover:text-sky-600 hover:bg-sky-50/80"
          >
            {{ cat.name }}
          </button>
        </nav>
      </div>
    </header>

    <!-- 2. ARTICLE DETAIL MAIN SECTION (Chuẩn Báo Tuổi Trẻ) -->
    <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">

      <!-- Loading Skeleton -->
      <div v-if="isLoading" class="space-y-6 animate-pulse">
        <div class="h-4 bg-slate-200 rounded w-1/4"></div>
        <div class="h-10 bg-slate-200 rounded w-4/5"></div>
        <div class="h-6 bg-slate-100 rounded w-1/3"></div>
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
          <div class="lg:col-span-8 space-y-4">
            <div class="h-96 bg-slate-200 rounded-lg"></div>
            <div class="h-24 bg-slate-100 rounded"></div>
            <div class="h-48 bg-slate-100 rounded"></div>
          </div>
          <div class="lg:col-span-4 space-y-4">
            <div class="h-64 bg-slate-100 rounded-lg"></div>
          </div>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="errorMessage" class="text-center py-16 space-y-4 bg-white rounded-xl border border-slate-200 p-8">
        <div class="inline-flex size-14 items-center justify-center rounded-full bg-rose-50 text-rose-500 mb-2">
          <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="8" x2="12" y2="12" />
            <line x1="12" y1="16" x2="12.01" y2="16" />
          </svg>
        </div>
        <h2 class="text-xl font-bold text-slate-900">{{ errorMessage }}</h2>
        <p class="text-sm text-slate-500">Bài viết bạn tìm kiếm có thể đã bị gỡ hoặc đường dẫn chưa chính xác.</p>
        <button
          @click="navigateTo('/')"
          class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-sky-500 hover:bg-sky-600 text-white text-xs font-bold transition cursor-pointer shadow-sm"
        >
          ← Quay lại trang chủ
        </button>
      </div>

      <!-- Article Content Detail (Tuổi Trẻ Style) -->
      <div v-else-if="article" class="space-y-6">

        <!-- Breadcrumb Báo Chí: Trang chủ > Tên chuyên mục > Ngày giờ -->
        <nav class="flex flex-wrap items-center gap-2 text-xs text-slate-500 font-medium">
          <a href="/" @click.prevent="navigateTo('/')" class="text-slate-700 hover:text-sky-600 transition">Trang chủ</a>
          <span class="text-slate-300">/</span>
          <a
            :href="`/?category=${article.category_id}`"
            @click.prevent="navigateTo(`/?category=${article.category_id}`)"
            class="text-sky-600 hover:underline uppercase font-bold"
          >
            {{ article.categoryName }}
          </a>
          <span class="text-slate-300">/</span>
          <span class="text-slate-400 font-mono">{{ formattedPublishDate }}</span>
        </nav>

        <!-- Tiêu đề bài viết H1 chuẩn Báo Tuổi Trẻ -->
        <h1 class="text-2xl sm:text-3xl lg:text-[34px] font-extrabold text-[#222222] font-['Roboto',sans-serif] leading-tight tracking-tight">
          {{ article.title }}
        </h1>

        <!-- Metadata bài viết: Nguồn, tác giả, lượt xem -->
        <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-slate-200 text-xs text-slate-500 font-medium">
          <div class="flex items-center gap-2.5">
            <span class="font-bold text-slate-800">{{ article.author || article.sourceName }}</span>
            <span class="text-slate-300">•</span>
            <span class="text-slate-500 font-mono">{{ article.time }}</span>
            <span class="text-slate-300">•</span>
            <span class="text-slate-500 font-mono">{{ article.views }} lượt xem</span>
          </div>

          <!-- Nhóm nút cỡ chữ (A- / A+) chuẩn báo chí -->
          <div class="flex items-center gap-1 bg-slate-100 rounded-md p-0.5 border border-slate-200 text-xs font-bold text-slate-600">
            <span class="px-2 text-[11px] text-slate-400">Cỡ chữ:</span>
            <button
              @click="fontSizeLevel = 'sm'"
              :class="fontSizeLevel === 'sm' ? 'bg-white text-sky-600 shadow-2xs' : 'hover:text-slate-900'"
              class="px-2 py-0.5 rounded cursor-pointer"
              title="Cỡ chữ nhỏ"
            >
              A-
            </button>
            <button
              @click="fontSizeLevel = 'base'"
              :class="fontSizeLevel === 'base' ? 'bg-white text-sky-600 shadow-2xs' : 'hover:text-slate-900'"
              class="px-2 py-0.5 rounded cursor-pointer"
              title="Cỡ chữ chuẩn"
            >
              A
            </button>
            <button
              @click="fontSizeLevel = 'lg'"
              :class="fontSizeLevel === 'lg' ? 'bg-white text-sky-600 shadow-2xs' : 'hover:text-slate-900'"
              class="px-2 py-0.5 rounded cursor-pointer"
              title="Cỡ chữ lớn"
            >
              A+
            </button>
          </div>
        </div>

        <!-- BỐ CỤC 2 CỘT: CỘT TRÁI NỘI DUNG (8 CỘT) + CỘT PHẢI SIDEBAR (4 CỘT) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

          <!-- ============================================== -->
          <!-- CỘT TRÁI: NỘI DUNG BÀI VIẾT (8/12)             -->
          <!-- ============================================== -->
          <article class="lg:col-span-8 bg-white border border-slate-200 rounded-lg p-5 sm:p-7 space-y-6 shadow-2xs font-['Roboto',sans-serif] text-[#222222]">

            <!-- Thanh Trợ Lý Nghe Tin Bằng Giọng Nói AI (Đặc sắc của ReadsNews) -->
            <div class="rounded-lg border border-sky-200 bg-gradient-to-r from-sky-50/90 to-blue-50/60 p-3.5 sm:p-4 flex flex-col sm:flex-row items-center justify-between gap-4">
              <div class="flex items-center gap-3 w-full sm:w-auto">
                <button
                  @click="toggleSpeech"
                  class="size-11 rounded-full bg-sky-500 hover:bg-sky-600 text-white flex items-center justify-center shadow-md shadow-sky-500/30 transition-transform hover:scale-105 shrink-0 cursor-pointer"
                  :title="isSpeaking && !isPaused ? 'Tạm dừng' : 'Nghe bài viết'"
                  :disabled="ttsLoading"
                >
                  <!-- Loading spinner khi đang tải TTS -->
                  <svg v-if="ttsLoading" class="size-5 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                  </svg>
                  <svg v-else-if="isSpeaking && !isPaused" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <rect x="6" y="4" width="4" height="16" />
                    <rect x="14" y="4" width="4" height="16" />
                  </svg>
                  <svg v-else class="size-5 ps-0.5" viewBox="0 0 24 24" fill="currentColor">
                    <polygon points="5 3 19 12 5 21 5 3" />
                  </svg>
                </button>

                <div class="min-w-0">
                  <div class="flex items-center gap-2">
                    <span class="size-2 rounded-full bg-sky-500" :class="isSpeaking && !isPaused ? 'animate-ping' : ''"></span>
                    <h3 class="text-xs sm:text-sm font-bold text-slate-900 truncate">
                      {{ isSpeaking && !isPaused ? 'Đang đọc bài viết với AI...' : 'Nghe đọc bài báo (Trợ lý ảo AI tiếng Việt)' }}
                    </h3>
                  </div>
                  <p class="text-[11px] text-sky-700 font-mono">
                    Thời lượng ước tính: {{ article.minutes }} phút • Giọng đọc tự nhiên
                  </p>
                </div>
              </div>

              <!-- Tiện ích nghe: Tốc độ + Lưu + Sao chép -->
              <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                <!-- Tốc độ đọc -->
                <div class="flex items-center gap-0.5 bg-white border border-slate-200 p-0.5 rounded text-[11px] font-mono">
                  <button
                    v-for="rate in [0.75, 1, 1.25, 1.5]"
                    :key="rate"
                    @click="changeSpeed(rate)"
                    class="px-1.5 py-0.5 rounded transition cursor-pointer"
                    :class="speechRate === rate ? 'bg-sky-500 text-white font-bold' : 'text-slate-600 hover:text-sky-600'"
                  >
                    {{ rate }}x
                  </button>
                </div>

                <!-- Nút Lưu bài -->
                <button
                  @click="toggleSavedArticle"
                  class="p-2 rounded border border-slate-200 bg-white hover:bg-sky-50 hover:text-sky-600 hover:border-sky-300 transition cursor-pointer"
                  :title="isSaved ? 'Bỏ lưu bài' : 'Lưu bài viết'"
                >
                  <svg
                    class="size-4"
                    :fill="isSaved ? 'currentColor' : 'none'"
                    :class="isSaved ? 'text-sky-500' : 'text-slate-400'"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z" />
                  </svg>
                </button>

                <!-- Nút Copy link -->
                <button
                  @click="copyArticleLink"
                  class="p-2 rounded border border-slate-200 bg-white hover:bg-sky-50 hover:text-sky-600 hover:border-sky-300 text-slate-500 transition cursor-pointer"
                  title="Sao chép liên kết"
                >
                  <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect width="14" height="14" x="8" y="8" rx="2" ry="2" />
                    <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
                  </svg>
                </button>
              </div>
            </div>

            <!-- SAPO (Đoạn tóm tắt mở đầu in đậm chuẩn Tuổi Trẻ) -->
            <div v-if="article.summary" class="text-base sm:text-[17px] font-bold text-[#222222] font-['Roboto',sans-serif] leading-relaxed border-l-4 border-sky-500 ps-4 py-1">
              {{ article.summary }}
            </div>

            <!-- Hộp Tóm Tắt Thông Minh AI (AI Summary) -->
            <div v-if="article.aiSummary" class="rounded-lg border border-sky-200 bg-sky-50/60 p-4 space-y-2">
              <div class="flex items-center gap-2 text-xs font-bold text-sky-800 uppercase tracking-wide">
                <span class="size-2 rounded-full bg-sky-500 animate-ping"></span>
                Tóm tắt thông minh bằng AI
              </div>
              <p class="text-xs sm:text-[13.5px] text-[#222222] font-['Roboto',sans-serif] whitespace-pre-line leading-relaxed">
                {{ article.aiSummary }}
              </p>
            </div>

            <!-- Ảnh Bìa Bài Viết Lớn Có Caption Báo Chí -->
            <div v-if="article.image" class="space-y-2">
              <div class="relative overflow-hidden rounded aspect-16/10 bg-slate-100">
                <img
                  :src="article.image"
                  :alt="article.title"
                  class="w-full h-full object-cover"
                  @error="$event.target.src = '/images/home/city.png'"
                />
              </div>
              <p class="text-[12px] text-slate-500 text-center italic">
                Hình ảnh bài viết: {{ article.title }} (Nguồn: {{ article.sourceName }})
              </p>
            </div>

            <!-- THÂN BÀI VIẾT (FULL CONTENT) -->
            <div
              class="article-body-content max-w-none text-[#222222] font-normal font-['Roboto',sans-serif]"
              :class="fontSizeClasses"
            >
              <div v-html="formattedContentHtml"></div>
            </div>

            <!-- Ký tên tác giả cuối bài (Căn phải, in đậm chuẩn báo chí) -->
            <div class="pt-4 border-t border-slate-100 text-end space-y-0.5">
              <p class="font-bold text-[#222222] text-sm tracking-wide uppercase font-['Roboto',sans-serif]">
                {{ article.author || 'THEO ' + article.sourceName.toUpperCase() }}
              </p>
              <p v-if="article.original_url" class="text-xs text-slate-400">
                <a
                  :href="article.original_url"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="text-sky-600 hover:underline"
                >
                  Xem bài gốc trên {{ article.sourceName }} ↗
                </a>
              </p>
            </div>

            <!-- Tags / Thẻ từ khóa -->
            <div class="flex flex-wrap items-center gap-2 pt-2">
              <span class="text-xs text-slate-400 font-bold me-1">Từ khóa:</span>
              <span class="rounded bg-slate-100 border border-slate-200 px-2.5 py-0.5 text-xs text-slate-700 font-medium">
                #{{ article.categoryName }}
              </span>
              <span class="rounded bg-slate-100 border border-slate-200 px-2.5 py-0.5 text-xs text-slate-700 font-medium">
                #ReadsNews
              </span>
              <span class="rounded bg-slate-100 border border-slate-200 px-2.5 py-0.5 text-xs text-slate-700 font-medium">
                #VoiceAI
              </span>
            </div>

            <!-- THANH TƯƠNG TÁC CUỐI BÀI (Share & Bookmark) -->
            <div class="rounded-lg bg-slate-50 border border-slate-200 p-4 flex flex-wrap items-center justify-between gap-3">
              <div class="flex items-center gap-2">
                <button
                  @click="toggleSavedArticle"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded border border-slate-200 bg-white text-xs font-bold text-slate-700 hover:text-sky-600 hover:border-sky-300 transition cursor-pointer"
                >
                  <svg
                    class="size-4"
                    :fill="isSaved ? 'currentColor' : 'none'"
                    :class="isSaved ? 'text-sky-500' : 'text-slate-400'"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z" />
                  </svg>
                  <span>{{ isSaved ? 'Đã lưu bài viết' : 'Lưu bài viết' }}</span>
                </button>

                <button
                  @click="copyArticleLink"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded border border-slate-200 bg-white text-xs font-bold text-slate-700 hover:text-sky-600 hover:border-sky-300 transition cursor-pointer"
                >
                  <svg class="size-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect width="14" height="14" x="8" y="8" rx="2" ry="2" />
                    <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
                  </svg>
                  <span>Chia sẻ link</span>
                </button>
              </div>

              <button
                @click="navigateTo('/')"
                class="text-xs font-bold text-sky-600 hover:text-sky-700 cursor-pointer flex items-center gap-1"
              >
                ← Quay lại trang chủ
              </button>
            </div>

            <!-- ============================================== -->
            <!-- KHU VỰC BÌNH LUẬN (Ý KIẾN BẠN ĐỌC)            -->
            <!-- ============================================== -->
            <section class="space-y-5 pt-4 border-t border-slate-200">
              <div class="flex items-center justify-between border-b-2 border-sky-500 pb-2">
                <div class="flex items-center gap-2">
                  <span class="h-4 w-1.5 bg-sky-500 rounded-full"></span>
                  <h3 class="text-base font-bold uppercase tracking-wide text-slate-900">
                    Ý Kiến Bạn Đọc ({{ comments.length }})
                  </h3>
                </div>
              </div>

              <!-- Form gửi bình luận -->
              <form @submit.prevent="submitComment" class="space-y-3 bg-slate-50/80 p-4 rounded-lg border border-slate-200">
                <div v-if="!currentUser" class="max-w-xs">
                  <input
                    v-model="newCommentAuthor"
                    type="text"
                    placeholder="Họ và tên của bạn..."
                    class="w-full rounded border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-900 focus:border-sky-500 focus:outline-hidden"
                  />
                </div>

                <textarea
                  v-model="newCommentContent"
                  rows="3"
                  placeholder="Chia sẻ ý kiến của bạn về bài viết này..."
                  class="w-full rounded border border-slate-300 bg-white p-3 text-xs sm:text-sm text-slate-900 placeholder:text-slate-400 focus:border-sky-500 focus:outline-hidden focus:ring-1 focus:ring-sky-500"
                  required
                ></textarea>

                <div class="flex items-center justify-between">
                  <span class="text-[11px] text-slate-400">Ý kiến được kiểm duyệt trước khi hiển thị.</span>
                  <button
                    type="submit"
                    :disabled="isSubmittingComment || !newCommentContent.trim()"
                    class="rounded bg-sky-500 hover:bg-sky-600 text-white text-xs font-bold px-4 py-2 transition cursor-pointer disabled:opacity-50"
                  >
                    {{ isSubmittingComment ? 'Đang gửi...' : 'Gửi bình luận' }}
                  </button>
                </div>
              </form>

              <!-- Danh sách bình luận -->
              <div class="space-y-3">
                <div
                  v-for="cmt in comments"
                  :key="cmt.id"
                  class="p-3.5 rounded-lg border border-slate-100 bg-white space-y-1.5"
                >
                  <div class="flex items-center justify-between text-xs">
                    <span class="font-bold text-slate-800">{{ cmt.author }}</span>
                    <span class="text-[11px] text-slate-400 font-mono">{{ cmt.time }}</span>
                  </div>
                  <p class="text-xs sm:text-[13px] text-[#222222] font-['Roboto',sans-serif] leading-relaxed">
                    {{ cmt.content }}
                  </p>
                  <div class="pt-1 flex items-center gap-3 text-[11px] text-slate-400">
                    <button class="hover:text-sky-600 flex items-center gap-1 cursor-pointer">
                      👍 <span>{{ cmt.likes || 0 }} thích</span>
                    </button>
                    <button class="hover:text-sky-600 cursor-pointer">Trả lời</button>
                  </div>
                </div>
              </div>
            </section>

          </article>

          <!-- ============================================== -->
          <!-- CỘT PHẢI: SIDEBAR (4/12)                       -->
          <!-- ============================================== -->
          <aside class="lg:col-span-4 space-y-6">

            <!-- Khối: Tin Cùng Chuyên Mục -->
            <div class="bg-white border border-slate-200 rounded-lg p-5 space-y-4 shadow-2xs">
              <div class="flex items-center justify-between border-b-2 border-sky-500 pb-2">
                <div class="flex items-center gap-2">
                  <span class="h-4 w-1.5 bg-sky-500 rounded-full"></span>
                  <h3 class="text-sm font-bold uppercase tracking-wide text-slate-900">
                    Cùng Chuyên Mục {{ article.categoryName }}
                  </h3>
                </div>
              </div>

              <div class="space-y-3">
                <article
                  v-for="rel in relatedArticles"
                  :key="rel.id"
                  class="group flex items-start gap-3 border-b border-slate-100 pb-3 last:border-b-0 last:pb-0"
                >
                  <img
                    :src="rel.image"
                    :alt="rel.title"
                    class="size-18 rounded object-cover shrink-0 border border-slate-200 cursor-pointer"
                    @click="navigateToArticle(rel)"
                    @error="$event.target.src = '/images/home/city.png'"
                  />
                  <div class="min-w-0 flex-1 space-y-1">
                    <h4
                      @click="navigateToArticle(rel)"
                      class="text-xs sm:text-[13px] font-bold text-slate-900 line-clamp-2 leading-snug group-hover:text-sky-600 transition cursor-pointer"
                    >
                      {{ rel.title }}
                    </h4>
                    <span class="text-[10px] text-slate-400 font-mono block">{{ rel.time }}</span>
                  </div>
                </article>
              </div>
            </div>

            <!-- Khối: Đọc Nhiều Nhất (Ranking 01, 02, 03... Tuổi Trẻ Style) -->
            <div class="bg-white border border-slate-200 rounded-lg p-5 space-y-4 shadow-2xs">
              <div class="flex items-center justify-between border-b-2 border-sky-500 pb-2">
                <div class="flex items-center gap-2">
                  <span class="h-4 w-1.5 bg-sky-500 rounded-full"></span>
                  <h3 class="text-sm font-bold uppercase tracking-wide text-slate-900">
                    Đọc Nhiều Nhất
                  </h3>
                </div>
              </div>

              <div class="space-y-3">
                <article
                  v-for="(pop, index) in popularArticles"
                  :key="pop.id"
                  class="group flex items-start gap-3 p-2 rounded hover:bg-sky-50/50 transition"
                >
                  <span class="text-2xl font-extrabold text-sky-400/50 group-hover:text-sky-500 font-mono shrink-0">
                    0{{ index + 1 }}
                  </span>
                  <div class="min-w-0 flex-1 space-y-1">
                    <span class="text-[10px] text-sky-600 font-bold block">{{ pop.categoryName }}</span>
                    <h4
                      @click="navigateToArticle(pop)"
                      class="text-xs font-bold text-slate-900 line-clamp-2 leading-snug group-hover:text-sky-600 transition cursor-pointer"
                    >
                      {{ pop.title }}
                    </h4>
                  </div>
                </article>
              </div>
            </div>

            <!-- Banner: Trợ lý đọc báo thông minh giọng nói AI -->
            <div class="rounded-lg border border-sky-200 bg-gradient-to-br from-sky-50 to-blue-50 p-4 space-y-3 text-center">
              <div class="size-10 rounded-full bg-sky-500 text-white mx-auto flex items-center justify-center shadow-sm">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z" />
                  <path d="M19 10v2a7 7 0 0 1-14 0v-2" />
                  <line x1="12" y1="19" x2="12" y2="12" />
                </svg>
              </div>
              <h4 class="text-xs font-bold text-slate-900 uppercase">Trợ Lý Giọng Nói AI</h4>
              <p class="text-[11px] text-slate-600">
                Bạn có thể ra lệnh giọng nói "Đọc tiếp", "Tạm dừng" hoặc nghe toàn văn bài báo bất cứ lúc nào.
              </p>
              <button
                @click="toggleSpeech"
                :disabled="ttsLoading"
                class="w-full rounded bg-sky-500 hover:bg-sky-600 disabled:opacity-70 text-white text-xs font-bold py-1.5 transition cursor-pointer shadow-2xs flex items-center justify-center gap-1.5"
              >
                <svg v-if="ttsLoading" class="size-3.5 animate-spin" viewBox="0 0 24 24" fill="none">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                </svg>
                {{ ttsLoading ? 'Đang tải giọng đọc...' : (isSpeaking && !isPaused ? 'Tạm dừng đọc ⏸' : 'Bật nghe đọc bài báo 🎧') }}
              </button>
            </div>

          </aside>

        </div>

      </div>

    </main>

    <!-- 3. FOOTER BÁO CHÍ (Chuẩn Thanh Niên / Tuổi Trẻ) -->
    <footer class="mt-16 bg-white border-t border-slate-200 text-slate-700">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10 space-y-8">
        
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-6 text-xs sm:text-sm font-medium">
          <div class="space-y-2.5">
            <a href="/" @click.prevent="navigateTo('/')" class="block text-slate-800 hover:text-sky-500 transition-colors">Chính trị</a>
            <a href="/" @click.prevent="navigateTo('/')" class="block text-slate-800 hover:text-sky-500 transition-colors">Thời sự</a>
            <a href="/" @click.prevent="navigateTo('/')" class="block text-slate-800 hover:text-sky-500 transition-colors">Thế giới</a>
            <a href="/" @click.prevent="navigateTo('/')" class="block text-slate-800 hover:text-sky-500 transition-colors">Kinh tế</a>
          </div>

          <div class="space-y-2.5">
            <a href="/" @click.prevent="navigateTo('/')" class="block text-slate-800 hover:text-sky-500 transition-colors">Đời sống</a>
            <a href="/" @click.prevent="navigateTo('/')" class="block text-slate-800 hover:text-sky-500 transition-colors">Sức khoẻ</a>
            <a href="/" @click.prevent="navigateTo('/')" class="block text-slate-800 hover:text-sky-500 transition-colors">Giới trẻ</a>
            <a href="/" @click.prevent="navigateTo('/')" class="block text-slate-800 hover:text-sky-500 transition-colors">Giáo dục</a>
          </div>

          <div class="space-y-2.5">
            <a href="/" @click.prevent="navigateTo('/')" class="block text-slate-800 hover:text-sky-500 transition-colors">Du lịch</a>
            <a href="/" @click.prevent="navigateTo('/')" class="block text-slate-800 hover:text-sky-500 transition-colors">Văn hoá</a>
            <a href="/" @click.prevent="navigateTo('/')" class="block text-slate-800 hover:text-sky-500 transition-colors">Giải trí</a>
            <a href="/" @click.prevent="navigateTo('/')" class="block text-slate-800 hover:text-sky-500 transition-colors">Thể thao</a>
          </div>

          <div class="space-y-2.5">
            <a href="/" @click.prevent="navigateTo('/')" class="block text-slate-800 hover:text-sky-500 transition-colors">Công nghệ</a>
            <a href="/" @click.prevent="navigateTo('/')" class="block text-slate-800 hover:text-sky-500 transition-colors">Xe</a>
            <a href="/" @click.prevent="navigateTo('/')" class="block text-slate-800 hover:text-sky-500 transition-colors">Thời trang trẻ</a>
            <a href="/" @click.prevent="navigateTo('/')" class="block text-slate-800 hover:text-sky-500 transition-colors">Video & Audio</a>
          </div>

          <div class="space-y-2.5">
            <a href="/" @click.prevent="navigateTo('/')" class="block text-slate-800 hover:text-sky-500 transition-colors">Bạn đọc</a>
            <a href="/" @click.prevent="navigateTo('/')" class="block text-slate-800 hover:text-sky-500 transition-colors">Rao vặt</a>
            <a href="/" @click.prevent="navigateTo('/')" class="block text-slate-800 hover:text-sky-500 transition-colors">Podcast AI</a>
          </div>
        </div>

        <div class="pt-6 border-t border-slate-200 flex flex-col md:flex-row items-center justify-between gap-6">
          <div class="flex items-center gap-3">
            <img
              src="/logo/Khuon-Ngang-removebg.png"
              alt="ReadVn"
              class="h-8.5 w-auto object-contain cursor-pointer"
              @click="navigateTo('/')"
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
      class="fixed bottom-6 right-5 z-40 size-10 rounded border border-slate-300 bg-white/95 text-slate-700 hover:text-sky-600 hover:border-sky-400 hover:bg-sky-50 shadow-sm flex flex-col items-center justify-center transition-all cursor-pointer group"
      title="Lên đầu trang"
    >
      <svg class="size-3.5 group-hover:-translate-y-0.5 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <path d="m18 15-6-6-6 6" />
      </svg>
      <span class="text-[8px] font-extrabold uppercase">TOP</span>
    </button>

    <!-- Toast Notification -->
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

    <!-- Logout Confirmation Modal -->
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


<style scoped>
/* Khoảng cách rộng rãi giữa các đoạn văn chuẩn phong cách Báo Tuổi Trẻ giúp đọc nhanh */
:deep(.article-body-content p) {
  margin-bottom: 1.75rem !important; /* ~28px khoảng cách trống giữa các đoạn văn */
  line-height: 1.85 !important;       /* Độ giãn dòng thoáng mắt */
  color: #222222 !important;
  font-family: 'Roboto', sans-serif !important;
  font-weight: 400 !important;
}

:deep(.article-body-content p:last-child) {
  margin-bottom: 0 !important;
}

/* Đường liên kết trong bài báo */
:deep(.article-body-content a) {
  color: #0284c7 !important; /* Xanh sky-600 */
  text-decoration: none !important;
}

:deep(.article-body-content a:hover) {
  text-decoration: underline !important;
}

:deep(.article-body-content strong),
:deep(.article-body-content b) {
  color: #222222 !important;
  font-weight: 700 !important;
}

:deep(.article-body-content h2),
:deep(.article-body-content h3),
:deep(.article-body-content h4) {
  color: #222222 !important;
  font-family: 'Roboto', sans-serif !important;
  font-weight: 700 !important;
  margin-top: 2rem !important;
  margin-bottom: 1.25rem !important;
  line-height: 1.4 !important;
}

:deep(.article-body-content img) {
  border-radius: 0.375rem;
  margin: 1.75rem auto !important;
  max-width: 100%;
  height: auto;
  display: block;
}

:deep(.article-body-content figure) {
  margin: 1.75rem 0 !important;
  text-align: center;
}

:deep(.article-body-content figcaption) {
  font-size: 0.8125rem;
  color: #64748b;
  font-style: italic;
  margin-top: 0.5rem;
  text-align: center;
}
</style>
