PayMongo GCash Integration (Test Mode)
=======================================

STEPS FOR DEVELOPER:
1. Copy all files in this folder to the root of the project.

2. Open config.php and make sure the API keys are correct:
   - Public Key:  pk_test_Bf1KF9FR2KPnhRiR32FUn6AQ
   - Secret Key:  sk_test_5jLxkCtUH5wzWgMJn47VgBxC

3. Open paymongo.php and update the redirect URLs to match
   the actual project URL:
   - 'success' => 'http://your-actual-url/payment_success.php'
   - 'failed'  => 'http://your-actual-url/payment_failed.php'

4. Visit checkout.php to test the GCash payment flow.

5. On the GCash test page:
   - Click "Authorize" to simulate a successful payment
   - Click "Fail" to simulate a failed payment

NOTE: These are TEST keys only.