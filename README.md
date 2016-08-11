# CI images for Nextcloud

:package: Containers used for Continous Integration jobs (automated testing)

The images are automatic builds on Docker Hub. You can find them at https://hub.docker.com/u/nextcloudci/. The build is only triggered if a git tag is set.

## Git tags

To trigger a build of a specific container the tag needs to be named like the folder followed by a dash and a version number. This means `translations-1` will only trigger a build of the translation container and will then build the container `nextcloudci/translations:translations-1`. 

Other example:

- git tag `php7.1-5` will only build `nextcloudci/php7.1:php7.1-5`
