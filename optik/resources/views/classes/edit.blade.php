<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Sınıfı Düzenle</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('classes.update', $class) }}" method="POST">
                    @csrf @method('PUT')
                    <div>
                        <x-input-label for="name" value="Sınıf adı" />
                        <x-text-input id="name" name="name" class="mt-1 block w-full" value="{{ old('name', $class->name) }}" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <div class="mt-4 flex gap-2">
                        <x-primary-button>Güncelle</x-primary-button>
                        <a href="{{ route('classes.show', $class) }}" class="px-4 py-2 text-sm text-gray-600">Vazgeç</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
