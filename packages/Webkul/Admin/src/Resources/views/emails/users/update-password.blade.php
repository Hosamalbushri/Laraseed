@component('admin::emails.layout')
    <div style="margin-bottom: 34px;">
        <p style="font-size: 16px;color: #5E5E5E;line-height: 24px;">
            @lang('admin::app.emails.common.user.update-password.dear', ['username' => $user->name]), 👋
        </p>

        <p style="font-size: 16px;color: #5E5E5E;line-height: 24px;">
            @lang('admin::app.emails.common.user.update-password.info')
        </p>

        <p style="font-size: 16px;color: #5E5E5E;line-height: 24px;">
            @lang('admin::app.emails.common.user.update-password.thanks')
        </p>
    </div>
@endcomponent
