<x-layouts.app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 bg-gradient-to-br from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 transition-all duration-300">
                <a href="{{ route('qualification.index') }}"
                    class="absolute inset-0 flex flex-col items-center justify-center text-white p-6 text-center">
                    <div class="text-4xl mb-2">📋</div>
                    <h3 class="text-lg font-semibold mb-2">Qualification</h3>
                    <p class="text-sm opacity-90">Outil de qualification</p>
                </a>
            </div>
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 bg-gradient-to-br from-green-500 to-teal-600 hover:from-green-600 hover:to-teal-700 transition-all duration-300">
                <a href="{{ route('accommodations') }}"
                    class="absolute inset-0 flex flex-col items-center justify-center text-white p-6 text-center">
                    <div class="text-4xl mb-2">🏨</div>
                    <h3 class="text-lg font-semibold mb-2">Hébergements</h3>
                    <p class="text-sm opacity-90">Gérer les hébergements</p>
                </a>
            </div>
            <div
                class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <x-placeholder-pattern
                    class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
            </div>
        </div>
        <div
            class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
        </div>
    </div>
</x-layouts.app>
