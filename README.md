# CI images for Nextcloud

:package: Containers used for Continous Integration jobs (automated testing)

## GitHub Container Registry

The images are automatic builds on GitHub actions. You can find them at https://github.com/orgs/nextcloud/packages?repo_name=docker-ci. The build is triggered using GitHub workflows.

### GitHub workflows
To trigger a build of a specific container, go to https://github.com/nextcloud/docker-ci/actions/workflows/docker.yml and press "Run Workflow". Enter the following information:

- Folder name (e.g. `client`)
- Tag name (e.g. `latest`)

This would result in the Dockerfile `/client/Dockerfile` being built and the binary being pushed to the GitHub Container Registry as `client:latest`.

## Docker Hub

The images are automatic builds on Docker Hub. You can find them at https://hub.docker.com/u/nextcloudci/. The build is only triggered if a git tag is set.

### Git tags

To trigger a build of a specific container the tag needs to be named like the folder followed by a dash and a version number. This means `translations-1` will only trigger a build of the translation container and will then build the container `nextcloudci/translations:translations-1`. 

Other example:

- git tag `php7.1-5` will only build `nextcloudci/php7.1:php7.1-5`

