<?php

namespace Drupal\test_myusers\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements form to upload a file and start the batch on form submit.
 *
 * @see \Drupal\Core\Form\FormBase
 * @see \Drupal\Core\Form\ConfigFormBase
 */
class CSVimportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'csvimport_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['csvfile'] = [
      '#title'            => $this->t('CSV File'),
      '#type'             => 'managed_file',
      '#upload_validators' => ['file_validate_extensions' => ['csv']],
      '#upload_location' => 'public://users/',
    ];

    $form['submit'] = [
      '#type'  => 'submit',
      '#value' => $this->t('Start Import'),
    ];

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
    $batch = [
      'title' => t('Importing Users'),
      'operations' => [],
      'init_message' => t('Import process is starting.'),
      'progress_message' => t('Processed @current out of @total. Estimated time: @estimate.'),
      'error_message' => t('The process has encountered an error.'),
    ];

    $file = $form_state->getValue('csvfile');
    $fid = $file[0];
    $row = 1;
    if ($fid) {
      $file_storage = \Drupal::entityTypeManager()->getStorage('file');
      $file = $file_storage->load($fid);

      if (($handle = fopen($file->getFileUri(), "r")) !== FALSE) {
        while (($data = fgetcsv($handle)) !== FALSE) {
          $num = count($data);
          $row++;
          for ($c = 0; $c < $num; $c++) {
            // exit;.
            print_r($data[$c]);
          }
          $batch['operations'][] = [['\Drupal\test_myusers\Form\CSVimportForm', 'importUsers'], [$row[0]]];
        }
        fclose($handle);

        batch_set($batch);
        \Drupal::messenger()->addMessage('Imported ' . $row . ' Users!');
      }
    }

  }

  /**
   * Insert user from csv.
   */
  public function importUsers($item, &$context) {
    \Drupal::database()->insert("myusers")
      ->fields(['name' => $item])
      ->execute();
  }

}
