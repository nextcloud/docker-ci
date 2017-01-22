# Transifex Bot

This is the bot to push and pull translations from and to Transifex.

## Requirements

Having a folder structure like this:

```
├── gpg
│   ├── nextcloud-bot.asc
│   └── nextcloud-bot.public.asc
├── ssh
│   ├── id_rsa
│   └── id_rsa.pub
└── transifexrc
```

## Run it

```bash
docker run \
  -v /path/to/transifexrc:/root/.transifexrc \
  -v /path/to/gpg:/gpg \
  -v /path/to/ssh/id_rsa:/root/.ssh/id_rsa \
  --rm -ti \
  nextcloudci/translations:1.0.8
```

This will run the `handleTranslations.sh` on current master of nextcloud/server.
