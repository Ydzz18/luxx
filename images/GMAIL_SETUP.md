# Gmail SMTP Setup Guide for InfinityFree

## Step 1: Enable 2-Factor Authentication on Gmail
1. Go to https://myaccount.google.com/
2. Click "Security" in the left menu
3. Enable "2-Step Verification"

## Step 2: Create an App Password
1. Go to https://myaccount.google.com/apppasswords
2. Select "Mail" and "Windows Computer" (or your device)
3. Google will generate a 16-character password
4. **Copy this password** - you'll need it next
5. kcooqodzgkvynodo
6. xbmeddiipuizqogy

## Step 3: Configure in Admin Settings
1. Log in to admin panel → Settings
2. Add these settings:
   - **gmail_sender_email**: your-email@gmail.com
   - **gmail_sender_password**: [paste the 16-char password from Step 2]

## Step 4: Test
Send an order status update to test if emails work.

## Troubleshooting

**"SMTP Connection failed"**
- InfinityFree might block outgoing SMTP on port 587
- Contact your hosting support to enable SMTP

**"Authentication failed"**
- Verify the 16-character app password is correct (no spaces)
- Ensure 2FA is enabled on your Gmail account

**"STARTTLS failed"**
- Some hosts disable TLS - contact your host

**Emails not sending?**
- Check error logs (usually in `/public_html/error_log`)
- Look for "SMTP Error" messages
