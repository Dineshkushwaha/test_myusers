<?php

namespace Drupal\test_myusers\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * Implements an example form.
 */
class RegistrationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'registration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your Name'),
      '#required' => TRUE,
      '#prefix' => '<div id="error-msg"></div>',
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    // Here we add Ajax callback where we will process.
      '#ajax' => [
    // The data that came from the form and that
    // we will receive as a result in the modal window.
        'callback' => '::open_modal',
      ],
    ];

    $form['#title'] = 'Registration Form';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * Submit Action.
   */
  public function open_modal(&$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $validation = $this->validateName($form_state);
    $query = \Drupal::database()->select('myusers', 'n');
    $query->addField('n', 'name');
    $query->condition('n.name', $form_state->getValue('name'));
    $results = $query->execute();
    $records = $results->fetchAll();
    $count = count($records);

    if (!empty($validation)) {
      $response->addCommand(new HtmlCommand('#error-msg', $validation['err']));
    }
    elseif ($count >= 1) {
      $response->addCommand(new HtmlCommand('#error-msg', "Account Already exist"));
    }
    else {
      // Insert users.
      \Drupal::database()->insert("myusers")
        ->fields(['name' => $form_state->getValue('name')])
        ->execute();
      $title = 'Confirmation';
      $content = '<div class="test-popup-content"> You are Registered with Name : ' . $form_state->getValue('name') . '</div>';
      $options = [
        'dialogClass' => 'popup-dialog-class',
        'width' => '300',
        'height' => '300',
      ];
      $response->addCommand(new OpenModalDialogCommand($title, $content, $options));
    }

    return $response;
  }

  /**
   * Validate the registration Form.
   */
  public function validateName(FormStateInterface $form_state) {
    $error = [];
    if (strlen($form_state->getValue('name')) < 5) {
      $error = [
        'err' => 'The name is too short. Please enter full name.',
      ];
    }
    elseif ((preg_match("/[^A-Z]/", $form_state->getValue('name')))) {
      $error = [
        'err' => 'The name should contain only characters A-Z case sensitive',
      ];
    }

    return $error;

  }

}
