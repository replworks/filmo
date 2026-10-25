<?php

namespace App\Services\Filmography;

use App\Models\FilmographyCandidate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReviewManager
{
    public function approve(int $candidateId, string $reviewer, ?string $note = null): FilmographyCandidate
    {
        return $this->decide($candidateId, FilmographyCandidate::REVIEW_STATUS_APPROVED, $reviewer, $note);
    }

    public function reject(int $candidateId, string $reviewer, ?string $note = null): FilmographyCandidate
    {
        return $this->decide($candidateId, FilmographyCandidate::REVIEW_STATUS_REJECTED, $reviewer, $note);
    }

    /**
     * @throws RuntimeException
     */
    protected function decide(int $candidateId, string $status, string $reviewer, ?string $note): FilmographyCandidate
    {
        return DB::transaction(function () use ($candidateId, $status, $reviewer, $note): FilmographyCandidate {
            $candidate = FilmographyCandidate::findOrFail($candidateId);

            if ($candidate->review_status !== FilmographyCandidate::REVIEW_STATUS_PENDING) {
                throw new RuntimeException('Only a Pending Filmography Candidate can be reviewed.');
            }

            $candidate->update([
                'review_status' => $status,
                'reviewer' => trim($reviewer) === '' ? 'Operator' : trim($reviewer),
                'reviewed_at' => Carbon::now(),
                'review_note' => $note === null || trim($note) === '' ? null : trim($note),
            ]);

            return $candidate;
        });
    }
}
