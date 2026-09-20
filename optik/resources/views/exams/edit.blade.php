<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Sınavı Düzenle</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('exams.update', $exam) }}" method="POST">
                    @csrf @method('PUT')
                    @include('exams._form', ['exam' => $exam, 'classes' => $classes])
                    <div>
                        <x-input-label for="status" value="Durum" />
                        <select id="status" name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            @foreach (['draft' => 'Taslak', 'published' => 'Yayında', 'archived' => 'Arşiv'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $exam->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>
                    <div class="mt-4 flex gap-2">
                        <x-primary-button>Güncelle</x-primary-button>
                        <a href="{{ route('exams.show', $exam) }}" class="px-4 py-2 text-sm text-gray-600">Vazgeç</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
