FROM debian:bookworm

RUN apt-get update && apt-get install -y apt-utils software-properties-common
RUN apt-get install -y ruby wget libxdamage1 libgl1-mesa-glx libpulse0 locales unzip curl qrencode git && \
    apt-get autoremove -y && apt-get autoclean && apt-get clean && \
    rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*

RUN cd /tmp/ && wget https://download.java.net/java/GA/jdk21.0.2/f2283984656d49d69e91c558476027ac/13/GPL/openjdk-21.0.2_linux-x64_bin.tar.gz && \
	tar xf openjdk-21.0.2_linux-x64_bin.tar.gz && mv jdk-21.0.2 /usr/local/jdk-21 && rm openjdk-21.0.2_linux-x64_bin.tar.gz

RUN mkdir /opt/android-sdk-linux && \
    cd /opt/android-sdk-linux && \
    wget --output-document=android-sdk.zip https://dl.google.com/android/repository/commandlinetools-linux-11076708_latest.zip && \
    unzip android-sdk.zip && \
    rm -f android-sdk.zip && \
    mkdir cmdline-tools/latest && \
    cd cmdline-tools && \
    for file in $(ls | grep -v 'latest'); do mv $file latest; done

ENV SHELL /bin/bash
ENV ANDROID_HOME=/opt/android-sdk-linux/
ENV ANDROID_SDK_ROOT=/opt/android-sdk-linux/
ENV PATH=$PATH:/opt/android-sdk-linux/cmdline-tools/latest/bin/:/opt/android-sdk-linux/emulator/:/opt/android-sdk-linux/platform-tools/:/usr/local/jdk-21/bin
ENV JAVA_HOME=/usr/local/jdk-21

RUN echo en_US.UTF-8 UTF-8 >> /etc/locale.gen
RUN locale-gen && update-locale LANG=en_US.UTF-8
ENV LANG=en_US.UTF-8

ADD gradle /gradle
ADD .gradle /root/.gradle

RUN yes | sdkmanager --licenses

RUN cd /gradle && \
    wget https://raw.githubusercontent.com/nextcloud/android/master/build.gradle -O build.gradle && \
    sed -i s"/.*NC_TEST.*//" build.gradle && \
    mkdir -p app && \
    wget https://raw.githubusercontent.com/nextcloud/android/master/app/build.gradle -O app/build.gradle

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
