<?php
declare(strict_types=1);
namespace MRBS;

use PHPMailer\PHPMailer\PHPMailer;
use function MRBS\_tbl;

require "defaultincludes.inc";

/**
 * Send approval confirmation email to user
 * @param array $registration Registration request data
 * @return void
 */
function send_approval_email(array $registration) : void
{
  global $mail_settings;
  
  $login_url = url_base() . "/index.php";
  
  $subject = get_vocab('register_approval_subject');
  
  $body = get_vocab('register_approval_body',
    $registration['display_name'],
    $registration['username'],
    $login_url
  );
  
  // Use MRBS mail system
  $mail = new PHPMailer();
  $mail->CharSet = 'UTF-8';
  
  if (isset($mail_settings['from']) && $mail_settings['from'] != '')
  {
    $from_email = parse_email($mail_settings['from']);
    $mail->setFrom($from_email['email'], $from_email['name'] ?? '');
  }
  
  $mail->addAddress($registration['email'], $registration['display_name']);
  $mail->Subject = $subject;
  $mail->Body = $body;
  
  try
  {
    $mail->send();
  }
  catch (\Exception $e)
  {
    error_log("Failed to send approval email: " . $mail->ErrorInfo);
  }
}

/**
 * Create user account from approved registration
 * @param array $registration Registration request data
 * @return bool Success status
 */
function create_user_account(array $registration) : bool
{
  // Insert into users table
  $sql = "INSERT INTO " . _tbl('users') . "
          (name, display_name, email, password_hash, level, last_login)
          VALUES (?, ?, ?, ?, ?, 0)";
  
  $params = array(
    $registration['username'],
    $registration['display_name'],
    $registration['email'],
    $registration['password_hash'],
    1  // Level 1 = regular user
  );
  
  try
  {
    db()->command($sql, $params);
    return true;
  }
  catch (\Exception $e)
  {
    error_log("Failed to create user account: " . $e->getMessage());
    return false;
  }
}

// Main handler logic
$token = get_form_var('token', 'string');
$action = get_form_var('action', 'string');

if (empty($token))
{
  header("Location: index.php");
  exit();
}

// Look up the registration request
$sql = "SELECT * FROM " . _tbl('registration_requests') . "
        WHERE approval_token = ? AND email_verified = 1 AND approved = 0 AND rejected = 0";
$result = db()->query($sql, array($token));

if ($result->count() == 0)
{
  // Token not found, not verified, or already processed
  print_header();
  echo '<div class="container">';
  echo '<h2>' . get_vocab('register_approval_invalid_title') . '</h2>';
  echo '<p>' . get_vocab('register_approval_invalid_message') . '</p>';
  echo '<p><a href="index.php">' . get_vocab('back_to_home') . '</a></p>';
  echo '</div>';
  print_footer();
  exit();
}

$registration = $result->next_row_keyed();

// Handle POST action (approve or reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($action))
{
  if ($action === 'approve')
  {
    // Create the user account
    if (create_user_account($registration))
    {
      // Update registration request as approved
      $sql = "UPDATE " . _tbl('registration_requests') . "
              SET approved = 1, approved_at = ?, approved_by = ?, updated_at = ?
              WHERE id = ?";
      
      // Track who approved it - use logged in admin if available, otherwise 'email_approval'
      $approved_by = isset($user->username) ? $user->username : 'email_approval';
      db()->command($sql, array(time(), $approved_by, time(), $registration['id']));
      
      // Send approval email to user
      send_approval_email($registration);
      
      // Show success
      print_header();
      echo '<div class="container">';
      echo '<h2>' . get_vocab('register_approval_success_title') . '</h2>';
      echo '<p>' . get_vocab('register_approval_success_message', $registration['display_name']) . '</p>';
      echo '<p><a href="index.php">' . get_vocab('back_to_home') . '</a></p>';
      echo '</div>';
      print_footer();
      exit();
    }
    else
    {
      print_header();
      echo '<div class="container">';
      echo '<h2>' . get_vocab('error') . '</h2>';
      echo '<p>' . get_vocab('register_approval_error') . '</p>';
      echo '</div>';
      print_footer();
      exit();
    }
  }
  elseif ($action === 'reject')
  {
    // Update registration request as rejected
    $sql = "UPDATE " . _tbl('registration_requests') . "
            SET rejected = 1, rejected_at = ?, rejected_by = ?, updated_at = ?
            WHERE id = ?";
    
    // Track who rejected it - use logged in admin if available, otherwise 'email_rejection'
    $rejected_by = isset($user->username) ? $user->username : 'email_rejection';
    db()->command($sql, array(time(), $rejected_by, time(), $registration['id']));
    
    // Show rejection confirmation
    print_header();
    echo '<div class="container">';
    echo '<h2>' . get_vocab('register_rejection_title') . '</h2>';
    echo '<p>' . get_vocab('register_rejection_message', $registration['display_name']) . '</p>';
    echo '<p><a href="index.php">' . get_vocab('back_to_home') . '</a></p>';
    echo '</div>';
    print_footer();
    exit();
  }
}

// Display approval form
print_header();
?>

<div class="container">
  <h2><?php echo get_vocab('register_approval_title') ?></h2>
  
  <p><?php echo get_vocab('register_approval_instructions') ?></p>
  
  <div class="registration_details">
    <table>
      <tr>
        <th><?php echo get_vocab('display_name') ?>:</th>
        <td><?php echo htmlspecialchars($registration['display_name']) ?></td>
      </tr>
      <tr>
        <th><?php echo get_vocab('users.name') ?>:</th>
        <td><?php echo htmlspecialchars($registration['username']) ?></td>
      </tr>
      <tr>
        <th><?php echo get_vocab('email') ?>:</th>
        <td><?php echo htmlspecialchars($registration['email']) ?></td>
      </tr>
      <tr>
        <th><?php echo get_vocab('register_organization') ?>:</th>
        <td><?php echo htmlspecialchars($registration['organization']) ?></td>
      </tr>
      <tr>
        <th><?php echo get_vocab('register_role') ?>:</th>
        <td><?php echo htmlspecialchars($registration['role']) ?></td>
      </tr>
      <tr>
        <th><?php echo get_vocab('register_requested_at') ?>:</th>
        <td><?php echo date('Y-m-d H:i:s', $registration['created_at']) ?></td>
      </tr>
    </table>
  </div>
  
  <form method="POST" style="margin-top: 20px;">
    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token) ?>">
    <button type="submit" name="action" value="approve" class="button">
      <?php echo get_vocab('register_approve_button') ?>
    </button>
    <button type="submit" name="action" value="reject" class="button">
      <?php echo get_vocab('register_reject_button') ?>
    </button>
  </form>
</div>

<style>
.registration_details {
  background: #f5f5f5;
  padding: 20px;
  border-radius: 5px;
  margin: 20px 0;
}
.registration_details table {
  width: 100%;
}
.registration_details th {
  text-align: left;
  padding: 8px;
  width: 200px;
  font-weight: bold;
}
.registration_details td {
  padding: 8px;
}
form button {
  margin-right: 10px;
  padding: 10px 20px;
  font-size: 14px;
  cursor: pointer;
}
</style>

<?php
print_footer();
