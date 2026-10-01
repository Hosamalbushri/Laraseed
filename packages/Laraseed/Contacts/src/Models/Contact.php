<?php

namespace Laraseed\Contacts\Models;

use Illuminate\Database\Eloquent\Model;
use Laraseed\Contacts\Contracts\Contact as ContactContract;

class Contact extends Model implements ContactContract
{
    public const TYPE_PERSON = 'person';
    public const TYPE_ORGANIZATION = 'organization';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'contacts';

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'name',
        'first_name',
        'middle_name',
        'last_name',
        'job_title',
        'organization_name',
        'tax_number',
        'email',
        'phone',
        'mobile',
        'website',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country_code',
        'notes',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Determine if the contact is an individual person.
     */
    public function isPerson(): bool
    {
        return $this->type === self::TYPE_PERSON;
    }

    /**
     * Determine if the contact is an organization / company.
     */
    public function isOrganization(): bool
    {
        return $this->type === self::TYPE_ORGANIZATION;
    }
}
