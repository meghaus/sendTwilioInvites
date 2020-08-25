# SMSInvitesTwilio

A Limesurvey Plugin that adds the option to send survey invitations via SMS using [Twilio](https://www.twilio.com/sms).

## The Purpose

This plugin can be used in the case where the email field is missing/NA for some survey recipients, while their mobile numbers are available. In the token list preparation, it is essential to create “Dummy” email accounts for those recipients with no emails so that they can be included in the mailing list. Furthermore, we add an extra attribute with the name of your choice, for example ‘Mobile Number’. This extra attribute will be filled with the mobile number for the invitations to be sent via SMS, and “NA” for those to be sent by email as shown in the following example:

```
firstname	| lastname | email			| Mobile Number
---------------------------------------------------------------------------
John 		| Smith	   | valid_email@domain.com	| NA
Mary		| Anderson | RandomEmail@something.net	| 0099123456789

```

Note: The mobile number needs to be in the 1st extra attribute for the plugin to work properly.

## Getting Started

### Prerequisites

- Limesurvey Version 4.3
- An account at a Twilio SMS Gateway which provides a HTTP/HTTPS interface to interact with the plugin.

### Installation

In order to install this plugin:

1. Download the master file and extract to your local development environment.
2. Create a Twilio account. Refer [Twilio Documentation](https://www.twilio.com/docs/sms/quickstart/php) for further instructions. You will need your Twilio account SID, Auth Token and the Twilio number to proceed.
3. Replace the above details from your account in the SmsInvitesTwilio.php file.
4. Create a folder in the directory plugins located at your Limesurvey server, the folder created has to have the same name as the plugin (eg. SmsInvitesTwilio here).
5. Upload the files to the folder created.
6. After refreshing the admin page, activate the plugin from Configuration -> Plugin Manager Panel.

### Plugin Settings

This plugin includes two settings; EnableSendSMS and MessageBody. These settings can be set globally from the Plugin Manager -> (sendSMSInvites) -> Configure. The EnableSendSMS is set by default to No, this can be overridden on the survey level from the survey settings. The MessageBody setting gives the survey admin the space to write the SMS that will be sent to the recipients.

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details

## Acknowledgments

- Stefan Verweij – [Creating Limesurvey Plugins](https://medium.com/@evently/creating-limesurvey-plugins-adcdf8d7e334)
