<?php

namespace Webkul\Web\Seo;

use Closure;
use Webkul\Web\Contracts\SeoMetadataContract;

class SeoService implements SeoMetadataContract
{
    protected ?string $title = null;

    protected ?string $description = null;

    protected ?string $canonicalUrl = null;

    /**
     * Extra meta properties [name/property => content].
     *
     * @var array<string, string>
     */
    protected array $metas = [];

    /**
     * Default site title suffix.
     */
    protected string $siteSuffix = 'CampusHub';

    /**
     * Optional callback returning current request SEO defaults:
     * ['site_name' => ?string, 'default_title' => ?string, 'default_description' => ?string, 'default_image' => ?string]
     *
     * @var (Closure(): array{site_name?: ?string, default_title?: ?string, default_description?: ?string, default_image?: ?string})|null
     */
    protected ?Closure $defaultsResolver = null;

    public function setDefaultsResolver(?Closure $resolver): static
    {
        $this->defaultsResolver = $resolver;

        return $this;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        $suffix = $this->resolveSiteSuffix();

        if (empty($this->title)) {
            $defaults = $this->resolveDefaults();
            $defaultTitle = isset($defaults['default_title']) && is_string($defaults['default_title'])
                ? trim($defaults['default_title'])
                : '';

            if ($defaultTitle !== '' && $defaultTitle !== $suffix) {
                return "{$defaultTitle} | {$suffix}";
            }

            return $defaultTitle !== '' ? $defaultTitle : $suffix;
        }

        return "{$this->title} | {$suffix}";
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        if (! empty($this->description)) {
            return $this->description;
        }

        $defaults = $this->resolveDefaults();
        $defaultDescription = isset($defaults['default_description']) && is_string($defaults['default_description'])
            ? trim($defaults['default_description'])
            : '';

        return $defaultDescription !== '' ? $defaultDescription : null;
    }

    public function setCanonicalUrl(?string $url): static
    {
        $this->canonicalUrl = $url;

        return $this;
    }

    public function getCanonicalUrl(): ?string
    {
        return $this->canonicalUrl;
    }

    public function setMeta(string $name, string $content): static
    {
        $this->metas[$name] = $content;

        return $this;
    }

    public function getMetas(): array
    {
        return $this->metas;
    }

    public function renderHeadHtml(): string
    {
        $html = [];

        $title = htmlspecialchars($this->getTitle(), ENT_QUOTES, 'UTF-8');
        $html[] = "<title>{$title}</title>";

        $description = $this->getDescription();
        if (! empty($description)) {
            $desc = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');
            $html[] = "<meta name=\"description\" content=\"{$desc}\">";
        }

        if (! empty($this->canonicalUrl)) {
            $url = htmlspecialchars($this->canonicalUrl, ENT_QUOTES, 'UTF-8');
            $html[] = "<link rel=\"canonical\" href=\"{$url}\">";
        }

        $metas = $this->metas;
        $defaults = $this->resolveDefaults();

        if (! isset($metas['og:image']) && ! empty($defaults['default_image']) && is_string($defaults['default_image'])) {
            $metas['og:image'] = $defaults['default_image'];
        }

        foreach ($metas as $name => $content) {
            $n = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            $c = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
            $attribute = str_starts_with($name, 'og:') ? 'property' : 'name';
            $html[] = "<meta {$attribute}=\"{$n}\" content=\"{$c}\">";
        }

        return implode("\n    ", $html);
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveDefaults(): array
    {
        if ($this->defaultsResolver === null) {
            return [];
        }

        $resolved = ($this->defaultsResolver)();

        return is_array($resolved) ? $resolved : [];
    }

    protected function resolveSiteSuffix(): string
    {
        $defaults = $this->resolveDefaults();
        $siteName = isset($defaults['site_name']) && is_string($defaults['site_name'])
            ? trim($defaults['site_name'])
            : '';

        return $siteName !== '' ? $siteName : $this->siteSuffix;
    }
}
