FROM jacekmarchwicki/android:java7-8

RUN apt-get update && apt-get install -y libxdamage1 libxfixes3 libpulse0 ruby qt5-default
RUN apt-get autoremove -y && apt-get autoclean && apt-get clean

RUN mkdir /opt/android-sdk-linux
RUN cd /opt/android-sdk-linux && wget --output-document=android-sdk.zip https://dl.google.com/android/repository/sdk-tools-linux-3859397.zip && \
    unzip android-sdk.zip && \
    rm -f android-sdk.zip

ENV SHELL /bin/bash
ENV ANDROID_HOME=/opt/android-sdk-linux/
ENV PATH=$PATH:/opt/android-sdk-linux/tools/bin/:/opt/android-sdk-linux/emulator/:/opt/android-sdk-linux/platform-tools/

RUN locale-gen en_US.UTF-8 && update-locale LANG=en_US.UTF-8
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
    sed -n '/^ .*dependencies/,/\}/p' android-library.gradle | grep -v dep | grep -v "}" >> build.gradle && \
    grep -A 999 "^ [ ]*dependencies" android.gradle  | sed '/^dependencies/Q' | grep -v "dependencies" >> build.gradle && \
    echo "dependencies {" >> build.gradle && \
    sed -n '/^dependencies/,/\}/p' android-library.gradle | grep -v dep | grep -v "}" >> build.gradle && \
    grep -A 999 "^dependencies" android.gradle | grep -v "dependencies" >> build.gradle && \
    sed -i '/.*com.google.*/s/^.*\/\///g' build.gradle && \
    sed -i '/.*touch-image-view.*/s/^/\/\//g' build.gradle

RUN cd /gradle && ./gradlew clean assembleGenericDebug assembleGenericDebugAndroidTest lintGenericDebug

RUN gem install xml-simple

RUN wget https://github.com/stedolan/jq/releases/download/jq-1.5/jq-linux32 -O /usr/bin/jq && chmod +x /usr/bin/jq

RUN echo "y" | sdkmanager --sdk_root=/opt/android-sdk-linux/ --update
RUN sdkmanager --sdk_root=/opt/android-sdk-linux/ "platforms;android-27" 
RUN sdkmanager --sdk_root=/opt/android-sdk-linux/ "platform-tools"

RUN sdkmanager --sdk_root=/opt/android-sdk-linux/ "system-images;android-27;google_apis;x86"
RUN (sleep 5; echo "no") | avdmanager create avd -n android-27 -c 100M -k "system-images;android-27;google_apis;x86" --abi "google_apis/x86"
