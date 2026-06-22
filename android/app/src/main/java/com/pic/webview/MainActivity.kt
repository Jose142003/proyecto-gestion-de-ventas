package com.pic.webview

import android.annotation.SuppressLint
import android.app.AlertDialog
import android.content.Context
import android.content.Intent
import android.content.SharedPreferences
import android.graphics.Bitmap
import android.net.ConnectivityManager
import android.net.NetworkCapabilities
import android.net.Uri
import android.os.Build
import android.os.Bundle
import android.os.Handler
import android.os.Looper
import android.view.KeyEvent
import android.view.View
import android.webkit.*
import android.widget.*
import android.widget.Toast.LENGTH_SHORT
import androidx.appcompat.app.AppCompatActivity
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout

@SuppressLint("SetJavaScriptEnabled")
class MainActivity : AppCompatActivity() {

    companion object {
        private const val PREFS_NAME = "pic_prefs"
        private const val KEY_APP_URL = "app_url"
        private const val KEY_PUSH_ENABLED = "push_enabled"
    }

    private lateinit var webView: WebView
    private lateinit var swipeRefresh: SwipeRefreshLayout
    private lateinit var progressBar: ProgressBar
    private lateinit var errorLayout: LinearLayout
    private lateinit var txtError: TextView
    private lateinit var sharedPrefs: SharedPreferences

    private var currentUrl: String = ""
    private var exitTimer: Handler? = null
    private var backPressCount = 0

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)

        sharedPrefs = getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
        currentUrl = sharedPrefs.getString(KEY_APP_URL, BuildConfig.APP_URL) ?: BuildConfig.APP_URL

        initViews()
        setupWebView()
        setupSwipeRefresh()
        setupErrorView()
        if (savedInstanceState == null) {
            loadUrl(currentUrl)
        }
    }

    override fun onSaveInstanceState(outState: Bundle) {
        super.onSaveInstanceState(outState)
        webView.saveState(outState)
    }

    override fun onRestoreInstanceState(savedInstanceState: Bundle) {
        super.onRestoreInstanceState(savedInstanceState)
        webView.restoreState(savedInstanceState)
    }

    override fun onResume() {
        super.onResume()
        webView.onResume()
    }

    override fun onPause() {
        super.onPause()
        webView.onPause()
    }

    override fun onDestroy() {
        exitTimer?.removeCallbacksAndMessages(null)
        webView.destroy()
        super.onDestroy()
    }

    override fun onKeyDown(keyCode: Int, event: KeyEvent?): Boolean {
        if (keyCode == KeyEvent.KEYCODE_BACK) {
            if (webView.canGoBack()) {
                webView.goBack()
                return true
            }
            if (backPressCount == 0) {
                backPressCount = 1
                Toast.makeText(this, "Presiona atrás otra vez para salir", LENGTH_SHORT).show()
                exitTimer = Handler(Looper.getMainLooper())
                exitTimer?.postDelayed({ backPressCount = 0 }, 2000)
                return true
            }
            finishAffinity()
            return true
        }
        return super.onKeyDown(keyCode, event)
    }

    private fun initViews() {
        setContentView(
            SwipeRefreshLayout(this).apply {
                id = R.id.swipe_refresh
                layoutParams = ViewGroup.LayoutParams(
                    ViewGroup.LayoutParams.MATCH_PARENT,
                    ViewGroup.LayoutParams.MATCH_PARENT
                )
                addView(
                    RelativeLayout(this@MainActivity).apply {
                        layoutParams = ViewGroup.LayoutParams(
                            ViewGroup.LayoutParams.MATCH_PARENT,
                            ViewGroup.LayoutParams.MATCH_PARENT
                        )
                        addView(
                            ProgressBar(this@MainActivity, null, android.R.attr.progressBarStyleHorizontal).apply {
                                id = R.id.progress_bar
                                layoutParams = RelativeLayout.LayoutParams(
                                    ViewGroup.LayoutParams.MATCH_PARENT,
                                    6.dpToPx()
                                ).apply { addRule(RelativeLayout.ALIGN_PARENT_TOP) }
                                max = 100
                                progressDrawable = androidx.appcompat.content.res.AppCompatResources
                                    .getDrawable(this@MainActivity, R.drawable.progress_bar)
                            }
                        )
                        addView(
                            WebView(this@MainActivity).apply {
                                id = R.id.webview
                                layoutParams = RelativeLayout.LayoutParams(
                                    ViewGroup.LayoutParams.MATCH_PARENT,
                                    ViewGroup.LayoutParams.MATCH_PARENT
                                ).apply { addRule(RelativeLayout.BELOW, R.id.progress_bar) }
                            }
                        )
                        addView(
                            LinearLayout(this@MainActivity).apply {
                                id = R.id.error_layout
                                visibility = View.GONE
                                gravity = android.view.Gravity.CENTER
                                orientation = LinearLayout.VERTICAL
                                layoutParams = RelativeLayout.LayoutParams(
                                    ViewGroup.LayoutParams.MATCH_PARENT,
                                    ViewGroup.LayoutParams.MATCH_PARENT
                                )
                                addView(
                                    ImageView(this@MainActivity).apply {
                                        setImageDrawable(
                                            androidx.appcompat.content.res.AppCompatResources
                                                .getDrawable(this@MainActivity, android.R.drawable.ic_dialog_alert)
                                        )
                                        layoutParams = LinearLayout.LayoutParams(
                                            96.dpToPx(), 96.dpToPx()
                                        ).apply { setMargins(0, 0, 0, 24.dpToPx()) }
                                    }
                                )
                                addView(
                                    TextView(this@MainActivity).apply {
                                        id = R.id.txt_error
                                        text = getString(R.string.error_connection)
                                        textSize = 16f
                                        gravity = android.view.Gravity.CENTER
                                        layoutParams = LinearLayout.LayoutParams(
                                            ViewGroup.LayoutParams.WRAP_CONTENT,
                                            ViewGroup.LayoutParams.WRAP_CONTENT
                                        ).apply { setMargins(32.dpToPx(), 0, 32.dpToPx(), 24.dpToPx()) }
                                    }
                                )
                                addView(
                                    Button(this@MainActivity).apply {
                                        text = getString(R.string.button_reload)
                                        setOnClickListener { loadUrl(currentUrl) }
                                    }
                                )
                            }
                        )
                    }
                )
            }
        )

        swipeRefresh = findViewById(R.id.swipe_refresh)
        webView = findViewById(R.id.webview)
        progressBar = findViewById(R.id.progress_bar)
        errorLayout = findViewById(R.id.error_layout)
        txtError = findViewById(R.id.txt_error)
    }

    private fun setupWebView() {
        webView.apply {
            settings.apply {
                javaScriptEnabled = true
                domStorageEnabled = true
                databaseEnabled = true
                allowFileAccess = false
                allowContentAccess = false
                setSupportMultipleWindows(false)
                javaScriptCanOpenWindowsAutomatically = false
                useWideViewPort = true
                loadWithOverviewMode = true
                builtInZoomControls = false
                displayZoomControls = false
                setSupportZoom(true)
                allowFileAccessFromFileURLs = false
                allowUniversalAccessFromFileURLs = false
                cacheMode = WebSettings.LOAD_DEFAULT
                userAgentString = settings.userAgentString + " PIC-Android/1.0"
            }

            webViewClient = object : WebViewClient() {
                override fun onPageStarted(view: WebView?, url: String?, favicon: Bitmap?) {
                    super.onPageStarted(view, url, favicon)
                    currentUrl = url ?: currentUrl
                    errorLayout.visibility = View.GONE
                    webView.visibility = View.VISIBLE
                }

                override fun onPageFinished(view: WebView?, url: String?) {
                    super.onPageFinished(view, url)
                    swipeRefresh.isRefreshing = false
                }

                override fun onReceivedError(
                    view: WebView?,
                    request: WebResourceRequest?,
                    error: WebResourceError?
                ) {
                    if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
                        if (error?.primaryError == WebViewClient.ERROR_HOST_LOOKUP ||
                            error?.primaryError == WebViewClient.ERROR_CONNECT ||
                            error?.primaryError == WebViewClient.ERROR_TIMEOUT
                        ) {
                            showError(getString(R.string.error_connection))
                        }
                    }
                }

                @Deprecated("Deprecated in API 23")
                override fun onReceivedError(
                    view: WebView?,
                    errorCode: Int,
                    description: String?,
                    failingUrl: String?
                ) {
                    if (errorCode == WebViewClient.ERROR_HOST_LOOKUP ||
                        errorCode == WebViewClient.ERROR_CONNECT ||
                        errorCode == WebViewClient.ERROR_TIMEOUT
                    ) {
                        showError(getString(R.string.error_connection))
                    }
                }

                override fun shouldOverrideUrlLoading(
                    view: WebView?,
                    request: WebResourceRequest?
                ): Boolean {
                    val url = request?.url?.toString() ?: return false
                    if (url.startsWith("tel:") || url.startsWith("mailto:") || url.startsWith("whatsapp:")) {
                        startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(url)))
                        return true
                    }
                    return false
                }
            }

            webChromeClient = object : WebChromeClient() {
                override fun onProgressChanged(view: WebView?, newProgress: Int) {
                    progressBar.progress = newProgress
                    progressBar.visibility = if (newProgress < 100) View.VISIBLE else View.GONE
                }

                override fun onReceivedTitle(view: WebView?, title: String?) {
                    supportActionBar?.title = title ?: getString(R.string.app_name)
                }
            }

            @Suppress("DEPRECATION")
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                webView.setWebViewRendererClient(object : WebViewRendererClient() {
                    override fun onRendererResponsive(
                        view: WebView?,
                        renderer: WebViewRenderer?
                    ) {}
                    override fun onRendererUnresponsive(
                        view: WebView?,
                        renderer: WebViewRenderer?
                    ) {
                        runOnUiThread {
                            Toast.makeText(
                                this@MainActivity,
                                "La aplicación no responde. Recargando...",
                                LENGTH_SHORT
                            ).show()
                            view?.reload()
                        }
                    }
                })
            }

            addJavascriptInterface(
                object {
                    @JavascriptInterface
                    fun getPushEnabled(): Boolean = sharedPrefs.getBoolean(KEY_PUSH_ENABLED, false)

                    @JavascriptInterface
                    fun getAppVersion(): String = BuildConfig.VERSION_NAME
                },
                "AndroidBridge"
            )
        }
    }

    private fun setupSwipeRefresh() {
        swipeRefresh.apply {
            setColorSchemeResources(R.color.primary, R.color.accent)
            setOnRefreshListener {
                if (isNetworkAvailable()) {
                    webView.reload()
                } else {
                    swipeRefresh.isRefreshing = false
                    showError(getString(R.string.error_no_internet))
                }
            }
        }
    }

    private fun setupErrorView() {
        errorLayout.setOnClickListener { loadUrl(currentUrl) }
    }

    private fun loadUrl(url: String) {
        if (!isNetworkAvailable()) {
            showError(getString(R.string.error_no_internet))
            return
        }
        errorLayout.visibility = View.GONE
        webView.visibility = View.VISIBLE

        val finalUrl = if (!url.startsWith("http://") && !url.startsWith("https://")) {
            "http://$url"
        } else url

        currentUrl = finalUrl
        webView.loadUrl(finalUrl)
    }

    private fun showError(message: String) {
        webView.visibility = View.GONE
        errorLayout.visibility = View.VISIBLE
        txtError.text = message
        swipeRefresh.isRefreshing = false
    }

    private fun isNetworkAvailable(): Boolean {
        val cm = getSystemService(Context.CONNECTIVITY_SERVICE) as ConnectivityManager
        val network = cm.activeNetwork ?: return false
        val capabilities = cm.getNetworkCapabilities(network) ?: return false
        return capabilities.hasCapability(NetworkCapabilities.NET_CAPABILITY_INTERNET)
    }

    fun showSettings() {
        val inflater = layoutInflater
        val dialogView = inflater.inflate(R.layout.dialog_settings, null)
        val inputUrl = dialogView.findViewById<EditText>(R.id.input_url)
        inputUrl.setText(currentUrl)

        AlertDialog.Builder(this)
            .setTitle("Configuración")
            .setView(dialogView)
            .setPositiveButton("Guardar") { _, _ ->
                val newUrl = inputUrl.text.toString().trim()
                if (newUrl.isNotEmpty()) {
                    sharedPrefs.edit().putString(KEY_APP_URL, newUrl).apply()
                    loadUrl(newUrl)
                }
            }
            .setNegativeButton("Cancelar", null)
            .show()
    }

    private fun Int.dpToPx(): Int =
        (this * resources.displayMetrics.density).toInt()
}
