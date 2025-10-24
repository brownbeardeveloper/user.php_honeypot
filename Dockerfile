FROM php:8.2-apache

# Minimal utilities (optional)
RUN apt-get update && apt-get install -y --no-install-recommends \
    procps \
 && rm -rf /var/lib/apt/lists/*

COPY user.php /var/www/html/user.php

# Internal log dir (writable by www-data)
RUN mkdir -p /var/www/html/honeypot_logs \
 && chown www-data:www-data /var/www/html/honeypot_logs \
 && chmod 0700 /var/www/html/honeypot_logs

# Prepare honeypot log (host bind-mount preferred)
RUN touch /var/log/honeypot.log && chown root:adm /var/log/honeypot.log || true

EXPOSE 80

# Default CMD from base image (Apache)
