FROM jacekmarchwicki/android:java7-8-r25

RUN apt-get update && apt-get install -y libxdamage1 libxfixes3 libpulse0 && apt-get clean

ENV SHELL /bin/bash

ADD gradle /gradle
RUN cd /gradle && ./gradlew clean
