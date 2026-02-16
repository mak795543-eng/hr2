<?php

require_once __DIR__ . '/../../COMPETENCY/criticalgaps/config.php';

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

if (!function_exists('ensureCompetencyDevelopmentPlansSchema')) {
    function ensureCompetencyDevelopmentPlansSchema(): void
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
                "CREATE TABLE IF NOT EXISTS competency_development_plans (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    criteria_id INT NOT NULL,
                    status ENUM('Retrain','Reskilling','Refresher Training','Upskilling','Succession Ready') NOT NULL DEFAULT 'Retrain',
                    plan_text TEXT NOT NULL,
                    plan_hash CHAR(64) NOT NULL,
                    delivery_mode ENUM('Onsite','Online') NOT NULL DEFAULT 'Onsite',
                    target_percentage DECIMAL(5,2) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uniq_criteria_plan (criteria_id, plan_hash),
                    INDEX idx_criteria (criteria_id),
                    CONSTRAINT fk_cdp_criteria FOREIGN KEY (criteria_id) REFERENCES competency_criteria(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
        } catch (Throwable $e) {
        }

        try {
            $pdo->exec("ALTER TABLE competency_development_plans ADD COLUMN plan_hash CHAR(64) NOT NULL");
        } catch (Throwable $e) {
        }

        try {
            $pdo->exec("ALTER TABLE competency_development_plans ADD UNIQUE KEY uniq_criteria_plan (criteria_id, plan_hash)");
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

ensureCompetencyCriteriaSchema();
ensureCompetencyDevelopmentPlansSchema();

if (($_GET['api'] ?? '') === '1') {
    header('Content-Type: application/json; charset=utf-8');

    $action = (string)($_GET['action'] ?? '');
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        $payload = $_POST;
    }

    $allowedStatuses = ['Retrain', 'Reskilling', 'Refresher Training', 'Upskilling', 'Succession Ready'];
    $allowedModes = ['Onsite', 'Online'];

    $normalizePlanText = function (string $text): string {
        $text = trim($text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        return mb_strtolower($text);
    };

    try {
        if ($action === 'list_plans') {
            $criteriaId = isset($_GET['criteria_id']) ? (int)$_GET['criteria_id'] : (int)($payload['criteria_id'] ?? 0);
            if ($criteriaId <= 0) {
                throw new RuntimeException('Invalid criteria.');
            }

            $status = isset($_GET['status']) ? trim((string)$_GET['status']) : trim((string)($payload['status'] ?? ''));
            $where = "criteria_id = ?";
            $params = [$criteriaId];
            if ($status !== '') {
                if (!in_array($status, $allowedStatuses, true)) {
                    throw new RuntimeException('Invalid status.');
                }
                $where .= " AND status = ?";
                $params[] = $status;
            }

            $stmt = $pdo->prepare(
                "SELECT id, criteria_id, status, plan_text, delivery_mode, target_percentage, updated_at
                 FROM competency_development_plans
                 WHERE {$where}
                 ORDER BY updated_at DESC, id DESC"
            );
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            echo json_encode(['success' => true, 'data' => $rows], JSON_UNESCAPED_SLASHES);
            exit;
        }

        if ($action === 'create_plan') {
            $criteriaId = (int)($payload['criteria_id'] ?? 0);
            $status = trim((string)($payload['status'] ?? 'Retrain'));
            $deliveryMode = trim((string)($payload['delivery_mode'] ?? 'Onsite'));
            $targetPctRaw = trim((string)($payload['target_percentage'] ?? ''));
            $planText = trim((string)($payload['plan_text'] ?? ''));

            if ($criteriaId <= 0 || $planText === '') {
                throw new RuntimeException('Missing required fields.');
            }
            if (!in_array($status, $allowedStatuses, true)) {
                $status = 'Retrain';
            }
            if (!in_array($deliveryMode, $allowedModes, true)) {
                $deliveryMode = 'Onsite';
            }

            $targetPct = null;
            if ($targetPctRaw !== '' && is_numeric($targetPctRaw)) {
                $targetPct = (float)$targetPctRaw;
                if ($targetPct < 0) $targetPct = 0;
                if ($targetPct > 100) $targetPct = 100;
            }

            $planHash = hash('sha256', $normalizePlanText($planText));

            $stmtCheck = $pdo->prepare("SELECT id FROM competency_criteria WHERE id = ? LIMIT 1");
            $stmtCheck->execute([$criteriaId]);
            if (!$stmtCheck->fetchColumn()) {
                throw new RuntimeException('Criteria not found.');
            }

            $stmtIns = $pdo->prepare(
                "INSERT INTO competency_development_plans (criteria_id, status, plan_text, plan_hash, delivery_mode, target_percentage)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmtIns->execute([$criteriaId, $status, $planText, $planHash, $deliveryMode, $targetPct]);

            echo json_encode(['success' => true, 'id' => (int)$pdo->lastInsertId()], JSON_UNESCAPED_SLASHES);
            exit;
        }

        if ($action === 'update_plan') {
            $planId = (int)($payload['id'] ?? 0);
            $status = trim((string)($payload['status'] ?? 'Retrain'));
            $deliveryMode = trim((string)($payload['delivery_mode'] ?? 'Onsite'));
            $targetPctRaw = trim((string)($payload['target_percentage'] ?? ''));
            $planText = trim((string)($payload['plan_text'] ?? ''));

            if ($planId <= 0 || $planText === '') {
                throw new RuntimeException('Missing required fields.');
            }
            if (!in_array($status, $allowedStatuses, true)) {
                $status = 'Retrain';
            }
            if (!in_array($deliveryMode, $allowedModes, true)) {
                $deliveryMode = 'Onsite';
            }

            $targetPct = null;
            if ($targetPctRaw !== '' && is_numeric($targetPctRaw)) {
                $targetPct = (float)$targetPctRaw;
                if ($targetPct < 0) $targetPct = 0;
                if ($targetPct > 100) $targetPct = 100;
            }

            $planHash = hash('sha256', $normalizePlanText($planText));

            $stmtCur = $pdo->prepare("SELECT criteria_id FROM competency_development_plans WHERE id = ? LIMIT 1");
            $stmtCur->execute([$planId]);
            $criteriaId = (int)($stmtCur->fetchColumn() ?? 0);
            if ($criteriaId <= 0) {
                throw new RuntimeException('Plan not found.');
            }

            $stmtUpd = $pdo->prepare(
                "UPDATE competency_development_plans
                 SET status = ?, plan_text = ?, plan_hash = ?, delivery_mode = ?, target_percentage = ?
                 WHERE id = ?"
            );
            $stmtUpd->execute([$status, $planText, $planHash, $deliveryMode, $targetPct, $planId]);

            echo json_encode(['success' => true], JSON_UNESCAPED_SLASHES);
            exit;
        }

        if ($action === 'delete_plan') {
            $planId = (int)($payload['id'] ?? 0);
            if ($planId <= 0) {
                throw new RuntimeException('Invalid plan.');
            }

            $stmtDel = $pdo->prepare("DELETE FROM competency_development_plans WHERE id = ?");
            $stmtDel->execute([$planId]);

            echo json_encode(['success' => true], JSON_UNESCAPED_SLASHES);
            exit;
        }

        throw new RuntimeException('Unknown action.');
    } catch (PDOException $e) {
        $code = (string)($e->errorInfo[1] ?? '');
        if ($code === '1062') {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Duplicate plan already exists for this criteria.']);
            exit;
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Request failed.']);
        exit;
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => ($e->getMessage() !== '' ? $e->getMessage() : 'Invalid request.')]);
        exit;
    }
}

$criteria = [];
$criteriaById = [];
$planCountByCriteriaId = [];

try {
    $stmtCounts = $pdo->query("SELECT criteria_id, COUNT(*) AS cnt FROM competency_development_plans GROUP BY criteria_id");
    $rowsCounts = $stmtCounts ? $stmtCounts->fetchAll(PDO::FETCH_ASSOC) : [];
    foreach (($rowsCounts ?? []) as $r) {
        $cid = (int)($r['criteria_id'] ?? 0);
        $cnt = (int)($r['cnt'] ?? 0);
        if ($cid > 0) {
            $planCountByCriteriaId[$cid] = $cnt;
        }
    }
} catch (Throwable $e) {
}

try {
    $stmt = $pdo->query("SELECT id, name, description, required_level FROM competency_criteria ORDER BY name ASC");
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    foreach (($rows ?? []) as $r) {
        $id = (int)($r['id'] ?? 0);
        $name = trim((string)($r['name'] ?? ''));
        if ($id <= 0 || $name === '') {
            continue;
        }
        $row = [
            'id' => $id,
            'name' => $name,
            'description' => (string)($r['description'] ?? ''),
            'required_level' => is_numeric($r['required_level'] ?? null) ? (float)$r['required_level'] : 80.0,
            'plan_count' => (int)($planCountByCriteriaId[$id] ?? 0),
        ];
        $criteria[] = $row;
        $criteriaById[(string)$id] = $row;
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
                            <h1 class="text-2xl font-bold text-base-content">Competency &amp; Development Planning</h1>
                            <div class="text-sm text-base-content/70">Each competency criterion is its own container of plans.</div>
                        </div>
                        <div class="w-full md:w-auto flex flex-col gap-2">
                            <input id="criteriaSearch" type="text" placeholder="Search criteria..." class="input input-bordered w-full md:w-96" />
                        </div>
                    </div>

                    <div id="criteria-grid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                        <?php foreach ($criteria as $c): ?>
                            <?php
                            $cId = (int)($c['id'] ?? 0);
                            $cName = (string)($c['name'] ?? '');
                            $cDesc = (string)($c['description'] ?? '');
                            $cReq = is_numeric($c['required_level'] ?? null) ? (float)$c['required_level'] : 80.0;
                            $cCnt = (int)($c['plan_count'] ?? 0);
                            $searchText = trim($cName . ' ' . $cDesc);
                            ?>
                            <div class="criteria-card card bg-base-100 shadow overflow-hidden" data-search="<?php echo h($searchText); ?>" data-criteria-id="<?php echo h((string)$cId); ?>">
                                <div class="card-body p-5">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="text-lg font-semibold text-base-content"><?php echo h($cName); ?></div>
                                            <div class="text-xs text-base-content/70 mt-1">Required: <?php echo h(number_format($cReq, 1)); ?>%</div>
                                            <div class="text-xs text-base-content/70 mt-1"><?php echo (int)$cCnt; ?> plans</div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button type="button" class="btn btn-sm btn-primary" onclick="openCriteriaModal(<?php echo h((string)$cId); ?>)">Manage</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <dialog id="criteriaModal" class="modal">
                    <div class="modal-box max-w-4xl">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 id="criteriaModalTitle" class="font-bold text-lg"></h3>
                                <div id="criteriaModalMeta" class="text-xs text-base-content/70 mt-1"></div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button id="addPlanBtn" type="button" class="btn btn-sm btn-primary" disabled>Add Plan</button>
                                <form method="dialog"><button class="btn btn-sm">Close</button></form>
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="text-xs text-base-content/70 mb-2">Select status:</div>
                            <div id="statusTabs" class="flex flex-wrap gap-2"></div>
                        </div>

                        <div id="planFormWrap" class="mt-4 hidden border border-base-300 rounded-md p-3 bg-base-100">
                            <input type="hidden" id="planId" value="" />

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                <div>
                                    <label class="label"><span class="label-text">Competency Status</span></label>
                                    <select id="planStatus" class="select select-bordered w-full text-sm">
                                        <option value="Retrain">Retrain</option>
                                        <option value="Reskilling">Reskilling</option>
                                        <option value="Refresher Training">Refresher Training</option>
                                        <option value="Upskilling">Upskilling</option>
                                        <option value="Succession Ready">Succession Ready</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="label"><span class="label-text">Delivery Mode</span></label>
                                    <select id="planDeliveryMode" class="select select-bordered w-full text-sm">
                                        <option value="Onsite">Onsite</option>
                                        <option value="Online">Online</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="label"><span class="label-text">Target Percentage (%)</span></label>
                                    <input id="planTargetPct" type="number" step="0.1" min="0" max="100" class="input input-bordered w-full text-sm" />
                                </div>
                            </div>

                            <div class="mt-2">
                                <label class="label"><span class="label-text">Plan Text</span></label>
                                <textarea id="planText" class="textarea textarea-bordered w-full text-sm" rows="3" placeholder="Enter development plan..."></textarea>
                            </div>

                            <div class="mt-3 flex justify-end gap-2">
                                <button id="cancelPlanBtn" type="button" class="btn btn-sm">Cancel</button>
                                <button id="savePlanBtn" type="button" class="btn btn-sm btn-primary">Save</button>
                            </div>
                        </div>

                        <div class="mt-4 max-h-[65vh] overflow-y-auto">
                            <div id="plansContainer" class="space-y-2"></div>
                        </div>
                    </div>
                    <form method="dialog" class="modal-backdrop"><button>close</button></form>
                </dialog>

                <script>
                    const CRITERIA_BY_ID = <?php echo json_encode($criteriaById, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
                    const criteriaSearchEl = document.getElementById('criteriaSearch');
                    const criteriaCards = Array.from(document.querySelectorAll('.criteria-card'));
                    const criteriaModal = document.getElementById('criteriaModal');
                    const criteriaModalTitle = document.getElementById('criteriaModalTitle');
                    const criteriaModalMeta = document.getElementById('criteriaModalMeta');
                    const plansContainer = document.getElementById('plansContainer');
                    const addPlanBtn = document.getElementById('addPlanBtn');
                    const statusTabsEl = document.getElementById('statusTabs');
                    const planFormWrap = document.getElementById('planFormWrap');
                    const planIdEl = document.getElementById('planId');
                    const planStatusEl = document.getElementById('planStatus');
                    const planDeliveryModeEl = document.getElementById('planDeliveryMode');
                    const planTargetPctEl = document.getElementById('planTargetPct');
                    const planTextEl = document.getElementById('planText');
                    const cancelPlanBtn = document.getElementById('cancelPlanBtn');
                    const savePlanBtn = document.getElementById('savePlanBtn');

                    let activeCriteriaId = null;
                    let activeStatus = null;

                    const normalize = (s) => String(s || '').toLowerCase();
                    const escapeHtml = (s) => String(s || '')
                        .replaceAll('&', '&amp;')
                        .replaceAll('<', '&lt;')
                        .replaceAll('>', '&gt;')
                        .replaceAll('"', '&quot;')
                        .replaceAll("'", '&#039;');

                    function toast(type, message) {
                        const msg = String(message || '');
                        if (!msg) return;
                        if (window.Swal && typeof window.Swal.fire === 'function') {
                            const isError = String(type || '').toLowerCase() === 'error';
                            window.Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: isError ? 'error' : 'success',
                                title: msg,
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true,
                            });
                        } else {
                            alert(msg);
                        }
                    }

                    async function api(action, payload) {
                        const res = await fetch('development_plans.php?api=1&action=' + encodeURIComponent(action), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(payload || {})
                        });
                        const json = await res.json().catch(() => null);
                        if (!res.ok || !json || json.success !== true) {
                            const msg = (json && json.message) ? json.message : 'Request failed.';
                            throw new Error(msg);
                        }
                        return json;
                    }

                    async function loadPlans(criteriaId, status) {
                        const cid = encodeURIComponent(String(criteriaId));
                        const st = encodeURIComponent(String(status || ''));
                        const url = 'development_plans.php?api=1&action=list_plans&criteria_id=' + cid + (st ? ('&status=' + st) : '');
                        const res = await fetch(url);
                        const json = await res.json().catch(() => null);
                        if (!json || json.success !== true) {
                            throw new Error((json && json.message) ? json.message : 'Failed to load plans.');
                        }
                        return Array.isArray(json.data) ? json.data : [];
                    }

                    function resetPlanForm() {
                        planIdEl.value = '';
                        planStatusEl.value = activeStatus || 'Retrain';
                        planDeliveryModeEl.value = 'Onsite';
                        planTargetPctEl.value = '';
                        planTextEl.value = '';
                    }

                    function showPlanForm(show) {
                        if (show) {
                            planFormWrap.classList.remove('hidden');
                        } else {
                            planFormWrap.classList.add('hidden');
                            resetPlanForm();
                        }
                    }

                    const STATUSES = ['Retrain', 'Reskilling', 'Refresher Training', 'Upskilling', 'Succession Ready'];

                    function renderStatusTabs() {
                        if (!statusTabsEl) return;
                        statusTabsEl.innerHTML = STATUSES.map(st => {
                            const active = activeStatus === st;
                            const cls = active ? 'btn-primary' : 'btn-outline';
                            return `<button type="button" class="btn btn-sm ${cls}" data-status="${escapeHtml(st)}">${escapeHtml(st)}</button>`;
                        }).join('');
                    }

                    async function refreshPlans() {
                        if (!activeCriteriaId || !activeStatus) return;
                        plansContainer.innerHTML = '<div class="text-sm text-base-content/70">Loading...</div>';
                        try {
                            const plans = await loadPlans(activeCriteriaId, activeStatus);
                            renderPlans(plans);
                        } catch (e) {
                            plansContainer.innerHTML = '<div class="text-sm text-base-content/70">No development plans yet.</div>';
                            toast('error', e.message);
                        }
                    }

                    function setActiveStatus(st) {
                        const next = String(st || '').trim();
                        if (!STATUSES.includes(next)) return;
                        activeStatus = next;
                        addPlanBtn.disabled = false;
                        planStatusEl.value = activeStatus;
                        planStatusEl.disabled = true;
                        renderStatusTabs();
                        showPlanForm(false);
                        refreshPlans();
                    }

                    function formatPct(v) {
                        if (v === null || typeof v === 'undefined' || v === '') return '—';
                        const n = Number(v);
                        if (Number.isNaN(n)) return '—';
                        return n.toFixed(1) + '%';
                    }

                    function renderPlans(plans) {
                        if (!Array.isArray(plans) || plans.length === 0) {
                            plansContainer.innerHTML = '<div class="text-sm text-base-content/70">No development plans yet.</div>';
                            return;
                        }

                        plansContainer.innerHTML = plans.map(p => {
                            const id = String(p.id || '');
                            const status = String(p.status || '');
                            const mode = String(p.delivery_mode || '');
                            const target = formatPct(p.target_percentage);
                            const text = String(p.plan_text || '');
                            return `
                                <div class="border border-base-300 rounded-md p-3 bg-base-100">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap gap-2 mb-2">
                                                <span class="badge badge-outline">${escapeHtml(status)}</span>
                                                <span class="badge badge-ghost">${escapeHtml(mode)}</span>
                                                <span class="badge badge-ghost">Target: ${escapeHtml(target)}</span>
                                            </div>
                                            <div class="text-sm whitespace-pre-wrap break-words">${escapeHtml(text)}</div>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <button type="button" class="btn btn-xs btn-outline" data-action="edit" data-id="${escapeHtml(id)}">Edit</button>
                                            <button type="button" class="btn btn-xs btn-outline btn-error" data-action="delete" data-id="${escapeHtml(id)}">Delete</button>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }).join('');
                    }

                    async function openCriteriaModal(criteriaId) {
                        const cid = parseInt(String(criteriaId || '0'), 10);
                        if (!cid) return;
                        activeCriteriaId = cid;
                        activeStatus = null;

                        const meta = (CRITERIA_BY_ID && Object.prototype.hasOwnProperty.call(CRITERIA_BY_ID, String(cid))) ? CRITERIA_BY_ID[String(cid)] : null;
                        const name = meta ? String(meta.name || '') : 'Criteria';
                        const req = meta && typeof meta.required_level !== 'undefined' ? Number(meta.required_level) : null;

                        criteriaModalTitle.textContent = name;
                        criteriaModalMeta.textContent = (req !== null && !Number.isNaN(req)) ? ('Required: ' + req.toFixed(1) + '%') : '';
                        plansContainer.innerHTML = '<div class="text-sm text-base-content/70">Select a status to view plans.</div>';
                        showPlanForm(false);
                        addPlanBtn.disabled = true;
                        planStatusEl.disabled = true;
                        renderStatusTabs();

                        if (criteriaModal && typeof criteriaModal.showModal === 'function') {
                            criteriaModal.showModal();
                        }
                    }
                    window.openCriteriaModal = openCriteriaModal;

                    if (statusTabsEl) {
                        statusTabsEl.addEventListener('click', function(e) {
                            const btn = e.target && e.target.closest ? e.target.closest('button[data-status]') : null;
                            if (!btn) return;
                            const st = btn.getAttribute('data-status') || '';
                            setActiveStatus(st);
                        });
                    }

                    plansContainer.addEventListener('click', async function(e) {
                        const btn = e.target && e.target.closest ? e.target.closest('button[data-action]') : null;
                        if (!btn) return;
                        const action = btn.getAttribute('data-action') || '';
                        const id = parseInt(btn.getAttribute('data-id') || '0', 10);
                        if (!id || !activeCriteriaId) return;

                        if (action === 'edit') {
                            try {
                                if (!activeStatus) {
                                    throw new Error('Select a status first.');
                                }
                                const plans = await loadPlans(activeCriteriaId, activeStatus);
                                const plan = plans.find(p => Number(p.id) === id);
                                if (!plan) throw new Error('Plan not found.');
                                if (String(plan.status || '') && String(plan.status) !== activeStatus) {
                                    setActiveStatus(String(plan.status));
                                }
                                planIdEl.value = String(plan.id);
                                planStatusEl.value = String(plan.status || activeStatus || 'Retrain');
                                planDeliveryModeEl.value = String(plan.delivery_mode || 'Onsite');
                                planTargetPctEl.value = (plan.target_percentage === null || typeof plan.target_percentage === 'undefined') ? '' : String(plan.target_percentage);
                                planTextEl.value = String(plan.plan_text || '');
                                showPlanForm(true);
                            } catch (err) {
                                toast('error', err.message);
                            }
                            return;
                        }

                        if (action === 'delete') {
                            try {
                                if (!activeStatus) {
                                    throw new Error('Select a status first.');
                                }
                                if (window.Swal && typeof window.Swal.fire === 'function') {
                                    const res = await window.Swal.fire({
                                        title: 'Delete plan?',
                                        text: 'This cannot be undone.',
                                        icon: 'warning',
                                        showCancelButton: true,
                                        confirmButtonText: 'Delete',
                                    });
                                    if (!res.isConfirmed) return;
                                } else {
                                    if (!confirm('Delete plan?')) return;
                                }

                                await api('delete_plan', {
                                    id
                                });
                                toast('success', 'Plan deleted.');
                                const plans = await loadPlans(activeCriteriaId, activeStatus);
                                renderPlans(plans);
                                showPlanForm(false);
                            } catch (err) {
                                toast('error', err.message);
                            }
                        }
                    });

                    addPlanBtn.addEventListener('click', function() {
                        if (!activeStatus) {
                            toast('error', 'Select a status first.');
                            return;
                        }
                        resetPlanForm();
                        showPlanForm(true);
                    });

                    cancelPlanBtn.addEventListener('click', function() {
                        showPlanForm(false);
                    });

                    savePlanBtn.addEventListener('click', async function() {
                        if (!activeCriteriaId || !activeStatus) {
                            toast('error', 'Select a status first.');
                            return;
                        }
                        const payload = {
                            criteria_id: activeCriteriaId,
                            status: activeStatus,
                            delivery_mode: planDeliveryModeEl.value,
                            target_percentage: planTargetPctEl.value,
                            plan_text: planTextEl.value
                        };

                        try {
                            const id = parseInt(planIdEl.value || '0', 10);
                            if (id) {
                                await api('update_plan', {
                                    ...payload,
                                    id
                                });
                                toast('success', 'Plan updated.');
                            } else {
                                await api('create_plan', payload);
                                toast('success', 'Plan created.');
                            }
                            const plans = await loadPlans(activeCriteriaId, activeStatus);
                            renderPlans(plans);
                            showPlanForm(false);
                        } catch (err) {
                            toast('error', err.message);
                        }
                    });

                    function applyCriteriaSearch() {
                        const q = normalize(criteriaSearchEl.value);
                        criteriaCards.forEach(card => {
                            const t = normalize(card.getAttribute('data-search'));
                            card.style.display = (q === '' || t.includes(q)) ? '' : 'none';
                        });
                    }

                    criteriaSearchEl.addEventListener('input', applyCriteriaSearch);
                    applyCriteriaSearch();
                </script>
            </main>
        </div>
    </div>
    <?php require('../../partials/footer.php') ?>