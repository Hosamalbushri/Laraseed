<?php

namespace Webkul\LostAndFound\Services;

use DateTimeInterface;
use InvalidArgumentException;
use Webkul\LostAndFound\Enums\EvidenceType;
use Webkul\LostAndFound\Models\ClaimEvidence;
use Webkul\LostAndFound\Models\LostFoundClaim;

class ClaimEvidenceService
{
    public function addTextEvidence(
        LostFoundClaim $claim,
        EvidenceType $type,
        string $text,
        ?DateTimeInterface $submittedAt = null,
    ): ClaimEvidence {
        if (! $claim->exists) {
            throw new InvalidArgumentException('Evidence requires a persisted claim.');
        }

        if ($type === EvidenceType::IMAGE_ATTACHMENT) {
            throw new InvalidArgumentException('Image evidence must use the private image evidence workflow.');
        }

        SecurityInvariants::assertNoAuthSecrets($text);

        return $claim->evidence()->create([
            'evidence_type' => $type,
            'text_value' => $text,
            'submitted_at' => $submittedAt ?? now(),
        ]);
    }
}
