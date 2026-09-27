<?php

return [
    // A staff member's own DataGrid preferences, ownership-scoped by user_id.
    'admin.datagrid.saved_filters.store',
    'admin.datagrid.saved_filters.index',
    'admin.datagrid.saved_filters.update',
    'admin.datagrid.saved_filters.destroy',

    // Shared DataGrid filter-option infrastructure used from already-authorized grids.
    'admin.datagrid.look_up',

    // A staff member's own profile and password management.
    'admin.user.account.edit',
    'admin.user.account.update',
];
