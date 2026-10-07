<script setup>
import { computed, onBeforeUnmount, ref } from 'vue'

const sections = [
    { id: 'overview', label: 'Tổng quan' },
    { id: 'articles', label: 'Bài báo' },
    { id: 'categories', label: 'Danh mục' },
    { id: 'users', label: 'Người dùng' },
]
const activeSection = ref('overview')
const search = ref('')
const selectedCategory = ref('all')
const categoryModal = ref(false)
const articleModal = ref(false)
const deleteTarget = ref(null)
const editingId = ref(null)
const flashMessage = ref('')
let flashTimer

const categories = ref([
    { id: 1, parent_id: null, name: 'Tin mới nhất', slug: 'tin-moi-nhat', description: 'Các tin tức mới cập nhật', sort_order: 1, is_active: true },
    { id: 2, parent_id: null, name: 'Thế giới', slug: 'the-gioi', description: 'Tin tức quốc tế và khu vực', sort_order: 2, is_active: true },
    { id: 3, parent_id: null, name: 'Kinh doanh', slug: 'kinh-doanh', description: 'Thị trường, tài chính và doanh nghiệp', sort_order: 3, is_active: true },
    { id: 4, parent_id: null, name: 'Thể thao', slug: 'the-thao', description: 'Các giải đấu và câu chuyện thể thao', sort_order: 4, is_active: true },
    { id: 5, parent_id: null, name: 'Công nghệ', slug: 'cong-nghe', description: 'Sản phẩm, khoa học và công nghệ', sort_order: 5, is_active: false },
])

const sources = [
    { id: 1, name: 'VnExpress' },
    { id: 2, name: 'Tuổi Trẻ' },
]

const users = ref([
    { id: 1, name: 'Nguyễn Minh Anh', email: 'minhanh@gmail.com', role: 'admin', is_active: true, created_at: '2026-10-02T09:12:00' },
    { id: 2, name: 'Trần Quốc Bảo', email: 'bao.tran@gmail.com', role: 'editor', is_active: true, created_at: '2026-09-29T14:25:00' },
    { id: 3, name: 'Lê Thu Hà', email: 'thuha.le@gmail.com', role: 'user', is_active: true, created_at: '2026-09-27T10:48:00' },
    { id: 4, name: 'Phạm Hoàng Long', email: 'long.pham@gmail.com', role: 'user', is_active: false, created_at: '2026-09-23T16:05:00' },
    { id: 5, name: 'Đỗ Hải Yến', email: 'yen.do@gmail.com', role: 'editor', is_active: true, created_at: '2026-09-20T08:40:00' },
    { id: 6, name: 'Vũ Đức Thành', email: 'thanh.vu@gmail.com', role: 'user', is_active: true, created_at: '2026-09-18T11:30:00' },
])

const articles = ref([
    { id: 1, source_id: 1, category_id: 2, title: 'Những thành phố đang thay đổi cách chúng ta di chuyển', slug: 'thanh-pho-thay-doi-cach-di-chuyen', summary: 'Các đô thị đang ưu tiên giao thông công cộng và không gian dành cho người đi bộ.', ai_summary: 'Đô thị chuyển hướng đến giao thông công cộng và không gian xanh.', content: 'Các đô thị lớn đang nhìn lại cách không gian đường phố được phân chia...', content_tts: 'Các đô thị lớn đang nhìn lại cách không gian đường phố được phân chia.', thumbnail: '', original_url: 'https://vnexpress.net/do-thi-di-chuyen', url_hash: 'a2a8de45d1079e72c7cb8214334a8b01c8c57f432a75e5e1d1a2563a660fb102', author: 'Minh Anh', word_count: 1280, view_count: 12540, listen_count: 4610, published_at: '2026-10-07T08:42:00' },
    { id: 2, source_id: 2, category_id: 5, title: 'Văn phòng linh hoạt đang định hình lại ngày làm việc', slug: 'van-phong-linh-hoat-ngay-lam-viec', summary: 'Không gian làm việc mới kết hợp khu cộng tác, phòng yên tĩnh và lịch linh động.', ai_summary: 'Mô hình văn phòng mới cân bằng cộng tác với tập trung.', content: 'Nhiều tổ chức đang thiết kế lại văn phòng theo hướng linh hoạt hơn...', content_tts: 'Nhiều tổ chức đang thiết kế lại văn phòng theo hướng linh hoạt hơn.', thumbnail: '', original_url: 'https://tuoitre.vn/van-phong-linh-hoat', url_hash: 'be2310093d047c84cb05bd3dc97b8d3b08dab3bd94e4a99c570bf34ef1b30c12', author: 'Hoàng Long', word_count: 970, view_count: 9360, listen_count: 3270, published_at: '2026-10-07T08:14:00' },
    { id: 3, source_id: 1, category_id: 3, title: 'Doanh nghiệp tìm hướng phát triển không gian xanh', slug: 'doanh-nghiep-phat-trien-khong-gian-xanh', summary: 'Nhu cầu tiết kiệm năng lượng thúc đẩy tiêu chuẩn mới cho các công trình.', ai_summary: 'Tiêu chuẩn công trình xanh hướng tới hiệu quả vận hành dài hạn.', content: 'Các chủ đầu tư đang quan tâm nhiều hơn đến hiệu suất năng lượng...', content_tts: '', thumbnail: '', original_url: 'https://vnexpress.net/cong-trinh-xanh', url_hash: '60bf18986574952831b38ea40107fd42c84ea3a0de832551f3b7331eac6d3157', author: 'Quốc Bảo', word_count: 1425, view_count: 7210, listen_count: 1860, published_at: '2026-10-06T17:35:00' },
    { id: 4, source_id: 2, category_id: 4, title: 'Một mùa giải mới mở ra nhiều cơ hội cho cầu thủ trẻ', slug: 'mua-giai-moi-cau-thu-tre', summary: 'Các đội bóng đang trao thêm cơ hội cho những gương mặt trẻ ở mùa giải năm nay.', ai_summary: '', content: 'Mùa giải mới chứng kiến nhiều thay đổi trong đội hình...', content_tts: '', thumbnail: '', original_url: 'https://tuoitre.vn/mua-giai-moi', url_hash: 'f9292464a1734d1294f81880ad87167baea6b6739c4aaac59f6017f150eecfa2', author: 'Hải Yến', word_count: 830, view_count: 5120, listen_count: 940, published_at: null },
    { id: 5, source_id: 1, category_id: 2, title: 'Những thay đổi đáng chú ý trong chính sách năng lượng', slug: 'chinh-sach-nang-luong-moi', summary: 'Bản dự thảo mới đặt trọng tâm vào nguồn cung ổn định và chuyển dịch năng lượng.', ai_summary: '', content: 'Cơ quan quản lý vừa công bố định hướng cập nhật...', content_tts: '', thumbnail: '', original_url: 'https://vnexpress.net/chinh-sach-nang-luong', url_hash: '4071994e22bc8ccf85c8f52586835a13cf45a4cd11aa8b8d5c3da4d5ad613bab', author: 'Thu Hà', word_count: 0, view_count: 0, listen_count: 0, published_at: null },
])

const categoryForm = ref(emptyCategory())
const articleForm = ref(emptyArticle())
const titleMap = { overview: 'Tổng quan', articles: 'Quản lý bài báo', categories: 'Quản lý danh mục', users: 'Người dùng' }
const pageTitle = computed(() => titleMap[activeSection.value])
const categoryFormTitle = computed(() => editingId.value ? 'Sửa danh mục' : 'Thêm danh mục')
const articleFormTitle = computed(() => editingId.value ? 'Sửa bài báo' : 'Thêm bài báo')
const sectionDescription = computed(() => {
    if (activeSection.value === 'overview') return 'Đây là tình hình hoạt động của ReadsNews hôm nay.'
    const sectionName = activeSection.value === 'users'
        ? 'tài khoản'
        : activeSection.value === 'articles'
            ? 'nội dung bài báo'
            : 'chủ đề tin tức'
    return `Theo dõi và quản lý ${sectionName} của bạn.`
})
const totalViews = computed(() => articles.value.reduce((sum, article) => sum + Number(article.view_count || 0), 0))
const totalListens = computed(() => articles.value.reduce((sum, article) => sum + Number(article.listen_count || 0), 0))
const publishedCount = computed(() => articles.value.filter((article) => article.published_at).length)
const activeUserCount = computed(() => users.value.filter((user) => user.is_active).length)

const filteredArticles = computed(() => articles.value.filter((article) => {
    const matchesCategory = selectedCategory.value === 'all' || article.category_id === Number(selectedCategory.value)
    const term = search.value.trim().toLocaleLowerCase('vi')
    const matchesSearch = !term || `${article.title} ${article.author || ''} ${sourceName(article.source_id)}`.toLocaleLowerCase('vi').includes(term)
    return matchesCategory && matchesSearch
}))
const filteredCategories = computed(() => {
    const term = search.value.trim().toLocaleLowerCase('vi')
    return categories.value.filter((category) => !term || `${category.name} ${category.slug} ${category.description || ''}`.toLocaleLowerCase('vi').includes(term))
})
const filteredUsers = computed(() => {
    const term = search.value.trim().toLocaleLowerCase('vi')
    return users.value.filter((user) => !term || `${user.name} ${user.email} ${user.role}`.toLocaleLowerCase('vi').includes(term))
})

function emptyCategory() {
    return { parent_id: '', name: '', slug: '', description: '', sort_order: categories.value?.length + 1 || 1, is_active: true }
}
function emptyArticle() {
    return { source_id: '', category_id: '', title: '', slug: '', summary: '', ai_summary: '', content: '', content_tts: '', thumbnail: '', original_url: '', url_hash: '', author: '', word_count: 0, view_count: 0, listen_count: 0, published_at: '' }
}
function makeSlug(value) {
    return value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/đ/g, 'd').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')
}
function formatNumber(value) {
    return new Intl.NumberFormat('vi-VN').format(value || 0)
}
function formatDate(value) {
    if (!value) return 'Bản nháp'
    return new Intl.DateTimeFormat('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' }).format(new Date(value))
}
function sourceName(id) {
    return sources.find((source) => source.id === Number(id))?.name || 'Chưa chọn nguồn'
}
function categoryName(id) {
    return categories.value.find((category) => category.id === Number(id))?.name || 'Chưa phân loại'
}
function categoryCount(id) {
    return articles.value.filter((article) => article.category_id === id).length
}
function notify(message) {
    flashMessage.value = message
    window.clearTimeout(flashTimer)
    flashTimer = window.setTimeout(() => { flashMessage.value = '' }, 2800)
}
function setSection(section) {
    activeSection.value = section
    search.value = ''
    selectedCategory.value = 'all'
}
function openCategoryForm(category = null) {
    editingId.value = category?.id ?? null
    categoryForm.value = category ? { ...category, parent_id: category.parent_id ?? '' } : emptyCategory()
    categoryModal.value = true
}
function saveCategory() {
    const record = { ...categoryForm.value, parent_id: categoryForm.value.parent_id ? Number(categoryForm.value.parent_id) : null, sort_order: Number(categoryForm.value.sort_order) }
    record.slug = record.slug.trim() || makeSlug(record.name)
    if (categories.value.some((item) => item.slug === record.slug && item.id !== editingId.value)) {
        notify('Slug danh mục đã tồn tại. Hãy chọn slug khác.')
        return
    }
    if (editingId.value) {
        categories.value = categories.value.map((item) => item.id === editingId.value ? { ...record, id: item.id } : item)
        notify('Đã cập nhật danh mục trong bản xem trước.')
    } else {
        categories.value.push({ ...record, id: Math.max(0, ...categories.value.map((item) => item.id)) + 1 })
        notify('Đã thêm danh mục trong bản xem trước.')
    }
    categoryModal.value = false
}
function openArticleForm(article = null) {
    editingId.value = article?.id ?? null
    articleForm.value = article ? { ...article, source_id: article.source_id ?? '', category_id: article.category_id ?? '', published_at: article.published_at ? article.published_at.slice(0, 16) : '' } : emptyArticle()
    articleModal.value = true
}
async function hashUrl(value) {
    if (!value || !window.crypto?.subtle) return Array.from({ length: 64 }, () => Math.floor(Math.random() * 16).toString(16)).join('')
    const digest = await window.crypto.subtle.digest('SHA-256', new TextEncoder().encode(value.trim()))
    return [...new Uint8Array(digest)].map((byte) => byte.toString(16).padStart(2, '0')).join('')
}
async function saveArticle() {
    const record = { ...articleForm.value, source_id: articleForm.value.source_id ? Number(articleForm.value.source_id) : null, category_id: articleForm.value.category_id ? Number(articleForm.value.category_id) : null, published_at: articleForm.value.published_at || null }
    record.slug = record.slug.trim() || makeSlug(record.title)
    record.url_hash = record.url_hash || await hashUrl(record.original_url)
    if (articles.value.some((item) => item.id !== editingId.value && (item.slug === record.slug || item.original_url === record.original_url || item.url_hash === record.url_hash))) {
        notify('Slug hoặc URL bài báo đã tồn tại. Hãy kiểm tra lại thông tin.')
        return
    }
    record.word_count = Number(record.word_count) || (record.content_tts || record.content || '').trim().split(/\s+/).filter(Boolean).length
    if (editingId.value) {
        articles.value = articles.value.map((item) => item.id === editingId.value ? { ...record, id: item.id } : item)
        notify('Đã cập nhật bài báo trong bản xem trước.')
    } else {
        articles.value.unshift({ ...record, id: Math.max(0, ...articles.value.map((item) => item.id)) + 1, view_count: 0, listen_count: 0 })
        notify('Đã thêm bài báo trong bản xem trước.')
    }
    articleModal.value = false
}
function askDelete(type, record) {
    deleteTarget.value = { type, record }
}
function confirmDelete() {
    const { type, record } = deleteTarget.value
    if (type === 'article') {
        articles.value = articles.value.filter((article) => article.id !== record.id)
    } else {
        categories.value = categories.value.filter((category) => category.id !== record.id)
        categories.value = categories.value.map((category) => category.parent_id === record.id ? { ...category, parent_id: null } : category)
        articles.value = articles.value.map((article) => article.category_id === record.id ? { ...article, category_id: null } : article)
    }
    deleteTarget.value = null
    notify(type === 'article' ? 'Đã xóa bài báo khỏi bản xem trước.' : 'Đã xóa danh mục khỏi bản xem trước.')
}
function statusClasses(isActive) {
    return isActive ? 'bg-forest-50 text-forest-700 ring-1 ring-inset ring-forest-200' : 'bg-gray-100 text-gray-600 ring-1 ring-inset ring-gray-200'
}
function roleClasses(role) {
    if (role === 'admin') return 'bg-citrus-100 text-citrus-900'
    if (role === 'editor') return 'bg-sky-50 text-sky-700'
    return 'bg-gray-100 text-gray-600'
}

onBeforeUnmount(() => window.clearTimeout(flashTimer))
</script>

<template>
    <div class="min-h-screen bg-[#f4f6f2] text-gray-800">
        <aside class="fixed inset-y-0 start-0 z-30 hidden w-[248px] flex-col bg-forest-950 text-white lg:flex">
            <a  class="flex h-[82px] items-center gap-3 border-b border-white/10 px-7 font-display text-xl font-extrabold tracking-tight"
                href="#overview" @click.prevent="setSection('overview')">
                <span class="grid size-9 place-items-center rounded-lg bg-citrus-300 text-forest-950"><svg
                        class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <path d="M5 19V9m7 10V5m7 14v-7" />
                    </svg></span>
                Reads<span class="-ms-3 text-citrus-300">News</span>
            </a>
            <div class="px-4 pt-7">
                <p class="px-3 pb-3 text-[10px] font-bold uppercase tracking-[.16em] text-white/40">Quản trị nội dung
                </p>
                <nav class="space-y-1" aria-label="Điều hướng quản trị">
                    <button v-for="section in sections" :key="section.id"
                        :class="['group flex min-h-11 w-full items-center gap-3 rounded-lg px-3 text-start text-sm transition', activeSection === section.id ? 'bg-white/10 text-white shadow-sm' : 'text-white/65 hover:bg-white/5 hover:text-white']"
                        @click="setSection(section.id)">
                        <svg class="size-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <template v-if="section.id === 'overview'">
                                <rect x="3.5" y="3.5" width="7" height="7" rx="1.5" />
                                <rect x="13.5" y="3.5" width="7" height="7" rx="1.5" />
                                <rect x="3.5" y="13.5" width="7" height="7" rx="1.5" />
                                <rect x="13.5" y="13.5" width="7" height="7" rx="1.5" />
                            </template>
                            <template v-else-if="section.id === 'articles'">
                                <path
                                    d="M5 4.5h14a1.5 1.5 0 0 1 1.5 1.5v12a1.5 1.5 0 0 1-1.5 1.5H5A1.5 1.5 0 0 1 3.5 18V6A1.5 1.5 0 0 1 5 4.5Z" />
                                <path d="M7.5 8h9m-9 4h9m-9 4h5" />
                            </template>
                            <template v-else-if="section.id === 'categories'">
                                <path d="M4 5.5h6l2 2h8v11H4z" />
                                <path d="M4 8h16" />
                            </template>
                            <template v-else>
                                <circle cx="9" cy="8" r="3.2" />
                                <path
                                    d="M3.5 19c.3-3 2.4-4.8 5.5-4.8s5.2 1.8 5.5 4.8M16 5.5a3 3 0 0 1 0 5.8m2 3c1.7.8 2.5 2.2 2.7 4.7" />
                            </template>
                        </svg>
                        <span class="flex-1">{{ section.label }}</span>
                        <span v-if="section.id === 'articles'"
                            class="rounded bg-white/10 px-1.5 py-0.5 text-[10px] text-white/70">{{
                                articles.length }}</span>
                    </button>
                </nav>
            </div>
            <div class="mt-auto border-t border-white/10 p-4">
                <div class="flex items-center gap-3 rounded-lg bg-white/5 p-3">
                    <span
                        class="grid size-9 shrink-0 place-items-center rounded-full bg-citrus-200 font-bold text-forest-950">MA</span>
                    <span class="min-w-0 flex-1"><strong class="block truncate text-xs font-semibold">Minh
                            Anh</strong><small class="mt-0.5 block text-[10px] text-white/45">Quản trị
                            viên</small></span>
                    <button
                        class="grid size-8 place-items-center rounded-md text-white/50 hover:bg-white/10 hover:text-white"
                        aria-label="Tùy chọn tài khoản"><svg class="size-4" viewBox="0 0 24 24" fill="currentColor">
                            <circle cx="5" cy="12" r="1.5" />
                            <circle cx="12" cy="12" r="1.5" />
                            <circle cx="19" cy="12" r="1.5" />
                        </svg></button>
                </div>
            </div>
        </aside>

        <div class="lg:ps-[248px]">
            <header class="sticky top-0 z-20 border-b border-gray-200/80 bg-white/95 backdrop-blur">
                <div class="flex h-[68px] items-center justify-between gap-3 px-4 sm:px-7 xl:px-9">
                    <div class="flex min-w-0 items-center gap-3">
                        <span
                            class="grid size-9 shrink-0 place-items-center rounded-lg bg-forest-900 text-citrus-200 lg:hidden"><svg
                                class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M5 19V9m7 10V5m7 14v-7" />
                            </svg></span>
                        <div class="min-w-0">
                            <p class="truncate text-[10px] font-semibold uppercase tracking-[.12em] text-gray-400">
                                ReadsNews /
                                Quản trị · Bản xem trước</p>
                            <h1 class="truncate font-display text-base font-bold text-gray-800 sm:text-lg">{{ pageTitle
                            }}</h1>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 sm:gap-4">
                        <label
                            class="hidden h-9 w-52 items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 focus-within:border-forest-400 focus-within:bg-white md:flex xl:w-64">
                            <svg class="size-4 shrink-0 text-gray-400" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                                <circle cx="10.8" cy="10.8" r="6.5" />
                                <path d="m16 16 4 4" />
                            </svg>
                            <input v-model="search"
                                class="w-full border-0 bg-transparent p-0 text-xs outline-none placeholder:text-gray-400 focus:ring-0"
                                placeholder="Tìm kiếm..." aria-label="Tìm kiếm" />
                            <kbd class="rounded border border-gray-200 px-1.5 py-0.5 text-[9px] text-gray-400">/</kbd>
                        </label>
                        <button
                            class="relative grid size-9 place-items-center rounded-lg border border-gray-200 text-gray-500 transition hover:bg-gray-50"
                            aria-label="Thông báo"><svg class="size-[17px]" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" />
                            </svg><i
                                class="absolute end-2 top-2 size-1.5 rounded-full bg-orange-500 ring-2 ring-white"></i></button>
                        <span class="hidden h-8 w-px bg-gray-200 sm:block"></span>
                        <span class="hidden text-end sm:block"><strong class="block text-xs font-semibold">Minh
                                Anh</strong><small class="text-[10px] text-gray-400">Quản trị viên</small></span>
                        <span
                            class="grid size-9 place-items-center rounded-full bg-citrus-100 text-xs font-bold text-forest-900 sm:hidden">MA</span>
                    </div>
                </div>
                <nav class="flex gap-1 overflow-x-auto border-t border-gray-100 px-3 py-2 lg:hidden"
                    aria-label="Điều hướng quản trị mobile">
                    <button v-for="section in sections" :key="section.id"
                        :class="['shrink-0 rounded-md px-3 py-1.5 text-xs font-medium', activeSection === section.id ? 'bg-forest-50 text-forest-800' : 'text-gray-500 hover:bg-gray-50']"
                        @click="setSection(section.id)">{{ section.label }}</button>
                </nav>
            </header>

            <main class="mx-auto max-w-[1500px] px-4 py-6 sm:px-7 sm:py-8 xl:px-9">
                <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="mb-1 text-xs text-gray-500">Thứ Tư, 07 tháng 10, 2026</p>
                        <h2 class="font-display text-2xl font-bold tracking-tight text-gray-900">{{ activeSection ===
                            'overview'
                            ? 'Xin chào, Minh Anh' : pageTitle }}<span class="text-forest-500">.</span></h2>
                        <p class="mt-1 text-sm text-gray-500">{{ sectionDescription }}</p>
                    </div>
                    <button v-if="activeSection === 'articles' || activeSection === 'categories'"
                        class="inline-flex h-10 items-center gap-2 rounded-lg bg-forest-800 px-4 text-xs font-semibold text-white shadow-sm transition hover:bg-forest-700"
                        @click="activeSection === 'articles' ? openArticleForm() : openCategoryForm()"><svg
                            class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round">
                            <path d="M12 5v14M5 12h14" />
                        </svg>{{ activeSection === 'articles' ? 'Thêm bài báo' : 'Thêm danh mục' }}</button>
                </div>

                <template v-if="activeSection === 'overview'">
                    <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4"
                        aria-label="Thống kê tổng quan">
                        <article
                            class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm shadow-gray-900/[.02] sm:p-5">
                            <div class="flex items-start justify-between"><span
                                    class="text-xs font-medium text-gray-500">Tổng người dùng</span><span
                                    class="grid size-9 place-items-center rounded-lg bg-sky-50 text-sky-700"><svg
                                        class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="1.7">
                                        <circle cx="9" cy="8" r="3" />
                                        <path
                                            d="M3.5 19c.3-3 2.4-4.8 5.5-4.8s5.2 1.8 5.5 4.8M16 6a3 3 0 0 1 0 5.8M18 14c1.8.7 2.6 2.3 2.7 5" />
                                    </svg></span></div>
                            <div class="mt-5 flex items-end justify-between"><strong
                                    class="font-display text-2xl font-bold">{{ formatNumber(users.length)
                                    }}</strong><span class="text-[10px] font-semibold text-forest-700">{{
                                        activeUserCount }} đang hoạt động</span></div>
                            <p class="mt-2 text-[10px] text-gray-400">Theo bảng users</p>
                        </article>
                        <article
                            class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm shadow-gray-900/[.02] sm:p-5">
                            <div class="flex items-start justify-between"><span
                                    class="text-xs font-medium text-gray-500">Bài báo</span><span
                                    class="grid size-9 place-items-center rounded-lg bg-citrus-50 text-citrus-700"><svg
                                        class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="1.7">
                                        <path
                                            d="M5 4.5h14A1.5 1.5 0 0 1 20.5 6v12a1.5 1.5 0 0 1-1.5 1.5H5A1.5 1.5 0 0 1 3.5 18V6A1.5 1.5 0 0 1 5 4.5Z" />
                                        <path d="M7.5 8h9m-9 4h9m-9 4h5" />
                                    </svg></span></div>
                            <div class="mt-5 flex items-end justify-between"><strong
                                    class="font-display text-2xl font-bold">{{ formatNumber(articles.length)
                                    }}</strong><span class="text-[10px] font-semibold text-gray-500">{{ publishedCount
                                    }} đã xuất bản</span></div>
                            <p class="mt-2 text-[10px] text-gray-400">{{ articles.length - publishedCount }} bản nháp
                            </p>
                        </article>
                        <article
                            class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm shadow-gray-900/[.02] sm:p-5">
                            <div class="flex items-start justify-between"><span
                                    class="text-xs font-medium text-gray-500">Lượt xem</span><span
                                    class="grid size-9 place-items-center rounded-lg bg-orange-50 text-orange-700"><svg
                                        class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="1.7">
                                        <path d="M2.8 12s3.3-6 9.2-6 9.2 6 9.2 6-3.3 6-9.2 6-9.2-6-9.2-6Z" />
                                        <circle cx="12" cy="12" r="2.5" />
                                    </svg></span></div>
                            <div class="mt-5 flex items-end justify-between"><strong
                                    class="font-display text-2xl font-bold">{{ formatNumber(totalViews) }}</strong><span
                                    class="text-[10px] text-gray-500">toàn thời gian</span></div>
                            <p class="mt-2 text-[10px] text-gray-400">{{ formatNumber(totalListens) }} lượt nghe</p>
                        </article>
                        <article
                            class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm shadow-gray-900/[.02] sm:p-5">
                            <div class="flex items-start justify-between"><span
                                    class="text-xs font-medium text-gray-500">Danh mục</span><span
                                    class="grid size-9 place-items-center rounded-lg bg-violet-50 text-violet-700"><svg
                                        class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="1.7">
                                        <path d="M4 5.5h6l2 2h8v11H4z" />
                                        <path d="M4 8h16" />
                                    </svg></span></div>
                            <div class="mt-5 flex items-end justify-between"><strong
                                    class="font-display text-2xl font-bold">{{ categories.length }}</strong><span
                                    class="text-[10px] font-semibold text-gray-500">{{categories.filter((item) =>
                                        item.is_active).length}} đang hiển thị</span></div>
                            <p class="mt-2 text-[10px] text-gray-400">Phân loại nội dung</p>
                        </article>
                    </section>

                    <section class="mt-5 grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1.55fr)_minmax(280px,.8fr)]">
                        <article
                            class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm shadow-gray-900/[.02] sm:p-6">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-medium text-gray-500">Mức độ quan tâm</p>
                                    <h3 class="mt-1 font-display text-base font-bold">Lượt đọc bài báo</h3>
                                </div><select
                                    class="h-8 rounded-md border border-gray-200 bg-white px-2 text-[10px] text-gray-600">
                                    <option>7 ngày gần nhất</option>
                                    <option>30 ngày gần nhất</option>
                                </select>
                            </div>
                            <div
                                class="mt-7 flex h-44 items-end justify-between gap-2 border-b border-gray-100 px-1 sm:gap-4">
                                <div v-for="(bar, index) in [{ day: 'T5', height: 'h-16', value: '5.2k' }, { day: 'T6', height: 'h-24', value: '7.4k' }, { day: 'T7', height: 'h-20', value: '6.1k' }, { day: 'CN', height: 'h-28', value: '8.8k' }, { day: 'T2', height: 'h-24', value: '7.2k' }, { day: 'T3', height: 'h-32', value: '10.1k' }, { day: 'T4', height: 'h-28', value: '8.9k' }]"
                                    :key="bar.day" class="flex h-full flex-1 flex-col items-center justify-end gap-2">
                                    <span class="text-[9px] text-gray-400">{{ bar.value }}</span>
                                    <div
                                        :class="['w-full max-w-9 rounded-t-md bg-forest-100 transition hover:bg-forest-400', bar.height, index === 5 ? '!bg-forest-600' : '']">
                                    </div><span class="pb-2 text-[9px] text-gray-400">{{ bar.day }}</span>
                                </div>
                            </div>
                            <div class="mt-4 flex items-center justify-between text-[10px] text-gray-400"><span>Dữ liệu
                                    thống kê minh họa</span><span class="inline-flex items-center gap-1.5"><i
                                        class="size-2 rounded-full bg-forest-600"></i>Lượt xem</span></div>
                        </article>
                        <article
                            class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm shadow-gray-900/[.02] sm:p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium text-gray-500">Nội dung</p>
                                    <h3 class="mt-1 font-display text-base font-bold">Phân bổ danh mục</h3>
                                </div><button class="text-xs font-semibold text-forest-700 hover:text-forest-900"
                                    @click="setSection('categories')">Quản lý</button>
                            </div>
                            <div class="mt-5 space-y-4">
                                <div v-for="category in categories.slice(0, 4)" :key="category.id">
                                    <div class="mb-1.5 flex justify-between text-[11px]"><span
                                            class="font-medium text-gray-700">{{ category.name }}</span><span
                                            class="text-gray-400">{{ categoryCount(category.id) }} bài</span></div>
                                    <div class="h-1.5 overflow-hidden rounded-full bg-gray-100">
                                        <div :class="['h-full rounded-full', category.id % 2 ? 'bg-forest-500' : 'bg-citrus-400']"
                                            :style="{ width: `${Math.max(8, categoryCount(category.id) * 18)}%` }">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-6 border-t border-gray-100 pt-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-[10px] text-gray-400">Tổng danh mục</p><strong
                                            class="mt-1 block font-display text-lg">{{ categories.length }}</strong>
                                    </div>
                                    <div class="text-end">
                                        <p class="text-[10px] text-gray-400">Bài đang hoạt động</p><strong
                                            class="mt-1 block font-display text-lg">{{ publishedCount }}</strong>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </section>

                    <section
                        class="mt-5 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm shadow-gray-900/[.02]">
                        <div
                            class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                            <div>
                                <h3 class="font-display text-sm font-bold">Bài báo cập nhật gần đây</h3>
                                <p class="mt-1 text-[10px] text-gray-400">Theo thời điểm xuất bản</p>
                            </div><button class="text-xs font-semibold text-forest-700 hover:text-forest-900"
                                @click="setSection('articles')">Xem tất cả <span aria-hidden="true">→</span></button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[700px] text-left">
                                <thead
                                    class="bg-gray-50/80 text-[9px] font-semibold uppercase tracking-wide text-gray-400">
                                    <tr>
                                        <th class="px-5 py-3">Tiêu đề bài báo</th>
                                        <th class="px-4 py-3">Danh mục</th>
                                        <th class="px-4 py-3">Lượt xem</th>
                                        <th class="px-4 py-3">Trạng thái</th>
                                        <th class="px-5 py-3 text-end">Ngày đăng</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="article in articles.slice(0, 4)" :key="article.id"
                                        class="hover:bg-gray-50/70">
                                        <td class="max-w-[420px] px-5 py-3.5">
                                            <p class="truncate text-xs font-semibold text-gray-800">{{ article.title }}
                                            </p>
                                            <p class="mt-1 text-[10px] text-gray-400">{{ sourceName(article.source_id)
                                            }} · {{ article.author || 'Chưa rõ tác giả' }}</p>
                                        </td>
                                        <td class="px-4 py-3.5 text-[11px] text-gray-600">{{
                                            categoryName(article.category_id) }}</td>
                                        <td class="px-4 py-3.5 text-xs font-semibold tabular-nums">{{
                                            formatNumber(article.view_count) }}</td>
                                        <td class="px-4 py-3.5"><span
                                                :class="['rounded-full px-2 py-1 text-[9px] font-semibold', article.published_at ? statusClasses(true) : 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200']">{{
                                                    article.published_at ? 'Đã xuất bản' : 'Bản nháp' }}</span></td>
                                        <td class="px-5 py-3.5 text-end text-[10px] text-gray-500">{{
                                            formatDate(article.published_at) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </template>

                <template v-else-if="activeSection === 'users'">
                    <section
                        class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm shadow-gray-900/[.02]">
                        <div
                            class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                            <div>
                                <h3 class="font-display text-sm font-bold">Tài khoản người dùng</h3>
                                <p class="mt-1 text-[10px] text-gray-400">{{ users.length }} tài khoản · {{
                                    activeUserCount }} đang hoạt động</p>
                            </div>
                            <div class="flex items-center gap-2"><select
                                    class="h-9 rounded-lg border border-gray-200 bg-white px-3 text-xs text-gray-600"
                                    @change="search = $event.target.value === 'all' ? '' : $event.target.value">
                                    <option value="all">Tất cả vai trò</option>
                                    <option value="admin">Admin</option>
                                    <option value="editor">Editor</option>
                                    <option value="user">User</option>
                                </select></div>
                        </div>
                        <div class="border-b border-gray-100 px-4 py-3 md:hidden"><label
                                class="flex h-9 items-center gap-2 rounded-lg border border-gray-200 px-3"><svg
                                    class="size-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.8">
                                    <circle cx="10.8" cy="10.8" r="6.5" />
                                    <path d="m16 16 4 4" />
                                </svg><input v-model="search"
                                    class="w-full border-0 p-0 text-xs outline-none focus:ring-0"
                                    placeholder="Tìm người dùng" /></label></div>
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[720px] text-left">
                                <thead
                                    class="bg-gray-50/80 text-[9px] font-semibold uppercase tracking-wide text-gray-400">
                                    <tr>
                                        <th class="px-5 py-3">Người dùng</th>
                                        <th class="px-4 py-3">Vai trò</th>
                                        <th class="px-4 py-3">Trạng thái</th>
                                        <th class="px-4 py-3">Ngày tham gia</th>
                                        <th class="px-5 py-3 text-end">ID</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-gray-50/70">
                                        <td class="px-5 py-3.5">
                                            <div class="flex items-center gap-3"><span
                                                    class="grid size-9 shrink-0 place-items-center rounded-full bg-forest-50 text-xs font-bold text-forest-700">{{
                                                        user.name.split(' ').map((part) => part[0]).slice(-2).join('')
                                                    }}</span><span class="min-w-0"><strong
                                                        class="block text-xs font-semibold text-gray-800">{{ user.name
                                                        }}</strong><small
                                                        class="mt-1 block text-[10px] text-gray-400">{{ user.email
                                                        }}</small></span></div>
                                        </td>
                                        <td class="px-4 py-3.5"><span
                                                :class="['rounded-md px-2 py-1 text-[9px] font-semibold capitalize', roleClasses(user.role)]">{{
                                                    user.role }}</span></td>
                                        <td class="px-4 py-3.5"><span
                                                :class="['inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-[9px] font-semibold', statusClasses(user.is_active)]"><i
                                                    class="size-1.5 rounded-full"
                                                    :class="user.is_active ? 'bg-forest-500' : 'bg-gray-400'"></i>{{
                                                        user.is_active ? 'Hoạt động' : 'Đã khóa' }}</span></td>
                                        <td class="px-4 py-3.5 text-[10px] text-gray-500">{{ formatDate(user.created_at)
                                        }}</td>
                                        <td class="px-5 py-3.5 text-end font-mono text-[10px] text-gray-400">#{{ user.id
                                        }}</td>
                                    </tr>
                                    <tr v-if="!filteredUsers.length">
                                        <td colspan="5" class="px-5 py-14 text-center text-xs text-gray-400">Không tìm
                                            thấy người dùng phù hợp.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div
                            class="flex items-center justify-between border-t border-gray-100 px-5 py-3 text-[10px] text-gray-400">
                            <span>Hiển thị {{ filteredUsers.length }} / {{ users.length }} tài khoản mẫu</span>
                            <div class="flex gap-1"><button
                                    class="grid size-7 place-items-center rounded border border-gray-200 text-gray-400"
                                    disabled>‹</button><button
                                    class="grid size-7 place-items-center rounded bg-forest-800 text-white">1</button><button
                                    class="grid size-7 place-items-center rounded border border-gray-200 text-gray-500">2</button><button
                                    class="grid size-7 place-items-center rounded border border-gray-200 text-gray-500">›</button>
                            </div>
                        </div>
                    </section>
                </template>

                <template v-else-if="activeSection === 'categories'">
                    <section
                        class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm shadow-gray-900/[.02]">
                        <div
                            class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                            <div>
                                <h3 class="font-display text-sm font-bold">Danh sách danh mục</h3>
                                <p class="mt-1 text-[10px] text-gray-400">{{ categories.length }} danh mục đã tạo</p>
                            </div><label
                                class="hidden h-9 w-56 items-center gap-2 rounded-lg border border-gray-200 px-3 md:flex"><svg
                                    class="size-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.8">
                                    <circle cx="10.8" cy="10.8" r="6.5" />
                                    <path d="m16 16 4 4" />
                                </svg><input v-model="search"
                                    class="w-full border-0 p-0 text-xs outline-none focus:ring-0"
                                    placeholder="Tìm danh mục" /></label>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[760px] text-left">
                                <thead
                                    class="bg-gray-50/80 text-[9px] font-semibold uppercase tracking-wide text-gray-400">
                                    <tr>
                                        <th class="px-5 py-3">Tên danh mục</th>
                                        <th class="px-4 py-3">Slug</th>
                                        <th class="px-4 py-3">Danh mục cha</th>
                                        <th class="px-4 py-3">Bài báo</th>
                                        <th class="px-4 py-3">Trạng thái</th>
                                        <th class="px-4 py-3">Thứ tự</th>
                                        <th class="px-5 py-3 text-end">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="category in filteredCategories" :key="category.id"
                                        class="hover:bg-gray-50/70">
                                        <td class="px-5 py-3.5"><strong
                                                class="block text-xs font-semibold text-gray-800">{{ category.name
                                                }}</strong><small
                                                class="mt-1 block max-w-56 truncate text-[10px] text-gray-400">{{
                                                    category.description || 'Chưa có mô tả' }}</small></td>
                                        <td class="px-4 py-3.5 font-mono text-[10px] text-gray-500">{{ category.slug }}
                                        </td>
                                        <td class="px-4 py-3.5 text-[10px] text-gray-500">{{ category.parent_id ?
                                            categoryName(category.parent_id) : '—' }}</td>
                                        <td class="px-4 py-3.5 text-xs font-semibold tabular-nums">{{
                                            categoryCount(category.id) }}</td>
                                        <td class="px-4 py-3.5"><button
                                                :class="['rounded-full px-2 py-1 text-[9px] font-semibold', statusClasses(category.is_active)]"
                                                @click="category.is_active = !category.is_active">{{ category.is_active
                                                    ? 'Đang hiển thị' : 'Đã ẩn' }}</button></td>
                                        <td class="px-4 py-3.5 text-xs text-gray-500">{{ category.sort_order }}</td>
                                        <td class="px-5 py-3.5">
                                            <div class="flex justify-end gap-1"><button
                                                    class="grid size-8 place-items-center rounded-md text-gray-400 hover:bg-forest-50 hover:text-forest-700"
                                                    :aria-label="`Sửa ${category.name}`"
                                                    @click="openCategoryForm(category)"><svg class="size-4"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="1.7" stroke-linecap="round"
                                                        stroke-linejoin="round">
                                                        <path
                                                            d="m14 5 5 5M4 20l4.5-1 10-10a2.1 2.1 0 0 0-3-3l-10 10L4 20Z" />
                                                    </svg></button><button
                                                    class="grid size-8 place-items-center rounded-md text-gray-400 hover:bg-red-50 hover:text-red-600"
                                                    :aria-label="`Xóa ${category.name}`"
                                                    @click="askDelete('category', category)"><svg class="size-4"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="1.7" stroke-linecap="round">
                                                        <path d="M4 7h16M10 11v6m4-6v6M6 7l1 13h10l1-13M9 7V4h6v3" />
                                                    </svg></button></div>
                                        </td>
                                    </tr>
                                    <tr v-if="!filteredCategories.length">
                                        <td colspan="7" class="px-5 py-14 text-center text-xs text-gray-400">Chưa có
                                            danh mục phù hợp.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="border-t border-gray-100 px-5 py-3 text-[10px] text-gray-400">Danh mục và số liệu
                            trên màn hình là dữ liệu mẫu cục bộ.</div>
                    </section>
                </template>

                <template v-else>
                    <section
                        class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm shadow-gray-900/[.02]">
                        <div
                            class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                            <div>
                                <h3 class="font-display text-sm font-bold">Danh sách bài báo</h3>
                                <p class="mt-1 text-[10px] text-gray-400">{{ articles.length }} bài · {{ publishedCount
                                }} đã xuất bản</p>
                            </div>
                            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row"><label
                                    class="flex h-9 items-center gap-2 rounded-lg border border-gray-200 px-3 sm:w-56"><svg
                                        class="size-4 shrink-0 text-gray-400" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="1.8">
                                        <circle cx="10.8" cy="10.8" r="6.5" />
                                        <path d="m16 16 4 4" />
                                    </svg><input v-model="search"
                                        class="w-full border-0 p-0 text-xs outline-none focus:ring-0"
                                        placeholder="Tìm bài báo" /></label><select v-model="selectedCategory"
                                    class="h-9 rounded-lg border border-gray-200 bg-white px-3 text-xs text-gray-600">
                                    <option value="all">Tất cả danh mục</option>
                                    <option v-for="category in categories" :key="category.id"
                                        :value="String(category.id)">{{ category.name }}</option>
                                </select></div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[940px] text-left">
                                <thead
                                    class="bg-gray-50/80 text-[9px] font-semibold uppercase tracking-wide text-gray-400">
                                    <tr>
                                        <th class="px-5 py-3">Bài báo</th>
                                        <th class="px-4 py-3">Nguồn</th>
                                        <th class="px-4 py-3">Danh mục</th>
                                        <th class="px-4 py-3">Lượt xem / nghe</th>
                                        <th class="px-4 py-3">Ngày đăng</th>
                                        <th class="px-4 py-3">Trạng thái</th>
                                        <th class="px-5 py-3 text-end">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="article in filteredArticles" :key="article.id"
                                        class="hover:bg-gray-50/70">
                                        <td class="max-w-[350px] px-5 py-3.5"><strong
                                                class="block truncate text-xs font-semibold text-gray-800">{{
                                                    article.title }}</strong><small
                                                class="mt-1 block truncate font-mono text-[9px] text-gray-400">/{{
                                                    article.slug }}</small></td>
                                        <td class="px-4 py-3.5 text-[10px] text-gray-600">{{
                                            sourceName(article.source_id) }}</td>
                                        <td class="px-4 py-3.5"><span
                                                class="rounded-md bg-gray-100 px-2 py-1 text-[9px] font-medium text-gray-600">{{
                                                    categoryName(article.category_id) }}</span></td>
                                        <td class="px-4 py-3.5"><span
                                                class="block text-[10px] font-semibold tabular-nums text-gray-700">{{
                                                    formatNumber(article.view_count) }} <small
                                                    class="font-normal text-gray-400">xem</small></span><span
                                                class="mt-1 block text-[9px] text-gray-400">{{
                                                    formatNumber(article.listen_count) }} nghe</span></td>
                                        <td class="px-4 py-3.5 text-[10px] text-gray-500">{{
                                            formatDate(article.published_at) }}</td>
                                        <td class="px-4 py-3.5"><span
                                                :class="['rounded-full px-2 py-1 text-[9px] font-semibold', article.published_at ? statusClasses(true) : 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200']">{{
                                                    article.published_at ? 'Đã xuất bản' : 'Bản nháp' }}</span></td>
                                        <td class="px-5 py-3.5">
                                            <div class="flex justify-end gap-1"><button
                                                    class="grid size-8 place-items-center rounded-md text-gray-400 hover:bg-forest-50 hover:text-forest-700"
                                                    :aria-label="`Sửa ${article.title}`"
                                                    @click="openArticleForm(article)"><svg class="size-4"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="1.7" stroke-linecap="round"
                                                        stroke-linejoin="round">
                                                        <path
                                                            d="m14 5 5 5M4 20l4.5-1 10-10a2.1 2.1 0 0 0-3-3l-10 10L4 20Z" />
                                                    </svg></button><button
                                                    class="grid size-8 place-items-center rounded-md text-gray-400 hover:bg-red-50 hover:text-red-600"
                                                    :aria-label="`Xóa ${article.title}`"
                                                    @click="askDelete('article', article)"><svg class="size-4"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="1.7" stroke-linecap="round">
                                                        <path d="M4 7h16M10 11v6m4-6v6M6 7l1 13h10l1-13M9 7V4h6v3" />
                                                    </svg></button></div>
                                        </td>
                                    </tr>
                                    <tr v-if="!filteredArticles.length">
                                        <td colspan="7" class="px-5 py-14 text-center text-xs text-gray-400">Không tìm
                                            thấy bài báo phù hợp.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div
                            class="flex items-center justify-between border-t border-gray-100 px-5 py-3 text-[10px] text-gray-400">
                            <span>Hiển thị {{ filteredArticles.length }} / {{ articles.length }} bài báo
                                mẫu</span><span>Phân trang minh họa</span>
                        </div>
                    </section>
                </template>
            </main>
        </div>

        <Transition name="fade">
            <div v-if="categoryModal || articleModal"
                class="fixed inset-0 z-50 flex items-end justify-center bg-gray-950/45 p-0 backdrop-blur-[2px] sm:items-center sm:p-5"
                @click.self="categoryModal = false; articleModal = false">
                <form v-if="categoryModal"
                    class="max-h-[92vh] w-full max-w-xl overflow-y-auto rounded-t-2xl bg-white shadow-2xl sm:rounded-xl"
                    @submit.prevent="saveCategory">
                    <div
                        class="sticky top-0 flex items-start justify-between border-b border-gray-100 bg-white px-5 py-4 sm:px-6">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-forest-600">Cấu trúc nội dung
                            </p>
                            <h3 class="mt-1 font-display text-lg font-bold">{{ categoryFormTitle }}
                            </h3>
                        </div><button type="button"
                            class="grid size-8 place-items-center rounded-md text-gray-400 hover:bg-gray-100"
                            aria-label="Đóng" @click="categoryModal = false">×</button>
                    </div>
                    <div class="grid gap-4 px-5 py-5 sm:grid-cols-2 sm:px-6"><label
                            class="grid gap-1.5 text-xs font-semibold text-gray-700">Tên danh mục <input
                                v-model.trim="categoryForm.name" required maxlength="100"
                                class="h-10 rounded-lg border border-gray-200 px-3 text-xs font-normal outline-none focus:border-forest-500 focus:ring-2 focus:ring-forest-100"
                                placeholder="Ví dụ: Công nghệ"
                                @input="!editingId && (categoryForm.slug = makeSlug(categoryForm.name))" /></label><label
                            class="grid gap-1.5 text-xs font-semibold text-gray-700">Slug <input
                                v-model.trim="categoryForm.slug" required maxlength="120"
                                class="h-10 rounded-lg border border-gray-200 px-3 font-mono text-xs font-normal outline-none focus:border-forest-500 focus:ring-2 focus:ring-forest-100"
                                placeholder="cong-nghe" /></label><label
                            class="grid gap-1.5 text-xs font-semibold text-gray-700">Danh mục cha <select
                                v-model="categoryForm.parent_id"
                                class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-xs font-normal outline-none focus:border-forest-500">
                                <option value="">Không có</option>
                                <option v-for="category in categories.filter((item) => item.id !== editingId)"
                                    :key="category.id" :value="String(category.id)">{{ category.name }}</option>
                            </select></label><label class="grid gap-1.5 text-xs font-semibold text-gray-700">Thứ tự hiển
                            thị
                            <input v-model.number="categoryForm.sort_order" type="number" min="0"
                                class="h-10 rounded-lg border border-gray-200 px-3 text-xs font-normal outline-none focus:border-forest-500" /></label><label
                            class="grid gap-1.5 text-xs font-semibold text-gray-700 sm:col-span-2">Mô tả <textarea
                                v-model.trim="categoryForm.description" maxlength="255" rows="3"
                                class="rounded-lg border border-gray-200 px-3 py-2.5 text-xs font-normal outline-none focus:border-forest-500 focus:ring-2 focus:ring-forest-100"
                                placeholder="Mô tả ngắn cho danh mục" /></label><label
                            class="flex items-center gap-2 text-xs font-medium text-gray-600 sm:col-span-2"><input
                                v-model="categoryForm.is_active" type="checkbox"
                                class="size-4 rounded border-gray-300 text-forest-700 focus:ring-forest-500" /> Hiển thị
                            danh
                            mục</label></div>
                    <div class="flex justify-end gap-2 border-t border-gray-100 px-5 py-4 sm:px-6"><button type="button"
                            class="h-9 rounded-lg border border-gray-200 px-4 text-xs font-semibold text-gray-600 hover:bg-gray-50"
                            @click="categoryModal = false">Hủy</button><button
                            class="h-9 rounded-lg bg-forest-800 px-4 text-xs font-semibold text-white hover:bg-forest-700">{{
                                editingId ? 'Lưu thay đổi' : 'Tạo danh mục' }}</button></div>
                </form>

                <form v-else
                    class="max-h-[94vh] w-full max-w-3xl overflow-y-auto rounded-t-2xl bg-white shadow-2xl sm:rounded-xl"
                    @submit.prevent="saveArticle">
                    <div
                        class="sticky top-0 z-10 flex items-start justify-between border-b border-gray-100 bg-white px-5 py-4 sm:px-6">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-forest-600">Thư viện nội dung
                            </p>
                            <h3 class="mt-1 font-display text-lg font-bold">{{ articleFormTitle }}
                            </h3>
                        </div><button type="button"
                            class="grid size-8 place-items-center rounded-md text-gray-400 hover:bg-gray-100"
                            aria-label="Đóng" @click="articleModal = false">×</button>
                    </div>
                    <div class="grid gap-x-4 gap-y-4 px-5 py-5 sm:grid-cols-2 sm:px-6"><label
                            class="grid gap-1.5 text-xs font-semibold text-gray-700 sm:col-span-2">Tiêu đề <input
                                v-model.trim="articleForm.title" required maxlength="500"
                                class="h-10 rounded-lg border border-gray-200 px-3 text-xs font-normal outline-none focus:border-forest-500 focus:ring-2 focus:ring-forest-100"
                                placeholder="Nhập tiêu đề bài báo"
                                @input="!editingId && (articleForm.slug = makeSlug(articleForm.title))" /></label><label
                            class="grid gap-1.5 text-xs font-semibold text-gray-700">Slug <input
                                v-model.trim="articleForm.slug" required maxlength="520"
                                class="h-10 rounded-lg border border-gray-200 px-3 font-mono text-xs font-normal outline-none focus:border-forest-500"
                                placeholder="duong-dan-bai-viet" /></label><label
                            class="grid gap-1.5 text-xs font-semibold text-gray-700">Tác giả <input
                                v-model.trim="articleForm.author" maxlength="150"
                                class="h-10 rounded-lg border border-gray-200 px-3 text-xs font-normal outline-none focus:border-forest-500"
                                placeholder="Tên tác giả" /></label><label
                            class="grid gap-1.5 text-xs font-semibold text-gray-700">Nguồn báo <select
                                v-model="articleForm.source_id"
                                class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-xs font-normal outline-none focus:border-forest-500">
                                <option value="">Chưa chọn nguồn</option>
                                <option v-for="source in sources" :key="source.id" :value="String(source.id)">{{
                                    source.name }}
                                </option>
                            </select></label><label class="grid gap-1.5 text-xs font-semibold text-gray-700">Danh mục
                            <select v-model="articleForm.category_id"
                                class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-xs font-normal outline-none focus:border-forest-500">
                                <option value="">Chưa phân loại</option>
                                <option v-for="category in categories" :key="category.id" :value="String(category.id)">
                                    {{
                                        category.name }}</option>
                            </select></label><label
                            class="grid gap-1.5 text-xs font-semibold text-gray-700 sm:col-span-2">URL
                            bài gốc <input v-model.trim="articleForm.original_url" type="url" required maxlength="700"
                                class="h-10 rounded-lg border border-gray-200 px-3 text-xs font-normal outline-none focus:border-forest-500"
                                placeholder="https://example.com/bai-viet" /></label><label
                            class="grid gap-1.5 text-xs font-semibold text-gray-700 sm:col-span-2">Ảnh đại diện (URL)
                            <input v-model.trim="articleForm.thumbnail" type="url" maxlength="500"
                                class="h-10 rounded-lg border border-gray-200 px-3 text-xs font-normal outline-none focus:border-forest-500"
                                placeholder="https://example.com/thumbnail.jpg" /></label><label
                            class="grid gap-1.5 text-xs font-semibold text-gray-700 sm:col-span-2">Mô tả ngắn <textarea
                                v-model="articleForm.summary" rows="2"
                                class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-normal outline-none focus:border-forest-500"
                                placeholder="Tóm tắt hiển thị trong danh sách" /></label><label
                            class="grid gap-1.5 text-xs font-semibold text-gray-700 sm:col-span-2">Nội dung bài báo
                            <textarea v-model="articleForm.content" rows="5"
                                class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-normal leading-5 outline-none focus:border-forest-500"
                                placeholder="Nội dung bài báo" /></label>
                        <details class="rounded-lg border border-gray-200 sm:col-span-2">
                            <summary class="cursor-pointer px-3 py-2.5 text-[11px] font-semibold text-gray-600">Trường
                                AI và đọc
                                thành tiếng</summary>
                            <div class="grid gap-3 border-t border-gray-100 p-3"><label
                                    class="grid gap-1.5 text-[11px] font-medium text-gray-600">Tóm tắt AI <textarea
                                        v-model="articleForm.ai_summary" rows="2"
                                        class="rounded-md border border-gray-200 px-3 py-2 text-xs font-normal outline-none focus:border-forest-500" /></label><label
                                    class="grid gap-1.5 text-[11px] font-medium text-gray-600">Nội dung TTS <textarea
                                        v-model="articleForm.content_tts" rows="3"
                                        class="rounded-md border border-gray-200 px-3 py-2 text-xs font-normal outline-none focus:border-forest-500" /></label>
                            </div>
                        </details><label class="grid gap-1.5 text-xs font-semibold text-gray-700">Ngày xuất bản <input
                                v-model="articleForm.published_at" type="datetime-local"
                                class="h-10 rounded-lg border border-gray-200 px-3 text-xs font-normal outline-none focus:border-forest-500" /><small
                                class="font-normal text-gray-400">Để trống để lưu ở trạng thái bản
                                nháp.</small></label><label class="grid gap-1.5 text-xs font-semibold text-gray-700">Số
                            từ <input v-model.number="articleForm.word_count" type="number" min="0"
                                class="h-10 rounded-lg border border-gray-200 px-3 text-xs font-normal outline-none focus:border-forest-500" /></label>
                        <p class="text-[10px] text-gray-400 sm:col-span-2">URL hash được tạo tự động từ URL bài gốc.
                            Lượt xem và
                            lượt nghe là số liệu chỉ đọc.</p>
                    </div>
                    <div
                        class="sticky bottom-0 flex justify-end gap-2 border-t border-gray-100 bg-white px-5 py-4 sm:px-6">
                        <button type="button"
                            class="h-9 rounded-lg border border-gray-200 px-4 text-xs font-semibold text-gray-600 hover:bg-gray-50"
                            @click="articleModal = false">Hủy</button><button
                            class="h-9 rounded-lg bg-forest-800 px-4 text-xs font-semibold text-white hover:bg-forest-700">{{
                                editingId ? 'Lưu thay đổi' : 'Thêm bài báo' }}</button>
                    </div>
                </form>
            </div>
        </Transition>

        <Transition name="fade">
            <div v-if="deleteTarget" class="fixed inset-0 z-[60] grid place-items-center bg-gray-950/40 p-4"
                @click.self="deleteTarget = null">
                <section class="w-full max-w-sm rounded-xl bg-white p-5 shadow-2xl"><span
                        class="grid size-10 place-items-center rounded-full bg-red-50 text-red-600"><svg class="size-5"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                            stroke-linecap="round">
                            <path d="M4 7h16M10 11v6m4-6v6M6 7l1 13h10l1-13M9 7V4h6v3" />
                        </svg></span>
                    <h3 class="mt-4 font-display text-base font-bold">Xác nhận xóa</h3>
                    <p class="mt-1 text-xs leading-5 text-gray-500">Bạn có chắc muốn xóa “{{ deleteTarget.record.name ||
                        deleteTarget.record.title }}”? Thao tác này chỉ thay đổi dữ liệu mẫu trên giao diện.</p>
                    <div class="mt-5 flex justify-end gap-2"><button
                            class="h-9 rounded-lg border border-gray-200 px-3 text-xs font-semibold text-gray-600"
                            @click="deleteTarget = null">Hủy</button><button
                            class="h-9 rounded-lg bg-red-600 px-3 text-xs font-semibold text-white hover:bg-red-700"
                            @click="confirmDelete">Xóa mục này</button></div>
                </section>
            </div>
        </Transition>

        <Transition name="notice">
            <div v-if="flashMessage"
                class="fixed bottom-5 end-5 z-[70] flex max-w-[calc(100vw-2rem)] items-center gap-2 rounded-lg border border-forest-200 bg-white px-4 py-3 text-xs font-medium text-forest-800 shadow-lg">
                <span class="grid size-5 place-items-center rounded-full bg-forest-100 text-forest-700">✓</span>{{
                    flashMessage
                }}
            </div>
        </Transition>
    </div>
</template>
