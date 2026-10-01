<?php

namespace Laraseed\Contacts\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Laraseed\Contacts\Contracts\Contact as ContactContract;
use Laraseed\Contacts\Events\ContactCreated;
use Laraseed\Contacts\Events\ContactDeleted;
use Laraseed\Contacts\Events\ContactUpdated;
use Laraseed\Contacts\Models\Contact;
use Webkul\Core\Eloquent\Repository;

class ContactRepository extends Repository
{
    /**
     * Searchable fields
     *
     * @var array<int, string>
     */
    protected $fieldSearchable = [
        'type',
        'name',
        'first_name',
        'middle_name',
        'last_name',
        'organization_name',
        'tax_number',
        'email',
        'phone',
        'mobile',
        'city',
        'state',
        'postal_code',
        'country_code',
        'is_active',
    ];

    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Laraseed\Contacts\Contracts\Contact';
    }

    /**
     * Find all active contacts.
     *
     * @param  array<int, string>  $columns
     * @return mixed
     */
    public function findActive(array $columns = ['*'])
    {
        return $this->findWhere(['is_active' => true], $columns);
    }

    /**
     * Find contacts by type.
     *
     * @param  string  $type
     * @param  array<int, string>  $columns
     * @return mixed
     */
    public function findByType(string $type, array $columns = ['*'])
    {
        return $this->findWhere(['type' => $type], $columns);
    }

    /**
     * Create a new contact with domain validation, normalization, and true post-commit event dispatch.
     *
     * @param  array<string, mixed>  $attributes
     * @return ContactContract
     */
    public function create(array $attributes)
    {
        $normalized = $this->normalizeAttributes($attributes);
        $this->validateDomainInvariants($normalized, isCreate: true);
        $normalized['name'] = $this->deriveCanonicalName($normalized);

        return DB::transaction(function () use ($normalized) {
            $contact = parent::create($normalized);

            DB::afterCommit(function () use ($contact) {
                Event::dispatch(new ContactCreated($contact));
            });

            return $contact;
        });
    }

    /**
     * Update an existing contact with partial semantics, normalization, and true post-commit event dispatch.
     *
     * @param  array<string, mixed>  $attributes
     * @param  int  $id
     * @return ContactContract
     */
    public function update(array $attributes, $id)
    {
        $contact = $this->findOrFail($id);

        $normalized = $this->normalizeAttributes($attributes);
        $this->validateDomainInvariants($normalized, isCreate: false);

        // Handle Type Transition
        if (isset($normalized['type']) && $normalized['type'] !== $contact->type) {
            if ($normalized['type'] === Contact::TYPE_ORGANIZATION) {
                $normalized = array_merge([
                    'first_name' => null,
                    'middle_name' => null,
                    'last_name' => null,
                    'job_title' => null,
                ], $normalized);
            } elseif ($normalized['type'] === Contact::TYPE_PERSON) {
                $normalized = array_merge([
                    'organization_name' => null,
                    'tax_number' => null,
                ], $normalized);
            }
        }

        // Recalculate canonical name if identity-relevant fields are present
        if (
            isset($normalized['type'])
            || isset($normalized['first_name'])
            || isset($normalized['middle_name'])
            || isset($normalized['last_name'])
            || isset($normalized['organization_name'])
            || isset($normalized['name'])
        ) {
            $mergedForName = array_merge($contact->toArray(), $normalized);
            $normalized['name'] = $this->deriveCanonicalName($mergedForName, $contact->name);
        }

        return DB::transaction(function () use ($contact, $normalized, $id) {
            $contact->fill($normalized);
            $dirty = $contact->getDirty();

            $updated = parent::update($normalized, $id);

            if ($dirty !== []) {
                DB::afterCommit(function () use ($updated, $dirty) {
                    Event::dispatch(new ContactUpdated($updated, $dirty));
                });
            }

            return $updated;
        });
    }

    /**
     * Delete a contact and dispatch event after true commit.
     *
     * @param  int  $id
     * @return bool
     */
    public function delete($id)
    {
        $contact = $this->findOrFail($id);
        $contactId = (int) $contact->id;
        $snapshot = [
            'id' => $contactId,
            'type' => $contact->type,
            'name' => $contact->name,
            'email' => $contact->email,
            'phone' => $contact->phone,
            'is_active' => $contact->is_active,
        ];

        return DB::transaction(function () use ($contactId, $snapshot, $id) {
            $result = parent::delete($id);

            if ($result) {
                DB::afterCommit(function () use ($contactId, $snapshot) {
                    Event::dispatch(new ContactDeleted($contactId, $snapshot));
                });
            }

            return (bool) $result;
        });
    }

    /**
     * Normalize attribute values (trimming, casing, empty-to-null, hardened boolean parsing).
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException
     */
    public function normalizeAttributes(array $attributes): array
    {
        $normalized = [];

        foreach ($attributes as $key => $value) {
            if ($key === 'is_active' && $value !== null) {
                if (is_bool($value)) {
                    $normalized[$key] = $value;
                } else {
                    $filtered = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    if ($filtered === null && ! in_array($value, [0, 1, '0', '1'], true)) {
                        throw new InvalidArgumentException("Invalid boolean value for [is_active].");
                    }
                    $normalized[$key] = (bool) $filtered;
                }
                continue;
            }

            if (is_string($value)) {
                $trimmed = trim($value);

                if ($trimmed === '' && $key !== 'name') {
                    $normalized[$key] = null;
                    continue;
                }

                $value = $trimmed;
            }

            if ($key === 'email' && is_string($value)) {
                $value = strtolower($value);
            }

            if ($key === 'country_code' && is_string($value)) {
                $value = strtoupper($value);
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }

    /**
     * Derive canonical name deterministically.
     *
     * @param  array<string, mixed>  $attributes
     * @param  string|null  $currentName
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public function deriveCanonicalName(array $attributes, ?string $currentName = null): string
    {
        $type = $attributes['type'] ?? Contact::TYPE_PERSON;

        if ($type === Contact::TYPE_PERSON) {
            $nameParts = array_filter([
                $attributes['first_name'] ?? null,
                $attributes['middle_name'] ?? null,
                $attributes['last_name'] ?? null,
            ], fn ($p) => $p !== null && trim((string) $p) !== '');

            if ($nameParts !== []) {
                return trim(implode(' ', array_map('trim', $nameParts)));
            }

            if (! empty($attributes['name']) && trim((string) $attributes['name']) !== '') {
                return trim((string) $attributes['name']);
            }

            if (! empty($currentName) && trim($currentName) !== '') {
                return trim($currentName);
            }

            throw new InvalidArgumentException('A person contact must have a name or at least one name component.');
        }

        if ($type === Contact::TYPE_ORGANIZATION) {
            if (! empty($attributes['organization_name']) && trim((string) $attributes['organization_name']) !== '') {
                return trim((string) $attributes['organization_name']);
            }

            if (! empty($attributes['name']) && trim((string) $attributes['name']) !== '') {
                return trim((string) $attributes['name']);
            }

            if (! empty($currentName) && trim($currentName) !== '') {
                return trim($currentName);
            }

            throw new InvalidArgumentException('An organization contact must have an organization name or name.');
        }

        throw new InvalidArgumentException("Invalid contact type [{$type}].");
    }

    /**
     * Validate core domain invariants.
     *
     * @param  array<string, mixed>  $attributes
     * @param  bool  $isCreate
     * @return void
     *
     * @throws InvalidArgumentException
     */
    public function validateDomainInvariants(array $attributes, bool $isCreate = true): void
    {
        if ($isCreate || array_key_exists('type', $attributes)) {
            $type = $attributes['type'] ?? null;
            if (! in_array($type, [Contact::TYPE_PERSON, Contact::TYPE_ORGANIZATION], true)) {
                throw new InvalidArgumentException("Invalid contact type [{$type}]. Type must be 'person' or 'organization'.");
            }
        }

        if (! empty($attributes['email'])) {
            if (! filter_var($attributes['email'], FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException("Invalid email format [{$attributes['email']}].");
            }
        }

        if (! empty($attributes['country_code'])) {
            $code = trim((string) $attributes['country_code']);
            if (strlen($code) !== 2) {
                throw new InvalidArgumentException("Country code must be exactly 2 characters, received [{$attributes['country_code']}].");
            }
        }

        if (! empty($attributes['website'])) {
            $website = trim((string) $attributes['website']);
            if (! filter_var($website, FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException("Invalid website URL [{$attributes['website']}].");
            }
        }
    }
}
