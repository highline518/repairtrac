
# Setting Up SMS Notifications with Twilio

This document explains how to set up Twilio for SMS notifications in your task management system.

## Step 1: Create a Twilio Account

1. Go to [Twilio's website](https://www.twilio.com/) and sign up for an account.
2. After signing up, you'll be directed to the Twilio Console.

## Step 2: Get a Twilio Phone Number

1. In the Twilio Console, navigate to "Phone Numbers" > "Manage" > "Buy a Number".
2. Search for a phone number with SMS capabilities and purchase it.

## Step 3: Get Your Twilio Credentials

In the Twilio Console:
1. Find your Account SID and Auth Token on the dashboard.
2. Note your Twilio phone number.

## Step 4: Set Up Environment Variables

You need to add three environment variables to your server:

1. `TWILIO_ACCOUNT_SID`: Your Twilio account SID
2. `TWILIO_AUTH_TOKEN`: Your Twilio auth token
3. `TWILIO_PHONE_NUMBER`: Your Twilio phone number (in E.164 format, e.g., +12345678900)

### Setting Environment Variables on Your Server

Add these variables to your server configuration or create a `.env` file in the root directory.

Example:
```
TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_PHONE_NUMBER=+12345678900
```

## Step 5: Run the SMS Setup Script

1. Navigate to `http://your-domain.com/api/setup_sms.php` to create the necessary database table.
2. Then visit `http://your-domain.com/sms_admin.php` to manage your SMS recipients.

## Usage

Once set up, the system will automatically send SMS notifications when:
1. A new task is created
2. A task status is changed (issue status, progress status, completion status)

You can customize who receives these notifications in the SMS Admin panel.
