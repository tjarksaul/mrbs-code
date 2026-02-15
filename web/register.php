<?php
declare(strict_types=1);
namespace MRBS;

use MRBS\Form\ElementFieldset;
use MRBS\Form\ElementP;
use MRBS\Form\FieldDiv;
use MRBS\Form\FieldInputEmail;
use MRBS\Form\FieldInputPassword;
use MRBS\Form\FieldInputSubmit;
use MRBS\Form\FieldInputText;
use MRBS\Form\Form;

require "defaultincludes.inc";

// Check if already logged in
if (isset($user))
{
  header("Location: index.php");
  exit();
}

/**
 * Generate the registration form
 * @param string|null $error Error message to display
 * @return void
 */
function generate_registration_form(?string $error=null) : void
{
  global $pwd_policy;

  $form = new Form(Form::METHOD_POST);
  $form->setAttributes(array(
      'class'  => 'standard',
      'id'     => 'registration_form',
      'action' => multisite('register_handler.php')
    ));

  $fieldset = new ElementFieldset();
  $fieldset->addLegend(get_vocab('register_account'));

  // Display error message if any
  if (isset($error))
  {
    $field = new FieldDiv();
    $p = new ElementP();
    $p->setText(get_vocab('register_' . $error))
      ->setAttribute('class', 'error');
    $field->addControlElement($p);
    $fieldset->addElement($field);
  }

  // Instructions
  $field = new FieldDiv();
  $p = new ElementP();
  $p->setText(get_vocab('register_instructions'));
  $field->addControlElement($p);
  $fieldset->addElement($field);

  // Username field
  $field = new FieldInputText();
  $field->setLabel(get_vocab('users.name'))
        ->setControlAttributes(array(
          'id'           => 'username',
          'name'         => 'username',
          'maxlength'    => 30,
          'required'     => true,
          'autofocus'    => true,
          'autocomplete' => 'username'
        ));
  $fieldset->addElement($field);

  // Display name field
  $field = new FieldInputText();
  $field->setLabel(get_vocab('display_name'))
        ->setControlAttributes(array(
          'id'        => 'display_name',
          'name'      => 'display_name',
          'maxlength' => 191,
          'required'  => true
        ));
  $fieldset->addElement($field);

  // Email field
  $field = new FieldInputEmail();
  $field->setLabel(get_vocab('email'))
        ->setControlAttributes(array(
          'id'        => 'email',
          'name'      => 'email',
          'maxlength' => 75,
          'required'  => true,
          'autocomplete' => 'email'
        ));
  $fieldset->addElement($field);

  // Organization field (Verein)
  $field = new FieldInputText();
  $field->setLabel(get_vocab('register_organization'))
        ->setControlAttributes(array(
          'id'        => 'organization',
          'name'      => 'organization',
          'maxlength' => 255,
          'required'  => true
        ));
  $fieldset->addElement($field);

  // Role field (Funktion im Verein)
  $field = new FieldInputText();
  $field->setLabel(get_vocab('register_role'))
        ->setControlAttributes(array(
          'id'        => 'role',
          'name'      => 'role',
          'maxlength' => 255,
          'required'  => true
        ));
  $fieldset->addElement($field);

  // Password field
  $field = new FieldInputPassword();
  $field->setLabel(get_vocab('password'))
        ->setControlAttributes(array(
          'id'           => 'password',
          'name'         => 'password',
          'required'     => true,
          'autocomplete' => 'new-password'
        ));
  $fieldset->addElement($field);

  // Confirm password field
  $field = new FieldInputPassword();
  $field->setLabel(get_vocab('confirm_password'))
        ->setControlAttributes(array(
          'id'           => 'password_confirm',
          'name'         => 'password_confirm',
          'required'     => true,
          'autocomplete' => 'new-password'
        ));
  $fieldset->addElement($field);

  // Password policy
  if (isset($pwd_policy))
  {
    $field = new FieldDiv();
    $p = new ElementP();
    $p->setText(get_vocab('pwd_must_contain'));
    $field->addControlElement($p);
    
    $ul = new \MRBS\Form\Element('ul');
    $ul->setAttribute('id', 'pwd_policy');
    foreach ($pwd_policy as $rule => $value)
    {
      if ($value != 0)
      {
        $li = new \MRBS\Form\Element('li');
        $li->setText(get_vocab('policy_' . $rule, $value));
        $ul->addElement($li);
      }
    }
    $field->addControlElement($ul);
    $fieldset->addElement($field);
  }

  $form->addElement($fieldset);

  // Submit button
  $fieldset = new ElementFieldset();
  $field = new FieldInputSubmit();
  $field->setControlAttributes(array('value' => get_vocab('register_submit')));
  $fieldset->addElement($field);

  $form->addElement($fieldset);

  $form->render();
}

// Check if there's an error parameter
$error = get_form_var('error', 'string');

// Print the page header
print_header();

// Display the form
generate_registration_form($error);

// Print the page footer
print_footer();
