<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'

const categories = ['Tất cả', 'Thời sự', 'Thế giới', 'Kinh doanh', 'Thể thao', 'Công nghệ']
const articles = [
    { id: 1, category: 'Thời sự', source: 'Nhịp sống Việt', time: '12 phút trước', minutes: 6, author: 'Minh Anh', views: 12540, title: 'Những thành phố đang thay đổi cách chúng ta di chuyển', summary: 'Từ giao thông công cộng đến những tuyến phố ưu tiên người đi bộ, nhiều đô thị đang thử nghiệm một nhịp sống ít phụ thuộc vào xe cá nhân hơn.', image: '/images/home/city.png', body: 'Các đô thị lớn đang nhìn lại cách không gian đường phố được phân chia. Thay vì dành phần lớn diện tích cho phương tiện cá nhân, nhiều kế hoạch mới ưu tiên kết nối giao thông công cộng, lối đi bộ và không gian xanh.\n\nSự chuyển dịch này không diễn ra trong một sớm một chiều. Những thay đổi nhỏ như mở rộng vỉa hè, tổ chức lại điểm trung chuyển và bổ sung dữ liệu giao thông theo thời gian thực đang giúp người dân có thêm lựa chọn cho hành trình hằng ngày.\n\nGiới quy hoạch cho rằng thành công của các thử nghiệm sẽ phụ thuộc vào khả năng kết nối giữa khu dân cư, nơi làm việc và dịch vụ thiết yếu.' },
    { id: 2, category: 'Công nghệ', source: 'Dữ liệu Mở', time: '35 phút trước', minutes: 4, author: 'Hoàng Long', views: 9360, title: 'Văn phòng linh hoạt đang định hình lại ngày làm việc', summary: 'Không gian làm việc mới kết hợp khu cộng tác, phòng yên tĩnh và lịch làm việc linh động để phù hợp với từng nhóm.', image: '/images/home/office.png', body: 'Nhiều tổ chức đang thiết kế lại văn phòng theo hướng linh hoạt hơn. Thay vì một chỗ ngồi cố định cho mọi nhân viên, không gian được chia theo nhu cầu: khu cộng tác, phòng tập trung và các điểm gặp nhanh.\n\nCách làm này đặt ra yêu cầu mới về công nghệ đặt chỗ, quyền riêng tư và thói quen phối hợp. Hiệu quả phụ thuộc nhiều vào quy ước làm việc rõ ràng, không chỉ vào thiết kế nội thất.' },
    { id: 3, category: 'Đời sống', source: 'Tạp chí Cuối Tuần', time: '1 giờ trước', minutes: 5, author: 'Thu Hà', views: 7210, title: 'Một khoảng nghỉ ngắn có thể giúp ngày làm việc nhẹ hơn', summary: 'Những cách đơn giản để tạo nhịp nghỉ hợp lý giữa lịch làm việc dày đặc.', image: '/images/home/work.png', body: 'Những khoảng nghỉ ngắn giúp nhiều người lấy lại sự tập trung sau các phiên làm việc liên tục. Một vòng đi bộ, vài phút rời màn hình hoặc một cuộc trò chuyện ngắn đều có thể tạo điểm ngắt cần thiết.\n\nĐiều quan trọng là lựa chọn cách nghỉ phù hợp với công việc và duy trì lịch nghỉ như một phần bình thường của ngày làm việc.' },
    { id: 4, category: 'Kinh doanh', source: 'Bản tin Thị trường', time: '2 giờ trước', minutes: 7, author: 'Quốc Bảo', views: 5120, title: 'Doanh nghiệp tìm hướng phát triển không gian xanh', summary: 'Nhu cầu tiết kiệm năng lượng và nâng chất lượng môi trường đang thúc đẩy những tiêu chuẩn mới cho công trình.', image: '/images/home/spaces.png', body: 'Các chủ đầu tư đang quan tâm nhiều hơn đến hiệu suất năng lượng và chất lượng môi trường bên trong công trình. Những tiêu chuẩn thiết kế mới khuyến khích tận dụng ánh sáng tự nhiên, lựa chọn vật liệu bền vững và theo dõi mức tiêu thụ năng lượng.\n\nBài toán nằm ở việc cân bằng chi phí ban đầu với hiệu quả vận hành trong dài hạn.' },
    { id: 5, category: 'Thế giới', source: 'Góc nhìn Toàn cầu', time: '3 giờ trước', minutes: 5, author: 'Hải Yến', views: 4380, title: 'Các đô thị ven biển chuẩn bị cho mùa mưa lớn', summary: 'Nhiều địa phương đang kết hợp dữ liệu thời tiết với quy hoạch hạ tầng để giảm rủi ro ngập lụt.', image: '/images/home/city.png', body: 'Các đô thị ven biển đang cập nhật kế hoạch ứng phó với những đợt mưa lớn và triều cường. Dữ liệu thời tiết theo khu vực giúp cơ quan quản lý chủ động điều phối giao thông, vận hành hệ thống thoát nước và thông tin đến cư dân.\n\nCác chuyên gia nhấn mạnh rằng hạ tầng xanh và quy hoạch dài hạn cần song hành với cảnh báo sớm.' },
]

const activeCategory = ref('Tất cả')
const activeView = ref('latest')
const searchQuery = ref('')
const savedIds = ref(readSavedIds())
const selectedArticle = ref(null)
const speechRate = ref(1)
const playingId = ref(null)
const notice = ref('')
const pageReady = ref(false)
let noticeTimer
const featuredArticle = articles[0]
const readerName = computed(() => localStorage.getItem('auth_user') ? JSON.parse(localStorage.getItem('auth_user')).name?.split(' ').at(-1) || 'bạn đọc' : 'bạn đọc')
const playerStatus = computed(() => playingId.value ? 'Đang đọc bài viết' : 'Sẵn sàng phát')
const feedTitle = computed(() => {
    if (activeView.value === 'saved') return 'Bài viết đã lưu'
    if (activeView.value === 'popular') return 'Được quan tâm'
    return activeCategory.value === 'Tất cả' ? 'Tin mới nhất' : activeCategory.value
})
const filteredArticles = computed(() => {
    let result = articles.filter((article) => article.id !== featuredArticle.id)
    if (activeView.value === 'saved') result = articles.filter((article) => savedIds.value.includes(article.id))
    if (activeCategory.value !== 'Tất cả') result = result.filter((article) => article.category === activeCategory.value)
    const query = searchQuery.value.trim().toLocaleLowerCase('vi')
    if (query) result = result.filter((article) => `${article.title} ${article.summary} ${article.source}`.toLocaleLowerCase('vi').includes(query))
    if (activeView.value === 'popular') result = [...result].sort((a, b) => b.views - a.views)
    return result
})

function readSavedIds() {
    try {
        return JSON.parse(localStorage.getItem('readsnews_saved') || '[]')
    } catch {
        return []
    }
}
watch(savedIds, (value) => localStorage.setItem('readsnews_saved', JSON.stringify(value)), { deep: true })
onMounted(() => requestAnimationFrame(() => { pageReady.value = true }))
function toggleSaved(article) {
    savedIds.value = savedIds.value.includes(article.id) ? savedIds.value.filter((id) => id !== article.id) : [...savedIds.value, article.id]
}
function setCategory(category) {
    activeCategory.value = category
    activeView.value = 'latest'
}
function notify(message) {
    notice.value = message
    window.clearTimeout(noticeTimer)
    noticeTimer = window.setTimeout(() => { notice.value = '' }, 2600)
}
function toggleSpeech(article) {
    if (!('speechSynthesis' in window)) return notify('Trình duyệt này chưa hỗ trợ đọc thành tiếng.')
    if (playingId.value === article.id) {
        window.speechSynthesis.cancel()
        playingId.value = null
        return
    }
    window.speechSynthesis.cancel()
    const utterance = new SpeechSynthesisUtterance(`${article.title}. ${article.summary}. ${article.body}`)
    utterance.lang = 'vi-VN'
    utterance.rate = speechRate.value
    utterance.onend = utterance.onerror = () => { playingId.value = null }
    playingId.value = article.id
    window.speechSynthesis.speak(utterance)
}
onBeforeUnmount(() => {
    if ('speechSynthesis' in window) window.speechSynthesis.cancel()
    window.clearTimeout(noticeTimer)
})
</script>

<template>
    <div :class="['min-h-screen bg-[#f4f6f2] text-gray-800 transition-transform duration-500 ease-out motion-reduce:transition-none', pageReady ? 'translate-y-0' : 'translate-y-2']">
        <header class="sticky top-0 z-30 border-b border-white/10 bg-forest-950 text-white shadow-sm">
            <div class="mx-auto flex min-h-[72px] max-w-[1500px] items-center gap-3 px-3 sm:gap-5 sm:px-6 xl:px-9">
                <a href="/"
                    class="flex shrink-0 items-center gap-2 font-display text-lg font-extrabold tracking-tight sm:text-xl">
                    <span class="grid size-9 place-items-center rounded-lg bg-citrus-300 text-forest-950"><svg
                            class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                            <path d="M5 19V9m7 10V5m7 14v-7" />
                        </svg></span>
                    Reads<span class="-ms-2 text-citrus-300">News</span>
                </a>
                <label
                    class="flex h-10 min-w-0 max-w-xl flex-1 items-center gap-2 rounded-lg border border-white/15 bg-white/10 px-3 text-white focus-within:border-citrus-300 focus-within:bg-white/15">
                    <svg class="size-4 shrink-0 text-white/55" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.8" stroke-linecap="round">
                        <circle cx="10.8" cy="10.8" r="6.5" />
                        <path d="m16 16 4 4" />
                    </svg>
                    <input v-model="searchQuery"
                        class="w-full border-0 bg-transparent p-0 text-sm text-white outline-none placeholder:text-white/50 focus:ring-0"
                        placeholder="Tìm bài viết..." aria-label="Tìm kiếm bài viết" />
                    <kbd
                        class="hidden rounded border border-white/20 px-1.5 py-0.5 text-xs text-white/50 sm:block">/</kbd>
                </label>
                <span class="ms-auto hidden shrink-0 text-sm text-white/70 xl:block">Xin chào, {{
                    readerName }}</span>
                <a href="/signin"
                    class="shrink-0 rounded-lg px-2 py-2 text-xs font-semibold text-white/80 transition hover:bg-white/10 hover:text-white sm:px-3 sm:text-sm">Đăng nhập</a>
                <a href="/admin"
                    class="shrink-0 rounded-lg border border-white/25 px-3 py-2 text-xs font-semibold text-white transition hover:bg-white/10 sm:px-4 sm:text-sm">Quản
                    trị</a>
            </div>
            <nav class="reader-nav-scroll mx-auto flex max-w-[1500px] items-center gap-1 overflow-x-auto border-t border-white/10 px-3 py-2 sm:px-6 xl:px-9"
                aria-label="Điều hướng tin tức">
                <button
                    :class="['shrink-0 rounded-md px-3 py-2 text-xs font-semibold transition sm:text-sm', activeView === 'latest' ? 'bg-white/15 text-white' : 'text-white/65 hover:bg-white/10 hover:text-white']"
                    @click="activeView = 'latest'; activeCategory = 'Tất cả'">Dành cho bạn</button>
                <button
                    :class="['shrink-0 rounded-md px-3 py-2 text-xs font-semibold transition sm:text-sm', activeView === 'popular' ? 'bg-white/15 text-white' : 'text-white/65 hover:bg-white/10 hover:text-white']"
                    @click="activeView = 'popular'; activeCategory = 'Tất cả'">Phổ biến</button>
                <button
                    :class="['shrink-0 rounded-md px-3 py-2 text-xs font-semibold transition sm:text-sm', activeView === 'saved' ? 'bg-white/15 text-white' : 'text-white/65 hover:bg-white/10 hover:text-white']"
                    @click="activeView = 'saved'; activeCategory = 'Tất cả'">Đã lưu <span
                        class="ms-1 text-citrus-200">{{ savedIds.length }}</span></button>
                <span class="mx-2 h-5 w-px shrink-0 bg-white/20" aria-hidden="true"></span>
                <button v-for="category in categories" :key="category"
                    :class="['shrink-0 rounded-md px-3 py-2 text-xs font-medium transition sm:text-sm', activeCategory === category ? 'bg-citrus-200 text-forest-950' : 'text-white/65 hover:bg-white/10 hover:text-white']"
                    @click="setCategory(category)">{{ category }}</button>
            </nav>
        </header>

        <div class="min-w-0">

            <main class="mx-auto max-w-[1500px] px-4 py-6 sm:px-7 sm:py-8 xl:px-9">
                <section class="mb-7 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p
                            class="mb-2 flex items-center gap-2 text-xs font-bold uppercase tracking-[.14em] text-forest-700">
                            <i class="size-2 rounded-full bg-citrus-500"></i>BẢN TIN CỦA BẠN
                        </p>
                        <h1 class="font-display text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Tin mới,
                            góc nhìn mới<span class="text-forest-500">.</span></h1>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-500">Những câu chuyện đáng đọc hôm nay,
                            được chọn lọc để bạn nắm bắt thế giới theo nhịp riêng.</p>
                    </div>
                    <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
                        <span
                            class="grid size-10 place-items-center rounded-lg bg-citrus-50 text-xl text-forest-700">♫</span><span><strong
                                class="block text-sm font-bold text-gray-800">Nghe tin theo cách bạn
                                thích</strong><small class="text-xs text-gray-500">Bật đọc thành tiếng trên mỗi bài
                                viết</small></span>
                    </div>
                </section>

                <section class="grid gap-4 xl:grid-cols-[minmax(0,1.65fr)_minmax(320px,.8fr)]"
                    aria-label="Tin nổi bật và danh sách nghe">
                    <article
                        class="relative flex min-h-[430px] overflow-hidden rounded-2xl bg-forest-950 text-white sm:min-h-[460px]">
                        <img class="absolute inset-0 size-full object-cover" :src="featuredArticle.image"
                            :alt="`Ảnh minh họa: ${featuredArticle.title}`" />
                        <div
                            class="absolute inset-0 bg-gradient-to-r from-forest-950/90 via-forest-950/65 to-forest-950/10">
                        </div>
                        <div class="relative z-10 flex max-w-2xl flex-col justify-end p-6 sm:p-9">
                            <div class="flex items-center gap-3 text-xs font-semibold text-white/75"><span
                                    class="rounded-md border border-white/30 bg-white/10 px-2.5 py-1.5 text-citrus-100">{{
                                        featuredArticle.category }}</span><span>{{ featuredArticle.time }}</span></div>
                            <h2 class="mt-5 max-w-xl font-display text-3xl font-bold leading-tight sm:text-4xl">{{
                                featuredArticle.title }}</h2>
                            <p class="mt-4 max-w-xl text-sm leading-6 text-white/80">{{ featuredArticle.summary }}</p>
                            <div class="mt-6 flex flex-wrap items-center gap-3"><button
                                    class="inline-flex h-11 items-center gap-3 rounded-lg bg-citrus-200 px-4 text-sm font-bold text-forest-950 transition hover:bg-citrus-100"
                                    @click="selectedArticle = featuredArticle">Đọc bài <span
                                        aria-hidden="true">↗</span></button><button
                                    class="grid size-11 place-items-center rounded-lg border border-white/35 bg-white/10 text-white transition hover:bg-white/20"
                                    :aria-label="playingId === featuredArticle.id ? 'Dừng đọc' : 'Nghe bài viết'"
                                    @click="toggleSpeech(featuredArticle)">{{ playingId === featuredArticle.id ? 'Ⅱ' :
                                        '▶' }}</button><button
                                    :class="['grid size-11 place-items-center rounded-lg border border-white/35 text-lg transition hover:bg-white/20', savedIds.includes(featuredArticle.id) ? 'bg-citrus-200 text-forest-950' : 'bg-white/10 text-white']"
                                    :aria-label="savedIds.includes(featuredArticle.id) ? 'Bỏ lưu bài viết' : 'Lưu bài viết'"
                                    @click="toggleSaved(featuredArticle)">♧</button><span
                                    class="ms-auto text-xs text-white/70">{{ featuredArticle.minutes }} phút đọc · {{
                                        featuredArticle.author }}</span></div>
                        </div><span
                            class="absolute end-5 top-5 rounded-full border border-white/20 bg-forest-950/40 px-3 py-1.5 text-xs font-semibold text-white/80">BÀI
                            NỔI BẬT</span>
                    </article>

                    <aside class="flex flex-col rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[.14em] text-forest-600">NGHE TIN</p>
                                <h2 class="mt-2 font-display text-xl font-bold text-gray-900">Vừa đủ để cập nhật</h2>
                            </div><span class="flex h-8 items-center gap-1" aria-hidden="true"><i
                                    class="h-2 w-1 rounded-full bg-citrus-400"></i><i
                                    class="h-4 w-1 rounded-full bg-citrus-500"></i><i
                                    class="h-6 w-1 rounded-full bg-forest-500"></i><i
                                    class="h-4 w-1 rounded-full bg-citrus-500"></i><i
                                    class="h-2 w-1 rounded-full bg-citrus-400"></i></span>
                        </div>
                        <p class="mt-3 text-sm leading-6 text-gray-500">Chọn tốc độ đọc phù hợp, để ReadsNews kể bạn
                            nghe.</p>
                        <div class="mt-4 flex items-center justify-between border-y border-gray-100 py-3"><span
                                class="text-sm text-gray-600">Tốc độ đọc</span>
                            <div class="flex rounded-lg bg-gray-100 p-1" role="group" aria-label="Tốc độ đọc"><button
                                    v-for="speed in [0.8, 1, 1.2]" :key="speed"
                                    :class="['rounded-md px-2.5 py-1.5 text-xs font-semibold', speechRate === speed ? 'bg-white text-forest-800 shadow-sm' : 'text-gray-500']"
                                    @click="speechRate = speed">{{ speed }}x</button></div>
                        </div>
                        <p class="mb-2 mt-5 text-xs font-bold uppercase tracking-wider text-gray-400">Gợi ý tiếp theo
                            <span class="float-end">03 BÀI</span>
                        </p><button v-for="article in articles.slice(1, 4)" :key="article.id"
                            class="flex min-h-[68px] items-center gap-3 border-b border-gray-100 text-start last:border-0"
                            @click="toggleSpeech(article)"><span
                                class="grid size-9 shrink-0 place-items-center rounded-full border border-gray-200 text-xs text-forest-700">{{
                                    playingId === article.id ? 'Ⅱ' : '▶' }}</span><span class="min-w-0"><strong
                                    class="block line-clamp-2 text-sm font-semibold leading-5 text-gray-800">{{
                                        article.title }}</strong><small class="mt-1 block text-xs text-gray-500">{{
                                        article.source }} · {{ article.minutes }} phút</small></span></button>
                        <div class="mt-auto pt-3">
                            <div class="h-1.5 overflow-hidden rounded-full bg-gray-100"><span
                                    class="block h-full rounded-full bg-forest-500 transition-all"
                                    :class="playingId ? 'w-2/5' : 'w-[8%]'"></span></div>
                            <p class="mt-2 flex justify-between text-xs text-gray-500"><span>{{ playerStatus
                                    }}</span><span>{{ speechRate }}x</span></p>
                        </div>
                    </aside>
                </section>

                <section class="mt-10">
                    <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[.14em] text-forest-600">ĐỌC THEO NHỊP CỦA
                                BẠN</p>
                            <h2 class="mt-2 font-display text-2xl font-bold text-gray-900">{{ feedTitle }}</h2>
                        </div>
                        <div class="flex gap-2"><button
                                :class="['rounded-lg px-4 py-2.5 text-sm font-semibold transition', activeView === 'latest' ? 'bg-forest-900 text-white' : 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50']"
                                @click="activeView = 'latest'">Mới nhất</button><button
                                :class="['rounded-lg px-4 py-2.5 text-sm font-semibold transition', activeView === 'popular' ? 'bg-forest-900 text-white' : 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50']"
                                @click="activeView = 'popular'">Phổ biến</button></div>
                    </div>
                    <div class="mb-5 hidden flex-wrap gap-2 lg:flex"><button v-for="category in categories"
                            :key="category"
                            :class="['rounded-full px-4 py-2 text-sm font-medium transition', activeCategory === category ? 'bg-forest-100 text-forest-900' : 'bg-white text-gray-600 hover:bg-gray-100']"
                            @click="setCategory(category)">{{ category }}</button></div>
                    <div v-if="filteredArticles.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        <article v-for="article in filteredArticles" :key="article.id"
                            class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                            <button class="group relative block h-48 w-full overflow-hidden bg-forest-100 text-start"
                                :aria-label="`Đọc bài: ${article.title}`" @click="selectedArticle = article"><img
                                    class="size-full object-cover transition duration-500 group-hover:scale-105"
                                    :src="article.image" :alt="`Ảnh minh họa: ${article.title}`" loading="lazy" /><span
                                    class="absolute start-3 top-3 rounded-md bg-white/95 px-3 py-1.5 text-xs font-semibold text-forest-800">{{
                                        article.category }}</span></button>
                            <div class="p-5">
                                <div class="flex items-center justify-between gap-3 text-xs text-gray-500"><strong
                                        class="truncate font-semibold text-forest-700">{{ article.source
                                        }}</strong><span class="shrink-0">{{ article.time }}</span></div><button
                                    class="mt-3 line-clamp-2 text-start font-display text-lg font-bold leading-6 text-gray-900 hover:text-forest-700"
                                    @click="selectedArticle = article">{{ article.title }}</button>
                                <p class="mt-2 line-clamp-3 text-sm leading-6 text-gray-500">{{ article.summary }}</p>
                                <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-3"><span
                                        class="text-xs text-gray-500">{{ article.minutes }} phút đọc <i
                                            class="mx-1 inline-block size-1 rounded-full bg-gray-300"></i>{{
                                                article.author }}</span>
                                    <div class="flex gap-2"><button
                                            class="grid size-9 place-items-center rounded-lg border border-gray-200 text-sm text-forest-700 transition hover:bg-forest-50"
                                            :aria-label="playingId === article.id ? 'Dừng đọc' : 'Nghe bài viết'"
                                            @click="toggleSpeech(article)">{{ playingId === article.id ? 'Ⅱ' : '▶'
                                            }}</button><button
                                            :class="['grid size-9 place-items-center rounded-lg border text-lg transition', savedIds.includes(article.id) ? 'border-citrus-300 bg-citrus-50 text-forest-700' : 'border-gray-200 text-gray-500 hover:bg-gray-50']"
                                            :aria-label="savedIds.includes(article.id) ? 'Bỏ lưu bài viết' : 'Lưu bài viết'"
                                            @click="toggleSaved(article)">♧</button></div>
                                </div>
                            </div>
                        </article>
                    </div>
                    <div v-else
                        class="grid min-h-64 place-content-center justify-items-center rounded-xl border border-dashed border-gray-300 bg-white text-center">
                        <span class="text-3xl text-forest-400">⌕</span>
                        <h3 class="mt-3 font-display text-lg font-bold text-gray-800">Không tìm thấy bài viết</h3>
                        <p class="mt-1 text-sm text-gray-500">Hãy thử từ khóa hoặc chủ đề khác.</p><button
                            class="mt-4 rounded-lg bg-forest-900 px-4 py-2.5 text-sm font-semibold text-white"
                            @click="searchQuery = ''; activeCategory = 'Tất cả'; activeView = 'latest'">Xem tất cả
                            tin</button>
                    </div>
                </section>
                <footer
                    class="mt-12 flex flex-wrap justify-between gap-3 border-t border-gray-200 py-5 text-xs text-gray-500">
                    <span class="font-bold tracking-wider text-forest-800">READSNEWS · TIN TỨC THEO CÁCH CỦA
                        BẠN</span><span>Đọc chậm lại, hiểu nhiều hơn.</span>
                </footer>
            </main>
        </div>

        <Transition name="reader-modal">
            <div v-if="selectedArticle"
                class="fixed inset-0 z-50 grid place-items-center overflow-y-auto bg-gray-950/60 p-0 backdrop-blur-sm sm:p-5"
                @click.self="selectedArticle = null">
                <article
                    class="relative max-h-screen w-full overflow-y-auto bg-white shadow-2xl sm:max-h-[92vh] sm:max-w-3xl sm:rounded-2xl"
                    role="dialog" aria-modal="true" :aria-label="selectedArticle.title"><button
                        class="absolute end-4 top-4 z-10 grid size-10 place-items-center rounded-full bg-gray-950/60 text-2xl text-white"
                        aria-label="Đóng bài viết" @click="selectedArticle = null">×</button><img
                        class="h-56 w-full object-cover sm:h-72" :src="selectedArticle.image"
                        :alt="`Ảnh minh họa: ${selectedArticle.title}`" />
                    <div class="px-5 py-7 sm:px-10 sm:py-9">
                        <p class="text-sm font-bold text-forest-700">{{ selectedArticle.category }} <span
                                class="px-2 text-gray-300">·</span>{{ selectedArticle.source }}</p>
                        <h2 class="mt-3 font-display text-3xl font-bold leading-tight text-gray-900">{{
                            selectedArticle.title }}</h2>
                        <p class="mt-4 text-base leading-7 text-gray-600">{{ selectedArticle.summary }}</p>
                        <p class="mt-4 border-y border-gray-100 py-3 text-sm text-gray-500">{{ selectedArticle.author }}
                            · {{ selectedArticle.time }} · {{ selectedArticle.minutes }} phút đọc</p>
                        <div class="article-body mt-5 whitespace-pre-line text-base leading-8 text-gray-700">{{
                            selectedArticle.body }}</div><button
                            class="mt-6 inline-flex h-11 items-center gap-2 rounded-lg bg-forest-900 px-4 text-sm font-semibold text-white hover:bg-forest-800"
                            @click="toggleSpeech(selectedArticle)">{{ playingId === selectedArticle.id ? 'Ⅱ Dừng nghe' :
                                '▶ Nghe bài viết' }}</button>
                    </div>
                </article>
            </div>
        </Transition>
        <Transition name="reader-toast">
            <div v-if="notice"
                class="fixed bottom-5 end-5 z-[60] rounded-lg border border-forest-200 bg-white px-4 py-3 text-sm font-medium text-forest-800 shadow-lg"
                role="status">{{ notice }}</div>
        </Transition>
    </div>
</template>
