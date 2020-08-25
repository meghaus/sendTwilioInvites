<?php
/*
*	This class implements a plugin that extends Limesurvey v.4.3+ 
*	The SmsInvitesTwilio Plugin adds the feature of sending survey invitations to mobiles via SMS
*	To differentiate between the survey invites that will be sent via email and 
*	those to be sent via SMS, an extra attribute (attribute_1) needs to be added with the value NA 
*	for email invites and the recipient's mobile number for SMS invites.
*	It is tested with Limesurvey Version 4.3.9+200806
*	@author: Mira Zeit forked -> stfandrade forked -> meghaus
*	@version: 4.0.1
*/

//Twilio SMS API
require __DIR__ . '/vendor/autoload.php';

use Twilio\Rest\Client;

class SmsInvitesTwilio extends PluginBase
{
  static protected $description = 'SMS Invitations Functionality';
  static protected $name = 'SmsInvitesTwilio';
  protected $storage = 'DbStorage';

  //Plugin Initial Settings
  protected $settings = array(
    'EnableSendSMS' => array(
      'type' => 'select',
      'options' => array(
        0 => 'No',
        1 => 'Yes'
      ),
      'default' => 0,
      'label' => 'Enable sending SMS invites to mobiles?',
      'help' => 'Overwritable in each Survey setting',
    ),
    'MessageBody' => array(
      'type' => 'text',
      'label' => 'Enter the message body to be sent to survey participant\'s mobile:',
      'help' => 'You may use the placeholders {FIRSTNAME}, {LASTNAME} and {SURVEYURL}.',
      'default' => "Dear {FIRSTNAME} {LASTNAME}, \n We invite you to participate in the survey below: \n {SURVEYURL} \n Survey Team",
    )
  );

  // Register custom function/s
  public function init()
  {
    // Settings to display errors for better debugging
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    $this->subscribe('beforeTokenEmail');
    $this->subscribe('beforeSurveySettings');
    $this->subscribe('newSurveySettings');
  }

  public function beforeTokenEmail()
  {
    $oEvent = $this->getEvent();
    $surveyId = (string)$oEvent->get('survey');
    $typeOfEmail = $oEvent->get('type');

    // Before changing any settings we need to check that:
    // 1. the SmsInvitesTwilio is enabled by the admin for this specific survey
    $pluginEnabled = strcmp($this->get('EnableSendSMS', 'survey', $surveyId), '1') == 0;
    // 2. Check the type of email, invitaiton or reminder => send, confirmation just ignore (not included in my plans :/).
    $vaildEmailType = (((strcmp($typeOfEmail, 'invite') == 0) or (strcmp($typeOfEmail, 'remind') == 0)) or (strcmp($typeOfEmail, 'confirm') == 0));
    $ourTokenData = $oEvent->get("token");

    if ($pluginEnabled and $vaildEmailType) {

      // Then we need to check if the admin added an extra attribute
      if (isset($ourTokenData['attribute_1'])) {
        // 3. This invite should be send via SMS and not to the Email account
        $mobile = $ourTokenData['attribute_1'];
        if (strcmp($mobile, 'NA') != 0 and !empty($mobile)) {
          // disable sending email for this token and send SMS
          $this->event->set("send", false);

          // we get the token data and prepare the survey link 
          $SMS_message = $this->get('MessageBody', 'survey', $surveyId);  // The MessageBody entered by the admin
          $participantToken = $ourTokenData['token'];
          $participantFirstName = (string)$ourTokenData['firstname'];
          $participantLastName = (string)$ourTokenData['lastname'];
          $surveyLink = 'https://' . $_SERVER['SERVER_NAME'] . '/lsv/?r=survey/index&sid=' . $surveyId . '&token=' . $participantToken;

          // Setting up the default SMS message in case the admin left it empty.
          if (empty($SMS_message)) {
            $SMS_message = "Dear {FIRSTNAME} {LASTNAME},\nWe invite you to participate in the survey: \n {SURVEYURL} \nThank You,\nSurvey Team";
          }

          // Replacing the placeholders in the Admin message, so as to have the participant's data.
          $SMS_message_with_Replacement = str_replace("{FIRSTNAME}", $participantFirstName, $SMS_message);
          $SMS_message_with_Replacement = str_replace("{LASTNAME}", $participantLastName, $SMS_message_with_Replacement);
          $SMS_message_ready_to_be_sent = str_replace("{SURVEYURL}", $surveyLink, $SMS_message_with_Replacement);

          // Since I don't want to send confirmation SMS, only for invite and remind
          if ((strcmp($typeOfEmail, 'invite') == 0) or (strcmp($typeOfEmail, 'remind') == 0)) {
            // setting up the connection with SMS Service Provider then sending SMS msg            
            $mobile;
            $SMS_message_ready_to_be_sent;
            $result_of_post = $this->sendSMS($mobile, $SMS_message_ready_to_be_sent);
            if ($result_of_post === FALSE) {
              echo ("SMS not sent. Please contact the administrator at survey_admin@xyz.com");
              exit;
            }
          } else {
          }  // Confirmation don't want to send --> change this if you want to enter message and send confirmation SMS
        }
      } else {
        echo ("sendTwilioInvites Plugin is enabled. If you do not wish to send SMS invitations, disable it. If you intend to use it, the SMS was not sent. Please add an extra attribute with the mobile number or NA for emails.");
        exit;
      }
    } else {
    } // The SmsInvitesTwilio is not enabled. Don't change anything!	
  }

  private function sendSMS($to, $body)
  {
    // Require the bundled autoload file - the path may need to change
    // based on where you downloaded and unzipped the SDK

    $account_sid = 'Insert Your Twilio Account SID Here';
    $auth_token = 'Insert Your Twilio Account Auth ID Here';
    // A Twilio number with SMS capabilities
    $twilio_number = "Insert Your Twilio Mobile Number Here";

    $client = new Client($account_sid, $auth_token);

    // Use the client to do fun stuff like send text messages!
    $response = $client->messages->create(
      // the number you'd like to send the message to
      $to,
      [
        'from' => $twilio_number,
        'body' => $body
      ]
    );
    return $response;
  }

  /**
   * This event is fired by the administration panel to gather extra settings
   * available for a survey. These settings override the global settings.
   * The plugin should return setting meta data.
   * @param PluginEvent $event
   */
  public function beforeSurveySettings()
  {
    $event = $this->event;
    $event->set("surveysettings.{$this->id}", array(
      'name' => get_class($this),
      'settings' => array(
        'EnableSendSMS' => array(
          'type' => 'select',
          'options' => array(
            0 => 'No',
            1 => 'Yes'
          ),
          'default' => 0,
          'label' => 'Enable sending SMS invites to mobiles?',
          'current' => $this->get('EnableSendSMS', 'Survey', $event->get('survey'), $this->get('EnableSendSMS', null, null, $this->settings['EnableSendSMS']['default'])),
        ),
        'MessageBody' => array(
          'type' => 'text',
          'label' => 'Enter the message body to be sent to survey participant\'s mobile:',
          'help' => 'You may use the placeholders {FIRSTNAME}, {LASTNAME} and {SURVEYURL}.',
          'default' => "Dear {FIRSTNAME} {LASTNAME},\nWe invite you to participate in the survey: \n {SURVEYURL} \nThank You,\nSurvey Team",
          'current' => $this->get('MessageBody', 'Survey', $event->get('survey'), $this->get('MessageBody', null, null, $this->settings['MessageBody']['default'])),
        )
      )
    ));
  }

  public function newSurveySettings()
  {
    $event = $this->event;
    foreach ($event->get('settings') as $name => $value) {
      /* In order use survey setting, if not set, use global, if not set use default */
      $default = $event->get($name, null, null, isset($this->settings[$name]['default']) ? $this->settings[$name]['default'] : NULL);
      $this->set($name, $value, 'Survey', $event->get('survey'), $default);
    }
  }
}
