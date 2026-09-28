<x-admin::layouts>
    <x-slot:title>@lang('lost_found::app.employee.claims.detail_title', ['id' => $detail['id']])</x-slot>

    <div class="flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
        <h1 class="text-xl font-bold">@lang('lost_found::app.employee.claims.detail_title', ['id' => $detail['id']])</h1>
        <p>@lang('lost_found::app.employee.claims.item'): <a href="{{ route('admin.lost_found.items.claims.index', $detail['found_item']['id']) }}">{{ $detail['found_item']['public_reference'] }} — {{ $detail['found_item']['title'] }}</a></p>
        <p>@lang('lost_found::app.employee.claims.item_status'): {{ trans('lost_found::app.employee.items.statuses.'.$detail['found_item']['status']) }}</p>
        <p>@lang('lost_found::app.employee.claims.status'): {{ trans('lost_found::app.employee.claims.statuses.'.$detail['status']) }}</p>
        <p>@lang('lost_found::app.employee.claims.claimant_name'): {{ $detail['claimant']['name'] }} (#{{ $detail['claimant']['id'] }})</p>
        <p>@lang('lost_found::app.employee.claims.submitted_at'): {{ $detail['submitted_at'] }}</p>
        <p>@lang('lost_found::app.employee.claims.is_approved_claim'): {{ $detail['is_approved_claim'] ? trans('lost_found::app.employee.claims.yes') : trans('lost_found::app.employee.claims.no') }}</p>

        <h2 class="font-semibold">@lang('lost_found::app.employee.claims.evidence')</h2>
        @forelse ($detail['evidence'] as $evidence)
            <div class="border-t border-gray-200 pt-2 dark:border-gray-700">
                <p>{{ trans('lost_found::app.employee.claims.evidence_types.'.$evidence['type']) }} — {{ $evidence['submitted_at'] }}</p>
                @if ($evidence['text'] !== null)
                    <p class="whitespace-pre-wrap">{{ $evidence['text'] }}</p>
                @endif
            </div>
        @empty
            <p>@lang('lost_found::app.employee.claims.none')</p>
        @endforelse

        <h2 class="font-semibold">@lang('lost_found::app.employee.claims.reviews')</h2>
        @forelse ($detail['reviews'] as $review)
            <div class="border-t border-gray-200 pt-2 dark:border-gray-700">
                <p>{{ trans('lost_found::app.employee.claims.statuses.'.$review['from_status']) }} → {{ trans('lost_found::app.employee.claims.statuses.'.$review['to_status']) }} — {{ $review['reviewed_at'] }} — {{ $review['reviewer_name'] }}</p>
                @if ($review['staff_notes'] !== null)
                    <p class="whitespace-pre-wrap">{{ $review['staff_notes'] }}</p>
                @endif
            </div>
        @empty
            <p>@lang('lost_found::app.employee.claims.none')</p>
        @endforelse
    </div>
</x-admin::layouts>
