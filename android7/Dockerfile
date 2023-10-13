FROM ghcr.io/nextcloud/continuous-integration-androidbase:3

# Android 7 (API 24)
RUN sdkmanager "platforms;android-24"
RUN sdkmanager "system-images;android-24;google_apis;x86"
RUN sdkmanager emulator
RUN (sleep 5; echo "no") | avdmanager create avd -n android -c 100M -k "system-images;android-24;google_apis;x86"
