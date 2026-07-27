<div
    x-cloak
    x-show="confirmationOpen"
    x-on:keydown.escape.window="confirmationOpen && cancelConfirmation()"
    x-on:keydown.tab="trapConfirmationFocus($event)"
    class="fixed inset-0 z-[100] overflow-y-auto px-4 py-6 sm:px-0"
    role="dialog"
    aria-modal="true"
    aria-labelledby="confirmation-modal-title"
    aria-describedby="confirmation-modal-message"
>
    <div
        x-show="confirmationOpen"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm"
        x-on:click="cancelConfirmation()"
        aria-hidden="true"
    ></div>

    <div class="flex min-h-full items-center justify-center">
        <section
            x-show="confirmationOpen"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-3 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-3 scale-95"
            class="relative w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
            x-on:click.stop
        >
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <div
                        class="grid h-11 w-11 shrink-0 place-items-center rounded-xl text-xl font-black"
                        :class="confirmationVariant === 'danger'
                            ? 'bg-red-50 text-red-600'
                            : 'bg-amber-50 text-amber-600'"
                        aria-hidden="true"
                    >
                        <span x-text="confirmationVariant === 'danger' ? '!' : '?'"></span>
                    </div>
                    <div class="min-w-0">
                        <h2 id="confirmation-modal-title" class="text-lg font-bold text-slate-950" x-text="confirmationTitle"></h2>
                        <p id="confirmation-modal-message" class="mt-2 text-sm leading-6 text-slate-600" x-text="confirmationMessage"></p>
                    </div>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-2 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end">
                <button
                    x-ref="confirmationCancel"
                    type="button"
                    x-on:click="cancelConfirmation()"
                    class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2"
                >
                    Cancelar
                </button>
                <button
                    x-ref="confirmationConfirm"
                    type="button"
                    x-on:click="confirmAction()"
                    class="rounded-xl px-5 py-2.5 text-sm font-bold text-white shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-2"
                    :class="confirmationVariant === 'danger'
                        ? 'bg-red-600 hover:bg-red-500 focus:ring-red-600'
                        : 'bg-amber-500 text-slate-950 hover:bg-amber-400 focus:ring-amber-500'"
                    x-text="confirmationButton"
                ></button>
            </div>
        </section>
    </div>
</div>
