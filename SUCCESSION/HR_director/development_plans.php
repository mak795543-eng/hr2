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

if (!function_exists('getDevelopmentPlansRepo')) {
    function getDevelopmentPlansRepo()
    {
        global $pdo;
        global $DEVELOPMENT_PLANS;

        if (!$pdo) {
            return [];
        }

        ensureDevelopmentPlansSchema();
        seedDevelopmentPlansIfEmpty($DEVELOPMENT_PLANS);

        $repo = [];
        $statuses = ['Retrain', 'Reskilling', 'Refresher Training', 'Upskilling', 'Succession Ready'];

        $stmt = $pdo->query(
            "SELECT dpi.department,
                    COALESCE(dpi.role, '') AS role_key,
                    dpi.skill_id,
                    s.skill_name,
                    dpi.status,
                    dpi.plan_text,
                    COALESCE(dpi.delivery_mode, 'Onsite') AS delivery_mode,
                    dpi.target_percentage
             FROM development_plan_items dpi
             JOIN skills s ON s.id = dpi.skill_id
             WHERE s.category = 'General Skills'
               AND COALESCE(dpi.role, '') = ''
             ORDER BY dpi.department ASC, role_key ASC, s.skill_name ASC"
        );
        $rows = $stmt->fetchAll();

        foreach ($rows as $r) {
            $dept = (string)($r['department'] ?? '');
            $role = (string)($r['role_key'] ?? '');
            $skillId = (int)($r['skill_id'] ?? 0);
            $skillName = (string)($r['skill_name'] ?? '');
            $status = (string)($r['status'] ?? '');
            $planText = (string)($r['plan_text'] ?? '');
            $deliveryMode = (string)($r['delivery_mode'] ?? 'Onsite');
            $targetPct = $r['target_percentage'] ?? null;

            if ($dept === '' || $skillId <= 0 || $skillName === '' || $status === '') {
                continue;
            }

            if (!isset($repo[$dept])) {
                $repo[$dept] = [];
            }
            if (!isset($repo[$dept][$skillId])) {
                $repo[$dept][$skillId] = [
                    'skill_name' => $skillName,
                    'roles' => [],
                ];
            }
            if (!isset($repo[$dept][$skillId]['roles'][$role])) {
                $repo[$dept][$skillId]['roles'][$role] = [
                    'plans' => [],
                    'modes' => [],
                    'targets' => [],
                ];
            }

            $repo[$dept][$skillId]['roles'][$role]['plans'][$status] = $planText;
            $repo[$dept][$skillId]['roles'][$role]['modes'][$status] = $deliveryMode;
            $repo[$dept][$skillId]['roles'][$role]['targets'][$status] = $targetPct;
        }

        foreach ($repo as $dept => $skillsById) {
            foreach ($skillsById as $sid => $skillData) {
                $roles = (array)($skillData['roles'] ?? []);
                foreach ($roles as $roleKey => $roleData) {
                    foreach ($statuses as $st) {
                        if (!isset($repo[$dept][$sid]['roles'][$roleKey]['plans'][$st])) {
                            $repo[$dept][$sid]['roles'][$roleKey]['plans'][$st] = '';
                        }
                        if (!isset($repo[$dept][$sid]['roles'][$roleKey]['modes'][$st])) {
                            $repo[$dept][$sid]['roles'][$roleKey]['modes'][$st] = 'Onsite';
                        }
                        if (!isset($repo[$dept][$sid]['roles'][$roleKey]['targets'][$st])) {
                            $repo[$dept][$sid]['roles'][$roleKey]['targets'][$st] = null;
                        }
                    }
                }
            }
        }

        return $repo;
    }
}

if (!function_exists('getSuggestedPlansForDepartmentStatus')) {
    function getSuggestedPlansForDepartmentStatus($department, $status, $role = null)
    {
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
            $out = [];
            if (isset($DEVELOPMENT_PLANS[$department]) && is_array($DEVELOPMENT_PLANS[$department])) {
                foreach ($DEVELOPMENT_PLANS[$department] as $skillName => $byStatus) {
                    if (!is_array($byStatus)) {
                        continue;
                    }
                    if (isset($byStatus[$status]) && trim((string)$byStatus[$status]) !== '') {
                        $out[(string)$skillName] = [
                            'plan_text' => (string)$byStatus[$status],
                            'delivery_mode' => 'Onsite',
                        ];
                    }
                }
            }
            return $out;
        }

        ensureDevelopmentPlansSchema();
        seedDevelopmentPlansIfEmpty($DEVELOPMENT_PLANS);

        $out = [];
        $stmt = $pdo->prepare(
            "SELECT s.skill_name, dpi.plan_text, COALESCE(dpi.delivery_mode, 'Onsite') AS delivery_mode
             FROM development_plan_items dpi
             JOIN skills s ON s.id = dpi.skill_id
             WHERE dpi.department = ?
               AND dpi.status = ?
               AND dpi.role = ''
               AND s.category = 'General Skills'
             ORDER BY s.skill_name ASC"
        );
        $stmt->execute([$department, $status]);

        $rows = $stmt->fetchAll();
        foreach ($rows as $r) {
            $skillName = (string)($r['skill_name'] ?? '');
            $planText = trim((string)($r['plan_text'] ?? ''));
            $deliveryMode = (string)($r['delivery_mode'] ?? 'Onsite');
            if ($skillName === '' || $planText === '') {
                continue;
            }
            if (!isset($out[$skillName])) {
                $out[$skillName] = [
                    'plan_text' => $planText,
                    'delivery_mode' => $deliveryMode,
                ];
            }
        }

        return $out;
    }
}

if (!function_exists('getDevelopmentPlansForSkill')) {
    function getDevelopmentPlansForSkill($department, $status, $skillName)
    {
        global $pdo;
        global $DEVELOPMENT_PLANS;

        $department = trim((string)$department);
        $status = trim((string)$status);
        $skillName = trim((string)$skillName);

        $allowedStatuses = ['Retrain', 'Reskilling', 'Refresher Training', 'Upskilling', 'Succession Ready'];
        if ($department === '' || $status === '' || $skillName === '' || !in_array($status, $allowedStatuses, true)) {
            return [];
        }

        if (!$pdo) {
            $out = [];
            if (
                isset($DEVELOPMENT_PLANS[$department]) &&
                isset($DEVELOPMENT_PLANS[$department][$skillName]) &&
                is_array($DEVELOPMENT_PLANS[$department][$skillName])
            ) {
                $byStatus = $DEVELOPMENT_PLANS[$department][$skillName];
                if (isset($byStatus[$status]) && trim((string)$byStatus[$status]) !== '') {
                    $out[] = [
                        'plan_text' => (string)$byStatus[$status],
                        'delivery_mode' => 'Onsite',
                    ];
                }
            }
            return $out;
        }

        ensureDevelopmentPlansSchema();
        seedDevelopmentPlansIfEmpty($DEVELOPMENT_PLANS);

        $stmt = $pdo->prepare(
            "SELECT dpi.plan_text, COALESCE(dpi.delivery_mode, 'Onsite') AS delivery_mode
             FROM development_plan_items dpi
             JOIN skills s ON s.id = dpi.skill_id
             WHERE dpi.department = ?
               AND dpi.status = ?
               AND dpi.role = ''
               AND s.category = 'General Skills'
               AND s.skill_name = ?
             ORDER BY dpi.id ASC"
        );
        $stmt->execute([$department, $status, $skillName]);

        $rows = $stmt->fetchAll();
        $out = [];
        foreach ($rows as $r) {
            $planText = trim((string)($r['plan_text'] ?? ''));
            if ($planText === '') {
                continue;
            }
            $out[] = [
                'plan_text' => $planText,
                'delivery_mode' => (string)($r['delivery_mode'] ?? 'Onsite'),
            ];
        }

        return $out;
    }
}

if (!function_exists('h')) {
    function h($v)
    {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('splitPlanItems')) {
    function splitPlanItems($planText)
    {
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
    function ensureDevelopmentPlansSchema()
    {
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
                    skill_id INT NOT NULL,
                    status ENUM('Retrain','Reskilling','Refresher Training','Upskilling','Succession Ready') NOT NULL,
                    plan_text TEXT NOT NULL,
                    delivery_mode ENUM('Onsite','Online') NOT NULL DEFAULT 'Onsite',
                    target_percentage DECIMAL(5,2) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uniq_devplan (department, role, skill_id, status),
                    INDEX idx_dept_status (department, status),
                    CONSTRAINT fk_devplan_skill FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
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
    function seedDevelopmentPlansIfEmpty($seedRepo)
    {
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

        try {
            $cnt = (int)$pdo->query("SELECT COUNT(*) FROM development_plan_items")->fetchColumn();
            if ($cnt > 0) {
                return;
            }
        } catch (Throwable $e) {
            return;
        }

        if (!is_array($seedRepo) || count($seedRepo) === 0) {
            return;
        }

        $insSkill = $pdo->prepare(
            "INSERT INTO skills (skill_name, category, department)
             VALUES (?, 'General Skills', ?)
             ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)"
        );

        $insPlan = $pdo->prepare(
            "INSERT INTO development_plan_items (department, role, skill_id, status, plan_text, delivery_mode)
             VALUES (?, '', ?, ?, ?, 'Onsite')
             ON DUPLICATE KEY UPDATE plan_text = VALUES(plan_text), delivery_mode = VALUES(delivery_mode)"
        );

        foreach ($seedRepo as $dept => $skills) {
            if (!is_array($skills)) {
                continue;
            }
            $dept = trim((string)$dept);
            if ($dept === '') {
                continue;
            }

            foreach ($skills as $skillName => $byStatus) {
                if (!is_array($byStatus)) {
                    continue;
                }

                $skillName = trim((string)$skillName);
                if ($skillName === '') {
                    continue;
                }

                try {
                    $insSkill->execute([$skillName, $dept]);
                    $skillId = (int)$pdo->lastInsertId();
                } catch (Throwable $e) {
                    continue;
                }

                foreach ($byStatus as $status => $planText) {
                    $status = trim((string)$status);
                    $planText = (string)$planText;
                    if ($status === '') {
                        continue;
                    }

                    try {
                        $insPlan->execute([$dept, $skillId, $status, $planText]);
                    } catch (Throwable $e) {
                    }
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
            $role = '';
            $status = trim((string)($_POST['status'] ?? ''));
            $planText = trim((string)($_POST['plan_text'] ?? ''));
            $deliveryMode = trim((string)($_POST['delivery_mode'] ?? 'Onsite'));
            $skillIdRaw = trim((string)($_POST['skill_id'] ?? ''));
            $targetPctRaw = trim((string)($_POST['target_percentage'] ?? ''));

            if ($department === '' || $status === '' || !in_array($status, $allowedStatuses, true)) {
                throw new RuntimeException('Invalid request.');
            }
            if ($skillIdRaw === '' || !ctype_digit($skillIdRaw)) {
                throw new RuntimeException('Invalid skill.');
            }
            $skillId = (int)$skillIdRaw;

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
        } elseif ($action === 'add_general_skill') {
            $department = trim((string)($_POST['department'] ?? ''));
            $role = '';
            $skillName = trim((string)($_POST['skill_name'] ?? ''));

            if ($department === '' || $skillName === '') {
                throw new RuntimeException('Missing required fields.');
            }

            $stmtSkill = $pdo->prepare(
                "INSERT INTO skills (skill_name, category, department)
                 VALUES (?, 'General Skills', ?)
                 ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)"
            );
            $stmtSkill->execute([$skillName, $department]);
            $skillId = (int)$pdo->lastInsertId();

            $defaultTarget = 80.0;
            try {
                $stmtStd = $pdo->prepare("SELECT standard_percentage FROM general_skill_standards WHERE skill_id = ? LIMIT 1");
                $stmtStd->execute([$skillId]);
                $v = $stmtStd->fetchColumn();
                if ($v !== false && is_numeric($v)) {
                    $defaultTarget = (float)$v;
                }
            } catch (Throwable $e) {
            }

            $stmtPlan = $pdo->prepare(
                "INSERT INTO development_plan_items (department, role, skill_id, status, plan_text, delivery_mode, target_percentage)
                 VALUES (?, ?, ?, ?, '', 'Onsite', ?)
                 ON DUPLICATE KEY UPDATE plan_text = plan_text"
            );
            foreach ($allowedStatuses as $st) {
                $stmtPlan->execute([$department, $role, $skillId, $st, $defaultTarget]);
            }

            $_SESSION['dp_flash'] = ['type' => 'success', 'message' => 'General skill added.'];
        } elseif ($action === 'add_skill_plan') {
            $department = trim((string)($_POST['department'] ?? ''));
            $role = '';
            $status = trim((string)($_POST['status'] ?? ''));
            $skillIdRaw = trim((string)($_POST['skill_id'] ?? ''));
            $skillName = trim((string)($_POST['skill_name'] ?? ''));
            $planText = trim((string)($_POST['plan_text'] ?? ''));
            $deliveryMode = trim((string)($_POST['delivery_mode'] ?? 'Onsite'));
            $targetPctRaw = trim((string)($_POST['target_percentage'] ?? ''));

            if ($department === '' || $status === '' || !in_array($status, $allowedStatuses, true)) {
                throw new RuntimeException('Missing required fields.');
            }

            if ($planText === '') {
                throw new RuntimeException('Missing required fields.');
            }

            if (!in_array($deliveryMode, $allowedModes, true)) {
                $deliveryMode = 'Onsite';
            }

            $skillId = 0;
            if ($skillIdRaw !== '' && ctype_digit($skillIdRaw)) {
                $skillId = (int)$skillIdRaw;
            }

            if ($skillId <= 0) {
                if ($skillName === '') {
                    throw new RuntimeException('Missing required fields.');
                }
                $stmtSkill = $pdo->prepare(
                    "INSERT INTO skills (skill_name, category, department)
                     VALUES (?, 'General Skills', ?)
                     ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)"
                );
                $stmtSkill->execute([$skillName, $department]);
                $skillId = (int)$pdo->lastInsertId();
            }

            $existing = '';
            try {
                $stmtCur = $pdo->prepare(
                    "SELECT plan_text
                     FROM development_plan_items
                     WHERE department = ?
                       AND role = ''
                       AND skill_id = ?
                       AND status = ?
                     LIMIT 1"
                );
                $stmtCur->execute([$department, $skillId, $status]);
                $existing = (string)($stmtCur->fetchColumn() ?: '');
            } catch (Throwable $e) {
                $existing = '';
            }

            $items = splitPlanItems($existing);
            $newItem = trim((string)$planText);
            if ($newItem !== '' && !in_array($newItem, $items, true)) {
                $items[] = $newItem;
            }
            $mergedText = implode("\n", $items);

            $stmtPlan = $pdo->prepare(
                "INSERT INTO development_plan_items (department, role, skill_id, status, plan_text, delivery_mode, target_percentage)
                 VALUES (?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE plan_text = VALUES(plan_text), delivery_mode = VALUES(delivery_mode), target_percentage = VALUES(target_percentage)"
            );
            $stmtPlan->execute([$department, $role, $skillId, $status, $mergedText, $deliveryMode, $targetPct]);
            $_SESSION['dp_flash'] = ['type' => 'success', 'message' => 'Skill plan saved.'];
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

$repo = getDevelopmentPlansRepo();
ksort($repo);

$departments = array_keys($repo);
sort($departments);

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

$defaultDeptRoles = [
    'Front Office / Reception' => [
        'Front Desk Manager',
        'Receptionist / Front Desk Officer',
        'Guest Service Agent / Concierge',
        'Reservation Agent',
        'Bellhop / Porter',
    ],
    'Housekeeping' => [
        'Executive Housekeeper / Housekeeping Manager',
        'Floor Supervisor',
        'Room Attendant / Housekeeper',
        'Laundry Attendant',
        'Public Area Attendant',
    ],
    'Food & Beverage (F&B)' => [
        'F&B Manager / Director',
        'Restaurant Manager / Captain',
        'Waiter / Waitress / Server',
    ],
    'Kitchen / Culinary' => [
        'Executive Chef / Head Chef',
        'Sous Chef (assistant to head chef)',
        'Line Cook / Station Chef',
        'Pastry Chef / Baker',
        'Kitchen Steward / Dishwasher',
    ],
    'Sales & Marketing' => [
        'Sales & Marketing Manager',
        'Revenue Manager',
        'Event / Banquet Sales Coordinator',
        'Social Media / Marketing Executive',
    ],
    'Human Resources (HR)' => [
        'HR Manager / Director',
        'Recruitment Officer',
        'Training & Development Specialist',
        'Payroll / HR Assistant',
    ],
    'Finance / Accounting' => [
        'Finance Manager / Controller',
        'Accountant',
        'Payroll Officer',
        'Cost Controller',
    ],
    'Engineering / Maintenance' => [
        'Chief Engineer / Engineering Manager',
        'Maintenance Technician',
        'Electrician / Plumber',
        'HVAC Technician',
    ],
    'Security' => [
        'Security Manager / Supervisor',
        'Security Guard',
        'CCTV / Surveillance Officer',
    ],
];

foreach ($defaultDeptRoles as $dept => $roles) {
    $dept = trim((string)$dept);
    if ($dept === '' || !is_array($roles)) {
        continue;
    }
    if (!isset($deptRoles[$dept])) {
        $deptRoles[$dept] = [];
    }
    foreach ($roles as $roleName) {
        $roleName = trim((string)$roleName);
        if ($roleName === '') {
            continue;
        }
        $deptRoles[$dept][$roleName] = true;
    }
}

try {
    $stmt = $pdo->query(
        "SELECT DISTINCT department, role
         FROM development_plan_items
         WHERE role IS NOT NULL AND role <> ''"
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

$skillsByDept = [];
try {
    $stmt = $pdo->query(
        "SELECT id, skill_name, department
         FROM skills
         WHERE category = 'General Skills'
         ORDER BY department ASC, skill_name ASC"
    );
    $rows = $stmt->fetchAll();
    foreach ($rows as $r) {
        $d = trim((string)($r['department'] ?? ''));
        $id = (int)($r['id'] ?? 0);
        $n = trim((string)($r['skill_name'] ?? ''));
        if ($d === '' || $id <= 0 || $n === '') {
            continue;
        }
        if (!isset($skillsByDept[$d])) {
            $skillsByDept[$d] = [];
        }
        $skillsByDept[$d][] = ['id' => $id, 'name' => $n];
    }
} catch (Throwable $e) {
}

$standardsBySkillId = [];
try {
    $stmt = $pdo->query(
        "SELECT gss.skill_id, gss.standard_percentage
         FROM general_skill_standards gss
         JOIN skills s ON s.id = gss.skill_id
         WHERE s.category = 'General Skills'"
    );
    $rows = $stmt->fetchAll();
    foreach ($rows as $r) {
        $sid = (int)($r['skill_id'] ?? 0);
        $pct = $r['standard_percentage'] ?? null;
        if ($sid <= 0 || $pct === null || !is_numeric($pct)) {
            continue;
        }
        $standardsBySkillId[$sid] = (float)$pct;
    }
} catch (Throwable $e) {
}
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
                            <div class="text-sm text-base-content/70">Editable repository per Department / General Skill / Status.</div>
                        </div>
                        <div class="w-full md:w-auto flex flex-col gap-2">
                            <input id="search" type="text" placeholder="Search department, skill, or plan..." class="input input-bordered w-full md:w-96" />
                        </div>
                    </div>

                    <div id="dept-grid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                        <?php foreach ($repo as $deptName => $skillsById): ?>
                            <?php
                            $deptNameStr = (string)$deptName;
                            $skillCount = is_array($skillsById) ? count($skillsById) : 0;
                            $deptModalId = 'modal_dept_' . substr(md5($deptNameStr), 0, 10);
                            $deptSearchParts = [$deptNameStr];
                            if (is_array($skillsById)) {
                                foreach ($skillsById as $sid => $sd) {
                                    $deptSearchParts[] = (string)($sd['skill_name'] ?? '');
                                    $rp = (array)($sd['roles'] ?? []);
                                    foreach ($rp as $rk => $rd) {
                                        $rk = (string)$rk;
                                        if ($rk !== '') {
                                            $deptSearchParts[] = $rk;
                                        }
                                        $pmap = (array)(($rd['plans'] ?? []));
                                        foreach ($pmap as $pv) {
                                            $deptSearchParts[] = (string)$pv;
                                        }
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
                                            <div class="text-xs text-base-content/70 mt-1"><?php echo (int)$skillCount; ?> skills</div>
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
                                            <div class="text-xs text-base-content/70 mt-1"><?php echo (int)$skillCount; ?> skills</div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button type="button" class="btn btn-sm btn-outline" onclick="openAddGeneralSkillModal(<?php echo h(json_encode($deptNameStr)); ?>);">Add General Skill</button>
                                            <form method="dialog"><button class="btn btn-sm">Close</button></form>
                                        </div>
                                    </div>

                                    <div class="mt-4 max-h-[70vh] overflow-y-auto">
                                        <div class="space-y-3">
                                            <?php foreach ($skillsById as $skillId => $skillData): ?>
                                                <?php
                                                $skillName = (string)($skillData['skill_name'] ?? '');
                                                $plans = [];
                                                if (isset($skillData['roles']) && is_array($skillData['roles']) && isset($skillData['roles']['']) && is_array($skillData['roles']['']) && isset($skillData['roles']['']['plans']) && is_array($skillData['roles']['']['plans'])) {
                                                    $plans = (array)$skillData['roles']['']['plans'];
                                                }
                                                $modes = [];
                                                if (isset($skillData['roles']) && is_array($skillData['roles']) && isset($skillData['roles']['']) && is_array($skillData['roles']['']) && isset($skillData['roles']['']['modes']) && is_array($skillData['roles']['']['modes'])) {
                                                    $modes = (array)$skillData['roles']['']['modes'];
                                                }

                                                $targetPercentages = [];
                                                if (isset($skillData['roles']) && is_array($skillData['roles']) && isset($skillData['roles']['']) && is_array($skillData['roles']['']) && isset($skillData['roles']['']['targets']) && is_array($skillData['roles']['']['targets'])) {
                                                    $targetPercentages = (array)$skillData['roles']['']['targets'];
                                                }
                                                ?>
                                                <details class="skill-block border border-base-300 rounded-md">
                                                    <summary class="cursor-pointer px-3 py-2 text-sm font-semibold text-base-content bg-base-200">
                                                        <?php echo h($skillName); ?>
                                                    </summary>
                                                    <div class="p-3 space-y-3 text-sm">
                                                        <div class="flex items-center justify-between gap-2">
                                                            <div class="flex items-center gap-2">
                                                                <button type="button" class="btn btn-xs btn-outline skill-edit-btn">Edit</button>
                                                                <button type="button" class="btn btn-xs hidden skill-cancel-btn">Cancel</button>
                                                            </div>
                                                            <button type="button" class="btn btn-xs btn-outline" onclick="openAddSkillPlanModal(<?php echo h(json_encode($deptNameStr)); ?>, <?php echo h(json_encode((string)$skillId)); ?>, <?php echo h(json_encode($skillName)); ?>);">Add Skill Plan</button>
                                                        </div>

                                                        <div class="skill-view space-y-3">
                                                            <?php foreach (['Retrain' => 'Retraining', 'Reskilling' => 'Reskilling', 'Refresher Training' => 'Refresher', 'Upskilling' => 'Upskilling', 'Succession Ready' => 'Succession Ready'] as $stKey => $stLabel): ?>
                                                                <?php
                                                                $planText = (string)($plans[$stKey] ?? '');
                                                                $items = splitPlanItems($planText);
                                                                $mode = (string)($modes[$stKey] ?? 'Onsite');
                                                                $targetPercentage = (string)($targetPercentages[$stKey] ?? '');
                                                                $targetDisplay = '';
                                                                if ($targetPercentage !== '' && is_numeric($targetPercentage)) {
                                                                    $targetDisplay = number_format((float)$targetPercentage, 1) . '%';
                                                                } else {
                                                                    $targetDisplay = 'â€”';
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
                                                            <?php foreach (['Retrain' => 'Retraining', 'Reskilling' => 'Reskilling', 'Refresher Training' => 'Refresher', 'Upskilling' => 'Upskilling', 'Succession Ready' => 'Succession Ready'] as $stKey => $stLabel): ?>
                                                                <?php $planText = (string)($plans[$stKey] ?? ''); ?>
                                                                <?php $mode = (string)($modes[$stKey] ?? 'Onsite'); ?>
                                                                <?php $targetPercentage = (string)($targetPercentages[$stKey] ?? ''); ?>
                                                                <form method="post" class="border border-base-300 rounded-md p-3 bg-base-100">
                                                                    <input type="hidden" name="action" value="save_plan" />
                                                                    <input type="hidden" name="department" value="<?php echo h($deptNameStr); ?>" />
                                                                    <input type="hidden" name="skill_id" value="<?php echo h((string)$skillId); ?>" />
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
                            </dialog>
                        <?php endforeach; ?>
                    </div>
                </div>

                <dialog id="modal_add_general_skill" class="modal">
                    <div class="modal-box">
                        <h3 class="font-bold text-lg">Add General Skill</h3>
                        <form method="post" class="mt-4 space-y-3">
                            <input type="hidden" name="action" value="add_general_skill" />
                            <input type="hidden" name="department" id="add_gs_department" value="" />

                            <div>
                                <label class="label"><span class="label-text">Department</span></label>
                                <input id="add_gs_department_display" class="input input-bordered w-full" value="" readonly />
                            </div>

                            <div>
                                <label class="label"><span class="label-text">General Skill</span></label>
                                <input id="add_gs_skill" name="skill_name" class="input input-bordered w-full" required />
                            </div>

                            <div class="modal-action">
                                <button type="button" class="btn" onclick="document.getElementById('modal_add_general_skill').close();">Cancel</button>
                                <button type="submit" class="btn btn-primary">Add</button>
                            </div>
                        </form>
                    </div>
                    <form method="dialog" class="modal-backdrop"><button>close</button></form>
                </dialog>

                <dialog id="modal_add_skill_plan" class="modal">
                    <div class="modal-box">
                        <h3 class="font-bold text-lg">Add Skill Plan</h3>
                        <form method="post" class="mt-4 space-y-3">
                            <input type="hidden" name="action" value="add_skill_plan" />
                            <input type="hidden" name="department" id="add_sp_department" value="" />
                            <input type="hidden" name="skill_id" id="add_sp_skill_id" value="" />

                            <div>
                                <label class="label"><span class="label-text">Department</span></label>
                                <input id="add_sp_department_display" class="input input-bordered w-full" value="" readonly />
                            </div>

                            <div>
                                <label class="label"><span class="label-text">Competency Status</span></label>
                                <select id="add_sp_status" name="status" class="select select-bordered w-full" required>
                                    <option value="" disabled selected>Select status</option>
                                    <option value="Retrain">Retrain</option>
                                    <option value="Reskilling">Reskilling</option>
                                    <option value="Refresher Training">Refresher Training</option>
                                    <option value="Upskilling">Upskilling</option>
                                    <option value="Succession Ready">Succession Ready</option>
                                </select>
                            </div>

                            <div>
                                <label class="label"><span class="label-text">Delivery Mode</span></label>
                                <select id="add_sp_delivery_mode" name="delivery_mode" class="select select-bordered w-full" required>
                                    <option value="Onsite">Onsite</option>
                                    <option value="Online">Online</option>
                                </select>
                            </div>

                            <div>
                                <label class="label"><span class="label-text">Skill</span></label>
                                <input id="add_sp_skill_display" class="input input-bordered w-full" value="" readonly />
                            </div>

                            <div>
                                <label class="label"><span class="label-text">Plan Text</span></label>
                                <textarea id="add_sp_plan_text" name="plan_text" class="textarea textarea-bordered w-full" rows="3" required></textarea>
                            </div>

                            <div>
                                <label class="label"><span class="label-text">Target Percentage (%)</span></label>
                                <input id="add_sp_target_percentage" type="number" step="0.1" min="0" max="100" name="target_percentage" class="input input-bordered w-full" />
                            </div>

                            <div class="modal-action">
                                <button type="button" class="btn" onclick="document.getElementById('modal_add_skill_plan').close();">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save</button>
                            </div>
                        </form>
                    </div>
                    <form method="dialog" class="modal-backdrop"><button>close</button></form>
                </dialog>

                <script>
                    const DP_FLASH = <?php echo json_encode($flash, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
                    const STANDARD_BY_SKILL_ID = <?php echo json_encode($standardsBySkillId, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
                    const searchEl = document.getElementById('search');
                    const deptCards = Array.from(document.querySelectorAll('.dept-card'));
                    const normalize = (s) => String(s || '').toLowerCase();

                    if (DP_FLASH && DP_FLASH.message) {
                        const isError = String(DP_FLASH.type || '').toLowerCase() === 'error';
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: isError ? 'error' : 'success',
                            title: String(DP_FLASH.message),
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true,
                        });
                    }

                    function setOpenDeptParam(dept) {
                        const url = new URL(window.location.href);
                        const d = String(dept || '').trim();
                        if (d !== '') {
                            url.searchParams.set('open_dept', d);
                        } else {
                            url.searchParams.delete('open_dept');
                        }
                        window.history.replaceState({}, '', url.toString());
                    }

                    function findDeptModalByName(dept) {
                        const d = String(dept || '').trim();
                        if (d === '') return null;
                        let found = null;
                        Array.from(document.querySelectorAll('dialog.modal[data-department]')).some(function(dlg) {
                            if (String(dlg.getAttribute('data-department') || '') === d) {
                                found = dlg;
                                return true;
                            }
                            return false;
                        });
                        return found;
                    }

                    window.openDepartmentModal = function(modalId, deptName) {
                        const id = String(modalId || '');
                        const dept = String(deptName || '').trim();
                        const dlg = id ? document.getElementById(id) : findDeptModalByName(dept);
                        if (dlg && typeof dlg.showModal === 'function') {
                            setOpenDeptParam(dept);
                            dlg.showModal();
                        }
                    };

                    window.openAddGeneralSkillModal = function(dept) {
                        const modal = document.getElementById('modal_add_general_skill');
                        const deptHidden = document.getElementById('add_gs_department');
                        const deptDisplay = document.getElementById('add_gs_department_display');
                        const skillInput = document.getElementById('add_gs_skill');

                        const d = String(dept || '').trim();
                        if (deptHidden) deptHidden.value = d;
                        if (deptDisplay) deptDisplay.value = d;
                        if (skillInput) skillInput.value = '';
                        if (modal && typeof modal.showModal === 'function') {
                            modal.showModal();
                        }
                    };

                    window.openAddSkillPlanModal = function(dept, skillId, skillName) {
                        const modal = document.getElementById('modal_add_skill_plan');
                        const deptHidden = document.getElementById('add_sp_department');
                        const deptDisplay = document.getElementById('add_sp_department_display');
                        const skillIdHidden = document.getElementById('add_sp_skill_id');
                        const skillDisplay = document.getElementById('add_sp_skill_display');
                        const statusSel = document.getElementById('add_sp_status');
                        const deliveryModeSel = document.getElementById('add_sp_delivery_mode');
                        const planText = document.getElementById('add_sp_plan_text');
                        const targetPctEl = document.getElementById('add_sp_target_percentage');

                        const d = String(dept || '').trim();
                        if (deptHidden) deptHidden.value = d;
                        if (deptDisplay) deptDisplay.value = d;
                        if (skillIdHidden) skillIdHidden.value = String(skillId || '').trim();
                        if (skillDisplay) skillDisplay.value = String(skillName || '').trim();
                        if (statusSel) statusSel.value = '';
                        if (deliveryModeSel) deliveryModeSel.value = 'Onsite';
                        if (planText) planText.value = '';

                        if (targetPctEl) {
                            const sid = parseInt(String(skillId || '0'), 10);
                            const v = (STANDARD_BY_SKILL_ID && sid && Object.prototype.hasOwnProperty.call(STANDARD_BY_SKILL_ID, sid)) ? STANDARD_BY_SKILL_ID[sid] : '';
                            targetPctEl.value = (v !== '' && v !== null && typeof v !== 'undefined') ? v : '';
                        }
                        if (modal && typeof modal.showModal === 'function') {
                            modal.showModal();
                        }
                    };

                    Array.from(document.querySelectorAll('details.skill-block')).forEach(function(details) {
                        const viewEl = details.querySelector('.skill-view');
                        const editEl = details.querySelector('.skill-edit');
                        const editBtn = details.querySelector('.skill-edit-btn');
                        const cancelBtn = details.querySelector('.skill-cancel-btn');

                        if (editBtn) {
                            editBtn.addEventListener('click', function() {
                                if (viewEl) viewEl.classList.add('hidden');
                                if (editEl) editEl.classList.remove('hidden');
                                editBtn.classList.add('hidden');
                                if (cancelBtn) cancelBtn.classList.remove('hidden');
                            });
                        }

                        if (cancelBtn) {
                            cancelBtn.addEventListener('click', function() {
                                if (editEl) {
                                    Array.from(editEl.querySelectorAll('textarea[data-initial]')).forEach(function(ta) {
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

                    Array.from(document.querySelectorAll('dialog.modal[data-department]')).forEach(function(dlg) {
                        dlg.addEventListener('close', function() {
                            const openDept = new URLSearchParams(window.location.search).get('open_dept') || '';
                            const dept = String(dlg.getAttribute('data-department') || '');
                            if (openDept !== '' && dept !== '' && openDept === dept) {
                                setOpenDeptParam('');
                            }
                        });
                    });

                    (function autoOpenDeptModal() {
                        const openDept = new URLSearchParams(window.location.search).get('open_dept') || '';
                        const dlg = findDeptModalByName(openDept);
                        if (dlg && typeof dlg.showModal === 'function') {
                            dlg.showModal();
                        }
                    })();

                    function applySearch() {
                        const q = normalize(searchEl.value);

                        deptCards.forEach(card => {
                            const deptText = normalize(card.getAttribute('data-search'));
                            const deptVisible = q === '' ? true : deptText.includes(q);
                            card.style.display = deptVisible ? '' : 'none';
                        });
                    }

                    searchEl.addEventListener('input', applySearch);
                    applySearch();
                </script>
        </div>
    </div>
    <?php require('../../partials/footer.php') ?>