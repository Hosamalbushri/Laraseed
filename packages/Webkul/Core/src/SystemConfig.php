<?php

namespace Webkul\Core;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Webkul\Core\Repositories\CoreConfigRepository;
use Webkul\Core\SystemConfig\Item;

class SystemConfig
{
    /**
     * Items array.
     */
    public array $items = [];

    /**
     * Create a new class instance.
     *
     * @return void
     */
    public function __construct(protected CoreConfigRepository $coreConfigRepository) {}

    /**
     * Add Item.
     */
    public function addItem(Item $item): void
    {
        $this->items[] = $item;
    }

    /**
     * Get all configuration items.
     */
    public function getItems(): Collection
    {
        if (! $this->items) {
            $this->prepareConfigurationItems();
        }

        return collect($this->items)
            ->sortBy('sort');
    }

    /**
     * Retrieve Core Config
     */
    private function retrieveCoreConfig(): array
    {
        $items = config('core_config');

        return is_array($items) ? $items : [];
    }

    /**
     * Prepare configuration items.
     */
    public function prepareConfigurationItems()
    {
        $this->items = [];
        $configWithDotNotation = [];

        foreach ($this->retrieveCoreConfig() as $item) {
            if (isset($item['key'])) {
                $configWithDotNotation[$item['key']] = $item;
            }
        }

        $configs = Arr::undot(Arr::dot($configWithDotNotation));

        foreach ($configs as $configItem) {
            if (! isset($configItem['key'])) {
                continue;
            }

            $subConfigItems = $this->processSubConfigItems($configItem);

            $this->addItem(new Item(
                children: $subConfigItems,
                fields: $configItem['fields'] ?? null,
                icon: $configItem['icon'] ?? null,
                key: $configItem['key'],
                name: isset($configItem['name']) ? trans($configItem['name']) : '',
                route: $configItem['route'] ?? null,
                info: isset($configItem['info']) ? trans($configItem['info']) : null,
                sort: $configItem['sort'] ?? null,
            ));
        }
    }

    /**
     * Process sub config items.
     */
    private function processSubConfigItems($configItem): Collection
    {
        return collect($configItem)
            ->sortBy('sort')
            ->filter(fn ($value) => is_array($value) && isset($value['name']))
            ->map(function ($subConfigItem) {
                $configItemChildren = $this->processSubConfigItems($subConfigItem);

                return new Item(
                    children: $configItemChildren,
                    fields: $subConfigItem['fields'] ?? null,
                    icon: $subConfigItem['icon'] ?? null,
                    key: $subConfigItem['key'],
                    name: isset($subConfigItem['name']) ? trans($subConfigItem['name']) : '',
                    info: isset($subConfigItem['info']) ? trans($subConfigItem['info']) : null,
                    route: $subConfigItem['route'] ?? null,
                    sort: $subConfigItem['sort'] ?? null,
                );
            });
    }

    /**
     * Get active configuration item.
     */
    public function getActiveConfigurationItem(): ?Item
    {
        if (! $slug = request()->route('slug')) {
            return null;
        }

        $activeItem = $this->getItems()->where('key', $slug)->first() ?? null;

        if (! $activeItem) {
            return null;
        }

        if ($slug2 = request()->route('slug2')) {
            $children = $activeItem->getChildren();

            $activeItem = $children->has($slug2) ? $children->get($slug2) : ($children[$slug2] ?? null);
        }

        return $activeItem;
    }

    /**
     * Get config field.
     */
    public function getConfigField(string $fieldName): ?array
    {
        foreach ($this->retrieveCoreConfig() as $coreData) {
            if (! isset($coreData['fields'])) {
                continue;
            }

            foreach ($coreData['fields'] as $field) {
                $name = $coreData['key'].'.'.$field['name'];

                if ($name == $fieldName) {
                    return $field;
                }
            }
        }

        return null;
    }

    /**
     * Get default config.
     */
    private function getDefaultConfig(string $field): mixed
    {
        $configFieldInfo = $this->getConfigField($field);

        $fields = explode('.', $field);

        array_shift($fields);

        $field = implode('.', $fields);

        return Config::get($field, $configFieldInfo['default'] ?? null);
    }

    /**
     * Retrieve information for configuration
     */
    public function getConfigData(string $field): mixed
    {
        $coreConfigValue = $this->coreConfigRepository->findOneWhere([
            'code' => $field,
        ]);

        if (! $coreConfigValue) {
            return $this->getDefaultConfig($field);
        }

        return $coreConfigValue->value;
    }
}
