# Annexx test app

Application de test Symfony (Live Components, Doctrine, Docker).

## Requirements

- Docker
- Docker Compose

## Installation

```bash
git clone <repo>
cd annexx_test_app
docker compose up -d --build

docker compose exec php composer install

docker compose exec php php bin/console doctrine:migrations:migrate

#optionnel : charger les fixtures
docker compose exec php php bin/console doctrine:fixtures:load
```

## emails
par defaut: MAILER_DSN=null://null
```
docker compose up -d
```

config sur .env ou .env.local : MAILER_DSN=smtp://mailpit:1025
accéder aux mails http://127.0.0.1:8025/

## envoyer la newsletter:
```
docker compose exec php php bin/console app:send-newsletter #id
```