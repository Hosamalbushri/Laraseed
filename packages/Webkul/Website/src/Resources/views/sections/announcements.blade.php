<div class="website-announcements py-6 bg-gray-50 rounded-2xl p-6 md:p-8 border border-gray-200">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-1">
            {{ trans('website::app.announcements.heading') }}
        </h2>
        <p class="text-gray-600 text-sm">
            {{ trans('website::app.announcements.subheading') }}
        </p>
    </div>

    <div class="space-y-4">
        <div class="p-4 bg-white rounded-lg border border-gray-200 flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div>
                <h4 class="font-bold text-gray-900 text-base">{{ trans('website::app.announcements.notice1_title') }}</h4>
                <p class="text-gray-600 text-sm mt-1">{{ trans('website::app.announcements.notice1_desc') }}</p>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded text-xs font-medium bg-blue-50 text-blue-700 self-start md:self-center">
                {{ trans('website::app.announcements.badge_update') }}
            </span>
        </div>

        <div class="p-4 bg-white rounded-lg border border-gray-200 flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div>
                <h4 class="font-bold text-gray-900 text-base">{{ trans('website::app.announcements.notice2_title') }}</h4>
                <p class="text-gray-600 text-sm mt-1">{{ trans('website::app.announcements.notice2_desc') }}</p>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded text-xs font-medium bg-green-50 text-green-700 self-start md:self-center">
                {{ trans('website::app.announcements.badge_notice') }}
            </span>
        </div>
    </div>
</div>
