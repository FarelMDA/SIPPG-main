<div
    x-data="{
        toasts: [],
        push(detail) {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, ...detail });
            setTimeout(() => this.dismiss(id), 4000);
        },
        dismiss(id) {
            this.toasts = this.toasts.filter((t) => t.id !== id);
        },
        icon(variant) {
            return {
                success: '<circle cx=\'12\' cy=\'12\' r=\'9\'/><path d=\'m8.5 12.5 2.5 2.5 4.5-5\'/>',
                danger: '<circle cx=\'12\' cy=\'12\' r=\'9\'/><path d=\'m9 9 6 6M15 9l-6 6\'/>',
                warning: '<circle cx=\'12\' cy=\'12\' r=\'9\'/><path d=\'M12 7v5l3 3\'/>',
            }[variant] || '<circle cx=\'12\' cy=\'12\' r=\'9\'/><line x1=\'12\' y1=\'11\' x2=\'12\' y2=\'16\'/><circle cx=\'12\' cy=\'7.5\' r=\'.6\' fill=\'currentColor\' stroke=\'none\'/>';
        },
    }"
    @toast.window="push($event.detail)"
    class="no-print pointer-events-none fixed bottom-4 right-4 z-[100] flex w-80 flex-col gap-2"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="true"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-x-8"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="pointer-events-auto flex w-full items-start gap-3 rounded-lg border p-4 text-sm shadow-md"
            :class="{
                'bg-success-bg border-success-border text-success-text': toast.variant === 'success',
                'bg-danger-bg border-danger-border text-danger-text': toast.variant === 'danger',
                'bg-warning-bg border-warning-border text-warning-text': toast.variant === 'warning',
                'bg-info-bg border-info-border text-info-text': !toast.variant || toast.variant === 'info',
            }"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 shrink-0" x-html="icon(toast.variant)"></svg>
            <p class="min-w-0 flex-1 font-medium" x-text="toast.message"></p>
            <button type="button" @click="dismiss(toast.id)" class="shrink-0 rounded p-0.5 text-current/60 hover:text-current">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
    </template>
</div>
