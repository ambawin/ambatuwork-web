<div x-data="{
    toasts: [],
    add(message, type = 'success') {
        const id = Date.now();
        this.toasts.push({ id, message, type });
        setTimeout(() => this.remove(id), 4500);
    },
    remove(id) {
        this.toasts = this.toasts.filter(t => t.id !== id);
    }
}"
x-init="
    @if (session()->has('success')) add('{{ session('success') }}', 'success'); @endif
    @if (session()->has('error')) add('{{ session('error') }}', 'danger'); @endif
"
x-on:toast.window="add($event.detail.message, $event.detail.type)"
class="fixed top-20 right-6 z-[9999] flex flex-col gap-3 w-full max-w-sm pointer-events-none">
    
    <template x-for="toast in toasts" :key="toast.id">
        <div x-show="true"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-x-12 opacity-0 scale-95"
             x-transition:enter-end="translate-x-0 opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="pointer-events-auto flex items-center gap-3.5 p-4 rounded-full border-2 backdrop-blur-md shadow-lg transition-all duration-300"
             :class="{
                 'bg-white/95 border-[#FDCB40] text-[#604B10] shadow-[0_8px_30px_rgba(253,203,64,0.18)]': toast.type === 'success',
                 'bg-white/95 border-rose-500/50 text-rose-950 shadow-[0_8px_30px_rgba(244,63,94,0.18)]': toast.type === 'danger' || toast.type === 'error',
                 'bg-white/95 border-amber-500/50 text-amber-950 shadow-[0_8px_30px_rgba(245,158,11,0.18)]': toast.type === 'warning',
                 'bg-white/95 border-blue-500/50 text-blue-950 shadow-[0_8px_30px_rgba(59,130,246,0.18)]': toast.type === 'info'
             }">
             
             <!-- Icon -->
             <div class="shrink-0 mt-0.5">
                 <!-- Success Icon (Gold Checkmark) -->
                 <template x-if="toast.type === 'success'">
                     <div class="bg-[#6E5003] text-white p-1 rounded-full">
                        <x-heroicon-s-check class="w-4 h-4" />
                     </div>
                 </template>
                 
                 <!-- Error Icon -->
                 <template x-if="toast.type === 'danger' || toast.type === 'error'">
                     <div class="bg-rose-100 p-1 rounded-lg">
                        <x-heroicon-s-x-mark class="w-4 h-4" />
                     </div>
                 </template>
                 
                 <!-- Warning Icon -->
                 <template x-if="toast.type === 'warning'">
                     <div class="bg-amber-100 p-1 rounded-lg">
                        <x-heroicon-s-exclamation-circle class="w-4 h-4" />
                     </div>
                 </template>
                 
                 <!-- Info Icon -->
                 <template x-if="toast.type === 'info'">
                     <div class="bg-blue-100 p-1 rounded-lg">
                        <x-heroicon-s-information-circle class="w-4 h-4" />
                     </div>
                 </template>
             </div>

             <!-- Content -->
             <div class="flex-grow">
                 <p class="text-xs font-black font-sans tracking-wide leading-relaxed" x-text="toast.message"></p>
             </div>

             <!-- Close Button -->
             <button x-on:click="remove(toast.id)" class="shrink-0 text-slate-400 hover:text-slate-600 transition-colors">
                 <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                     <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                 </svg>
             </button>
        </div>
    </template>
</div>
