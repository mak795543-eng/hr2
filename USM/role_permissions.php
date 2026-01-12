<?php
// role_permissions.php
return [
    // Administrator - Full access
    'manager' => [
        'dashboard',
        'learning_training',
        'competency_management',
        'succession_planning',
        'employee_self_service',
        'user_management' 
    ],
    
    // Employee
     'employee' => [
         'dashboard',
         'employee_self_service',
         'user_management'
    ],

    // // ADMIN
     'admin' => [
         'admin_dashboard',
         'admin_learning',
        'admin_competency',
        'admin_succession',
         'user_management',
        'admin_training',

    ],

    // // APPLICANTS
     'applicant' => [
      'Exam'
     ],


    // // TRAINING & DEVELOPMENT OFFICER
     'trainer' => [
      ''
     ],
   
     // // COMPETENCY COORDINATOR
     '' => [
      ''
     ],
];