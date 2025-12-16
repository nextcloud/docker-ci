FROM ghcr.io/nextcloud/continuous-integration-android-androidbase:latest

# Android 16 (API 36)
RUN sdkmanager "platforms;android-36" && sdkmanager "system-images;android-36;google_apis;x86_64" && (sleep 5; echo "no") | avdmanager create avd -n android -c 100M -k "system-images;android-36;google_apis;x86_64"
