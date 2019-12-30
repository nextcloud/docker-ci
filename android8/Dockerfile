FROM nextcloudci/android:androidBase-2

# Android 8 (API 26)
RUN sdkmanager --sdk_root=/opt/android-sdk-linux/ "platforms;android-26"
RUN sdkmanager --sdk_root=/opt/android-sdk-linux/ "system-images;android-26;google_apis;x86"
RUN (sleep 5; echo "no") | avdmanager create avd -n android -c 100M -k "system-images;android-26;google_apis;x86" --abi "google_apis/x86"
