FROM ghcr.io/nextcloud/continuous-integration-androidbase:3

# Android 14 (API 34)
RUN sdkmanager "platforms;android-34"
RUN sdkmanager "system-images;android-34;google_apis;x86_64"
RUN (sleep 5; echo "no") | avdmanager create avd -n android -c 100M -k "system-images;android-34;google_apis;x86_64"
