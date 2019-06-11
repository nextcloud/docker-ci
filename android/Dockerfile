FROM debian:stretch

RUN apt-get update && apt-get install -y software-properties-common
RUN apt-get install -y ruby wget libxdamage1 libgl1-mesa-glx libpulse0 locales unzip openjdk-8-jdk-headless curl qrencode && \
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
    wget https://raw.githubusercontent.com/nextcloud/android/master/build.gradle -O android.gradle && \
    wget https://raw.githubusercontent.com/nextcloud/android/master/gplay.gradle && \
    wget https://raw.githubusercontent.com/nextcloud/android-library/master/build.gradle -O android-library.gradle && \
    sed '/^ [ ]*dependencies/Q' android.gradle >> build.gradle && \
    echo "    dependencies {" >> build.gradle && \
    sed -n '/^ .*dependencies/,/\}/p' android-library.gradle | grep -v dep >> build.gradle && \
    grep -A 999 "^ [ ]*dependencies" android.gradle  | sed '/^dependencies/Q' | grep -v "dependencies" >> build.gradle && \
    echo "dependencies {" >> build.gradle && \
    sed -n '/^dependencies/,/\}/p' android-library.gradle | grep -v dep | grep -v "}" >> build.gradle && \
    grep -A 999 "^dependencies" android.gradle | grep -v "dependencies" >> build.gradle && \
    sed -i '/.*com.google.*/s/^.*\/\///g' build.gradle && \
    sed -i '/.*touch-image-view.*/s/^/\/\//g' build.gradle && \
    sed -i s'#minSdkVersion\ 14#minSdkVersion\ 18#' build.gradle && \
    sed -i s"/implementation 'com.github.tobiaskaminsky:android-job:v1.2.6.1'//" build.gradle && \
    sed -i s"/implementation 'com.afollestad:sectioned-recyclerview:0.5.0'//" build.gradle && \
    sed -i s"/.*NC_TEST.*//" build.gradle && \
    sed -i s"/compileOnly.*findbugs.*//" build.gradle

RUN yes | sdkmanager --sdk_root=/opt/android-sdk-linux/ --update

RUN cd /gradle && ./gradlew clean assemble assembleAndroidTest lint && \
    ./gradlew clean && \
    rm -rf /root/.gradle/wrapper/dists/*/*/*.zip

RUN gem install xml-simple

RUN wget https://github.com/stedolan/jq/releases/download/jq-1.5/jq-linux32 -O /usr/bin/jq && chmod +x /usr/bin/jq

RUN sdkmanager --sdk_root=/opt/android-sdk-linux/ "platforms;android-27" 
RUN sdkmanager --sdk_root=/opt/android-sdk-linux/ "platform-tools"

RUN sdkmanager --sdk_root=/opt/android-sdk-linux/ "system-images;android-27;google_apis;x86"
RUN (sleep 5; echo "no") | avdmanager create avd -n android-27 -c 100M -k "system-images;android-27;google_apis;x86" --abi "google_apis/x86"

WORKDIR /opt/workspace/
