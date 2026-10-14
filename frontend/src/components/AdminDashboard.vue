<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { auth } from '@/utils/auth'
import { userService } from '@/services/userService'
import { categoryService } from '@/services/categoryService'
import { articleService, sourceService } from '@/services/articleService'

const sections = [
    { id: 'overview', label: 'Tổng quan' },
    { id: 'articles', label: 'Bài báo' },
    { id: 'categories', label: 'Danh mục' },
    { id: 'users', label: 'Người dùng' },
]

const activeSection = ref('overview')
const search = ref('')
const selectedCategory = ref('all')
const selectedStatus = ref('all')
const selectedRole = ref('all')
const categoryModal = ref(false)
const articleModal = ref(false)
const deleteTarget = ref(null)
const editingId = ref(null)
const flashMessage = ref('')
let flashTimer

// Lấy thông tin tài khoản hiện tại từ auth utils hoặc fallback mặc định
const currentUser = computed(() => {
    return auth.getUser() || { id: null, name: 'Admin ReadsNews', email: 'admin@readsnews.vn', role: 'admin' }
})

// Quản lý danh mục thực tế từ Database
const categories = ref([])
const isCategoriesLoading = ref(false)
const categoriesMeta = ref({ total: 0, per_page: 10, current_page: 1, last_page: 1 })

// Nguồn báo từ Database
const sources = ref([])

// Quản lý người dùng thực tế từ Database
const users = ref([])
const isUsersLoading = ref(false)
const usersMeta = ref({ total: 0, per_page: 10, current_page: 1, last_page: 1 })
const userModal = ref(false)
const userForm = ref({
    name: '',
    email: '',
    password: '',
    role: 'user',
    is_active: true,
})

// Quản lý bài báo thực tế từ Database
const articles = ref([])
const isArticlesLoading = ref(false)
const articlesMeta = ref({ total: 0, per_page: 10, current_page: 1, last_page: 1 })

// Thumbnail upload state
const thumbnailFile = ref(null)
const thumbnailPreview = ref(null)
const thumbnailInputRef = ref(null)

// Crawl RSS state
const isCrawling = ref(false)
const crawlSourceId = ref(null)

const categoryForm = ref(emptyCategory())
const articleForm = ref(emptyArticle())

const titleMap = {
    overview: 'Tổng quan hệ thống',
    articles: 'Quản lý bài báo',
    categories: 'Quản lý danh mục',
    users: 'Quản lý người dùng'
}
const pageTitle = computed(() => titleMap[activeSection.value])
const categoryFormTitle = computed(() => editingId.value ? 'Chỉnh sửa danh mục' : 'Thêm danh mục mới')
const articleFormTitle = computed(() => editingId.value ? 'Chỉnh sửa bài báo' : 'Thêm bài báo mới')

const sectionDescription = computed(() => {
    if (activeSection.value === 'overview') return 'Theo dõi số liệu đọc báo, lượt phát audio AI và hoạt động hệ thống hôm nay.'
    if (activeSection.value === 'users') return 'Quản lý danh sách thành viên, phân quyền vai trò và trạng thái tài khoản.'
    if (activeSection.value === 'articles') return 'Kiểm duyệt, biên tập, xuất bản bài báo và cấu hình giọng đọc TTS.'
    return 'Phân loại các chủ đề tin tức, thứ tự hiển thị và đường dẫn danh mục.'
})

const totalViews = computed(() => articles.value.reduce((sum, article) => sum + Number(article.view_count || 0), 0))
const totalListens = computed(() => articles.value.reduce((sum, article) => sum + Number(article.listen_count || 0), 0))
const publishedCount = computed(() => articles.value.filter((article) => article.published_at).length)
const activeUserCount = computed(() => users.value.filter((user) => user.is_active).length)

const filteredArticles = computed(() => articles.value.filter((article) => {
    const matchesCategory = selectedCategory.value === 'all' || article.category_id === Number(selectedCategory.value)
    const matchesStatus = selectedStatus.value === 'all'
        ? true
        : selectedStatus.value === 'published' ? !!article.published_at : !article.published_at
    const term = search.value.trim().toLocaleLowerCase('vi')
    const matchesSearch = !term || `${article.title} ${article.author || ''} ${sourceName(article.source_id)}`.toLocaleLowerCase('vi').includes(term)
    return matchesCategory && matchesStatus && matchesSearch
}))

const filteredCategories = computed(() => {
    const term = search.value.trim().toLocaleLowerCase('vi')
    return categories.value.filter((category) => !term || `${category.name} ${category.slug} ${category.description || ''}`.toLocaleLowerCase('vi').includes(term))
})

const filteredUsers = computed(() => {
    const term = search.value.trim().toLocaleLowerCase('vi')
    return users.value.filter((user) => {
        const matchesRole = selectedRole.value === 'all' || user.role === selectedRole.value
        const matchesSearch = !term || `${user.name} ${user.email} ${user.role}`.toLocaleLowerCase('vi').includes(term)
        return matchesRole && matchesSearch
    })
})

function emptyCategory() {
    return {
        parent_id: '',
        name: '',
        slug: '',
        description: '',
        sort_order: (categories.value?.length || 0) + 1,
        is_active: true
    }
}

function emptyArticle() {
    return {
        source_id: '',
        category_id: '',
        title: '',
        slug: '',
        summary: '',
        ai_summary: '',
        content: '',
        content_tts: '',
        thumbnail: '',
        original_url: '',
        url_hash: '',
        author: '',
        word_count: 0,
        view_count: 0,
        listen_count: 0,
        published_at: ''
    }
}

function makeSlug(value) {
    return (value || '').normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/đ/g, 'd')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '')
}

function formatNumber(value) {
    return new Intl.NumberFormat('vi-VN').format(value || 0)
}

function formatDate(value) {
    if (!value) return 'Bản nháp'
    return new Intl.DateTimeFormat('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(new Date(value))
}

function formatDateShort(value) {
    if (!value) return 'Bản nháp'
    return new Intl.DateTimeFormat('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' }).format(new Date(value))
}

function sourceName(id) {
    return sources.value.find((source) => source.id === Number(id))?.name || 'Chưa chọn nguồn'
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
    flashTimer = window.setTimeout(() => { flashMessage.value = '' }, 3000)
}

function setSection(section) {
    activeSection.value = section
    search.value = ''
    selectedCategory.value = 'all'
    selectedStatus.value = 'all'
    selectedRole.value = 'all'
}

function openCategoryForm(category = null) {
    editingId.value = category?.id ?? null
    categoryForm.value = category ? { ...category, parent_id: category.parent_id ?? '' } : emptyCategory()
    categoryModal.value = true
}

async function saveCategory() {
    const record = {
        ...categoryForm.value,
        parent_id: categoryForm.value.parent_id ? Number(categoryForm.value.parent_id) : null,
        sort_order: Number(categoryForm.value.sort_order) || 0,
        is_active: Boolean(categoryForm.value.is_active)
    }
    record.slug = record.slug ? record.slug.trim() : makeSlug(record.name)
    try {
        if (editingId.value) {
            const res = await categoryService.updateCategory(editingId.value, record)
            if (res?.success) { notify(res.message || 'Đã cập nhật danh mục thành công!'); categoryModal.value = false; await fetchCategories() }
        } else {
            const res = await categoryService.createCategory(record)
            if (res?.success) { notify(res.message || 'Đã thêm danh mục mới thành công!'); categoryModal.value = false; await fetchCategories() }
        }
    } catch (err) {
        notify(err.response?.data?.message || 'Có lỗi xảy ra khi lưu danh mục.')
        console.error('Lỗi khi lưu danh mục:', err)
    }
}

function openArticleForm(article = null) {
    editingId.value = article?.id ?? null
    articleForm.value = article
        ? { ...article, source_id: article.source_id ? String(article.source_id) : '', category_id: article.category_id ? String(article.category_id) : '', published_at: article.published_at ? article.published_at.slice(0, 16) : '' }
        : emptyArticle()
    thumbnailFile.value = null
    thumbnailPreview.value = article?.thumbnail || null
    articleModal.value = true
}

function onThumbnailChange(event) {
    const file = event.target.files[0]
    if (!file) return
    thumbnailFile.value = file
    thumbnailPreview.value = URL.createObjectURL(file)
}

function removeThumbnail() {
    thumbnailFile.value = null
    thumbnailPreview.value = null
    articleForm.value.thumbnail = ''
    if (thumbnailInputRef.value) thumbnailInputRef.value.value = ''
}

async function saveArticle() {
    try {
        const fd = new FormData()
        fd.append('title', articleForm.value.title || '')
        fd.append('slug', articleForm.value.slug ? makeSlug(articleForm.value.slug) : makeSlug(articleForm.value.title))
        fd.append('original_url', articleForm.value.original_url || '')
        if (articleForm.value.source_id) fd.append('source_id', articleForm.value.source_id)
        if (articleForm.value.category_id) fd.append('category_id', articleForm.value.category_id)
        if (articleForm.value.author) fd.append('author', articleForm.value.author)
        if (articleForm.value.summary) fd.append('summary', articleForm.value.summary)
        if (articleForm.value.ai_summary) fd.append('ai_summary', articleForm.value.ai_summary)
        if (articleForm.value.content) fd.append('content', articleForm.value.content)
        if (articleForm.value.content_tts) fd.append('content_tts', articleForm.value.content_tts)
        if (articleForm.value.word_count) fd.append('word_count', articleForm.value.word_count)
        if (articleForm.value.published_at) fd.append('published_at', articleForm.value.published_at)
        if (thumbnailFile.value) fd.append('thumbnail', thumbnailFile.value)
        let res
        if (editingId.value) {
            res = await articleService.updateArticle(editingId.value, fd)
        } else {
            res = await articleService.createArticle(fd)
        }
        if (res?.success) {
            notify(res.message || (editingId.value ? 'Cập nhật thành công!' : 'Thêm bài báo thành công!'))
            articleModal.value = false
            await fetchArticles()
        }
    } catch (err) {
        const errs = err.response?.data?.errors
        const msg = errs ? Object.values(errs).flat().join(' | ') : (err.response?.data?.message || 'Có lỗi khi lưu bài báo.')
        notify(msg)
        console.error('Lỗi khi lưu bài báo:', err)
    }
}

function askDelete(type, record) {
    deleteTarget.value = { type, record }
}

async function confirmDelete() {
    const { type, record } = deleteTarget.value
    if (type === 'article') {
        try {
            await articleService.deleteArticle(record.id)
            notify('Đã xóa bài báo thành công.')
            await fetchArticles()
        } catch (err) {
            notify(err.response?.data?.message || 'Lỗi khi xóa bài báo.')
        }
    } else if (type === 'category') {
        try {
            await categoryService.deleteCategory(record.id)
            notify(`Đã xóa danh mục "${record.name}" thành công.`)
            await fetchCategories()
        } catch (err) {
            notify(err.response?.data?.message || 'Lỗi khi xóa danh mục.')
            console.error('Lỗi khi xóa danh mục:', err)
        }
    } else if (type === 'user') {
        try {
            await userService.deleteUser(record.id)
            users.value = users.value.filter((u) => u.id !== record.id)
            notify(`Đã xóa tài khoản ${record.name} thành công.`)
        } catch (err) {
            console.error('Lỗi khi xóa người dùng:', err)
        }
    }
    deleteTarget.value = null
}

// ─── API Bài Báo (Articles) ──────────────────────────────────────────────────
async function fetchArticles(page = articlesMeta.value.current_page, params = {}) {
    isArticlesLoading.value = true
    try {
        const res = await articleService.getArticles({ per_page: 10, page, ...params })
        if (res?.success) {
            articles.value = res.data || []
            if (res.meta) articlesMeta.value = res.meta
        }
    } catch (err) {
        console.error('Lỗi khi tải danh sách bài báo:', err)
    } finally {
        isArticlesLoading.value = false
    }
}

async function handleTogglePublish(article) {
    try {
        const res = await articleService.togglePublish(article.id)
        if (res?.success) {
            article.published_at = res.data.published_at
            notify(res.message || 'Cập nhật trạng thái xuất bản thành công!')
        }
    } catch (err) {
        notify('Không thể cập nhật trạng thái xuất bản.')
        console.error('Lỗi toggle publish:', err)
    }
}

// ─── API Nguồn Báo (Sources) ─────────────────────────────────────────────────
async function fetchSources() {
    try {
        const res = await sourceService.getSources()
        if (res?.success) sources.value = res.data || []
    } catch (err) {
        console.error('Lỗi khi tải nguồn báo:', err)
    }
}

async function handleCrawlRss(sourceId) {
    crawlSourceId.value = sourceId
    isCrawling.value = true
    try {
        const res = await sourceService.crawlRss(sourceId)
        if (res?.success) {
            notify(res.message || 'Crawl RSS thành công!')
            await fetchArticles(1)
        }
    } catch (err) {
        notify(err.response?.data?.message || 'Lỗi khi crawl RSS.')
        console.error('Lỗi crawl RSS:', err)
    } finally {
        isCrawling.value = false
        crawlSourceId.value = null
    }
}

// ─── API Người Dùng (Users) ──────────────────────────────────────────────────
async function fetchUsers(page = 1) {
    isUsersLoading.value = true
    try {
        const res = await userService.getUsers({ per_page: 10, page })
        if (res?.success) {
            users.value = res.data || []
            if (res.meta) usersMeta.value = res.meta
        }
    } catch (err) {
        console.error('Lỗi khi tải người dùng:', err)
    } finally {
        isUsersLoading.value = false
    }
}

async function handleToggleUserStatus(user) {
    if (user.id === currentUser.value?.id) { notify('Bạn không thể tự khóa tài khoản của chính mình!'); return }
    try {
        const res = await userService.toggleStatus(user.id)
        if (res?.success) { user.is_active = res.data.is_active; notify(res.message || 'Cập nhật trạng thái thành công!') }
    } catch (err) { console.error('Lỗi đổi trạng thái người dùng:', err) }
}

async function handleRoleChange(user, newRole) {
    if (user.id === currentUser.value?.id) { notify('Bạn không thể tự thay đổi vai trò của chính mình!'); return }
    try {
        const res = await userService.updateRole(user.id, newRole)
        if (res?.success) { user.role = res.data.role; notify(res.message || 'Cập nhật vai trò thành công!') }
    } catch (err) { console.error('Lỗi đổi vai trò:', err) }
}

function openUserModal() {
    userForm.value = { name: '', email: '', password: '', role: 'user', is_active: true }
    userModal.value = true
}

async function saveUser() {
    try {
        const res = await userService.createUser(userForm.value)
        if (res?.success) {
            userModal.value = false
            userForm.value = { name: '', email: '', password: '', role: 'user', is_active: true }
            notify('Đã thêm người dùng mới thành công!')
            await fetchUsers()
        }
    } catch (err) { console.error('Lỗi tạo người dùng:', err) }
}

// ─── API Danh Mục (Categories) ────────────────────────────────────────────────
async function fetchCategories(page = 1) {
    isCategoriesLoading.value = true
    try {
        const res = await categoryService.getCategories({ per_page: 10, page })
        if (res?.success) {
            categories.value = res.data || []
            if (res.meta) categoriesMeta.value = res.meta
        }
    } catch (err) {
        console.error('Lỗi khi tải danh mục:', err)
    } finally {
        isCategoriesLoading.value = false
    }
}

async function handleToggleCategoryStatus(category) {
    try {
        const res = await categoryService.toggleStatus(category.id)
        if (res?.success) { category.is_active = res.data.is_active; notify(res.message || 'Cập nhật trạng thái danh mục thành công!') }
    } catch (err) {
        notify('Không thể cập nhật trạng thái danh mục.')
        console.error('Lỗi đổi trạng thái danh mục:', err)
    }
}

function handleLogout() {
    auth.clearAuth()
    notify('Đã đăng xuất tài khoản quản trị.')
    setTimeout(() => { window.location.href = '/' }, 400)
}

function roleBadgeClasses(role) {
    if (role === 'admin') return 'bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-200/60'
    if (role === 'editor') return 'bg-violet-50 text-violet-700 ring-1 ring-inset ring-violet-200/60'
    return 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-200/60'
}

onMounted(() => {
    fetchUsers()
    fetchCategories()
    fetchArticles()
    fetchSources()
})

onBeforeUnmount(() => window.clearTimeout(flashTimer))
</script>

<template>
    <div
        class="min-h-screen bg-slate-50/70 font-sans text-slate-800 antialiased selection:bg-sky-500 selection:text-white">
        <!-- Sidebar Cố Định (Desktop) -->
        <aside
            class="fixed inset-y-0 start-0 z-30 hidden w-64 flex-col border-r border-slate-800/80 bg-slate-900 text-slate-300 lg:flex shadow-2xl">
            <!-- Brand Logo Header -->
            <div class="flex h-18 items-center justify-between border-b border-slate-800/80 px-5">
                <a href="#overview" @click.prevent="setSection('overview')" class="group flex items-center gap-3">
                    <img src="/logo/Khuon-Tron-removebg.png" alt="ReadVn"
                        class="size-10 rounded-xl bg-white p-1 shadow-md shadow-sky-500/20 transition group-hover:scale-105 shrink-0 object-contain" />
                    <div>
                        <div
                            class="flex items-center gap-1.5 font-display text-base font-extrabold tracking-tight text-white">
                            <span>Read<span class="text-orange-500">Vn</span></span>
                            <span
                                class="rounded bg-sky-500/20 px-1.5 py-0.5 text-[9px] font-bold text-sky-300 ring-1 ring-sky-500/30">AI
                                CMS</span>
                        </div>
                        <p class="text-[11px] text-slate-400">Báo Đọc – Tin Nhanh</p>
                    </div>
                </a>
            </div>

            <!-- Navigation Links -->
            <div class="flex-1 overflow-y-auto px-3.5 py-6 space-y-6">
                <div>
                    <p class="px-3 pb-2 text-[10px] font-bold tracking-wider text-slate-400 uppercase">Quản trị hệ thống
                    </p>
                    <nav class="space-y-1" aria-label="Điều hướng chính">
                        <button v-for="section in sections" :key="section.id" :class="[
                            'group flex w-full items-center gap-3 rounded-xl px-3.5 py-2.5 text-start text-xs font-semibold transition-all duration-150',
                            activeSection === section.id
                                ? 'bg-gradient-to-r from-sky-600 to-blue-700 text-white shadow-md shadow-sky-600/25 ring-1 ring-white/10'
                                : 'text-slate-400 hover:bg-slate-800/80 hover:text-white'
                        ]" @click="setSection(section.id)">
                            <!-- SVG Icons tương ứng từng section -->
                            <svg v-if="section.id === 'overview'" class="size-[18px] shrink-0" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <rect x="3" y="3" width="7" height="7" rx="1.5" />
                                <rect x="14" y="3" width="7" height="7" rx="1.5" />
                                <rect x="14" y="14" width="7" height="7" rx="1.5" />
                                <rect x="3" y="14" width="7" height="7" rx="1.5" />
                            </svg>
                            <svg v-else-if="section.id === 'articles'" class="size-[18px] shrink-0" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z" />
                                <path d="M8 7h8M8 11h8M8 15h5" />
                            </svg>
                            <svg v-else-if="section.id === 'categories'" class="size-[18px] shrink-0"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                                <polyline points="9 22 9 12 15 12 15 22" />
                            </svg>
                            <svg v-else class="size-[18px] shrink-0" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
                            </svg>

                            <span class="flex-1">{{ section.label }}</span>

                            <span v-if="section.id === 'articles'" :class="[
                                'rounded-md px-1.5 py-0.5 text-[10px] font-bold tabular-nums',
                                activeSection === section.id ? 'bg-white/20 text-white' : 'bg-slate-800 text-slate-400 group-hover:text-slate-300'
                            ]">
                                {{ articles.length }}
                            </span>
                        </button>
                    </nav>
                </div>

                <!-- Thao tác nhanh & Chuyển hướng -->
                <div>
                    <p class="px-3 pb-2 text-[10px] font-bold tracking-wider text-slate-400 uppercase">Liên kết ngoài
                    </p>
                    <a href="/"
                        class="group flex items-center justify-between rounded-xl px-3.5 py-2.5 text-xs font-semibold text-slate-400 hover:bg-slate-800/80 hover:text-white transition">
                        <span class="flex items-center gap-2.5">
                            <svg class="size-4 text-indigo-400 transition group-hover:-translate-x-0.5"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="m15 18-6-6 6-6" />
                            </svg>
                            Về trang đọc báo
                        </span>
                        <span
                            class="rounded bg-slate-800 px-1.5 py-0.5 text-[9px] text-slate-400 group-hover:text-slate-300">Reader</span>
                    </a>
                </div>

                <!-- Widget Trạng thái AI Engine -->
                <div class="rounded-xl border border-slate-800 bg-slate-800/50 p-3.5 text-xs">
                    <div class="flex items-center justify-between pb-2">
                        <span class="flex items-center gap-2 font-medium text-slate-300">
                            <span
                                class="size-2 rounded-full bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.8)]"></span>
                            AI TTS Engine
                        </span>
                        <span class="text-[10px] font-semibold text-emerald-400">Online</span>
                    </div>
                    <p class="text-[11px] leading-relaxed text-slate-400">5 giọng đọc Tiếng Việt & tóm tắt tự động sẵn
                        sàng.</p>
                </div>
            </div>

            <!-- Profile & Nút Đăng xuất ở chân Sidebar -->
            <div class="border-t border-slate-800/90 p-3.5">
                <div class="flex items-center gap-3 rounded-xl bg-slate-800/60 p-2.5 border border-slate-700/40">
                    <div
                        class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-xs font-bold text-white shadow-inner">
                        {{ currentUser.name ? currentUser.name.slice(0, 2).toUpperCase() : 'AD' }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs font-semibold text-white">{{ currentUser.name }}</p>
                        <p class="truncate text-[10px] text-slate-400 capitalize">{{ currentUser.role === 'admin' ?
                            'Quản trị viên' : currentUser.role }}</p>
                    </div>
                    <button @click="handleLogout" title="Đăng xuất"
                        class="flex size-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-rose-500/10 hover:text-rose-400">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                            <polyline points="16 17 21 12 16 7" />
                            <line x1="21" y1="12" x2="9" y2="12" />
                        </svg>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Khung Nội Dung Chính -->
        <div class="lg:ps-64 flex flex-col min-h-screen">
            <!-- Topbar / Header -->
            <header class="sticky top-0 z-20 border-b border-slate-200/80 bg-white/90 backdrop-blur-md">
                <div class="flex h-18 items-center justify-between gap-4 px-4 sm:px-8 xl:px-10">
                    <!-- Tiêu đề & Breadcrumb -->
                    <div class="flex items-center gap-3 min-w-0">
                        <div
                            class="lg:hidden flex size-9 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-white">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.2">
                                <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <div
                                class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                                <span>ReadsNews</span>
                                <span>/</span>
                                <span class="text-indigo-600 font-bold">Admin Portal</span>
                            </div>
                            <h1 class="truncate font-display text-lg font-bold text-slate-900 tracking-tight">{{
                                pageTitle }}</h1>
                        </div>
                    </div>

                    <!-- Right Controls -->
                    <div class="flex items-center gap-3">
                        <!-- Quick Search Input -->
                        <div class="relative hidden sm:block w-64 md:w-72">
                            <span
                                class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-slate-400">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="8" />
                                    <path d="m21 21-4.35-4.35" />
                                </svg>
                            </span>
                            <input v-model="search" type="text"
                                class="h-9.5 w-full rounded-xl border border-slate-200 bg-slate-50/80 ps-9 pe-9 text-xs text-slate-800 placeholder-slate-400 transition focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-100"
                                placeholder="Tìm kiếm nhanh bài viết, người dùng..." />
                            <kbd v-if="!search"
                                class="pointer-events-none absolute inset-y-0 end-0 my-auto me-2.5 flex h-5 items-center rounded border border-slate-200 bg-white px-1.5 font-mono text-[9px] font-medium text-slate-400">⌘K</kbd>
                            <button v-else @click="search = ''"
                                class="absolute inset-y-0 end-0 my-auto me-2.5 flex size-5 items-center justify-center text-slate-400 hover:text-slate-600">×</button>
                        </div>

                        <!-- Back to Reader Portal Button -->
                        <a href="/"
                            class="inline-flex h-9.5 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-indigo-600">
                            <svg class="size-3.5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
                                <polyline points="15 3 21 3 21 9" />
                                <line x1="10" y1="14" x2="21" y2="3" />
                            </svg>
                            <span class="hidden sm:inline">Xem trang báo</span>
                        </a>

                        <!-- Notification Bell -->
                        <div class="relative">
                            <button
                                class="relative flex size-9.5 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" />
                                    <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
                                </svg>
                                <span
                                    class="absolute end-2 top-2 size-2 rounded-full bg-indigo-600 ring-2 ring-white"></span>
                            </button>
                        </div>

                        <!-- User Profile Pill -->
                        <div class="flex items-center gap-2.5 ps-1 sm:ps-2">
                            <div
                                class="flex size-9.5 items-center justify-center rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-600 text-xs font-bold text-white shadow-sm">
                                {{ currentUser.name ? currentUser.name.slice(0, 2).toUpperCase() : 'AD' }}
                            </div>
                            <div class="hidden text-start md:block">
                                <p class="text-xs font-bold text-slate-900 leading-tight">{{ currentUser.name }}</p>
                                <p class="text-[10px] font-medium text-slate-400 capitalize">{{ currentUser.role }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Tabs trên Mobile -->
                <nav class="flex gap-2 overflow-x-auto border-t border-slate-100 px-4 py-2.5 lg:hidden"
                    aria-label="Điều hướng mobile">
                    <button v-for="section in sections" :key="section.id" :class="[
                        'shrink-0 rounded-lg px-3 py-1.5 text-xs font-semibold transition',
                        activeSection === section.id
                            ? 'bg-indigo-600 text-white shadow-sm'
                            : 'text-slate-600 hover:bg-slate-100'
                    ]" @click="setSection(section.id)">
                        {{ section.label }}
                    </button>
                </nav>
            </header>

            <!-- Nội Dung Từng Trang Quản Trị -->
            <main class="flex-1 mx-auto w-full max-w-7xl px-4 py-6 sm:px-8 sm:py-8 xl:px-10">
                <!-- Page Subheader & Action CTA -->
                <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="flex items-center gap-2 text-xs font-medium text-slate-400">
                            <svg class="size-3.5 text-sky-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                <line x1="16" y1="2" x2="16" y2="6" />
                                <line x1="8" y1="2" x2="8" y2="6" />
                                <line x1="3" y1="10" x2="21" y2="10" />
                            </svg>
                            <span>Tháng 10, 2026 · Hệ thống ReadVn AI</span>
                        </div>
                        <h2 class="mt-1 font-display text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
                            {{ activeSection === 'overview' ? `Xin chào, ${currentUser.name}` : pageTitle }}
                        </h2>
                        <p class="mt-1 text-xs sm:text-sm text-slate-500">{{ sectionDescription }}</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <button v-if="activeSection === 'articles'" @click="openArticleForm()"
                            class="inline-flex h-10 items-center gap-2 rounded-xl bg-gradient-to-r from-sky-600 to-blue-700 px-4 text-xs font-bold text-white shadow-md shadow-sky-600/25 transition hover:brightness-110 active:scale-98">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 5v14M5 12h14" />
                            </svg>
                            <span>Thêm bài báo mới</span>
                        </button>

                        <button v-if="activeSection === 'categories'" @click="openCategoryForm()"
                            class="inline-flex h-10 items-center gap-2 rounded-xl bg-gradient-to-r from-sky-600 to-blue-700 px-4 text-xs font-bold text-white shadow-md shadow-sky-600/25 transition hover:brightness-110 active:scale-98">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 5v14M5 12h14" />
                            </svg>
                            <span>Thêm danh mục mới</span>
                        </button>

                        <button v-if="activeSection === 'users'" @click="openUserModal()"
                            class="inline-flex h-10 items-center gap-2 rounded-xl bg-gradient-to-r from-orange-500 to-amber-600 px-4 text-xs font-bold text-white shadow-md shadow-orange-500/25 transition hover:brightness-110 active:scale-98">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                                <line x1="19" y1="8" x2="19" y2="14" />
                                <line x1="22" y1="11" x2="16" y2="11" />
                            </svg>
                            <span>Thêm tài khoản mới</span>
                        </button>
                    </div>
                </div>

                <!-- ================= SECTION 1: TỔNG QUAN (OVERVIEW) ================= -->
                <template v-if="activeSection === 'overview'">
                    <!-- 4 Thẻ Thống Kê Chính -->
                    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4"
                        aria-label="Thống kê tổng quan">
                        <!-- Card 1: Người Dùng -->
                        <article
                            class="group rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:shadow-md hover:border-slate-300">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-slate-500">Người dùng đã đăng ký</span>
                                <div
                                    class="flex size-10 items-center justify-center rounded-xl bg-sky-50 text-sky-600 ring-1 ring-sky-200/60 transition group-hover:scale-105">
                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                        <circle cx="9" cy="7" r="4" />
                                        <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
                                    </svg>
                                </div>
                            </div>
                            <div class="mt-4 flex items-baseline justify-between">
                                <strong class="font-display text-2xl font-black text-slate-900">{{
                                    formatNumber(users.length) }}</strong>
                                <span
                                    class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-200/50">
                                    <span class="size-1.5 rounded-full bg-emerald-500"></span>
                                    {{ activeUserCount }} đang hoạt động
                                </span>
                            </div>
                            <p class="mt-2 text-[11px] text-slate-400">Tăng trưởng người dùng ổn định</p>
                        </article>

                        <!-- Card 2: Bài Báo -->
                        <article
                            class="group rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:shadow-md hover:border-slate-300">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-slate-500">Tổng số bài báo</span>
                                <div
                                    class="flex size-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 ring-1 ring-indigo-200/60 transition group-hover:scale-105">
                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z" />
                                        <path d="M8 7h8M8 11h8M8 15h5" />
                                    </svg>
                                </div>
                            </div>
                            <div class="mt-4 flex items-baseline justify-between">
                                <strong class="font-display text-2xl font-black text-slate-900">{{
                                    formatNumber(articles.length) }}</strong>
                                <span
                                    class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-700 ring-1 ring-indigo-200/50">
                                    {{ publishedCount }} đã xuất bản
                                </span>
                            </div>
                            <p class="mt-2 text-[11px] text-slate-400">{{ articles.length - publishedCount }} bài đang ở
                                dạng bản nháp</p>
                        </article>

                        <!-- Card 3: Lượt Xem & Lượt Nghe -->
                        <article
                            class="group rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:shadow-md hover:border-slate-300">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-slate-500">Lượt đọc & nghe AI</span>
                                <div
                                    class="flex size-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-200/60 transition group-hover:scale-105">
                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M3 18v-6a9 9 0 0 1 18 0v6" />
                                        <path
                                            d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="mt-4 flex items-baseline justify-between">
                                <strong class="font-display text-2xl font-black text-slate-900">{{
                                    formatNumber(totalViews) }}</strong>
                                <span class="text-[11px] font-bold text-slate-500">lượt đọc</span>
                            </div>
                            <p class="mt-2 text-[11px] font-semibold text-amber-600">{{ formatNumber(totalListens) }}
                                lượt nghe audio AI</p>
                        </article>

                        <!-- Card 4: Danh Mục Tin -->
                        <article
                            class="group rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:shadow-md hover:border-slate-300">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-slate-500">Chủ đề & danh mục</span>
                                <div
                                    class="flex size-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600 ring-1 ring-violet-200/60 transition group-hover:scale-105">
                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                                        <polyline points="9 22 9 12 15 12 15 22" />
                                    </svg>
                                </div>
                            </div>
                            <div class="mt-4 flex items-baseline justify-between">
                                <strong class="font-display text-2xl font-black text-slate-900">{{ categories.length
                                }}</strong>
                                <span
                                    class="inline-flex items-center gap-1 rounded-full bg-violet-50 px-2 py-0.5 text-[10px] font-bold text-violet-700 ring-1 ring-violet-200/50">
                                    {{categories.filter((item) => item.is_active).length}} đang hiển thị
                                </span>
                            </div>
                            <p class="mt-2 text-[11px] text-slate-400">Phân loại tin bài đồng bộ</p>
                        </article>
                    </section>

                    <!-- Biểu Đồ & Phân Bổ Nội Dung -->
                    <section class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
                        <!-- Cột Trái: Biểu Đồ Lượt Đọc & Nghe Tuần Qua (2/3) -->
                        <article class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm xl:col-span-2">
                            <div
                                class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
                                <div>
                                    <h3 class="font-display text-base font-bold text-slate-900">Mức độ tương tác độc giả
                                    </h3>
                                    <p class="text-xs text-slate-400">Thống kê lượt xem bài viết theo các ngày trong
                                        tuần</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600">
                                        <span class="size-2.5 rounded-full bg-indigo-600"></span>
                                        Lượt xem
                                    </span>
                                    <select
                                        class="h-8 rounded-lg border border-slate-200 bg-slate-50 px-2.5 text-xs font-medium text-slate-600 focus:outline-none">
                                        <option>7 ngày gần nhất</option>
                                        <option>30 ngày gần nhất</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Cột Biểu Đồ Minh Họa Dạng Bar Chart Hiện Đại -->
                            <div class="mt-8 flex h-52 items-end justify-between gap-2 sm:gap-4 px-2">
                                <div v-for="(bar, index) in [
                                    { day: 'Thứ 2', height: 'h-24', value: '5,240' },
                                    { day: 'Thứ 3', height: 'h-32', value: '7,410' },
                                    { day: 'Thứ 4', height: 'h-28', value: '6,180' },
                                    { day: 'Thứ 5', height: 'h-40', value: '8,850' },
                                    { day: 'Thứ 6', height: 'h-36', value: '7,230' },
                                    { day: 'Thứ 7', height: 'h-48', value: '11,400' },
                                    { day: 'Chủ nhật', height: 'h-44', value: '9,820' },
                                ]" :key="bar.day"
                                    class="group flex h-full flex-1 flex-col items-center justify-end gap-2">
                                    <span
                                        class="text-[10px] font-bold text-slate-400 opacity-0 transition group-hover:opacity-100 group-hover:-translate-y-1">{{
                                            bar.value }}</span>
                                    <div :class="[
                                        'w-full max-w-10 rounded-t-xl transition-all duration-300 group-hover:brightness-110',
                                        bar.height,
                                        index === 5
                                            ? 'bg-gradient-to-t from-indigo-700 to-indigo-500 shadow-md shadow-indigo-500/25'
                                            : 'bg-indigo-100 group-hover:bg-indigo-200'
                                    ]"></div>
                                    <span class="pt-1 text-[11px] font-semibold text-slate-500">{{ bar.day }}</span>
                                </div>
                            </div>

                            <div
                                class="mt-6 flex items-center justify-between border-t border-slate-100 pt-4 text-xs text-slate-400">
                                <span>Trung bình mỗi bài báo đạt 6,800 lượt tiếp cận độc giả</span>
                                <span class="font-semibold text-emerald-600">+18.4% so với tuần trước</span>
                            </div>
                        </article>

                        <!-- Cột Phải: Phân Bổ Theo Danh Mục (1/3) -->
                        <article class="flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                                <div>
                                    <h3 class="font-display text-base font-bold text-slate-900">Phân bổ danh mục</h3>
                                    <p class="text-xs text-slate-400">Số lượng bài theo từng chủ đề</p>
                                </div>
                                <button @click="setSection('categories')"
                                    class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition">
                                    Quản lý →
                                </button>
                            </div>

                            <div class="mt-5 flex-1 space-y-4">
                                <div v-for="category in categories" :key="category.id">
                                    <div class="mb-1.5 flex items-center justify-between text-xs">
                                        <span class="font-semibold text-slate-700">{{ category.name }}</span>
                                        <span class="font-bold text-slate-500 tabular-nums">{{
                                            categoryCount(category.id) }} bài</span>
                                    </div>
                                    <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                        <div :class="[
                                            'h-full rounded-full transition-all duration-500',
                                            category.id % 2 === 1 ? 'bg-gradient-to-r from-indigo-500 to-indigo-600' : 'bg-gradient-to-r from-violet-500 to-violet-600'
                                        ]"
                                            :style="{ width: `${Math.max(12, (categoryCount(category.id) / Math.max(articles.length, 1)) * 100)}%` }">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 rounded-xl bg-slate-50 p-4 border border-slate-100 text-xs text-slate-500">
                                <div class="flex justify-between font-semibold text-slate-700">
                                    <span>Tổng danh mục: {{ categories.length }}</span>
                                    <span class="text-indigo-600">{{ publishedCount }} bài đang online</span>
                                </div>
                            </div>
                        </article>
                    </section>

                    <!-- Danh Sách Bài Báo Cập Nhật Gần Đây -->
                    <section class="mt-6 rounded-2xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">
                        <div
                            class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 px-6 py-4.5">
                            <div>
                                <h3 class="font-display text-base font-bold text-slate-900">Bài báo cập nhật mới nhất
                                </h3>
                                <p class="text-xs text-slate-400">Danh sách các bài viết vừa được biên tập hoặc xuất bản
                                </p>
                            </div>
                            <button @click="setSection('articles')"
                                class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-600 hover:text-indigo-800 transition">
                                <span>Xem toàn bộ {{ articles.length }} bài</span>
                                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.5">
                                    <path d="m9 18 6-6-6-6" />
                                </svg>
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-start text-xs">
                                <thead>
                                    <tr
                                        class="border-b border-slate-100 bg-slate-50/70 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                        <th class="px-6 py-3.5 text-start">Bài viết</th>
                                        <th class="px-4 py-3.5 text-start">Danh mục</th>
                                        <th class="px-4 py-3.5 text-start">Lượt xem / Nghe</th>
                                        <th class="px-4 py-3.5 text-start">Trạng thái</th>
                                        <th class="px-6 py-3.5 text-end">Thời gian</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="article in articles.slice(0, 5)" :key="article.id"
                                        class="transition hover:bg-slate-50/80">
                                        <td class="px-6 py-4 max-w-md">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="size-11 shrink-0 overflow-hidden rounded-lg bg-slate-100 border border-slate-200/60">
                                                    <img v-if="article.thumbnail" :src="article.thumbnail"
                                                        :alt="article.title" class="size-full object-cover" />
                                                    <div v-else
                                                        class="flex size-full items-center justify-center text-slate-400">
                                                        <svg class="size-5" viewBox="0 0 24 24" fill="none"
                                                            stroke="currentColor" stroke-width="1.8">
                                                            <rect x="3" y="3" width="18" height="18" rx="2" />
                                                            <circle cx="8.5" cy="8.5" r="1.5" />
                                                            <path d="m21 15-5-5L5 21" />
                                                        </svg>
                                                    </div>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="truncate font-semibold text-slate-900">{{ article.title }}
                                                    </p>
                                                    <p class="mt-0.5 text-[11px] text-slate-400">Nguồn: {{
                                                        sourceName(article.source_id) }} · Tác giả: {{ article.author ||
                                                            'Chưa rõ' }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span
                                                class="rounded-lg bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">
                                                {{ categoryName(article.category_id) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="font-bold text-slate-800 tabular-nums">{{
                                                formatNumber(article.view_count) }} <span
                                                    class="font-normal text-slate-400 text-[10px]">xem</span></div>
                                            <div class="text-[11px] text-indigo-600 font-semibold">{{
                                                formatNumber(article.listen_count) }} <span
                                                    class="text-slate-400 font-normal text-[10px]">nghe</span></div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span :class="[
                                                'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold',
                                                article.published_at
                                                    ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/60'
                                                    : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200/60'
                                            ]">
                                                <span class="size-1.5 rounded-full"
                                                    :class="article.published_at ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                                                {{ article.published_at ? 'Đã xuất bản' : 'Bản nháp' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-end text-[11px] text-slate-400 whitespace-nowrap">
                                            {{ formatDateShort(article.published_at) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </template>

                <!-- ================= SECTION 2: QUẢN LÝ BÀI BÁO (ARTICLES) ================= -->
                <template v-else-if="activeSection === 'articles'">
                    <section class="rounded-2xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">
                        <!-- Toolbar Bộ Lọc -->
                        <div
                            class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <div class="flex flex-1 flex-wrap items-center gap-2.5">
                                <!-- Search bar -->
                                <div class="relative min-w-56 flex-1 sm:max-w-xs">
                                    <span
                                        class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-slate-400">
                                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2">
                                            <circle cx="11" cy="11" r="8" />
                                            <path d="m21 21-4.35-4.35" />
                                        </svg>
                                    </span>
                                    <input v-model="search" type="text"
                                        class="h-9 w-full rounded-xl border border-slate-200 bg-slate-50/60 ps-8.5 pe-3 text-xs placeholder-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-100"
                                        placeholder="Tìm theo tiêu đề, tác giả..." />
                                </div>

                                <!-- Lọc Danh mục -->
                                <select v-model="selectedCategory"
                                    class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 px-3 text-xs font-medium text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                                    <option value="all">Tất cả danh mục</option>
                                    <option v-for="category in categories" :key="category.id"
                                        :value="String(category.id)">{{ category.name }}</option>
                                </select>

                                <!-- Lọc Trạng thái -->
                                <select v-model="selectedStatus"
                                    class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 px-3 text-xs font-medium text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                                    <option value="all">Tất cả trạng thái</option>
                                    <option value="published">Đã xuất bản</option>
                                    <option value="draft">Bản nháp</option>
                                </select>
                            </div>

                            <!-- Nhóm bên phải: Crawl RSS + Thêm bài -->
                            <div class="flex flex-wrap items-center gap-2 shrink-0">
                                <!-- Nút Crawl RSS -->
                                <div v-if="sources.length" class="flex items-center gap-1.5">
                                    <span class="text-xs font-semibold text-slate-500 shrink-0">Crawl RSS:</span>
                                    <button v-for="src in sources" :key="src.id" type="button"
                                        @click="handleCrawlRss(src.id)"
                                        :disabled="isCrawling"
                                        :title="`Crawl tin mới từ ${src.name}`"
                                        class="inline-flex items-center gap-1 rounded-lg border border-sky-200 bg-sky-50 px-2.5 py-1 text-[11px] font-bold text-sky-700 hover:bg-sky-100 disabled:opacity-50 transition">
                                        <svg v-if="isCrawling && crawlSourceId === src.id"
                                            class="size-3 animate-spin" viewBox="0 0 24 24" fill="none">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4" />
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8v8H4z" />
                                        </svg>
                                        <svg v-else class="size-3" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2.5">
                                            <path d="M23 4v6h-6M1 20v-6h6" />
                                            <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15" />
                                        </svg>
                                        {{ src.name }}
                                    </button>
                                </div>

                                <!-- Nút thêm bài -->
                                <button @click="openArticleForm(null)" type="button"
                                    class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm hover:bg-indigo-700 transition active:scale-95">
                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2.5">
                                        <line x1="12" y1="5" x2="12" y2="19" />
                                        <line x1="5" y1="12" x2="19" y2="12" />
                                    </svg>
                                    Thêm bài báo
                                </button>

                                <div class="text-xs font-semibold text-slate-500 pl-2 border-l border-slate-200">
                                    <span class="font-bold text-indigo-600">{{ filteredArticles.length }}</span> /
                                    {{ articles.length }} bài
                                </div>
                            </div>
                        </div>

                        <!-- Data Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-start text-xs">
                                <thead>
                                    <tr
                                        class="border-b border-slate-100 bg-slate-50/70 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                        <th class="px-6 py-3.5 text-start">Bài báo</th>
                                        <th class="px-4 py-3.5 text-start">Danh mục</th>
                                        <th class="px-4 py-3.5 text-start">Nguồn</th>
                                        <th class="px-4 py-3.5 text-start">Tương tác</th>
                                        <th class="px-4 py-3.5 text-start">Ngày xuất bản</th>
                                        <th class="px-4 py-3.5 text-start">Trạng thái</th>
                                        <th class="px-6 py-3.5 text-end">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <!-- Loading state -->
                                    <tr v-if="isArticlesLoading">
                                        <td colspan="7" class="py-14 text-center text-slate-400">
                                            <div class="inline-flex items-center gap-2 font-medium">
                                                <svg class="size-4 animate-spin text-sky-500" viewBox="0 0 24 24"
                                                    fill="none">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                                        stroke="currentColor" stroke-width="4" />
                                                    <path class="opacity-75" fill="currentColor"
                                                        d="M4 12a8 8 0 018-8v8H4z" />
                                                </svg>
                                                Đang tải dữ liệu bài báo...
                                            </div>
                                        </td>
                                    </tr>

                                    <tr v-if="!isArticlesLoading" v-for="article in filteredArticles" :key="article.id"
                                        class="transition hover:bg-slate-50/80">
                                        <!-- Tiêu đề + Thumbnail -->
                                        <td class="px-6 py-4 max-w-sm">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="size-11 shrink-0 overflow-hidden rounded-lg bg-slate-100 border border-slate-200/60">
                                                    <img v-if="article.thumbnail" :src="article.thumbnail"
                                                        :alt="article.title" class="size-full object-cover" />
                                                    <div v-else
                                                        class="flex size-full items-center justify-center text-slate-400">
                                                        <svg class="size-5" viewBox="0 0 24 24" fill="none"
                                                            stroke="currentColor" stroke-width="1.8">
                                                            <rect x="3" y="3" width="18" height="18" rx="2" />
                                                            <circle cx="8.5" cy="8.5" r="1.5" />
                                                            <path d="m21 15-5-5L5 21" />
                                                        </svg>
                                                    </div>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="truncate font-bold text-slate-900" :title="article.title">
                                                        {{ article.title }}</p>
                                                    <p class="mt-0.5 truncate font-mono text-[10px] text-slate-400">/{{
                                                        article.slug }}</p>
                                                    <span v-if="article.ai_summary"
                                                        class="mt-1 inline-flex items-center gap-1 rounded bg-indigo-50 px-1.5 py-0.2 text-[9px] font-bold text-indigo-600">
                                                        ✨ Có AI Tóm tắt
                                                    </span>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Danh mục -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span
                                                class="rounded-lg bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-700">
                                                {{ categoryName(article.category_id) }}
                                            </span>
                                        </td>

                                        <!-- Nguồn -->
                                        <td class="px-4 py-4 whitespace-nowrap text-slate-600 font-medium">
                                            {{ sourceName(article.source_id) }}
                                        </td>

                                        <!-- Tương tác -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="font-bold text-slate-800 tabular-nums">{{
                                                formatNumber(article.view_count) }} <span
                                                    class="font-normal text-slate-400 text-[10px]">xem</span></div>
                                            <div class="text-[11px] text-indigo-600 font-semibold">{{
                                                formatNumber(article.listen_count) }} <span
                                                    class="text-slate-400 font-normal text-[10px]">nghe</span></div>
                                        </td>

                                        <!-- Ngày xuất bản -->
                                        <td class="px-4 py-4 whitespace-nowrap text-[11px] text-slate-500">
                                            {{ formatDate(article.published_at) }}
                                        </td>

                                        <!-- Trạng thái -->
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <button @click="handleTogglePublish(article)"
                                                :title="article.published_at ? 'Nhấn để chuyển thành bản nháp' : 'Nhấn để xuất bản'"
                                                :class="[
                                                    'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold cursor-pointer transition hover:opacity-80 active:scale-95',
                                                    article.published_at
                                                        ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/60 hover:bg-emerald-100'
                                                        : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200/60 hover:bg-amber-100'
                                                ]">
                                                <span class="size-1.5 rounded-full"
                                                    :class="article.published_at ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                                                {{ article.published_at ? 'Đã xuất bản' : 'Bản nháp' }}
                                            </button>
                                        </td>

                                        <!-- Thao tác Sửa / Xóa -->
                                        <td class="px-6 py-4 text-end whitespace-nowrap">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <button @click="openArticleForm(article)" title="Chỉnh sửa bài báo"
                                                    class="flex size-8 items-center justify-center rounded-lg text-slate-500 transition hover:bg-indigo-50 hover:text-indigo-600">
                                                    <svg class="size-4" viewBox="0 0 24 24" fill="none"
                                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round">
                                                        <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
                                                    </svg>
                                                </button>
                                                <button @click="askDelete('article', article)" title="Xóa bài báo"
                                                    class="flex size-8 items-center justify-center rounded-lg text-slate-500 transition hover:bg-rose-50 hover:text-rose-600">
                                                    <svg class="size-4" viewBox="0 0 24 24" fill="none"
                                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round">
                                                        <path
                                                            d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Empty state -->
                                    <tr v-if="!isArticlesLoading && !filteredArticles.length">
                                        <td colspan="7" class="py-16 text-center text-slate-400">
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="size-10 text-slate-300 mb-2" viewBox="0 0 24 24" fill="none"
                                                    stroke="currentColor" stroke-width="1.5">
                                                    <circle cx="11" cy="11" r="8" />
                                                    <path d="m21 21-4.35-4.35" />
                                                </svg>
                                                <p class="font-semibold text-slate-600">Không tìm thấy bài báo phù hợp
                                                </p>
                                                <p class="text-xs text-slate-400 mt-1">Thử đổi từ khóa hoặc bộ lọc danh
                                                    mục/trạng thái.</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Phân trang Footer -->
                        <div class="flex items-center justify-between border-t border-slate-100 px-6 py-3.5 text-xs text-slate-500">
                            <span>
                                Trang <strong class="text-slate-700">{{ articlesMeta.current_page }}</strong> /
                                {{ articlesMeta.last_page }} —
                                <strong class="text-indigo-600">{{ articlesMeta.total }}</strong> bài báo
                            </span>
                            <div class="flex items-center gap-1">
                                <button @click="fetchArticles(1)" :disabled="articlesMeta.current_page === 1 || isArticlesLoading"
                                    class="flex size-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition">«</button>
                                <button @click="fetchArticles(articlesMeta.current_page - 1)" :disabled="articlesMeta.current_page <= 1 || isArticlesLoading"
                                    class="flex size-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition">‹</button>
                                <template v-for="p in articlesMeta.last_page" :key="p">
                                    <button v-if="Math.abs(p - articlesMeta.current_page) <= 2"
                                        @click="fetchArticles(p)" :disabled="isArticlesLoading"
                                        :class="['flex size-8 items-center justify-center rounded-lg border text-xs font-bold transition',
                                            p === articlesMeta.current_page
                                                ? 'border-indigo-600 bg-indigo-600 text-white shadow-sm'
                                                : 'border-slate-200 text-slate-600 hover:bg-slate-50 disabled:opacity-40']">
                                        {{ p }}
                                    </button>
                                </template>
                                <button @click="fetchArticles(articlesMeta.current_page + 1)" :disabled="articlesMeta.current_page >= articlesMeta.last_page || isArticlesLoading"
                                    class="flex size-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition">›</button>
                                <button @click="fetchArticles(articlesMeta.last_page)" :disabled="articlesMeta.current_page === articlesMeta.last_page || isArticlesLoading"
                                    class="flex size-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition">»</button>
                            </div>
                        </div>
                    </section>
                </template>

                <!-- ================= SECTION 3: QUẢN LÝ DANH MỤC (CATEGORIES) ================= -->
                <template v-else-if="activeSection === 'categories'">
                    <section class="rounded-2xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">
                        <div
                            class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <div>
                                <h3 class="font-display text-base font-bold text-slate-900">Danh mục tin tức</h3>
                                <p class="text-xs text-slate-400">Cấu trúc các chuyên mục hiển thị trên thanh menu chính
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="relative w-60">
                                    <span
                                        class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-slate-400">
                                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2">
                                            <circle cx="11" cy="11" r="8" />
                                            <path d="m21 21-4.35-4.35" />
                                        </svg>
                                    </span>
                                    <input v-model="search" type="text"
                                        class="h-9 w-full rounded-xl border border-slate-200 bg-slate-50/60 ps-8.5 pe-3 text-xs placeholder-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-100"
                                        placeholder="Tìm danh mục..." />
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-start text-xs">
                                <thead>
                                    <tr
                                        class="border-b border-slate-100 bg-slate-50/70 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                        <th class="px-6 py-3.5 text-start">Tên danh mục</th>
                                        <th class="px-4 py-3.5 text-start">Slug</th>
                                        <th class="px-4 py-3.5 text-start">Danh mục cha</th>
                                        <th class="px-4 py-3.5 text-start">Số bài viết</th>
                                        <th class="px-4 py-3.5 text-start">Thứ tự</th>
                                        <th class="px-4 py-3.5 text-start">Trạng thái</th>
                                        <th class="px-6 py-3.5 text-end">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="category in filteredCategories" :key="category.id"
                                        class="transition hover:bg-slate-50/80">
                                        <td class="px-6 py-4">
                                            <p class="font-bold text-slate-900">{{ category.name }}</p>
                                            <p class="mt-0.5 text-[11px] text-slate-400 max-w-xs truncate">{{
                                                category.description || 'Chưa có mô tả' }}</p>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span
                                                class="rounded bg-slate-100 px-2 py-0.5 font-mono text-[11px] text-slate-600">/{{
                                                    category.slug }}</span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-slate-500">
                                            {{ category.parent_id ? categoryName(category.parent_id) : '— (Gốc)' }}
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="font-bold text-indigo-600 tabular-nums">{{
                                                category.articles_count ?? categoryCount(category.id) }} bài</span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap font-medium text-slate-600 tabular-nums">
                                            {{ category.sort_order }}
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <button @click="handleToggleCategoryStatus(category)" :class="[
                                                'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold transition hover:opacity-80',
                                                category.is_active
                                                    ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/60'
                                                    : 'bg-slate-100 text-slate-600 ring-1 ring-slate-200/60'
                                            ]">
                                                <span class="size-1.5 rounded-full"
                                                    :class="category.is_active ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                                {{ category.is_active ? 'Đang hiển thị' : 'Đã ẩn' }}
                                            </button>
                                        </td>
                                        <td class="px-6 py-4 text-end whitespace-nowrap">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <button @click="openCategoryForm(category)" title="Sửa danh mục"
                                                    class="flex size-8 items-center justify-center rounded-lg text-slate-500 transition hover:bg-indigo-50 hover:text-indigo-600">
                                                    <svg class="size-4" viewBox="0 0 24 24" fill="none"
                                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round">
                                                        <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
                                                    </svg>
                                                </button>
                                                <button @click="askDelete('category', category)" title="Xóa danh mục"
                                                    class="flex size-8 items-center justify-center rounded-lg text-slate-500 transition hover:bg-rose-50 hover:text-rose-600">
                                                    <svg class="size-4" viewBox="0 0 24 24" fill="none"
                                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round">
                                                        <path
                                                            d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr v-if="isCategoriesLoading">
                                        <td colspan="7" class="py-14 text-center text-slate-400">
                                            <div class="inline-flex items-center gap-2 font-medium">
                                                <svg class="size-4 animate-spin text-sky-500" viewBox="0 0 24 24"
                                                    fill="none">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                                        stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor"
                                                        d="M4 12a8 8 0 018-8v8H4z"></path>
                                                </svg>
                                                Đang tải dữ liệu danh mục...
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-else-if="!filteredCategories.length">
                                        <td colspan="7" class="py-14 text-center text-slate-400">
                                            Chưa có danh mục nào phù hợp với tìm kiếm.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </template>

                <!-- ================= SECTION 4: QUẢN LÝ NGƯỜI DÙNG (USERS) ================= -->
                <template v-else-if="activeSection === 'users'">
                    <section class="rounded-2xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">
                        <div
                            class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <div>
                                <h3 class="font-display text-base font-bold text-slate-900">Danh sách tài khoản</h3>
                                <p class="text-xs text-slate-400">{{ users.length }} tài khoản trong hệ thống · {{
                                    activeUserCount }} đang hoạt động</p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2.5">
                                <!-- Nút Làm mới danh sách -->
                                <button @click="fetchUsers" :disabled="isUsersLoading"
                                    title="Tải lại danh sách người dùng từ máy chủ"
                                    class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:opacity-50">
                                    <svg class="size-3.5" :class="{ 'animate-spin text-indigo-600': isUsersLoading }"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
                                        <path d="M3 3v5h5" />
                                        <path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16" />
                                        <path d="M16 21h5v-5" />
                                    </svg>
                                    <span class="hidden sm:inline">Làm mới</span>
                                </button>

                                <!-- Ô tìm kiếm -->
                                <div class="relative w-52 sm:w-56">
                                    <span
                                        class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-slate-400">
                                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2">
                                            <circle cx="11" cy="11" r="8" />
                                            <path d="m21 21-4.35-4.35" />
                                        </svg>
                                    </span>
                                    <input v-model="search" type="text"
                                        class="h-9 w-full rounded-xl border border-slate-200 bg-slate-50/60 ps-8.5 pe-3 text-xs placeholder-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-100"
                                        placeholder="Tìm tên, email..." />
                                </div>

                                <!-- Lọc theo vai trò -->
                                <select v-model="selectedRole"
                                    class="h-9 rounded-xl border border-slate-200 bg-slate-50/60 px-3 text-xs font-medium text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                                    <option value="all">Tất cả vai trò</option>
                                    <option value="admin">Admin</option>
                                    <option value="editor">Editor</option>
                                    <option value="user">User</option>
                                </select>
                            </div>
                        </div>

                        <!-- Trạng thái loading -->
                        <div v-if="isUsersLoading && !users.length" class="p-12 text-center text-slate-400">
                            <div class="inline-flex items-center gap-2 text-xs font-semibold text-indigo-600">
                                <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.5">
                                    <circle cx="12" cy="12" r="10" stroke-opacity="0.25" />
                                    <path d="M12 2a10 10 0 0 1 10 10" />
                                </svg>
                                <span>Đang tải danh sách người dùng từ hệ thống...</span>
                            </div>
                        </div>

                        <!-- Bảng dữ liệu người dùng -->
                        <div v-else class="overflow-x-auto">
                            <table class="w-full text-start text-xs">
                                <thead>
                                    <tr
                                        class="border-b border-slate-100 bg-slate-50/70 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                        <th class="px-6 py-3.5 text-start">Thành viên</th>
                                        <th class="px-4 py-3.5 text-start">Vai trò</th>
                                        <th class="px-4 py-3.5 text-start">Trạng thái</th>
                                        <th class="px-4 py-3.5 text-start">Ngày tham gia</th>
                                        <th class="px-4 py-3.5 text-center">Thao tác</th>
                                        <th class="px-6 py-3.5 text-end">Mã định danh</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="user in filteredUsers" :key="user.id"
                                        class="transition hover:bg-slate-50/80">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="flex size-9.5 items-center justify-center rounded-xl bg-slate-100 font-bold text-slate-700 border border-slate-200">
                                                    {{user.name.split(' ').map((p) => p[0]).slice(-2).join('')}}
                                                </div>
                                                <div>
                                                    <div class="flex items-center gap-1.5">
                                                        <p class="font-bold text-slate-900">{{ user.name }}</p>
                                                        <span v-if="user.id === currentUser?.id"
                                                            class="rounded bg-indigo-50 px-1.5 py-0.2 text-[9px] font-bold text-indigo-600 ring-1 ring-indigo-200/50">
                                                            Bạn
                                                        </span>
                                                    </div>
                                                    <p class="text-[11px] text-slate-400">{{ user.email }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <!-- Thay đổi vai trò qua dropdown gọn gàng -->
                                            <select :value="user.role" :disabled="user.id === currentUser?.id"
                                                @change="handleRoleChange(user, $event.target.value)" :class="[
                                                    'h-7 rounded-lg px-2 py-0.5 text-[11px] font-bold border-0 cursor-pointer capitalize focus:ring-2 focus:ring-indigo-300 disabled:opacity-60 disabled:cursor-not-allowed',
                                                    roleBadgeClasses(user.role)
                                                ]">
                                                <option value="user">User</option>
                                                <option value="editor">Editor</option>
                                                <option value="admin">Admin</option>
                                            </select>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <button @click="handleToggleUserStatus(user)"
                                                :disabled="user.id === currentUser?.id"
                                                :title="user.id === currentUser?.id ? 'Không thể tự khóa tài khoản của bạn' : (user.is_active ? 'Nhấp để khóa tài khoản' : 'Nhấp để kích hoạt tài khoản')"
                                                :class="[
                                                    'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold transition hover:opacity-80 disabled:opacity-60 disabled:cursor-not-allowed',
                                                    user.is_active
                                                        ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/60'
                                                        : 'bg-rose-50 text-rose-700 ring-1 ring-rose-200/60'
                                                ]">
                                                <span class="size-1.5 rounded-full"
                                                    :class="user.is_active ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                                {{ user.is_active ? 'Hoạt động' : 'Đã khóa' }}
                                            </button>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-slate-500 text-[11px]">
                                            {{ formatDateShort(user.created_at) }}
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-center">
                                            <button v-if="user.id !== currentUser?.id" @click="askDelete('user', user)"
                                                title="Xóa tài khoản người dùng"
                                                class="inline-flex size-7 items-center justify-center rounded-lg text-slate-400 hover:bg-rose-50 hover:text-rose-600 transition">
                                                <svg class="size-3.5" viewBox="0 0 24 24" fill="none"
                                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <path
                                                        d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                                </svg>
                                            </button>
                                            <span v-else class="text-[11px] text-slate-400 italic">—</span>
                                        </td>
                                        <td class="px-6 py-4 text-end font-mono text-[11px] text-slate-400">
                                            #US-{{ String(user.id).padStart(3, '0') }}
                                        </td>
                                    </tr>

                                    <tr v-if="!filteredUsers.length">
                                        <td colspan="6" class="py-14 text-center text-slate-400">
                                            Không tìm thấy tài khoản người dùng phù hợp.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </template>
            </main>
        </div>

        <!-- ================= MODAL THÊM TÀI KHOẢN NGƯỜI DÙNG ================= -->
        <Transition name="fade">
            <div v-if="userModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
                @click.self="userModal = false">
                <form class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl border border-slate-100"
                    @submit.prevent="saveUser">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4.5 bg-slate-50/50">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600">Phân quyền hệ
                                thống</span>
                            <h3 class="font-display text-lg font-bold text-slate-900">Thêm người dùng mới</h3>
                        </div>
                        <button type="button" @click="userModal = false"
                            class="flex size-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                            ✕
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Họ và tên <span
                                    class="text-rose-500">*</span></label>
                            <input v-model.trim="userForm.name" required maxlength="100"
                                class="h-10 w-full rounded-xl border border-slate-200 px-3 text-xs placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                                placeholder="Ví dụ: Nguyễn Văn A" />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Địa chỉ Email <span
                                    class="text-rose-500">*</span></label>
                            <input v-model.trim="userForm.email" type="email" required maxlength="191"
                                class="h-10 w-full rounded-xl border border-slate-200 px-3 text-xs placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                                placeholder="name@example.com" />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Mật khẩu khởi tạo <span
                                    class="text-rose-500">*</span></label>
                            <input v-model="userForm.password" type="password" required minlength="6"
                                class="h-10 w-full rounded-xl border border-slate-200 px-3 text-xs placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                                placeholder="Tối thiểu 6 ký tự..." />
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Vai trò (Role) <span
                                        class="text-rose-500">*</span></label>
                                <select v-model="userForm.role" required
                                    class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                                    <option value="user">Người dùng (User)</option>
                                    <option value="editor">Biên tập viên (Editor)</option>
                                    <option value="admin">Quản trị viên (Admin)</option>
                                </select>
                            </div>

                            <div class="flex items-center gap-2 pt-6">
                                <label class="relative inline-flex cursor-pointer items-center">
                                    <input v-model="userForm.is_active" type="checkbox" class="peer sr-only" />
                                    <div
                                        class="peer h-5 w-9 rounded-full bg-slate-200 after:absolute after:start-[2px] after:top-[2px] after:size-4 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-emerald-500 peer-checked:after:translate-x-full">
                                    </div>
                                    <span class="ms-2.5 text-xs font-semibold text-slate-700">Kích hoạt tài khoản
                                        ngay</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div
                        class="flex items-center justify-end gap-2.5 border-t border-slate-100 bg-slate-50/50 px-6 py-4">
                        <button type="button" @click="userModal = false"
                            class="h-9.5 rounded-xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                            Hủy bỏ
                        </button>
                        <button type="submit"
                            class="h-9.5 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-700 px-5 text-xs font-bold text-white shadow-md shadow-indigo-600/25 hover:brightness-110 transition">
                            Tạo tài khoản
                        </button>
                    </div>
                </form>
            </div>
        </Transition>

        <!-- ================= MODAL THÊM / SỬA DANH MỤC ================= -->
        <Transition name="fade">
            <div v-if="categoryModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
                @click.self="categoryModal = false">
                <form class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl border border-slate-100"
                    @submit.prevent="saveCategory">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4.5 bg-slate-50/50">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600">Cấu trúc chuyên
                                mục</span>
                            <h3 class="font-display text-lg font-bold text-slate-900">{{ categoryFormTitle }}</h3>
                        </div>
                        <button type="button" @click="categoryModal = false"
                            class="flex size-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                            ✕
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tên danh mục <span
                                        class="text-rose-500">*</span></label>
                                <input v-model.trim="categoryForm.name" required maxlength="100"
                                    @input="!editingId && (categoryForm.slug = makeSlug(categoryForm.name))"
                                    class="h-10 w-full rounded-xl border border-slate-200 px-3 text-xs placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                                    placeholder="VD: Kinh tế số" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Slug định danh <span
                                        class="text-rose-500">*</span></label>
                                <input v-model.trim="categoryForm.slug" required maxlength="120"
                                    class="h-10 w-full rounded-xl border border-slate-200 px-3 font-mono text-xs placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                                    placeholder="kinh-te-so" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Danh mục cha</label>
                                <select v-model="categoryForm.parent_id"
                                    class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                                    <option value="">Không có (Danh mục gốc)</option>
                                    <option v-for="cat in categories.filter((c) => c.id !== editingId)" :key="cat.id"
                                        :value="String(cat.id)">
                                        {{ cat.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Thứ tự hiển thị</label>
                                <input v-model.number="categoryForm.sort_order" type="number" min="0"
                                    class="h-10 w-full rounded-xl border border-slate-200 px-3 text-xs focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Mô tả danh mục</label>
                            <div
                                class="quill-wrapper rounded-xl border border-slate-200 overflow-hidden bg-white focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-100">
                                <QuillEditor v-model:content="categoryForm.description" contentType="html" theme="snow"
                                    :toolbar="[['bold', 'italic', 'underline'], [{ 'list': 'ordered' }, { 'list': 'bullet' }], ['clean']]"
                                    placeholder="Mô tả ngắn gọn về chủ đề bài viết thuộc danh mục này..."
                                    style="min-height: 80px;" />
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-1">
                            <input v-model="categoryForm.is_active" type="checkbox" id="cat-active"
                                class="size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                            <label for="cat-active" class="text-xs font-semibold text-slate-700 cursor-pointer">
                                Cho phép hiển thị trên thanh điều hướng tin tức
                            </label>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div
                        class="flex items-center justify-end gap-2.5 border-t border-slate-100 bg-slate-50/50 px-6 py-4">
                        <button type="button" @click="categoryModal = false"
                            class="h-9.5 rounded-xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                            Hủy bỏ
                        </button>
                        <button type="submit"
                            class="h-9.5 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-700 px-5 text-xs font-bold text-white shadow-md shadow-indigo-600/25 hover:brightness-110 transition">
                            {{ editingId ? 'Lưu thay đổi' : 'Tạo danh mục' }}
                        </button>
                    </div>
                </form>
            </div>
        </Transition>

        <!-- ================= MODAL THÊM / SỬA BÀI BÁO ================= -->
        <Transition name="fade">
            <div v-if="articleModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
                @click.self="articleModal = false">
                <form
                    class="flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl border border-slate-100"
                    @submit.prevent="saveArticle">
                    <!-- Modal Header -->
                    <div
                        class="flex items-center justify-between border-b border-slate-100 px-6 py-4.5 bg-slate-50/50 shrink-0">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600">Biên tập nội
                                dung</span>
                            <h3 class="font-display text-lg font-bold text-slate-900">{{ articleFormTitle }}</h3>
                        </div>
                        <button type="button" @click="articleModal = false"
                            class="flex size-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                            ✕
                        </button>
                    </div>

                    <!-- Modal Body (Scrollable) -->
                    <div class="flex-1 overflow-y-auto p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tiêu đề bài báo <span
                                    class="text-rose-500">*</span></label>
                            <input v-model.trim="articleForm.title" required maxlength="500"
                                @input="!editingId && (articleForm.slug = makeSlug(articleForm.title))"
                                class="h-10 w-full rounded-xl border border-slate-200 px-3 text-xs placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                                placeholder="Nhập tiêu đề tin tức nổi bật..." />
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Slug đường dẫn <span
                                        class="text-rose-500">*</span></label>
                                <input v-model.trim="articleForm.slug" required maxlength="520"
                                    class="h-10 w-full rounded-xl border border-slate-200 px-3 font-mono text-xs placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                                    placeholder="tieu-de-bai-bao" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tác giả biên
                                    soạn</label>
                                <input v-model.trim="articleForm.author" maxlength="150"
                                    class="h-10 w-full rounded-xl border border-slate-200 px-3 text-xs placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                                    placeholder="VD: Ban Biên Tập ReadsNews" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nguồn báo đối tác <span
                                        class="text-rose-500">*</span></label>
                                <select v-model="articleForm.source_id" required
                                    class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                                    <option value="">Chọn nguồn gốc tin</option>
                                    <option v-for="source in sources" :key="source.id" :value="String(source.id)">{{
                                        source.name
                                    }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Chuyên mục <span
                                        class="text-rose-500">*</span></label>
                                <select v-model="articleForm.category_id" required
                                    class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                                    <option value="">Chọn danh mục</option>
                                    <option v-for="category in categories" :key="category.id"
                                        :value="String(category.id)">{{
                                            category.name }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">URL bài viết gốc <span
                                        class="text-rose-500">*</span></label>
                                <input v-model.trim="articleForm.original_url" type="url" required maxlength="700"
                                    class="h-10 w-full rounded-xl border border-slate-200 px-3 text-xs placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                                    placeholder="https://vnexpress.net/..." />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Ảnh Thumbnail</label>
                                <!-- Preview -->
                                <div v-if="thumbnailPreview"
                                    class="mb-2 relative w-full h-32 overflow-hidden rounded-xl border border-slate-200 bg-slate-100">
                                    <img :src="thumbnailPreview" class="size-full object-cover" alt="preview" />
                                    <button type="button" @click="removeThumbnail"
                                        class="absolute top-1 right-1 flex size-6 items-center justify-center rounded-full bg-rose-600 text-white text-xs hover:bg-rose-700">✕</button>
                                </div>
                                <!-- File input -->
                                <label
                                    class="flex cursor-pointer items-center gap-2 rounded-xl border-2 border-dashed border-slate-200 px-3 py-2.5 text-xs text-slate-500 hover:border-indigo-400 hover:text-indigo-600 transition">
                                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                        <polyline points="17 8 12 3 7 8" />
                                        <line x1="12" y1="3" x2="12" y2="15" />
                                    </svg>
                                    <span class="truncate">{{ thumbnailFile ? thumbnailFile.name : 'Chọn ảnh (JPG, PNG, WebP · tối đa 5MB)' }}</span>
                                    <input ref="thumbnailInputRef" type="file"
                                        accept="image/jpeg,image/png,image/webp" @change="onThumbnailChange"
                                        class="sr-only" />
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Mô tả ngắn (Lead)</label>
                            <div
                                class="quill-wrapper rounded-xl border border-slate-200 overflow-hidden bg-white focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-100">
                                <QuillEditor v-model:content="articleForm.summary" contentType="html" theme="snow"
                                    :toolbar="[['bold', 'italic', 'underline'], ['clean']]"
                                    placeholder="Tóm tắt ngắn 1-2 câu hiển thị ở danh sách bài viết..."
                                    style="min-height: 80px;" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nội dung bài viết đầy
                                đủ</label>
                            <div
                                class="quill-wrapper rounded-xl border border-slate-200 overflow-hidden bg-white focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-100">
                                <QuillEditor v-model:content="articleForm.content" contentType="html" theme="snow"
                                    :toolbar="[
                                        [{ 'header': [1, 2, 3, false] }],
                                        ['bold', 'italic', 'underline', 'strike'],
                                        [{ 'color': [] }, { 'background': [] }],
                                        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                                        [{ 'align': [] }],
                                        ['blockquote', 'code-block'],
                                        ['link', 'image'],
                                        ['clean']
                                    ]" placeholder="Soạn thảo hoặc dán nội dung chi tiết bài báo tại đây..."
                                    style="min-height: 220px;" />
                            </div>
                        </div>

                        <!-- Cấu hình Trí Tuệ Nhân Tạo AI & TTS Audio -->
                        <div class="rounded-xl border border-indigo-100 bg-indigo-50/40 p-4 space-y-3">
                            <div class="flex items-center gap-2">
                                <span
                                    class="flex size-6 items-center justify-center rounded-lg bg-indigo-600 text-white text-[11px] font-bold">AI</span>
                                <h4 class="text-xs font-bold text-indigo-950">Tích hợp Trí Tuệ Nhân Tạo & Giọng đọc TTS
                                </h4>
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Tóm tắt tự động bằng
                                    AI (3
                                    câu cốt lõi)</label>
                                <div
                                    class="quill-wrapper rounded-lg border border-slate-200 overflow-hidden bg-white focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-100">
                                    <QuillEditor v-model:content="articleForm.ai_summary" contentType="html"
                                        theme="snow" :toolbar="[['bold', 'italic'], [{ 'list': 'bullet' }], ['clean']]"
                                        placeholder="Nội dung tóm tắt súc tích do AI phân tích để độc giả đọc nhanh trong 30 giây..."
                                        style="min-height: 75px;" />
                                </div>
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Văn bản chuẩn hóa cho
                                    TTS
                                    Audio</label>
                                <div
                                    class="quill-wrapper rounded-lg border border-slate-200 overflow-hidden bg-white focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-100">
                                    <QuillEditor v-model:content="articleForm.content_tts" contentType="html"
                                        theme="snow" :toolbar="[['bold', 'italic'], ['clean']]"
                                        placeholder="Văn bản đã qua tiền xử lý dấu câu để giọng đọc AI phát âm tự nhiên nhất..."
                                        style="min-height: 75px;" />
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Thời điểm xuất
                                    bản</label>
                                <input v-model="articleForm.published_at" type="datetime-local"
                                    class="h-10 w-full rounded-xl border border-slate-200 px-3 text-xs text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100" />
                                <p class="mt-1 text-[10px] text-slate-400">Để trống nếu muốn lưu ở chế độ Bản nháp.</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Số lượng từ ước
                                    tính</label>
                                <input v-model.number="articleForm.word_count" type="number" min="0"
                                    class="h-10 w-full rounded-xl border border-slate-200 px-3 text-xs focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100" />
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div
                        class="flex items-center justify-end gap-2.5 border-t border-slate-100 bg-slate-50/50 px-6 py-4 shrink-0">
                        <button type="button" @click="articleModal = false"
                            class="h-9.5 rounded-xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                            Hủy bỏ
                        </button>
                        <button type="submit"
                            class="h-9.5 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-700 px-5 text-xs font-bold text-white shadow-md shadow-indigo-600/25 hover:brightness-110 transition">
                            {{ editingId ? 'Lưu thay đổi bài báo' : 'Thêm bài báo mới' }}
                        </button>
                    </div>
                </form>
            </div>
        </Transition>

        <!-- ================= MODAL XÁC NHẬN XÓA ================= -->
        <Transition name="fade">
            <div v-if="deleteTarget"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
                @click.self="deleteTarget = null">
                <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-slate-100 text-center">
                    <div
                        class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 ring-8 ring-rose-50/50">
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path
                                d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                            <line x1="10" y1="11" x2="10" y2="17" />
                            <line x1="14" y1="11" x2="14" y2="17" />
                        </svg>
                    </div>
                    <h3 class="mt-4 font-display text-base font-bold text-slate-900">Xác nhận xóa dữ liệu</h3>
                    <p class="mt-1.5 text-xs text-slate-500 leading-relaxed">
                        Bạn có chắc chắn muốn xóa mục <strong class="text-slate-800">"{{ deleteTarget.record.name ||
                            deleteTarget.record.title }}"</strong>? Thao tác này sẽ loại bỏ dữ liệu khỏi hệ thống.
                    </p>
                    <div class="mt-6 flex items-center justify-center gap-3">
                        <button @click="deleteTarget = null"
                            class="h-9.5 rounded-xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                            Hủy bỏ
                        </button>
                        <button @click="confirmDelete"
                            class="h-9.5 rounded-xl bg-rose-600 px-5 text-xs font-bold text-white shadow-md shadow-rose-600/25 hover:bg-rose-700 transition">
                            Xác nhận xóa
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- ================= THÔNG BÁO FLASH TOAST ================= -->
        <Transition name="notice">
            <div v-if="flashMessage"
                class="fixed bottom-6 end-6 z-50 flex items-center gap-2.5 rounded-2xl border border-indigo-100 bg-slate-900 px-4.5 py-3 text-xs font-semibold text-white shadow-2xl shadow-slate-900/40">
                <span
                    class="flex size-5 items-center justify-center rounded-full bg-indigo-500 text-[11px] text-white">✓</span>
                <span>{{ flashMessage }}</span>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: scale(0.97);
}

.notice-enter-active,
.notice-leave-active {
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.notice-enter-from,
.notice-leave-to {
    opacity: 0;
    transform: translateY(12px);
}

/* ─── Tùy biến Quill Editor hiện đại ────────────────────────────────────────── */
:deep(.quill-wrapper .ql-toolbar.ql-snow) {
    border: none;
    border-bottom: 1px solid #f1f5f9;
    background-color: #f8fafc;
    padding: 6px 10px;
    border-top-left-radius: 0.75rem;
    border-top-right-radius: 0.75rem;
}

:deep(.quill-wrapper .ql-container.ql-snow) {
    border: none;
    font-size: 0.8125rem;
    font-family: inherit;
    color: #1e293b;
    border-bottom-left-radius: 0.75rem;
    border-bottom-right-radius: 0.75rem;
}

:deep(.quill-wrapper .ql-editor) {
    min-height: inherit;
    padding: 10px 14px;
    line-height: 1.6;
}

:deep(.quill-wrapper .ql-editor.ql-blank::before) {
    font-style: normal;
    color: #94a3b8;
    font-size: 0.8125rem;
    left: 14px;
}

:deep(.quill-wrapper .ql-snow .ql-picker) {
    font-size: 0.75rem;
}

:deep(.quill-wrapper .ql-snow .ql-stroke) {
    stroke: #64748b;
}

:deep(.quill-wrapper .ql-snow .ql-fill) {
    fill: #64748b;
}

:deep(.quill-wrapper .ql-snow button:hover .ql-stroke),
:deep(.quill-wrapper .ql-snow .ql-picker:hover .ql-stroke),
:deep(.quill-wrapper .ql-snow button.ql-active .ql-stroke) {
    stroke: #4f46e5;
}

:deep(.quill-wrapper .ql-snow button:hover .ql-fill),
:deep(.quill-wrapper .ql-snow .ql-picker:hover .ql-fill),
:deep(.quill-wrapper .ql-snow button.ql-active .ql-fill) {
    fill: #4f46e5;
}
</style>
