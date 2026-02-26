#!/usr/bin/env python3
"""Minimal mock of the Userli Dovecot API for integration testing.

Serves hardcoded responses for two test users:
- mailcrypt@example.org: mailCrypt enabled (mailCrypt=2) with EC keypair
- user@example.org: mailCrypt disabled (mailCrypt=0)
"""

import json
import sys
from http.server import BaseHTTPRequestHandler, HTTPServer

TOKEN = "727eb7d3ad310bc510f5fa17c223572c"

PUBLIC_KEY = (
    "LS0tLS1CRUdJTiBQVUJMSUMgS0VZLS0tLS0K"
    "TUlHYk1CQUdCeXFHU000OUFnRUdCU3VCQkFBakE0R0dBQVFCUzhwUSs2RTdVWTlFa2cyTWxqR2Vqams1eVhPeQ"
    "oxTFFMclFpRUhBWjRYWjJybkcwUmdRYkpSYjEwV3R1VWluTHpmNlc1dVVWSC9USWRDeFp3aGluNlhxY0I2T2tJCl"
    "JBZkJQWWFrbi9ITWNTNUJGL3FTWG5ta1Q3TnV6elIzRTJuS1ZGRXN6VXdrMkhGcndnM3FENWhGeFNWQXNMSGEK"
    "NWZpMlV0a202ZFNoeGg0YmVxdz0KLS0tLS1FTkQgUFVCTElDIEtFWS0tLS0tCg=="
)

PRIVATE_KEY = (
    "LS0tLS1CRUdJTiBQUklWQVRFIEtFWS0tLS0t"
    "Ck1JSHVBZ0VBTUJBR0J5cUdTTTQ5QWdFR0JTdUJCQUFqQklIV01JSFRBZ0VCQkVJQWg2UHkvMUNxdi95QUZyREQK"
    "UGhZVHYrOG5aNEdJZC9SR0g5TUc0aEpEeDhhQVpBaFp3VkMxWW1yTTU1bXNKVWpQOG5xdWJpWFdRY3phQ1VNbgoz"
    "UStVbmx5aGdZa0RnWVlBQkFGTHlsRDdvVHRSajBTU0RZeVdNWjZPT1RuSmM3TFV0QXV0Q0lRY0JuaGRuYXVjCmJS"
    "R0JCc2xGdlhSYTI1U0tjdk4vcGJtNVJVZjlNaDBMRm5DR0tmcGVwd0hvNlFoRUI4RTlocVNmOGN4eExrRVgKK3BK"
    "ZWVhUlBzMjdQTkhjVGFjcFVVU3pOVENUWWNXdkNEZW9QbUVYRkpVQ3dzZHJsK0xaUzJTYnAxS0hHSGh0NgpyQT09"
    "Ci0tLS0tRU5EIFBSSVZBVEUgS0VZLS0tLS0K"
)

USERS = {
    "mailcrypt@example.org": {
        "password": "password",
        "mailCrypt": 2,
        "mailCryptPublicKey": PUBLIC_KEY,
        "mailCryptPrivateKey": PRIVATE_KEY,
        "quota": "",
    },
    "user@example.org": {
        "password": "password",
        "mailCrypt": 0,
        "mailCryptPublicKey": "",
        "mailCryptPrivateKey": "",
        "quota": "",
    },
}


class Handler(BaseHTTPRequestHandler):
    def check_auth(self):
        auth = self.headers.get("Authorization", "")
        if auth != f"Bearer {TOKEN}":
            self.send_json(401, {"message": "unauthorized"})
            return False
        return True

    def send_json(self, code, data):
        body = json.dumps(data).encode()
        self.send_response(code)
        self.send_header("Content-Type", "application/json")
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        self.wfile.write(body)

    def do_GET(self):
        if not self.check_auth():
            return

        if self.path == "/api/dovecot/status":
            self.send_json(200, {"message": "success"})
            return

        if self.path.startswith("/api/dovecot/"):
            email = self.path[len("/api/dovecot/") :]
            user = USERS.get(email)
            if user is None:
                self.send_json(404, {"message": "user not found"})
                return
            self.send_json(
                200,
                {
                    "message": "success",
                    "body": {
                        "user": email,
                        "mailCrypt": user["mailCrypt"],
                        "mailCryptPublicKey": user["mailCryptPublicKey"],
                        "quota": user["quota"],
                    },
                },
            )
            return

        self.send_json(404, {"message": "not found"})

    def do_POST(self):
        if not self.check_auth():
            return

        if self.path.startswith("/api/dovecot/"):
            email = self.path[len("/api/dovecot/") :]
            user = USERS.get(email)
            if user is None:
                self.send_json(404, {"message": "user not found"})
                return

            length = int(self.headers.get("Content-Length", 0))
            body = json.loads(self.rfile.read(length)) if length else {}
            password = body.get("password", "")

            if password != user["password"]:
                self.send_json(401, {"message": "authentication failed"})
                return

            self.send_json(
                200,
                {
                    "message": "success",
                    "body": {
                        "mailCrypt": user["mailCrypt"],
                        "mailCryptPrivateKey": user["mailCryptPrivateKey"],
                        "mailCryptPublicKey": user["mailCryptPublicKey"],
                    },
                },
            )
            return

        self.send_json(404, {"message": "not found"})


if __name__ == "__main__":
    port = int(sys.argv[1]) if len(sys.argv) > 1 else 80
    server = HTTPServer(("0.0.0.0", port), Handler)
    print(f"Dovecot API mock listening on :{port}")
    server.serve_forever()
