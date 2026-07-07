<?php

return [
    'bulk' => [
        'label' => 'Invite',
        'modal_heading' => 'Send Invite Emails',
        'notifications' => [
            'none' => [
                'title' => 'No invites sent',
                'body' => 'Selected users are already verified or cannot be invited.',
            ],
            'sent' => [
                'title' => 'Invites sent',
                'body' => '{1} :count invite sent.|[2,*] :count invites sent.',
            ],
            'skipped' => [
                'body' => '{1} :count user skipped.|[2,*] :count users skipped.',
            ],
        ],
    ],
];
