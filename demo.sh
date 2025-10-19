#!/bin/bash

clear

cat << "EOF"
╔═══════════════════════════════════════════════════════════╗
║                                                           ║
║   CI-Guard: Prevent Production Incidents                 ║
║   Before they happen                                      ║
║                                                           ║
╚═══════════════════════════════════════════════════════════╝
EOF

echo ""
echo "Creating a sample project with risky HTTP calls..."
sleep 1

mkdir -p /tmp/ci-guard-demo/src

# Payment service sans timeout
cat > /tmp/ci-guard-demo/src/PaymentService.php << 'EOF'
<?php
use GuzzleHttp\Client;

class PaymentService {
    public function charge($amount) {
        $client = new Client();
        // Critical: Payment gateway call without timeout
        return $client->post('https://payment-api.com/charge', [
            'json' => ['amount' => $amount]
        ]);
    }
}
EOF

# Notification service sans timeout
cat > /tmp/ci-guard-demo/src/NotificationService.php << 'EOF'
<?php
use Symfony\Component\HttpClient\HttpClient;

class NotificationService {
    public function notify($message) {
        $client = HttpClient::create();
        // Risk: External webhook without timeout
        $client->request('POST', 'https://hooks.slack.com/webhook', [
            'json' => ['text' => $message]
        ]);
    }
}
EOF

echo "✓ Project created"
echo ""
echo "Running CI-Guard analysis..."
echo ""
sleep 1

./bin/ci-guard /tmp/ci-guard-demo/src

echo ""
echo "═══════════════════════════════════════════════════════════"
echo "  ✅ Analysis complete - Issues detected and prevented!"
echo "═══════════════════════════════════════════════════════════"

rm -rf /tmp/ci-guard-demo