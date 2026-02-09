<?php
// role_permissions.php
return [
    'Admin' => [
        'dashboard',
        'learning_management',
        'training_management',
        'competency_management',
        'succession_planning',
        'approvals',
        'employee_self_service',
        'applicant_assessment',
        'table_reservation',
        'kitchen_orders',
        'inventory',
        'gap_analysis',
        'menu_management',
        'event_management',
        'table_turnover',
        'pos_system',
        'billing',
        'staff_management',
        'customer_feedback',
        'analytics',
        'user_management',

    ],

    'superviser' => [
        'dashboard',
        'learning_management',
        'training_management',
        'competency_management',
        'succession_planning',
        'approvals',
        'gap_analysis',
        'employee_self_service',
        'applicant_assessment',
        'table_reservation',
        'kitchen_orders',
        'inventory',
        'menu_management',
        'event_management',
        'table_turnover',
        'pos_system',
        'billing',
        'staff_management',
        'customer_feedback',
        'analytics',
        'user_management',

    ],

    // Manager - Department head access
    'manager' => [
        'dashboard',
        'approvals',
        'employee_self_service'
        // For department-level approvals
    ],

    // Manager - Department head access
    'HR Director' => [
        'dashboard',
        'Succession planning',
        'employee_self_service' // For department-level approvals
    ],

    // Employee - Regular staff access
    'sous chef' => [
        'dashboard',
        'employee_self_service'
    ],

    // Admin - Full system access (super admin)

    // Applicant - External exam takers
    'applicant' => [
        'applicant_assessment',
        'dashboard' // Optional: Simple dashboard for applicants
    ],

    // Trainer - Training development officer
    'Training & Development officer' => [
        'dashboard',
        'training_management',
        'employee_self_service'

    ],

    // Competency Coordinator
    'Competency coordinator' => [
        'dashboard',
        'competency_management',
        'employee_self_service'
    ],

    // HR Manager - Higher level approvals
    'Learning & Development officer' => [
        'dashboard',
        'learning_management',
        'employee_self_service'
    ]
];
