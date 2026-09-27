<section class="border-b border-slate-100 bg-gradient-to-br from-[color:var(--shop-gradient-from)] to-slate-50">
    <div class="container px-4 py-12 max-md:px-4 lg:px-[60px]">
        <h1 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
            {{ __('shop::app.home.hero.heading') }}
        </h1>

        <p class="mt-3 max-w-2xl text-lg text-slate-600">
            {{ __('shop::app.home.hero.subheading') }}
        </p>

        <a
            href="{{ route('shop.events.index') }}"
            class="primary-button mt-8"
        >
            {{ __('shop::app.home.hero.cta') }}
        </a>
    </div>
</section>
