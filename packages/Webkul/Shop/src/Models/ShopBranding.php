<?php

namespace Webkul\Shop\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ShopBranding extends Model
{
    protected $table = 'shop_branding';

    protected $fillable = [
        'logo',
        'favicon',
    ];

    public function logoUrl(): ?string
    {
        if (! $this->logo) {
            return null;
        }

        return Storage::disk('public')->url($this->logo);
    }

    public function faviconUrl(): ?string
    {
        if (! $this->favicon) {
            return null;
        }

        return Storage::disk('public')->url($this->favicon);
    }
}
