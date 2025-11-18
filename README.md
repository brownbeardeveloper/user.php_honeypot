# PHP Honeypot Logger

A lightweight honeypot that logs unauthorized access attempts to a fake admin login endpoint. Designed for security research and threat intelligence gathering.

## Overview

This honeypot presents a fake `user.php` admin login form that logs all access attempts including:
- Client IP addresses (with proper proxy detection)
- Request URIs and timestamps
- POST/GET requests

All attempts are logged to `service/honeypot.log` for analysis.

## Quick Start

```bash
docker compose up -d
tail -f service/honeypot.log -n 10 # views logs
```

The service runs on `localhost:8002` by default. Configure your nginx to expose this endpoint publicly at `/user.php`.

## Why Just Logging?

Initially tried syncing this with fail2ban for automatic IP banning, but it doesn't work when nginx-proxy handles the request first. fail2ban never sees the actual client connection, only nginx's internal forwarding. 

Yes, banning is still possible in other ways but since I'm using Cloudflare I've lost interest in maintaining that flow.

So this is purely for logging and research—monitor `service/honeypot.log` to see who's trying to break in.

## Log Format

```
==> service/honeypot.log <==
2025-11-18 19:12:42 honeypot: 172.21.0.1 uri="/user.php" username="admin" password="admin"
2025-11-18 19:12:54 honeypot: 172.21.0.1 uri="/user.php" username="root" password="root"
2025-11-18 19:13:01 honeypot: 172.21.0.1 uri="/user.php" username="andershansen" password="skarmhjarnan123"
```