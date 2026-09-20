<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Yeni Sınav</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('exams.store') }}" method="POST">
                    @csrf
                    @include('exams._form', ['exam' => null, 'classes' => $classes])
                    <div class="mt-4 flex gap-2">
                        <x-primary-button>Oluştur</x-primary-button>
                        <a href="{{ route('exams.index') }}" class="px-4 py-2 text-sm text-gray-600">Vazgeç</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
