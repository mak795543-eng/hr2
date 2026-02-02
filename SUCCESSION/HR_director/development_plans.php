<?php

require_once __DIR__ . '/../../COMPETENCY/criticalgaps/config.php';

if (session_status() === PHP_SESSION_NONE) {
}

$DEVELOPMENT_PLANS = [
    'Front Office / Reception' => [
        'Customer Service Excellence' => [
            'Retrain' => 'Customer service policies, routine guest interactions, etiquette refresh',
            'Refresher Training' => 'Role-playing difficult scenarios, updated service standards',
            'Reskilling' => 'Digital concierge tools, online guest interactions',
            'Upskilling' => 'VIP protocols, personalized guest experience strategies',
            'Succession Ready' => 'Leading guest service operations, mentoring junior staff',
        ],
        'Effective Communication Skills' => [
            'Retrain' => 'Verbal and written communication workshops',
            'Refresher Training' => 'Email etiquette, phone handling, professional messaging',
            'Reskilling' => 'Social media and digital communication skills',
            'Upskilling' => 'Public speaking, cross-cultural communication',
            'Succession Ready' => 'Leading team briefings, executive guest interactions',
        ],
        'Problem-Solving & Complaint Handling' => [
            'Retrain' => 'Standard complaint handling',
            'Refresher Training' => 'Case studies and escalation procedures',
            'Reskilling' => 'Data-driven problem solving',
            'Upskilling' => 'Advanced negotiation and emotional intelligence',
            'Succession Ready' => 'Policy development, service improvement leadership',
        ],
        'Hotel Systems & Reservation Knowledge' => [
            'Retrain' => 'PMS basics, reservation logging',
            'Refresher Training' => 'Updated booking systems, troubleshooting',
            'Reskilling' => 'Channel management, online bookings',
            'Upskilling' => 'Data reporting, guest analytics',
            'Succession Ready' => 'Leading system training and technology upgrades',
        ],
        'Professional Appearance & Etiquette' => [
            'Retrain' => 'Uniform standards, grooming, basic etiquette',
            'Refresher Training' => 'Updated guest interaction protocols',
            'Reskilling' => 'Cultural etiquette for international guests',
            'Upskilling' => 'Executive presentation, VIP hosting',
            'Succession Ready' => 'Coaching staff on professional standards',
        ],
        'Time Management & Multitasking' => [
            'Retrain' => 'Prioritizing daily tasks',
            'Refresher Training' => 'Efficient check-in/out',
            'Reskilling' => 'Digital scheduling tools',
            'Upskilling' => 'High-volume multitasking strategies',
            'Succession Ready' => 'Leading front office workflow optimization',
        ],
        'Cultural Awareness & Guest Sensitivity' => [
            'Retrain' => 'Basic diversity awareness',
            'Refresher Training' => 'International guest expectations',
            'Reskilling' => 'Basic language skills',
            'Upskilling' => 'Inclusion strategies, VIP profiling',
            'Succession Ready' => 'Cross-cultural training leadership',
        ],
    ],
    'Housekeeping' => [
        'Attention to Detail' => [
            'Retrain' => 'Room cleaning standards',
            'Refresher Training' => 'Spot checks, visual inspection',
            'Reskilling' => 'Digital tracking tools',
            'Upskilling' => 'Quality auditing, performance metrics',
            'Succession Ready' => 'Supervising quality control',
        ],
        'Knowledge of Cleaning Procedures & Chemicals' => [
            'Retrain' => 'Correct chemical and tool usage',
            'Refresher Training' => 'Updated chemicals and safety',
            'Reskilling' => 'Eco-friendly cleaning methods',
            'Upskilling' => 'Process optimization',
            'Succession Ready' => 'Leading procedural updates',
        ],
        'Time Management' => [
            'Retrain' => 'Standard room turnaround',
            'Refresher Training' => 'Prioritization at peak occupancy',
            'Reskilling' => 'Workflow scheduling tools',
            'Upskilling' => 'Advanced task management',
            'Succession Ready' => 'Planning housekeeping schedules',
        ],
        'Health & Safety Awareness' => [
            'Retrain' => 'Fire, hazard, sanitation basics',
            'Refresher Training' => 'Updated occupational safety',
            'Reskilling' => 'Emergency drills, accident prevention',
            'Upskilling' => 'Safety audits, compliance leadership',
            'Succession Ready' => 'Policy creation and team safety training',
        ],
        'Teamwork & Coordination' => [
            'Retrain' => 'Collaboration exercises',
            'Refresher Training' => 'Cross-department interaction',
            'Reskilling' => 'Conflict resolution, collaborative tools',
            'Upskilling' => 'Resource allocation and efficiency',
            'Succession Ready' => 'Mentoring supervisors',
        ],
        'Physical Endurance & Task Discipline' => [
            'Retrain' => 'Correct posture, lifting',
            'Refresher Training' => 'Ergonomics and repetitive task safety',
            'Reskilling' => 'Automation tools to reduce strain',
            'Upskilling' => 'Process efficiency, workload management',
            'Succession Ready' => 'Coaching and long-term health practices',
        ],
        'Quality Control Awareness' => [
            'Retrain' => 'Standard inspections',
            'Refresher Training' => 'Audit updates, scoring systems',
            'Reskilling' => 'Data analysis for recurring issues',
            'Upskilling' => 'Advanced QC techniques',
            'Succession Ready' => 'Leading audits and improvement initiatives',
        ],
    ],
    'Food & Beverage (F&B)' => [
        'Guest Service & Hospitality Skills' => [
            'Retrain' => 'Basic service protocols',
            'Refresher Training' => 'Handling complaints, upselling techniques',
            'Reskilling' => 'Digital ordering systems',
            'Upskilling' => 'Fine dining and VIP service',
            'Succession Ready' => 'Leading service standards and mentoring staff',
        ],
        'Food Safety & Hygiene Knowledge' => [
            'Retrain' => 'Basic food safety rules',
            'Refresher Training' => 'Updated sanitation standards',
            'Reskilling' => 'HACCP and allergen management',
            'Upskilling' => 'Advanced hygiene audits',
            'Succession Ready' => 'Policy leadership, team training',
        ],
        'Product Knowledge' => [
            'Retrain' => 'Menu item review',
            'Refresher Training' => 'Ingredient updates, new dishes',
            'Reskilling' => 'Digital POS systems, inventory tracking',
            'Upskilling' => 'Beverage pairing, wine/spirits expertise',
            'Succession Ready' => 'Menu strategy and training leadership',
        ],
        'Communication & Coordination' => [
            'Retrain' => 'Kitchen-service coordination basics',
            'Refresher Training' => 'Shift handovers, reporting issues',
            'Reskilling' => 'Team collaboration tools',
            'Upskilling' => 'Conflict resolution, high-pressure communication',
            'Succession Ready' => 'Leading service and kitchen coordination',
        ],
        'Sales & Upselling Skills' => [
            'Retrain' => 'Standard upselling techniques',
            'Refresher Training' => 'Promotional campaigns, suggestive selling',
            'Reskilling' => 'Digital sales tools',
            'Upskilling' => 'Revenue maximization strategies',
            'Succession Ready' => 'Sales strategy planning, coaching juniors',
        ],
        'Stress & Time Management' => [
            'Retrain' => 'Task prioritization under normal service',
            'Refresher Training' => 'Peak hour handling techniques',
            'Reskilling' => 'Workflow optimization tools',
            'Upskilling' => 'Advanced multitasking strategies',
            'Succession Ready' => 'Leading efficiency improvement initiatives',
        ],
        'Professional Conduct & Service Etiquette' => [
            'Retrain' => 'Service manners',
            'Refresher Training' => 'Modern etiquette updates',
            'Reskilling' => 'Cultural service standards',
            'Upskilling' => 'VIP hosting and fine dining protocols',
            'Succession Ready' => 'Mentoring staff, enforcing service excellence',
        ],
    ],
    'Kitchen / Culinary' => [
        'Food Preparation & Culinary Fundamentals' => [
            'Retrain' => 'Basic cooking techniques',
            'Refresher Training' => 'Recipe updates, portion control',
            'Reskilling' => 'Modern cooking methods, equipment use',
            'Upskilling' => 'Advanced culinary techniques, plating design',
            'Succession Ready' => 'Leading kitchen innovation and mentoring chefs',
        ],
        'Food Safety & Sanitation Compliance' => [
            'Retrain' => 'Hygiene basics',
            'Refresher Training' => 'HACCP updates',
            'Reskilling' => 'Allergen and dietary compliance',
            'Upskilling' => 'Kitchen audits, safety inspections',
            'Succession Ready' => 'Policy leadership, training kitchen staff',
        ],
        'Time & Workflow Management' => [
            'Retrain' => 'Standard prep and service timings',
            'Refresher Training' => 'Coordination under peak hours',
            'Reskilling' => 'Digital kitchen management',
            'Upskilling' => 'Advanced scheduling and multitasking',
            'Succession Ready' => 'Kitchen operational planning',
        ],
        'Teamwork & Kitchen Coordination' => [
            'Retrain' => 'Brigade collaboration basics',
            'Refresher Training' => 'Shift coordination',
            'Reskilling' => 'Conflict resolution',
            'Upskilling' => 'Leadership and delegation',
            'Succession Ready' => 'Leading kitchen operations, mentoring juniors',
        ],
        'Attention to Quality & Presentation' => [
            'Retrain' => 'Plating and portion standards',
            'Refresher Training' => 'Consistency checks',
            'Reskilling' => 'Digital feedback tracking',
            'Upskilling' => 'Creative presentation techniques',
            'Succession Ready' => 'Leading quality improvement initiatives',
        ],
        'Equipment Handling & Safety Awareness' => [
            'Retrain' => 'Basic equipment use',
            'Refresher Training' => 'Updated safety protocols',
            'Reskilling' => 'Advanced machinery operation',
            'Upskilling' => 'Equipment maintenance planning',
            'Succession Ready' => 'Leading safety programs',
        ],
        'Waste Control & Cost Awareness' => [
            'Retrain' => 'Standard portioning',
            'Refresher Training' => 'Waste reduction techniques',
            'Reskilling' => 'Inventory tracking tools',
            'Upskilling' => 'Cost optimization strategies',
            'Succession Ready' => 'Leading procurement and cost control programs',
        ],
    ],
    'Sales & Marketing' => [
        'Communication & Presentation Skills' => [
            'Retrain' => 'Basic client interactions',
            'Refresher Training' => 'Presentation updates',
            'Reskilling' => 'Digital presentation tools',
            'Upskilling' => 'Public speaking and persuasion',
            'Succession Ready' => 'Leading client presentations, coaching juniors',
        ],
        'Customer Relationship Management (CRM)' => [
            'Retrain' => 'Basic client records',
            'Refresher Training' => 'CRM tool usage',
            'Reskilling' => 'Advanced CRM analytics',
            'Upskilling' => 'Strategic relationship building',
            'Succession Ready' => 'Leading CRM implementation and team training',
        ],
        'Market & Trend Awareness' => [
            'Retrain' => 'Industry basics',
            'Refresher Training' => 'Competitor analysis updates',
            'Reskilling' => 'Digital trend monitoring tools',
            'Upskilling' => 'Market forecasting',
            'Succession Ready' => 'Strategy planning and mentorship',
        ],
        'Negotiation & Persuasion Skills' => [
            'Retrain' => 'Standard negotiation techniques',
            'Refresher Training' => 'Advanced deal closing scenarios',
            'Reskilling' => 'Digital negotiation simulations',
            'Upskilling' => 'Cross-cultural negotiation',
            'Succession Ready' => 'Leading contract negotiations, coaching juniors',
        ],
        'Analytical & Reporting Skills' => [
            'Retrain' => 'Basic data reporting',
            'Refresher Training' => 'Dashboard updates, KPI tracking',
            'Reskilling' => 'Analytics software usage',
            'Upskilling' => 'Data interpretation for strategy',
            'Succession Ready' => 'Leading reporting processes, decision-making guidance',
        ],
        'Planning & Coordination' => [
            'Retrain' => 'Event and campaign basics',
            'Refresher Training' => 'Updated workflows',
            'Reskilling' => 'Project management tools',
            'Upskilling' => 'Strategic campaign planning',
            'Succession Ready' => 'Leading marketing strategy implementation',
        ],
        'Professional Branding Awareness' => [
            'Retrain' => 'Brand guidelines',
            'Refresher Training' => 'Brand updates',
            'Reskilling' => 'Digital branding tools',
            'Upskilling' => 'Campaign development and brand strategy',
            'Succession Ready' => 'Leading brand management initiatives',
        ],
    ],
    'Human Resources (HR)' => [
        'Interpersonal & Communication Skills' => [
            'Retrain' => 'Employee interaction basics',
            'Refresher Training' => 'Feedback and appraisal communication',
            'Reskilling' => 'Digital HR communication platforms',
            'Upskilling' => 'Conflict management, negotiation',
            'Succession Ready' => 'Leading employee relations strategy',
        ],
        'Confidentiality & Ethical Judgment' => [
            'Retrain' => 'Data privacy basics',
            'Refresher Training' => 'HR ethical policies updates',
            'Reskilling' => 'Digital HRIS security',
            'Upskilling' => 'Advanced compliance management',
            'Succession Ready' => 'Policy creation and enforcement',
        ],
        'Policy & Labor Law Awareness' => [
            'Retrain' => 'Basic HR policies',
            'Refresher Training' => 'Updated labor laws',
            'Reskilling' => 'Compliance tracking tools',
            'Upskilling' => 'Strategic HR policy planning',
            'Succession Ready' => 'Leading policy development',
        ],
        'Organizational & Documentation Skills' => [
            'Retrain' => 'Employee record keeping',
            'Refresher Training' => 'Reporting updates',
            'Reskilling' => 'Digital documentation systems',
            'Upskilling' => 'HR workflow optimization',
            'Succession Ready' => 'Leading documentation process improvements',
        ],
        'Problem-Solving & Decision-Making' => [
            'Retrain' => 'Routine HR issue handling',
            'Refresher Training' => 'Complex scenario roleplay',
            'Reskilling' => 'Data-driven decision-making tools',
            'Upskilling' => 'Strategic HR problem solving',
            'Succession Ready' => 'Leading HR policy and workforce planning',
        ],
        'Training & Development Awareness' => [
            'Retrain' => 'Employee onboarding',
            'Refresher Training' => 'Updated learning programs',
            'Reskilling' => 'Digital LMS platforms',
            'Upskilling' => 'Learning pathway design',
            'Succession Ready' => 'Leading organizational development strategy',
        ],
        'System & HRIS Proficiency' => [
            'Retrain' => 'Basic HRIS usage',
            'Refresher Training' => 'System updates',
            'Reskilling' => 'Advanced HRIS analytics',
            'Upskilling' => 'Workflow integration',
            'Succession Ready' => 'Leading HR technology adoption',
        ],
    ],
    'Finance / Accounting' => [
        'Financial Accuracy & Attention to Detail' => [
            'Retrain' => 'Basic accounting principles',
            'Refresher Training' => 'Error-checking techniques',
            'Reskilling' => 'Accounting software updates',
            'Upskilling' => 'Advanced reconciliation',
            'Succession Ready' => 'Financial audit leadership',
        ],
        'Numerical & Analytical Skills' => [
            'Retrain' => 'Core calculations',
            'Refresher Training' => 'KPI monitoring',
            'Reskilling' => 'Data analysis tools',
            'Upskilling' => 'Forecasting and scenario planning',
            'Succession Ready' => 'Strategic financial decision-making',
        ],
        'Compliance & Policy Awareness' => [
            'Retrain' => 'Standard accounting policies',
            'Refresher Training' => 'Regulatory updates',
            'Reskilling' => 'Digital compliance tools',
            'Upskilling' => 'Risk assessment',
            'Succession Ready' => 'Policy leadership and oversight',
        ],
        'Confidentiality & Integrity' => [
            'Retrain' => 'Data handling basics',
            'Refresher Training' => 'Updated financial ethics',
            'Reskilling' => 'Digital confidentiality tools',
            'Upskilling' => 'Fraud detection and prevention',
            'Succession Ready' => 'Leading financial governance',
        ],
        'Reporting & Documentation Skills' => [
            'Retrain' => 'Standard reports',
            'Refresher Training' => 'Updated reporting formats',
            'Reskilling' => 'Digital reporting systems',
            'Upskilling' => 'Data interpretation for decision-making',
            'Succession Ready' => 'Leading finance reporting strategy',
        ],
        'Time Management & Deadline Control' => [
            'Retrain' => 'Meeting monthly deadlines',
            'Refresher Training' => 'Process optimization',
            'Reskilling' => 'Automated scheduling tools',
            'Upskilling' => 'Strategic planning for peak periods',
            'Succession Ready' => 'Leading financial operations planning',
        ],
        'Cost Control Awareness' => [
            'Retrain' => 'Expense monitoring basics',
            'Refresher Training' => 'Budget reviews',
            'Reskilling' => 'Cost-tracking tools',
            'Upskilling' => 'Advanced financial optimization',
            'Succession Ready' => 'Leading budget planning initiatives',
        ],
    ],
    'Engineering / Maintenance' => [
        'Technical & Mechanical Knowledge' => [
            'Retrain' => 'Equipment operation basics',
            'Refresher Training' => 'Updated technical procedures',
            'Reskilling' => 'Digital diagnostic tools',
            'Upskilling' => 'Advanced mechanical troubleshooting',
            'Succession Ready' => 'Leading technical strategy and team mentoring',
        ],
        'Preventive Maintenance Skills' => [
            'Retrain' => 'Routine checks',
            'Refresher Training' => 'Updated maintenance schedules',
            'Reskilling' => 'Predictive maintenance tools',
            'Upskilling' => 'Maintenance optimization',
            'Succession Ready' => 'Leading preventive maintenance planning',
        ],
        'Health & Safety Compliance' => [
            'Retrain' => 'Safety procedures basics',
            'Refresher Training' => 'Updated safety regulations',
            'Reskilling' => 'Emergency response simulations',
            'Upskilling' => 'Safety audits',
            'Succession Ready' => 'Leading compliance programs',
        ],
        'Problem Diagnosis & Troubleshooting' => [
            'Retrain' => 'Basic fault detection',
            'Refresher Training' => 'Complex problem handling',
            'Reskilling' => 'Diagnostic software tools',
            'Upskilling' => 'Advanced troubleshooting methodologies',
            'Succession Ready' => 'Leading technical problem-solving initiatives',
        ],
        'Documentation & Reporting Skills' => [
            'Retrain' => 'Maintenance logs',
            'Refresher Training' => 'Updated reporting procedures',
            'Reskilling' => 'Digital documentation systems',
            'Upskilling' => 'Analytical reporting for operations',
            'Succession Ready' => 'Leading maintenance reporting strategy',
        ],
        'Team Coordination & Communication' => [
            'Retrain' => 'Basic team collaboration',
            'Refresher Training' => 'Cross-department coordination',
            'Reskilling' => 'Project management tools',
            'Upskilling' => 'Leadership in team operations',
            'Succession Ready' => 'Leading departmental communication',
        ],
        'Emergency Response Readiness' => [
            'Retrain' => 'Standard emergency procedures',
            'Refresher Training' => 'Drills and simulations',
            'Reskilling' => 'Crisis management software',
            'Upskilling' => 'Strategic emergency planning',
            'Succession Ready' => 'Leading emergency preparedness programs',
        ],
    ],
    'Security' => [
        'Observation & Situational Awareness' => [
            'Retrain' => 'Patrolling basics',
            'Refresher Training' => 'Updated monitoring techniques',
            'Reskilling' => 'CCTV and digital surveillance',
            'Upskilling' => 'Threat assessment and prevention strategies',
            'Succession Ready' => 'Leading security operations',
        ],
        'Emergency Response & Crisis Handling' => [
            'Retrain' => 'Fire and safety drills',
            'Refresher Training' => 'Updated response protocols',
            'Reskilling' => 'Coordinated crisis simulations',
            'Upskilling' => 'Risk management strategies',
            'Succession Ready' => 'Leading emergency planning and team training',
        ],
        'Communication & Reporting Skills' => [
            'Retrain' => 'Incident reporting basics',
            'Refresher Training' => 'Updated protocols',
            'Reskilling' => 'Digital reporting tools',
            'Upskilling' => 'Analytical reporting and briefings',
            'Succession Ready' => 'Leading security reporting standards',
        ],
        'Access Control & Patrol Skills' => [
            'Retrain' => 'Access procedures basics',
            'Refresher Training' => 'Updated security protocols',
            'Reskilling' => 'Digital access systems',
            'Upskilling' => 'Strategic security planning',
            'Succession Ready' => 'Leading access and patrol policy implementation',
        ],
        'Conflict Management & De-escalation' => [
            'Retrain' => 'Basic conflict resolution',
            'Refresher Training' => 'Updated de-escalation techniques',
            'Reskilling' => 'Behavioral analysis',
            'Upskilling' => 'Advanced negotiation skills',
            'Succession Ready' => 'Leading conflict management training',
        ],
        'Discipline & Professional Conduct' => [
            'Retrain' => 'Code of conduct',
            'Refresher Training' => 'Updated standards',
            'Reskilling' => 'Leadership principles',
            'Upskilling' => 'Professional mentoring',
            'Succession Ready' => 'Leading security team conduct standards',
        ],
        'Safety & Risk Awareness' => [
            'Retrain' => 'Hazard identification',
            'Refresher Training' => 'Risk mitigation updates',
            'Reskilling' => 'Digital risk monitoring tools',
            'Upskilling' => 'Strategic risk assessment',
            'Succession Ready' => 'Leading safety and risk programs',
        ],
    ],
];

if (!function_exists('getRolePlanSkillId')) {
    function getRolePlanSkillId(): int {
        return 0;
    }
}

if (!function_exists('getDevelopmentPlansRepo')) {
    function getDevelopmentPlansRepo() {
        global $pdo;
        global $DEVELOPMENT_PLANS;

        if (!$pdo) {
            return [];
        }

        ensureDevelopmentPlansSchema();
        seedDevelopmentPlansIfEmpty($DEVELOPMENT_PLANS);

        $rolePlanSkillId = 0;

        $repo = [];
        $statuses = ['Retrain', 'Reskilling', 'Refresher Training', 'Upskilling', 'Succession Ready'];

        $stmt = $pdo->prepare(
            "SELECT dpi.department,
                    COALESCE(dpi.role, '') AS role_key,
                    dpi.status,
                    dpi.plan_text,
                    COALESCE(dpi.delivery_mode, 'Onsite') AS delivery_mode,
                    dpi.target_percentage
             FROM development_plan_items dpi
             WHERE dpi.skill_id = ?
             ORDER BY dpi.department ASC, role_key ASC, dpi.status ASC"
        );
        $stmt->execute([$rolePlanSkillId]);
        $rows = $stmt->fetchAll();

        foreach ($rows as $r) {
            $dept = (string)($r['department'] ?? '');
            $role = (string)($r['role_key'] ?? '');
            $status = (string)($r['status'] ?? '');
            $planText = (string)($r['plan_text'] ?? '');
            $deliveryMode = (string)($r['delivery_mode'] ?? 'Onsite');
            $targetPct = $r['target_percentage'] ?? null;

            if ($dept === '' || $status === '') {
                continue;
            }

            if (!isset($repo[$dept])) {
                $repo[$dept] = [];
            }
            if (!isset($repo[$dept][$role])) {
                $repo[$dept][$role] = [
                    'plans' => [],
                    'modes' => [],
                    'targets' => [],
                ];
            }

            $repo[$dept][$role]['plans'][$status] = $planText;
            $repo[$dept][$role]['modes'][$status] = $deliveryMode;
            $repo[$dept][$role]['targets'][$status] = $targetPct;
        }

        foreach ($repo as $dept => $skillsById) {
            foreach ((array)$skillsById as $roleKey => $roleData) {
                foreach ($statuses as $st) {
                    if (!isset($repo[$dept][$roleKey]['plans'][$st])) {
                        $repo[$dept][$roleKey]['plans'][$st] = '';
                    }
                    if (!isset($repo[$dept][$roleKey]['modes'][$st])) {
                        $repo[$dept][$roleKey]['modes'][$st] = 'Onsite';
                    }
                    if (!isset($repo[$dept][$roleKey]['targets'][$st])) {
                        $repo[$dept][$roleKey]['targets'][$st] = null;
                    }
                }
            }
        }

        foreach ($repo as $dept => $rolesByName) {
            if (!isset($repo[$dept][''])) {
                continue;
            }
            foreach ((array)$rolesByName as $roleKey => $roleData) {
                $roleKey = (string)$roleKey;
                if ($roleKey === '') {
                    continue;
                }
                foreach ($statuses as $st) {
                    $basePlan = (string)($repo[$dept]['']['plans'][$st] ?? '');
                    $baseMode = (string)($repo[$dept]['']['modes'][$st] ?? 'Onsite');
                    $baseTarget = $repo[$dept]['']['targets'][$st] ?? null;

                    $curPlan = (string)($repo[$dept][$roleKey]['plans'][$st] ?? '');
                    if ($curPlan === '' && $basePlan !== '') {
                        $repo[$dept][$roleKey]['plans'][$st] = $basePlan;
                        $repo[$dept][$roleKey]['modes'][$st] = $baseMode;
                        $repo[$dept][$roleKey]['targets'][$st] = $baseTarget;
                    }
                }
            }
        }

        return $repo;
    }
}

if (!function_exists('getSuggestedPlansForDepartmentStatus')) {
    function getSuggestedPlansForDepartmentStatus($department, $status, $role = null) {
        global $pdo;
        global $DEVELOPMENT_PLANS;

        $department = trim((string)$department);
        $status = trim((string)$status);
        $role = $role === null ? null : trim((string)$role);

        $allowedStatuses = ['Retrain', 'Reskilling', 'Refresher Training', 'Upskilling', 'Succession Ready'];
        if ($department === '' || $status === '' || !in_array($status, $allowedStatuses, true)) {
            return [];
        }

        if (!$pdo) {
            return [];
        }

        ensureDevelopmentPlansSchema();
        seedDevelopmentPlansIfEmpty($DEVELOPMENT_PLANS);

        $rolePlanSkillId = 0;

        $planText = '';
        $deliveryMode = 'Onsite';
        try {
            if ($role !== null && $role !== '') {
                $stmt = $pdo->prepare(
                    "SELECT plan_text, COALESCE(delivery_mode,'Onsite') AS delivery_mode
                     FROM development_plan_items
                     WHERE department = ? AND role = ? AND status = ? AND skill_id = ?
                     LIMIT 1"
                );
                $stmt->execute([$department, $role, $status, $rolePlanSkillId]);
                $r = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($r) {
                    $planText = trim((string)($r['plan_text'] ?? ''));
                    $deliveryMode = (string)($r['delivery_mode'] ?? 'Onsite');
                }
            }
        } catch (Throwable $e) {
        }

        if ($planText === '') {
            try {
                $stmt = $pdo->prepare(
                    "SELECT plan_text, COALESCE(delivery_mode,'Onsite') AS delivery_mode
                     FROM development_plan_items
                     WHERE department = ? AND COALESCE(role,'') = '' AND status = ? AND skill_id = ?
                     LIMIT 1"
                );
                $stmt->execute([$department, $status, $rolePlanSkillId]);
                $r = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($r) {
                    $planText = trim((string)($r['plan_text'] ?? ''));
                    $deliveryMode = (string)($r['delivery_mode'] ?? 'Onsite');
                }
            } catch (Throwable $e) {
            }
        }

        if ($planText === '') {
            return [];
        }

        return [
            'Role Development Plan' => [
                'plan_text' => $planText,
                'delivery_mode' => $deliveryMode,
            ]
        ];
    }
}

if (!function_exists('h')) {
    function h($v) {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('splitPlanItems')) {
    function splitPlanItems($planText) {
        $planText = trim((string)$planText);
        if ($planText === '') {
            return [];
        }

        $planText = str_replace(["\r\n", "\r"], "\n", $planText);
        $parts = preg_split('/\s*,\s*|\n+/', $planText);
        if (!is_array($parts)) {
            $parts = [$planText];
        }

        $out = [];
        foreach ($parts as $p) {
            $p = trim((string)$p);
            if (strpos($p, '- ') === 0) {
                $p = trim(substr($p, 2));
            }
            if ($p !== '') {
                $out[] = $p;
            }
        }

        return $out;
    }
}

if (!function_exists('ensureDevelopmentPlansSchema')) {
    function ensureDevelopmentPlansSchema() {
        global $pdo;
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        if (!$pdo) {
            return;
        }

        try {
            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS development_plan_items (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    department VARCHAR(100) NOT NULL,
                    role VARCHAR(100) NOT NULL DEFAULT '',
                    skill_id INT NOT NULL DEFAULT 0,
                    status ENUM('Retrain','Reskilling','Refresher Training','Upskilling','Succession Ready') NOT NULL,
                    plan_text TEXT NOT NULL,
                    delivery_mode ENUM('Onsite','Online') NOT NULL DEFAULT 'Onsite',
                    target_percentage DECIMAL(5,2) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uniq_devplan (department, role, skill_id, status),
                    INDEX idx_dept_status (department, status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
        } catch (Throwable $e) {
        }

        try {
            $pdo->exec("ALTER TABLE development_plan_items DROP FOREIGN KEY fk_devplan_skill");
        } catch (Throwable $e) {
        }

        try {
            $stmtFk = $pdo->query(
                "SELECT CONSTRAINT_NAME
                 FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'development_plan_items'
                   AND COLUMN_NAME = 'skill_id'
                   AND REFERENCED_TABLE_NAME = 'skills'
                   AND CONSTRAINT_NAME IS NOT NULL"
            );
            $fks = $stmtFk ? $stmtFk->fetchAll(PDO::FETCH_COLUMN) : [];
            if (is_array($fks)) {
                foreach ($fks as $fkName) {
                    $fkName = trim((string)$fkName);
                    if ($fkName === '') {
                        continue;
                    }
                    try {
                        $pdo->exec("ALTER TABLE development_plan_items DROP FOREIGN KEY `" . str_replace('`', '``', $fkName) . "`");
                    } catch (Throwable $e) {
                    }
                }
            }
        } catch (Throwable $e) {
        }

        try {
            $pdo->exec("ALTER TABLE development_plan_items MODIFY COLUMN skill_id INT NOT NULL DEFAULT 0");
        } catch (Throwable $e) {
        }

        try {
            $pdo->exec("ALTER TABLE development_plan_items ADD COLUMN delivery_mode ENUM('Onsite','Online') NOT NULL DEFAULT 'Onsite'");
        } catch (Throwable $e) {
        }

        try {
            $pdo->exec("ALTER TABLE development_plan_items ADD COLUMN target_percentage DECIMAL(5,2) NULL");
        } catch (Throwable $e) {
        }
    }
}

if (!function_exists('seedDevelopmentPlansIfEmpty')) {
    function seedDevelopmentPlansIfEmpty($seedRepo) {
        global $pdo;
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        if (!$pdo) {
            return;
        }

        ensureDevelopmentPlansSchema();

        if (!is_array($seedRepo) || count($seedRepo) === 0) {
            return;
        }

        $rolePlanSkillId = 0;

        $insPlan = $pdo->prepare(
            "INSERT INTO development_plan_items (department, role, skill_id, status, plan_text, delivery_mode)
             VALUES (?, ?, ?, ?, ?, 'Onsite')
             ON DUPLICATE KEY UPDATE
                plan_text = CASE WHEN TRIM(COALESCE(plan_text,'')) = '' THEN VALUES(plan_text) ELSE plan_text END,
                delivery_mode = CASE WHEN TRIM(COALESCE(plan_text,'')) = '' THEN VALUES(delivery_mode) ELSE delivery_mode END"
        );

        foreach ($seedRepo as $dept => $areas) {
            if (!is_array($areas)) {
                continue;
            }
            $dept = trim((string)$dept);
            if ($dept === '') {
                continue;
            }

            $byStatusMerged = [];
            foreach ($areas as $areaName => $byStatus) {
                if (!is_array($byStatus)) {
                    continue;
                }
                foreach ($byStatus as $status => $planText) {
                    $status = trim((string)$status);
                    $planText = trim((string)$planText);
                    if ($status === '' || $planText === '') {
                        continue;
                    }
                    if (!isset($byStatusMerged[$status])) {
                        $byStatusMerged[$status] = [];
                    }
                    $byStatusMerged[$status][] = $planText;
                }
            }

            foreach ($byStatusMerged as $status => $texts) {
                $merged = implode("\n", array_values(array_unique(array_filter(array_map('trim', (array)$texts), function ($v) { return $v !== ''; }))));
                if ($merged === '') {
                    continue;
                }
                try {
                    $insPlan->execute([$dept, '', $rolePlanSkillId, $status, $merged]);
                } catch (Throwable $e) {
                }
            }
        }
    }
}

$isDirect = realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'] ?? '');
if (!$isDirect) {
    return;
}

$flash = null;
if (isset($_SESSION['dp_flash']) && is_array($_SESSION['dp_flash'])) {
    $flash = $_SESSION['dp_flash'];
    unset($_SESSION['dp_flash']);
}

ensureDevelopmentPlansSchema();
seedDevelopmentPlansIfEmpty($DEVELOPMENT_PLANS);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));
    $allowedStatuses = ['Retrain', 'Reskilling', 'Refresher Training', 'Upskilling', 'Succession Ready'];
    $allowedModes = ['Onsite', 'Online'];

    $redirectDept = trim((string)($_POST['department'] ?? ''));

    try {
        if ($action === 'save_plan') {
            $department = trim((string)($_POST['department'] ?? ''));
            $role = trim((string)($_POST['role'] ?? ''));
            $status = trim((string)($_POST['status'] ?? ''));
            $planText = trim((string)($_POST['plan_text'] ?? ''));
            $deliveryMode = trim((string)($_POST['delivery_mode'] ?? 'Onsite'));
            $targetPctRaw = trim((string)($_POST['target_percentage'] ?? ''));

            if ($department === '' || $status === '' || !in_array($status, $allowedStatuses, true)) {
                throw new RuntimeException('Invalid request.');
            }

            $skillId = 0;

            if (!in_array($deliveryMode, $allowedModes, true)) {
                $deliveryMode = 'Onsite';
            }

            $targetPct = null;
            if ($targetPctRaw !== '' && is_numeric($targetPctRaw)) {
                $targetPct = (float)$targetPctRaw;
                if ($targetPct < 0) $targetPct = 0;
                if ($targetPct > 100) $targetPct = 100;
            }

            $stmt = $pdo->prepare(
                "INSERT INTO development_plan_items (department, role, skill_id, status, plan_text, delivery_mode, target_percentage)
                 VALUES (?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE plan_text = VALUES(plan_text), delivery_mode = VALUES(delivery_mode), target_percentage = VALUES(target_percentage)"
            );
            $stmt->execute([$department, $role, $skillId, $status, $planText, $deliveryMode, $targetPct]);
            $_SESSION['dp_flash'] = ['type' => 'success', 'message' => 'Plan updated.'];
        }
    } catch (Throwable $e) {
        $_SESSION['dp_flash'] = [
            'type' => 'error',
            'message' => ($e->getMessage() !== '' ? $e->getMessage() : 'Request failed.'),
        ];
    }

    $target = $_SERVER['PHP_SELF'] ?? '';
    if ($redirectDept !== '') {
        $target .= '?open_dept=' . rawurlencode($redirectDept);
    }
    header('Location: ' . $target);
    exit;
}

$deptRoles = [];
try {
    $stmt = $pdo->query(
        "SELECT DISTINCT department, position AS role
         FROM employees
         WHERE position IS NOT NULL AND position <> ''
         UNION
         SELECT DISTINCT department, position AS role
         FROM succession_submissions
         WHERE position IS NOT NULL AND position <> ''"
    );
    $rows = $stmt->fetchAll();
    foreach ($rows as $r) {
        $d = trim((string)($r['department'] ?? ''));
        $p = trim((string)($r['role'] ?? ''));
        if ($d === '' || $p === '') {
            continue;
        }
        if (!isset($deptRoles[$d])) {
            $deptRoles[$d] = [];
        }
        $deptRoles[$d][$p] = true;
    }
} catch (Throwable $e) {
}

foreach ($deptRoles as $d => $roles) {
    if (!isset($deptRoles[$d][''])) {
        $deptRoles[$d][''] = true;
    }
}

$repo = getDevelopmentPlansRepo();
ksort($repo);

foreach ($deptRoles as $d => $roles) {
    if (!isset($repo[$d])) {
        $repo[$d] = [];
    }
    foreach ((array)$roles as $rk => $v) {
        $rk = (string)$rk;
        if (!isset($repo[$d][$rk])) {
            $repo[$d][$rk] = [
                'plans' => [],
                'modes' => [],
                'targets' => [],
            ];
        }
    }
}

$statuses = ['Retrain', 'Reskilling', 'Refresher Training', 'Upskilling', 'Succession Ready'];
foreach ($repo as $d => $rolesByName) {
    if (!isset($repo[$d][''])) {
        continue;
    }
    foreach ((array)$rolesByName as $rk => $rd) {
        $rk = (string)$rk;
        if ($rk === '') {
            continue;
        }
        foreach ($statuses as $st) {
            $basePlan = (string)($repo[$d]['']['plans'][$st] ?? '');
            $baseMode = (string)($repo[$d]['']['modes'][$st] ?? 'Onsite');
            $baseTarget = $repo[$d]['']['targets'][$st] ?? null;

            if (!isset($repo[$d][$rk]['plans'][$st]) || trim((string)$repo[$d][$rk]['plans'][$st]) === '') {
                if ($basePlan !== '') {
                    $repo[$d][$rk]['plans'][$st] = $basePlan;
                }
            }
            if (!isset($repo[$d][$rk]['modes'][$st]) || trim((string)$repo[$d][$rk]['modes'][$st]) === '') {
                $repo[$d][$rk]['modes'][$st] = $baseMode;
            }
            if (!array_key_exists($st, (array)($repo[$d][$rk]['targets'] ?? []))) {
                $repo[$d][$rk]['targets'][$st] = $baseTarget;
            }
        }
    }
}

$departments = array_keys($repo);
sort($departments);

require('../../partials/header.php');
?>
 <body class="bg-gray-50 min-h-screen">
 <div class="flex h-screen">
    <!-- Sidebar -->
    <?php 
    // Use relative path or absolute path based on your directory structure
    include '../../USM/sidebarr.php'; 
    ?>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto">
      <!-- Navbar -->
      <?php include '../../USM/navbar.php'; ?>
    
            
            <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-y-auto">

    <div class="max-w-7xl mx-auto p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-base-content">Development Plans Repository</h1>
                <div class="text-sm text-base-content/70">Editable repository per Department / Role / Status.</div>
            </div>
            <div class="w-full md:w-auto flex flex-col gap-2">
                <input id="search" type="text" placeholder="Search department, skill, or plan..." class="input input-bordered w-full md:w-96" />
            </div>
        </div>

        <div id="dept-grid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            <?php foreach ($repo as $deptName => $rolesByName): ?>
                <?php
                    $deptNameStr = (string)$deptName;
                    $roleCount = is_array($rolesByName) ? count($rolesByName) : 0;
                    $deptModalId = 'modal_dept_' . substr(md5($deptNameStr), 0, 10);
                    $deptSearchParts = [$deptNameStr];
                    if (is_array($rolesByName)) {
                        foreach ($rolesByName as $rk => $rd) {
                            $rk = (string)$rk;
                            $deptSearchParts[] = ($rk !== '' ? $rk : 'All Roles');
                            $pmap = (array)(($rd['plans'] ?? []));
                            foreach ($pmap as $pv) {
                                $deptSearchParts[] = (string)$pv;
                            }
                        }
                    }
                    $deptSearchText = implode(' ', $deptSearchParts);
                ?>
                <div class="dept-card card bg-base-100 shadow overflow-hidden" data-search="<?php echo h($deptSearchText); ?>" data-department="<?php echo h($deptNameStr); ?>">
                    <div class="card-body p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-lg font-semibold text-base-content"><?php echo h($deptNameStr); ?></div>
                                <div class="text-xs text-base-content/70 mt-1"><?php echo (int)$roleCount; ?> roles</div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" class="btn btn-sm btn-primary" onclick="openDepartmentModal(<?php echo h(json_encode($deptModalId)); ?>, <?php echo h(json_encode($deptNameStr)); ?>);">View</button>
                            </div>
                        </div>
                    </div>
                </div>

                <dialog id="<?php echo h($deptModalId); ?>" class="modal" data-department="<?php echo h($deptNameStr); ?>">
                    <div class="modal-box max-w-5xl">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="font-bold text-lg"><?php echo h($deptNameStr); ?></h3>
                                <div class="text-xs text-base-content/70 mt-1"><?php echo (int)$roleCount; ?> roles</div>
                            </div>
                            <div class="flex items-center gap-2">
                                <form method="dialog"><button class="btn btn-sm">Close</button></form>
                            </div>
                        </div>

                        <div class="mt-4 max-h-[70vh] overflow-y-auto">
                            <div class="space-y-3">
                                <?php foreach ($rolesByName as $roleKey => $roleData): ?>
                                    <?php
                                        $roleKeyStr = (string)$roleKey;
                                        $displayRole = $roleKeyStr !== '' ? $roleKeyStr : 'All Roles';
                                        $plans = is_array($roleData) && isset($roleData['plans']) && is_array($roleData['plans']) ? (array)$roleData['plans'] : [];
                                        $modes = is_array($roleData) && isset($roleData['modes']) && is_array($roleData['modes']) ? (array)$roleData['modes'] : [];
                                        $targetPercentages = is_array($roleData) && isset($roleData['targets']) && is_array($roleData['targets']) ? (array)$roleData['targets'] : [];
                                    ?>
                                    <details class="skill-block border border-base-300 rounded-md">
                                        <summary class="cursor-pointer px-3 py-2 text-sm font-semibold text-base-content bg-base-200">
                                            <?php echo h($displayRole); ?>
                                        </summary>
                                        <div class="p-3 space-y-3 text-sm">
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="flex items-center gap-2">
                                                    <button type="button" class="btn btn-xs btn-outline skill-edit-btn">Edit</button>
                                                    <button type="button" class="btn btn-xs hidden skill-cancel-btn">Cancel</button>
                                                </div>
                                            </div>

                                            <div class="skill-view space-y-3">
                                                <?php foreach (['Retrain' => 'Retraining','Reskilling' => 'Reskilling','Refresher Training' => 'Refresher','Upskilling' => 'Upskilling','Succession Ready' => 'Succession Ready'] as $stKey => $stLabel): ?>
                                                    <?php
                                                        $planText = (string)($plans[$stKey] ?? '');
                                                        $items = splitPlanItems($planText);
                                                        $mode = (string)($modes[$stKey] ?? 'Onsite');
                                                        $targetPercentage = (string)($targetPercentages[$stKey] ?? '');
                                                        $targetDisplay = '';
                                                        if ($targetPercentage !== '' && is_numeric($targetPercentage)) {
                                                            $targetDisplay = number_format((float)$targetPercentage, 1) . '%';
                                                        } else {
                                                            $targetDisplay = '-';
                                                        }
                                                    ?>
                                                    <div class="border border-base-300 rounded-md p-3 bg-base-100">
                                                        <div class="text-xs font-semibold text-base-content/70 mb-2"><?php echo h($stLabel); ?></div>
                                                        <div class="text-xs text-base-content/60 mb-2">Mode: <?php echo h($mode); ?></div>
                                                        <div class="text-xs text-base-content/60 mb-2">Target Percentage: <?php echo h($targetDisplay); ?></div>
                                                        <div class="flex flex-wrap gap-2">
                                                            <?php if (count($items) === 0): ?>
                                                                <span class="text-xs text-base-content/60">No plans yet.</span>
                                                            <?php else: ?>
                                                                <?php foreach ($items as $it): ?>
                                                                    <span class="badge badge-outline"><?php echo h($it); ?></span>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>

                                            <div class="skill-edit hidden space-y-2">
                                                <?php foreach (['Retrain' => 'Retraining','Reskilling' => 'Reskilling','Refresher Training' => 'Refresher','Upskilling' => 'Upskilling','Succession Ready' => 'Succession Ready'] as $stKey => $stLabel): ?>
                                                    <?php $planText = (string)($plans[$stKey] ?? ''); ?>
                                                    <?php $mode = (string)($modes[$stKey] ?? 'Onsite'); ?>
                                                    <?php $targetPercentage = (string)($targetPercentages[$stKey] ?? ''); ?>
                                                    <form method="post" class="border border-base-300 rounded-md p-3 bg-base-100">
                                                        <input type="hidden" name="action" value="save_plan" />
                                                        <input type="hidden" name="department" value="<?php echo h($deptNameStr); ?>" />
                                                        <input type="hidden" name="role" value="<?php echo h($roleKeyStr); ?>" />
                                                        <input type="hidden" name="status" value="<?php echo h($stKey); ?>" />

                                                        <div class="flex items-center justify-between gap-2 mb-2">
                                                            <div class="text-xs font-semibold text-base-content/70"><?php echo h($stLabel); ?></div>
                                                            <button type="submit" class="btn btn-xs btn-primary">Save</button>
                                                        </div>

                                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-2">
                                                            <div>
                                                                <label class="label"><span class="label-text">Delivery Mode</span></label>
                                                                <select name="delivery_mode" class="select select-bordered w-full text-sm">
                                                                    <option value="Onsite" <?php echo $mode === 'Onsite' ? 'selected' : ''; ?>>Onsite</option>
                                                                    <option value="Online" <?php echo $mode === 'Online' ? 'selected' : ''; ?>>Online</option>
                                                                </select>
                                                            </div>
                                                            <div>
                                                                <label class="label"><span class="label-text">Target Percentage (%)</span></label>
                                                                <input type="number" step="0.1" min="0" max="100" name="target_percentage" value="<?php echo h($targetPercentage); ?>" class="input input-bordered w-full text-sm" />
                                                            </div>
                                                        </div>

                                                        <textarea name="plan_text" class="textarea textarea-bordered w-full text-sm" rows="2" data-initial="<?php echo h($planText); ?>"><?php echo h($planText); ?></textarea>
                                                    </form>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </details>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <form method="dialog" class="modal-backdrop"><button>close</button></form>
                </div>
            </div>
            <form method="dialog" class="modal-backdrop"><button>close</button></form>
        </dialog>
    <?php endforeach; ?>

</div>

<script>
    (function () {
        var searchEl = document.getElementById('search');
        var deptCards = Array.from(document.querySelectorAll('.dept-card'));

        function normalize(v) {
            return String(v || '').toLowerCase().trim();
        }

        function setOpenDeptParam(dept) {
            try {
                var url = new URL(window.location.href);
                if (dept && String(dept).trim() !== '') {
                    url.searchParams.set('open_dept', String(dept));
                } else {
                    url.searchParams.delete('open_dept');
                }
                window.history.replaceState(null, '', url.toString());
            } catch (e) {
            }
        }

        function findDeptModalByName(dept) {
            var d = String(dept || '').trim();
            if (!d) return null;
            var dialogs = Array.from(document.querySelectorAll('dialog.modal[data-department]'));
            for (var i = 0; i < dialogs.length; i++) {
                if (String(dialogs[i].getAttribute('data-department') || '').trim() === d) {
                    return dialogs[i];
                }
            }
            return null;
        }

        window.openDepartmentModal = function (id, dept) {
            var modal = document.getElementById(id);
            if (!modal || typeof modal.showModal !== 'function') return;
            setOpenDeptParam(dept);
            modal.showModal();
        };

        Array.from(document.querySelectorAll('details.skill-block')).forEach(function (details) {
            var viewEl = details.querySelector('.skill-view');
            var editEl = details.querySelector('.skill-edit');
            var editBtn = details.querySelector('.skill-edit-btn');
            var cancelBtn = details.querySelector('.skill-cancel-btn');

            if (editBtn) {
                editBtn.addEventListener('click', function () {
                    if (viewEl) viewEl.classList.add('hidden');
                    if (editEl) editEl.classList.remove('hidden');
                    editBtn.classList.add('hidden');
                    if (cancelBtn) cancelBtn.classList.remove('hidden');
                });
            }

            if (cancelBtn) {
                cancelBtn.addEventListener('click', function () {
                    if (editEl) {
                        Array.from(editEl.querySelectorAll('textarea[data-initial]')).forEach(function (ta) {
                            ta.value = ta.getAttribute('data-initial') || '';
                        });
                    }
                    if (editEl) editEl.classList.add('hidden');
                    if (viewEl) viewEl.classList.remove('hidden');
                    cancelBtn.classList.add('hidden');
                    if (editBtn) editBtn.classList.remove('hidden');
                });
            }
        });

        Array.from(document.querySelectorAll('dialog.modal[data-department]')).forEach(function (dlg) {
            dlg.addEventListener('close', function () {
                var openDept = new URLSearchParams(window.location.search).get('open_dept') || '';
                var dept = String(dlg.getAttribute('data-department') || '');
                if (openDept !== '' && dept !== '' && openDept === dept) {
                    setOpenDeptParam('');
                }
            });
        });

        (function autoOpenDeptModal() {
            var openDept = new URLSearchParams(window.location.search).get('open_dept') || '';
            var dlg = findDeptModalByName(openDept);
            if (dlg && typeof dlg.showModal === 'function') {
                dlg.showModal();
            }
        })();

        function applySearch() {
            if (!searchEl) return;
            var q = normalize(searchEl.value);
            deptCards.forEach(function (card) {
                var deptText = normalize(card.getAttribute('data-search'));
                var visible = q === '' ? true : deptText.indexOf(q) !== -1;
                card.style.display = visible ? '' : 'none';
            });
        }

        if (searchEl) {
            searchEl.addEventListener('input', applySearch);
            applySearch();
        }
    })();
</script>
    </div>
  </div>
  <script src="../../../soliera.js"></script>
  <script src="../../../sidebar.js"></script>
</body>
</html>

