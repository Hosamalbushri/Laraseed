<?php

namespace Laraseed\Contacts\Events;

use Illuminate\Queue\SerializesModels;
use Laraseed\Contacts\Contracts\Contact;

class ContactUpdated
{
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  array<string, mixed>  $changes
     */
    public function __construct(
        public Contact $contact,
        public array $changes = []
    ) {}
}
