<?php
return [
    'label' => 'Accounting',
    'read_only' => true,
    'permissions' => [
        'm_accounting_accounts' => ['name' => 'Accounting Accounts', 'capabilities' => ['get' => 'List']],
    ],
    'resources' => [
        'accounts' => ['label' => 'Accounts', 'table' => 'tblacc_accounts'],
        'transactions' => ['label' => 'Transactions', 'table' => 'tblacc_account_history'],
    ],
];
