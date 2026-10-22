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
    }
})

const emit = defineEmits(['close', 'submit'])

const errors = reactive({
    name: '',
    email: '',
    password: '',
    role: '',
})

const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

function validateField(field) {
    if (field === 'name') {
        const val = (props.form.name || '').trim()
        if (!val) {
            errors.name = 'Vui lòng nhập họ và tên.'
        } else if (val.length < 2) {
            errors.name = 'Họ và tên phải có ít nhất 2 ký tự.'
        } else if (val.length > 100) {
            errors.name = 'Họ và tên không được vượt quá 100 ký tự.'
        } else {
            errors.name = ''
        }
    } else if (field === 'email') {
        const val = (props.form.email || '').trim()
        if (!val) {
            errors.email = 'Vui lòng nhập địa chỉ email.'
        } else if (!emailRegex.test(val)) {
            errors.email = 'Địa chỉ email không đúng định dạng (VD: name@example.com).'
        } else {
            errors.email = ''
        }
    } else if (field === 'password') {
        const val = props.form.password || ''
        if (!val) {
            errors.password = 'Vui lòng nhập mật khẩu khởi tạo.'
        } else if (val.length < 6) {
            errors.password = 'Mật khẩu phải có ít nhất 6 ký tự.'
        } else {
            errors.password = ''
        }
    } else if (field === 'role') {
        if (!props.form.role) {
            errors.role = 'Vui lòng chọn vai trò cho tài khoản.'
        } else {
            errors.role = ''
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
    validateField('email')
    validateField('password')
    validateField('role')
    return !errors.name && !errors.email && !errors.password && !errors.role
}

function handleFormSubmit() {
    if (!validateAll()) {
        if (errors.name) document.getElementById('user-name')?.focus()
        else if (errors.email) document.getElementById('user-email')?.focus()
        else if (errors.password) document.getElementById('user-password')?.focus()
        else if (errors.role) document.getElementById('user-role')?.focus()
        return
    }
    emit('submit')
}

watch(() => props.isOpen, (open) => {
    if (open) {
        errors.name = ''
        errors.email = ''
        errors.password = ''
        errors.role = ''
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
                        <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600">Phân quyền hệ
                            thống</span>
                        <h3 class="font-display text-lg font-bold text-slate-900">Thêm người dùng mới</h3>
                    </div>
                    <button type="button" @click="$emit('close')"
                        class="flex size-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 cursor-pointer">
                        ✕
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-4">
                    <!-- Họ và tên -->
                    <div>
                        <label for="user-name" class="block text-xs font-semibold text-slate-700 mb-1.5">Họ và tên <span
                                class="text-rose-500">*</span></label>
                        <div class="relative">
                            <input id="user-name" v-model.trim="form.name" maxlength="100"
                                @input="clearFieldError('name')" @blur="validateField('name')"
                                :class="[
                                    'h-10 w-full rounded-xl border px-3 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 transition-colors',
                                    errors.name
                                        ? 'border-red-500 text-red-900 focus:border-red-500 focus:ring-red-100 pe-9'
                                        : 'border-slate-200 focus:border-indigo-500 focus:ring-indigo-100'
                                ]"
                                placeholder="Ví dụ: Nguyễn Văn A" />
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

                    <!-- Email -->
                    <div>
                        <label for="user-email" class="block text-xs font-semibold text-slate-700 mb-1.5">Địa chỉ Email <span
                                class="text-rose-500">*</span></label>
                        <div class="relative">
                            <input id="user-email" v-model.trim="form.email" type="email" maxlength="191"
                                @input="clearFieldError('email')" @blur="validateField('email')"
                                :class="[
                                    'h-10 w-full rounded-xl border px-3 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 transition-colors',
                                    errors.email
                                        ? 'border-red-500 text-red-900 focus:border-red-500 focus:ring-red-100 pe-9'
                                        : 'border-slate-200 focus:border-indigo-500 focus:ring-indigo-100'
                                ]"
                                placeholder="name@example.com" />
                            <div v-if="errors.email" class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3 text-red-500">
                                <svg class="size-4" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                                    <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
                                </svg>
                            </div>
                        </div>
                        <p v-if="errors.email" class="mt-1 text-xs text-red-500 font-medium">
                            {{ errors.email }}
                        </p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="user-password" class="block text-xs font-semibold text-slate-700 mb-1.5">Mật khẩu khởi tạo <span
                                class="text-rose-500">*</span></label>
                        <div class="relative">
                            <input id="user-password" v-model="form.password" type="password" minlength="6"
                                @input="clearFieldError('password')" @blur="validateField('password')"
                                :class="[
                                    'h-10 w-full rounded-xl border px-3 text-xs placeholder-slate-400 focus:outline-none focus:ring-2 transition-colors',
                                    errors.password
                                        ? 'border-red-500 text-red-900 focus:border-red-500 focus:ring-red-100 pe-9'
                                        : 'border-slate-200 focus:border-indigo-500 focus:ring-indigo-100'
                                ]"
                                placeholder="Tối thiểu 6 ký tự..." />
                            <div v-if="errors.password" class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3 text-red-500">
                                <svg class="size-4" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                                    <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
                                </svg>
                            </div>
                        </div>
                        <p v-if="errors.password" class="mt-1 text-xs text-red-500 font-medium">
                            {{ errors.password }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- Role -->
                        <div>
                            <label for="user-role" class="block text-xs font-semibold text-slate-700 mb-1.5">Vai trò (Role) <span
                                    class="text-rose-500">*</span></label>
                            <div class="relative">
                                <select id="user-role" v-model="form.role"
                                    @change="clearFieldError('role')"
                                    :class="[
                                        'h-10 w-full rounded-xl border bg-white px-3 text-xs focus:outline-none focus:ring-2 transition-colors',
                                        errors.role
                                            ? 'border-red-500 text-red-900 focus:border-red-500 focus:ring-red-100'
                                            : 'border-slate-200 text-slate-700 focus:border-indigo-500 focus:ring-indigo-100'
                                    ]">
                                    <option value="user">Người dùng (User)</option>
                                    <option value="editor">Biên tập viên (Editor)</option>
                                    <option value="admin">Quản trị viên (Admin)</option>
                                </select>
                            </div>
                            <p v-if="errors.role" class="mt-1 text-xs text-red-500 font-medium">
                                {{ errors.role }}
                            </p>
                        </div>

                        <!-- Active status -->
                        <div class="flex items-center gap-2 pt-6">
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input v-model="form.is_active" type="checkbox" class="peer sr-only" />
                                <div
                                    class="peer h-5 w-9 rounded-full bg-slate-200 after:absolute after:start-[2px] after:top-[2px] after:size-4 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-emerald-500 peer-checked:after:translate-x-full">
                                </div>
                                <span class="ms-2.5 text-xs font-semibold text-slate-700">Kích hoạt tài khoản ngay</span>
                            </label>
                        </div>
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
                        Tạo tài khoản
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
</style>
