<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { auth } from '@/utils/auth'
import { authService } from '@/services/authService'
import { toast } from 'vue3-toastify'

const categories = ['Tất cả', 'Thời sự', 'Thế giới', 'Kinh doanh', 'Thể thao', 'Công nghệ', 'Đời sống']

const articles = [
  {
    id: 1,
    category: 'Thời sự',
    source: 'Nhịp sống Việt',
    time: '12 phút trước',
    minutes: 6,
    author: 'Minh Anh',
    views: 12540,
    title: 'Những đô thị thông minh đang thay đổi cách chúng ta di chuyển hằng ngày',
    summary: 'Từ mạng lưới xe buýt điện, tàu cao tốc đô thị đến các tuyến phố ưu tiên người đi bộ, nhiều thành phố đang mở ra phong cách sống xanh và tiện nghi hơn.',
    aiSummary: '• Ưu tiên giao thông công cộng và kết nối xanh thay vì phát triển xe cá nhân.\n• Ứng dụng bản đồ dữ liệu thời gian thực giúp giảm 35% thời gian chờ đợi.\n• Thử nghiệm phố đi bộ và mở rộng vỉa hè nâng cao chất lượng không gian sống đô thị.',
    image: '/images/home/city.png',
    voice: 'Google WaveNet Nữ (vi-VN)',
    body: 'Các đô thị lớn đang nhìn lại cách không gian đường phố được phân chia. Thay vì dành phần lớn diện tích cho phương tiện cá nhân, nhiều kế hoạch mới ưu tiên kết nối giao thông công cộng, lối đi bộ và không gian xanh.\n\nSự chuyển dịch này không diễn ra trong một sớm một chiều. Những thay đổi nhỏ như mở rộng vỉa hè, tổ chức lại điểm trung chuyển và bổ sung dữ liệu giao thông theo thời gian thực đang giúp người dân có thêm lựa chọn cho hành trình hằng ngày.\n\nGiới quy hoạch cho rằng thành công của các thử nghiệm sẽ phụ thuộc vào khả năng kết nối giữa khu dân cư, nơi làm việc và dịch vụ thiết yếu, mang đến trải nghiệm di chuyển liền mạch và bảo vệ môi trường.',
  },
  {
    id: 2,
    category: 'Công nghệ',
    source: 'Dữ liệu Mở',
    time: '35 phút trước',
    minutes: 4,
    author: 'Hoàng Long',
    views: 9360,
    title: 'Mô hình văn phòng linh hoạt kết hợp AI đang định hình lại ngày làm việc',
    summary: 'Không gian làm việc mới tích hợp trợ lý AI, phòng tập trung yên tĩnh và lịch làm việc linh động đang tối ưu hóa năng suất cho nhân sự số.',
    aiSummary: '• Doanh nghiệp chuyển đổi sang không gian làm việc phân vùng linh hoạt theo dự án.\n• Trợ lý AI hỗ trợ tự động hóa ghi chú cuộc họp và điều phối lịch làm việc.\n• Năng suất lao động tăng 28% khi nhân viên được chủ động chọn nhịp độ làm việc.',
    image: '/images/home/office.png',
    voice: 'Google WaveNet Nam (vi-VN)',
    body: 'Nhiều tổ chức đang thiết kế lại văn phòng theo hướng linh hoạt hơn. Thay vì một chỗ ngồi cố định cho mọi nhân viên, không gian được chia theo nhu cầu: khu cộng tác, phòng tập trung và các điểm gặp nhanh.\n\nCách làm này đặt ra yêu cầu mới về công nghệ đặt chỗ, quyền riêng tư và thói quen phối hợp. Hiệu quả phụ thuộc nhiều vào quy ước làm việc rõ ràng, không chỉ vào thiết kế nội thất.',
  },
  {
    id: 3,
    category: 'Đời sống',
    source: 'Tạp chí Cuối Tuần',
    time: '1 giờ trước',
    minutes: 5,
    author: 'Thu Hà',
    views: 7210,
    title: 'Nghệ thuật ngắt nhịp: Một khoảng nghỉ ngắn giúp phục hồi năng lượng tư duy',
    summary: 'Những phương pháp khoa học đơn giản để tạo điểm ngắt nhịp lý tưởng giữa lịch trình làm việc căng thẳng mà không làm gián đoạn hiệu suất.',
    aiSummary: '• Áp dụng chu kỳ làm việc 50 phút tập trung kết hợp 10 phút thả lỏng mắt và vận động nhẹ.\n• Giảm 40% tình trạng kiệt sức kỹ thuật số (digital fatigue) khi rời màn hình định kỳ.\n• Duy trì năng lượng ổn định suốt cả ngày mà không cần lạm dụng caffeine.',
    image: '/images/home/work.png',
    voice: 'Google WaveNet Nữ (vi-VN)',
    body: 'Những khoảng nghỉ ngắn giúp nhiều người lấy lại sự tập trung sau các phiên làm việc liên tục. Một vòng đi bộ, vài phút rời màn hình hoặc một cuộc trò chuyện ngắn đều có thể tạo điểm ngắt cần thiết.\n\nĐiều quan trọng là lựa chọn cách nghỉ phù hợp với công việc và duy trì lịch nghỉ như một phần bình thường của ngày làm việc.',
  },
  {
    id: 4,
    category: 'Kinh doanh',
    source: 'Bản tin Thị trường',
    time: '2 giờ trước',
    minutes: 7,
    author: 'Quốc Bảo',
    views: 5120,
    title: 'Xu hướng đầu tư công trình bền vững và bài toán tiết kiệm năng lượng dài hạn',
    summary: 'Tiêu chuẩn xanh không còn là khẩu hiệu mà đang trở thành thước đo giá trị cốt lõi giúp các tập đoàn tối ưu hóa chi phí vận hành 20 năm tới.',
    aiSummary: '• Tiêu chuẩn LEED & Lotus giúp giảm tới 30% hóa đơn tiền điện và nước của tòa nhà.\n• Nhu cầu thuê văn phòng xanh tăng mạnh từ các công ty đa quốc gia có cam kết ESG.\n• Bài toán hoàn vốn đầu tư công nghệ xanh rút ngắn xuống còn 4-6 năm.',
    image: '/images/home/spaces.png',
    voice: 'Google WaveNet Nam (vi-VN)',
    body: 'Các chủ đầu tư đang quan tâm nhiều hơn đến hiệu suất năng lượng và chất lượng môi trường bên trong công trình. Những tiêu chuẩn thiết kế mới khuyến khích tận dụng ánh sáng tự nhiên, lựa chọn vật liệu bền vững và theo dõi mức tiêu thụ năng lượng.\n\nBài toán nằm ở việc cân bằng chi phí ban đầu với hiệu quả vận hành trong dài hạn.',
  },
  {
    id: 5,
    category: 'Thế giới',
    source: 'Góc nhìn Toàn cầu',
    time: '3 giờ trước',
    minutes: 5,
    author: 'Hải Yến',
    views: 4380,
    title: 'Các siêu đô thị ven biển ứng dụng AI dự báo để chủ động thích ứng biến đổi khí hậu',
    summary: 'Mô hình học máy kết hợp mạng lưới cảm biến IoT đang giúp các thành phố ven biển phát cảnh báo sớm ngập lụt trước 48 giờ.',
    aiSummary: '• Mạng lưới cảm biến vệ tinh & IoT cung cấp dữ liệu thủy triều và vũ lượng chính xác từng mét vuông.\n• Hệ thống đê kè thông minh tự động đóng mở giảm thiểu rủi ro cho khu dân cư trũng thấp.\n• Hợp tác chia sẻ dữ liệu khí hậu xuyên biên giới đạt cột mốc mới.',
    image: '/images/home/city.png',
    voice: 'Google WaveNet Nữ (vi-VN)',
    body: 'Các đô thị ven biển đang cập nhật kế hoạch ứng phó với những đợt mưa lớn và triều cường. Dữ liệu thời tiết theo khu vực giúp cơ quan quản lý chủ động điều phối giao thông, vận hành hệ thống thoát nước và thông tin đến cư dân.\n\nCác chuyên gia nhấn mạnh rằng hạ tầng xanh và quy hoạch dài hạn cần song hành với cảnh báo sớm.',
  },
]

// Trạng thái ứng dụng
const activeCategory = ref('Tất cả')
const activeView = ref('latest') // 'latest' | 'popular' | 'saved'
const searchQuery = ref('')
const savedIds = ref(readSavedIds())
const selectedArticle = ref(null)
const notice = ref('')
let noticeTimer = null

// Trình phát âm thanh
const playingId = ref(null)
const activePlayingArticle = ref(null)
const isPaused = ref(false)
const speechRate = ref(1)
const playbackProgress = ref(0)
let progressInterval = null

const featuredArticle = articles[0]

// User Auth info
const currentUser = computed(() => auth.getUser())
const readerName = computed(() => {
  if (currentUser.value?.name) {
    return currentUser.value.name.split(' ').at(-1)
  }
  return 'bạn đọc'
})

// Tiêu đề danh sách
const feedTitle = computed(() => {
  if (activeView.value === 'saved') return 'Bài viết đã lưu'
  if (activeView.value === 'popular') return 'Được quan tâm nhất'
  return activeCategory.value === 'Tất cả' ? 'Tin mới cập nhật' : `Chuyên mục: ${activeCategory.value}`
})

// Lọc bài viết
const filteredArticles = computed(() => {
  let list = articles.filter((a) => a.id !== featuredArticle.id)

  if (activeView.value === 'saved') {
    list = articles.filter((a) => savedIds.value.includes(a.id))
  }

  if (activeCategory.value !== 'Tất cả') {
    list = list.filter((a) => a.category === activeCategory.value)
  }

  const query = searchQuery.value.trim().toLocaleLowerCase('vi')
  if (query) {
    list = list.filter((a) => `${a.title} ${a.summary} ${a.source} ${a.author}`.toLocaleLowerCase('vi').includes(query))
  }

  if (activeView.value === 'popular') {
    list = [...list].sort((a, b) => b.views - a.views)
  }

  return list
})

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
  if (savedIds.value.includes(article.id)) {
    savedIds.value = savedIds.value.filter((id) => id !== article.id)
    notify('Đã xóa khỏi danh sách lưu trữ')
  } else {
    savedIds.value.push(article.id)
    notify('Đã lưu bài viết vào thư viện cá nhân ✨')
  }
}

function setCategory(cat) {
  activeCategory.value = cat
  activeView.value = 'latest'
}

function notify(msg) {
  notice.value = msg
  clearTimeout(noticeTimer)
  noticeTimer = setTimeout(() => {
    notice.value = ''
  }, 2800)
}

// Xử lý đọc văn bản SpeechSynthesis
function toggleSpeech(article) {
  if (!('speechSynthesis' in window)) {
    return notify('Trình duyệt của bạn chưa hỗ trợ Web Speech API.')
  }

  // Đang nghe bài này -> Bấm để tạm dừng hoặc tiếp tục
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

  // Chuyển sang đọc bài mới
  stopSpeech()
  activePlayingArticle.value = article
  playingId.value = article.id
  isPaused.value = false
  playbackProgress.value = 5

  const textToRead = `${article.title}. Tóm tắt. ${article.summary}. Nội dung chi tiết. ${article.body}`
  const utterance = new SpeechSynthesisUtterance(textToRead)
  utterance.lang = 'vi-VN'
  utterance.rate = speechRate.value

  // Giả lập thanh tiến trình phát dựa theo độ dài bài
  const estSeconds = Math.max(article.minutes * 60, 60)
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
  notify(`Đang phát: ${article.title}`)
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
    // Khởi động lại với tốc độ mới
    toggleSpeech(activePlayingArticle.value)
    toggleSpeech(activePlayingArticle.value)
  }
  notify(`Tốc độ đọc: ${rate}x`)
}

const showLogoutModal = ref(false)

function handleLogout() {
  showLogoutModal.value = true
}

async function confirmLogout() {
  try {
    await authService.logout()
  } catch (e) {
    // Không gián đoạn nếu network có lỗi
  }
  auth.clearAuth()
  showLogoutModal.value = false
  toast.success('Đã đăng xuất tài khoản thành công!', {
    autoClose: 2000,
  })
  setTimeout(() => {
    window.location.reload()
  }, 1000)
}

onBeforeUnmount(() => {
  stopSpeech()
  clearTimeout(noticeTimer)
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 text-slate-800 font-sans selection:bg-indigo-500 selection:text-white">
    <!-- 1. TOP NAVBAR -->
    <header
      class="sticky top-0 z-40 bg-white/90 backdrop-blur-md border-b border-slate-200/80 transition-all duration-200 shadow-xs">
      <div class="mx-auto flex h-18 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">

        <!-- Logo -->
        <a href="/" class="group flex shrink-0 items-center gap-2.5">
          <div
            class="flex size-10 items-center justify-center rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-600 text-white shadow-md shadow-indigo-500/20 group-hover:scale-105 transition-transform duration-200">
            <!-- Soundwave AI Icon -->
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
              stroke-linecap="round" stroke-linejoin="round">
              <path d="M2 10v4" />
              <path d="M6 7v10" />
              <path d="M10 4v16" />
              <path d="M14 7v10" />
              <path d="M18 10v4" />
              <path d="M22 12v0" />
            </svg>
          </div>
          <div class="flex flex-col">
            <span
              class="text-lg font-black tracking-tight text-slate-900 group-hover:text-indigo-600 transition-colors">
              Reads<span class="text-indigo-600">News</span>
            </span>
            <span
              class="text-[10px] font-semibold tracking-wider text-indigo-500 uppercase -mt-1 flex items-center gap-1">
              <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
              AI Audio Reader
            </span>
          </div>
        </a>

        <!-- Search Bar -->
        <div class="relative hidden sm:block max-w-md flex-1">
          <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-slate-400">
            <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
              stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8" />
              <path d="m21 21-4.3-4.3" />
            </svg>
          </div>
          <input v-model="searchQuery" type="search"
            class="w-full rounded-full border border-slate-200 bg-slate-100/70 py-2 ps-10 pe-11 text-sm text-slate-900 placeholder:text-slate-400 transition-all focus:border-indigo-500 focus:bg-white focus:outline-hidden focus:ring-3 focus:ring-indigo-500/15"
            placeholder="Tìm kiếm tin tức, chủ đề AI..." />
          <kbd
            class="pointer-events-none absolute inset-y-0 end-0 my-auto me-2.5 flex h-5.5 items-center rounded-md border border-slate-300 bg-white px-1.5 text-[10px] font-semibold text-slate-400 shadow-2xs">
            ⌘K
          </kbd>
        </div>

        <!-- User Actions / Status -->
        <div class="flex items-center gap-2.5 sm:gap-3">
          <template v-if="currentUser">
            <div
              class="hidden md:flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 border border-slate-200/80">
              <span class="size-2 rounded-full bg-emerald-500"></span>
              <span class="text-xs font-medium text-slate-600">
                Chào, <strong class="text-slate-900 font-semibold">{{ readerName }}</strong>
              </span>
              <span v-if="currentUser.role === 'admin'"
                class="rounded bg-indigo-100 px-1.5 py-0.5 text-[10px] font-bold text-indigo-700 uppercase">
                Admin
              </span>
            </div>

            <a v-if="currentUser.role === 'admin' || currentUser.role === 'editor'" href="/admin"
              class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:border-slate-300 hover:bg-slate-50 transition-colors">
              <svg class="size-3.5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2">
                <rect width="7" height="9" x="3" y="3" rx="1" />
                <rect width="7" height="5" x="14" y="3" rx="1" />
                <rect width="7" height="9" x="14" y="12" rx="1" />
                <rect width="7" height="5" x="3" y="16" rx="1" />
              </svg>
              Quản trị
            </a>

            <button @click="handleLogout"
              class="rounded-lg p-2 text-slate-500 hover:bg-rose-50 hover:text-rose-600 transition-colors"
              title="Đăng xuất">
              <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                <polyline points="16 17 21 12 16 7" />
                <line x1="21" x2="9" y1="12" y2="12" />
              </svg>
            </button>
          </template>

          <template v-else>
            <a href="/signin"
              class="rounded-lg px-3.5 py-2 text-xs sm:text-sm font-semibold text-slate-700 hover:bg-slate-100 hover:text-slate-900 transition-colors">
              Đăng nhập
            </a>
            <a href="/signup"
              class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-xs sm:text-sm font-semibold text-white shadow-xs shadow-indigo-600/30 hover:bg-indigo-500 transition-all hover:shadow-md hover:shadow-indigo-600/20 active:scale-97">
              Tạo tài khoản
            </a>
          </template>
        </div>
      </div>

      <!-- Categories & Sub-nav Pills -->
      <div class="border-t border-slate-100 bg-white">
        <div
          class="mx-auto flex max-w-7xl items-center justify-between gap-3 overflow-x-auto px-4 py-2 sm:px-6 lg:px-8 no-scrollbar">

          <!-- Category Selector -->
          <div class="flex items-center gap-1.5 shrink-0">
            <button v-for="cat in categories" :key="cat" @click="setCategory(cat)" :class="[
              'rounded-full px-3.5 py-1.5 text-xs font-semibold transition-all duration-200 shrink-0',
              activeCategory === cat
                ? 'bg-indigo-600 text-white shadow-xs shadow-indigo-600/25'
                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
            ]">
              {{ cat }}
            </button>
          </div>

          <!-- Feed Filter Modes -->
          <div class="flex items-center gap-1.5 border-s border-slate-200 ps-3 shrink-0">
            <button @click="activeView = 'latest'; activeCategory = 'Tất cả'" :class="[
              'flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors',
              activeView === 'latest' ? 'bg-slate-100 font-semibold text-indigo-700' : 'text-slate-500 hover:text-slate-800'
            ]">
              <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" />
                <polyline points="12 6 12 12 16 14" />
              </svg>
              Mới nhất
            </button>

            <button @click="activeView = 'popular'; activeCategory = 'Tất cả'" :class="[
              'flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors',
              activeView === 'popular' ? 'bg-slate-100 font-semibold text-indigo-700' : 'text-slate-500 hover:text-slate-800'
            ]">
              <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path
                  d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 3z" />
              </svg>
              Nổi bật
            </button>

            <button @click="activeView = 'saved'; activeCategory = 'Tất cả'" :class="[
              'flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors',
              activeView === 'saved' ? 'bg-slate-100 font-semibold text-indigo-700' : 'text-slate-500 hover:text-slate-800'
            ]">
              <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z" />
              </svg>
              Đã lưu ({{ savedIds.length }})
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- 2. MAIN CONTAINER -->
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

      <!-- HERO SECTION: FEATURED ARTICLE & AI AUDIO BRIEFING -->
      <section class="mb-12 grid gap-6 lg:grid-cols-12 items-stretch">

        <!-- FEATURED HERO CARD (8 Cols) -->
        <article
          class="lg:col-span-8 group relative overflow-hidden rounded-3xl bg-slate-900 shadow-xl shadow-slate-900/10 min-h-[460px] flex flex-col justify-end transition-all duration-300">
          <!-- Background Cover Image with Gradient Scrim -->
          <img :src="featuredArticle.image" :alt="featuredArticle.title"
            class="absolute inset-0 size-full object-cover object-center opacity-85 transition-transform duration-700 ease-out group-hover:scale-105" />
          <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/70 to-transparent"></div>

          <!-- Top Tag -->
          <div class="absolute top-6 start-6 z-10 flex items-center gap-2">
            <span
              class="inline-flex items-center gap-1.5 rounded-full bg-indigo-600/90 px-3 py-1 text-xs font-bold text-white shadow-sm backdrop-blur-md">
              <span class="size-1.5 rounded-full bg-amber-300 animate-ping"></span>
              TIN NỔI BẬT HÔM NAY
            </span>
            <span
              class="inline-flex items-center gap-1 rounded-full bg-white/20 px-2.5 py-1 text-xs font-medium text-white backdrop-blur-md border border-white/20">
              <svg class="size-3 text-indigo-300" viewBox="0 0 24 24" fill="currentColor">
                <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5" />
                <path d="M15.54 8.46a5 5 0 0 1 0 7.07" />
              </svg>
              Giọng AI WaveNet
            </span>
          </div>

          <!-- Bottom Content Info -->
          <div class="relative z-10 p-6 sm:p-9 max-w-3xl">
            <div class="flex items-center gap-3 text-xs font-medium text-slate-300 mb-3">
              <span class="text-indigo-400 font-semibold">{{ featuredArticle.category }}</span>
              <span>•</span>
              <span>{{ featuredArticle.source }}</span>
              <span>•</span>
              <span>{{ featuredArticle.time }}</span>
            </div>

            <h1
              class="text-2xl sm:text-3xl lg:text-4xl font-extrabold tracking-tight text-white leading-snug drop-shadow-xs">
              {{ featuredArticle.title }}
            </h1>

            <p class="mt-3 text-sm sm:text-base text-slate-300 line-clamp-2 leading-relaxed">
              {{ featuredArticle.summary }}
            </p>

            <!-- Actions Bar -->
            <div class="mt-6 flex flex-wrap items-center gap-3.5">
              <!-- Play / Pause Speech Button -->
              <button @click="toggleSpeech(featuredArticle)" :class="[
                'inline-flex items-center gap-2.5 rounded-xl px-5 py-3 text-sm font-bold transition-all shadow-md active:scale-97 cursor-pointer',
                playingId === featuredArticle.id && !isPaused
                  ? 'bg-emerald-500 text-white shadow-emerald-500/30 ring-4 ring-emerald-500/20'
                  : 'bg-indigo-600 hover:bg-indigo-500 text-white shadow-indigo-600/30'
              ]">
                <!-- Play / Pause Icon -->
                <svg v-if="playingId === featuredArticle.id && !isPaused" class="size-4.5" viewBox="0 0 24 24"
                  fill="currentColor">
                  <rect x="6" y="4" width="4" height="16" rx="1" />
                  <rect x="14" y="4" width="4" height="16" rx="1" />
                </svg>
                <svg v-else class="size-4.5" viewBox="0 0 24 24" fill="currentColor">
                  <polygon points="5 3 19 12 5 21 5 3" />
                </svg>
                <span>{{ playingId === featuredArticle.id && !isPaused ? 'Đang phát...' : 'Nghe bài viết' }}</span>
                <span class="rounded bg-black/20 px-1.5 py-0.5 text-[11px] font-normal text-indigo-100">
                  {{ featuredArticle.minutes }} phút
                </span>
              </button>

              <!-- Read Article Detail -->
              <button @click="selectedArticle = featuredArticle"
                class="inline-flex items-center gap-2 rounded-xl bg-white/15 hover:bg-white/25 px-4 py-3 text-sm font-semibold text-white backdrop-blur-md border border-white/20 transition-colors cursor-pointer">
                Đọc toàn bài
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M5 12h14" />
                  <path d="m12 5 7 7-7 7" />
                </svg>
              </button>

              <!-- Bookmark Button -->
              <button @click="toggleSaved(featuredArticle)" :class="[
                'size-11 flex items-center justify-center rounded-xl border backdrop-blur-md transition-all cursor-pointer',
                savedIds.includes(featuredArticle.id)
                  ? 'bg-amber-400/90 border-amber-300 text-slate-950 shadow-md shadow-amber-400/20'
                  : 'bg-white/10 hover:bg-white/20 border-white/20 text-white'
              ]" :title="savedIds.includes(featuredArticle.id) ? 'Bỏ lưu' : 'Lưu bài'">
                <svg class="size-5" viewBox="0 0 24 24"
                  :fill="savedIds.includes(featuredArticle.id) ? 'currentColor' : 'none'" stroke="currentColor"
                  stroke-width="2">
                  <path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z" />
                </svg>
              </button>
            </div>
          </div>
        </article>

        <!-- AI AUDIO BRIEFING ASIDE (4 Cols) -->
        <aside
          class="lg:col-span-4 rounded-3xl border border-slate-200/90 bg-white p-6 shadow-sm flex flex-col justify-between">
          <div>
            <!-- Box Header -->
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
              <div class="flex items-center gap-2.5">
                <div class="flex size-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                  <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z" />
                    <path d="M19 10v2a7 7 0 0 1-14 0v-2" />
                    <line x1="12" x2="12" y1="19" y2="22" />
                  </svg>
                </div>
                <div>
                  <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Trạm Tin AI Audio</h2>
                  <p class="text-xs text-slate-500">Giọng đọc tổng hợp thông minh</p>
                </div>
              </div>

              <!-- Animated Soundwave EQ -->
              <div class="flex items-end gap-1 h-5" aria-hidden="true">
                <span :class="['w-1 rounded-full bg-indigo-500', playingId ? 'h-5 animate-pulse' : 'h-2']"></span>
                <span :class="['w-1 rounded-full bg-indigo-600', playingId ? 'h-3 animate-bounce' : 'h-3']"></span>
                <span :class="['w-1 rounded-full bg-violet-600', playingId ? 'h-5 animate-pulse' : 'h-4']"></span>
                <span :class="['w-1 rounded-full bg-indigo-500', playingId ? 'h-4 animate-bounce' : 'h-2']"></span>
              </div>
            </div>

            <!-- Voice Control Speed -->
            <div class="mt-4 rounded-xl bg-slate-50 p-3.5 border border-slate-100">
              <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-slate-600">Tốc độ phát âm:</span>
                <span class="text-xs font-bold text-indigo-600">{{ speechRate }}x</span>
              </div>
              <div class="grid grid-cols-4 gap-1.5">
                <button v-for="rate in [0.8, 1.0, 1.25, 1.5]" :key="rate" @click="changeSpeed(rate)" :class="[
                  'rounded-lg py-1.5 text-xs font-bold transition-all',
                  speechRate === rate
                    ? 'bg-indigo-600 text-white shadow-xs'
                    : 'bg-white text-slate-600 hover:bg-slate-200/70 border border-slate-200'
                ]">
                  {{ rate }}x
                </button>
              </div>
            </div>

            <!-- Quick Listen Playlist -->
            <div class="mt-5">
              <div class="flex items-center justify-between mb-2.5">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Danh sách phát nhanh</span>
                <span class="text-xs font-semibold text-indigo-600">3 bài hot</span>
              </div>

              <div class="space-y-2">
                <div v-for="item in articles.slice(1, 4)" :key="item.id" @click="toggleSpeech(item)" :class="[
                  'group flex items-center gap-3 rounded-xl p-2.5 border transition-all cursor-pointer',
                  playingId === item.id
                    ? 'border-indigo-400 bg-indigo-50/60 shadow-xs'
                    : 'border-slate-100 bg-white hover:border-slate-200 hover:bg-slate-50/80'
                ]">
                  <div :class="[
                    'size-9 shrink-0 rounded-lg flex items-center justify-center transition-colors',
                    playingId === item.id
                      ? 'bg-indigo-600 text-white'
                      : 'bg-slate-100 text-slate-600 group-hover:bg-indigo-100 group-hover:text-indigo-600'
                  ]">
                    <svg v-if="playingId === item.id && !isPaused" class="size-4" viewBox="0 0 24 24"
                      fill="currentColor">
                      <rect x="6" y="4" width="4" height="16" rx="1" />
                      <rect x="14" y="4" width="4" height="16" rx="1" />
                    </svg>
                    <svg v-else class="size-4" viewBox="0 0 24 24" fill="currentColor">
                      <polygon points="5 3 19 12 5 21 5 3" />
                    </svg>
                  </div>
                  <div class="min-w-0 flex-1">
                    <p
                      class="text-xs font-semibold text-slate-900 truncate group-hover:text-indigo-600 transition-colors">
                      {{ item.title }}
                    </p>
                    <p class="text-[11px] text-slate-400 mt-0.5 flex items-center gap-1.5">
                      <span>{{ item.category }}</span>
                      <span>•</span>
                      <span>{{ item.minutes }} phút</span>
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Bottom Helper Note -->
          <div class="mt-6 pt-4 border-t border-slate-100 text-center">
            <p class="text-xs text-slate-400">
              Công nghệ chuyển văn bản thành giọng nói tiếng Việt
            </p>
          </div>
        </aside>
      </section>

      <!-- 3. FEED SECTION: ARTICLE GRID -->
      <section>
        <!-- Section Header -->
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
          <div>
            <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
              <span>{{ feedTitle }}</span>
              <span class="rounded-full bg-slate-200/70 px-2 py-0.5 text-xs font-bold text-slate-600">
                {{ filteredArticles.length }}
              </span>
            </h2>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
              Tuyển tập những tin tức nổi bật được tối ưu cho cả đọc và nghe bằng AI
            </p>
          </div>
        </div>

        <!-- Articles Grid -->
        <div v-if="filteredArticles.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
          <article v-for="item in filteredArticles" :key="item.id"
            class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs transition-all duration-300 hover:-translate-y-1 hover:border-slate-300 hover:shadow-xl hover:shadow-slate-200/50">
            <!-- Card Image Box -->
            <div class="relative h-48 w-full overflow-hidden bg-slate-100">
              <img :src="item.image" :alt="item.title"
                class="size-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy" />

              <!-- Badges on Image -->
              <span
                class="absolute top-3 start-3 rounded-lg bg-white/95 px-2.5 py-1 text-xs font-bold text-indigo-700 shadow-sm backdrop-blur-xs">
                {{ item.category }}
              </span>

              <!-- Quick Play Floating Button -->
              <button @click="toggleSpeech(item)" :class="[
                'absolute bottom-3 end-3 flex size-10 items-center justify-center rounded-full shadow-lg transition-transform active:scale-90 cursor-pointer',
                playingId === item.id && !isPaused
                  ? 'bg-emerald-500 text-white shadow-emerald-500/40'
                  : 'bg-indigo-600 text-white hover:bg-indigo-500 shadow-indigo-600/40'
              ]" :title="playingId === item.id && !isPaused ? 'Tạm dừng' : 'Nghe bài này'">
                <svg v-if="playingId === item.id && !isPaused" class="size-4" viewBox="0 0 24 24" fill="currentColor">
                  <rect x="6" y="4" width="4" height="16" rx="1" />
                  <rect x="14" y="4" width="4" height="16" rx="1" />
                </svg>
                <svg v-else class="size-4" viewBox="0 0 24 24" fill="currentColor">
                  <polygon points="5 3 19 12 5 21 5 3" />
                </svg>
              </button>
            </div>

            <!-- Card Content Body -->
            <div class="flex flex-1 flex-col justify-between p-5">
              <div>
                <div class="flex items-center justify-between text-xs font-medium text-slate-400 mb-2.5">
                  <span class="text-indigo-600 font-semibold">{{ item.source }}</span>
                  <span>{{ item.time }}</span>
                </div>

                <h3 @click="selectedArticle = item"
                  class="text-base sm:text-lg font-bold text-slate-900 leading-snug line-clamp-2 hover:text-indigo-600 transition-colors cursor-pointer">
                  {{ item.title }}
                </h3>

                <p class="mt-2 text-xs sm:text-sm text-slate-500 line-clamp-3 leading-relaxed">
                  {{ item.summary }}
                </p>
              </div>

              <!-- Card Footer -->
              <div
                class="mt-5 pt-3.5 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400">
                <div class="flex items-center gap-1.5">
                  <span class="font-medium text-slate-600">{{ item.author }}</span>
                  <span>•</span>
                  <span>{{ item.minutes }} phút đọc</span>
                </div>

                <div class="flex items-center gap-2">
                  <button @click="toggleSaved(item)" :class="[
                    'rounded-lg p-1.5 transition-colors cursor-pointer',
                    savedIds.includes(item.id)
                      ? 'text-amber-500 hover:text-amber-600 bg-amber-50'
                      : 'text-slate-400 hover:text-slate-700 hover:bg-slate-100'
                  ]" :title="savedIds.includes(item.id) ? 'Bỏ lưu' : 'Lưu lại'">
                    <svg class="size-4" viewBox="0 0 24 24" :fill="savedIds.includes(item.id) ? 'currentColor' : 'none'"
                      stroke="currentColor" stroke-width="2">
                      <path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z" />
                    </svg>
                  </button>

                  <button @click="selectedArticle = item"
                    class="rounded-lg p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors cursor-pointer"
                    title="Đọc toàn bài">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M5 12h14" />
                      <path d="m12 5 7 7-7 7" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </article>
        </div>

        <!-- Empty State -->
        <div v-else class="rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center my-6">
          <div class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 mb-4">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8" />
              <path d="m21 21-4.3-4.3" />
            </svg>
          </div>
          <h3 class="text-base font-bold text-slate-900">Không tìm thấy bài viết phù hợp</h3>
          <p class="mt-1 text-sm text-slate-500 max-w-sm mx-auto">
            Thử tìm kiếm với từ khóa khác hoặc chuyển sang chuyên mục khác để tiếp tục đọc.
          </p>
          <button @click="searchQuery = ''; activeCategory = 'Tất cả'; activeView = 'latest'"
            class="mt-5 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-indigo-500 transition-colors cursor-pointer">
            Quay lại trang chủ
          </button>
        </div>
      </section>

      <!-- FOOTER -->
      <footer
        class="mt-20 border-t border-slate-200 pt-8 pb-16 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-400">
        <div class="flex items-center gap-2">
          <span class="font-black text-slate-800">ReadsNews AI</span>
          <span>— Nền tảng đọc tin tức thông minh bằng giọng nói</span>
        </div>
        <div>
          <span>Thiết kế tối ưu cho trải nghiệm người dùng & đồ án công nghệ</span>
        </div>
      </footer>
    </main>

    <!-- 4. FLOATING DOCK AUDIO PLAYER (Hiển thị khi đang phát hoặc pause) -->
    <Transition name="slide-up">
      <div v-if="activePlayingArticle"
        class="fixed bottom-4 inset-x-4 sm:inset-x-auto sm:end-8 sm:w-[480px] z-50 rounded-2xl border border-slate-200/90 bg-white/95 backdrop-blur-xl shadow-2xl p-4 transition-all">
        <div class="flex items-center gap-3.5">
          <!-- Thumbnail -->
          <img :src="activePlayingArticle.image" :alt="activePlayingArticle.title"
            class="size-13 rounded-xl object-cover shrink-0 shadow-xs border border-slate-200" />

          <!-- Title & Meta -->
          <div class="min-w-0 flex-1">
            <div class="flex items-center gap-1.5 text-[10px] font-semibold text-indigo-600">
              <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
              <span>ĐANG PHÁT AI VOICE</span>
              <span class="text-slate-400">•</span>
              <span class="text-slate-500">{{ speechRate }}x</span>
            </div>
            <h4 class="text-xs font-bold text-slate-900 truncate mt-0.5">
              {{ activePlayingArticle.title }}
            </h4>
            <p class="text-[11px] text-slate-400 truncate">
              {{ activePlayingArticle.author }} · {{ activePlayingArticle.source }}
            </p>
          </div>

          <!-- Player Controls -->
          <div class="flex items-center gap-1 shrink-0">
            <!-- Play/Pause -->
            <button @click="toggleSpeech(activePlayingArticle)"
              class="flex size-10 items-center justify-center rounded-full bg-indigo-600 hover:bg-indigo-500 text-white shadow-md shadow-indigo-600/30 transition-all active:scale-95 cursor-pointer">
              <svg v-if="!isPaused" class="size-4.5" viewBox="0 0 24 24" fill="currentColor">
                <rect x="6" y="4" width="4" height="16" rx="1" />
                <rect x="14" y="4" width="4" height="16" rx="1" />
              </svg>
              <svg v-else class="size-4.5" viewBox="0 0 24 24" fill="currentColor">
                <polygon points="5 3 19 12 5 21 5 3" />
              </svg>
            </button>

            <!-- Close Player -->
            <button @click="stopSpeech"
              class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-colors cursor-pointer"
              title="Đóng trình phát">
              <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Mini Progress Bar -->
        <div class="mt-3">
          <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
            <div class="h-full bg-gradient-to-r from-indigo-500 to-emerald-500 transition-all duration-300 rounded-full"
              :style="{ width: `${playbackProgress}%` }"></div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- 5. ARTICLE DETAIL MODAL (MODAL ĐỌC BÀI KÈM TÓM TẮT AI) -->
    <Transition name="fade">
      <div v-if="selectedArticle"
        class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-950/70 p-4 sm:p-6 backdrop-blur-sm"
        @click.self="selectedArticle = null">
        <div
          class="relative max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-3xl bg-white shadow-2xl transition-all">

          <!-- Close Button -->
          <button @click="selectedArticle = null"
            class="absolute top-4 end-4 z-20 flex size-9 items-center justify-center rounded-full bg-slate-900/60 hover:bg-slate-900 text-white backdrop-blur-md transition-colors cursor-pointer">
            <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18" />
              <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
          </button>

          <!-- Modal Cover Image -->
          <div class="relative h-64 sm:h-80 w-full overflow-hidden bg-slate-900">
            <img :src="selectedArticle.image" :alt="selectedArticle.title"
              class="size-full object-cover object-center" />
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent"></div>

            <div class="absolute bottom-4 start-6 text-white text-xs font-semibold flex items-center gap-2">
              <span class="rounded-md bg-indigo-600 px-2.5 py-1">{{ selectedArticle.category }}</span>
              <span>{{ selectedArticle.source }}</span>
            </div>
          </div>

          <!-- Article Content Body -->
          <div class="p-6 sm:p-10">
            <div class="flex items-center gap-2 text-xs text-slate-400 mb-3">
              <span>Tác giả: <strong class="text-slate-700">{{ selectedArticle.author }}</strong></span>
              <span>•</span>
              <span>{{ selectedArticle.time }}</span>
              <span>•</span>
              <span>{{ selectedArticle.minutes }} phút đọc</span>
            </div>

            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 leading-tight">
              {{ selectedArticle.title }}
            </h2>

            <!-- AI Summary Box Spotlight -->
            <div
              class="mt-6 rounded-2xl border border-indigo-200 bg-gradient-to-br from-indigo-50/80 via-white to-violet-50/60 p-5 shadow-xs">
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2 text-indigo-700">
                  <!-- Sparkles AI Icon -->
                  <svg class="size-5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2l2.4 7.2L21.6 12l-7.2 2.4L12 21.6l-2.4-7.2L2.4 12l7.2-2.4L12 2z" />
                  </svg>
                  <span class="text-xs font-extrabold uppercase tracking-wider">Tóm tắt thông minh bởi AI</span>
                </div>
                <span class="text-[11px] font-semibold text-slate-400">Thời gian đọc ~30s</span>
              </div>
              <p class="whitespace-pre-line text-sm text-slate-700 leading-relaxed font-medium">
                {{ selectedArticle.aiSummary }}
              </p>
            </div>

            <!-- Listen Bar in Modal -->
            <div
              class="mt-6 flex flex-wrap items-center justify-between gap-3 p-4 rounded-xl bg-slate-50 border border-slate-100">
              <div class="flex items-center gap-3">
                <button @click="toggleSpeech(selectedArticle)"
                  class="flex size-11 items-center justify-center rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white shadow-md shadow-indigo-600/25 transition-transform active:scale-95 cursor-pointer">
                  <svg v-if="playingId === selectedArticle.id && !isPaused" class="size-5" viewBox="0 0 24 24"
                    fill="currentColor">
                    <rect x="6" y="4" width="4" height="16" rx="1" />
                    <rect x="14" y="4" width="4" height="16" rx="1" />
                  </svg>
                  <svg v-else class="size-5" viewBox="0 0 24 24" fill="currentColor">
                    <polygon points="5 3 19 12 5 21 5 3" />
                  </svg>
                </button>
                <div>
                  <p class="text-xs font-bold text-slate-900">
                    {{ playingId === selectedArticle.id && !isPaused ? 'Đang đọc thành tiếng...' : 'Nghe toàn bộ bài viết'
                    }}
                  </p>
                  <p class="text-[11px] text-slate-500">{{ selectedArticle.voice }}</p>
                </div>
              </div>

              <button @click="toggleSaved(selectedArticle)" :class="[
                'inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold border transition-colors cursor-pointer',
                savedIds.includes(selectedArticle.id)
                  ? 'border-amber-300 bg-amber-50 text-amber-700'
                  : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-100'
              ]">
                <svg class="size-4" viewBox="0 0 24 24"
                  :fill="savedIds.includes(selectedArticle.id) ? 'currentColor' : 'none'" stroke="currentColor"
                  stroke-width="2">
                  <path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z" />
                </svg>
                <span>{{ savedIds.includes(selectedArticle.id) ? 'Đã lưu' : 'Lưu bài' }}</span>
              </button>
            </div>

            <!-- Full Article Body -->
            <div class="mt-8 text-slate-700 leading-relaxed text-base sm:text-lg whitespace-pre-line space-y-4">
              {{ selectedArticle.body }}
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- 6. LOGOUT CONFIRM MODAL -->
    <Transition name="fade">
      <div v-if="showLogoutModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
        @click.self="showLogoutModal = false">
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl border border-slate-100 text-center">
          <div
            class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-rose-50 text-rose-500 ring-8 ring-rose-50/50">
            <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
              stroke-linecap="round" stroke-linejoin="round">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
              <polyline points="16 17 21 12 16 7" />
              <line x1="21" y1="12" x2="9" y2="12" />
            </svg>
          </div>
          <h3 class="font-display text-lg font-extrabold text-slate-900">Đăng xuất tài khoản?</h3>
          <p class="mt-1.5 text-xs leading-relaxed text-slate-500">
            Bạn có chắc muốn đăng xuất khỏi ReadsNews không?<br />
            Các bài viết đã lưu vẫn được giữ nguyên trên thiết bị này.
          </p>
          <div class="mt-5 flex items-center justify-center gap-3">
            <button @click="showLogoutModal = false"
              class="h-10 rounded-xl border border-slate-200 bg-white px-5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
              Ở lại
            </button>
            <button @click="confirmLogout"
              class="h-10 rounded-xl bg-rose-600 px-5 text-xs font-bold text-white shadow-md shadow-rose-600/25 hover:bg-rose-700 transition">
              Đăng xuất
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- 7. TOAST NOTIFICATION -->
    <Transition name="fade">
      <div v-if="notice"
        class="fixed bottom-6 start-6 z-50 flex items-center gap-2.5 rounded-xl border border-indigo-200 bg-white px-4 py-3 text-xs sm:text-sm font-semibold text-slate-800 shadow-xl">
        <span class="size-2 rounded-full bg-indigo-500 animate-ping"></span>
        <span>{{ notice }}</span>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
/* Smooth scroll and transitions */
.no-scrollbar::-webkit-scrollbar {
  display: none;
}

.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

/* Modal Transition */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Floating Dock Slide Up */
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
  transform: translateY(20px);
}
</style>
