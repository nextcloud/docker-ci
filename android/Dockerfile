FROM jacekmarchwicki/android:java7-8

RUN apt-get update && apt-get install -y libxdamage1 libxfixes3 libpulse0 ruby && apt-get clean

RUN cd /opt && wget --output-document=android-sdk.zip https://dl.google.com/android/repository/sdk-tools-linux-3859397.zip && \
    unzip android-sdk.zip && \
    rm -f android-sdk.zip

ENV SHELL /bin/bash
ENV ANDROID_HOME=/opt/android-sdk-linux/

ADD gradle /gradle
ADD licenses /opt/android-sdk-linux/licenses/

RUN cd /gradle && \
    wget https://raw.githubusercontent.com/nextcloud/android/master/build.gradle && \
    sed -i '/.*com.google.*/s/^.*\/\///g' build.gradle && \
    sed -i 's/annotationProcessor/implementation/g' build.gradle && \
    sed -i 's/testImplementation/implementation/g' build.gradle && \
    sed -i 's/androidTestImplementation/implementation/g' build.gradle

RUN cd /gradle && ./gradlew clean
