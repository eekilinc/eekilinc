<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="{{ route('classes.index') }}" class="bg-white shadow-sm sm:rounded-lg p-6 hover:shadow">
                    <div class="text-sm text-gray-500">Sınıf</div>
                    <div class="text-3xl font-bold">{{ $classCount }}</div>
                </a>
                <a href="{{ route('exams.index') }}" class="bg-white shadow-sm sm:rounded-lg p-6 hover:shadow">
                    <div class="text-sm text-gray-500">Sınav</div>
                    <div class="text-3xl font-bold">{{ $examCount }}</div>
                </a>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">İncelenecek kağıt</div>
                    <div class="text-3xl font-bold {{ $reviewCount > 0 ? 'text-yellow-600' : '' }}">{{ $reviewCount }}</div>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-medium mb-3">Son sınavlar</h3>
                <div class="space-y-2">
                    @forelse ($recentExams as $exam)
                        <div class="flex justify-between items-center text-sm">
                            <span>{{ $exam->title }} <span class="text-gray-500">({{ $exam->scans_count }} okuma)</span></span>
                            <a href="{{ route('results.index', $exam) }}" class="text-indigo-600 hover:underline">Sonuçlar</a>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Henüz sınav yok. <a href="{{ route('exams.create') }}" class="text-indigo-600 hover:underline">Oluştur</a></p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
