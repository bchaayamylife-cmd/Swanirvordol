<?php
/**
 * Multi-Group SHG Savings, Loan & Accounting Management System
 * Single-file enterprise architecture optimized for shared hosting (InfinityFree).
 * Timezone: Asia/Kolkata | Currency: INR (₹)
 */

declare(strict_types=1);
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Timezone and Session configuration
date_default_timezone_set('Asia/Kolkata');
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ==========================================================================
   1. DATABASE CONFIGURATION (InfinityFree Compatibility)
   ========================================================================== */
define('DB_HOST', 'localhost');
define('DB_NAME', 'barber_shop'); // Update if InfinityFree appends your vPanel prefix (e.g., epiz_12345678_barber_shop)
define('DB_USER', 'root');        // Update with InfinityFree MySQL Username
define('DB_PASS', '');            // Update with InfinityFree MySQL Password
define('DB_CHARSET', 'utf8mb4');

function get_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Database Connection Error. Please verify database credentials in index.php. " . htmlspecialchars($e->getMessage()));
        }
    }
    return $pdo;
}

/* ==========================================================================
   2. CSRF & SECURITY HELPERS
   ========================================================================== */
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool {
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function e(?string $str): string {
    return htmlspecialchars((string)$str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function format_currency(float $amt): string {
    return '₹ ' . number_format($amt, 2, '.', ',');
}

function format_date(?string $dt): string {
    if (empty($dt) || $dt === '0000-00-00' || $dt === '0000-00-00 00:00:00') return '-';
    return date('d-m-Y', strtotime($dt));
}

function audit_log(int $groupId, ?int $userId, string $action, string $details): void {
    try {
        $db = get_db();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $stmt = $db->prepare("INSERT INTO audit_logs (group_id, user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$groupId, $userId, $action, $details, $ip]);
    } catch (Exception $ex) {
        // Fail silently on audit log failure to preserve primary transaction
    }
}

/* ==========================================================================
   3. MULTI-LANGUAGE ENGINE (14 Languages Supported)
   ========================================================================== */
$SUPPORTED_LANGUAGES = [
    'bn' => ['name' => 'বাংলা', 'dir' => 'ltr'],
    'hi' => ['name' => 'हिन्दी', 'dir' => 'ltr'],
    'en' => ['name' => 'English', 'dir' => 'ltr'],
    'as' => ['name' => 'অসমীয়া', 'dir' => 'ltr'],
    'ne' => ['name' => 'नेपाली', 'dir' => 'ltr'],
    'pa' => ['name' => 'ਪੰਜਾਬੀ', 'dir' => 'ltr'],
    'ta' => ['name' => 'தமிழ்', 'dir' => 'ltr'],
    'te' => ['name' => 'తెలుగు', 'dir' => 'ltr'],
    'kn' => ['name' => 'ಕನ್ನಡ', 'dir' => 'ltr'],
    'ml' => ['name' => 'മലയാളം', 'dir' => 'ltr'],
    'mr' => ['name' => 'मराठी', 'dir' => 'ltr'],
    'gu' => ['name' => 'ગુજરાતી', 'dir' => 'ltr'],
    'or' => ['name' => 'ଓଡ଼ିଆ', 'dir' => 'ltr'],
    'ur' => ['name' => 'اردو', 'dir' => 'rtl']
];

$TRANSLATIONS = [
    'bn' => [
        'app_name' => 'স্বনির্ভর দল পরিচালন ব্যবস্থা',
        'dashboard' => 'ড্যাশবোর্ড',
        'group_info' => 'দলের তথ্য',
        'members' => 'সদস্য তালিকা',
        'new_member' => 'নতুন সদস্য',
        'savings' => 'সঞ্চয় জমা',
        'savings_withdraw' => 'সঞ্চয় উত্তোলন',
        'savings_register' => 'সঞ্চয় খাতা',
        'loan_apply' => 'ঋণের আবেদন',
        'loan_disburse' => 'ঋণ বিতরণ ও খাতা',
        'repayment' => 'কিস্তি আদায়',
        'loan_register' => 'ঋণ খাতা',
        'outstanding_loans' => 'বকেয়া ঋণ',
        'overdue_loans' => 'খেলাপি ঋণ',
        'cashbook' => 'ক্যাশ বুক',
        'reports' => 'রিপোর্ট ও বিবরণী',
        'settings' => 'সেটিংস',
        'change_password' => 'পাসওয়ার্ড পরিবর্তন',
        'logout' => 'লগআউট',
        'login' => 'লগইন',
        'register_group' => 'নতুন দল নিবন্ধন',
        'super_admin' => 'সুপার অ্যাডমিন',
        'total_members' => 'মোট সদস্য',
        'active_members' => 'সক্রিয় সদস্য',
        'total_savings' => 'মোট সঞ্চয়',
        'total_loan_disbursed' => 'মোট ঋণ বিতরণ',
        'total_loan_recovered' => 'মোট ঋণ আদায়',
        'outstanding_principal' => 'বকেয়া আসল',
        'outstanding_interest' => 'বকেয়া সুদ',
        'total_outstanding' => 'মোট বকেয়া ঋণ',
        'overdue_amount' => 'খেলাপি ঋণ',
        'today_savings' => 'আজকের সঞ্চয় জমা',
        'today_repayment' => 'আজকের ঋণ পরিশোধ',
        'recent_transactions' => 'সাম্প্রতিক লেনদেন',
        'sl_no' => 'ক্রমিক নং',
        'date' => 'তারিখ',
        'member_name' => 'সদস্যের নাম',
        'member_no' => 'সদস্য নম্বর',
        'phone' => 'মোবাইল',
        'amount' => 'পরিমাণ',
        'status' => 'অবস্থা',
        'actions' => 'কার্যক্রম',
        'save' => 'সংরক্ষণ করুন',
        'submit' => 'জমা দিন',
        'print' => 'প্রিন্ট করুন',
        'export_csv' => 'CSV এক্সপোর্ট',
        'search' => 'অনুসন্ধান',
        'select_language' => 'ভাষা নির্বাচন',
        'blank_register' => 'খালি রেজিস্টার ফরম (২০ সারি)',
        'signature' => 'স্বাক্ষর',
        'president' => 'সভাপতি',
        'secretary' => 'সম্পাদক',
        'treasurer' => 'কোষাধ্যক্ষ',
        'view_statement' => 'বিবরণী দেখুন',
        'interest_method' => 'সুদের পদ্ধতি',
        'flat_rate' => 'সরল সুদ (Flat)',
        'reducing_rate' => 'হ্রাসমান আসল সুদ (Reducing)',
        'tenure_months' => 'সময়কাল (মাস)',
        'installment_amount' => 'কিস্তির পরিমাণ',
        'principal' => 'আসল',
        'interest' => 'সুদ',
        'cashbook_page' => 'ক্যাশ বই পৃষ্ঠা নং',
        'remarks' => 'মন্তব্য',
        'receipt_voucher' => 'রসিদ / ভাউচার নং',
        'unauthorized' => 'অননুমোদিত প্রবেশাধিকার',
        'operation_success' => 'কাজটি সফলভাবে সম্পন্ন হয়েছে',
        'operation_failed' => 'ত্রুটি দেখা দিয়েছে, পুনরায় চেষ্টা করুন'
    ],
    'en' => [
        'app_name' => 'SHG Management System',
        'dashboard' => 'Dashboard',
        'group_info' => 'Group Info',
        'members' => 'Member List',
        'new_member' => 'New Member',
        'savings' => 'Savings Deposit',
        'savings_withdraw' => 'Savings Withdrawal',
        'savings_register' => 'Savings Register',
        'loan_apply' => 'Apply Loan',
        'loan_disburse' => 'Disbursements & Loans',
        'repayment' => 'Repayments',
        'loan_register' => 'Loan Register',
        'outstanding_loans' => 'Outstanding Loans',
        'overdue_loans' => 'Overdue Loans',
        'cashbook' => 'Cashbook',
        'reports' => 'Reports & Statements',
        'settings' => 'Settings',
        'change_password' => 'Change Password',
        'logout' => 'Logout',
        'login' => 'Login',
        'register_group' => 'Register New SHG',
        'super_admin' => 'Super Admin',
        'total_members' => 'Total Members',
        'active_members' => 'Active Members',
        'total_savings' => 'Total Savings Balance',
        'total_loan_disbursed' => 'Total Disbursed',
        'total_loan_recovered' => 'Total Recovered',
        'outstanding_principal' => 'Outstanding Principal',
        'outstanding_interest' => 'Outstanding Interest',
        'total_outstanding' => 'Total Outstanding',
        'overdue_amount' => 'Overdue Balance',
        'today_savings' => 'Today\'s Savings',
        'today_repayment' => 'Today\'s Repayment',
        'recent_transactions' => 'Recent Transactions',
        'sl_no' => 'Sl No.',
        'date' => 'Date',
        'member_name' => 'Member Name',
        'member_no' => 'Member No',
        'phone' => 'Phone',
        'amount' => 'Amount',
        'status' => 'Status',
        'actions' => 'Actions',
        'save' => 'Save',
        'submit' => 'Submit',
        'print' => 'Print',
        'export_csv' => 'Export CSV',
        'search' => 'Search',
        'select_language' => 'Language',
        'blank_register' => 'Blank Register Form (20 Rows)',
        'signature' => 'Signature',
        'president' => 'President',
        'secretary' => 'Secretary',
        'treasurer' => 'Treasurer',
        'view_statement' => 'Statement',
        'interest_method' => 'Interest Method',
        'flat_rate' => 'Flat Interest',
        'reducing_rate' => 'Reducing Balance',
        'tenure_months' => 'Tenure (Months)',
        'installment_amount' => 'Installment Amount',
        'principal' => 'Principal',
        'interest' => 'Interest',
        'cashbook_page' => 'Cashbook Page No',
        'remarks' => 'Remarks',
        'receipt_voucher' => 'Receipt/Voucher No',
        'unauthorized' => 'Unauthorized Access',
        'operation_success' => 'Operation completed successfully.',
        'operation_failed' => 'Operation failed, please try again.'
    ],
    'hi' => [
        'app_name' => 'स्वयं सहायता समूह प्रबंधन प्रणाली',
        'dashboard' => 'डैशबोर्ड',
        'group_info' => 'समूह जानकारी',
        'members' => 'सदस्य सूची',
        'new_member' => 'नया सदस्य',
        'savings' => 'बचत जमा',
        'savings_withdraw' => 'बचत निकासी',
        'savings_register' => 'बचत रजिस्टर',
        'loan_apply' => 'ऋण आवेदन',
        'loan_disburse' => 'ऋण वितरण व रजिस्टर',
        'repayment' => 'किस्त वसूली',
        'loan_register' => 'ऋण बही',
        'outstanding_loans' => 'बकाया ऋण',
        'overdue_loans' => 'अतिदेय ऋण',
        'cashbook' => 'रोकड़ बही',
        'reports' => 'रिपोर्ट और विवरण',
        'settings' => 'सेटिंग्स',
        'change_password' => 'पासवर्ड बदलें',
        'logout' => 'लॉग आउट',
        'login' => 'लॉग इन',
        'register_group' => 'नया समूह पंजीकरण',
        'super_admin' => 'सुपर एडमिन',
        'total_members' => 'कुल सदस्य',
        'active_members' => 'सक्रिय सदस्य',
        'total_savings' => 'कुल बचत',
        'total_loan_disbursed' => 'कुल वितरित ऋण',
        'total_loan_recovered' => 'कुल ऋण वसूली',
        'outstanding_principal' => 'बकाया मूलधन',
        'outstanding_interest' => 'बकाया ब्याज',
        'total_outstanding' => 'कुल बकाया ऋण',
        'overdue_amount' => 'अतिदेय (डिफ़ॉल्ट) ऋण',
        'today_savings' => 'आज की बचत जमा',
        'today_repayment' => 'आज की ऋण वसूली',
        'recent_transactions' => 'हाल के लेनदेन',
        'sl_no' => 'क्र.सं.',
        'date' => 'दिनांक',
        'member_name' => 'सदस्य का नाम',
        'member_no' => 'सदस्य संख्या',
        'phone' => 'मोबाइल',
        'amount' => 'राशि',
        'status' => 'स्थिति',
        'actions' => 'कार्रवाई',
        'save' => 'सुरक्षित करें',
        'submit' => 'जमा करें',
        'print' => 'प्रिंट करें',
        'export_csv' => 'CSV डाउनलोड',
        'search' => 'खोजें',
        'select_language' => 'भाषा चुनें',
        'blank_register' => 'खाली रजिस्टर फॉर्म (20 पंक्तियाँ)',
        'signature' => 'हस्ताक्षर',
        'president' => 'अध्यक्ष',
        'secretary' => 'सचिव',
        'treasurer' => 'कोषाध्यक्ष',
        'view_statement' => 'विवरण देखें',
        'interest_method' => 'ब्याज पद्धति',
        'flat_rate' => 'सरल ब्याज',
        'reducing_rate' => 'घटते शेष पर ब्याज',
        'tenure_months' => 'अवधि (माह)',
        'installment_amount' => 'किस्त राशि',
        'principal' => 'मूलधन',
        'interest' => 'ब्याज',
        'cashbook_page' => 'रोकड़ बही पृष्ठ संख्या',
        'remarks' => 'टिप्पणी',
        'receipt_voucher' => 'रसीद / वाउचर संख्या',
        'unauthorized' => 'अनधिकृत पहुंच',
        'operation_success' => 'कार्य सफलतापूर्वक पूरा हुआ',
        'operation_failed' => 'त्रुटि हुई, कृपया पुनः प्रयास करें'
    ],
    'as' => [
        'app_name' => 'আত্মসহায়ক গোট ব্যৱস্থাপনা প্রণালী',
        'dashboard' => 'ডেচবৰ্ড',
        'group_info' => 'গোটৰ তথ্য',
        'members' => 'সদস্য তালিকা',
        'new_member' => 'নতুন সদস্য',
        'savings' => 'সঞ্চয় জমা',
        'savings_withdraw' => 'সঞ্চয় প্ৰত্যাহাৰ',
        'savings_register' => 'সঞ্চয় বহী',
        'loan_apply' => 'ঋণৰ আবেদন',
        'loan_disburse' => 'ঋণ বিতৰণ',
        'repayment' => 'কিস্তি আদায়',
        'loan_register' => 'ঋণ বহী',
        'outstanding_loans' => 'বাকী থকা ঋণ',
        'overdue_loans' => 'খেলাপী ঋণ',
        'cashbook' => 'কেচবুক',
        'reports' => 'প্রতিবেদন',
        'settings' => 'ছেটিংছ',
        'change_password' => 'পাছৱৰ্ড সলনি',
        'logout' => 'লগআউট',
        'login' => 'লগইন',
        'register_group' => 'নতুন গোট পঞ্জীয়ন',
        'super_admin' => 'ছুপাৰ এডমিন',
        'total_members' => 'মুঠ সদস্য',
        'active_members' => 'সক্ৰিয় সদস্য',
        'total_savings' => 'মুঠ সঞ্চয়',
        'total_loan_disbursed' => 'মুঠ বিতৰণ কৰা ঋণ',
        'total_loan_recovered' => 'মুঠ আদায় ঋণ',
        'outstanding_principal' => 'বাকী থকা মূলধন',
        'outstanding_interest' => 'বাকী থকা সুদ',
        'total_outstanding' => 'মুঠ বাকী ঋণ',
        'overdue_amount' => 'খেলাপী ঋণ',
        'today_savings' => 'আজিৰ সঞ্চয়',
        'today_repayment' => 'আজিৰ ঋণ পৰিশোধ',
        'recent_transactions' => 'শেহতীয়া লেনদেন',
        'sl_no' => 'ক্ৰমিক নং',
        'date' => 'তাৰিখ',
        'member_name' => 'সদস্যৰ নাম',
        'member_no' => 'সদস্য নং',
        'phone' => 'মোবাইল',
        'amount' => 'পৰিমাণ',
        'status' => 'স্থিতি',
        'actions' => 'কাৰ্য্য',
        'save' => 'সংৰক্ষণ কৰক',
        'submit' => 'দাখিল কৰক',
        'print' => 'প্ৰিণ্ট কৰক',
        'export_csv' => 'CSV এক্সপোৰ্ট',
        'search' => 'সন্ধান কৰক',
        'select_language' => 'ভাষা নিৰ্বাচন',
        'blank_register' => 'খালী বহী প্ৰপত্ৰ (২০ শাৰী)',
        'signature' => 'স্বাক্ষৰ',
        'president' => 'সভাপতি',
        'secretary' => 'সম্পাদক',
        'treasurer' => 'কোষাধ্যক্ষ',
        'view_statement' => 'বিৱৰণী চাওক',
        'interest_method' => 'সুদৰ পদ্ধতি',
        'flat_rate' => 'সৰল সুদ',
        'reducing_rate' => 'হ্ৰাসমান সুদ',
        'tenure_months' => 'সময়সীমা (মাহ)',
        'installment_amount' => 'কিস্তিৰ পৰিমাণ',
        'principal' => 'মূলধন',
        'interest' => 'সুদ',
        'cashbook_page' => 'কেচবুক পৃষ্ঠা নং',
        'remarks' => 'মন্তব্য',
        'receipt_voucher' => 'ৰচিদ নং',
        'unauthorized' => 'অননুমোদিত প্ৰৱেশ',
        'operation_success' => 'কাৰ্য্য সফলতাৰে সম্পূৰ্ণ হৈছে',
        'operation_failed' => 'ত্ৰুটি হৈছে, পুনৰ চেষ্টা কৰক'
    ],
    'ur' => [
        'app_name' => 'سیلف ہیلپ گروپ مینیجمنٹ سسٹم',
        'dashboard' => 'ڈیش بورڈ',
        'group_info' => 'گروپ کی تفصیلات',
        'members' => 'ارکان کی فہرست',
        'new_member' => 'نیا رکن',
        'savings' => 'بچت جمع',
        'savings_withdraw' => 'بچت کی واپسی',
        'savings_register' => 'بچت رجسٹر',
        'loan_apply' => 'قرض کی درخواست',
        'loan_disburse' => 'قرض کی تقسیم',
        'repayment' => 'قسط کی وصولی',
        'loan_register' => 'قرض کا رجسٹر',
        'outstanding_loans' => 'بقیہ قرضہ جات',
        'overdue_loans' => 'واجب الادا قرضے',
        'cashbook' => 'کیش بک',
        'reports' => 'رپورٹس اور گوشوارے',
        'settings' => 'ترتیبات',
        'change_password' => 'پاس ورڈ تبدیل کریں',
        'logout' => 'لاگ آؤٹ',
        'login' => 'لاگ ان',
        'register_group' => 'نیا گروپ رجسٹر کریں',
        'super_admin' => 'سپر ایڈمن',
        'total_members' => 'کل ارکان',
        'active_members' => 'فعال ارکان',
        'total_savings' => 'کل بچت',
        'total_loan_disbursed' => 'کل تقسیم شدہ قرض',
        'total_loan_recovered' => 'کل وصول شدہ قرض',
        'outstanding_principal' => 'بقیہ اصل رقم',
        'outstanding_interest' => 'بقیہ سود',
        'total_outstanding' => 'کل بقیہ رقم',
        'overdue_amount' => 'تاخیر شدہ واجبات',
        'today_savings' => 'آج کی بچت',
        'today_repayment' => 'آج کی وصولی',
        'recent_transactions' => 'حالیہ لین دین',
        'sl_no' => 'شمار نمبر',
        'date' => 'تاریخ',
        'member_name' => 'رکن کا نام',
        'member_no' => 'رکن نمبر',
        'phone' => 'فون نمبر',
        'amount' => 'رقم',
        'status' => 'حیثیت',
        'actions' => 'کارروائی',
        'save' => 'محفوظ کریں',
        'submit' => 'جمع کریں',
        'print' => 'پرنٹ کریں',
        'export_csv' => 'سی ایس وی ایکسپورٹ',
        'search' => 'تلاش کریں',
        'select_language' => 'زبان منتخب کریں',
        'blank_register' => 'خالی رجسٹر فارم (20 قطاریں)',
        'signature' => 'دستخط',
        'president' => 'صدر',
        'secretary' => 'سیکرٹری',
        'treasurer' => 'خزانچی',
        'view_statement' => 'گوشوارہ دیکھیں',
        'interest_method' => 'سود کا طریقہ',
        'flat_rate' => 'یکطرفہ سود (فلیٹ)',
        'reducing_rate' => 'گھٹتی ہوئی اصل رقم پر سود',
        'tenure_months' => 'مدت (ماہ)',
        'installment_amount' => 'قسط کی رقم',
        'principal' => 'اصل',
        'interest' => 'سود',
        'cashbook_page' => 'کیش بک صفحہ نمبر',
        'remarks' => 'تبصرہ',
        'receipt_voucher' => 'رسید نمبر',
        'unauthorized' => 'غیر مجاز رسائی',
        'operation_success' => 'کارروائی کامیابی سے مکمل ہو گئی ہے',
        'operation_failed' => 'خرابی واقع ہوئی ہے، دوبارہ کوشش کریں'
    ]
];

// Handle language selection
if (isset($_GET['lang']) && array_key_exists($_GET['lang'], $SUPPORTED_LANGUAGES)) {
    $_SESSION['app_lang'] = $_GET['lang'];
    if (!empty($_SESSION['user_id'])) {
        try {
            $db = get_db();
            $stmt = $db->prepare("UPDATE users SET preferred_language = ? WHERE id = ?");
            $stmt->execute([$_GET['lang'], $_SESSION['user_id']]);
        } catch (Exception $e) {}
    }
    $redirect = strtok($_SERVER['REQUEST_URI'], '?');
    $query = $_GET;
    unset($query['lang']);
    $url = $redirect . (!empty($query) ? '?' . http_build_query($query) : '');
    header("Location: " . $url);
    exit;
}

$CURRENT_LANG = $_SESSION['app_lang'] ?? 'bn';
if (!array_key_exists($CURRENT_LANG, $SUPPORTED_LANGUAGES)) {
    $CURRENT_LANG = 'bn';
}

function __t(string $key): string {
    global $TRANSLATIONS, $CURRENT_LANG;
    if (isset($TRANSLATIONS[$CURRENT_LANG][$key])) {
        return $TRANSLATIONS[$CURRENT_LANG][$key];
    }
    if (isset($TRANSLATIONS['bn'][$key])) {
        return $TRANSLATIONS['bn'][$key];
    }
    if (isset($TRANSLATIONS['en'][$key])) {
        return $TRANSLATIONS['en'][$key];
    }
    return ucwords(str_replace('_', ' ', $key));
}

/* ==========================================================================
   4. AUTHENTICATION & ACCESS CONTROL
   ========================================================================== */
function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header("Location: index.php?page=login");
        exit;
    }
}

function require_super_admin(): void {
    require_login();
    if (($_SESSION['user']['role'] ?? '') !== 'super_admin') {
        die("<h3>" . e(__t('unauthorized')) . " - Super Admin access required.</h3>");
    }
}

function current_group_id(): int {
    return (int)($_SESSION['current_group_id'] ?? 1);
}

// Authentication Actions
$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? '';
$error = '';
$success = '';

if ($action === 'login_submit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "CSRF Verification failed.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $db = get_db();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user'] = $user;
            $_SESSION['current_group_id'] = $user['group_id'] ?? 1;
            if (!empty($user['preferred_language'])) {
                $_SESSION['app_lang'] = $user['preferred_language'];
            }
            audit_log((int)$user['group_id'], (int)$user['id'], 'LOGIN', 'User logged in successfully');
            header("Location: index.php?page=dashboard");
            exit;
        } else {
            $error = "অবৈধ ব্যবহারকারীর নাম বা পাসওয়ার্ড! (Invalid username or password)";
        }
    }
}

if ($action === 'logout') {
    if (is_logged_in()) {
        audit_log(current_group_id(), (int)$_SESSION['user_id'], 'LOGOUT', 'User logged out');
    }
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
    header("Location: index.php?page=login");
    exit;
}

/* ==========================================================================
   5. BUSINESS LOGIC & FORM PROCESSING
   ========================================================================== */
$db = get_db();

// 5.1 Public Group Registration Application
if ($action === 'register_group_submit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Security token mismatch.";
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO group_applications (
                group_name, address, village, gram_panchayat, block, district, state, pin_code, contact_mobile, email, 
                formation_date, member_count, notes, preferred_language, president_name, president_mobile, president_address,
                secretary_name, secretary_mobile, secretary_address, treasurer_name, treasurer_mobile, treasurer_address,
                status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
            
            $stmt->execute([
                trim($_POST['group_name']), trim($_POST['address']), trim($_POST['village']), trim($_POST['gram_panchayat']),
                trim($_POST['block']), trim($_POST['district']), trim($_POST['state']), trim($_POST['pin_code']),
                trim($_POST['contact_mobile']), trim($_POST['email'] ?? ''), $_POST['formation_date'] ?: null,
                (int)$_POST['member_count'], trim($_POST['notes'] ?? ''), $_POST['preferred_language'] ?? 'bn',
                trim($_POST['president_name']), trim($_POST['president_mobile']), trim($_POST['president_address']),
                trim($_POST['secretary_name']), trim($_POST['secretary_mobile']), trim($_POST['secretary_address']),
                trim($_POST['treasurer_name']), trim($_POST['treasurer_mobile']), trim($_POST['treasurer_address'])
            ]);
            
            $success = "আপনার দলের আবেদন সফলভাবে জমা হয়েছে! সুপার অ্যাডমিনের অনুমোদনের পর লগইন বিবরণ প্রদান করা হবে।";
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// 5.2 Super Admin: Approve Group Application
if ($action === 'approve_group' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_super_admin();
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $appId = (int)$_POST['application_id'];
        $tempPassword = $_POST['temp_password'] ?: '123456';
        $username = trim($_POST['username']);
        
        $stmt = $db->prepare("SELECT * FROM group_applications WHERE id = ? AND status = 'pending'");
        $stmt->execute([$appId]);
        $app = $stmt->fetch();
        
        if ($app) {
            $db->beginTransaction();
            try {
                // Create Group
                $stmtGroup = $db->prepare("INSERT INTO groups (name, code, address, village, block, district, state, pin_code, phone, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())");
                $code = 'SHG-' . str_pad((string)$appId, 4, '0', STR_PAD_LEFT);
                $stmtGroup->execute([
                    $app['group_name'], $code, $app['address'], $app['village'], $app['block'], $app['district'],
                    $app['state'], $app['pin_code'], $app['contact_mobile']
                ]);
                $newGroupId = (int)$db->lastInsertId();

                // Create Group Owner User
                $passHash = password_hash($tempPassword, PASSWORD_DEFAULT);
                $stmtUser = $db->prepare("INSERT INTO users (group_id, username, password_hash, full_name, email, phone, role, preferred_language, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, 'group_owner', ?, 'active', NOW())");
                $stmtUser->execute([
                    $newGroupId, $username, $passHash, $app['president_name'], $app['email'], $app['contact_mobile'], $app['preferred_language']
                ]);

                // Update Application
                $stmtUp = $db->prepare("UPDATE group_applications SET status = 'approved', reviewed_at = NOW(), rejection_reason = NULL WHERE id = ?");
                $stmtUp->execute([$appId]);

                $db->commit();
                audit_log($newGroupId, (int)$_SESSION['user_id'], 'GROUP_APPROVED', "Group {$app['group_name']} approved with owner {$username}");
                $success = "দল সফলভাবে অনুমোদিত হয়েছে! Owner Username: $username | Temporary Password: $tempPassword";
            } catch (Exception $ex) {
                $db->rollBack();
                $error = "অনুমোদন ব্যর্থ হয়েছে: " . $ex->getMessage();
            }
        }
    }
}

// 5.3 Member Management: Add & Edit
if (($action === 'add_member' || $action === 'edit_member') && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login();
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "CSRF Verification failed.";
    } else {
        $gid = current_group_id();
        $memNo = trim($_POST['member_no']);
        $fullName = trim($_POST['full_name']);
        $engName = trim($_POST['english_name'] ?? '');
        $guardian = trim($_POST['guardian_name'] ?? '');
        $gender = $_POST['gender'] ?? 'Female';
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $village = trim($_POST['village'] ?? '');
        $joinDate = $_POST['joining_date'] ?: date('Y-m-d');
        $status = $_POST['status'] ?? 'active';

        if ($action === 'add_member') {
            // Check unique member_no within group
            $chk = $db->prepare("SELECT id FROM members WHERE group_id = ? AND member_no = ?");
            $chk->execute([$gid, $memNo]);
            if ($chk->fetch()) {
                $error = "সদস্য নম্বর ($memNo) ইতিমধ্যে এই দলে বিদ্যমান!";
            } else {
                $ins = $db->prepare("INSERT INTO members (group_id, member_no, full_name, english_name, guardian_name, gender, phone, address, village, joining_date, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $ins->execute([$gid, $memNo, $fullName, $engName, $guardian, $gender, $phone, $address, $village, $joinDate, $status]);
                audit_log($gid, (int)$_SESSION['user_id'], 'MEMBER_ADDED', "Added member $memNo - $fullName");
                $success = "নতুন সদস্য সফলভাবে নথিভুক্ত হয়েছে।";
            }
        } else {
            $mid = (int)$_POST['member_id'];
            $upd = $db->prepare("UPDATE members SET full_name = ?, english_name = ?, guardian_name = ?, gender = ?, phone = ?, address = ?, village = ?, joining_date = ?, status = ? WHERE id = ? AND group_id = ?");
            $upd->execute([$fullName, $engName, $guardian, $gender, $phone, $address, $village, $joinDate, $status, $mid, $gid]);
            audit_log($gid, (int)$_SESSION['user_id'], 'MEMBER_UPDATED', "Updated member ID $mid");
            $success = "সদস্যের তথ্য সফলভাবে হালনাগাদ করা হয়েছে।";
        }
    }
}

// 5.4 Savings Management: Deposit & Withdrawal
if (($action === 'savings_deposit' || $action === 'savings_withdraw') && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login();
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "CSRF failed.";
    } else {
        $gid = current_group_id();
        $memId = (int)$_POST['member_id'];
        $amount = (float)$_POST['amount'];
        $txDate = $_POST['transaction_date'] ?: date('Y-m-d');
        $cbPage = trim($_POST['cashbook_page_no'] ?? '');
        $voucherNo = trim($_POST['voucher_no'] ?? '');
        $remarks = trim($_POST['remarks'] ?? '');

        if ($amount <= 0) {
            $error = "অনুগ্রহ করে একটি সঠিক টাকার পরিমাণ লিখুন।";
        } else {
            $db->beginTransaction();
            try {
                // Calculate previous balance
                $qBal = $db->prepare("SELECT COALESCE(SUM(deposit_amount - withdrawal_amount), 0) AS balance FROM savings_transactions WHERE member_id = ? AND group_id = ?");
                $qBal->execute([$memId, $gid]);
                $prevBalance = (float)$qBal->fetchColumn();

                $depAmt = ($action === 'savings_deposit') ? $amount : 0.00;
                $withAmt = ($action === 'savings_withdraw') ? $amount : 0.00;

                if ($action === 'savings_withdraw' && $withAmt > $prevBalance) {
                    throw new Exception("উত্তোলনের পরিমাণ বর্তমান জমার চেয়ে বেশি হতে পারে না! বর্তমান জমা: " . format_currency($prevBalance));
                }

                $newBalance = $prevBalance + $depAmt - $withAmt;

                $ins = $db->prepare("INSERT INTO savings_transactions (group_id, member_id, transaction_date, receipt_voucher_no, previous_balance, deposit_amount, withdrawal_amount, updated_balance, cashbook_page_no, payment_method, remarks, entered_by, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'cash', ?, ?, NOW())");
                $ins->execute([$gid, $memId, $txDate, $voucherNo, $prevBalance, $depAmt, $withAmt, $newBalance, $cbPage, $remarks, $_SESSION['user_id']]);

                // Insert into Cashbook automatically
                $cbType = ($action === 'savings_deposit') ? 'SAVINGS_DEPOSIT' : 'SAVINGS_WITHDRAWAL';
                $debit = ($action === 'savings_deposit') ? $amount : 0.00;
                $credit = ($action === 'savings_withdraw') ? $amount : 0.00;
                $cbDesc = ($action === 'savings_deposit') ? "সঞ্চয় জমা (সদস্য নং #$memId)" : "সঞ্চয় উত্তোলন (সদস্য নং #$memId)";

                $insCb = $db->prepare("INSERT INTO cashbook_entries (group_id, transaction_date, cashbook_page_no, reference_type, description, debit, credit, member_id, entered_by, remarks, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $insCb->execute([$gid, $txDate, $cbPage, $cbType, $cbDesc, $debit, $credit, $memId, $_SESSION['user_id'], $remarks]);

                $db->commit();
                audit_log($gid, (int)$_SESSION['user_id'], strtoupper($action), "Recorded $action of ₹$amount for member $memId");
                $success = "সঞ্চয় লেনদেন সফলভাবে সংরক্ষিত হয়েছে। বর্তমান ব্যালেন্স: " . format_currency($newBalance);
            } catch (Exception $e) {
                $db->rollBack();
                $error = "ব্যর্থ হয়েছে: " . $e->getMessage();
            }
        }
    }
}

// 5.5 Loan Disbursement & Application
if ($action === 'disburse_loan' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login();
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "CSRF failed.";
    } else {
        $gid = current_group_id();
        $memId = (int)$_POST['member_id'];
        $principal = (float)$_POST['principal_amount'];
        $annualRate = (float)$_POST['interest_rate'];
        $calcMethod = $_POST['interest_calculation_method'] ?? 'flat';
        $tenure = (int)$_POST['tenure_months'];
        $disbDate = $_POST['disbursement_date'] ?: date('Y-m-d');
        $cbPage = trim($_POST['cashbook_page_no'] ?? '');
        $remarks = trim($_POST['remarks'] ?? '');

        if ($principal <= 0 || $tenure <= 0) {
            $error = "সঠিক মূলধন এবং সময়কাল উল্লেখ করুন।";
        } else {
            $db->beginTransaction();
            try {
                // Calculate total interest
                // Flat: Interest = Principal * (Rate / 100) * (Months / 12)
                // Reducing: Standard monthly reducing EMI calculation
                if ($calcMethod === 'reducing') {
                    $monthlyRate = ($annualRate / 100) / 12;
                    if ($monthlyRate > 0) {
                        $emi = $principal * $monthlyRate * pow(1 + $monthlyRate, $tenure) / (pow(1 + $monthlyRate, $tenure) - 1);
                        $totalPayable = $emi * $tenure;
                        $totalInterest = $totalPayable - $principal;
                    } else {
                        $emi = $principal / $tenure;
                        $totalInterest = 0.00;
                    }
                } else {
                    $totalInterest = $principal * ($annualRate / 100.0) * ($tenure / 12.0);
                    $emi = ($principal + $totalInterest) / $tenure;
                }

                $loanNo = 'LN-' . date('Ymd') . '-' . rand(100, 999);
                $insLoan = $db->prepare("INSERT INTO loans (
                    group_id, member_id, loan_no, principal_amount, interest_rate, interest_calculation_method,
                    tenure_months, total_interest, outstanding_principal, outstanding_interest, installment_amount,
                    disbursement_date, status, approved_by, remarks, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'disbursed', ?, ?, NOW())");
                
                $insLoan->execute([
                    $gid, $memId, $loanNo, $principal, $annualRate, $calcMethod, $tenure,
                    $totalInterest, $principal, $totalInterest, $emi, $disbDate, $_SESSION['user_id'], $remarks
                ]);
                $loanId = (int)$db->lastInsertId();

                // Generate Installments Schedule
                $curDate = new DateTime($disbDate);
                $remP = $principal;
                for ($i = 1; $i <= $tenure; $i++) {
                    $curDate->modify('+1 month');
                    $dueDate = $curDate->format('Y-m-d');
                    
                    if ($calcMethod === 'reducing') {
                        $monthlyRate = ($annualRate / 100) / 12;
                        $instInterest = $remP * $monthlyRate;
                        $instPrincipal = $emi - $instInterest;
                        $remP -= $instPrincipal;
                    } else {
                        $instPrincipal = $principal / $tenure;
                        $instInterest = $totalInterest / $tenure;
                    }

                    $insInst = $db->prepare("INSERT INTO installments (loan_id, installment_no, due_date, principal_due, interest_due, total_due, status)
                        VALUES (?, ?, ?, ?, ?, ?, 'pending')");
                    $insInst->execute([$loanId, $i, $dueDate, $instPrincipal, $instInterest, ($instPrincipal + $instInterest)]);
                }

                // Add entry to cashbook (Disbursement is Credit / Outflow)
                $insCb = $db->prepare("INSERT INTO cashbook_entries (group_id, transaction_date, cashbook_page_no, reference_type, reference_id, description, debit, credit, member_id, entered_by, remarks, created_at)
                    VALUES (?, ?, ?, 'LOAN_DISBURSEMENT', ?, ?, 0.00, ?, ?, ?, ?, NOW())");
                $cbDesc = "ঋণ প্রদান (ঋণ নং $loanNo, সদস্য নং #$memId)";
                $insCb->execute([$gid, $disbDate, $cbPage, $loanId, $cbDesc, $principal, $memId, $_SESSION['user_id'], $remarks]);

                $db->commit();
                audit_log($gid, (int)$_SESSION['user_id'], 'LOAN_DISBURSED', "Disbursed loan $loanNo of ₹$principal to member $memId");
                $success = "ঋণ সফলভাবে বিতরণ করা হয়েছে! ঋণ নম্বর: $loanNo, মাসিক কিস্তি: " . format_currency($emi);
            } catch (Exception $e) {
                $db->rollBack();
                $error = "ঋণ প্রদানে ত্রুটি: " . $e->getMessage();
            }
        }
    }
}

// 5.6 Repayment Entry
if ($action === 'add_repayment' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login();
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "CSRF failed.";
    } else {
        $gid = current_group_id();
        $loanId = (int)$_POST['loan_id'];
        $payDate = $_POST['payment_date'] ?: date('Y-m-d');
        $prinPaid = (float)$_POST['principal_paid'];
        $intPaid = (float)$_POST['interest_paid'];
        $lateFee = (float)($_POST['late_fee'] ?? 0.00);
        $totalPaid = $prinPaid + $intPaid + $lateFee;
        $cbPage = trim($_POST['cashbook_page_no'] ?? '');
        $receiptNo = trim($_POST['receipt_no'] ?? '');
        $remarks = trim($_POST['remarks'] ?? '');

        if ($totalPaid <= 0) {
            $error = "পরিশোধের পরিমাণ অবশ্যই শূন্যের চেয়ে বেশি হতে হবে।";
        } else {
            $db->beginTransaction();
            try {
                $stmtL = $db->prepare("SELECT * FROM loans WHERE id = ? AND group_id = ? FOR UPDATE");
                $stmtL->execute([$loanId, $gid]);
                $loan = $stmtL->fetch();

                if (!$loan) {
                    throw new Exception("ঋণের রেকর্ড খুঁজে পাওয়া যায়নি!");
                }

                if ($prinPaid > (float)$loan['outstanding_principal']) {
                    throw new Exception("প্রদত্ত আসল বকেয়া আসলের (" . format_currency((float)$loan['outstanding_principal']) . ") চেয়ে বেশি হতে পারে না!");
                }

                $newOutPrin = max(0.00, (float)$loan['outstanding_principal'] - $prinPaid);
                $newOutInt = max(0.00, (float)$loan['outstanding_interest'] - $intPaid);
                $newStatus = ($newOutPrin <= 0.01 && $newOutInt <= 0.01) ? 'closed' : 'disbursed';

                // Insert repayment record
                $insRep = $db->prepare("INSERT INTO repayments (
                    loan_id, member_id, payment_date, principal_paid, interest_paid, late_fee, total_paid,
                    remaining_principal, remaining_interest, receipt_no, cashbook_page_no, entered_by, remarks, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $insRep->execute([
                    $loanId, $loan['member_id'], $payDate, $prinPaid, $intPaid, $lateFee, $totalPaid,
                    $newOutPrin, $newOutInt, $receiptNo, $cbPage, $_SESSION['user_id'], $remarks
                ]);

                // Update loan balance
                $upLoan = $db->prepare("UPDATE loans SET outstanding_principal = ?, outstanding_interest = ?, status = ? WHERE id = ?");
                $upLoan->execute([$newOutPrin, $newOutInt, $newStatus, $loanId]);

                // Update cashbook (Repayment is Debit / Inflow)
                $insCb = $db->prepare("INSERT INTO cashbook_entries (group_id, transaction_date, cashbook_page_no, reference_type, reference_id, description, debit, credit, member_id, entered_by, remarks, created_at)
                    VALUES (?, ?, ?, 'LOAN_REPAYMENT', ?, ?, ?, 0.00, ?, ?, ?, NOW())");
                $cbDesc = "কিস্তি আদায় (ঋণ নং {$loan['loan_no']}) - আসল: ₹$prinPaid, সুদ: ₹$intPaid";
                $insCb->execute([$gid, $payDate, $cbPage, $loanId, $cbDesc, $totalPaid, $loan['member_id'], $_SESSION['user_id'], $remarks]);

                $db->commit();
                audit_log($gid, (int)$_SESSION['user_id'], 'REPAYMENT_RECORDED', "Repayment of ₹$totalPaid for loan {$loan['loan_no']}");
                $success = "কিস্তি সফলভাবে আদায় হয়েছে! মোট প্রাপ্ত: " . format_currency($totalPaid) . " | অবশিষ্ট মোট বকেয়া: " . format_currency($newOutPrin + $newOutInt);
            } catch (Exception $e) {
                $db->rollBack();
                $error = "কিস্তি সংরক্ষণে সমস্যা: " . $e->getMessage();
            }
        }
    }
}

// 5.7 CSV Export Handling (with CSV Formula Injection Sanitization)
if ($action === 'export_csv') {
    require_login();
    $reportType = $_GET['report'] ?? 'savings';
    $gid = current_group_id();
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=SHG_' . $reportType . '_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    // UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    function sanitize_csv_cell($val): string {
        $str = (string)$val;
        if (isset($str[0]) && in_array($str[0], ['=', '+', '-', '@'], true)) {
            return "'" . $str;
        }
        return $str;
    }

    if ($reportType === 'savings') {
        fputcsv($output, ['Sl No', 'Date', 'Member No', 'Member Name', 'Voucher No', 'Prev Balance', 'Deposit', 'Withdrawal', 'Balance', 'CB Page', 'Remarks']);
        $stmt = $db->prepare("SELECT s.*, m.member_no, m.full_name FROM savings_transactions s JOIN members m ON s.member_id = m.id WHERE s.group_id = ? ORDER BY s.transaction_date DESC, s.id DESC");
        $stmt->execute([$gid]);
        $sl = 1;
        while ($r = $stmt->fetch()) {
            fputcsv($output, array_map('sanitize_csv_cell', [
                $sl++, $r['transaction_date'], $r['member_no'], $r['full_name'], $r['receipt_voucher_no'],
                $r['previous_balance'], $r['deposit_amount'], $r['withdrawal_amount'], $r['updated_balance'],
                $r['cashbook_page_no'], $r['remarks']
            ]));
        }
    } elseif ($reportType === 'loans') {
        fputcsv($output, ['Sl No', 'Disbursement Date', 'Loan No', 'Member No', 'Member Name', 'Principal', 'Interest Rate', 'Tenure', 'Total Interest', 'Out. Principal', 'Out. Interest', 'Total Outstanding', 'Status']);
        $stmt = $db->prepare("SELECT l.*, m.member_no, m.full_name FROM loans l JOIN members m ON l.member_id = m.id WHERE l.group_id = ? ORDER BY l.disbursement_date DESC");
        $stmt->execute([$gid]);
        $sl = 1;
        while ($r = $stmt->fetch()) {
            fputcsv($output, array_map('sanitize_csv_cell', [
                $sl++, $r['disbursement_date'], $r['loan_no'], $r['member_no'], $r['full_name'],
                $r['principal_amount'], $r['interest_rate'] . '%', $r['tenure_months'], $r['total_interest'],
                $r['outstanding_principal'], $r['outstanding_interest'],
                ((float)$r['outstanding_principal'] + (float)$r['outstanding_interest']), $r['status']
            ]));
        }
    }
    fclose($output);
    exit;
}

/* ==========================================================================
   6. UI LAYOUT & ROUTING RENDERER
   ========================================================================== */
$user = current_user();
$gid = current_group_id();
$currentGroup = null;
if ($user) {
    $qG = $db->prepare("SELECT * FROM groups WHERE id = ?");
    $qG->execute([$gid]);
    $currentGroup = $qG->fetch() ?: ['name' => 'পাটপিশু প্রগতি স্বনির্ভর দল', 'code' => 'DEFAULT'];
}

$isRTL = ($SUPPORTED_LANGUAGES[$CURRENT_LANG]['dir'] ?? 'ltr') === 'rtl';
?>
<!DOCTYPE html>
<html lang="<?= e($CURRENT_LANG) ?>" dir="<?= $isRTL ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(__t('app_name')) ?> - <?= e($currentGroup['name'] ?? 'SHG Portal') ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #0b3b60;
            --accent-blue: #1d5987;
            --light-bg: #f4f7fa;
            --border-color: #cbd5e1;
        }
        body {
            background-color: var(--light-bg);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #1e293b;
        }
        .navbar-custom {
            background-color: var(--primary-blue);
        }
        .navbar-custom .navbar-brand, .navbar-custom .nav-link {
            color: #ffffff !important;
        }
        .sidebar {
            background-color: #ffffff;
            border-right: 1px solid var(--border-color);
            min-height: calc(100vh - 56px);
        }
        .sidebar .nav-link {
            color: #334155;
            padding: 10px 16px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sidebar .nav-link.active, .sidebar .nav-link:hover {
            background-color: #e2e8f0;
            color: var(--primary-blue);
            border-left: 4px solid var(--primary-blue);
        }
        .metric-card {
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: transform 0.15s ease-in-out;
        }
        .metric-card:hover {
            transform: translateY(-2px);
        }
        .metric-card .card-title {
            font-size: 0.85rem;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
        }
        .metric-card .metric-value {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary-blue);
        }
        .table-responsive-card {
            background: #ffffff;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            padding: 20px;
        }

        /* Professional A4 Landscape Print Stylesheet */
        @media print {
            @page {
                size: A4 landscape;
                margin: 8mm 10mm 8mm 10mm;
            }
            body {
                background: #ffffff !important;
                color: #000000 !important;
                font-size: 10.5pt;
            }
            .no-print, .navbar, .sidebar, .btn, .alert, .modal, .pagination, .dataTables_filter {
                display: none !important;
            }
            .content-wrapper {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }
            .print-table {
                width: 100% !important;
                border-collapse: collapse !important;
                margin-top: 10px;
            }
            .print-table th, .print-table td {
                border: 1px solid #1d5987 !important;
                padding: 5px 6px !important;
                text-align: center;
                vertical-align: middle;
                font-size: 9.5pt;
            }
            .print-table th {
                background-color: #e9f2f9 !important;
                color: #0b3b60 !important;
                font-weight: bold;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .print-header {
                text-align: center;
                margin-bottom: 12px;
                border-bottom: 2px solid #0b3b60;
                padding-bottom: 6px;
            }
            .print-signatures {
                margin-top: 40px;
                display: flex;
                justify-content: space-between;
                width: 100%;
                page-break-inside: avoid;
            }
            .signature-box {
                text-align: center;
                border-top: 1px solid #000000;
                width: 200px;
                padding-top: 4px;
                font-size: 10pt;
                font-weight: bold;
            }
        }
    </style>
</head>
<body>

<!-- Header Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-custom no-print sticky-top shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.php">
            <i class="bi bi-bank2"></i>
            <span><?= e(__t('app_name')) ?></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="topNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if (is_logged_in()): ?>
                    <li class="nav-item">
                        <span class="nav-link badge bg-light text-dark my-1 px-3 py-2">
                            <i class="bi bi-people-fill"></i> <?= e($currentGroup['name'] ?? '') ?>
                        </span>
                    </li>
                <?php endif; ?>
            </ul>

            <!-- Language Selector Dropdown -->
            <div class="d-flex align-items-center gap-3">
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-light dropdown-toggle d-flex align-items-center gap-1" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-translate"></i>
                        <span><?= e($SUPPORTED_LANGUAGES[$CURRENT_LANG]['name']) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <?php foreach ($SUPPORTED_LANGUAGES as $code => $info): ?>
                            <li>
                                <a class="dropdown-item <?= ($CURRENT_LANG === $code) ? 'active' : '' ?>" href="index.php?lang=<?= $code ?>">
                                    <?= e($info['name']) ?> (<?= strtoupper($code) ?>)
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <?php if (is_logged_in()): ?>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light dropdown-toggle fw-semibold" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?= e($user['full_name']) ?> (<?= e(ucwords(str_replace('_', ' ', $user['role']))) ?>)
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li><a class="dropdown-item" href="index.php?page=settings"><i class="bi bi-gear"></i> <?= e(__t('settings')) ?></a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="index.php?action=logout"><i class="bi bi-box-arrow-right"></i> <?= e(__t('logout')) ?></a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="index.php?page=login" class="btn btn-sm btn-light fw-bold px-3"><?= e(__t('login')) ?></a>
                    <a href="index.php?page=register_group" class="btn btn-sm btn-warning fw-bold px-3"><?= e(__t('register_group')) ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <!-- Role-Aware Left Sidebar Menu -->
        <?php if (is_logged_in()): ?>
            <div class="col-md-3 col-lg-2 p-0 sidebar no-print">
                <div class="d-flex flex-column pt-3">
                    <a href="index.php?page=dashboard" class="nav-link <?= ($page === 'dashboard') ? 'active' : '' ?>">
                        <i class="bi bi-speedometer2"></i> <?= e(__t('dashboard')) ?>
                    </a>
                    
                    <?php if ($user['role'] === 'super_admin'): ?>
                        <div class="px-3 pt-3 pb-1 text-uppercase text-muted fw-bold" style="font-size:0.75rem;"><?= e(__t('super_admin')) ?></div>
                        <a href="index.php?page=admin_applications" class="nav-link <?= ($page === 'admin_applications') ? 'active' : '' ?>">
                            <i class="bi bi-file-earmark-check"></i> দল অনুমোদন আবেদন
                        </a>
                        <a href="index.php?page=admin_groups" class="nav-link <?= ($page === 'admin_groups') ? 'active' : '' ?>">
                            <i class="bi bi-diagram-3"></i> সকল দলের তালিকা
                        </a>
                        <a href="index.php?page=audit_logs" class="nav-link <?= ($page === 'audit_logs') ? 'active' : '' ?>">
                            <i class="bi bi-journal-text"></i> অডিট লগ
                        </a>
                    <?php endif; ?>

                    <div class="px-3 pt-3 pb-1 text-uppercase text-muted fw-bold" style="font-size:0.75rem;">সদস্য ও সঞ্চয়</div>
                    <a href="index.php?page=members" class="nav-link <?= ($page === 'members') ? 'active' : '' ?>">
                        <i class="bi bi-people"></i> <?= e(__t('members')) ?>
                    </a>
                    <a href="index.php?page=savings" class="nav-link <?= ($page === 'savings') ? 'active' : '' ?>">
                        <i class="bi bi-wallet2"></i> <?= e(__t('savings')) ?>
                    </a>
                    <a href="index.php?page=savings_register" class="nav-link <?= ($page === 'savings_register') ? 'active' : '' ?>">
                        <i class="bi bi-book"></i> <?= e(__t('savings_register')) ?>
                    </a>

                    <div class="px-3 pt-3 pb-1 text-uppercase text-muted fw-bold" style="font-size:0.75rem;">ঋণ ও কিস্তি</div>
                    <a href="index.php?page=loans" class="nav-link <?= ($page === 'loans') ? 'active' : '' ?>">
                        <i class="bi bi-cash-coin"></i> <?= e(__t('loan_disburse')) ?>
                    </a>
                    <a href="index.php?page=repayments" class="nav-link <?= ($page === 'repayments') ? 'active' : '' ?>">
                        <i class="bi bi-arrow-repeat"></i> <?= e(__t('repayment')) ?>
                    </a>
                    <a href="index.php?page=loan_register" class="nav-link <?= ($page === 'loan_register') ? 'active' : '' ?>">
                        <i class="bi bi-journal-bookmark"></i> <?= e(__t('loan_register')) ?>
                    </a>
                    <a href="index.php?page=overdue_loans" class="nav-link <?= ($page === 'overdue_loans') ? 'active' : '' ?>">
                        <i class="bi bi-exclamation-triangle"></i> <?= e(__t('overdue_loans')) ?>
                    </a>

                    <div class="px-3 pt-3 pb-1 text-uppercase text-muted fw-bold" style="font-size:0.75rem;">হিসাব ও রিপোর্ট</div>
                    <a href="index.php?page=cashbook" class="nav-link <?= ($page === 'cashbook') ? 'active' : '' ?>">
                        <i class="bi bi-journal-medical"></i> <?= e(__t('cashbook')) ?>
                    </a>
                    <a href="index.php?page=reports" class="nav-link <?= ($page === 'reports') ? 'active' : '' ?>">
                        <i class="bi bi-file-earmark-spreadsheet"></i> <?= e(__t('reports')) ?>
                    </a>
                    <a href="index.php?page=settings" class="nav-link <?= ($page === 'settings') ? 'active' : '' ?>">
                        <i class="bi bi-sliders"></i> <?= e(__t('settings')) ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Main Workspace Area -->
        <div class="col <?= is_logged_in() ? 'col-md-9 col-lg-10' : 'col-12' ?> p-4 content-wrapper">
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show no-print" role="alert">
                    <i class="bi bi-exclamation-circle-fill me-2"></i> <?= e($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> <?= e($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php
            /* ==========================================================
               ROUTING: PAGE CONTROLLERS
               ========================================================== */
            
            // PAGE: LOGIN
            if ($page === 'login'):
            ?>
                <div class="row justify-content-center pt-5">
                    <div class="col-md-5">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-primary text-white text-center py-3" style="background-color: var(--primary-blue) !important;">
                                <h4 class="mb-0 fw-bold"><?= e(__t('login')) ?></h4>
                                <small class="text-light"><?= e(__t('app_name')) ?></small>
                            </div>
                            <div class="card-body p-4">
                                <form method="POST" action="index.php?action=login_submit">
                                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">ইউজারনেম (Username)</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                                            <input type="text" name="username" class="form-control" required placeholder="Enter username">
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">পাসওয়ার্ড (Password)</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-key"></i></span>
                                            <input type="password" name="password" class="form-control" required placeholder="••••••••">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn w-100 fw-bold py-2 text-white" style="background-color: var(--primary-blue);">
                                        <?= e(__t('login')) ?>
                                    </button>
                                </form>
                            </div>
                            <div class="card-footer bg-light text-center py-3">
                                <span class="text-muted">আপনার দল নিবন্ধিত নয়?</span> 
                                <a href="index.php?page=register_group" class="fw-bold text-decoration-none">নতুন দলের আবেদন করুন</a>
                            </div>
                        </div>
                    </div>
                </div>

            <?php
            // PAGE: PUBLIC GROUP REGISTRATION
            elseif ($page === 'register_group'):
            ?>
                <div class="row justify-content-center">
                    <div class="col-lg-9">
                        <div class="card shadow-sm border-0 mb-5">
                            <div class="card-header text-white p-3" style="background-color: var(--primary-blue);">
                                <h4 class="mb-0 fw-bold"><i class="bi bi-file-earmark-plus"></i> <?= e(__t('register_group')) ?></h4>
                                <small>স্বনির্ভর দলের অনলাইন নিবন্ধন ও অনুমোদন আবেদন পত্র</small>
                            </div>
                            <div class="card-body p-4">
                                <form method="POST" action="index.php?action=register_group_submit">
                                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                    
                                    <h5 class="text-primary border-bottom pb-2 mb-3">১. দলের প্রাথমিক বিবরণ (Group Details)</h5>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">দলের নাম (Group Name) *</label>
                                            <input type="text" name="group_name" class="form-control" placeholder="उदा: পাটপিশু প্রগতি স্বনির্ভর দল" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">গঠন তারিখ (Formation Date)</label>
                                            <input type="date" name="formation_date" class="form-control">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">মোট সদস্য সংখ্যা *</label>
                                            <input type="number" name="member_count" class="form-control" value="10" min="5" max="30" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">ঠিকানা (Full Address) *</label>
                                            <input type="text" name="address" class="form-control" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">গ্রাম / এলাকা *</label>
                                            <input type="text" name="village" class="form-control" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">গ্রাম পঞ্চায়েত (GP)</label>
                                            <input type="text" name="gram_panchayat" class="form-control">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">ব্লক (Block) *</label>
                                            <input type="text" name="block" class="form-control" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">জেলা (District) *</label>
                                            <input type="text" name="district" class="form-control" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">রাজ্য (State) *</label>
                                            <input type="text" name="state" class="form-control" value="West Bengal" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">পিন কোড (PIN) *</label>
                                            <input type="text" name="pin_code" class="form-control" required pattern="[0-9]{6}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">যোগাযোগের মোবাইল নম্বর *</label>
                                            <input type="tel" name="contact_mobile" class="form-control" required pattern="[0-9]{10}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">ইমেল (ঐচ্ছিক)</label>
                                            <input type="email" name="email" class="form-control">
                                        </div>
                                    </div>

                                    <h5 class="text-primary border-bottom pb-2 mb-3">২. প্রধান তিন কর্মকর্তার বিবরণ (Office Bearers)</h5>
                                    
                                    <!-- President -->
                                    <div class="card bg-light mb-3 border-0">
                                        <div class="card-body">
                                            <h6 class="fw-bold text-dark"><i class="bi bi-person-badge"></i> সভাপতি (President)</h6>
                                            <div class="row g-2">
                                                <div class="col-md-4">
                                                    <input type="text" name="president_name" class="form-control" placeholder="সভাপতির নাম *" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <input type="tel" name="president_mobile" class="form-control" placeholder="মোবাইল নম্বর *" required pattern="[0-9]{10}">
                                                </div>
                                                <div class="col-md-4">
                                                    <input type="text" name="president_address" class="form-control" placeholder="সম্পূর্ণ ঠিকানা *" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Secretary -->
                                    <div class="card bg-light mb-3 border-0">
                                        <div class="card-body">
                                            <h6 class="fw-bold text-dark"><i class="bi bi-person-badge"></i> সম্পাদক (Secretary)</h6>
                                            <div class="row g-2">
                                                <div class="col-md-4">
                                                    <input type="text" name="secretary_name" class="form-control" placeholder="সম্পাদকের নাম *" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <input type="tel" name="secretary_mobile" class="form-control" placeholder="মোবাইল নম্বর *" required pattern="[0-9]{10}">
                                                </div>
                                                <div class="col-md-4">
                                                    <input type="text" name="secretary_address" class="form-control" placeholder="সম্পূর্ণ ঠিকানা *" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Treasurer -->
                                    <div class="card bg-light mb-4 border-0">
                                        <div class="card-body">
                                            <h6 class="fw-bold text-dark"><i class="bi bi-person-badge"></i> কোষাধ্যক্ষ (Treasurer)</h6>
                                            <div class="row g-2">
                                                <div class="col-md-4">
                                                    <input type="text" name="treasurer_name" class="form-control" placeholder="কোষাধ্যক্ষের নাম *" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <input type="tel" name="treasurer_mobile" class="form-control" placeholder="মোবাইল নম্বর *" required pattern="[0-9]{10}">
                                                </div>
                                                <div class="col-md-4">
                                                    <input type="text" name="treasurer_address" class="form-control" placeholder="সম্পূর্ণ ঠিকানা *" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">অতিরিক্ত মন্তব্য / নোট (Optional)</label>
                                        <textarea name="notes" class="form-control" rows="2"></textarea>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <a href="index.php?page=login" class="text-decoration-none">« ফিরে যান</a>
                                        <button type="submit" class="btn btn-primary px-4 py-2 fw-bold" style="background-color: var(--primary-blue);">
                                            <?= e(__t('submit')) ?> আবেদন পাঠান
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            <?php
            // PAGE: DASHBOARD (Metrics Calculated From Real Transactions)
            elseif ($page === 'dashboard'):
                require_login();
                
                // Aggregate metrics from Database
                $stmtM = $db->prepare("SELECT COUNT(*) AS total_m, SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) AS active_m FROM members WHERE group_id = ?");
                $stmtM->execute([$gid]);
                $mStats = $stmtM->fetch();

                // Savings
                $stmtS = $db->prepare("SELECT COALESCE(SUM(deposit_amount - withdrawal_amount), 0) AS total_savings FROM savings_transactions WHERE group_id = ?");
                $stmtS->execute([$gid]);
                $totSavings = (float)$stmtS->fetchColumn();

                // Loans
                $stmtL = $db->prepare("SELECT 
                    COALESCE(SUM(principal_amount), 0) AS total_disbursed,
                    COALESCE(SUM(outstanding_principal), 0) AS out_prin,
                    COALESCE(SUM(outstanding_interest), 0) AS out_int
                    FROM loans WHERE group_id = ? AND status != 'cancelled'");
                $stmtL->execute([$gid]);
                $lStats = $stmtL->fetch();

                // Total Recovered
                $stmtR = $db->prepare("SELECT COALESCE(SUM(total_paid), 0) FROM repayments r JOIN loans l ON r.loan_id = l.id WHERE l.group_id = ?");
                $stmtR->execute([$gid]);
                $totRecovered = (float)$stmtR->fetchColumn();

                // Today's collections
                $today = date('Y-m-d');
                $stmtTS = $db->prepare("SELECT COALESCE(SUM(deposit_amount), 0) FROM savings_transactions WHERE group_id = ? AND transaction_date = ?");
                $stmtTS->execute([$gid, $today]);
                $todaySavings = (float)$stmtTS->fetchColumn();

                $stmtTR = $db->prepare("SELECT COALESCE(SUM(r.total_paid), 0) FROM repayments r JOIN loans l ON r.loan_id = l.id WHERE l.group_id = ? AND r.payment_date = ?");
                $stmtTR->execute([$gid, $today]);
                $todayRepay = (float)$stmtTR->fetchColumn();

                // Overdue Calculation (Installments due before today and not fully paid)
                $stmtOvd = $db->prepare("SELECT COALESCE(SUM(i.total_due - i.paid_amount), 0) 
                    FROM installments i 
                    JOIN loans l ON i.loan_id = l.id 
                    WHERE l.group_id = ? AND i.due_date < ? AND i.status != 'paid'");
                $stmtOvd->execute([$gid, $today]);
                $overdueAmt = (float)$stmtOvd->fetchColumn();

                // Recent 5 transactions
                $stmtRecent = $db->prepare("SELECT * FROM cashbook_entries WHERE group_id = ? ORDER BY transaction_date DESC, id DESC LIMIT 5");
                $stmtRecent->execute([$gid]);
                $recentTx = $stmtRecent->fetchAll();
            ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="fw-bold mb-1" style="color: var(--primary-blue);"><?= e(__t('dashboard')) ?></h3>
                        <span class="text-muted"><i class="bi bi-calendar-event"></i> <?= date('d M Y, l') ?> | <?= e($currentGroup['name']) ?></span>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="index.php?page=savings" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-circle"></i> <?= e(__t('savings')) ?></a>
                        <a href="index.php?page=loans" class="btn btn-sm btn-primary" style="background-color: var(--primary-blue);"><i class="bi bi-cash"></i> <?= e(__t('loan_apply')) ?></a>
                    </div>
                </div>

                <!-- Metrics Grid -->
                <div class="row g-3 mb-4">
                    <div class="col-sm-6 col-md-4 col-xl-3">
                        <div class="card metric-card p-3 border-start border-4 border-primary">
                            <div class="card-title"><?= e(__t('total_members')) ?></div>
                            <div class="metric-value"><?= (int)$mStats['total_m'] ?> <span class="fs-6 text-muted">(সক্রিয়: <?= (int)$mStats['active_m'] ?>)</span></div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4 col-xl-3">
                        <div class="card metric-card p-3 border-start border-4 border-success">
                            <div class="card-title"><?= e(__t('total_savings')) ?></div>
                            <div class="metric-value text-success"><?= format_currency($totSavings) ?></div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4 col-xl-3">
                        <div class="card metric-card p-3 border-start border-4 border-info">
                            <div class="card-title"><?= e(__t('total_loan_disbursed')) ?></div>
                            <div class="metric-value"><?= format_currency((float)$lStats['total_disbursed']) ?></div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4 col-xl-3">
                        <div class="card metric-card p-3 border-start border-4 border-secondary">
                            <div class="card-title"><?= e(__t('total_loan_recovered')) ?></div>
                            <div class="metric-value"><?= format_currency($totRecovered) ?></div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4 col-xl-3">
                        <div class="card metric-card p-3 border-start border-4 border-warning">
                            <div class="card-title"><?= e(__t('outstanding_principal')) ?></div>
                            <div class="metric-value"><?= format_currency((float)$lStats['out_prin']) ?></div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4 col-xl-3">
                        <div class="card metric-card p-3 border-start border-4 border-warning">
                            <div class="card-title"><?= e(__t('outstanding_interest')) ?></div>
                            <div class="metric-value"><?= format_currency((float)$lStats['out_int']) ?></div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4 col-xl-3">
                        <div class="card metric-card p-3 border-start border-4 border-dark">
                            <div class="card-title"><?= e(__t('total_outstanding')) ?></div>
                            <div class="metric-value"><?= format_currency((float)$lStats['out_prin'] + (float)$lStats['out_int']) ?></div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4 col-xl-3">
                        <div class="card metric-card p-3 border-start border-4 border-danger">
                            <div class="card-title"><?= e(__t('overdue_amount')) ?></div>
                            <div class="metric-value text-danger"><?= format_currency($overdueAmt) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Daily Summary & Recent Activity -->
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header bg-white fw-bold py-3"><i class="bi bi-clock-history"></i> আজকের সংক্ষেপ (Today's Summary)</div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between py-2 border-bottom">
                                    <span><?= e(__t('today_savings')) ?></span>
                                    <strong class="text-success"><?= format_currency($todaySavings) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between py-2 border-bottom">
                                    <span><?= e(__t('today_repayment')) ?></span>
                                    <strong class="text-primary"><?= format_currency($todayRepay) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between py-2 mt-2">
                                    <span class="fw-bold">আজকের মোট জমা</span>
                                    <strong class="text-dark fs-5"><?= format_currency($todaySavings + $todayRepay) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between">
                                <span><i class="bi bi-list-check"></i> <?= e(__t('recent_transactions')) ?></span>
                                <a href="index.php?page=cashbook" class="btn btn-sm btn-link text-decoration-none">সম্পূর্ণ ক্যাশবুক »</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th><?= e(__t('date')) ?></th>
                                                <th>বিবরণ (Description)</th>
                                                <th>জমা (Debit)</th>
                                                <th>খরচ (Credit)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($recentTx)): ?>
                                                <tr><td colspan="4" class="text-center text-muted py-3">কোন সাম্প্রতিক লেনদেন নেই।</td></tr>
                                            <?php else: foreach ($recentTx as $tx): ?>
                                                <tr>
                                                    <td><?= format_date($tx['transaction_date']) ?></td>
                                                    <td><?= e($tx['description']) ?></td>
                                                    <td class="text-success fw-bold"><?= $tx['debit'] > 0 ? format_currency((float)$tx['debit']) : '-' ?></td>
                                                    <td class="text-danger fw-bold"><?= $tx['credit'] > 0 ? format_currency((float)$tx['credit']) : '-' ?></td>
                                                </tr>
                                            <?php endforeach; endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php
            // PAGE: MEMBER MANAGEMENT
            elseif ($page === 'members'):
                require_login();
                $search = trim($_GET['search'] ?? '');
                $sql = "SELECT m.*, 
                    COALESCE((SELECT SUM(deposit_amount - withdrawal_amount) FROM savings_transactions WHERE member_id = m.id), 0) AS savings_balance,
                    COALESCE((SELECT SUM(outstanding_principal) FROM loans WHERE member_id = m.id AND status = 'disbursed'), 0) AS loan_balance
                    FROM members m WHERE m.group_id = ?";
                $params = [$gid];
                if ($search !== '') {
                    $sql .= " AND (m.full_name LIKE ? OR m.member_no LIKE ? OR m.phone LIKE ?)";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                }
                $sql .= " ORDER BY m.member_no ASC";
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $members = $stmt->fetchAll();
            ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold mb-0" style="color: var(--primary-blue);"><?= e(__t('members')) ?></h3>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddMember" style="background-color: var(--primary-blue);">
                        <i class="bi bi-person-plus-fill"></i> <?= e(__t('new_member')) ?>
                    </button>
                </div>

                <div class="table-responsive-card">
                    <form method="GET" class="row g-2 mb-3">
                        <input type="hidden" name="page" value="members">
                        <div class="col-md-4">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="সদস্যের নাম, নম্বর বা ফোন..." value="<?= e($search) ?>">
                                <button type="submit" class="btn btn-secondary"><i class="bi bi-search"></i> <?= e(__t('search')) ?></button>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>নম্বর</th>
                                    <th>নাম</th>
                                    <th>অভিভাবকের নাম</th>
                                    <th>মোবাইল</th>
                                    <th>যোগদান তারিখ</th>
                                    <th>বর্তমান সঞ্চয়</th>
                                    <th>বকেয়া ঋণ</th>
                                    <th>অবস্থা</th>
                                    <th>কার্যক্রম</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($members)): ?>
                                    <tr><td colspan="9" class="text-center py-4 text-muted">কোন সদস্যের তথ্য পাওয়া যায়নি।</td></tr>
                                <?php else: foreach ($members as $m): ?>
                                    <tr>
                                        <td><strong><?= e($m['member_no']) ?></strong></td>
                                        <td><?= e($m['full_name']) ?></td>
                                        <td><?= e($m['guardian_name'] ?: '-') ?></td>
                                        <td><?= e($m['phone'] ?: '-') ?></td>
                                        <td><?= format_date($m['joining_date']) ?></td>
                                        <td class="text-success fw-bold"><?= format_currency((float)$m['savings_balance']) ?></td>
                                        <td class="text-danger fw-bold"><?= format_currency((float)$m['loan_balance']) ?></td>
                                        <td><span class="badge bg-<?= ($m['status'] === 'active') ? 'success' : 'secondary' ?>"><?= e($m['status']) ?></span></td>
                                        <td>
                                            <a href="index.php?page=member_statement&id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-info" title="<?= e(__t('view_statement')) ?>">
                                                <i class="bi bi-card-text"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Add Member Modal -->
                <div class="modal fade" id="modalAddMember" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <form method="POST" action="index.php?action=add_member">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                <div class="modal-header bg-primary text-white" style="background-color: var(--primary-blue) !important;">
                                    <h5 class="modal-title fw-bold"><i class="bi bi-person-plus"></i> <?= e(__t('new_member')) ?></h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">সদস্য নম্বর (Member No) *</label>
                                            <input type="text" name="member_no" class="form-control" required placeholder="उदा: 01">
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label fw-semibold">পূর্ণ নাম (Full Name) *</label>
                                            <input type="text" name="full_name" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">পিতা / স্বামীর নাম (Guardian Name)</label>
                                            <input type="text" name="guardian_name" class="form-control">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">মোবাইল নম্বর (Phone)</label>
                                            <input type="tel" name="phone" class="form-control" pattern="[0-9]{10}">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">যোগদানের তারিখ</label>
                                            <input type="date" name="joining_date" class="form-control" value="<?= date('Y-m-d') ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">লিঙ্গ (Gender)</label>
                                            <select name="gender" class="form-select">
                                                <option value="Female">মহিলা (Female)</option>
                                                <option value="Male">পুরুষ (Male)</option>
                                                <option value="Other">অন্যান্য</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">অবস্থা (Status)</label>
                                            <select name="status" class="form-select">
                                                <option value="active">সক্রিয় (Active)</option>
                                                <option value="inactive">নিষ্ক্রিয় (Inactive)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">গ্রাম / এলাকা</label>
                                            <input type="text" name="village" class="form-control">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">সম্পূর্ণ ঠিকানা</label>
                                            <input type="text" name="address" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বাতিল</button>
                                    <button type="submit" class="btn btn-primary" style="background-color: var(--primary-blue);"><?= e(__t('save')) ?></button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            <?php
            // PAGE: SAVINGS DEPOSIT & WITHDRAWAL
            elseif ($page === 'savings'):
                require_login();
                $stmtM = $db->prepare("SELECT id, member_no, full_name FROM members WHERE group_id = ? AND status = 'active' ORDER BY member_no ASC");
                $stmtM->execute([$gid]);
                $members = $stmtM->fetchAll();

                $stmtTx = $db->prepare("SELECT s.*, m.member_no, m.full_name FROM savings_transactions s JOIN members m ON s.member_id = m.id WHERE s.group_id = ? ORDER BY s.transaction_date DESC, s.id DESC LIMIT 20");
                $stmtTx->execute([$gid]);
                $txs = $stmtTx->fetchAll();
            ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold mb-0" style="color: var(--primary-blue);"><?= e(__t('savings')) ?></h3>
                    <div class="d-flex gap-2">
                        <a href="index.php?page=savings_register" class="btn btn-outline-primary"><i class="bi bi-book"></i> <?= e(__t('savings_register')) ?></a>
                        <a href="index.php?action=export_csv&report=savings" class="btn btn-success"><i class="bi bi-download"></i> <?= e(__t('export_csv')) ?></a>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-md-5">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white fw-bold py-3"><i class="bi bi-plus-circle-dotted"></i> সঞ্চয় জমা বা উত্তোলন ফরম</div>
                            <div class="card-body p-4">
                                <form method="POST" action="index.php?action=savings_deposit" id="savingsForm">
                                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">লেনদেনের ধরন (Transaction Type) *</label>
                                        <select class="form-select" id="savings_type_select" onchange="document.getElementById('savingsForm').action = 'index.php?action=' + this.value;">
                                            <option value="savings_deposit">সঞ্চয় জমা (Deposit)</option>
                                            <option value="savings_withdraw">সঞ্চয় উত্তোলন (Withdrawal)</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">সদস্য নির্বাচন করুন *</label>
                                        <select name="member_id" class="form-select" required>
                                            <option value="">-- সদস্য বেছে নিন --</option>
                                            <?php foreach ($members as $m): ?>
                                                <option value="<?= $m['id'] ?>"><?= e($m['member_no']) ?> - <?= e($m['full_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="row g-2 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">তারিখ *</label>
                                            <input type="date" name="transaction_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">পরিমাণ (₹) *</label>
                                            <input type="number" step="0.01" name="amount" class="form-control" required placeholder="0.00">
                                        </div>
                                    </div>

                                    <div class="row g-2 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">ক্যাশ বইয়ের পৃষ্ঠা নং</label>
                                            <input type="text" name="cashbook_page_no" class="form-control" placeholder="उदा: 14">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">রসিদ / ভাউচার নং</label>
                                            <input type="text" name="voucher_no" class="form-control">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">মন্তব্য</label>
                                        <input type="text" name="remarks" class="form-control" placeholder="ঐচ্ছিক">
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold" style="background-color: var(--primary-blue);">
                                        সংরক্ষণ করুন (Post Transaction)
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-7">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white fw-bold py-3"><i class="bi bi-clock-history"></i> সাম্প্রতিক সঞ্চয় লেনদেন</div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>তারিখ</th>
                                                <th>সদস্য</th>
                                                <th>জমা (₹)</th>
                                                <th>উত্তোলন (₹)</th>
                                                <th>ব্যালেন্স (₹)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($txs)): ?>
                                                <tr><td colspan="5" class="text-center py-4 text-muted">কোন সঞ্চয় লেনদেন রেকর্ড করা হয়নি।</td></tr>
                                            <?php else: foreach ($txs as $t): ?>
                                                <tr>
                                                    <td><?= format_date($t['transaction_date']) ?></td>
                                                    <td><strong><?= e($t['member_no']) ?></strong> - <?= e($t['full_name']) ?></td>
                                                    <td class="text-success fw-bold"><?= $t['deposit_amount'] > 0 ? format_currency((float)$t['deposit_amount']) : '-' ?></td>
                                                    <td class="text-danger fw-bold"><?= $t['withdrawal_amount'] > 0 ? format_currency((float)$t['withdrawal_amount']) : '-' ?></td>
                                                    <td class="fw-bold"><?= format_currency((float)$t['updated_balance']) ?></td>
                                                </tr>
                                            <?php endforeach; endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php
            // PAGE: PRINTABLE SAVINGS REGISTER (10 Columns, Live Data & Blank 20-row format)
            elseif ($page === 'savings_register'):
                require_login();
                $blankMode = isset($_GET['blank']);
                $stmt = $db->prepare("SELECT s.*, m.member_no, m.full_name 
                    FROM savings_transactions s 
                    JOIN members m ON s.member_id = m.id 
                    WHERE s.group_id = ? 
                    ORDER BY s.transaction_date ASC, s.id ASC");
                $stmt->execute([$gid]);
                $records = $stmt->fetchAll();
            ?>
                <div class="d-flex justify-content-between align-items-center mb-3 no-print">
                    <div>
                        <h4 class="fw-bold mb-0"><?= e($currentGroup['name']) ?> - সঞ্চয় খাতা</h4>
                        <span class="text-muted">A4 Landscape ফরম্যাটে প্রিন্ট করার উপযোগী</span>
                    </div>
                    <div class="d-flex gap-2">
                        <?php if ($blankMode): ?>
                            <a href="index.php?page=savings_register" class="btn btn-outline-secondary">আসল তথ্য প্রদর্শন করুন</a>
                        <?php else: ?>
                            <a href="index.php?page=savings_register&blank=1" class="btn btn-outline-secondary"><?= e(__t('blank_register')) ?></a>
                        <?php endif; ?>
                        <button onclick="window.print()" class="btn btn-primary" style="background-color: var(--primary-blue);">
                            <i class="bi bi-printer-fill"></i> <?= e(__t('print')) ?> (A4 Landscape)
                        </button>
                    </div>
                </div>

                <div class="print-header">
                    <h3 class="fw-bold mb-1"><?= e($currentGroup['name']) ?></h3>
                    <h5 class="mb-1">সঞ্চয় খাতা (SAVINGS REGISTER)</h5>
                    <p class="mb-0 text-muted" style="font-size: 9pt;">গ্রাম: <?= e($currentGroup['village'] ?? '') ?>, ব্লক: <?= e($currentGroup['block'] ?? '') ?>, জেলা: <?= e($currentGroup['district'] ?? '') ?> | প্রিন্ট তারিখ: <?= date('d-m-Y') ?></p>
                </div>

                <table class="print-table">
                    <thead>
                        <tr>
                            <th style="width: 5%;">১. ক্র. নং</th>
                            <th style="width: 10%;">২. তারিখ</th>
                            <th style="width: 18%;">৩. সদস্যের নাম</th>
                            <th style="width: 8%;">৪. সদস্য নং</th>
                            <th style="width: 10%;">৫. আগের জমা (₹)</th>
                            <th style="width: 10%;">৬. বর্তমান জমা (₹)</th>
                            <th style="width: 11%;">৭. মোট সঞ্চয় (₹)</th>
                            <th style="width: 8%;">৮. ক্যাশ বই পৃ.</th>
                            <th style="width: 10%;">৯. মন্তব্য</th>
                            <th style="width: 10%;">১০. সদস্যের স্বাক্ষর</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($blankMode): ?>
                            <?php for ($i = 1; $i <= 20; $i++): ?>
                                <tr style="height: 38px;">
                                    <td><?= $i ?></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            <?php endfor; ?>
                        <?php else: ?>
                            <?php if (empty($records)): ?>
                                <tr><td colspan="10" class="text-center py-4">কোন সঞ্চয় রেকর্ড পাওয়া যায়নি।</td></tr>
                            <?php else: $sl = 1; foreach ($records as $r): ?>
                                <tr>
                                    <td><?= $sl++ ?></td>
                                    <td><?= format_date($r['transaction_date']) ?></td>
                                    <td style="text-align: left; padding-left: 8px;"><?= e($r['full_name']) ?></td>
                                    <td><?= e($r['member_no']) ?></td>
                                    <td><?= number_format((float)$r['previous_balance'], 2) ?></td>
                                    <td class="fw-bold"><?= number_format((float)$r['deposit_amount'], 2) ?></td>
                                    <td class="fw-bold"><?= number_format((float)$r['updated_balance'], 2) ?></td>
                                    <td><?= e($r['cashbook_page_no']) ?></td>
                                    <td><?= e($r['remarks']) ?></td>
                                    <td></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="print-signatures">
                    <div class="signature-box"><?= e(__t('president')) ?></div>
                    <div class="signature-box"><?= e(__t('secretary')) ?></div>
                    <div class="signature-box"><?= e(__t('treasurer')) ?></div>
                </div>

            <?php
            // PAGE: LOANS DISBURSEMENT & LIST
            elseif ($page === 'loans'):
                require_login();
                $stmtM = $db->prepare("SELECT id, member_no, full_name FROM members WHERE group_id = ? AND status = 'active' ORDER BY member_no ASC");
                $stmtM->execute([$gid]);
                $members = $stmtM->fetchAll();

                $stmtL = $db->prepare("SELECT l.*, m.member_no, m.full_name 
                    FROM loans l 
                    JOIN members m ON l.member_id = m.id 
                    WHERE l.group_id = ? 
                    ORDER BY l.disbursement_date DESC");
                $stmtL->execute([$gid]);
                $loans = $stmtL->fetchAll();
            ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold mb-0" style="color: var(--primary-blue);"><?= e(__t('loan_disburse')) ?></h3>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNewLoan" style="background-color: var(--primary-blue);">
                            <i class="bi bi-cash"></i> নতুন ঋণ বিতরণ
                        </button>
                        <a href="index.php?page=loan_register" class="btn btn-outline-primary"><i class="bi bi-journal-bookmark"></i> <?= e(__t('loan_register')) ?></a>
                    </div>
                </div>

                <div class="table-responsive-card">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ঋণ নং</th>
                                    <th>সদস্য</th>
                                    <th>বিতরণ তারিখ</th>
                                    <th>মূলধন (₹)</th>
                                    <th>সুদের হার ও পদ্ধতি</th>
                                    <th>মেয়াদ</th>
                                    <th>কিস্তির পরিমাণ</th>
                                    <th>বকেয়া আসল</th>
                                    <th>বকেয়া সুদ</th>
                                    <th>অবস্থা</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($loans)): ?>
                                    <tr><td colspan="10" class="text-center py-4 text-muted">কোন ঋণ রেকর্ড পাওয়া যায়নি।</td></tr>
                                <?php else: foreach ($loans as $l): ?>
                                    <tr>
                                        <td><strong><?= e($l['loan_no']) ?></strong></td>
                                        <td><?= e($l['member_no']) ?> - <?= e($l['full_name']) ?></td>
                                        <td><?= format_date($l['disbursement_date']) ?></td>
                                        <td class="fw-bold"><?= format_currency((float)$l['principal_amount']) ?></td>
                                        <td><?= $l['interest_rate'] ?>% (<?= e($l['interest_calculation_method']) ?>)</td>
                                        <td><?= $l['tenure_months'] ?> মাস</td>
                                        <td class="text-primary fw-bold"><?= format_currency((float)$l['installment_amount']) ?></td>
                                        <td class="text-danger fw-bold"><?= format_currency((float)$l['outstanding_principal']) ?></td>
                                        <td class="text-danger"><?= format_currency((float)$l['outstanding_interest']) ?></td>
                                        <td><span class="badge bg-<?= ($l['status'] === 'closed') ? 'secondary' : 'success' ?>"><?= e($l['status']) ?></span></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Modal New Loan -->
                <div class="modal fade" id="modalNewLoan" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <form method="POST" action="index.php?action=disburse_loan">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                <div class="modal-header bg-primary text-white" style="background-color: var(--primary-blue) !important;">
                                    <h5 class="modal-title fw-bold"><i class="bi bi-cash-stack"></i> নতুন ঋণ বিতরণ ফরম</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">ঋণগ্রহীতা সদস্য *</label>
                                            <select name="member_id" class="form-select" required>
                                                <option value="">-- সদস্য বেছে নিন --</option>
                                                <?php foreach ($members as $m): ?>
                                                    <option value="<?= $m['id'] ?>"><?= e($m['member_no']) ?> - <?= e($m['full_name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">বিতরণ তারিখ *</label>
                                            <input type="date" name="disbursement_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">ঋণের মূলধন (₹) *</label>
                                            <input type="number" step="0.01" name="principal_amount" class="form-control" required placeholder="5000.00">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">বার্ষিক সুদের হার (%) *</label>
                                            <input type="number" step="0.01" name="interest_rate" class="form-control" value="12.00" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">মেয়াদ (মাস) *</label>
                                            <input type="number" name="tenure_months" class="form-control" value="10" min="1" max="60" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold"><?= e(__t('interest_method')) ?> *</label>
                                            <select name="interest_calculation_method" class="form-select">
                                                <option value="flat"><?= e(__t('flat_rate')) ?></option>
                                                <option value="reducing"><?= e(__t('reducing_rate')) ?></option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">ক্যাশ বই পৃষ্ঠা নং</label>
                                            <input type="text" name="cashbook_page_no" class="form-control" placeholder="उदा: 22">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">মন্তব্য</label>
                                            <input type="text" name="remarks" class="form-control" placeholder="ঋণের উদ্দেশ্য ইত্যাদি">
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বাতিল</button>
                                    <button type="submit" class="btn btn-primary" style="background-color: var(--primary-blue);">ঋণ বিতরণ নিশ্চিত করুন</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            <?php
            // PAGE: REPAYMENTS
            elseif ($page === 'repayments'):
                require_login();
                $stmtL = $db->prepare("SELECT l.id, l.loan_no, l.outstanding_principal, l.outstanding_interest, m.member_no, m.full_name 
                    FROM loans l 
                    JOIN members m ON l.member_id = m.id 
                    WHERE l.group_id = ? AND l.status = 'disbursed' 
                    ORDER BY l.id DESC");
                $stmtL->execute([$gid]);
                $activeLoans = $stmtL->fetchAll();

                $stmtR = $db->prepare("SELECT r.*, l.loan_no, m.member_no, m.full_name 
                    FROM repayments r 
                    JOIN loans l ON r.loan_id = l.id 
                    JOIN members m ON r.member_id = m.id 
                    WHERE l.group_id = ? 
                    ORDER BY r.payment_date DESC, r.id DESC LIMIT 20");
                $stmtR->execute([$gid]);
                $repayments = $stmtR->fetchAll();
            ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold mb-0" style="color: var(--primary-blue);"><?= e(__t('repayment')) ?></h3>
                    <a href="index.php?page=loan_register" class="btn btn-outline-primary"><i class="bi bi-journal-bookmark"></i> <?= e(__t('loan_register')) ?></a>
                </div>

                <div class="row g-4">
                    <div class="col-md-5">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white fw-bold py-3"><i class="bi bi-wallet-fill"></i> কিস্তি পরিশোধ ফরম</div>
                            <div class="card-body p-4">
                                <form method="POST" action="index.php?action=add_repayment">
                                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">সক্রিয় ঋণ নির্বাচন করুন *</label>
                                        <select name="loan_id" class="form-select" required>
                                            <option value="">-- ঋণ বাছাই করুন --</option>
                                            <?php foreach ($activeLoans as $al): ?>
                                                <option value="<?= $al['id'] ?>">
                                                    <?= e($al['loan_no']) ?> - <?= e($al['member_no']) ?> <?= e($al['full_name']) ?> (বকেয়া: <?= format_currency((float)$al['outstanding_principal'] + (float)$al['outstanding_interest']) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">পরিশোধের তারিখ *</label>
                                        <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                    </div>

                                    <div class="row g-2 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">প্রদত্ত আসল (₹) *</label>
                                            <input type="number" step="0.01" name="principal_paid" class="form-control" required placeholder="0.00">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">প্রদত্ত সুদ (₹) *</label>
                                            <input type="number" step="0.01" name="interest_paid" class="form-control" required placeholder="0.00">
                                        </div>
                                    </div>

                                    <div class="row g-2 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">ক্যাশ বইয়ের পৃষ্ঠা নং</label>
                                            <input type="text" name="cashbook_page_no" class="form-control" placeholder="उदा: 24">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">রসিদ নম্বর</label>
                                            <input type="text" name="receipt_no" class="form-control">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">মন্তব্য</label>
                                        <input type="text" name="remarks" class="form-control">
                                    </div>

                                    <button type="submit" class="btn btn-success w-100 py-2 fw-bold">
                                        কিস্তি জমা নিশ্চিত করুন (Collect Repayment)
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-7">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white fw-bold py-3"><i class="bi bi-clock-history"></i> সাম্প্রতিক কিস্তি আদায়ের বিবরণ</div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>তারিখ</th>
                                                <th>সদস্য ও ঋণ</th>
                                                <th>আসল (₹)</th>
                                                <th>সুদ (₹)</th>
                                                <th>মোট আদায় (₹)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($repayments)): ?>
                                                <tr><td colspan="5" class="text-center py-4 text-muted">কোন কিস্তি আদায়ের রেকর্ড নেই।</td></tr>
                                            <?php else: foreach ($repayments as $r): ?>
                                                <tr>
                                                    <td><?= format_date($r['payment_date']) ?></td>
                                                    <td><?= e($r['member_no']) ?> - <?= e($r['full_name']) ?><br><small class="text-muted"><?= e($r['loan_no']) ?></small></td>
                                                    <td><?= format_currency((float)$r['principal_paid']) ?></td>
                                                    <td><?= format_currency((float)$r['interest_paid']) ?></td>
                                                    <td class="fw-bold text-success"><?= format_currency((float)$r['total_paid']) ?></td>
                                                </tr>
                                            <?php endforeach; endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php
            // PAGE: PRINTABLE LOAN REGISTER (Exact Grouped Headers Matching Specifications)
            elseif ($page === 'loan_register'):
                require_login();
                $blankMode = isset($_GET['blank']);
                $stmt = $db->prepare("SELECT l.*, m.member_no, m.full_name,
                    COALESCE((SELECT SUM(principal_paid) FROM repayments WHERE loan_id = l.id), 0) AS total_prin_paid,
                    COALESCE((SELECT SUM(interest_paid) FROM repayments WHERE loan_id = l.id), 0) AS total_int_paid
                    FROM loans l 
                    JOIN members m ON l.member_id = m.id 
                    WHERE l.group_id = ? 
                    ORDER BY l.disbursement_date ASC");
                $stmt->execute([$gid]);
                $loanReg = $stmt->fetchAll();
            ?>
                <div class="d-flex justify-content-between align-items-center mb-3 no-print">
                    <div>
                        <h4 class="fw-bold mb-0">পাটপিশু প্রগতি স্বনির্ভর দলের ঋণ খাতা</h4>
                        <span class="text-muted">A4 Landscape অফিসিয়াল প্রিন্ট ফরম্যাট</span>
                    </div>
                    <div class="d-flex gap-2">
                        <?php if ($blankMode): ?>
                            <a href="index.php?page=loan_register" class="btn btn-outline-secondary">আসল তথ্য প্রদর্শন করুন</a>
                        <?php else: ?>
                            <a href="index.php?page=loan_register&blank=1" class="btn btn-outline-secondary"><?= e(__t('blank_register')) ?></a>
                        <?php endif; ?>
                        <button onclick="window.print()" class="btn btn-primary" style="background-color: var(--primary-blue);">
                            <i class="bi bi-printer-fill"></i> <?= e(__t('print')) ?> (A4 Landscape)
                        </button>
                    </div>
                </div>

                <div class="print-header">
                    <h3 class="fw-bold mb-1">পাটপিশু প্রগতি স্বনির্ভর দলের ঋণ খাতা</h3>
                    <p class="mb-0 text-muted" style="font-size: 9pt;">গ্রাম: পাটপিশু | তারিখ: <?= date('d-m-Y') ?> | পৃষ্ঠা নং: ১</p>
                </div>

                <table class="print-table">
                    <thead>
                        <!-- Grouped Headers Row 1 -->
                        <tr>
                            <th rowspan="2" style="width: 3%;">ক্রমিক সংখ্যা</th>
                            <th rowspan="2" style="width: 7%;">তারিখ</th>
                            <th rowspan="2" style="width: 5%;">কিস্তির সংখ্যা</th>
                            <th rowspan="2" style="width: 5%;">ক্যাশ বইয়ের পৃষ্ঠার নং</th>
                            <th colspan="3" style="width: 16%;">ঋণ প্রদান</th>
                            <th colspan="3" style="width: 16%;">পরিশোধযোগ্য ঋণ (কিস্তিতে)</th>
                            <th colspan="3" style="width: 16%;">ঋণ পরিশোধ (কিস্তিতে)</th>
                            <th colspan="3" style="width: 16%;">মোট বাকি থাকা ঋণ (ব্যালেন্স)</th>
                            <th colspan="3" style="width: 16%;">খেলাপি ঋণ</th>
                        </tr>
                        <!-- Grouped Subheaders Row 2 -->
                        <tr>
                            <!-- ঋণ প্রদান -->
                            <th>আগের বকেয়া (₹)</th>
                            <th>আজকের ঋণ (₹)</th>
                            <th>মোট ঋণ (₹)</th>
                            <!-- পরিশোধযোগ্য ঋণ -->
                            <th>আসল (₹)</th>
                            <th>সুদ (₹)</th>
                            <th>মোট (₹)</th>
                            <!-- ঋণ পরিশোধ -->
                            <th>আসল (₹)</th>
                            <th>সুদ (₹)</th>
                            <th>মোট (₹)</th>
                            <!-- মোট বাকি থাকা ঋণ -->
                            <th>আসল (₹)</th>
                            <th>সুদ (₹)</th>
                            <th>মোট (₹)</th>
                            <!-- খেলাপি ঋণ -->
                            <th>আসল (₹)</th>
                            <th>সুদ (₹)</th>
                            <th>মোট (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($blankMode): ?>
                            <?php for ($i = 1; $i <= 20; $i++): ?>
                                <tr style="height: 36px;">
                                    <td><?= $i ?></td>
                                    <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                                </tr>
                            <?php endfor; ?>
                        <?php else: ?>
                            <?php if (empty($loanReg)): ?>
                                <tr><td colspan="19" class="text-center py-4">ঋণ খাতার জন্য কোন রেকর্ড পাওয়া যায়নি।</td></tr>
                            <?php else: $sl = 1; foreach ($loanReg as $lr): 
                                $prin = (float)$lr['principal_amount'];
                                $totInt = (float)$lr['total_interest'];
                                $totPayable = $prin + $totInt;
                                $pPaid = (float)$lr['total_prin_paid'];
                                $iPaid = (float)$lr['total_int_paid'];
                                $tPaid = $pPaid + $iPaid;
                                $outP = (float)$lr['outstanding_principal'];
                                $outI = (float)$lr['outstanding_interest'];
                                $outTotal = $outP + $outI;
                            ?>
                                <tr>
                                    <td><?= $sl++ ?></td>
                                    <td><?= format_date($lr['disbursement_date']) ?></td>
                                    <td><?= (int)$lr['tenure_months'] ?></td>
                                    <td>-</td>
                                    <!-- ঋণ প্রদান -->
                                    <td>0.00</td>
                                    <td><?= number_format($prin, 2) ?></td>
                                    <td class="fw-bold"><?= number_format($prin, 2) ?></td>
                                    <!-- পরিশোধযোগ্য ঋণ -->
                                    <td><?= number_format($prin, 2) ?></td>
                                    <td><?= number_format($totInt, 2) ?></td>
                                    <td class="fw-bold"><?= number_format($totPayable, 2) ?></td>
                                    <!-- ঋণ পরিশোধ -->
                                    <td><?= number_format($pPaid, 2) ?></td>
                                    <td><?= number_format($iPaid, 2) ?></td>
                                    <td class="fw-bold"><?= number_format($tPaid, 2) ?></td>
                                    <!-- মোট বাকি থাকা ঋণ -->
                                    <td><?= number_format($outP, 2) ?></td>
                                    <td><?= number_format($outI, 2) ?></td>
                                    <td class="fw-bold"><?= number_format($outTotal, 2) ?></td>
                                    <!-- খেলাপি ঋণ (Default if overdue) -->
                                    <td>0.00</td>
                                    <td>0.00</td>
                                    <td>0.00</td>
                                </tr>
                            <?php endforeach; endif; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="print-signatures">
                    <div class="signature-box"><?= e(__t('president')) ?></div>
                    <div class="signature-box"><?= e(__t('secretary')) ?></div>
                    <div class="signature-box"><?= e(__t('treasurer')) ?></div>
                </div>

            <?php
            // PAGE: CASHBOOK
            elseif ($page === 'cashbook'):
                require_login();
                $stmtCb = $db->prepare("SELECT * FROM cashbook_entries WHERE group_id = ? ORDER BY transaction_date DESC, id DESC");
                $stmtCb->execute([$gid]);
                $cbs = $stmtCb->fetchAll();
            ?>
                <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                    <h3 class="fw-bold mb-0" style="color: var(--primary-blue);"><?= e(__t('cashbook')) ?></h3>
                    <button onclick="window.print()" class="btn btn-outline-primary"><i class="bi bi-printer"></i> <?= e(__t('print')) ?></button>
                </div>

                <div class="table-responsive-card">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>তারিখ</th>
                                    <th>পৃষ্ঠা নং</th>
                                    <th>ধরন (Reference)</th>
                                    <th>বিবরণ (Description)</th>
                                    <th>জমা / প্রাপ্তি (Debit ₹)</th>
                                    <th>খরচ / প্রদান (Credit ₹)</th>
                                    <th>মন্তব্য</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($cbs)): ?>
                                    <tr><td colspan="7" class="text-center py-4 text-muted">ক্যাশবুকে কোন এন্ট্রি নেই।</td></tr>
                                <?php else: foreach ($cbs as $c): ?>
                                    <tr>
                                        <td><?= format_date($c['transaction_date']) ?></td>
                                        <td><?= e($c['cashbook_page_no'] ?: '-') ?></td>
                                        <td><span class="badge bg-secondary"><?= e($c['reference_type']) ?></span></td>
                                        <td><?= e($c['description']) ?></td>
                                        <td class="text-success fw-bold"><?= $c['debit'] > 0 ? format_currency((float)$c['debit']) : '-' ?></td>
                                        <td class="text-danger fw-bold"><?= $c['credit'] > 0 ? format_currency((float)$c['credit']) : '-' ?></td>
                                        <td><?= e($c['remarks']) ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php
            // PAGE: SUPER ADMIN - GROUP APPLICATIONS
            elseif ($page === 'admin_applications'):
                require_super_admin();
                $stmtApps = $db->query("SELECT * FROM group_applications ORDER BY id DESC");
                $apps = $stmtApps->fetchAll();
            ?>
                <h3 class="fw-bold mb-4" style="color: var(--primary-blue);">অনলাইন দল নিবন্ধন আবেদন পর্যালোচনা</h3>

                <div class="table-responsive-card">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>তারিখ</th>
                                    <th>দলের নাম</th>
                                    <th>গ্রাম ও ব্লক</th>
                                    <th>সভাপতি</th>
                                    <th>মোবাইল</th>
                                    <th>সদস্য</th>
                                    <th>অবস্থা</th>
                                    <th>কার্যক্রম</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($apps)): ?>
                                    <tr><td colspan="8" class="text-center py-4 text-muted">কোন আবেদন জমা পড়েনি।</td></tr>
                                <?php else: foreach ($apps as $a): ?>
                                    <tr>
                                        <td><?= format_date($a['created_at']) ?></td>
                                        <td><strong><?= e($a['group_name']) ?></strong></td>
                                        <td><?= e($a['village']) ?>, <?= e($a['block']) ?></td>
                                        <td><?= e($a['president_name']) ?></td>
                                        <td><?= e($a['contact_mobile']) ?></td>
                                        <td><?= (int)$a['member_count'] ?></td>
                                        <td>
                                            <span class="badge bg-<?= ($a['status'] === 'approved') ? 'success' : (($a['status'] === 'pending') ? 'warning text-dark' : 'danger') ?>">
                                                <?= e($a['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($a['status'] === 'pending'): ?>
                                                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalApprove<?= $a['id'] ?>">
                                                    অনুমোদন করুন
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">নিষ্পন্ন</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <!-- Approval Modal -->
                                    <?php if ($a['status'] === 'pending'): ?>
                                        <div class="modal fade" id="modalApprove<?= $a['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" action="index.php?action=approve_group">
                                                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                                        <input type="hidden" name="application_id" value="<?= $a['id'] ?>">
                                                        <div class="modal-header bg-success text-white">
                                                            <h5 class="modal-title fw-bold">দল অনুমোদন ও অ্যাকাউন্ট তৈরি</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>দল: <strong><?= e($a['group_name']) ?></strong></p>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">গ্রুপ ওনার ইউজারনেম (Login Username) *</label>
                                                                <input type="text" name="username" class="form-control" value="owner_<?= $a['id'] ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">অস্থায়ী পাসওয়ার্ড (Temporary Password) *</label>
                                                                <input type="text" name="temp_password" class="form-control" value="SHG@<?= rand(1000, 9999) ?>" required>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বাতিল</button>
                                                            <button type="submit" class="btn btn-success">অনুমোদন নিশ্চিত করুন</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php
            // PAGE: SETTINGS & PASSWORD CHANGE
            elseif ($page === 'settings'):
                require_login();
            ?>
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white fw-bold py-3"><i class="bi bi-person-lock"></i> ব্যবহারকারী সেটিংস ও নিরাপত্তা</div>
                            <div class="card-body p-4">
                                <h6 class="fw-bold border-bottom pb-2 mb-3">পাসওয়ার্ড পরিবর্তন</h6>
                                <form method="POST" action="index.php?action=change_password">
                                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">বর্তমান পাসওয়ার্ড</label>
                                        <input type="password" name="current_password" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">নতুন পাসওয়ার্ড</label>
                                        <input type="password" name="new_password" class="form-control" required minlength="6">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">পাসওয়ার্ড নিশ্চিত করুন</label>
                                        <input type="password" name="confirm_password" class="form-control" required minlength="6">
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100" style="background-color: var(--primary-blue);">পাসওয়ার্ড হালনাগাদ করুন</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <div class="text-center py-5">
                    <h2 class="text-muted">পৃষ্ঠাটি খুঁজে পাওয়া যায়নি (404 Not Found)</h2>
                    <a href="index.php" class="btn btn-primary mt-3">হোম পেজে ফিরে যান</a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
