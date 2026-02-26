#!/bin/bash

# Tests if mailcrypt is working by uploading emails via IMAP and checking disk storage
set -e

DOVECOT_HOST="localhost"
DOVECOT_PORT="1143"

# User WITH mailcrypt enabled
CRYPT_USER="mailcrypt@example.org"
CRYPT_PASS="password"
CRYPT_INBOX="/srv/vmail/mailcrypt/Mail/mailboxes/INBOX/dbox-Mails/"

# User WITHOUT mailcrypt
PLAIN_USER="user@example.org"
PLAIN_PASS="password"
PLAIN_INBOX="/srv/vmail/user/Mail/mailboxes/INBOX/dbox-Mails/"

TEST_STRING="Content of email"

create_test_email() {
    cat > /tmp/test-email.eml << EOF
From: test@example.org
To: $1
Subject: Mailcrypt Test
Date: $(date -R)
Message-ID: <test-$(date +%s)@example.org>

$TEST_STRING
EOF
}

upload_email_curl() {
    local user=$1
    local pass=$2

    echo "Uploading email to $user..."

    curl --silent -k \
        --url "imap://$DOVECOT_HOST:$DOVECOT_PORT/INBOX" \
        --user "$user:$pass" \
        --upload-file /tmp/test-email.eml
}

clear_inbox() {
    local user=$1
    echo "Clearing INBOX for $user..."

    docker compose exec dovecot doveadm mailbox delete INBOX -r -u "$user"
    docker compose exec dovecot doveadm mailbox cache purge INBOX -u "$user"
}

check_encryption() {
    local user=$1
    local path=$2
    local should_be_encrypted=$3

    echo "Checking disk storage for $user..."

    if docker compose run --rm -T tools grep -r -q "$TEST_STRING" $path 2>/dev/null \
    ; then
        if [ "$should_be_encrypted" = "yes" ]; then
            echo "❌ FAIL: Content IS readable for $user (should be encrypted)"
            return 1
        else
            echo "✔️ PASS: Content is readable for $user (as expected - no encryption)"
            return 0
        fi
    else
        if [ "$should_be_encrypted" = "yes" ]; then
            echo "✔️ PASS: Content is NOT readable for $user (properly encrypted)"
            return 0
        else
            echo "❌ FAIL: Content is NOT readable for $user (should be plaintext)"
            return 1
        fi
    fi
}

main() {
    echo "=== Mailcrypt Test Script ==="
    echo

    # Test 1: User WITH mailcrypt
    echo "--- Testing user WITH mailcrypt: $CRYPT_USER ---"
    clear_inbox "$CRYPT_USER"
    create_test_email "$CRYPT_USER"
    upload_email_curl "$CRYPT_USER" "$CRYPT_PASS"
    sleep 2  # Give dovecot time to process
    check_encryption "$CRYPT_USER" "$CRYPT_INBOX" "yes"
    echo

    # Test 2: User WITHOUT mailcrypt (control)
    echo "--- Testing user WITHOUT mailcrypt: $PLAIN_USER ---"
    clear_inbox "$PLAIN_USER"
    create_test_email "$PLAIN_USER"
    upload_email_curl "$PLAIN_USER" "$PLAIN_PASS"
    sleep 2  # Give dovecot time to process
    check_encryption "$PLAIN_USER" "$PLAIN_INBOX" "no"
    echo

    echo "=== Test Complete ==="
}

main
