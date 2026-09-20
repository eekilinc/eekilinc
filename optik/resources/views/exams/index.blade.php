<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Sınavlar</h2>
            <a href="{{ route('exams.create') }}" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">Yeni Sınav</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-auth-session-status class="mb-4" :status="session('status')" />
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sınav</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Soru</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kitapçık</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Durum</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Okuma</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($exams as $exam)
                            <tr>
                                <td class="px-6 py-4 font-medium">{{ $exam->title }}<br><span class="text-sm text-gray-500 font-normal">{{ $exam->course }}</span></td>
                                <td class="px-6 py-4">{{ $exam->question_count }} × {{ $exam->option_count }}</td>
                                <td class="px-6 py-4 font-mono">{{ implode('/', $exam->booklets) }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs rounded-full {{ $exam->status === 'published' ? 'bg-green-100 text-green-800' : ($exam->status === 'archived' ? 'bg-gray-200 text-gray-600' : 'bg-yellow-100 text-yellow-800') }}">{{ $exam->status }}</span>
                                </td>
                                <td class="px-6 py-4">{{ $exam->scans_count }}</td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <a href="{{ route('exams.show', $exam) }}" class="text-indigo-600 hover:underline text-sm">Aç</a>
                                    <a href="{{ route('results.index', $exam) }}" class="text-indigo-600 hover:underline text-sm">Sonuçlar</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-4 text-gray-500">Henüz sınav yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
