<script setup>
defineProps({
    message: {
        type: String,
        default: ''
    }
})
</script>

<template>
    <Transition name="notice">
        <div v-if="message"
            class="fixed bottom-6 end-6 z-50 flex items-center gap-2.5 rounded-2xl border border-indigo-100 bg-slate-900 px-4.5 py-3 text-xs font-semibold text-white shadow-2xl shadow-slate-900/40">
            <span
                class="flex size-5 items-center justify-center rounded-full bg-indigo-500 text-[11px] text-white">✓</span>
            <span>{{ message }}</span>
        </div>
    </Transition>
</template>

<style scoped>
.notice-enter-active,
.notice-leave-active {
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.notice-enter-from,
.notice-leave-to {
    opacity: 0;
    transform: translateY(12px);
}
</style>

