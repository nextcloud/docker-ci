FROM ghcr.io/nextcloud/continuous-integration-androidbase:2

# Android 10 (API 29)
RUN sdkmanager "platforms;android-29"
RUN sdkmanager "system-images;android-29;google_apis;x86"
RUN (sleep 5; echo "no") | avdmanager create avd -n android -c 100M -k "system-images;android-29;google_apis;x86" --abi "google_apis/x86"
