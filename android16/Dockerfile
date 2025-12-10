FROM ghcr.io/nextcloud/continuous-integration-androidbase:5

# Android 16 (API 36)
RUN sdkmanager "platforms;android-36"
RUN sdkmanager "system-images;android-36;google_apis;x86_64"
RUN (sleep 5; echo "no") | avdmanager create avd -n android -c 100M -k "system-images;android-36;google_apis;x86_64"
