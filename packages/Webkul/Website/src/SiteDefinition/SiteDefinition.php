<?php

namespace Webkul\Website\SiteDefinition;

/**
 * Immutable, request-locale-resolved representation of the public Website definition.
 */
final readonly class SiteDefinition
{
    public function __construct(
        public string $locale,
        public string $name,
        public string $shortName,
        public ?string $tagline = null,
        public ?string $description = null,
        public ?string $aboutHeading = null,
        public ?string $aboutBody = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $address = null,
        public ?string $officeHours = null,
        public ?string $logoUrl = null,
        public string $logoAlt = 'CampusHub',
        public ?string $faviconUrl = null,
        public string $seoSiteName = 'CampusHub',
        public string $seoDefaultTitle = 'CampusHub',
        public ?string $seoDefaultDescription = null,
        public ?string $seoDefaultImageUrl = null,
    ) {}

    /**
     * @return array{
     *     name: string,
     *     short_name: string,
     *     tagline: ?string,
     *     description: ?string,
     *     about_heading: ?string,
     *     about_body: ?string
     * }
     */
    public function identity(): array
    {
        return [
            'name'          => $this->name,
            'short_name'    => $this->shortName,
            'tagline'       => $this->tagline,
            'description'   => $this->description,
            'about_heading' => $this->aboutHeading,
            'about_body'    => $this->aboutBody,
        ];
    }

    /**
     * @return array{
     *     email: ?string,
     *     phone: ?string,
     *     address: ?string,
     *     office_hours: ?string
     * }
     */
    public function contact(): array
    {
        return [
            'email'        => $this->email,
            'phone'        => $this->phone,
            'address'      => $this->address,
            'office_hours' => $this->officeHours,
        ];
    }

    /**
     * Determine whether any organization-wide public contact field is present.
     */
    public function hasContact(): bool
    {
        return $this->email !== null
            || $this->phone !== null
            || $this->address !== null
            || $this->officeHours !== null;
    }

    /**
     * Return the already-validated phone number in a URI-safe dial form.
     */
    public function phoneHref(): ?string
    {
        if ($this->phone === null) {
            return null;
        }

        $dialValue = preg_replace('/[^0-9+]/', '', $this->phone);

        return is_string($dialValue) && $dialValue !== '' ? $dialValue : null;
    }

    /**
     * @return array{
     *     logo_url: ?string,
     *     logo_alt: string,
     *     favicon_url: ?string
     * }
     */
    public function branding(): array
    {
        return [
            'logo_url'    => $this->logoUrl,
            'logo_alt'    => $this->logoAlt,
            'favicon_url' => $this->faviconUrl,
        ];
    }

    /**
     * @return array{
     *     site_name: string,
     *     default_title: string,
     *     default_description: ?string,
     *     default_image_url: ?string
     * }
     */
    public function seo(): array
    {
        return [
            'site_name'           => $this->seoSiteName,
            'default_title'       => $this->seoDefaultTitle,
            'default_description' => $this->seoDefaultDescription,
            'default_image_url'   => $this->seoDefaultImageUrl,
        ];
    }

    /**
     * @return array{
     *     locale: string,
     *     identity: array<string, ?string>,
     *     contact: array<string, ?string>,
     *     branding: array<string, ?string>,
     *     seo: array<string, ?string>
     * }
     */
    public function toArray(): array
    {
        return [
            'locale'   => $this->locale,
            'identity' => $this->identity(),
            'contact'  => $this->contact(),
            'branding' => $this->branding(),
            'seo'      => $this->seo(),
        ];
    }
}
