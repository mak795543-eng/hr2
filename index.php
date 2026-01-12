<?php
session_start();
include("db.php");


$usm_connection = $connections["hr2_usm"];
// $fin_usm_connection = $connections["fin_usm"];
// $logs2_usm = $connections["logs2_usm"];
// $logs1_usm = $connections["logs1_usm"];
// $cr1_usm = $connections["cr1_usm"];
$cr2_usm = $connections["hr2_soliera_usm"];
// $hr1_2_usm = $connections["hr_1&2_usm"];
// $hr34_usm = $connections["hr34_usm"];
// $cr1_usm = $connections["cr3_usm"] ?? '';

$employee_ID = trim($_POST["employee_id"] ?? '');
$password = trim($_POST["password"] ?? '');
$loginAttemptsKey = "login_attempts_$employee_ID";
$Log_Date_Time = date('Y-m-d H:i:s');


// === Function: Log user login attempts ===
function logAttempt($conn, $Employee_ID, $Employee_name, $Role, $Log_Status, $Attempt_Type, $Attempt_Count, $Failure_reason, $Cooldown)
{
    $date = date('Y-m-d H:i:s');
    $sql = "
        INSERT INTO employee_logs 
        (employee_id, employee_name, role, log_status, attempt_count, log_type, failure_reason, Cooldown, `date`) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param(
        $stmt,
        "sssssssss",
        $Employee_ID,
        $Employee_name,
        $Role,
        $Log_Status,
        $Attempt_Type,
        $Attempt_Count,
        $Failure_reason,
        $Cooldown,
        $date
    );
    mysqli_stmt_execute($stmt);
}

function logDepartmentAttempt($conn, $Department_ID, $employee_ID, $Name, $Role, $Log_Status, $Attempt_type, $Attempt_Count, $Failure_reason, $Cooldown_Until)
{
    $Log_Date_Time = date('Y-m-d H:i:s');
    $sql = "
        INSERT INTO department_logs
        (dept_id, employee_id, employee_name, role, log_status, log_type, attempt_count, failure_reason, cooldown, date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param(
        $stmt,
        "ssssssisss",
        $Department_ID,
        $employee_ID,
        $Name,
        $Role,
        $Log_Status,
        $Attempt_type,
        $Attempt_Count,
        $Failure_reason,
        $Cooldown_Until,
        $Log_Date_Time
    );
    mysqli_stmt_execute($stmt);
}


// === Function: Increment login attempts ===
function incrementLoginAttempts($employee_ID)
{
    $key = "login_attempts_$employee_ID";
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 1, 'last' => time()];
    } else {
        $_SESSION[$key]['count']++;
        $_SESSION[$key]['last'] = time();
    }
}

// === Cooldown enforcement ===
if ($employee_ID !== '' && isset($_SESSION[$loginAttemptsKey]) && $_SESSION[$loginAttemptsKey]['count'] >= 5) {
    $lastAttempt = $_SESSION[$loginAttemptsKey]['last'];
    $remaining = 3600 - (time() - $lastAttempt);
    if ($remaining > 0) {
        $minutes = ceil($remaining / 60);
        $cooldownUntil = date('Y-m-d H:i:s', $lastAttempt + 3600);
        // fin_usm_connection might be commented; guard usage
        if (isset($fin_usm_connection)) {
            logAttempt($fin_usm_connection, $employee_ID, $employee_ID, 'Unknown', 'Failed', '2FA', $_SESSION[$loginAttemptsKey]['count'], 'Account banned (cooldown)', $cooldownUntil);
        }
        $_SESSION["loginError"] = "Your account is temporarily banned. Try again in $minutes minute(s).";
        header("Location: login.php");
        exit();
    } else {
        unset($_SESSION[$loginAttemptsKey]);
    }
}

// === Function: Send OTP via email ===
function sendOTP($email, $otp)
{
    require_once 'PHPMailer/PHPMailerAutoload.php';
    $mail = new PHPMailer;
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;

    // Use environment variables for security instead of hardcoding
    $mail->Username = 'VehicleReservationManagement@gmail.com';
    $mail->Password = 'fzja ezgo ojdu fobc'; // Move to env in production
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('Soliera_Hotel&Restaurant@gmail.com', 'Soliera 2FA Authenticator');
    $mail->addAddress($email);

    $mail->Subject = 'Soliera 2FA Verification Code';

    // Email Header
    $header = "<h2 style='color:#4CAF50; font-family: Arial, sans-serif;'>Soliera Hotel & Restaurant</h2>
               <hr style='border:1px solid #ddd;'>";

    // Main Message
    $message = "<p style='font-family: Arial, sans-serif; font-size:14px;'>
                    <br>
                    We received a request to verify your login to <strong>Soliera Hotel & Restaurant</strong>.
                    Please use the one-time verification code below to complete your login:
                </p>
                <p style='font-size:22px; font-weight:bold; color:#333; letter-spacing:2px;'>
                    $otp
                </p>
                <p style='font-family: Arial, sans-serif; font-size:14px; color:#555;'>
                    This code will expire in <strong>5 minutes</strong> for your security.
                    If you did not request this code, please ignore this email or contact our support team immediately.
                </p>";

    // Email Footer
    $footer = "<hr style='border:1px solid #ddd;'>
               <p style='font-size:12px; color:#777; font-family: Arial, sans-serif;'>
                    Thank you for choosing Soliera.<br>
                    📞 Hotline: +63-900-123-4567 | 📧 support@soliera.com<br>
                    <em>This is an automated message. Please do not reply directly to this email.</em>
               </p>";

    // Final HTML body
    $mail->isHTML(true);
    $mail->Body = $header . $message . $footer;

    return $mail->send();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $employee_ID && $password) {
    // Step 1: CAPTCHA validation
    $recaptcha_secret = "6LdY-KorAAAAAGcqt-PDA0GhtsBEM8AEJhxpKpkW";
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
    $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptcha_secret}&response={$recaptcha_response}");
    $captcha_success = json_decode($verify);

    if (!$captcha_success->success) {
        $_SESSION["loginError"] = "Captcha verification failed. Please try again.";
        header("Location: login_main.php");
        exit();
    }

    // Step 2: Continue with your normal login + OTP + logs
    // (Your Core 2 / USM database checks remain unchanged here)
}

// === Main Login Logic ===
if ($_SERVER["REQUEST_METHOD"] === "POST" && $employee_ID && $password) {

    // ---------------------------------------------------------------------
    // NOTE: Many department checks are commented out below (left as-is).
    // The active checks: Core 2 (uses 2FA/pending flow) and Department USM (direct login).
    // I preserved your commented sections unchanged.
    // ---------------------------------------------------------------------

    // Core 2
    $stmt = mysqli_prepare($cr2_usm, "SELECT email, employee_name, password, Dept_id , employee_id, role FROM department_accounts WHERE employee_id = ?");
    mysqli_stmt_bind_param($stmt, "s", $employee_ID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $Department_ID = $row["Dept_id"];
        $Role = $row["role"];
        $Name = $row["employee_name"];

        // === Password check (plain equality in your original; keep but recommend hashing)
        if ($password === $row["password"]) {
            // generate OTP and store PENDING login state (do NOT mark full session yet)
            $otp = rand(100000, 999999);
            $_SESSION["otp"] = (string)$otp;
            $_SESSION["otp_expiry"] = time() + 300; // 5 minutes expiry

            // store pending login info (so 2fa page can validate)
            $_SESSION["pending_employee_id"] = $employee_ID;
            $_SESSION["pending_role"] = $Role;
            $_SESSION["pending_Dept_id"] = $row["Dept_id"];
            $_SESSION["pending_email"] = $row["email"];
            $_SESSION["otp_attempts"] = 0;
            $_SESSION["auth_method"] = "2FA";

            if (sendOTP($row["email"], $otp)) {
                logAttempt($cr2_usm, $employee_ID, $Name, $Role, 'Authenticating', 'Login', 0, 'Authenticating', '');
                logDepartmentAttempt($cr2_usm, $Department_ID, $employee_ID, $Name, $Role, 'Success', 'Login', 0, 'Login Successful', '');
                header("Location: USM/2fa_verify.php");
                exit();
            } else {
                logAttempt($cr2_usm, $employee_ID, $Name, $Role, 'Failed', 'Login', 0, 'Failed to send OTP email', '');
                $_SESSION["loginError"] = "Failed to send OTP email.";
                header("Location: index.php");
                exit();
            }
        } else {
            incrementLoginAttempts($employee_ID);
            logAttempt($cr2_usm, $employee_ID, $Name, $Role, 'Failed', 'Login', 0, 'Incorrect password', '');
            $_SESSION["loginError"] = "Incorrect password.";
            header("Location: index.php");
            exit();
        }
    }

    // Check in Department USM (this one seems to bypass 2FA in your original - preserved)
    $stmt = mysqli_prepare($usm_connection, "SELECT email, employee_name, password, role, Dept_id FROM department_accounts WHERE employee_id = ?");
    mysqli_stmt_bind_param($stmt, "s", $employee_ID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $Department_ID = $row["Dept_id"];
        $Role = $row["role"];
        $Name = $row["employee_name"];

        if ($password === $row["password"]) {
            // Full login for this DB (as in original)
            $_SESSION["employee_id"] = $employee_ID;
            // fix: use $Role (was $role in original)
            $_SESSION["role"] = $Role;
            $_SESSION["Dept_id"] = $row["Dept_id"];
            $_SESSION["email"] = $row["email"] ?? $row["Email"] ?? '';
            header("Location: dashboard.php");
            exit();
        } else {
            incrementLoginAttempts($employee_ID);
            logAttempt($usm_connection, $employee_ID, $Name, $Role, 'Failed', 'Login', 0, 'Incorrect password', '');
            $_SESSION["loginError"] = "Incorrect password.";
            header("Location: index.php");
            exit();
        }
    }

    // If we reach here — no user found in checked DBs
    $_SESSION["loginError"] = "Invalid employee ID or password.";
    header("Location: index.php");
    exit();

    
}


?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soliera Hotel - Department Login</title>
    
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
        <script src="https://cdn.tailwindcss.com"></script>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>


    <style>
        /* Add any custom styles here if needed */
    </style>
</head>
<body>
   <section class="relative w-full h-screen">
        <!-- Background image with overlay -->
        <div class="absolute inset-0 bg-cover bg-center z-0" style="background-image: url('hotel3.jpg');"></div>
        <div class="absolute inset-0 bg-black/40 z-10"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-black/70 z-10"></div>
        
        <!-- Content container -->
        <div class="relative z-10 w-full h-full flex justify-center items-center p-4">
            <div class="w-1/2 flex justify-center items-center max-md:hidden">
                <div class="max-w-lg p-8">
                    <!-- Hotel & Restaurant Illustration -->
                    <div class="text-center mb-8">
                        <a href="/">
                            <img data-aos="zoom-in" data-aos-delay="100" class="w-full max-h-52 hover:scale-105 transition-all" src="logo.svg" alt="">
                        </a>
                        <h1 data-aos="zoom-in-up" data-aos-delay="200" class="text-3xl font-bold text-white mb-2">Welcome to <span class="text-[#F7B32B]">Soliera</span> Hotel & Restaurant</h1>
                        <p data-aos="zoom-in-up" data-aos-delay="300" class="text-white/80">Savor The Stay, Dine With Elegance</p>
                    </div>
                </div>
            </div>
            
            <div class="w-1/2 flex justify-center items-center max-md:w-full">
                <div class="max-w-md w-full bg-white/10 backdrop-blur-lg p-6 rounded-xl shadow-2xl border border-white/20">
                    <!-- Card Header -->
                    <div class="mb-6 text-center flex justify-center items-center flex-col">
                        <h2 class="text-2xl font-bold text-white">Sign in to your account</h2>
                        <p class="text-white/80 mt-1">Enter your credentials to continue</p>
                    </div>
                    
                    <!-- Card Body -->
                    <div>
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <!-- Employee ID Input -->
                            <div class="mb-4">
                                <label class="block text-white/90 text-sm font-medium mb-2" for="employee_id">
                                    Employee ID
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <box-icon name='user' color="rgba(255,255,255,0.5)"></box-icon>
                                    </div>
                                    <input 
                                        id="employee_id" 
                                        type="text" 
                                        class="w-full pl-10 pr-3 py-3 bg-white/5 border border-white/20 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-transparent placeholder-white/50" 
                                        placeholder="Your ID"
                                        required
                                        name="employee_id"
                                        value="<?php echo htmlspecialchars($employee_ID); ?>"
                                    >
                                </div>
                            </div>
                            
                            <!-- Password Input with Toggle -->
                            <div class="mb-6">
                                <label class="block text-white/90 text-sm font-medium mb-2" for="password">
                                    Password
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <box-icon name='key' color="rgba(255,255,255,0.5)"></box-icon>
                                    </div>
                                    <input 
                                        id="password" 
                                        type="password" 
                                        class="w-full pl-10 pr-10 py-3 bg-white/5 border border-white/20 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-transparent placeholder-white/50" 
                                        placeholder="••••••••"
                                        required
                                        name="password"
                                        value="<?php echo htmlspecialchars($password); ?>"
                                    >
                                    <button 
                                        type="button" 
                                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-white/50 hover:text-white focus:outline-none"
                                        onclick="togglePasswordVisibility()"
                                    >
                                        <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <svg id="eye-slash-icon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd"/>
                                            <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Remember Me & Forgot Password -->
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center">
                                    <input id="remember-me" name="remember-me" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-white/30 rounded bg-white/10">
                                    <label for="remember-me" class="ml-2 block text-sm text-white/80">
                                        Remember me
                                    </label>
                                </div>
                                <div class="text-sm">
                                  <a href="javascript:void(0)" onclick="toggleForgotModal(true)" class="font-medium text-blue-400 hover:text-blue-300">
    Forgot password?
</a>

                                </div>
                            </div>

                            <!-- Google reCAPTCHA widget -->
                            <div class="mb-4">
                                <div class="g-recaptcha" data-sitekey="6LdY-KorAAAAAGVPn6PIe_Ro0UrFB-9DBYqDZ6_f"></div>
                            </div>

                            <!-- Sign In Button -->
                            <button 
                                type="submit" 
                                value="Login"
                                class="w-full bg-[#EDB886] hover:bg-[#F7B32B] text-white font-bold py-3 px-4 rounded-lg transition duration-300"
                            >
                                Login
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

       <!-- Forgot Password Modal -->
<div id="forgot-modal" class="fixed inset-0 bg-black/20 backdrop-blur-sm hidden items-center justify-center z-50">
    <div class="bg-white/90 backdrop-blur-md rounded-xl p-6 w-full max-w-md shadow-xl border border-white/20">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-gray-800">Reset your password</h2>
            <button onclick="toggleForgotModal(false)" class="text-gray-500 hover:text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <form action="forgot_password.php" method="POST">
            <div class="mb-4">
                <label class="block mb-2 text-sm font-medium text-gray-700">Email address</label>
                <input type="email" name="email" required 
                       class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            
            <div class="flex justify-end gap-3">
                <button type="button" onclick="toggleForgotModal(false)" 
                        class="px-4 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 text-gray-800 transition">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition">
                    Send Reset Link
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleForgotModal(show) {
    const modal = document.getElementById('forgot-modal');
    if (show) {
        modal.classList.remove('hidden');
    } else {
        modal.classList.add('hidden');
    }
}
</script>
        
        <div class="absolute left-5 bottom-5 text-white text-sm z-20">Build By: BSIT - 4102</div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="https://unpkg.com/boxicons@2.1.4/dist/boxicons.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: true
        });
        
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            const eyeSlashIcon = document.getElementById('eye-slash-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.add('hidden');
                eyeSlashIcon.classList.remove('hidden');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('hidden');
                eyeSlashIcon.classList.add('hidden');
            }
        }

        function toggleForgotModal(show) {
    const modal = document.getElementById("forgot-modal");
    if (show) {
        modal.classList.remove("hidden", "opacity-0");
        modal.classList.add("flex");
    } else {
        modal.classList.add("hidden");
        modal.classList.remove("flex");
    }
}

    </script>
    
    <?php if (isset($_SESSION["loginError"])): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Login Failed',
                text: '<?= htmlspecialchars($_SESSION["loginError"], ENT_QUOTES); ?>',
                confirmButtonColor: '#3085d6'
            });
        </script>
    <?php 
        unset($_SESSION["loginError"]);
    endif; ?>
</body>
</html>