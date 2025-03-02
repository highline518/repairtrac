
# Twilio SMS Consent Compliance Documentation

This document outlines how this Task Manager application complies with SMS messaging regulations and Twilio's acceptable use policies.

## Consent Collection Process

1. **Consent Form**: A dedicated form at `consent_form.php` collects explicit consent from users before they receive SMS notifications.

2. **Consent Record Storage**: All consent records are stored in the `sms_consent` table with the following information:
   - Recipient's name
   - Phone number
   - IP address (to verify the source of consent)
   - Date and time of consent
   - Exact consent language agreed to

3. **Opt-out Mechanism**: Users can opt out at any time through the SMS Admin interface.

## Compliance Features

### Records Maintenance
- All consent records are maintained indefinitely for compliance purposes
- Records can be exported to CSV for auditing or record-keeping
- The system documents who received consent, when it was received, and what the user agreed to

### Content Guidelines
- All SMS messages are clearly related to task management
- Messages identify the sender (Task Manager) and purpose
- Message frequency is limited to essential notifications

### Regulatory Alignment
This implementation helps maintain compliance with:
- Telephone Consumer Protection Act (TCPA)
- Cellular Telecommunications Industry Association (CTIA) guidelines
- Twilio's Acceptable Use Policy

## Verifying Numbers with Twilio

During the trial period, phone numbers must be verified before sending messages:
1. Visit `verify_phone.php` to verify each recipient's phone number
2. This process uses Twilio's Verify API to confirm number ownership
3. After verification, numbers are automatically added to the approved recipient list

## How to Demonstrate Compliance

If requested by Twilio or regulatory authorities, you can demonstrate compliance by:
1. Exporting consent records from `consent_records.php`
2. Showing the consent form which clearly describes what users are agreeing to
3. Demonstrating the opt-out mechanism in the SMS admin panel

## Setup Instructions

1. Ensure all recipients provide consent through the consent form
2. Maintain accurate records of all consent provided
3. Review compliance documentation periodically to ensure ongoing adherence
