FROM ghcr.io/nextcloud/continuous-integration-androidbase:1

# Android 5 (API 21)
RUN sdkmanager "platforms;android-21"
RUN sdkmanager "system-images;android-21;google_apis;x86"
RUN (sleep 5; echo "no") | avdmanager create avd -n android -c 100M -k "system-images;android-21;google_apis;x86" --abi "google_apis/x86"
