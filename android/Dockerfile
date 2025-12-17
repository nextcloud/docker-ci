FROM debian:trixie

RUN apt-get update && apt-get install -y apt-utils
RUN apt-get install -y ruby wget libxdamage1  libpulse0 locales openjdk-21-jdk qrencode git curl xz-utils && \
    apt-get autoremove -y && apt-get autoclean && apt-get clean && \
    rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*

RUN mkdir /opt/android-sdk-linux && \
    cd /opt/android-sdk-linux && \
    wget --output-document=android-sdk.zip https://dl.google.com/android/repository/commandlinetools-linux-13114758_latest.zip && \
    unzip android-sdk.zip && \
    rm -f android-sdk.zip && \
    mkdir cmdline-tools/latest && \
    cd cmdline-tools && \
    for file in $(ls | grep -v 'latest'); do mv $file latest; done

ENV SHELL /bin/bash
ENV ANDROID_HOME=/opt/android-sdk-linux/
ENV ANDROID_SDK_ROOT=/opt/android-sdk-linux/
ENV PATH=$PATH:/opt/android-sdk-linux/cmdline-tools/latest/bin/:/opt/android-sdk-linux/emulator/:/opt/android-sdk-linux/platform-tools/

RUN echo en_US.UTF-8 UTF-8 >> /etc/locale.gen
RUN locale-gen && update-locale LANG=en_US.UTF-8
ENV LANG=en_US.UTF-8

ADD gradle /gradle
ADD .gradle /root/.gradle

RUN yes | sdkmanager --licenses

RUN cd /gradle && \
    wget https://raw.githubusercontent.com/nextcloud/android/master/build.gradle.kts && \
    sed -i s"/.*NC_TEST.*//" build.gradle.kts && \
    mkdir -p app && \
    wget https://raw.githubusercontent.com/nextcloud/android/master/app/build.gradle.kts -O app/build.gradle.kts && \
    mkdir -p gradle && \
    wget https://raw.githubusercontent.com/nextcloud/android/master/gradle/libs.versions.toml -O gradle/libs.versions.toml && \
    wget https://raw.githubusercontent.com/nextcloud/android/master/settings.gradle.kts && \
    wget https://raw.githubusercontent.com/nextcloud/android/master/jacoco.gradle.kts  && \
    wget https://raw.githubusercontent.com/nextcloud/android/master/ndk.env  && \
    mkdir -p appscan

RUN yes | sdkmanager --update

# RUN cd /gradle && ./gradlew clean assemble assembleAndroidTest lint && \
#     ./gradlew clean

# RUN cd /gradle && \
#     wget https://raw.githubusercontent.com/nextcloud/android-library/master/build.gradle -O build.gradle && \
#     sed -i s"/.*NC_TEST.*//" build.gradle

RUN yes | sdkmanager --update

# RUN cd /gradle && ./gradlew clean assemble assembleAndroidTest lint && \
RUN cd /gradle && ./gradlew clean  && \
    ./gradlew clean && \
    rm -rf /root/.gradle/wrapper/dists/*/*/*.zip

RUN gem install xml-simple

RUN wget https://github.com/stedolan/jq/releases/download/jq-1.5/jq-linux32 -O /usr/bin/jq && chmod +x /usr/bin/jq

RUN sdkmanager "platform-tools"

EXPOSE 5037
WORKDIR /opt/workspace/
