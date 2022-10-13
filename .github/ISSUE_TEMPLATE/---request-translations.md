---
name: "\U0001F523 Request translations"
about: Request set up of translation syncing from transifex.
title: ''
labels: 'transifex'
assignees: ''

---

<!-- Just submit the form as is and afterwards work through the points and tick the boxes -->

# 👤 App repository

**To be done by you**

- [ ] Link to your repository: 
- [ ] If the app is personal repository
    - [ ] Invite `nextcloud-bot` with write permissions
    - [ ] Make sure all translated branches do **not** have branch protection enabled, see https://docs.nextcloud.com/server/latest/developer_manual/basics/front-end/l10n.html#setup-of-the-transifex-sync for more details
- [ ] Create file `l10n/.gitkeep` with empty content
- [ ] Add `.l10nignore` to exclude compiled JS files and thirdparty code, e.g. ignoring compiled javascript assets and composer PHP dependencies in the vendor/ directory:
```
js/
vendor/
```
- [ ] Create file `.tx/config` with the following content, replace `{{APPID}}` with your app id 3 times:
```ini
[main]
host     = https://www.transifex.com
lang_map = hu_HU: hu, nb_NO: nb, sk_SK: sk, th_TH: th, ja_JP: ja, bg_BG: bg, cs_CZ: cs, fi_FI: fi

[o:nextcloud:p:nextcloud:r:{{APPID}}]
file_filter = translationfiles/<lang>/{{APPID}}.po
source_file = translationfiles/templates/{{APPID}}.pot
source_lang = en
type        = PO
```

# 🏗️ Sysadmin team
- [ ] 👀 Ensure access:
    - [ ] Nextcloud-Org repository: Add `nextcloud-bot` with admin permissions
    - [ ] Other repositories:
        - [ ] Ensure the invite was accepted
        - [ ] Ensure the app certificate is linked to the same repository/author https://github.com/nextcloud/app-certificate-requests
- [ ] ⚙️ Ensure repository setup:
    - [ ] `.tx/config`
    - [ ] `l10n/.gitkeep`
    - [ ] `.l10ignore`
- [ ] ➕ Add `"Owner Repository",` into https://github.com/nextcloud/docker-ci/edit/master/translations/config.json
    - [ ] Pull request:
- [ ] 🏃Run action: https://github.com/nextcloud/docker-ci/actions/workflows/docker.yml
   1. Path: `translations-app`
   2. File: `Dockerfile`
   3. Suffix empty ``
   4. Tag `latest`
- [ ] 🔑 SSH into translation machine: `ssh root@transifex-sync.nextcloud.com`
- [ ] ↩️ Change dir: `cd /srv/docker-ci`
- [ ] ⬇️ Pull: `git pull origin master`
- [ ] 🧑‍💻 Run the first sync manually:

```sh
docker run -v /srv/cronie-data/transifexrc:/root/.transifexrc \
    -v /srv/cronie-data/gpg:/gpg \
    -v /srv/cronie-data/ssh/id_rsa:/root/.ssh/id_rsa \
    --rm -i ghcr.io/nextcloud/continuous-integration-translations-app \
    AUTHOR APPID
```


# 🔣 Translation team
- [ ] Transifex: New resource should appear
- [ ] Transifex: Do translations and check sync to repo
- [ ] Add resource to [wiki](https://help.nextcloud.com/t/list-of-resources-and-their-priority-for-translation/78312/)
