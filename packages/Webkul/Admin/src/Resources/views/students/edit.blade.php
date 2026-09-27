<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.students.edit.title')
    </x-slot>

    <x-admin::form
        :action="route('admin.students.update', $student->id)"
        method="PUT"
        enctype="multipart/form-data"
    >
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <div class="flex flex-col gap-2">
                    <x-admin::breadcrumbs
                        name="students.edit"
                        :entity="$student"
                    />

                    <div class="text-xl font-bold dark:text-white">
                        @lang('admin::app.students.edit.title')
                    </div>
                </div>

                <button
                    type="submit"
                    class="primary-button"
                >
                    @lang('admin::app.students.edit.save-btn')
                </button>
            </div>

            <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <div class="grid gap-4 md:grid-cols-2">
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.students.form.name')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="name"
                            rules="required"
                            :label="trans('admin::app.students.form.name')"
                            :placeholder="trans('admin::app.students.form.name')"
                            :value="old('name', $student->name)"
                        />

                        <x-admin::form.control-group.error control-name="name" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.students.form.university-card-number')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="university_card_number"
                            rules="required"
                            :label="trans('admin::app.students.form.university-card-number')"
                            :placeholder="trans('admin::app.students.form.university-card-number')"
                            :value="old('university_card_number', $student->university_card_number)"
                        />

                        <x-admin::form.control-group.error control-name="university_card_number" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('admin::app.students.form.registration-number')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="registration_number"
                            :label="trans('admin::app.students.form.registration-number')"
                            :placeholder="trans('admin::app.students.form.registration-number')"
                            :value="old('registration_number', $student->registration_number)"
                        />

                        <x-admin::form.control-group.error control-name="registration_number" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('admin::app.students.form.major')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="major"
                            :label="trans('admin::app.students.form.major')"
                            :placeholder="trans('admin::app.students.form.major')"
                            :value="old('major', $student->major)"
                        />

                        <x-admin::form.control-group.error control-name="major" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('admin::app.students.form.academic-level')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="academic_level"
                            :label="trans('admin::app.students.form.academic-level')"
                            :placeholder="trans('admin::app.students.form.academic-level')"
                            :value="old('academic_level', $student->academic_level)"
                        />

                        <x-admin::form.control-group.error control-name="academic_level" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('admin::app.students.form.password')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="password"
                            name="password"
                            rules="min:6"
                            :label="trans('admin::app.students.form.password')"
                            :placeholder="trans('admin::app.students.form.password')"
                        />

                        <x-admin::form.control-group.error control-name="password" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('admin::app.students.form.password-confirmation')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="password"
                            name="password_confirmation"
                            rules="confirmed:@password"
                            :label="trans('admin::app.students.form.password-confirmation')"
                            :placeholder="trans('admin::app.students.form.password-confirmation')"
                        />

                        <x-admin::form.control-group.error control-name="password_confirmation" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group class="md:col-span-2 !mb-0">
                        <x-admin::form.control-group.label>
                            @lang('admin::app.students.form.profile-image')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="file"
                            name="profile_image"
                            accept="image/*"
                            :label="trans('admin::app.students.form.profile-image')"
                        />

                        <x-admin::form.control-group.error control-name="profile_image" />
                    </x-admin::form.control-group>
                </div>
            </div>
        </div>
    </x-admin::form>
</x-admin::layouts>
