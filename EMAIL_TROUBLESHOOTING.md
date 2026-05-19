# Email Not Receiving - Diagnostic Steps

## Quick Diagnosis (5 minutes)

### Step 1: Run the Email Test Script
1. Open browser: `http://localhost/Document_Tracker_Final-develop/test_email.php`
2. Enter your test email address
3. Click "Send Test Email"
4. Check what happens:
   - ✅ **Success?** Email worked! Check spam folder
   - ❌ **Failed?** Check error message below

---

## Common Issues & Fixes

### ❌ Issue 1: "SMTP Error: Could not authenticate"

**Cause:** Gmail App Password is invalid or 2FA is not enabled

**Fix:**
```
1. Go to: https://myaccount.google.com/security
2. Verify "2-Step Verification" is ON (not off!)
3. If OFF, turn it ON now
4. Go to: https://myaccount.google.com/apppasswords
5. Select "Mail" and "Windows Computer"
6. Generate NEW app password (16 characters)
7. Copy the password WITHOUT spaces
8. Edit: email_helper.php line 20
   OLD: define('GMAIL_APP_PASSWORD', 'gsxa qkej lfpc yhkb');
   NEW: define('GMAIL_APP_PASSWORD', '[YOUR NEW PASSWORD]');
9. Save file
10. Try test_email.php again
```

### ❌ Issue 2: "Unable to connect to SMTP server"

**Cause:** Firewall blocking port 587 or SMTP disabled

**Fix:**
```
1. Try from different network (mobile hotspot)
2. Check firewall settings
3. On Windows, check if port 587 is blocked:
   - Settings > Windows Defender Firewall > Advanced Settings
   - Allow port 587 outbound
```

### ❌ Issue 3: "SMTP connection timeout"

**Cause:** Gmail server is not responding

**Fix:**
```
1. Try later (Gmail server issue)
2. Check internet connection
3. Try different email account (test Gmail account)
```

### ✅ Issue 4: Email sent but not received

**Cause:** Email going to SPAM folder

**Fix:**
```
1. Check your SPAM/Promotions folder
2. Mark email as "Not Spam"
3. Add docutracker.system@gmail.com to contacts
4. In Gmail: Settings > Filters and Blocked Addresses
5. Create filter to send from docutracker to "Always go to inbox"
```

### ✅ Issue 5: Email sent but invalid recipient

**Cause:** Recipient email in database is wrong

**Fix:**
```
1. Go to Track Document.php
2. Click "👤 Details" button on document
3. Check "Email Address" field is valid
4. If wrong, you need to update database:
   UPDATE requests SET email='correct@email.com' 
   WHERE tracking_id='TRK-XXXXX'
```

---

## Verify Gmail Credentials

### Check if 2FA is enabled:
```
https://myaccount.google.com/security
Look for "2-Step Verification" 
Should say: "Status: On"
```

### Check/Generate App Password:
```
https://myaccount.google.com/apppasswords
Select: Mail
Select: Windows Computer
Click: Generate
Copy the 16-character password (no spaces)
```

### Current configuration:
```
Gmail Account: docutracker.system@gmail.com
SMTP Server: smtp.gmail.com:587
Encryption: STARTTLS
App Password: gsxa qkej lfpc yhkb ← NEED TO VERIFY THIS IS CURRENT
```

---

## Step-by-Step Email Sending Test

### 1. Create Test Document Request
- Go to "Add Request"
- Fill form with:
  - Name: John Doe
  - Email: **YOUR EMAIL** ← Important!
  - Document Type: Any
  - Phone: 1234567890
  - Submit

### 2. Mark as Completed
- Go to "Track Document"
- Find the request you just created
- Click "Update Status"
- Select "Completed"
- Click "Save Changes"
- **CHECK:** Does it say "✅ Status updated & completion email sent!"?

### 3. Check Email Inbox
- Open your email (use the email you entered in form)
- Check INBOX first
- Check SPAM/Promotions folder
- Look for email from: docutracker.system@gmail.com
- Subject: "Document Request Completed"

### 4. Monitor Error Log
- If not received, check error log:
  - File: `C:\xampp\apache\logs\error.log`
  - Look for recent entries
  - Copy error messages

---

## Gmail Account Setup (Complete Guide)

If your Gmail account doesn't have 2FA enabled, follow this:

### 1. Enable 2-Factor Authentication
```
1. Go to: https://myaccount.google.com/security
2. Scroll down to "Your devices"
3. Click "2-Step Verification"
4. Follow Google's prompts
5. Verify with phone
6. Save recovery codes
```

### 2. Generate App Password
```
1. Go to: https://myaccount.google.com/apppasswords
2. Select "Mail"
3. Select "Windows Computer"
4. Click "Generate"
5. Copy password (format: xxxx xxxx xxxx xxxx)
6. IMPORTANT: Remove spaces → xxxxxxxxxxxxxxxx
```

### 3. Update email_helper.php
```php
Edit file: email_helper.php
Line 20: define('GMAIL_APP_PASSWORD', 'YOUR_NEW_PASSWORD_HERE');

Example:
define('GMAIL_APP_PASSWORD', 'gsxa qkej lfpc yhkb');
```

### 4. Save and Test
- Save the file
- Go to: test_email.php
- Send test email
- Verify receipt

---

## Test Email Script Output Explained

### ✅ All Green?
```
✅ vendor/autoload.php found
✅ PHPMailer class loaded
✅ email_helper.php found
✅ Gmail credentials are configured
✅ SMTP configured
✅ Email configured
✅ EMAIL SENT SUCCESSFULLY!
```
→ Email SHOULD arrive in 10-30 seconds

### ❌ Any Red?
```
❌ PHPMailer class NOT found
→ Run: composer install

❌ email_helper.php NOT found
→ File missing, reinstall

❌ Gmail credentials missing
→ Edit email_helper.php line 20

❌ EMAIL SEND FAILED
→ Check error message below
```

---

## Debug: If Email Still Not Working

### 1. Check SMTP Connection
```bash
# Open Command Prompt and run:
telnet smtp.gmail.com 587

# Should show:
220 smtp.google.com ESMTP

# If hangs or times out:
→ Firewall or network issue
→ Try from different location
```

### 2. Check Database Email Field
```sql
-- In phpMyAdmin, run:
SELECT tracking_id, email FROM requests WHERE email IS NOT NULL LIMIT 5;

-- Should show emails like: user@gmail.com
-- If empty or NULL:
→ Email not captured in form
→ Need to update form capture
```

### 3. Check Error Log
```
File: C:\xampp\apache\logs\error.log

Look for lines like:
❌ EMAIL SENDING FAILED!
    Error: SMTP connect() failed

OR

✅ EMAIL SENT SUCCESSFULLY!
```

### 4. Check Console Errors
- Press F12 in browser
- Go to "Console" tab
- Look for JavaScript errors
- Check "Network" tab to see API response

---

## When You Get It Working

### You'll see:
1. ✅ Button "Save Changes" clicked
2. ✅ Modal closes
3. ✅ Toast message appears: "✅ Status updated & completion email sent!"
4. ✅ 10-30 seconds later: Email arrives in inbox
5. ✅ Professional HTML email with personalized greeting

---

## Support Contacts

If stuck:
1. Check Apache error log
2. Run test_email.php
3. Verify Gmail 2FA is ON
4. Verify App Password is current
5. Verify recipient email is valid
6. Try different email account

---

**Next Step:** Open your browser and go to:
```
http://localhost/Document_Tracker_Final-develop/test_email.php
```

This will tell us exactly what's wrong!

