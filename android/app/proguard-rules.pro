# WebView
-keepclassmembers class * {
    @android.webkit.JavascriptInterface <methods>;
}
-keepclassmembers class com.pic.webview.MainActivity$WebViewJavaScriptInterface {
    @android.webkit.JavascriptInterface <methods>;
}

# BuildConfig
-keep class com.pic.webview.BuildConfig { *; }

# Keep custom WebView client
-keep class com.pic.webview.** { *; }
