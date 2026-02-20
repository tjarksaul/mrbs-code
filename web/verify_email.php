<?php
declare(strict_types=1);
namespace MRBS;

use PHPMailer\PHPMailer\PHPMailer;
use function MRBS\_tbl;

require "defaultincludes.inc";

/**
 * Send admin notification email
 * @param array $registration Registration request data
 * @return void
 */
function send_admin_notification(array $registration) : void
{
  global $mail_settings, $registration_approval_email;
  
  $approval_url = url_base() . "/approve_registration.php?token=" . urlencode($registration['approval_token']);
  
  $subject = get_vocab('register_admin_notification_subject');
  
  $body = get_vocab('register_admin_notification_body',
    $registration['display_name'],
    $registration['username'],
    $registration['email'],
    $registration['organization'],
    $registration['role'],
    $approval_url
  );
  
  // Use MRBS mail system
  $mail = new PHPMailer();
  $mail->CharSet = 'UTF-8';
  
  // Add Auto-Submitted header for automated emails
  $mail->addCustomHeader('Auto-Submitted', 'auto-generated');
  
  // Set From address from MRBS mail settings
  if (isset($mail_settings['from']) && !empty($mail_settings['from']))
  {
    $from_addresses = parse_addresses($mail_settings['from']);
    if (!empty($from_addresses))
    {
      $mail->setFrom($from_addresses[0]['address'], $from_addresses[0]['name']);
    }
  }
  
  // Determine admin email
  $admin_email = $registration_approval_email ?? $mail_settings['recipients'] ?? '';
  if (empty($admin_email))
  {
    error_log("No admin email configured for registration approvals");
    return;
  }
  
  $to_addresses = parse_addresses($admin_email);
  if (!empty($to_addresses))
  {
    $mail->addAddress($to_addresses[0]['address'], $to_addresses[0]['name']);
  }
  $mail->Subject = $subject;
  $mail->Body = $body;
  
  try
  {
    $mail->send();
  }
  catch (\Exception $e)
  {
    error_log("Failed to send admin notification: " . $mail->ErrorInfo);
  }
}

// Main handler logic
$token = get_form_var('token', 'string');

if (empty($token))
{
  header("Location: index.php");
  exit();
}

// Look up the registration request
$sql = "SELECT * FROM " . _tbl('registration_requests') . "
        WHERE email_verification_token = ? AND email_verified = 0";
$result = db()->query($sql, array($token));

if ($result->count() == 0)
{
  // Token not found or already verified
  print_header();
  echo '<div class="container">';
  echo '<h2>' . get_vocab('register_verify_invalid_title') . '</h2>';
  echo '<p>' . get_vocab('register_verify_invalid_message') . '</p>';
  echo '<p><a href="index.php">' . get_vocab('back_to_home') . '</a></p>';
  echo '</div>';
  print_footer();
  exit();
}

$registration = $result->next_row_keyed();

// Update the registration request as email verified
$sql = "UPDATE " . _tbl('registration_requests') . "
        SET email_verified = 1, email_verified_at = ?, updated_at = ?
        WHERE id = ?";
db()->command($sql, array(time(), time(), $registration['id']));

// Send notification to admin
send_admin_notification($registration);

// Display success page
print_header();
?>

<div class="container">
  <h2><?php echo get_vocab('register_verify_success_title') ?></h2>
  
  <p><?php echo get_vocab('register_verify_success_message') ?></p>
  
  <p><?php echo get_vocab('register_approval_pending') ?></p>
  
  <p>
    <a href="index.php"><?php echo get_vocab('back_to_home') ?></a>
  </p>
</div>

<?php
print_footer();
