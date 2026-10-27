<script setup>
import { reactive, watch } from 'vue'

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false
    },
    form: {
        type: Object,
        required: true
    },
    editingId: {
        type: [Number, String],
        default: null
    },
    categories: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['close', 'submit'])

const errors = reactive({
    name: '',
    slug: '',
    website_url: '',
    rss_url: ''
})

function makeSlug(value) {
    return (value || '').normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/đ/g, 'd')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '')
}

const slugRegex = /^[a-z0-9]+(?:-[a-z0-9]+)*$/
const urlRegex = /^https?:\/\/.+/i

function validateField(field) {
    if (field === 'name') {
        const val = (props.form.name || '').trim()
        if (!val) {
            errors.name = 'Vui lòng nhập tên nguồn báo.'
        } else if (val.length > 100) {
            errors.name = 'Tên nguồn báo không được vượt quá 100 ký tự.'
        } else {
            errors.name = ''
        }
    } else if (field === 'slug') {
        const val = (props.form.slug || '').trim()
        if (!val) {
            errors.slug = 'Vui lòng nhập slug định danh.'
        } else if (val.length > 120) {
            errors.slug = 'Slug không được vượt quá 120 ký tự.'
        } else if (!slugRegex.test(val)) {
            errors.slug = 'Slug chỉ gồm chữ thường không dấu, số và dấu gạch ngang (VD: vnexpress).'
        } else {
            errors.slug = ''
        }
    } else if (field === 'website_url') {
        const val = (props.form.website_url || '').trim()
        if (!val) {
            errors.website_url = 'Vui lòng nhập URL trang web.'
        } else if (val.length > 255) {
            errors.website_url = 'URL trang web không được vượt quá 255 ký tự.'
        } else if (!urlRegex.test(val)) {
            errors.website_url = 'URL phải bắt đầu bằng http:// hoặc https://.'
        } else {
            errors.website_url = ''
        }
    } else if (field === 'rss_url') {
        const val = (props.form.rss_url || '').trim()
        if (val) {
            if (val.length > 500) {
                errors.rss_url = 'URL RSS không được vượt quá 500 ký tự.'
            } else if (!urlRegex.test(val)) {
                errors.rss_url = 'URL RSS phải bắt đầu bằng http:// hoặc https://.'
            } else {
                errors.rss_url = ''
            }
        } else {
            errors.rss_url = ''
        }
    }
}

function clearFieldError(field) {
    if (errors[field]) {
        validateField(field)
    }
}

function handleNameInput() {
    if (!props.editingId) {
        props.form.slug = makeSlug(props.form.name)
        clearFieldError('slug')
    }
    clearFieldError('name')
}

function validateAll() {
    validateField('name')
    validateField('slug')
    validateField('website_url')
    validateField('rss_url')
    return !errors.name && !errors.slug && !errors.website_url && !errors.rss_url
}

function handleFormSubmit() {
    if (!validateAll()) {
        if (errors.name) document.getElementById('source-name')?.focus()
        else if (errors.slug) document.getElementById('source-slug')?.focus()
        else if (errors.website_url) document.getElementById('source-web-url')?.focus()
        else if (errors.rss_url) document.getElementById('source-rss-url')?.focus()
        return
    }
    emit('submit')
}

watch(() => props.isOpen, (open) => {
    if (open) {
        errors.name = ''
        errors.slug = ''
        errors.website_url = ''
        errors.rss_url = ''
    }
})
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

        <!-- Dialog Box -->
        <div class="relative w-full max-w-xl rounded-2xl bg-white shadow-2xl transition-all">
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <div class="flex items-center gap-2.5">
                    <div class="flex size-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="2" y1="12" x2="22" y2="12"/>
                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-display text-base font-bold text-slate-900">
                            {{ editingId ? 'Chỉnh sửa nguồn báo' : 'Thêm nguồn báo mới' }}
                        </h3>
                        <p class="text-xs text-slate-400">
                            {{ editingId ? 'Cập nhật thông tin và cấu hình crawler RSS' : 'Đăng ký nguồn tin tức mới vào hệ thống crawler' }}
                        </p>
                    </div>
                </div>
                <button type="button" @click="$emit('close')"
                    class="rounded-xl p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>

            <!-- Modal Form -->
            <form @submit.prevent="handleFormSubmit" novalidate class="p-6 space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <!-- Tên nguồn -->
                    <div>
                        <label for="source-name" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Tên nguồn báo <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input id="source-name" v-model.trim="form.name" maxlength="100"
                                @input="handleNameInput" @blur="validateField('name')"
                                :class="[
                                    'h-10 w-full rounded-xl border px-3 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 transition-colors',
                                    errors.name
                                        ? 'border-red-500 text-red-900 focus:border-red-500 focus:ring-red-100 pe-9'
                                        : 'border-slate-200 focus:border-indigo-500 focus:ring-indigo-100'
                                ]"
                                placeholder="VD: VnExpress, Tuổi Trẻ..." />
                            <div v-if="errors.name" class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3 text-red-500">
                                <svg class="size-4" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                                    <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
                                </svg>
                            </div>
                        </div>
                        <p v-if="errors.name" class="mt-1 text-xs text-red-500 font-medium">
                            {{ errors.name }}
                        </p>
                    </div>

                    <!-- Slug định danh -->
                    <div>
                        <label for="source-slug" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Slug định danh <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input id="source-slug" v-model.trim="form.slug" maxlength="120"
                                @input="clearFieldError('slug')" @blur="validateField('slug')"
                                :class="[
                                    'h-10 w-full rounded-xl border px-3 font-mono text-xs placeholder-slate-400 focus:outline-none focus:ring-2 transition-colors',
                                    errors.slug
                                        ? 'border-red-500 text-red-900 focus:border-red-500 focus:ring-red-100 pe-9'
                                        : 'border-slate-200 focus:border-indigo-500 focus:ring-indigo-100'
                                ]"
                                placeholder="vnexpress" />
                            <div v-if="errors.slug" class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3 text-red-500">
                                <svg class="size-4" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                                    <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
                                </svg>
                            </div>
                        </div>
                        <p v-if="errors.slug" class="mt-1 text-xs text-red-500 font-medium">
                            {{ errors.slug }}
                        </p>
                    </div>
                </div>

                <!-- URL Trang web -->
                <div>
                    <label for="source-web-url" class="block text-xs font-semibold text-slate-700 mb-1.5">
                        URL Website gốc <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input id="source-web-url" v-model.trim="form.website_url" maxlength="255"
                            @input="clearFieldError('website_url')" @blur="validateField('website_url')"
                            :class="[
                                'h-10 w-full rounded-xl border px-3 font-mono text-xs placeholder-slate-400 focus:outline-none focus:ring-2 transition-colors',
                                errors.website_url
                                    ? 'border-red-500 text-red-900 focus:border-red-500 focus:ring-red-100 pe-9'
                                    : 'border-slate-200 focus:border-indigo-500 focus:ring-indigo-100'
                            ]"
                            placeholder="https://vnexpress.net" />
                        <div v-if="errors.website_url" class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3 text-red-500">
                            <svg class="size-4" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                                <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
                            </svg>
                        </div>
                    </div>
                    <p v-if="errors.website_url" class="mt-1 text-xs text-red-500 font-medium">
                        {{ errors.website_url }}
                    </p>
                </div>

                <!-- RSS Feed URL -->
                <div>
                    <label for="source-rss-url" class="block text-xs font-semibold text-slate-700 mb-1.5">
                        URL RSS Feed (tùy chọn)
                    </label>
                    <div class="relative">
                        <input id="source-rss-url" v-model.trim="form.rss_url" maxlength="500"
                            @input="clearFieldError('rss_url')" @blur="validateField('rss_url')"
                            :class="[
                                'h-10 w-full rounded-xl border px-3 font-mono text-xs placeholder-slate-400 focus:outline-none focus:ring-2 transition-colors',
                                errors.rss_url
                                    ? 'border-red-500 text-red-900 focus:border-red-500 focus:ring-red-100 pe-9'
                                    : 'border-slate-200 focus:border-indigo-500 focus:ring-indigo-100'
                            ]"
                            placeholder="https://vnexpress.net/rss/tin-moi-nhat.rss" />
                        <div v-if="errors.rss_url" class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3 text-red-500">
                            <svg class="size-4" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                                <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
                            </svg>
                        </div>
                    </div>
                    <p v-if="errors.rss_url" class="mt-1 text-xs text-red-500 font-medium">
                        {{ errors.rss_url }}
                    </p>
                    <p class="mt-1 text-[11px] text-slate-400">
                        Cung cấp link RSS để hệ thống tự động quét và thu thập tin tức mới.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <!-- Chuyên mục mặc định -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Chuyên mục mặc định</label>
                        <select v-model="form.category_id"
                            class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                            <option value="">Không gán (để trống)</option>
                            <option v-for="cat in categories" :key="cat.id" :value="String(cat.id)">
                                {{ cat.name }}
                            </option>
                        </select>
                        <p class="mt-1 text-[11px] text-slate-400">
                            Bài viết crawl từ nguồn này sẽ tự động gắn chuyên mục này nếu chưa phân loại.
                        </p>
                    </div>

                    <!-- Trạng thái hoạt động -->
                    <div class="flex flex-col justify-start pt-1">
                        <label class="block text-xs font-semibold text-slate-700 mb-2">Trạng thái kích hoạt</label>
                        <label class="inline-flex items-center gap-2.5 cursor-pointer mt-1">
                            <input type="checkbox" v-model="form.is_active"
                                class="size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                            <span class="text-xs text-slate-700 font-medium">Cho phép thu thập và hiển thị</span>
                        </label>
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="flex items-center justify-end gap-2.5 border-t border-slate-100 pt-4">
                    <button type="button" @click="$emit('close')"
                        class="h-9 rounded-xl border border-slate-200 px-4 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition">
                        Hủy bỏ
                    </button>
                    <button type="submit"
                        class="h-9 rounded-xl bg-indigo-600 px-5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 transition active:scale-95">
                        {{ editingId ? 'Lưu cập nhật' : 'Thêm nguồn báo' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

