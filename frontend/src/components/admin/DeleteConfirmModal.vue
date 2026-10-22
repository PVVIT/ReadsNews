<script setup>
defineProps({
    target: {
        type: Object,
        default: null
    }
})

defineEmits(['close', 'confirm'])
</script>

<template>
    <Transition name="fade">
        <div v-if="target"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
            @click.self="$emit('close')">
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
                <h3 class="mt-4 font-display text-base font-bold text-slate-900">
                    {{ target.count ? `Xác nhận xóa ${target.count} mục` : 'Xác nhận xóa dữ liệu' }}
                </h3>
                <p v-if="target.count" class="mt-1.5 text-xs text-slate-500 leading-relaxed">
                    Bạn có chắc chắn muốn xóa vĩnh viễn <strong class="text-rose-600 font-bold">{{ target.count }}</strong> mục đã chọn? Thao tác này sẽ xóa dữ liệu khỏi hệ thống và không thể hoàn tác.
                </p>
                <p v-else class="mt-1.5 text-xs text-slate-500 leading-relaxed">
                    Bạn có chắc chắn muốn xóa mục <strong class="text-slate-800">"{{ target.record?.name ||
                        target.record?.title }}"</strong>? Thao tác này sẽ loại bỏ dữ liệu khỏi hệ thống.
                </p>
                <div class="mt-6 flex items-center justify-center gap-3">
                    <button type="button" @click="$emit('close')"
                        class="h-9.5 rounded-xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                        Hủy bỏ
                    </button>
                    <button type="button" @click="$emit('confirm')"
                        class="h-9.5 rounded-xl bg-rose-600 px-5 text-xs font-bold text-white shadow-md shadow-rose-600/25 hover:bg-rose-700 transition">
                        Xác nhận xóa
                    </button>
                </div>
            </div>
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

