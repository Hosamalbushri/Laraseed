<x-admin::layouts>
    <x-slot:title>
        @lang('contacts_admin::app.admin.edit_contact') - {{ $contact->canonical_name }}
    </x-slot>

    <x-admin::form :action="route('admin.contacts.update', $contact->id)" method="PUT">
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <div class="flex flex-col gap-2">
                    <div class="text-xl font-bold dark:text-white">
                        @lang('contacts_admin::app.admin.edit_contact')
                    </div>
                </div>

                <div class="flex items-center gap-x-2.5">
                    <a
                        href="{{ route('admin.contacts.index') }}"
                        class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                    >
                        @lang('contacts_admin::app.admin.back_btn')
                    </a>

                    <button
                        type="submit"
                        class="primary-button"
                    >
                        @lang('contacts_admin::app.admin.save_btn')
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <!-- Core Information -->
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('contacts_admin::app.admin.show.identity')
                    </h2>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('contacts_admin::app.admin.fields.type')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            name="type"
                            id="type"
                            rules="required"
                            :label="trans('contacts_admin::app.admin.fields.type')"
                        >
                            <option value="person" {{ old('type', $contact->type) === 'person' ? 'selected' : '' }}>
                                @lang('contacts_admin::app.admin.types.person')
                            </option>
                            <option value="organization" {{ old('type', $contact->type) === 'organization' ? 'selected' : '' }}>
                                @lang('contacts_admin::app.admin.types.organization')
                            </option>
                        </x-admin::form.control-group.control>

                        <x-admin::form.control-group.error control-name="type" />
                    </x-admin::form.control-group>

                    <!-- Person Fields -->
                    <div class="mt-4 flex flex-col gap-4">
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('contacts_admin::app.admin.fields.first_name')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="first_name"
                                :value="old('first_name', $contact->first_name)"
                                :placeholder="trans('contacts_admin::app.admin.fields.first_name')"
                            />

                            <x-admin::form.control-group.error control-name="first_name" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('contacts_admin::app.admin.fields.middle_name')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="middle_name"
                                :value="old('middle_name', $contact->middle_name)"
                                :placeholder="trans('contacts_admin::app.admin.fields.middle_name')"
                            />

                            <x-admin::form.control-group.error control-name="middle_name" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('contacts_admin::app.admin.fields.last_name')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="last_name"
                                :value="old('last_name', $contact->last_name)"
                                :placeholder="trans('contacts_admin::app.admin.fields.last_name')"
                            />

                            <x-admin::form.control-group.error control-name="last_name" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('contacts_admin::app.admin.fields.job_title')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="job_title"
                                :value="old('job_title', $contact->job_title)"
                                :placeholder="trans('contacts_admin::app.admin.fields.job_title')"
                            />

                            <x-admin::form.control-group.error control-name="job_title" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('contacts_admin::app.admin.fields.department')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="department"
                                :value="old('department', $contact->department)"
                                :placeholder="trans('contacts_admin::app.admin.fields.department')"
                            />

                            <x-admin::form.control-group.error control-name="department" />
                        </x-admin::form.control-group>
                    </div>

                    <!-- Organization Fields -->
                    <div class="mt-4 flex flex-col gap-4">
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('contacts_admin::app.admin.fields.organization_name')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="organization_name"
                                :value="old('organization_name', $contact->organization_name)"
                                :placeholder="trans('contacts_admin::app.admin.fields.organization_name')"
                            />

                            <x-admin::form.control-group.error control-name="organization_name" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('contacts_admin::app.admin.fields.tax_number')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="tax_number"
                                :value="old('tax_number', $contact->tax_number)"
                                :placeholder="trans('contacts_admin::app.admin.fields.tax_number')"
                            />

                            <x-admin::form.control-group.error control-name="tax_number" />
                        </x-admin::form.control-group>
                    </div>
                </div>

                <!-- Contact Details, Address & Status -->
                <div class="flex flex-col gap-4">
                    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                        <h2 class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                            @lang('contacts_admin::app.admin.show.communication')
                        </h2>

                        <div class="flex flex-col gap-4">
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('contacts_admin::app.admin.fields.email')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="email"
                                    name="email"
                                    :value="old('email', $contact->email)"
                                    :placeholder="trans('contacts_admin::app.admin.fields.email')"
                                />

                                <x-admin::form.control-group.error control-name="email" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('contacts_admin::app.admin.fields.phone')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="phone"
                                    :value="old('phone', $contact->phone)"
                                    :placeholder="trans('contacts_admin::app.admin.fields.phone')"
                                />

                                <x-admin::form.control-group.error control-name="phone" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('contacts_admin::app.admin.fields.mobile')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="mobile"
                                    :value="old('mobile', $contact->mobile)"
                                    :placeholder="trans('contacts_admin::app.admin.fields.mobile')"
                                />

                                <x-admin::form.control-group.error control-name="mobile" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('contacts_admin::app.admin.fields.website')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="website"
                                    :value="old('website', $contact->website)"
                                    :placeholder="trans('contacts_admin::app.admin.fields.website')"
                                />

                                <x-admin::form.control-group.error control-name="website" />
                            </x-admin::form.control-group>
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                        <h2 class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                            @lang('contacts_admin::app.admin.show.address')
                        </h2>

                        <div class="flex flex-col gap-4">
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('contacts_admin::app.admin.fields.address_line1')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="address_line_1"
                                    :value="old('address_line_1', $contact->address_line_1)"
                                    :placeholder="trans('contacts_admin::app.admin.fields.address_line1')"
                                />

                                <x-admin::form.control-group.error control-name="address_line_1" />
                            </x-admin::form.control-group>

                            <div class="grid grid-cols-2 gap-4">
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        @lang('contacts_admin::app.admin.fields.city')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="city"
                                        :value="old('city', $contact->city)"
                                        :placeholder="trans('contacts_admin::app.admin.fields.city')"
                                    />

                                    <x-admin::form.control-group.error control-name="city" />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        @lang('contacts_admin::app.admin.fields.country')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="country_code"
                                        :value="old('country_code', $contact->country_code)"
                                        :placeholder="trans('contacts_admin::app.admin.fields.country')"
                                    />

                                    <x-admin::form.control-group.error control-name="country_code" />
                                </x-admin::form.control-group>
                            </div>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('contacts_admin::app.admin.fields.notes')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="textarea"
                                    name="notes"
                                    :value="old('notes', $contact->notes)"
                                    :placeholder="trans('contacts_admin::app.admin.fields.notes')"
                                />

                                <x-admin::form.control-group.error control-name="notes" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('contacts_admin::app.admin.fields.status')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="select"
                                    name="is_active"
                                    :label="trans('contacts_admin::app.admin.fields.status')"
                                >
                                    <option value="1" {{ (string) old('is_active', (int) $contact->is_active) === '1' ? 'selected' : '' }}>
                                        @lang('contacts_admin::app.admin.status.active')
                                    </option>
                                    <option value="0" {{ (string) old('is_active', (int) $contact->is_active) === '0' ? 'selected' : '' }}>
                                        @lang('contacts_admin::app.admin.status.inactive')
                                    </option>
                                </x-admin::form.control-group.control>

                                <x-admin::form.control-group.error control-name="is_active" />
                            </x-admin::form.control-group>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-admin::form>
</x-admin::layouts>
