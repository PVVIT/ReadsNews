<script setup>
import { ref, reactive, watch } from 'vue'

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
    sources: {
        type: Array,
        default: () => []
    },
    categories: {
        type: Array,
        default: () => []
    },
    thumbnailPreview: {
        type: String,
        default: null
    },
    thumbnailFile: {
        type: Object,
        default: null
    }
})

const emit = defineEmits(['close', 'submit', 'thumbnail-change', 'remove-thumbnail'])

const thumbnailInputRef = ref(null)

const errors = reactive({
    title: '',
    slug: '',
    source_id: '',
    category_id: '',
    original_url: '',
})

function makeSlug(value) {
    return (value || '').normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/đ/g, 'd')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '')
}

function isValidUrl(string) {
    try {
        const url = new URL(string)
        return url.protocol === 'http:' || url.protocol === 'https:'
    } catch (_) {
        return false
    }
}

function validateField(field) {
    if (field === 'title') {
        const val = (props.form.title || '').trim()
        if (!val) {
            errors.title = 'Vui lòng nhập tiêu đề bài báo.'
        } else if (val.length > 500) {
            errors.title = 'Tiêu đề không được vượt quá 500 ký tự.'
        } else {
            errors.title = ''
        }
    } else if (field === 'slug') {
        const val = (props.form.slug || '').trim()
        if (!val) {
            errors.slug = 'Vui lòng nhập slug đường dẫn.'
        } else if (val.length > 520) {
            errors.slug = 'Slug không được vượt quá 520 ký tự.'
        } else {
            errors.slug = ''
        }
    } else if (field === 'source_id') {
        if (!props.form.source_id) {
            errors.source_id = 'Vui lòng chọn nguồn gốc tin tức.'
        } else {
            errors.source_id = ''
        }
    } else if (field === 'category_id') {
        if (!props.form.category_id) {
            errors.category_id = 'Vui lòng chọn chuyên mục cho bài báo.'
        } else {
            errors.category_id = ''
        }
    } else if (field === 'original_url') {
        const val = (props.form.original_url || '').trim()
        if (!val) {
            errors.original_url = 'Vui lòng nhập URL bài viết gốc.'
        } else if (!isValidUrl(val)) {
            errors.original_url = 'URL không đúng định dạng (VD: https://vnexpress.net/...).'
        } else {
            errors.original_url = ''
        }
    }
}

function clearFieldError(field) {
    if (errors[field]) {
        validateField(field)
    }
}

function handleTitleInput() {
    if (!props.editingId) {
        props.form.slug = makeSlug(props.form.title)
        clearFieldError('slug')
    }
    clearFieldError('title')
}

function validateAll() {
    validateField('title')
    validateField('slug')
    validateField('source_id')
    validateField('category_id')
    validateField('original_url')

    return !errors.title && !errors.slug && !errors.source_id && !errors.category_id && !errors.original_url
}

function handleFormSubmit() {
    if (!validateAll()) {
        if (errors.title) document.getElementById('article-title')?.focus()
        else if (errors.slug) document.getElementById('article-slug')?.focus()
        else if (errors.source_id) document.getElementById('article-source')?.focus()
        else if (errors.category_id) document.getElementById('article-category')?.focus()
        else if (errors.original_url) document.getElementById('article-url')?.focus()
        return
    }
    emit('submit')
}

function handleThumbnailSelect(event) {
    emit('thumbnail-change', event)
}

function handleRemoveThumbnail() {
    emit('remove-thumbnail')
    if (thumbnailInputRef.value) thumbnailInputRef.value.value = ''
}

watch(() => props.isOpen, (open) => {
    if (open) {
        errors.title = ''
        errors.slug = ''
        errors.source_id = ''
        errors.category_id = ''
        errors.original_url = ''
    }
})
</script>

<template>
    <Transition name="fade">
        <div v-if="isOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
            @click.self="$emit('close')">
            <form
                class="flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl border border-slate-100"
                @submit.prevent="handleFormSubmit" novalidate>
                <!-- Modal Header -->
                <div
                    class="flex items-center justify-between border-b border-slate-100 px-6 py-4.5 bg-slate-50/50 shrink-0">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600">Biên tập nội
                            dung</span>
                        <h3 class="font-display text-lg font-bold text-slate-900">
                            {{ editingId ? 'Chỉnh sửa bài báo' : 'Thêm bài báo mới' }}
                        </h3>
                    </div>
                    <button type="button" @click="$emit('close')"
                        class="flex size-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 cursor-pointer">
                        ✕
                    </button>
                </div>

                <!-- Modal Body (Scrollable) -->
                <div class="flex-1 overflow-y-auto p-6 space-y-4">
                    <!-- Tiêu đề bài báo -->
                    <div>
                        <label for="article-title" class="block text-xs font-semibold text-slate-700 mb-1.5">Tiêu đề bài báo <span
                                class="text-rose-500">*</span></label>
                        <div class="relative">
                            <input id="article-title" v-model.trim="form.title" maxlength="500"
                                @input="handleTitleInput" @blur="validateField('title')"
                                :class="[
                                    'h-10 w-full rounded-xl border px-3 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 transition-colors',
                                    errors.title
                                        ? 'border-red-500 text-red-900 focus:border-red-500 focus:ring-red-100 pe-9'
                                        : 'border-slate-200 focus:border-indigo-500 focus:ring-indigo-100'
                                ]"
                                placeholder="Nhập tiêu đề tin tức nổi bật..." />
                            <div v-if="errors.title" class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3 text-red-500">
                                <svg class="size-4" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                                    <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
                                </svg>
                            </div>
                        </div>
                        <p v-if="errors.title" class="mt-1 text-xs text-red-500 font-medium">
                            {{ errors.title }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- Slug đường dẫn -->
                        <div>
                            <label for="article-slug" class="block text-xs font-semibold text-slate-700 mb-1.5">Slug đường dẫn <span
                                    class="text-rose-500">*</span></label>
                            <div class="relative">
                                <input id="article-slug" v-model.trim="form.slug" maxlength="520"
                                    @input="clearFieldError('slug')" @blur="validateField('slug')"
                                    :class="[
                                        'h-10 w-full rounded-xl border px-3 font-mono text-xs placeholder-slate-400 focus:outline-none focus:ring-2 transition-colors',
                                        errors.slug
                                            ? 'border-red-500 text-red-900 focus:border-red-500 focus:ring-red-100 pe-9'
                                            : 'border-slate-200 focus:border-indigo-500 focus:ring-indigo-100'
                                    ]"
                                    placeholder="tieu-de-bai-bao" />
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

                        <!-- Tác giả -->
                        <div>
                            <label for="article-author" class="block text-xs font-semibold text-slate-700 mb-1.5">Tác giả biên soạn</label>
                            <input id="article-author" v-model.trim="form.author" maxlength="150"
                                class="h-10 w-full rounded-xl border border-slate-200 px-3 text-xs placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                                placeholder="VD: Ban Biên Tập ReadsNews" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- Nguồn báo -->
                        <div>
                            <label for="article-source" class="block text-xs font-semibold text-slate-700 mb-1.5">Nguồn báo đối tác <span
                                    class="text-rose-500">*</span></label>
                            <div class="relative">
                                <select id="article-source" v-model="form.source_id"
                                    @change="clearFieldError('source_id')"
                                    :class="[
                                        'h-10 w-full rounded-xl border bg-white px-3 text-xs focus:outline-none focus:ring-2 transition-colors',
                                        errors.source_id
                                            ? 'border-red-500 text-red-900 focus:border-red-500 focus:ring-red-100'
                                            : 'border-slate-200 text-slate-700 focus:border-indigo-500 focus:ring-indigo-100'
                                    ]">
                                    <option value="">Chọn nguồn gốc tin</option>
                                    <option v-for="source in sources" :key="source.id" :value="String(source.id)">{{
                                        source.name
                                    }}</option>
                                </select>
                            </div>
                            <p v-if="errors.source_id" class="mt-1 text-xs text-red-500 font-medium">
                                {{ errors.source_id }}
                            </p>
                        </div>

                        <!-- Chuyên mục -->
                        <div>
                            <label for="article-category" class="block text-xs font-semibold text-slate-700 mb-1.5">Chuyên mục <span
                                    class="text-rose-500">*</span></label>
                            <div class="relative">
                                <select id="article-category" v-model="form.category_id"
                                    @change="clearFieldError('category_id')"
                                    :class="[
                                        'h-10 w-full rounded-xl border bg-white px-3 text-xs focus:outline-none focus:ring-2 transition-colors',
                                        errors.category_id
                                            ? 'border-red-500 text-red-900 focus:border-red-500 focus:ring-red-100'
                                            : 'border-slate-200 text-slate-700 focus:border-indigo-500 focus:ring-indigo-100'
                                    ]">
                                    <option value="">Chọn danh mục</option>
                                    <option v-for="category in categories" :key="category.id"
                                        :value="String(category.id)">{{
                                            category.name }}</option>
                                </select>
                            </div>
                            <p v-if="errors.category_id" class="mt-1 text-xs text-red-500 font-medium">
                                {{ errors.category_id }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- URL gốc -->
                        <div>
                            <label for="article-url" class="block text-xs font-semibold text-slate-700 mb-1.5">URL bài viết gốc <span
                                    class="text-rose-500">*</span></label>
                            <div class="relative">
                                <input id="article-url" v-model.trim="form.original_url" type="url" maxlength="700"
                                    @input="clearFieldError('original_url')" @blur="validateField('original_url')"
                                    :class="[
                                        'h-10 w-full rounded-xl border px-3 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 transition-colors',
                                        errors.original_url
                                            ? 'border-red-500 text-red-900 focus:border-red-500 focus:ring-red-100 pe-9'
                                            : 'border-slate-200 focus:border-indigo-500 focus:ring-indigo-100'
                                    ]"
                                    placeholder="https://vnexpress.net/..." />
                                <div v-if="errors.original_url" class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3 text-red-500">
                                    <svg class="size-4" viewBox="0 0 16 16" fill="currentColor">
                                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                                        <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
                                    </svg>
                                </div>
                            </div>
                            <p v-if="errors.original_url" class="mt-1 text-xs text-red-500 font-medium">
                                {{ errors.original_url }}
                            </p>
                        </div>

                        <!-- Thumbnail -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Ảnh Thumbnail</label>
                            <!-- Preview -->
                            <div v-if="thumbnailPreview"
                                class="mb-2 relative w-full h-32 overflow-hidden rounded-xl border border-slate-200 bg-slate-100">
                                <img :src="thumbnailPreview" class="size-full object-cover" alt="preview" />
                                <button type="button" @click="handleRemoveThumbnail"
                                    class="absolute top-1 right-1 flex size-6 items-center justify-center rounded-full bg-rose-600 text-white text-xs hover:bg-rose-700 cursor-pointer">✕</button>
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
                                    accept="image/jpeg,image/png,image/webp" @change="handleThumbnailSelect"
                                    class="sr-only" />
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Mô tả ngắn (Lead)</label>
                        <div
                            class="quill-wrapper rounded-xl border border-slate-200 overflow-hidden bg-white focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-100">
                            <QuillEditor v-model:content="form.summary" contentType="html" theme="snow"
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
                            <QuillEditor v-model:content="form.content" contentType="html" theme="snow"
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
                                <QuillEditor v-model:content="form.ai_summary" contentType="html"
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
                                <QuillEditor v-model:content="form.content_tts" contentType="html"
                                    theme="snow" :toolbar="[['bold', 'italic'], ['clean']]"
                                    placeholder="Văn bản đã qua tiền xử lý dấu câu để giọng đọc AI phát âm tự nhiên nhất..."
                                    style="min-height: 75px;" />
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="article-published" class="block text-xs font-semibold text-slate-700 mb-1.5">Thời điểm xuất
                                bản</label>
                            <input id="article-published" v-model="form.published_at" type="datetime-local"
                                class="h-10 w-full rounded-xl border border-slate-200 px-3 text-xs text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100" />
                            <p class="mt-1 text-[10px] text-slate-400">Để trống nếu muốn lưu ở chế độ Bản nháp.</p>
                        </div>
                        <div>
                            <label for="article-wordcount" class="block text-xs font-semibold text-slate-700 mb-1.5">Số lượng từ ước
                                tính</label>
                            <input id="article-wordcount" v-model.number="form.word_count" type="number" min="0"
                                class="h-10 w-full rounded-xl border border-slate-200 px-3 text-xs focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100" />
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div
                    class="flex items-center justify-end gap-2.5 border-t border-slate-100 bg-slate-50/50 px-6 py-4 shrink-0">
                    <button type="button" @click="$emit('close')"
                        class="h-9.5 rounded-xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-600 hover:bg-slate-100 transition cursor-pointer">
                        Hủy bỏ
                    </button>
                    <button type="submit"
                        class="h-9.5 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-700 px-5 text-xs font-bold text-white shadow-md shadow-indigo-600/25 hover:brightness-110 transition cursor-pointer">
                        {{ editingId ? 'Lưu thay đổi bài báo' : 'Thêm bài báo mới' }}
                    </button>
                </div>
            </form>
        </div>
    </Transition>
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
