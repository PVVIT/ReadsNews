<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { auth } from '@/utils/auth'
import { userService } from '@/services/userService'
import { categoryService } from '@/services/categoryService'
import { articleService } from '@/services/articleService'
import { sourceService } from '@/services/sourceService'
import { voiceService } from '@/services/voiceService'

import AdminSidebar from './admin/AdminSidebar.vue'
import AdminHeader from './admin/AdminHeader.vue'
import AdminOverview from './admin/AdminOverview.vue'
import ArticlesTable from './admin/ArticlesTable.vue'
import CategoriesTable from './admin/CategoriesTable.vue'
import SourcesTable from './admin/SourcesTable.vue'
import VoicesTable from './admin/VoicesTable.vue'
import UsersTable from './admin/UsersTable.vue'
import UserFormModal from './admin/UserFormModal.vue'
import CategoryFormModal from './admin/CategoryFormModal.vue'
import SourceFormModal from './admin/SourceFormModal.vue'
import VoiceFormModal from './admin/VoiceFormModal.vue'
import ArticleFormModal from './admin/ArticleFormModal.vue'
import DeleteConfirmModal from './admin/DeleteConfirmModal.vue'
import FlashToast from './admin/FlashToast.vue'

const sections = [
    { id: 'overview',    label: 'Tổng quan',    path: '/admin' },
    { id: 'articles',   label: 'Bài báo',       path: '/admin/articles' },
    { id: 'categories', label: 'Danh mục',      path: '/admin/categories' },
    { id: 'sources',    label: 'Nguồn báo',     path: '/admin/sources' },
    { id: 'voices',     label: 'Giọng nói',  path: '/admin/voices' },
    { id: 'users',      label: 'Người dùng',    path: '/admin/users' },
]

// ─── Map URL path → section id ───────────────────────────────────────────────
function pathToSection(pathname) {
    const p = pathname.replace(/\/+$/, '') || '/admin'
    const match = sections.find(s => s.path === p)
    return match ? match.id : 'overview'
}

// Khởi tạo activeSection từ URL hiện tại (hỗ trợ reload đúng trang)
const activeSection = ref(pathToSection(window.location.pathname))
const search = ref('')
const selectedCategory = ref('all')
const selectedStatus = ref('all')
const selectedRole = ref('all')

// Filter state riêng cho Sources
const filterSourceCategory = ref('')
const filterSourceStatus = ref('all')

// Filter state riêng cho Voices
const filterVoiceProvider = ref('all')
const filterVoiceGender = ref('all')
const filterVoiceStatus = ref('all')

// Modals visibility
const categoryModal = ref(false)
const articleModal = ref(false)
const userModal = ref(false)
const sourceModal = ref(false)
const voiceModal = ref(false)

const deleteTarget = ref(null)
const editingId = ref(null)
const flashMessage = ref('')
let flashTimer

// Danh sách ID được chọn để thao tác hàng loạt
const selectedArticles = ref([])
const selectedCategories = ref([])
const selectedSources = ref([])
const selectedVoices = ref([])
const selectedUsers = ref([])

// Lấy thông tin tài khoản hiện tại từ auth utils hoặc fallback mặc định
const currentUser = computed(() => {
    return auth.getUser() || { id: null, name: 'Admin ReadsNews', email: 'admin@readsnews.vn', role: 'admin' }
})

// Quản lý danh mục thực tế từ Database
const categories = ref([])
const allCategories = ref([])
const isCategoriesLoading = ref(true)
const categoriesMeta = ref({ total: 0, per_page: 10, current_page: 1, last_page: 1 })

// Nguồn báo dropdown phục vụ Article Form
const sources = ref([])

// Quản lý Nguồn báo trong bảng quản lý riêng (SourcesTable)
const adminSources = ref([])
const isAdminSourcesLoading = ref(true)
const adminSourcesMeta = ref({ total: 0, per_page: 10, current_page: 1, last_page: 1, active_count: 0 })

// Quản lý Giọng nói AI (VoicesTable)
const voices = ref([])
const isVoicesLoading = ref(true)
const voicesMeta = ref({ total: 0, per_page: 10, current_page: 1, last_page: 1, active_count: 0, available_providers: [] })

// Quản lý người dùng thực tế từ Database
const users = ref([])
const isUsersLoading = ref(true)
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
const isArticlesLoading = ref(true)
const articlesMeta = ref({ total: 0, per_page: 10, current_page: 1, last_page: 1 })

// Thumbnail upload state
const thumbnailFile = ref(null)
const thumbnailPreview = ref(null)

// Crawl RSS state
const isCrawling = ref(false)
const crawlSourceId = ref(null)

const categoryForm = ref(emptyCategory())
const articleForm = ref(emptyArticle())
const sourceForm = ref(emptySource())
const voiceForm = ref(emptyVoice())

const titleMap = {
    overview: 'Tổng quan hệ thống',
    articles: 'Quản lý bài báo',
    categories: 'Quản lý danh mục',
    sources: 'Quản lý nguồn báo',
    voices: 'Quản lý giọng nói AI',
    users: 'Quản lý người dùng'
}
const pageTitle = computed(() => titleMap[activeSection.value] || 'Quản trị hệ thống')

const sectionDescription = computed(() => {
    if (activeSection.value === 'overview') return 'Theo dõi số liệu đọc báo, lượt phát audio AI và hoạt động hệ thống hôm nay.'
    if (activeSection.value === 'users') return 'Quản lý danh sách thành viên, phân quyền vai trò và trạng thái tài khoản.'
    if (activeSection.value === 'articles') return 'Kiểm duyệt, biên tập, xuất bản bài báo và cấu hình giọng đọc TTS.'
    if (activeSection.value === 'categories') return 'Phân loại các chủ đề tin tức, thứ tự hiển thị và đường dẫn danh mục.'
    if (activeSection.value === 'sources') return 'Cấu hình các đầu mối thu thập tin tức tự động RSS và liên kết báo chí.'
    if (activeSection.value === 'voices') return 'Quản lý giọng đọc máy AI đa vùng miền phục vụ tính năng đọc báo tự động (TTS).'
    return ''
})

const totalViews = computed(() => articles.value.reduce((sum, article) => sum + Number(article.view_count || 0), 0))
const totalListens = computed(() => articles.value.reduce((sum, article) => sum + Number(article.listen_count || 0), 0))
const publishedCount = computed(() => articles.value.filter((article) => article.published_at).length)
const activeUserCount = computed(() => users.value.filter((user) => user.is_active).length)
const isLoggingOut = ref(false)
const isOverviewLoading = computed(() => isUsersLoading.value || isArticlesLoading.value || isCategoriesLoading.value)

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

function emptySource() {
    return {
        name: '',
        slug: '',
        website_url: '',
        rss_url: '',
        category_id: '',
        is_active: true
    }
}

function emptyVoice() {
    return {
        provider: 'google',
        code: '',
        name: '',
        language: 'vi-VN',
        gender: 'female',
        is_active: true
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
watch([
    search, selectedCategory, selectedStatus, selectedRole,
    filterSourceCategory, filterSourceStatus,
    filterVoiceProvider, filterVoiceGender, filterVoiceStatus
], () => {
    clearTimeout(searchDebounceTimer)
    searchDebounceTimer = setTimeout(() => {
        if (activeSection.value === 'articles') {
            fetchArticles(1)
        } else if (activeSection.value === 'categories') {
            fetchCategories(1)
        } else if (activeSection.value === 'sources') {
            fetchAdminSources(1)
        } else if (activeSection.value === 'voices') {
            fetchVoices(1)
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
    filterSourceCategory.value = ''
    filterSourceStatus.value = 'all'
    filterVoiceProvider.value = 'all'
    filterVoiceGender.value = 'all'
    filterVoiceStatus.value = 'all'
    selectedArticles.value = []
    selectedCategories.value = []
    selectedSources.value = []
    selectedVoices.value = []
    selectedUsers.value = []

    // Cập nhật URL trình duyệt theo section đang chọn
    const targetSection = sections.find(s => s.id === section)
    if (targetSection && window.location.pathname !== targetSection.path) {
        window.history.pushState({ section }, '', targetSection.path)
    }

    if (section === 'articles') {
        fetchArticles(1)
    } else if (section === 'categories') {
        fetchCategories(1)
    } else if (section === 'sources') {
        fetchAdminSources(1)
    } else if (section === 'voices') {
        fetchVoices(1)
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
    } else if (section === 'sources') {
        ids = [...selectedSources.value]
        type = 'bulk-sources'
    } else if (section === 'voices') {
        ids = [...selectedVoices.value]
        type = 'bulk-voices'
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

// ─── Xử lý Danh Mục ─────────────────────────────────────────────────────────
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

// ─── Xử lý Nguồn Báo ────────────────────────────────────────────────────────
function openSourceForm(source = null) {
    editingId.value = source?.id ?? null
    sourceForm.value = source ? {
        name: source.name || '',
        slug: source.slug || '',
        website_url: source.website_url || '',
        rss_url: source.rss_url || '',
        category_id: source.category_id ? String(source.category_id) : '',
        is_active: Boolean(source.is_active)
    } : emptySource()
    sourceModal.value = true
}

async function saveSource() {
    const record = {
        ...sourceForm.value,
        category_id: sourceForm.value.category_id ? Number(sourceForm.value.category_id) : null,
        is_active: Boolean(sourceForm.value.is_active)
    }
    record.slug = record.slug ? record.slug.trim() : makeSlug(record.name)
    try {
        if (editingId.value) {
            const res = await sourceService.updateSource(editingId.value, record)
            if (res?.success) {
                notify(res.message || 'Đã cập nhật nguồn báo thành công!')
                sourceModal.value = false
                await fetchAdminSources(adminSourcesMeta.value.current_page)
                await fetchSources()
            }
        } else {
            const res = await sourceService.createSource(record)
            if (res?.success) {
                notify(res.message || 'Đã thêm nguồn báo mới thành công!')
                sourceModal.value = false
                await fetchAdminSources(1)
                await fetchSources()
            }
        }
    } catch (err) {
        notify(err.response?.data?.message || 'Có lỗi xảy ra khi lưu nguồn báo.')
        console.error('Lỗi khi lưu nguồn báo:', err)
    }
}

async function handleToggleSourceStatus(sourceId) {
    try {
        const res = await sourceService.toggleStatus(sourceId)
        if (res?.success) {
            notify(res.message || 'Cập nhật trạng thái nguồn báo thành công!')
            const found = adminSources.value.find(s => s.id === sourceId)
            if (found) found.is_active = res.data.is_active
            await fetchSources()
        }
    } catch (err) {
        notify('Không thể cập nhật trạng thái nguồn báo.')
        console.error('Lỗi toggle status nguồn báo:', err)
    }
}

// ─── Xử lý Giọng Nói AI ─────────────────────────────────────────────────────
function openVoiceForm(voice = null) {
    editingId.value = voice?.id ?? null
    voiceForm.value = voice ? {
        provider: voice.provider || 'google',
        code: voice.code || '',
        name: voice.name || '',
        language: voice.language || 'vi-VN',
        gender: voice.gender || 'female',
        is_active: Boolean(voice.is_active)
    } : emptyVoice()
    voiceModal.value = true
}

async function saveVoice() {
    const record = {
        ...voiceForm.value,
        is_active: Boolean(voiceForm.value.is_active)
    }
    try {
        if (editingId.value) {
            const res = await voiceService.updateVoice(editingId.value, record)
            if (res?.success) {
                notify(res.message || 'Đã cập nhật giọng nói AI thành công!')
                voiceModal.value = false
                await fetchVoices(voicesMeta.value.current_page)
            }
        } else {
            const res = await voiceService.createVoice(record)
            if (res?.success) {
                notify(res.message || 'Đã thêm giọng nói AI mới thành công!')
                voiceModal.value = false
                await fetchVoices(1)
            }
        }
    } catch (err) {
        notify(err.response?.data?.message || 'Có lỗi xảy ra khi lưu giọng nói AI.')
        console.error('Lỗi khi lưu giọng nói AI:', err)
    }
}

async function handleToggleVoiceStatus(voiceId) {
    try {
        const res = await voiceService.toggleStatus(voiceId)
        if (res?.success) {
            notify(res.message || 'Cập nhật trạng thái giọng nói thành công!')
            const found = voices.value.find(v => v.id === voiceId)
            if (found) found.is_active = res.data.is_active
        }
    } catch (err) {
        notify('Không thể cập nhật trạng thái giọng nói.')
        console.error('Lỗi toggle status giọng nói:', err)
    }
}

// ─── Xử lý Bài Báo ──────────────────────────────────────────────────────────
async function openArticleForm(article = null) {
    editingId.value = article?.id ?? null
    thumbnailFile.value = null
    thumbnailPreview.value = null

    if (article) {
        articleForm.value = {
            source_id: article.source_id ? String(article.source_id) : '',
            category_id: article.category_id ? String(article.category_id) : '',
            title: article.title || '',
            slug: article.slug || '',
            summary: article.summary || '',
            ai_summary: article.ai_summary || '',
            content: article.content || '',
            content_tts: article.content_tts || '',
            thumbnail: article.thumbnail || '',
            original_url: article.original_url || '',
            url_hash: article.url_hash || '',
            author: article.author || '',
            word_count: article.word_count || 0,
            view_count: article.view_count || 0,
            listen_count: article.listen_count || 0,
            published_at: article.published_at ? article.published_at.slice(0, 16) : ''
        }
        thumbnailPreview.value = article.thumbnail || null
    } else {
        articleForm.value = emptyArticle()
    }
    articleModal.value = true
}

function onThumbnailChange(e) {
    const file = e.target.files[0]
    if (!file) return
    if (!file.type.startsWith('image/')) {
        notify('Vui lòng chọn file hình ảnh hợp lệ (JPG, PNG, WebP).')
        return
    }
    if (file.size > 5 * 1024 * 1024) {
        notify('Kích thước ảnh không được vượt quá 5MB.')
        return
    }
    thumbnailFile.value = file
    thumbnailPreview.value = URL.createObjectURL(file)
}

function removeThumbnail() {
    thumbnailFile.value = null
    thumbnailPreview.value = null
    articleForm.value.thumbnail = ''
}

async function saveArticle() {
    const fd = new FormData()
    fd.append('title', articleForm.value.title.trim())
    const slug = articleForm.value.slug.trim() || makeSlug(articleForm.value.title)
    fd.append('slug', slug)
    if (articleForm.value.category_id) fd.append('category_id', articleForm.value.category_id)
    if (articleForm.value.source_id) fd.append('source_id', articleForm.value.source_id)
    if (articleForm.value.author) fd.append('author', articleForm.value.author.trim())
    if (articleForm.value.summary) fd.append('summary', articleForm.value.summary.trim())
    if (articleForm.value.content) fd.append('content', articleForm.value.content)
    if (articleForm.value.content_tts) fd.append('content_tts', articleForm.value.content_tts)
    if (articleForm.value.original_url) fd.append('original_url', articleForm.value.original_url.trim())
    if (articleForm.value.published_at) {
        fd.append('published_at', articleForm.value.published_at)
    }
    if (thumbnailFile.value) {
        fd.append('thumbnail', thumbnailFile.value)
    }

    try {
        if (editingId.value) {
            const res = await articleService.updateArticle(editingId.value, fd)
            if (res?.success) {
                notify(res.message || 'Đã cập nhật bài báo thành công!')
                articleModal.value = false
                await fetchArticles(articlesMeta.value.current_page)
            }
        } else {
            const res = await articleService.createArticle(fd)
            if (res?.success) {
                notify(res.message || 'Đã tạo bài báo mới thành công!')
                articleModal.value = false
                await fetchArticles(1)
            }
        }
    } catch (err) {
        const errorMsg = err.response?.data?.message ||
            (err.response?.data?.errors ? Object.values(err.response.data.errors).flat().join(', ') : 'Có lỗi xảy ra khi lưu bài báo.')
        notify(errorMsg)
        console.error('Lỗi khi lưu bài báo:', err)
    }
}

// ─── Xác nhận Xóa ───────────────────────────────────────────────────────────
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
    } else if (type === 'source') {
        try {
            await sourceService.deleteSource(record.id)
            selectedSources.value = selectedSources.value.filter(id => id !== record.id)
            notify(`Đã xóa nguồn báo "${record.name}" thành công.`)
            await fetchAdminSources(adminSourcesMeta.value.current_page)
            await fetchSources()
        } catch (err) {
            notify(err.response?.data?.message || 'Lỗi khi xóa nguồn báo.')
            console.error('Lỗi khi xóa nguồn báo:', err)
        }
    } else if (type === 'voice') {
        try {
            await voiceService.deleteVoice(record.id)
            selectedVoices.value = selectedVoices.value.filter(id => id !== record.id)
            notify(`Đã xóa giọng nói "${record.name}" thành công.`)
            await fetchVoices(voicesMeta.value.current_page)
        } catch (err) {
            notify(err.response?.data?.message || 'Lỗi khi xóa giọng nói.')
            console.error('Lỗi khi xóa giọng nói:', err)
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
    } else if (type === 'bulk-sources') {
        try {
            const res = await sourceService.bulkDeleteSources(ids)
            notify(res.message || `Đã xóa ${count} nguồn báo thành công.`)
            selectedSources.value = []
            await fetchAdminSources(adminSourcesMeta.value.current_page)
            await fetchSources()
        } catch (err) {
            notify(err.response?.data?.message || 'Lỗi khi xóa các nguồn báo đã chọn.')
        }
    } else if (type === 'bulk-voices') {
        try {
            const res = await voiceService.bulkDeleteVoices(ids)
            notify(res.message || `Đã xóa ${count} giọng nói thành công.`)
            selectedVoices.value = []
            await fetchVoices(voicesMeta.value.current_page)
        } catch (err) {
            notify(err.response?.data?.message || 'Lỗi khi xóa các giọng nói đã chọn.')
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
        const res = await sourceService.getSources({ all: 1 })
        if (res?.success) sources.value = res.data || []
    } catch (err) {
        console.error('Lỗi khi tải nguồn báo:', err)
    }
}

async function fetchAdminSources(page = 1, params = {}) {
    isAdminSourcesLoading.value = true
    try {
        const queryParams = {
            per_page: 10,
            page,
            search: search.value.trim() || undefined,
            category_id: filterSourceCategory.value || undefined,
            status: filterSourceStatus.value !== 'all' ? filterSourceStatus.value : undefined,
            ...params
        }
        const res = await sourceService.getSources(queryParams)
        if (res?.success) {
            adminSources.value = res.data || []
            if (res.meta) adminSourcesMeta.value = res.meta
        }
    } catch (err) {
        console.error('Lỗi khi tải danh sách nguồn báo quản trị:', err)
    } finally {
        isAdminSourcesLoading.value = false
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
            await fetchAdminSources(adminSourcesMeta.value.current_page)
        }
    } catch (err) {
        notify(err.response?.data?.message || 'Lỗi khi crawl RSS.')
        console.error('Lỗi crawl RSS:', err)
    } finally {
        isCrawling.value = false
        crawlSourceId.value = null
    }
}

// ─── API Giọng Nói AI (Voices) ──────────────────────────────────────────────
async function fetchVoices(page = 1, params = {}) {
    isVoicesLoading.value = true
    try {
        const queryParams = {
            per_page: 10,
            page,
            search: search.value.trim() || undefined,
            provider: filterVoiceProvider.value !== 'all' ? filterVoiceProvider.value : undefined,
            gender: filterVoiceGender.value !== 'all' ? filterVoiceGender.value : undefined,
            status: filterVoiceStatus.value !== 'all' ? filterVoiceStatus.value : undefined,
            ...params
        }
        const res = await voiceService.getVoices(queryParams)
        if (res?.success) {
            voices.value = res.data || []
            if (res.meta) voicesMeta.value = res.meta
        }
    } catch (err) {
        console.error('Lỗi khi tải danh sách giọng nói AI:', err)
    } finally {
        isVoicesLoading.value = false
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
    if (isLoggingOut.value) return
    isLoggingOut.value = true
    notify('Đang đăng xuất tài khoản quản trị...')
    setTimeout(() => {
        auth.clearAuth()
        window.location.href = '/'
    }, 600)
}

onMounted(() => {
    fetchUsers(1)
    fetchCategories(1)
    fetchAllCategories()
    fetchArticles(1)
    fetchSources()
    fetchAdminSources(1)
    fetchVoices(1)

    // Lắng nghe nút Back/Forward của trình duyệt → đồng bộ activeSection với URL
    window.addEventListener('popstate', handlePopState)
})

onBeforeUnmount(() => {
    window.clearTimeout(flashTimer)
    window.removeEventListener('popstate', handlePopState)
})

// Xử lý khi người dùng nhấn Back/Forward trên trình duyệt
function handlePopState() {
    const section = pathToSection(window.location.pathname)
    activeSection.value = section
}
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

                        <button v-if="activeSection === 'sources'" @click="openSourceForm()"
                            class="inline-flex h-10 items-center gap-2 rounded-xl bg-gradient-to-r from-sky-600 to-blue-700 px-4 text-xs font-bold text-white shadow-md shadow-sky-600/25 transition hover:brightness-110 active:scale-98 cursor-pointer">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 5v14M5 12h14" />
                            </svg>
                            <span>Thêm nguồn báo mới</span>
                        </button>

                        <button v-if="activeSection === 'voices'" @click="openVoiceForm()"
                            class="inline-flex h-10 items-center gap-2 rounded-xl bg-gradient-to-r from-sky-600 to-blue-700 px-4 text-xs font-bold text-white shadow-md shadow-sky-600/25 transition hover:brightness-110 active:scale-98 cursor-pointer">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 5v14M5 12h14" />
                            </svg>
                            <span>Thêm giọng nói mới</span>
                        </button>

                        <button v-if="activeSection === 'users'" @click="openUserModal()"
                            class="inline-flex h-10 items-center gap-2 rounded-xl bg-gradient-to-r from-sky-600 to-blue-700 px-4 text-xs font-bold text-white shadow-md shadow-sky-600/25 transition hover:brightness-110 active:scale-98 cursor-pointer">
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
                    :total-listens="totalListens" :is-loading="isOverviewLoading" @set-section="setSection" />

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

                <!-- SECTION 4: QUẢN LÝ NGUỒN BÁO (SOURCES) -->
                <SourcesTable v-else-if="activeSection === 'sources'"
                    :sources="adminSources"
                    :categories="allCategories.length ? allCategories : categories"
                    :is-loading="isAdminSourcesLoading"
                    :crawling-source-id="crawlSourceId"
                    :meta="adminSourcesMeta"
                    v-model:search="search"
                    v-model:filter-category="filterSourceCategory"
                    v-model:filter-status="filterSourceStatus"
                    v-model:selected-sources="selectedSources"
                    @open-create-modal="openSourceForm(null)"
                    @edit-source="openSourceForm($event)"
                    @delete-source="askDelete('source', $event)"
                    @crawl-source="handleCrawlRss($event)"
                    @bulk-delete="askBulkDelete('sources')"
                    @toggle-status="handleToggleSourceStatus"
                    @change-page="fetchAdminSources($event)" />

                <!-- SECTION 5: QUẢN LÝ GIỌNG NÓI AI (VOICES) -->
                <VoicesTable v-else-if="activeSection === 'voices'"
                    :voices="voices"
                    :is-loading="isVoicesLoading"
                    :meta="voicesMeta"
                    v-model:search="search"
                    v-model:filter-provider="filterVoiceProvider"
                    v-model:filter-gender="filterVoiceGender"
                    v-model:filter-status="filterVoiceStatus"
                    v-model:selected-voices="selectedVoices"
                    @open-create-modal="openVoiceForm(null)"
                    @edit-voice="openVoiceForm($event)"
                    @delete-voice="askDelete('voice', $event)"
                    @bulk-delete="askBulkDelete('voices')"
                    @toggle-status="handleToggleVoiceStatus"
                    @change-page="fetchVoices($event)" />

                <!-- SECTION 6: QUẢN LÝ NGƯỜI DÙNG (USERS) -->
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

        <!-- MODAL THÊM / SỬA NGUỒN BÁO -->
        <SourceFormModal
            :is-open="sourceModal"
            :form="sourceForm"
            :editing-id="editingId"
            :categories="allCategories.length ? allCategories : categories"
            @close="sourceModal = false"
            @submit="saveSource" />

        <!-- MODAL THÊM / SỬA GIỌNG NÓI AI -->
        <VoiceFormModal
            :is-open="voiceModal"
            :form="voiceForm"
            :editing-id="editingId"
            @close="voiceModal = false"
            @submit="saveVoice" />

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
