<?php
declare(strict_types=1);
namespace MRBS;

require "defaultincludes.inc";

// Check if already logged in
if (isset($user))
{
  header("Location: index.php");
  exit();
}

// Print the page header
print_header();
?>

<div class="container">
  <h2><?php echo get_vocab('register_success_title') ?></h2>
  
  <p><?php echo get_vocab('register_success_message') ?></p>
  
  <p><?php echo get_vocab('register_check_email') ?></p>
  
  <p>
    <a href="index.php"><?php echo get_vocab('back_to_home') ?></a>
  </p>
</div>

<?php
// Print the page footer
print_footer();
