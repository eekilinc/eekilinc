<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $class->name }}</h2>
            <a href="{{ route('classes.students.create', $class) }}" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">Öğrenci Ekle</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-auth-session-status :status="session('status')" />

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-medium mb-2">CSV ile içe aktar</h3>
                <p class="text-sm text-gray-500 mb-3">Format: <code>numara;ad;soyad</code> (her satır bir öğrenci)</p>
                <form action="{{ route('classes.students.import', $class) }}" method="POST" enctype="multipart/form-data" class="flex gap-2 items-center">
                    @csrf
                    <input type="file" name="csv" accept=".csv,.txt" required class="text-sm" />
                    <x-primary-button>Yükle</x-primary-button>
                </form>
                <x-input-error :messages="$errors->get('csv')" class="mt-2" />
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ad Soyad</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($class->students as $student)
                            <tr>
                                <td class="px-6 py-4 font-mono">{{ $student->student_no }}</td>
                                <td class="px-6 py-4">{{ $student->first_name }} {{ $student->last_name }}</td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <a href="{{ route('classes.students.edit', [$class, $student]) }}" class="text-gray-600 hover:underline text-sm">Düzenle</a>
                                    <form action="{{ route('classes.students.destroy', [$class, $student]) }}" method="POST" class="inline" onsubmit="return confirm('Silinsin mi?')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 hover:underline text-sm">Sil</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-6 py-4 text-gray-500">Öğrenci yok. Tek tek ekleyin veya CSV yükleyin.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
