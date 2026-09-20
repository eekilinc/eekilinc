<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kağıt İncele — {{ $scan->student_no_raw }} ({{ $scan->booklet }})</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6 text-sm">
                Puan: <strong>{{ (float) $scan->score }} / {{ (float) $scan->max_score }}</strong> ·
                Durum: <strong>{{ $scan->status }}</strong> ·
                Güven: <strong>%{{ $scan->confidence }}</strong>
                @if ($scan->paper_image_path)
                    · <a href="{{ Illuminate\Support\Facades\Storage::url($scan->paper_image_path) }}" target="_blank" class="text-indigo-600 hover:underline">Kağıt fotoğrafı</a>
                @endif
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('scans.update', $scan) }}" method="POST">
                    @csrf @method('PUT')
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="py-2 text-left text-xs text-gray-500 uppercase">Soru</th>
                                @foreach ($options as $option)
                                    <th class="py-2 text-center text-xs text-gray-500 uppercase">{{ $option }}</th>
                                @endforeach
                                <th class="py-2 text-center text-xs text-gray-500 uppercase">Boş</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @for ($q = 1; $q <= $scan->exam->question_count; $q++)
                                @php($current = $scan->answers[(string) $q] ?? null)
                                <tr class="{{ is_array($current) ? 'bg-yellow-50' : '' }}">
                                    <td class="py-1 font-mono font-medium">{{ $q }}</td>
                                    @foreach ($options as $option)
                                        <td class="py-1 text-center">
                                            <input type="radio" name="answers[{{ $q }}]" value="{{ $option }}" @checked($current === $option) class="border-gray-300" />
                                        </td>
                                    @endforeach
                                    <td class="py-1 text-center">
                                        <input type="radio" name="answers[{{ $q }}]" value="" @checked($current === null || (is_string($current) && ! in_array($current, $options, true))) class="border-gray-300" />
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                    <div class="mt-4 flex gap-2">
                        <x-primary-button>Düzelt ve Yeniden Puanla</x-primary-button>
                        <a href="{{ route('results.index', $scan->exam) }}" class="px-4 py-2 text-sm text-gray-600">Vazgeç</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
