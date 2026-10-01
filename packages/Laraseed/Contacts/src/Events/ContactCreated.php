<?php

namespace Laraseed\Contacts\Events;

use Illuminate\Queue\SerializesModels;
use Laraseed\Contacts\Contracts\Contact;

class ContactCreated
{
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Contact $contact
    ) {}
}
