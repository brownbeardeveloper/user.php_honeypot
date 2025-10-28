## Honeypot (PHP + Nginx)

Minimal web honeypot that logs IP/time/URI in a Fail2Ban-friendly format. Runs PHP behind an nginx reverse proxy on an internal Docker network. Only the exact path `/user.php` is exposed via nginx on localhost.

### Files
- `user.php` — writes lines to `/var/log/honeypot.log`
- `Dockerfile` — PHP 8.2 Apache image
- `docker-compose.yml` — `honeypot` + `nginx` services, internal network
- `nginx/honeypot.conf` — proxies only `/user.php`; basic hardening and rate limit

### Quick start

# IMPORTANT: Adjust UID/GID for container user
Before building, ensure the Docker services run under the same user and group IDs as the host account `honeypot-v1`.
This prevents permission issues when mounting volumes and ensures logs are written securely.

To check your host IDs:
id honeypot-v1

Then edit `docker-compose.yml`:
user: "111:111"   # replace with actual UID:GID from the command above


```bash
# Ensure correct directories (host side)
sudo mkdir -p /srv/honeypot-v1/logs/php
sudo mkdir -p /srv/honeypot-v1/logs/nginx
sudo mkdir -p /srv/honeypot-v1/service/nginx/logs
sudo mkdir -p /srv/honeypot-v1/service/honeypot_host_logs

# Create honeypot host log file
sudo touch /srv/honeypot-v1/service/honeypot_host_logs/honeypot.log
sudo chown honeypot-v1:honeypot-v1 /srv/honeypot-v1/service/honeypot_host_logs/honeypot.log
sudo chmod 0640 /srv/honeypot-v1/service/honeypot_host_logs/honeypot.log

# Build and run everything as honeypot user
sudo -u honeypot-v1 docker compose -f /srv/honeypot-v1/service/docker-compose.yml up -d --build

# Test endpoint
curl -v http://127.0.0.1:8080/user.php
```

### Notes
- Logs are written inside the container to `/var/log/honeypot.log` (bind-mounted from host).
- Only `/user.php` is proxied by nginx; other paths return 404.
- Keep this isolated. Do not run on a production host without strict separation.
- Consider log rotation and Fail2Ban on the honeypot log or nginx access log.


### Fail2Ban (ban any POST in honeypot access log)
Simple config setup

Adjust `logpath` to your host path, e.g. `./nginx/logs/honeypot_access.log` or `/var/log/nginx/honeypot_access.log`.

```bash
sudo cp nginx/nginx-honeypot-post.conf /etc/fail2ban/filter.d/nginx-honeypot-post.conf
sudo cp nginx/logs/nginx-honeypot-post.local /etc/fail2ban/jail.d/nginx-honeypot-post.local

sudo systemctl restart fail2ban
sudo fail2ban-client status nginx-honeypot-post
```

Allowlist (optional): add `ignoreip` in the jail file.

```conf
[nginx-honeypot-post]
ignoreip = 127.0.0.1/8 ::1 203.0.113.10 198.51.100.0/24
```


### Logrotate (honeypot access log)
Copy the provided rule and test. Adjust the path inside the file if your log lives elsewhere.

```bash
sudo cp nginx/logrotate-honeypot /etc/logrotate.d/honeypot
sudo logrotate -d /etc/logrotate.d/honeypot
```