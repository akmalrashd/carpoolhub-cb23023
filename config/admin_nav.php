<?php

/**
 * Single source of truth for the 5 admin tools (Users, Reports, Audit Log,
 * Messages, Settings). Previously hardcoded separately in the desktop
 * sidebar, the "Create" quick-action menu (desktop + mobile), and the
 * mobile drawer — which had already drifted out of sync (the mobile
 * quick-action menu was missing 3 of the 5). Add/remove an admin tool here
 * and every nav surface picks it up.
 */
return [
    [
        'route' => 'admin.users.index',
        'active' => ['admin.users.*'],
        'label' => 'Users',
        'drawer_label' => 'Users Admin',
        'icon' => 'fa-solid fa-users-gear',
        'bento_title' => 'Manage Users',
        'bento_icon' => 'fa-solid fa-user-plus',
        'bento_desc' => 'Register or update user accounts.',
        'bento_keywords' => 'add register approve verify driver license user account',
    ],
    [
        'route' => 'admin.reports.index',
        'active' => ['admin.reports.*'],
        'label' => 'Reports',
        'icon' => 'fa-solid fa-chart-line',
        'bento_title' => 'View Reports',
        'bento_icon' => 'fa-solid fa-chart-pie',
        'bento_desc' => 'Analyze system metrics and export CSVs.',
        'bento_keywords' => 'analytics stats export csv download data insights metrics',
    ],
    [
        'route' => 'admin.audit-log.index',
        'active' => ['admin.audit-log.*'],
        'label' => 'Audit Log',
        'icon' => 'fa-solid fa-clipboard-list',
        'bento_title' => 'Audit Log',
        'bento_icon' => 'fa-solid fa-clipboard-list',
        'bento_desc' => 'See what other admins have changed.',
        'bento_keywords' => 'audit log history actions accountability trail',
    ],
    [
        'route' => 'admin.messages.create',
        'active' => ['admin.messages.*'],
        'label' => 'Messages',
        'icon' => 'fa-solid fa-paper-plane',
        'bento_title' => 'Message Users',
        'bento_icon' => 'fa-solid fa-paper-plane',
        'bento_desc' => 'Notify one user, a role, or everyone.',
        'bento_keywords' => 'message notify broadcast send announce',
    ],
    [
        'route' => 'admin.system-settings.index',
        'active' => ['admin.system-settings.*'],
        'label' => 'Settings',
        'drawer_label' => 'System Settings',
        'icon' => 'fa-solid fa-sliders',
        'bento_title' => 'System Settings',
        'bento_icon' => 'fa-solid fa-sliders',
        'bento_desc' => 'Fuel price fallback and platform config.',
        'bento_keywords' => 'settings fuel price config system',
    ],
];
