<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $exam->title }} — {{ $booklet }} Anahtarı</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <x-auth-session-status class="mb-4" :status="session('status')" />
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('exams.keys.update', [$exam, $booklet]) }}" method="POST">
                    @csrf @method('PUT')
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="py-2 text-left text-xs text-gray-500 uppercase">Soru</th>
                                @foreach ($options as $option)
                                    <th class="py-2 text-center text-xs text-gray-500 uppercase">{{ $option }}</th>
                                @endforeach
                                <th class="py-2 text-center text-xs text-gray-500 uppercase">Boş</th>
                                <th class="py-2 text-center text-xs text-gray-500 uppercase">İptal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @for ($q = 1; $q <= $exam->question_count; $q++)
                                <tr>
                                    <td class="py-1 font-mono font-medium">{{ $q }}</td>
                                    @foreach ($options as $option)
                                        <td class="py-1 text-center">
                                            <input type="radio" name="answers[{{ $q }}]" value="{{ $option }}" @checked(($key->answers[(string) $q] ?? null) === $option) class="border-gray-300" />
                                        </td>
                                    @endforeach
                                    <td class="py-1 text-center">
                                        <input type="radio" name="answers[{{ $q }}]" value="" @checked(! isset($key->answers[(string) $q])) class="border-gray-300" />
                                    </td>
                                    <td class="py-1 text-center">
                                        <input type="checkbox" name="cancelled[]" value="{{ $q }}" @checked(in_array($q, $key->cancelled ?? [], true) || in_array((string) $q, $key->cancelled ?? [], true)) class="rounded border-gray-300" />
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                    <div class="mt-4 flex gap-2">
                        <x-primary-button>Anahtarı Kaydet</x-primary-button>
                        <a href="{{ route('exams.show', $exam) }}" class="px-4 py-2 text-sm text-gray-600">Vazgeç</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
