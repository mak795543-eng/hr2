<?php
// role_permissions.php
return [
    'supervisor' => [
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
        'learning_management',
        'training_management',
        'competency_management',
        'succession_planning',
        'employee_self_service',
        'approvals' // For department-level approvals
    ],
    
    // Employee - Regular staff access
    'employee' => [
        'dashboard',
        'employee_self_service'
    ],

    // Admin - Full system access (super admin)
    'admin' => [
        'dashboard',
        'user_management',
        'training_management',
        'competency_management',
        'succession_planning',
        'approvals',
        'employee_self_service',
        'learning_management',
        'applicant_assessment' // Admin might need to view applicant assessments
    ],

    // Applicant - External exam takers
    'applicant' => [
        'applicant_assessment',
        'dashboard' // Optional: Simple dashboard for applicants
    ],

    // Trainer - Training development officer
    'trainer' => [
        'dashboard',
        'learning_management',
        'training_management',
        'approvals' // If trainers need to approve learning materials
    ],

    // Competency Coordinator
    'coordinator' => [
        'dashboard',
        'competency_management',
        'training_management' // Might need to create training based on gaps
    ],

    // HR Manager - Higher level approvals
    'hr_manager' => [
        'dashboard',
        'approvals',
        'employee_self_service',
        'learning_management',
        'training_management', // Might need to review all trainings
        'succession_planning' // HR typically handles succession
    ]
];