FROM devlikeapro/waha:latest

# Install Apache + PHP
RUN apt-get update && \
    apt-get install -y apache2 php libapache2-mod-php php-curl && \
    rm -rf /var/lib/apt/lists/*

# Copy webhook
COPY webhook.php /var/www/html/webhook.php
RUN mkdir -p /var/www/html/memory && chmod -R 777 /var/www/html/memory

EXPOSE 3000
EXPOSE 80

CMD service apache2 start && /entrypoint.sh
