# MTA-STS

Userli supports [MTA-STS (SMTP MTA Strict Transport Security)](https://datatracker.ietf.org/doc/html/rfc8461), a mechanism that allows mail domains to declare that they support TLS for inbound SMTP connections. 
Sending servers will refuse to deliver email without a valid TLS connection, protecting against downgrade and man-in-the-middle attacks.

MTA-STS is configured globally in the admin settings under **Settings → General**. The following settings are available:

- **MTA-STS Mode** — `testing` (default), `enforce`, or `none`
- **MX Hosts** — allowed MX hostnames, one per line (e.g. `mail.example.org`)
- **Max Age** — policy cache duration in seconds (default: 604800 = 1 week)

Userli serves the MTA-STS policy at `/.well-known/mta-sts.txt`, returning the policy for the domain derived from the `Host` header.

The controller extracts the domain from the `Host` header by stripping the `mta-sts.` prefix (e.g. `mta-sts.example.org` → `example.org`) and validates it against configured domains. 
If the domain is unknown, a `404` response is returned.

### Example Policy Response

```text
version: STSv1
mode: enforce
mx: mail.example.org
mx: backup.example.org
max_age: 604800
```

## DNS Configuration

MTA-STS requires two DNS records per domain.

### MTA-STS Subdomain

Point `mta-sts.{domain}` to your Userli server:

```text
mta-sts.example.org.  IN CNAME  users.example.org.
```

### MTA-STS TXT Record

Advertise MTA-STS support. The `id` value must be updated whenever the policy changes:

```text
_mta-sts.example.org.  IN TXT  "v=STSv1; id=20250227T000000;"
```

### TLSRPT (Optional)

Enable TLS reporting ([RFC 8460](https://datatracker.ietf.org/doc/html/rfc8460)) to receive reports about TLS delivery failures:

```text
_smtp._tls.example.org.  IN TXT  "v=TLSRPTv1; rua=mailto:tlsrpt@example.org;"
```

## TLS Certificate

The TLS certificate must be valid for `mta-sts.{domain}`. If you use Let's Encrypt, add the subdomain to your certificate:

```bash
certbot certonly -d users.example.org -d mta-sts.example.org
```

## Reverse Proxy Setup

The `mta-sts.{domain}` subdomain must be proxied to the Userli application. 
Ensure the `Host` header is forwarded so that Userli can identify the domain.

### Nginx

```nginx
server {
    listen 443 ssl;
    server_name mta-sts.example.org;

    ssl_certificate     /etc/letsencrypt/live/users.example.org/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/users.example.org/privkey.pem;

    location /.well-known/mta-sts.txt {
        proxy_pass http://upstream;
        proxy_set_header Host $host;
    }
}
```

### Apache 2

```apache
<VirtualHost *:443>
    ServerName mta-sts.example.org

    SSLEngine On
    SSLCertificateFile    /etc/letsencrypt/live/users.example.org/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/users.example.org/privkey.pem

    ProxyPreserveHost On
    ProxyPass "/.well-known/mta-sts.txt" "http://localhost:8000/.well-known/mta-sts.txt"
    ProxyPassReverse "/.well-known/mta-sts.txt" "http://localhost:8000/.well-known/mta-sts.txt"
</VirtualHost>
```

### Caddy

```caddy
mta-sts.example.org {
    handle /.well-known/mta-sts.txt {
        reverse_proxy localhost:8000
    }
    respond 404
}
```
