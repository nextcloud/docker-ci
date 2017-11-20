FROM jacekmarchwicki/android:java7-8

RUN apt-get update && apt-get install -y libxdamage1 libxfixes3 libpulse0 ruby && apt-get clean

RUN cd /opt && wget --output-document=android-sdk.zip https://dl.google.com/android/repository/sdk-tools-linux-3859397.zip && \
    unzip android-sdk.zip && \
    rm -f android-sdk.zip

ENV SHELL /bin/bash
ENV ANDROID_HOME=/opt/android-sdk-linux/

RUN locale-gen en_US.UTF-8 && update-locale LANG=en_US.UTF-8
ENV LANG=en_US.UTF-8

ADD gradle /gradle
ADD .gradle /root/

RUN yes | /opt/tools/bin/sdkmanager --sdk_root=/opt/android-sdk-linux/ --licenses

RUN cd /gradle && \
    wget https://raw.githubusercontent.com/nextcloud/android/master/build.gradle -O android.gradle && \
    wget https://raw.githubusercontent.com/nextcloud/android-library/master/build.gradle -O android-library.gradle && \
    sed '/^ [ ]*dependencies/Q' android.gradle >> build.gradle && \
    echo "    dependencies {" >> build.gradle && \
    sed -n '/^ .*dependencies/,/\}/p' android-library.gradle | grep -v dep | grep -v "}" >> build.gradle && \
    grep -A 999 "^ [ ]*dependencies" android.gradle  | sed '/^dependencies/Q' | grep -v "dependencies" >> build.gradle && \
    echo "dependencies {" >> build.gradle && \
    sed -n '/^dependencies/,/\}/p' android-library.gradle | grep -v dep | grep -v "}" >> build.gradle && \
    grep -A 999 "^dependencies" android.gradle | grep -v "dependencies" >> build.gradle && \
    sed -i '/.*com.google.*/s/^.*\/\///g' build.gradle && \
    sed -i '/.*touch-image-view.*/s/^/\/\//g' build.gradle

RUN cd /gradle && ./gradlew clean assembleGenericDebug assembleGenericDebugAndroidTest lintGenericDebug

RUN gem install xml-simple
