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
    sort_order: '',
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

function validateField(field) {
    if (field === 'name') {
        const val = (props.form.name || '').trim()
        if (!val) {
            errors.name = 'Vui lòng nhập tên danh mục.'
        } else if (val.length > 100) {
            errors.name = 'Tên danh mục không được vượt quá 100 ký tự.'
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
            errors.slug = 'Slug chỉ gồm chữ thường không dấu, số và dấu gạch ngang (VD: kinh-te-so).'
        } else {
            errors.slug = ''
        }
    } else if (field === 'sort_order') {
        if (props.form.sort_order < 0) {
            errors.sort_order = 'Thứ tự hiển thị phải là số không âm.'
        } else {
            errors.sort_order = ''
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
    validateField('sort_order')
    return !errors.name && !errors.slug && !errors.sort_order
}

function handleFormSubmit() {
    if (!validateAll()) {
        if (errors.name) document.getElementById('cat-name')?.focus()
        else if (errors.slug) document.getElementById('cat-slug')?.focus()
        else if (errors.sort_order) document.getElementById('cat-sort')?.focus()
        return
    }
    emit('submit')
}

watch(() => props.isOpen, (open) => {
    if (open) {
        errors.name = ''
        errors.slug = ''
        errors.sort_order = ''
    }
})
</script>

<template>
    <Transition name="fade">
        <div v-if="isOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
            @click.self="$emit('close')">
            <form class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl border border-slate-100"
                @submit.prevent="handleFormSubmit" novalidate>
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4.5 bg-slate-50/50">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600">Cấu trúc chuyên
                            mục</span>
                        <h3 class="font-display text-lg font-bold text-slate-900">
                            {{ editingId ? 'Chỉnh sửa danh mục' : 'Thêm danh mục mới' }}
                        </h3>
                    </div>
                    <button type="button" @click="$emit('close')"
                        class="flex size-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 cursor-pointer">
                        ✕
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- Tên danh mục -->
                        <div>
                            <label for="cat-name" class="block text-xs font-semibold text-slate-700 mb-1.5">Tên danh mục <span
                                    class="text-rose-500">*</span></label>
                            <div class="relative">
                                <input id="cat-name" v-model.trim="form.name" maxlength="100"
                                    @input="handleNameInput" @blur="validateField('name')"
                                    :class="[
                                        'h-10 w-full rounded-xl border px-3 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 transition-colors',
                                        errors.name
                                            ? 'border-red-500 text-red-900 focus:border-red-500 focus:ring-red-100 pe-9'
                                            : 'border-slate-200 focus:border-indigo-500 focus:ring-indigo-100'
                                    ]"
                                    placeholder="VD: Kinh tế số" />
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

                        <!-- Slug -->
                        <div>
                            <label for="cat-slug" class="block text-xs font-semibold text-slate-700 mb-1.5">Slug định danh <span
                                    class="text-rose-500">*</span></label>
                            <div class="relative">
                                <input id="cat-slug" v-model.trim="form.slug" maxlength="120"
                                    @input="clearFieldError('slug')" @blur="validateField('slug')"
                                    :class="[
                                        'h-10 w-full rounded-xl border px-3 font-mono text-xs placeholder-slate-400 focus:outline-none focus:ring-2 transition-colors',
                                        errors.slug
                                            ? 'border-red-500 text-red-900 focus:border-red-500 focus:ring-red-100 pe-9'
                                            : 'border-slate-200 focus:border-indigo-500 focus:ring-indigo-100'
                                    ]"
                                    placeholder="kinh-te-so" />
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

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- Danh mục cha -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Danh mục cha</label>
                            <select v-model="form.parent_id"
                                class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                                <option value="">Không có (Danh mục gốc)</option>
                                <option v-for="cat in categories.filter((c) => c.id !== editingId)" :key="cat.id"
                                    :value="String(cat.id)">
                                    {{ cat.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Thứ tự hiển thị -->
                        <div>
                            <label for="cat-sort" class="block text-xs font-semibold text-slate-700 mb-1.5">Thứ tự hiển thị</label>
                            <div class="relative">
                                <input id="cat-sort" v-model.number="form.sort_order" type="number" min="0"
                                    @input="clearFieldError('sort_order')" @blur="validateField('sort_order')"
                                    :class="[
                                        'h-10 w-full rounded-xl border px-3 text-xs focus:outline-none focus:ring-2 transition-colors',
                                        errors.sort_order
                                            ? 'border-red-500 text-red-900 focus:border-red-500 focus:ring-red-100'
                                            : 'border-slate-200 focus:border-indigo-500 focus:ring-indigo-100'
                                    ]" />
                            </div>
                            <p v-if="errors.sort_order" class="mt-1 text-xs text-red-500 font-medium">
                                {{ errors.sort_order }}
                            </p>
                        </div>
                    </div>

                    <!-- Mô tả danh mục -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Mô tả danh mục</label>
                        <div
                            class="quill-wrapper rounded-xl border border-slate-200 overflow-hidden bg-white focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-100">
                            <QuillEditor v-model:content="form.description" contentType="html" theme="snow"
                                :toolbar="[['bold', 'italic', 'underline'], [{ 'list': 'ordered' }, { 'list': 'bullet' }], ['clean']]"
                                placeholder="Mô tả ngắn gọn về chủ đề bài viết thuộc danh mục này..."
                                style="min-height: 80px;" />
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input v-model="form.is_active" type="checkbox" id="cat-active"
                            class="size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" />
                        <label for="cat-active" class="text-xs font-semibold text-slate-700 cursor-pointer select-none">
                            Cho phép hiển thị trên thanh điều hướng tin tức
                        </label>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div
                    class="flex items-center justify-end gap-2.5 border-t border-slate-100 bg-slate-50/50 px-6 py-4">
                    <button type="button" @click="$emit('close')"
                        class="h-9.5 rounded-xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-600 hover:bg-slate-100 transition cursor-pointer">
                        Hủy bỏ
                    </button>
                    <button type="submit"
                        class="h-9.5 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-700 px-5 text-xs font-bold text-white shadow-md shadow-indigo-600/25 hover:brightness-110 transition cursor-pointer">
                        {{ editingId ? 'Lưu thay đổi' : 'Tạo danh mục' }}
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
