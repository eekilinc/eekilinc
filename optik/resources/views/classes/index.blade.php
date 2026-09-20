<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Sınıflar</h2>
            <a href="{{ route('classes.create') }}" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">Yeni Sınıf</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-auth-session-status class="mb-4" :status="session('status')" />
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sınıf</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Öğrenci</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($classes as $class)
                            <tr>
                                <td class="px-6 py-4 font-medium">{{ $class->name }}</td>
                                <td class="px-6 py-4">{{ $class->students_count }}</td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <a href="{{ route('classes.show', $class) }}" class="text-indigo-600 hover:underline text-sm">Görüntüle</a>
                                    <a href="{{ route('classes.edit', $class) }}" class="text-gray-600 hover:underline text-sm">Düzenle</a>
                                    <form action="{{ route('classes.destroy', $class) }}" method="POST" class="inline" onsubmit="return confirm('Silinsin mi?')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 hover:underline text-sm">Sil</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-6 py-4 text-gray-500">Henüz sınıf yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
