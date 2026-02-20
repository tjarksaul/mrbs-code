<?php
declare(strict_types=1);
namespace MRBS;

use PHPMailer\PHPMailer\PHPMailer;
use function MRBS\_tbl;
use function MRBS\generate_token;

require "defaultincludes.inc";

// Security check - must not be logged in
if (isset($user))
{
  header("Location: index.php");
  exit();
}

/**
 * Validate registration input
 * @param array $data Form data
 * @return string|null Error message or null if valid
 */
function validate_registration(array $data) : ?string
{
  global $pwd_policy;

  // Check required fields
  $required_fields = ['username', 'display_name', 'email', 'organization', 'role', 'password', 'password_confirm'];
  foreach ($required_fields as $field)
  {
    if (empty($data[$field]))
    {
      return 'missing_fields';
    }
  }

  // Validate username format (alphanumeric and underscore only)
  if (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username']))
  {
    return 'invalid_username';
  }

  // Validate username length
  if (strlen($data['username']) > 30)
  {
    return 'username_too_long';
  }

  // Validate email format
  if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL))
  {
    return 'invalid_email';
  }

  // Check if passwords match
  if ($data['password'] !== $data['password_confirm'])
  {
    return 'passwords_not_match';
  }

  // Validate password policy
  if (isset($pwd_policy))
  {
    $password = $data['password'];
    
    if (isset($pwd_policy['length']) && strlen($password) < $pwd_policy['length'])
    {
      return 'password_policy';
    }
    
    if (isset($pwd_policy['alpha']) && $pwd_policy['alpha'] > 0)
    {
      if (preg_match_all('/[a-zA-Z]/', $password) < $pwd_policy['alpha'])
      {
        return 'password_policy';
      }
    }
    
    if (isset($pwd_policy['lower']) && $pwd_policy['lower'] > 0)
    {
      if (preg_match_all('/[a-z]/', $password) < $pwd_policy['lower'])
      {
        return 'password_policy';
      }
    }
    
    if (isset($pwd_policy['upper']) && $pwd_policy['upper'] > 0)
    {
      if (preg_match_all('/[A-Z]/', $password) < $pwd_policy['upper'])
      {
        return 'password_policy';
      }
    }
    
    if (isset($pwd_policy['numeric']) && $pwd_policy['numeric'] > 0)
    {
      if (preg_match_all('/[0-9]/', $password) < $pwd_policy['numeric'])
      {
        return 'password_policy';
      }
    }
    
    if (isset($pwd_policy['special']) && $pwd_policy['special'] > 0)
    {
      if (preg_match_all('/[^a-zA-Z0-9]/', $password) < $pwd_policy['special'])
      {
        return 'password_policy';
      }
    }
  }

  // Check if username already exists in users table
  $sql = "SELECT COUNT(*) FROM " . _tbl('users') . " WHERE name = ?";
  $count = db()->query1($sql, array($data['username']));
  if ($count > 0)
  {
    return 'username_exists';
  }

  // Check if email already exists in users table
  $sql = "SELECT COUNT(*) FROM " . _tbl('users') . " WHERE email = ?";
  $count = db()->query1($sql, array($data['email']));
  if ($count > 0)
  {
    return 'email_exists';
  }

  // Check if username already exists in registration_requests table
  $sql = "SELECT COUNT(*) FROM " . _tbl('registration_requests') . " WHERE username = ?";
  $count = db()->query1($sql, array($data['username']));
  if ($count > 0)
  {
    return 'username_pending';
  }

  // Check if email already exists in registration_requests table
  $sql = "SELECT COUNT(*) FROM " . _tbl('registration_requests') . " WHERE email = ?";
  $count = db()->query1($sql, array($data['email']));
  if ($count > 0)
  {
    return 'email_pending';
  }

  return null;
}

/**
 * Create registration request
 * @param array $data Form data
 * @return bool Success status
 */
function create_registration_request(array $data) : bool
{
  // Generate tokens (32 bytes = 64 hex characters for VARCHAR(64) fields)
  $email_verification_token = generate_token(32);
  $approval_token = generate_token(32);
  
  // Hash password
  $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
  
  // Prepare SQL
  $sql = "INSERT INTO " . _tbl('registration_requests') . "
          (username, display_name, email, organization, role, password_hash,
           email_verification_token, approval_token, created_at)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
  
  $params = array(
    mb_strtolower($data['username']),
    $data['display_name'],
    mb_strtolower($data['email']),
    $data['organization'],
    $data['role'],
    $password_hash,
    $email_verification_token,
    $approval_token,
    time()
  );
  
  try
  {
    db()->command($sql, $params);
    
    // Send verification email
    send_verification_email($data['email'], $data['display_name'], $email_verification_token);
    
    return true;
  }
  catch (\Exception $e)
  {
    error_log("Registration request creation failed: " . $e->getMessage());
    return false;
  }
}

/**
 * Send verification email
 * @param string $email Recipient email
 * @param string $name Recipient name
 * @param string $token Verification token
 * @return void
 */
function send_verification_email(string $email, string $name, string $token) : void
{
  global $mail_settings, $mrbs_admin_email;
  
  $verification_url = url_base() . "/verify_email.php?token=" . urlencode($token);
  
  $subject = get_vocab('register_verify_subject');
  
  $body = get_vocab('register_verify_body', $name, $verification_url);
  
  // Use MRBS mail system
  $mail = new PHPMailer();
  $mail->CharSet = 'UTF-8';
  
  // Add Auto-Submitted header for automated emails
  $mail->addCustomHeader('Auto-Submitted', 'auto-generated');
  
  // Set From address
  $from = $mail_settings['from'] ?? $mrbs_admin_email ?? '';
  if (!empty($from))
  {
    $from_email = parse_email($from);
    $mail->setFrom($from_email['email'], $from_email['name'] ?? '');
  }
  
  $mail->addAddress($email, $name);
  $mail->Subject = $subject;
  $mail->Body = $body;
  
  try
  {
    $mail->send();
  }
  catch (\Exception $e)
  {
    error_log("Failed to send verification email: " . $mail->ErrorInfo);
  }
}

// Main handler logic
if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
  header("Location: register.php");
  exit();
}

// Get form data
$data = array(
  'username'          => get_form_var('username', 'string'),
  'display_name'      => get_form_var('display_name', 'string'),
  'email'             => get_form_var('email', 'string'),
  'organization'      => get_form_var('organization', 'string'),
  'role'              => get_form_var('role', 'string'),
  'password'          => get_form_var('password', 'string'),
  'password_confirm'  => get_form_var('password_confirm', 'string')
);

// Validate input
$error = validate_registration($data);
if ($error !== null)
{
  header("Location: register.php?error=" . urlencode($error));
  exit();
}

// Create registration request
if (create_registration_request($data))
{
  // Redirect to success page
  header("Location: register_success.php");
  exit();
}
else
{
  // Redirect back with error
  header("Location: register.php?error=server_error");
  exit();
}
