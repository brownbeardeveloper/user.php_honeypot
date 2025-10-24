## Honeypot (PHP + Nginx)

Minimal web honeypot that logs IP/time/URI in a Fail2Ban-friendly format. Runs PHP behind an nginx reverse proxy on an internal Docker network. Only the exact path `/user.php` is exposed via nginx on localhost.

### Files
- `user.php` — writes lines to `/var/log/honeypot.log`
- `Dockerfile` — PHP 8.2 Apache image
- `docker-compose.yml` — `honeypot` + `nginx` services, internal network
- `nginx/honeypot.conf` — proxies only `/user.php`; basic hardening and rate limit

### Quick start
```bash
mkdir -p honeypot_logs honeypot_host_logs nginx/logs
sudo touch honeypot_host_logs/honeypot.log
sudo chown root:adm honeypot_host_logs/honeypot.log
sudo chmod 0640 honeypot_host_logs/honeypot.log

docker compose up -d --build

curl -v http://127.0.0.1:8080/user.php
```

### Notes
- Logs are written inside the container to `/var/log/honeypot.log` (bind-mounted from host).
- Only `/user.php` is proxied by nginx; other paths return 404.
- Keep this isolated. Do not run on a production host without strict separation.
- Consider log rotation and Fail2Ban on the honeypot log or nginx access log.


