@php($sessionFooterHtml = core()->getConfigData('general.settings.footer.label'))
@if (filled(trim(strip_tags((string) $sessionFooterHtml))))
    <div class="max-w-lg px-4 text-center text-sm font-normal text-gray-600 dark:text-gray-400 [&_a]:text-[color:var(--shop-accent)] [&_a]:hover:underline">
        {!! $sessionFooterHtml !!}
    </div>
@endif
