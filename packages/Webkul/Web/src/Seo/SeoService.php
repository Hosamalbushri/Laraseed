<?php

namespace Webkul\Web\Seo;

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

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        if (empty($this->title)) {
            return $this->siteSuffix;
        }

        return "{$this->title} | {$this->siteSuffix}";
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
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

        if (! empty($this->description)) {
            $desc = htmlspecialchars($this->description, ENT_QUOTES, 'UTF-8');
            $html[] = "<meta name=\"description\" content=\"{$desc}\">";
        }

        if (! empty($this->canonicalUrl)) {
            $url = htmlspecialchars($this->canonicalUrl, ENT_QUOTES, 'UTF-8');
            $html[] = "<link rel=\"canonical\" href=\"{$url}\">";
        }

        foreach ($this->metas as $name => $content) {
            $n = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            $c = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
            $attribute = str_starts_with($name, 'og:') ? 'property' : 'name';
            $html[] = "<meta {$attribute}=\"{$n}\" content=\"{$c}\">";
        }

        return implode("\n    ", $html);
    }
}
