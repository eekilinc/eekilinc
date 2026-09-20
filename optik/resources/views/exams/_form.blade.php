@php($exam = $exam ?? null)
<div class="space-y-4">
    <div>
        <x-input-label for="title" value="Sınav başlığı" />
        <x-text-input id="title" name="title" class="mt-1 block w-full" value="{{ old('title', $exam?->title) }}" required />
        <x-input-error :messages="$errors->get('title')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="course" value="Ders" />
        <x-text-input id="course" name="course" class="mt-1 block w-full" value="{{ old('course', $exam?->course) }}" />
        <x-input-error :messages="$errors->get('course')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="class_id" value="Sınıf (opsiyonel)" />
        <select id="class_id" name="class_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            <option value="">— Sınıfsız —</option>
            @foreach ($classes as $class)
                <option value="{{ $class->id }}" @selected((string) old('class_id', $exam?->class_id) === (string) $class->id)>{{ $class->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('class_id')" class="mt-2" />
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <x-input-label for="question_count" value="Soru sayısı" />
            <x-text-input id="question_count" name="question_count" type="number" min="5" max="100" class="mt-1 block w-full" value="{{ old('question_count', $exam?->question_count ?? 40) }}" required />
            <x-input-error :messages="$errors->get('question_count')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="option_count" value="Şık sayısı" />
            <select id="option_count" name="option_count" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                @foreach ([4, 5] as $count)
                    <option value="{{ $count }}" @selected((int) old('option_count', $exam?->option_count ?? 5) === $count)>{{ $count }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('option_count')" class="mt-2" />
        </div>
    </div>
    <div>
        <x-input-label value="Kitapçıklar" />
        <div class="mt-1 flex gap-4">
            @foreach (['A', 'B', 'C', 'D'] as $letter)
                <label class="inline-flex items-center gap-1 text-sm">
                    <input type="checkbox" name="booklets[]" value="{{ $letter }}" @checked(in_array($letter, old('booklets', $exam?->booklets ?? ['A']))) class="rounded border-gray-300" />
                    {{ $letter }}
                </label>
            @endforeach
        </div>
        <x-input-error :messages="$errors->get('booklets')" class="mt-2" />
    </div>
</div>
