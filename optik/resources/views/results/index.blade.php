<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $exam->title }} — Sonuçlar</h2>
            <a href="{{ route('exams.show', $exam) }}" class="px-4 py-2 bg-white border text-sm rounded-md">Sınava dön</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-auth-session-status :status="session('status')" />

            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                @foreach ([['Kağıt', $stats['count']], ['İnceleme', $stats['review_count']], ['Ortalama', $stats['average']], ['En yüksek', $stats['max']], ['En düşük', $stats['min']]] as [$label, $value])
                    <div class="bg-white shadow-sm sm:rounded-lg p-4">
                        <div class="text-sm text-gray-500">{{ $label }}</div>
                        <div class="text-2xl font-bold">{{ $value }}</div>
                    </div>
                @endforeach
            </div>

            <div class="flex gap-2 text-sm">
                <a href="{{ route('results.index', $exam) }}" class="px-3 py-1 rounded-full {{ ! $status ? 'bg-indigo-600 text-white' : 'bg-white border' }}">Tümü</a>
                <a href="{{ route('results.index', [$exam, 'status' => 'review']) }}" class="px-3 py-1 rounded-full {{ $status === 'review' ? 'bg-indigo-600 text-white' : 'bg-white border' }}">İncelenecekler</a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Öğrenci</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kitapçık</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Puan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Durum</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($scans as $scan)
                            <tr class="{{ $scan->status === 'review' ? 'bg-yellow-50' : '' }}">
                                <td class="px-6 py-4 font-mono">{{ $scan->student_no_raw }}</td>
                                <td class="px-6 py-4">{{ $scan->student ? $scan->student->first_name.' '.$scan->student->last_name : '—' }}</td>
                                <td class="px-6 py-4 font-mono">{{ $scan->booklet }}</td>
                                <td class="px-6 py-4 font-medium">{{ (float) $scan->score }} / {{ (float) $scan->max_score }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs rounded-full {{ $scan->status === 'ok' ? 'bg-green-100 text-green-800' : ($scan->status === 'review' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-200 text-gray-600') }}">{{ $scan->status }}</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('scans.edit', $scan) }}" class="text-indigo-600 hover:underline text-sm">İncele / Düzelt</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-4 text-gray-500">Henüz okuma yok. Mobil uygulamadan tarayın.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-medium mb-3">Madde Analizi</h3>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Soru</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Doğru %</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Dağılım</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($itemAnalysis as $question => $row)
                            <tr>
                                <td class="px-4 py-2 font-mono">{{ $question }}</td>
                                <td class="px-4 py-2">{{ $row['attempts'] ? round($row['correct'] / $row['attempts'] * 100) : '—' }}{{ $row['attempts'] ? '%' : '' }} <span class="text-gray-400">({{ $row['correct'] }}/{{ $row['attempts'] }})</span></td>
                                <td class="px-4 py-2 font-mono text-gray-600">
                                    @foreach ($row['options'] as $option => $count)
                                        {{ $option }}:{{ $count }}
                                    @endforeach
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
