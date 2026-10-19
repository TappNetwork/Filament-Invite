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
    'copy_link' => [
        'label' => 'Copy Invite Link',
        'modal_heading' => 'Copy Invite Link',
        'modal_description' => 'Share this link with the user so they can set their password. No email will be sent.',
        'field_label' => 'Invite link',
        'close' => 'Close',
    ],
];
