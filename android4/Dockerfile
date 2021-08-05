FROM ghcr.io/nextcloud/continuous-integration-androidbase:2

# Android 4 (API 16)
RUN sdkmanager "platforms;android-16"
RUN sdkmanager "system-images;android-16;google_apis;x86"
RUN (sleep 5; echo "no") | avdmanager create avd -n android -c 100M -k "system-images;android-16;google_apis;x86" --abi "google_apis/x86"
