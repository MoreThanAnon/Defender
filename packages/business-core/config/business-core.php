<?php

return [

    // The host application's user model. It should use BusinessCore\Concerns\HasBusinesses.
    'user_model' => env('BUSINESS_CORE_USER_MODEL', 'App\\Models\\User'),

    // Attribute used when showing a person's name (falls back to email).
    'user_display' => 'username',

    // Blade layout every screen extends. Point this at your own layout to re-skin the module;
    // include "business-core::partials.business-nav" in it to keep the per-business menu.
    'layout' => 'business-core::layouts.app',

    'route_prefix' => 'businesses',
    'middleware' => ['web', 'auth'],

    // What one "business" can be. One record per house / business / piece of land.
    'types' => [
        'business' => 'Business',
        'house' => 'House',
        'land' => 'Land',
        'site' => 'Site / property',
        'other' => 'Other',
    ],

    // Who may move a business between storage (sandbox) and live.
    // "platform_admin" = only the platform operator; "director" = the business's own directors too.
    'go_live' => 'platform_admin',

    // Role a platform administrator gets inside a business they are not a member of.
    // Keep this read-only: support access, never ownership.
    'platform_admin_role' => 'auditor',

    /*
     | Roles inside a business and what each may do.
     | Abilities: view, reports, manage, members, finance, operate, propose, vote, decide, documents, export
     */
    'roles' => [
        'director' => ['label' => 'Director', 'abilities' => ['*']],
        'finance' => ['label' => 'Finance lead', 'abilities' => ['view', 'reports', 'finance', 'propose', 'vote', 'documents']],
        'operator' => ['label' => 'Site / operator', 'abilities' => ['view', 'operate', 'propose', 'vote', 'documents']],
        'member' => ['label' => 'Member', 'abilities' => ['view', 'vote']],
        'auditor' => ['label' => 'Accountant / auditor', 'abilities' => ['view', 'reports']],
    ],

    'categories' => [
        'Sales / income', 'Member contribution', 'Supplier payment', 'Repairs & maintenance', 'Materials & supplies',
        'Wages & contractors', 'Rent & rates', 'Utilities', 'Insurance', 'Professional fees', 'Bank fees', 'Tax', 'Transfer', 'Other',
    ],

    'asset_kinds' => ['land' => 'Land', 'building' => 'Building', 'equipment' => 'Equipment', 'vehicle' => 'Vehicle', 'other' => 'Other'],

    'document_kinds' => ['quote' => 'Quote', 'invoice' => 'Invoice', 'contract' => 'Contract', 'minutes' => 'Minutes', 'policy' => 'Policy', 'photo' => 'Photo', 'other' => 'Other'],

    // An imported balance older than this many days is shown as stale.
    'stale_import_days' => 35,

    // Filesystem disk for statements and documents. Must NOT be publicly served.
    'disk' => 'local',
    'upload_max_kb' => 10240,
    'document_mimes' => 'pdf,jpg,jpeg,png,webp,csv,txt,doc,docx,xls,xlsx',

    'invitation_days' => 14,
];
