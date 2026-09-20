<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StudentImportController extends Controller
{
    public function __invoke(Request $request, SchoolClass $class): RedirectResponse
    {
        abort_if($class->user_id !== $request->user()->id, 404);

        $validated = $request->validate(['csv' => ['required', 'file', 'mimes:csv,txt', 'max:1024']]);

        $imported = 0;
        $path = $validated['csv']->getRealPath();

        if (($handle = fopen($path, 'r')) !== false) {
            while (($row = fgetcsv($handle, 1000, ';')) !== false) {
                if (count($row) === 1) {
                    $row = str_getcsv($row[0]);
                }

                [$studentNo, $firstName, $lastName] = array_pad(array_map(trim(...), $row), 3, '');

                if ($studentNo === '' || $firstName === '' || $lastName === '') {
                    continue;
                }

                $class->students()->updateOrCreate(
                    ['student_no' => $studentNo],
                    ['first_name' => $firstName, 'last_name' => $lastName]
                );
                $imported++;
            }

            fclose($handle);
        }

        return redirect()->route('classes.show', $class)->with('status', "{$imported} öğrenci içe aktarıldı.");
    }
}
