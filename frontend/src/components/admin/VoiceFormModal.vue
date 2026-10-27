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
    }
})

const emit = defineEmits(['close', 'submit'])

const errors = reactive({
    name: '',
    code: '',
    provider: '',
    language: ''
})

const codeRegex = /^[a-zA-Z0-9_-]+$/

function validateField(field) {
    if (field === 'name') {
        const val = (props.form.name || '').trim()
        if (!val) {
            errors.name = 'Vui lòng nhập tên hiển thị của giọng nói.'
        } else if (val.length > 100) {
            errors.name = 'Tên giọng nói không được vượt quá 100 ký tự.'
        } else {
            errors.name = ''
        }
    } else if (field === 'code') {
        const val = (props.form.code || '').trim()
        if (!val) {
            errors.code = 'Vui lòng nhập mã định danh (Voice Code).'
        } else if (val.length > 100) {
            errors.code = 'Mã giọng không được vượt quá 100 ký tự.'
        } else if (!codeRegex.test(val)) {
            errors.code = 'Mã giọng chỉ gồm chữ cái, số, dấu gạch nối (-) hoặc gạch dưới (_).'
        } else {
            errors.code = ''
        }
    } else if (field === 'provider') {
        const val = (props.form.provider || '').trim()
        if (!val) {
            errors.provider = 'Vui lòng chọn hoặc nhập nhà cung cấp dịch vụ TTS.'
        } else if (val.length > 50) {
            errors.provider = 'Nhà cung cấp không được vượt quá 50 ký tự.'
        } else {
            errors.provider = ''
        }
    } else if (field === 'language') {
        const val = (props.form.language || '').trim()
        if (!val) {
            errors.language = 'Vui lòng nhập mã ngôn ngữ (VD: vi-VN).'
        } else if (val.length > 10) {
            errors.language = 'Mã ngôn ngữ không được vượt quá 10 ký tự.'
        } else {
            errors.language = ''
        }
    }
}

function clearFieldError(field) {
    if (errors[field]) {
        validateField(field)
    }
}

function validateAll() {
    validateField('name')
    validateField('code')
    validateField('provider')
    validateField('language')
    return !errors.name && !errors.code && !errors.provider && !errors.language
}

function handleFormSubmit() {
    if (!validateAll()) {
        if (errors.name) document.getElementById('voice-name')?.focus()
        else if (errors.code) document.getElementById('voice-code')?.focus()
        else if (errors.provider) document.getElementById('voice-provider')?.focus()
        else if (errors.language) document.getElementById('voice-lang')?.focus()
        return
    }
    emit('submit')
}

watch(() => props.isOpen, (open) => {
    if (open) {
        errors.name = ''
        errors.code = ''
        errors.provider = ''
        errors.language = ''
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
                    <div class="flex size-9 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/>
                            <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                            <line x1="12" y1="19" x2="12" y2="22"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-display text-base font-bold text-slate-900">
                            {{ editingId ? 'Chỉnh sửa giọng nói AI' : 'Thêm giọng nói AI mới' }}
                        </h3>
                        <p class="text-xs text-slate-400">
                            {{ editingId ? 'Cập nhật cấu hình giọng đọc TTS' : 'Đăng ký giọng đọc máy (TTS) mới vào hệ thống' }}
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
                    <!-- Tên giọng nói -->
                    <div>
                        <label for="voice-name" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Tên hiển thị <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input id="voice-name" v-model.trim="form.name" maxlength="100"
                                @input="clearFieldError('name')" @blur="validateField('name')"
                                :class="[
                                    'h-10 w-full rounded-xl border px-3 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 transition-colors',
                                    errors.name
                                        ? 'border-red-500 text-red-900 focus:border-red-500 focus:ring-red-100 pe-9'
                                        : 'border-slate-200 focus:border-indigo-500 focus:ring-indigo-100'
                                ]"
                                placeholder="VD: Google Nữ (WaveNet A)" />
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

                    <!-- Mã giọng đọc (Voice Code) -->
                    <div>
                        <label for="voice-code" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Mã định danh (Code) <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input id="voice-code" v-model.trim="form.code" maxlength="100"
                                @input="clearFieldError('code')" @blur="validateField('code')"
                                :class="[
                                    'h-10 w-full rounded-xl border px-3 font-mono text-xs placeholder-slate-400 focus:outline-none focus:ring-2 transition-colors',
                                    errors.code
                                        ? 'border-red-500 text-red-900 focus:border-red-500 focus:ring-red-100 pe-9'
                                        : 'border-slate-200 focus:border-indigo-500 focus:ring-indigo-100'
                                ]"
                                placeholder="vi-VN-Wavenet-A" />
                            <div v-if="errors.code" class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3 text-red-500">
                                <svg class="size-4" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                                    <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
                                </svg>
                            </div>
                        </div>
                        <p v-if="errors.code" class="mt-1 text-xs text-red-500 font-medium">
                            {{ errors.code }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <!-- Nhà cung cấp (Provider) -->
                    <div>
                        <label for="voice-provider" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Nhà cung cấp <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <select id="voice-provider" v-model="form.provider"
                                @change="clearFieldError('provider')"
                                :class="[
                                    'h-10 w-full rounded-xl border bg-white px-3 text-xs text-slate-700 focus:outline-none focus:ring-2 transition-colors',
                                    errors.provider
                                        ? 'border-red-500 text-red-900 focus:border-red-500 focus:ring-red-100 pe-9'
                                        : 'border-slate-200 focus:border-indigo-500 focus:ring-indigo-100'
                                ]">
                                <option value="">-- Chọn nhà cung cấp --</option>
                                <option value="google">Google Cloud TTS</option>
                                <option value="fpt">FPT.AI Voice</option>
                                <option value="viettel">Viettel AI</option>
                                <option value="zalo">Zalo AI</option>
                                <option value="web-speech">Web Speech API (Browser)</option>
                                <option value="other">Khác</option>
                            </select>
                            <div v-if="errors.provider" class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3 text-red-500">
                                <svg class="size-4" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                                    <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
                                </svg>
                            </div>
                        </div>
                        <p v-if="errors.provider" class="mt-1 text-xs text-red-500 font-medium">
                            {{ errors.provider }}
                        </p>
                    </div>

                    <!-- Giới tính giọng đọc -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Giới tính giọng</label>
                        <select v-model="form.gender"
                            class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                            <option value="female">Nữ (Female)</option>
                            <option value="male">Nam (Male)</option>
                            <option value="neutral">Trung tính (Neutral)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <!-- Ngôn ngữ -->
                    <div>
                        <label for="voice-lang" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Ngôn ngữ <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input id="voice-lang" v-model.trim="form.language" maxlength="10"
                                @input="clearFieldError('language')" @blur="validateField('language')"
                                :class="[
                                    'h-10 w-full rounded-xl border px-3 font-mono text-xs placeholder-slate-400 focus:outline-none focus:ring-2 transition-colors',
                                    errors.language
                                        ? 'border-red-500 text-red-900 focus:border-red-500 focus:ring-red-100 pe-9'
                                        : 'border-slate-200 focus:border-indigo-500 focus:ring-indigo-100'
                                ]"
                                placeholder="vi-VN" />
                            <div v-if="errors.language" class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3 text-red-500">
                                <svg class="size-4" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                                    <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
                                </svg>
                            </div>
                        </div>
                        <p v-if="errors.language" class="mt-1 text-xs text-red-500 font-medium">
                            {{ errors.language }}
                        </p>
                    </div>

                    <!-- Trạng thái kích hoạt -->
                    <div class="flex flex-col justify-start pt-1">
                        <label class="block text-xs font-semibold text-slate-700 mb-2">Trạng thái kích hoạt</label>
                        <label class="inline-flex items-center gap-2.5 cursor-pointer mt-1">
                            <input type="checkbox" v-model="form.is_active"
                                class="size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                            <span class="text-xs text-slate-700 font-medium">Cho phép người dùng chọn đọc</span>
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
                        {{ editingId ? 'Lưu cập nhật' : 'Thêm giọng nói' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

