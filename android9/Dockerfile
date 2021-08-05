FROM ghcr.io/nextcloud/continuous-integration-androidbase:1

# Android 9 (API 28)
RUN sdkmanager "platforms;android-28"
RUN sdkmanager "system-images;android-28;google_apis;x86"
RUN (sleep 5; echo "no") | avdmanager create avd -n android -c 100M -k "system-images;android-28;google_apis;x86" --abi "google_apis/x86"
