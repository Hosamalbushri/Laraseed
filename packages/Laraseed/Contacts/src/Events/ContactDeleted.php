<?php

namespace Laraseed\Contacts\Events;

use Illuminate\Queue\SerializesModels;

class ContactDeleted
{
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  array<string, mixed>  $snapshot
     */
    public function __construct(
        public int $contactId,
        public array $snapshot = []
    ) {}
}
