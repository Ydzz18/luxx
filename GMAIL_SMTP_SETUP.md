# Gmail SMTP Configuration Guide

This guide will help you set up Gmail SMTP for sending emails from your LuxStore e-commerce platform.

## Prerequisites

- A Gmail account
- Access to LuxStore Admin Panel

## Step 1: Enable 2-Factor Authentication (Recommended)

1. Go to [Google Account Security](https://myaccount.google.com/security)
2. Click on "2-Step Verification" 
3. Follow the prompts to enable 2FA

## Step 2: Generate Gmail App Password

1. Go to [Google App Passwords](https://myaccount.google.com/apppasswords)
   - If you don't see this option, 2FA must be enabled first
2. Select **Mail** from the first dropdown
3. Select **Windows Computer** (or your device type) from the second dropdown
4. Click **Generate**
5. Copy the 16-character password shown in the yellow box
   - The password will look like: `xxxx xxxx xxxx xxxx`

## Step 3: Configure SMTP in LuxStore Admin

1. Log in to your LuxStore Admin Panel
2. Navigate to **Settings** → **Email** tab
3. Look for the **Gmail SMTP Configuration** section

### Fill in the following fields:

| Field | Value |
|-------|-------|
| **Enable Gmail SMTP** | Check this box ✓ |
| **Gmail Email Address** | Your full Gmail address (e.g., `your-email@gmail.com`) |
| **Gmail App Password** | The 16-character password from Step 2 (without spaces) |
| **From Name** | Your store name (e.g., `LuxStore`) |
| **SMTP Host** | `smtp.gmail.com` (already filled) |
| **SMTP Port** | `587` (already filled) |
| **Encryption** | `TLS` (already selected) |

4. Click **Save SMTP Settings**

## Step 4: Test Email Configuration

1. Still in the **Email** tab, scroll to the **Test Email** section
2. Enter your email address in the test email field
3. Click **Send Test**
4. Check your inbox (and spam folder) for the test email

If you receive the test email, your SMTP is configured correctly! ✅

## Troubleshooting

### I don't see "App Passwords" option
- Make sure you have **2-Factor Authentication** enabled on your Google Account
- Go to [Google Security Settings](https://myaccount.google.com/security) to enable it

### Test email is not arriving
1. Check your spam/trash folder
2. Wait a few seconds and try again
3. Check the browser console for error messages
4. Verify the Gmail email and app password are correct

### "SMTP Error: authentication failed"
- Make sure you're using the 16-character **App Password**, not your regular Gmail password
- Don't include spaces in the app password when entering it
- Remove any extra whitespace

### Emails are still using PHP mail() instead of SMTP
- Make sure you **checked the "Enable Gmail SMTP"** box
- Click **Save SMTP Settings** to apply the changes

## Email Notification Settings

Configure which emails your store will send:

- ✅ **Send order confirmation to customers** - Order confirmation when placed
- ✅ **Send new order notification to admin** - Alert when customer places order
- ✅ **Send status update emails to customers** - Notifications when order status changes
- ✅ **Send welcome email to new users** - Welcome message for new accounts

All these settings can be toggled in the **Email Notifications** section.

## How It Works

When SMTP is **enabled**:
- Emails are sent directly through Gmail's SMTP server
- More reliable delivery
- Bypasses local mail server limitations
- Works with Gmail's security features

When SMTP is **disabled**:
- Emails use PHP's built-in `mail()` function
- Depends on your server's mail configuration
- May have deliverability issues

## Security Notes

- Your Gmail password is stored in the database
- Never share your App Password or database access
- App Passwords only work with Gmail (they can't be used for logging into Google)
- You can revoke App Passwords anytime at [Google App Passwords](https://myaccount.google.com/apppasswords)

## Contact Support

If you encounter issues:
1. Check the error message in the admin panel
2. Verify all settings match this guide
3. Test with a different email address
4. Check your Gmail account security settings

---

**Last Updated:** December 2025
**LuxStore Version:** Current
