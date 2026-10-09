FROM php:8.4-cli-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends python3 libsqlite3-dev \
    && docker-php-ext-install pdo_mysql pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /app
COPY . .
RUN mkdir -p storage/exports storage/reports
EXPOSE 8000
CMD ["sh", "docker/start.sh"]
