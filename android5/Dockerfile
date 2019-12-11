FROM nextcloudci/android:androidBase-2

# Android 5 (API 21)
RUN sdkmanager --sdk_root=/opt/android-sdk-linux/ "platforms;android-21"
RUN sdkmanager --sdk_root=/opt/android-sdk-linux/ "system-images;android-21;google_apis;x86"
RUN (sleep 5; echo "no") | avdmanager create avd -n android -c 100M -k "system-images;android-21;google_apis;x86" --abi "google_apis/x86"
