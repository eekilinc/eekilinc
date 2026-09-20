<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $class->name }} — Yeni Öğrenci</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('classes.students.store', $class) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <x-input-label for="student_no" value="Öğrenci No" />
                        <x-text-input id="student_no" name="student_no" class="mt-1 block w-full" required autofocus />
                        <x-input-error :messages="$errors->get('student_no')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="first_name" value="Ad" />
                        <x-text-input id="first_name" name="first_name" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="last_name" value="Soyad" />
                        <x-text-input id="last_name" name="last_name" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button>Kaydet</x-primary-button>
                        <a href="{{ route('classes.show', $class) }}" class="px-4 py-2 text-sm text-gray-600">Vazgeç</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
