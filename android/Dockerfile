FROM jacekmarchwicki/android:java8-r24-4-1

RUN cd /opt && /opt/tools/android-accept-licenses.sh "android-sdk-linux/tools/android update sdk --all --no-ui --filter sys-img-x86-android-23"

RUN apt-get update && apt-get install -y libxdamage1 libxfixes3 libpulse0 && apt-get clean

ENV SHELL /bin/bash

ADD gradle /gradle
RUN cd /gradle && ./gradlew clean