<?php

namespace Drupal\telegram_notification\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
/**
 * Class ConfigurationForm.
 *
 * @package Drupal\pwa\Form
 */
class TelegramBotForm extends ConfigFormBase {
  
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'telegram_notification_main_settings_form';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
  
    $config = $this->config('tnotify.config');
    
    $form['settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Chat settings:'),
      '#open' => TRUE,
    ];
    
    $form['settings']['bot_token'] = [
      '#type' => 'textfield',
      '#title' => 'Telegram Bot Token:',
      '#required' => TRUE,
      '#default_value' => $config->get('bot_token'),
      '#description' => "Telegram bot token in format: 123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11. How create bot and get token, read more about <a href='https://core.telegram.org/bots#6-botfather'>BotFather</a>",
    ];
    
    $form['config'] = [
      '#type' => 'details',
      '#title' => $this->t('Contact forms configurations:'),
      '#open' => FALSE,
    ];
    
    $form['config']['cid'] = [
      '#type' => 'select',
      '#title' => 'Select contact form:',
      '#options' => \Drupal::entityQuery('contact_form')->execute(),
      '#default_value' => $config->get('cid'),
    ];
    
    return parent::buildForm($form, $form_state);
  }
  
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  
    //TODO add validations criteria
    
    //$config = $this->config('tnotify.config');
    
    if (substr($form_state->getValue('bot_token'), 0, 3) == "bot") {
      $form_state->setErrorByName('Invalid token', $this->t('The token must conform to the format'));
    }
    
    parent::validateForm($form, $form_state);
  }
  
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('tnotify.config');
    $token = $form_state->getValue('bot_token');

    $ids = $this->getChatIds($token);

    drupal_set_message(print_r($ids,1), error);

    $config
      ->set('bot_token', $form_state->getValue('bot_token'))
      ->set('cid', $form_state->getValue('cid'))
      ->save();
    
    parent::submitForm($form, $form_state);
  }

  function getChatIds($token) {
    if (isset($token) && !empty($token)) {
      $url = "https://api.telegram.org/bot{$token}/getUpdates";

      $json = file_get_contents($url);
      $obj = json_decode($json);

      $responseStatus = $obj->ok;

      if ($responseStatus == TRUE){
        $ids = [];
        $messages = $obj->result;
        foreach ($messages as $message) {
          $data = $message->message->from;
          if ($data->is_bot == FALSE && in_array($data->id, $ids) == FALSE) {
            $ids[] = $data->id;
          }
        }
        return $ids;
      }
      else return FALSE;
    }
    else return FALSE;
  }
  
  protected function getEditableConfigNames() {
    return ['tnotify.config'];
  }
}
