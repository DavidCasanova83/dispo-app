<div class="mt-8 border-t border-gray-200 pt-4">
    <button onclick="document.getElementById('report-form').classList.toggle('hidden')"
        class="text-sm text-red-600 hover:text-red-800 font-bold underline cursor-pointer">
        Signaler un problème
    </button>

    <form id="report-form" method="POST" action="{{ route('accommodation.report-problem') }}" class="hidden mt-4 text-left">
        @csrf
        <input type="hidden" name="accommodation_name" value="{{ $accommodationName ?? 'Non identifié' }}">
        <input type="hidden" name="page_url" value="{{ url()->full() }}">

        <label for="comment" class="block text-sm font-medium text-gray-700 mb-1">
            Décrivez votre problème (facultatif)
        </label>
        <textarea name="comment" id="comment" rows="3" maxlength="1000"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent resize-none"
            placeholder="Ex: Je ne reçois plus les emails, le lien ne fonctionne pas..."></textarea>

        <button type="submit"
            class="mt-3 w-full bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium py-2 px-4 rounded-lg transition-colors">
            Envoyer le signalement
        </button>
    </form>
</div>
