FROM nextcloudci/android:androidBase-2

# Android 6 (API 23)
RUN sdkmanager --sdk_root=/opt/android-sdk-linux/ "platforms;android-23"
RUN sdkmanager --sdk_root=/opt/android-sdk-linux/ "system-images;android-23;google_apis;x86"
RUN (sleep 5; echo "no") | avdmanager create avd -n android -c 100M -k "system-images;android-23;google_apis;x86" --abi "google_apis/x86"
