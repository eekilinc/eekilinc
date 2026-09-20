<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $exam->title }}</h2>
            <div class="flex gap-2">
                <a href="{{ route('results.index', $exam) }}" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">Sonuçlar</a>
                <a href="{{ route('exams.edit', $exam) }}" class="px-4 py-2 bg-white border text-sm rounded-md">Düzenle</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-auth-session-status :status="session('status')" />

            <div class="bg-white shadow-sm sm:rounded-lg p-6 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div><span class="text-gray-500">Ders:</span> {{ $exam->course ?? '—' }}</div>
                <div><span class="text-gray-500">Soru:</span> {{ $exam->question_count }} × {{ $exam->option_count }}</div>
                <div><span class="text-gray-500">Durum:</span> {{ $exam->status }}</div>
                <div><span class="text-gray-500">Form versiyonu:</span> {{ $exam->formTemplates->first()?->version ?? 'henüz basılmadı' }}</div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-medium mb-3">Cevap Anahtarları</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach ($exam->booklets as $booklet)
                        @php($key = $exam->answerKeys->firstWhere('booklet', $booklet))
                        <a href="{{ route('exams.keys.edit', [$exam, $booklet]) }}" class="px-4 py-2 border rounded-md text-sm hover:bg-gray-50">
                            {{ $booklet }} kitapçığı
                            <span class="text-gray-500">({{ $key ? count($key->answers ?? []) : 0 }}/{{ $exam->question_count }})</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-medium mb-3">Optik Form Yazdır</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach ($exam->booklets as $booklet)
                        <a href="{{ route('exams.form-pdf', [$exam, 'booklet' => $booklet]) }}" class="px-4 py-2 border rounded-md text-sm hover:bg-gray-50">{{ $booklet }} formu (PDF)</a>
                    @endforeach
                </div>
                <p class="text-sm text-gray-500 mt-2">Yazdırmadan önce sınavı <strong>Yayında</strong> yapın ve her kitapçığın anahtarını girin.</p>
                @if ($exam->formTemplates->count() > 0)
                    <form action="{{ route('exams.form-template.destroy', $exam) }}" method="POST" class="mt-2" onsubmit="return confirm('Form şablonu silinsin mi? Yazdırdıysanız tekrar basın.')">
                        @csrf @method('DELETE')
                        <button class="text-orange-600 text-sm hover:underline">Form şablonunu sil (yeniden basım için)</button>
                    </form>
                @endif
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('exams.destroy', $exam) }}" method="POST" onsubmit="return confirm('Sınav ve tüm okumalar silinsin mi?')">
                    @csrf @method('DELETE')
                    <button class="text-red-600 text-sm hover:underline">Sınavı sil</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
