<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { auth } from '@/utils/auth'
import { userService } from '@/services/userService'
import { categoryService } from '@/services/categoryService'
import { articleService, sourceService } from '@/services/articleService'

// Import các component con đã tách
import AdminSidebar from './admin/AdminSidebar.vue'
import AdminHeader from './admin/AdminHeader.vue'
import AdminOverview from './admin/AdminOverview.vue'
import ArticlesTable from './admin/ArticlesTable.vue'
import CategoriesTable from './admin/CategoriesTable.vue'
import UsersTable from './admin/UsersTable.vue'
import UserFormModal from './admin/UserFormModal.vue'
import CategoryFormModal from './admin/CategoryFormModal.vue'
import ArticleFormModal from './admin/ArticleFormModal.vue'
import DeleteConfirmModal from './admin/DeleteConfirmModal.vue'
import FlashToast from './admin/FlashToast.vue'

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
const userModal = ref(false)
const deleteTarget = ref(null)
const editingId = ref(null)
const flashMessage = ref('')
let flashTimer

// Danh sách ID được chọn để thao tác hàng loạt
const selectedArticles = ref([])
const selectedCategories = ref([])
const selectedUsers = ref([])

// Lấy thông tin tài khoản hiện tại từ auth utils hoặc fallback mặc định
const currentUser = computed(() => {
    return auth.getUser() || { id: null, name: 'Admin ReadsNews', email: 'admin@readsnews.vn', role: 'admin' }
})

// Quản lý danh mục thực tế từ Database
const categories = ref([])
const allCategories = ref([])
const isCategoriesLoading = ref(false)
const categoriesMeta = ref({ total: 0, per_page: 10, current_page: 1, last_page: 1 })

// Nguồn báo từ Database
const sources = ref([])

// Quản lý người dùng thực tế từ Database
const users = ref([])
const isUsersLoading = ref(false)
const usersMeta = ref({ total: 0, per_page: 10, current_page: 1, last_page: 1 })
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

function emptyCategory() {
    return {
        parent_id: '',
        name: '',
        slug: '',
        description: '',
        sort_order: (allCategories.value?.length || categories.value?.length || 0) + 1,
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

function notify(message) {
    flashMessage.value = message
    window.clearTimeout(flashTimer)
    flashTimer = window.setTimeout(() => { flashMessage.value = '' }, 3000)
}

let searchDebounceTimer = null
watch([search, selectedCategory, selectedStatus, selectedRole], () => {
    clearTimeout(searchDebounceTimer)
    searchDebounceTimer = setTimeout(() => {
        if (activeSection.value === 'articles') {
            fetchArticles(1)
        } else if (activeSection.value === 'categories') {
            fetchCategories(1)
        } else if (activeSection.value === 'users') {
            fetchUsers(1)
        }
    }, 300)
})

function setSection(section) {
    activeSection.value = section
    search.value = ''
    selectedCategory.value = 'all'
    selectedStatus.value = 'all'
    selectedRole.value = 'all'
    selectedArticles.value = []
    selectedCategories.value = []
    selectedUsers.value = []
    if (section === 'articles') {
        fetchArticles(1)
    } else if (section === 'categories') {
        fetchCategories(1)
    } else if (section === 'users') {
        fetchUsers(1)
    }
}

function askBulkDelete(section) {
    let ids = []
    let type = ''
    if (section === 'articles') {
        ids = [...selectedArticles.value]
        type = 'bulk-articles'
    } else if (section === 'categories') {
        ids = [...selectedCategories.value]
        type = 'bulk-categories'
    } else if (section === 'users') {
        ids = [...selectedUsers.value]
        type = 'bulk-users'
    }

    if (!ids.length) return

    deleteTarget.value = {
        type,
        count: ids.length,
        ids
    }
}

function openCategoryForm(category = null) {
    editingId.value = category?.id ?? null
    categoryForm.value = category ? { ...category, parent_id: category.parent_id ?? '', description: category.description ?? '' } : emptyCategory()
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
            if (res?.success) {
                notify(res.message || 'Đã cập nhật danh mục thành công!')
                categoryModal.value = false
                await fetchCategories(categoriesMeta.value.current_page)
                await fetchAllCategories()
            }
        } else {
            const res = await categoryService.createCategory(record)
            if (res?.success) {
                notify(res.message || 'Đã thêm danh mục mới thành công!')
                categoryModal.value = false
                await fetchCategories(1)
                await fetchAllCategories()
            }
        }
    } catch (err) {
        notify(err.response?.data?.message || 'Có lỗi xảy ra khi lưu danh mục.')
        console.error('Lỗi khi lưu danh mục:', err)
    }
}

async function openArticleForm(article = null) {
    editingId.value = article?.id ?? null
    thumbnailFile.value = null

    if (article) {
        articleForm.value = {
            ...emptyArticle(),
            ...article,
            summary: article.summary ?? '',
            content: article.content ?? '',
            ai_summary: article.ai_summary ?? '',
            content_tts: article.content_tts ?? '',
            original_url: article.original_url ?? '',
            author: article.author ?? '',
            source_id: article.source_id ? String(article.source_id) : '',
            category_id: article.category_id ? String(article.category_id) : '',
            published_at: article.published_at ? article.published_at.slice(0, 16) : ''
        }
        thumbnailPreview.value = article.thumbnail || null
        articleModal.value = true

        // Nếu thiếu content, fetch thêm từ server để hiển thị đầy đủ
        if (!article.content) {
            try {
                const res = await articleService.getArticle(article.id)
                if (res?.data) {
                    const full = res.data
                    articleForm.value = {
                        ...articleForm.value,
                        ...full,
                        summary: full.summary ?? articleForm.value.summary,
                        content: full.content ?? articleForm.value.content,
                        ai_summary: full.ai_summary ?? articleForm.value.ai_summary,
                        content_tts: full.content_tts ?? articleForm.value.content_tts,
                        original_url: full.original_url ?? articleForm.value.original_url,
                        author: full.author ?? articleForm.value.author,
                        source_id: full.source_id ? String(full.source_id) : articleForm.value.source_id,
                        category_id: full.category_id ? String(full.category_id) : articleForm.value.category_id,
                        published_at: full.published_at ? full.published_at.slice(0, 16) : articleForm.value.published_at
                    }
                    if (!thumbnailFile.value && full.thumbnail) {
                        thumbnailPreview.value = full.thumbnail
                    }
                }
            } catch (err) {
                console.error('Lỗi khi tải chi tiết bài viết:', err)
            }
        }
    } else {
        articleForm.value = emptyArticle()
        thumbnailPreview.value = null
        articleModal.value = true
    }
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
    const { type, record, ids, count } = deleteTarget.value
    if (type === 'article') {
        try {
            await articleService.deleteArticle(record.id)
            selectedArticles.value = selectedArticles.value.filter(id => id !== record.id)
            notify('Đã xóa bài báo thành công.')
            await fetchArticles(articlesMeta.value.current_page)
        } catch (err) {
            notify(err.response?.data?.message || 'Lỗi khi xóa bài báo.')
        }
    } else if (type === 'category') {
        try {
            await categoryService.deleteCategory(record.id)
            selectedCategories.value = selectedCategories.value.filter(id => id !== record.id)
            notify(`Đã xóa danh mục "${record.name}" thành công.`)
            await fetchCategories(categoriesMeta.value.current_page)
            await fetchAllCategories()
        } catch (err) {
            notify(err.response?.data?.message || 'Lỗi khi xóa danh mục.')
            console.error('Lỗi khi xóa danh mục:', err)
        }
    } else if (type === 'user') {
        try {
            await userService.deleteUser(record.id)
            selectedUsers.value = selectedUsers.value.filter(id => id !== record.id)
            notify(`Đã xóa tài khoản ${record.name} thành công.`)
            await fetchUsers(usersMeta.value.current_page)
        } catch (err) {
            console.error('Lỗi khi xóa người dùng:', err)
        }
    } else if (type === 'bulk-articles') {
        try {
            const res = await articleService.bulkDeleteArticles(ids)
            notify(res.message || `Đã xóa ${count} bài báo thành công.`)
            selectedArticles.value = []
            await fetchArticles(articlesMeta.value.current_page)
        } catch (err) {
            notify(err.response?.data?.message || 'Lỗi khi xóa các bài báo đã chọn.')
        }
    } else if (type === 'bulk-categories') {
        try {
            const res = await categoryService.bulkDeleteCategories(ids)
            notify(res.message || `Đã xóa ${count} danh mục thành công.`)
            selectedCategories.value = []
            await fetchCategories(categoriesMeta.value.current_page)
            await fetchAllCategories()
        } catch (err) {
            notify(err.response?.data?.message || 'Lỗi khi xóa các danh mục đã chọn.')
        }
    } else if (type === 'bulk-users') {
        try {
            const res = await userService.bulkDeleteUsers(ids)
            notify(res.message || `Đã xóa ${count} người dùng thành công.`)
            selectedUsers.value = []
            await fetchUsers(usersMeta.value.current_page)
        } catch (err) {
            notify(err.response?.data?.message || 'Lỗi khi xóa các người dùng đã chọn.')
        }
    }
    deleteTarget.value = null
}

// ─── API Bài Báo (Articles) ──────────────────────────────────────────────────
async function fetchArticles(page = 1, params = {}) {
    isArticlesLoading.value = true
    try {
        const queryParams = {
            per_page: 10,
            page,
            search: search.value.trim() || undefined,
            category_id: selectedCategory.value !== 'all' ? selectedCategory.value : undefined,
            status: selectedStatus.value !== 'all' ? selectedStatus.value : undefined,
            ...params
        }
        const res = await articleService.getArticles(queryParams)
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
async function fetchUsers(page = 1, params = {}) {
    isUsersLoading.value = true
    try {
        const queryParams = {
            per_page: 10,
            page,
            search: search.value.trim() || undefined,
            role: selectedRole.value !== 'all' ? selectedRole.value : undefined,
            status: selectedStatus.value !== 'all' ? selectedStatus.value : undefined,
            ...params
        }
        const res = await userService.getUsers(queryParams)
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

async function handleRoleChange({ user, role: newRole }) {
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
            await fetchUsers(1)
        }
    } catch (err) { console.error('Lỗi tạo người dùng:', err) }
}

// ─── API Danh Mục (Categories) ────────────────────────────────────────────────
async function fetchCategories(page = 1, params = {}) {
    isCategoriesLoading.value = true
    try {
        const queryParams = {
            per_page: 10,
            page,
            search: search.value.trim() || undefined,
            status: selectedStatus.value !== 'all' ? selectedStatus.value : undefined,
            ...params
        }
        const res = await categoryService.getCategories(queryParams)
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

async function fetchAllCategories() {
    try {
        const res = await categoryService.getCategories({ all: 1 })
        if (res?.success) {
            allCategories.value = res.data || []
        }
    } catch (err) {
        console.error('Lỗi khi tải toàn bộ danh mục:', err)
    }
}

async function handleToggleCategoryStatus(category) {
    try {
        const res = await categoryService.toggleStatus(category.id)
        if (res?.success) {
            category.is_active = res.data.is_active
            notify(res.message || 'Cập nhật trạng thái danh mục thành công!')
            await fetchAllCategories()
        }
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

onMounted(() => {
    fetchUsers(1)
    fetchCategories(1)
    fetchAllCategories()
    fetchArticles(1)
    fetchSources()
})

onBeforeUnmount(() => window.clearTimeout(flashTimer))
</script>

<template>
    <div
        class="min-h-screen bg-slate-50/70 font-sans text-slate-800 antialiased selection:bg-sky-500 selection:text-white">
        <!-- Sidebar Cố Định (Desktop) -->
        <AdminSidebar :active-section="activeSection" :sections="sections" :articles-count="articles.length"
            :current-user="currentUser" @select-section="setSection" @logout="handleLogout" />

        <!-- Khung Nội Dung Chính -->
        <div class="lg:ps-64 flex flex-col min-h-screen">
            <!-- Topbar / Header -->
            <AdminHeader :page-title="pageTitle" v-model:search-query="search" :current-user="currentUser"
                :sections="sections" :active-section="activeSection" @select-section="setSection" />

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
                            class="inline-flex h-10 items-center gap-2 rounded-xl bg-gradient-to-r from-sky-600 to-blue-700 px-4 text-xs font-bold text-white shadow-md shadow-sky-600/25 transition hover:brightness-110 active:scale-98 cursor-pointer">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 5v14M5 12h14" />
                            </svg>
                            <span>Thêm bài báo mới</span>
                        </button>

                        <button v-if="activeSection === 'categories'" @click="openCategoryForm()"
                            class="inline-flex h-10 items-center gap-2 rounded-xl bg-gradient-to-r from-sky-600 to-blue-700 px-4 text-xs font-bold text-white shadow-md shadow-sky-600/25 transition hover:brightness-110 active:scale-98 cursor-pointer">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 5v14M5 12h14" />
                            </svg>
                            <span>Thêm danh mục mới</span>
                        </button>

                        <button v-if="activeSection === 'users'" @click="openUserModal()"
                            class="inline-flex h-10 items-center gap-2 rounded-xl bg-gradient-to-r from-orange-500 to-amber-600 px-4 text-xs font-bold text-white shadow-md shadow-orange-500/25 transition hover:brightness-110 active:scale-98 cursor-pointer">
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

                <!-- SECTION 1: TỔNG QUAN (OVERVIEW) -->
                <AdminOverview v-if="activeSection === 'overview'" :users="users" :articles="articles"
                    :categories="categories" :all-categories="allCategories" :sources="sources"
                    :active-user-count="activeUserCount" :published-count="publishedCount" :total-views="totalViews"
                    :total-listens="totalListens" @set-section="setSection" />

                <!-- SECTION 2: QUẢN LÝ BÀI BÁO (ARTICLES) -->
                <ArticlesTable v-else-if="activeSection === 'articles'" :articles="articles" :categories="categories"
                    :all-categories="allCategories" :sources="sources" :is-loading="isArticlesLoading"
                    :meta="articlesMeta" v-model:search="search" v-model:selected-category="selectedCategory"
                    v-model:selected-status="selectedStatus" v-model:selected-articles="selectedArticles"
                    :is-crawling="isCrawling" :crawl-source-id="crawlSourceId" @crawl-rss="handleCrawlRss"
                    @create-article="openArticleForm(null)" @edit-article="openArticleForm($event)"
                    @delete-article="askDelete('article', $event)" @bulk-delete="askBulkDelete('articles')"
                    @toggle-publish="handleTogglePublish" @change-page="fetchArticles($event)" />

                <!-- SECTION 3: QUẢN LÝ DANH MỤC (CATEGORIES) -->
                <CategoriesTable v-else-if="activeSection === 'categories'" :categories="categories"
                    :all-categories="allCategories" :articles="articles" :is-loading="isCategoriesLoading"
                    :meta="categoriesMeta" v-model:search="search" v-model:selected-categories="selectedCategories"
                    @edit-category="openCategoryForm($event)" @delete-category="askDelete('category', $event)"
                    @bulk-delete="askBulkDelete('categories')" @toggle-status="handleToggleCategoryStatus"
                    @change-page="fetchCategories($event)" />

                <!-- SECTION 4: QUẢN LÝ NGƯỜI DÙNG (USERS) -->
                <UsersTable v-else-if="activeSection === 'users'" :users="users" :current-user="currentUser"
                    :is-loading="isUsersLoading" :meta="usersMeta" v-model:search="search"
                    v-model:selected-role="selectedRole" v-model:selected-users="selectedUsers"
                    :active-user-count="activeUserCount" @refresh="fetchUsers" @change-role="handleRoleChange"
                    @toggle-status="handleToggleUserStatus" @delete-user="askDelete('user', $event)"
                    @bulk-delete="askBulkDelete('users')" @change-page="fetchUsers($event)" />
            </main>
        </div>

        <!-- MODAL THÊM TÀI KHOẢN NGƯỜI DÙNG -->
        <UserFormModal :is-open="userModal" :form="userForm" @close="userModal = false" @submit="saveUser" />

        <!-- MODAL THÊM / SỬA DANH MỤC -->
        <CategoryFormModal :is-open="categoryModal" :form="categoryForm" :editing-id="editingId"
            :categories="allCategories.length ? allCategories : categories" @close="categoryModal = false"
            @submit="saveCategory" />

        <!-- MODAL THÊM / SỬA BÀI BÁO -->
        <ArticleFormModal :is-open="articleModal" :form="articleForm" :editing-id="editingId" :sources="sources"
            :categories="allCategories.length ? allCategories : categories" :thumbnail-preview="thumbnailPreview"
            :thumbnail-file="thumbnailFile" @close="articleModal = false" @submit="saveArticle"
            @thumbnail-change="onThumbnailChange" @remove-thumbnail="removeThumbnail" />

        <!-- MODAL XÁC NHẬN XÓA -->
        <DeleteConfirmModal :target="deleteTarget" @close="deleteTarget = null" @confirm="confirmDelete" />

        <!-- THÔNG BÁO FLASH TOAST -->
        <FlashToast :message="flashMessage" />
    </div>
</template>
