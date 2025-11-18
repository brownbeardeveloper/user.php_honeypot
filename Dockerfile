FROM php:8.2-cli-alpine

# Create app directory
WORKDIR /var/www/html

# Ensure www-data user/group (UID/GID 33) exist on the CLI image
RUN addgroup -S -g 33 www-data 2>/dev/null || true \
 && adduser -S -G www-data -u 33 www-data 2>/dev/null || true \
 && mkdir -p /var/log/honeypot \
 && chown -R www-data:www-data /var/log/honeypot \
 && chmod 700 /var/log/honeypot

# Copy source code
COPY . .

# Drop root privileges
USER www-data

# Expose internal port
EXPOSE 8002

# Start lightweight PHP server
CMD ["php", "-S", "0.0.0.0:8002", "-t", "/var/www/html"]