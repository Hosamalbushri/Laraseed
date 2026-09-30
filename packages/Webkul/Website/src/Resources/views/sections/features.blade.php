<div class="website-features py-8">
    <div class="text-center max-w-2xl mx-auto mb-10">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-3">
            {{ trans('website::app.features.heading') }}
        </h2>
        <p class="text-gray-600 text-base md:text-lg">
            {{ trans('website::app.features.subheading') }}
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
            <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-xl mb-4">
                🎓
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">{{ trans('website::app.features.academic_title') }}</h3>
            <p class="text-gray-600 text-sm leading-relaxed">{{ trans('website::app.features.academic_desc') }}</p>
        </div>

        <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
            <div class="w-12 h-12 rounded-lg bg-green-100 text-green-800 flex items-center justify-center font-bold text-xl mb-4">
                🏛️
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">{{ trans('website::app.features.student_life_title') }}</h3>
            <p class="text-gray-600 text-sm leading-relaxed">{{ trans('website::app.features.student_life_desc') }}</p>
        </div>

        <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
            <div class="w-12 h-12 rounded-lg bg-purple-100 text-purple-800 flex items-center justify-center font-bold text-xl mb-4">
                💻
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">{{ trans('website::app.features.digital_services_title') }}</h3>
            <p class="text-gray-600 text-sm leading-relaxed">{{ trans('website::app.features.digital_services_desc') }}</p>
        </div>
    </div>
</div>
