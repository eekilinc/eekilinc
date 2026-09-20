<?php

namespace App\Services;

use App\Models\AnswerKey;
use App\Models\Exam;
use InvalidArgumentException;

class ScanScoringService
{
    public const QR_PREFIX = 'OPTIK1';

    public const REVIEW_CONFIDENCE_THRESHOLD = 70;

    /**
     * Parse a scanned QR payload like OPTIK1:{exam_id}:{form_version}:{booklet}:{checksum}.
     *
     * @return array{exam_id: int, version: int, booklet: string, checksum: string}
     */
    public function parseQrPayload(string $payload): array
    {
        $parts = explode(':', trim($payload));

        if (count($parts) !== 5 || $parts[0] !== self::QR_PREFIX) {
            throw new InvalidArgumentException('Geçersiz QR formatı.');
        }

        [, $examId, $version, $booklet, $checksum] = $parts;

        if (! ctype_digit($examId) || ! ctype_digit($version) || strlen($booklet) !== 1 || strlen($checksum) !== 4) {
            throw new InvalidArgumentException('Geçersiz QR içeriği.');
        }

        return [
            'exam_id' => (int) $examId,
            'version' => (int) $version,
            'booklet' => strtoupper($booklet),
            'checksum' => strtolower($checksum),
        ];
    }

    public function qrChecksum(int $examId, int $version, string $booklet): string
    {
        return substr(md5(self::QR_PREFIX.":{$examId}:{$version}:{$booklet}:".config('app.key')), 0, 4);
    }

    /**
     * Score a scan server-side. Cancelled questions count as correct for everyone,
     * blanks score zero, multi-marks need manual review.
     *
     * @param  array<string, string|string[]|null>  $answers
     * @return array{score: float, max_score: float, review_items: array<int>, correct: int, wrong: int, blank: int}
     */
    public function score(Exam $exam, AnswerKey $key, array $answers): array
    {
        $keyAnswers = $key->answers ?? [];
        $points = $key->points ?? [];
        $cancelled = $key->cancelled ?? [];
        $defaultPoint = 100 / max(1, $exam->question_count);

        $score = 0.0;
        $maxScore = 0.0;
        $reviewItems = [];
        $correct = 0;
        $wrong = 0;
        $blank = 0;

        for ($question = 1; $question <= $exam->question_count; $question++) {
            $point = (float) ($points[$question] ?? $points[(string) $question] ?? $defaultPoint);
            $maxScore += $point;

            if (in_array($question, $cancelled, true) || in_array((string) $question, $cancelled, true)) {
                $score += $point;
                $correct++;

                continue;
            }

            $mark = $answers[$question] ?? $answers[(string) $question] ?? null;

            if ($mark === null || $mark === '') {
                $blank++;

                continue;
            }

            if (is_array($mark)) {
                $reviewItems[] = $question;

                continue;
            }

            $expected = $keyAnswers[$question] ?? $keyAnswers[(string) $question] ?? null;

            if ($expected === null) {
                $reviewItems[] = $question;

                continue;
            }

            if (strtoupper((string) $mark) === strtoupper((string) $expected)) {
                $score += $point;
                $correct++;
            } else {
                $wrong++;
            }
        }

        return [
            'score' => round($score, 2),
            'max_score' => round($maxScore, 2),
            'review_items' => array_values($reviewItems),
            'correct' => $correct,
            'wrong' => $wrong,
            'blank' => $blank,
        ];
    }

    /**
     * @param  array<string, string|string[]|null>  $answers
     * @return array<int>
     */
    public function reviewItems(Exam $exam, AnswerKey $key, array $answers): array
    {
        return $this->score($exam, $key, $answers)['review_items'];
    }
}
