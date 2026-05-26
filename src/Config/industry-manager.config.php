<?php

return [
    'name' => 'Industry Manager',
    'version' => '0.1.0-dev',
    'author' => 'Matt Falahe',
    'description' => 'Blueprint browser, best-structure picker (rig-bonus aware), and industry jobs board. Read-only consumer of SeAT data — no ESI calls.',

    // Permission definitions surfaced for SeAT's permission picker.
    'permissions' => [
        'industry-manager.view' => 'View Industry Manager',
        'industry-manager.calculate' => 'Use the production calculator',
        'industry-manager.manage' => 'Manage Industry Manager settings',
        'industry-manager.admin' => 'Administer Industry Manager (diagnostic page)',
    ],

    // Menu configuration (sidebar nav resolves through Config/Menu/package.sidebar.php).
    'menu' => [
        'main' => [
            'name' => 'Industry Manager',
            'icon' => 'fas fa-industry',
            'route' => 'industry-manager.index',
            'permission' => 'industry-manager.view',
        ],
    ],
];
