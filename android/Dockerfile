FROM debian:stretch

RUN apt-get update && apt-get install -y software-properties-common
RUN apt-get install -y ruby wget libxdamage1 libgl1-mesa-glx libpulse0 locales unzip openjdk-8-jdk-headless curl qrencode git && \
    apt-get autoremove -y && apt-get autoclean && apt-get clean && \
    rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*

RUN mkdir /opt/android-sdk-linux
RUN cd /opt/android-sdk-linux && wget --output-document=android-sdk.zip https://dl.google.com/android/repository/sdk-tools-linux-4333796.zip && \
    unzip android-sdk.zip && \
    rm -f android-sdk.zip

ENV SHELL /bin/bash
ENV ANDROID_HOME=/opt/android-sdk-linux/
ENV PATH=$PATH:/opt/android-sdk-linux/tools/bin/:/opt/android-sdk-linux/emulator/:/opt/android-sdk-linux/platform-tools/

RUN echo en_US.UTF-8 UTF-8 >> /etc/locale.gen
RUN locale-gen && update-locale LANG=en_US.UTF-8
ENV LANG=en_US.UTF-8

ADD gradle /gradle
ADD .gradle /root/.gradle

RUN yes | sdkmanager --sdk_root=/opt/android-sdk-linux/ --licenses

RUN cd /gradle && \
    wget https://raw.githubusercontent.com/nextcloud/android/master/build.gradle -O build.gradle && \
    wget https://raw.githubusercontent.com/nextcloud/android/master/gplay.gradle && \
    sed -i s"/.*NC_TEST.*//" build.gradle

RUN yes | sdkmanager --sdk_root=/opt/android-sdk-linux/ --update

RUN cd /gradle && ./gradlew clean assemble assembleAndroidTest lint && \
    ./gradlew clean
    
RUN cd /gradle && \
    wget https://raw.githubusercontent.com/nextcloud/android-library/master/build.gradle -O build.gradle && \
    sed -i s"/.*NC_TEST.*//" build.gradle

RUN yes | sdkmanager --sdk_root=/opt/android-sdk-linux/ --update

RUN cd /gradle && ./gradlew clean assemble assembleAndroidTest lint && \
    ./gradlew clean && \
    rm -rf /root/.gradle/wrapper/dists/*/*/*.zip

RUN gem install xml-simple

RUN wget https://github.com/stedolan/jq/releases/download/jq-1.5/jq-linux32 -O /usr/bin/jq && chmod +x /usr/bin/jq

RUN sdkmanager --sdk_root=/opt/android-sdk-linux/ "platform-tools"

EXPOSE 5037
WORKDIR /opt/workspace/
