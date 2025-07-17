<x-layouts.app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-adaptive-secondary dark:border-neutral-700 bg-gradient-to-br from-[var(--color-primary)] to-[var(--color-secondary)] dark:from-[var(--color-primary-dark)] dark:to-[var(--color-secondary-dark)] hover:brightness-110 transition-all duration-300">
                <a href="{{ route('accommodations') }}"
                    class="absolute inset-0 flex flex-col items-center justify-center text-white p-6 text-center">
                    <div class="text-4xl mb-2">🏨</div>
                    <h3 class="text-lg font-semibold mb-2">Hébergements</h3>
                    <p class="text-sm opacity-90">Gérer les hébergements</p>
                </a>
            </div>
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-adaptive-secondary dark:border-neutral-700 bg-gradient-to-br from-emerald-500 to-teal-600 hover:brightness-110 transition-all duration-300">
                <div class="absolute inset-0 flex flex-col items-center justify-center text-white p-6 text-center">
                    <div class="text-4xl mb-2">🌐</div>
                    <h3 class="text-lg font-semibold mb-2">Affichage Public</h3>
                    <p class="text-sm opacity-90 mb-3">Liste pour intégration iframe</p>
                    <div class="flex gap-2">
                        <a href="{{ route('accommodations.public') }}" target="_blank"
                            class="bg-white/20 hover:bg-white/30 px-3 py-1 rounded-md text-xs font-medium transition-all">
                            👁️ Voir
                        </a>
                        <button onclick="copyIframeCode()"
                            class="bg-white/20 hover:bg-white/30 px-3 py-1 rounded-md text-xs font-medium transition-all">
                            📋 Copier iframe
                        </button>
                    </div>
                </div>
            </div>
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-adaptive-secondary dark:border-neutral-700 bg-adaptive-accent">
                <x-placeholder-pattern
                    class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
            </div>
        </div>
        <div
            class="relative h-full flex-1 overflow-hidden rounded-xl border border-adaptive-secondary dark:border-neutral-700 bg-adaptive-accent">
            <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
        </div>
    </div>

    <script>
        function copyIframeCode() {
            const iframeCode =
                `<iframe src="{{ route('accommodations.public') }}" width="100%" height="600" frameborder="0" scrolling="auto" style="border: 1px solid #ddd; border-radius: 8px; display: block; max-width: 100%; min-height: 400px; resize: vertical; overflow: auto;"></iframe>`;

            navigator.clipboard.writeText(iframeCode).then(function() {
                // Créer une notification de succès
                const notification = document.createElement('div');
                notification.innerHTML = '✅ Code iframe copié dans le presse-papiers !';
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: #10b981;
                    color: white;
                    padding: 12px 20px;
                    border-radius: 8px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    z-index: 9999;
                    font-size: 14px;
                    font-weight: 500;
                    animation: slideIn 0.3s ease-out;
                `;

                // Ajouter l'animation CSS
                if (!document.querySelector('#iframe-notification-styles')) {
                    const style = document.createElement('style');
                    style.id = 'iframe-notification-styles';
                    style.textContent = `
                        @keyframes slideIn {
                            from { transform: translateX(100%); opacity: 0; }
                            to { transform: translateX(0); opacity: 1; }
                        }
                        @keyframes slideOut {
                            from { transform: translateX(0); opacity: 1; }
                            to { transform: translateX(100%); opacity: 0; }
                        }
                    `;
                    document.head.appendChild(style);
                }

                document.body.appendChild(notification);

                // Supprimer la notification après 3 secondes
                setTimeout(() => {
                    notification.style.animation = 'slideOut 0.3s ease-out';
                    setTimeout(() => {
                        document.body.removeChild(notification);
                    }, 300);
                }, 3000);
            }).catch(function(err) {
                console.error('Erreur lors de la copie :', err);
                alert('Erreur lors de la copie du code iframe');
            });
        }
    </script>
</x-layouts.app>
