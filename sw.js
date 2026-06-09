// sw.js - Service Worker para PIC Industrial con Push Notifications
const CACHE_NAME = 'pic-v3';
const OFFLINE_URL = '/proyecto/offline.html';

// ============================================
// ESTRATEGIAS DE CACHE
// ============================================

function isImage(url) {
  return /\.(png|jpg|jpeg|gif|webp|svg|ico)$/i.test(url);
}

function isStaticAsset(url) {
  return /\.(css|js|woff2?|ttf|eot|otf)$/i.test(url) ||
         url.includes('bootstrap') ||
         url.includes('font-awesome') ||
         url.includes('cdn.jsdelivr.net') ||
         url.includes('cdnjs.cloudflare.com');
}

function isPage(url) {
  return url.endsWith('.html') || url.endsWith('.php') || url === self.location.origin + '/' || url === self.location.origin + '/proyecto/';
}

function isSameOrigin(url) {
  return url.startsWith(self.location.origin);
}

// ============================================
// INSTALACION: cachear assets estaticos esenciales
// ============================================
self.addEventListener('install', function(event) {
  console.log('[SW] Instalando...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(function(cache) {
        return cache.addAll([
          OFFLINE_URL,
          '/proyecto/interfaz_usuario/pagina_modernizada.html',
          '/proyecto/interfaz_usuario/login.html',
          '/proyecto/interfaz_usuario/index.html',
          '/proyecto/panel_admin/panel_admin.php',
          '/proyecto/img/pic.png',
          '/proyecto/img/icon-192.png',
          '/proyecto/img/icon-512.png',
          'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css',
          'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
          'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js',
          'https://code.jquery.com/jquery-3.6.0.min.js'
        ]);
      })
      .then(function() {
        return self.skipWaiting();
      })
      .catch(function(error) {
        console.error('[SW] Error en cache inicial:', error);
      })
  );
});

// ============================================
// ACTIVACION: limpiar caches antiguos
// ============================================
self.addEventListener('activate', function(event) {
  console.log('[SW] Activando...');
  event.waitUntil(
    caches.keys().then(function(cacheNames) {
      return Promise.all(
        cacheNames.map(function(cacheName) {
          if (cacheName !== CACHE_NAME) {
            console.log('[SW] Eliminando cache antiguo:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(function() {
      return self.clients.claim();
    })
  );
});

// ============================================
// FETCH: estrategias de cache segun tipo
// ============================================
self.addEventListener('fetch', function(event) {
  var request = event.request;
  var url = request.url;

  // Ignorar requests no HTTP y no GET (no se pueden cachear POST, etc.)
  if (!url.startsWith('http')) return;
  if (request.method !== 'GET') return;

  // Estrategia: Network First para paginas (navegacion)
  if (request.mode === 'navigate') {
    event.respondWith(networkFirst(request));
    return;
  }

  // Estrategia: Cache First para assets estaticos (CSS, JS, fonts, imagenes)
  if (isStaticAsset(url) || isImage(url)) {
    event.respondWith(cacheFirst(request));
    return;
  }

  // Para todo lo demas: Network First con fallback a cache
  event.respondWith(networkFirst(request));
});

// ============================================
// Network First: intenta red, fallback a cache
// ============================================
function networkFirst(request) {
  return fetch(request)
    .then(function(response) {
      if (!response || !response.ok) {
        return response;
      }
      var responseClone = response.clone();
      caches.open(CACHE_NAME).then(function(cache) {
        cache.put(request, responseClone).catch(function(err) {
          console.warn('[SW] Error al cachear:', request.url, err);
        });
      }).catch(function(err) {
        console.warn('[SW] Error al abrir cache:', err);
      });
      return response;
    })
    .catch(function() {
      return caches.match(request).then(function(cached) {
        return cached || caches.match(OFFLINE_URL);
      });
    });
}

// ============================================
// Cache First: sirve desde cache, fallback a red
// ============================================
function cacheFirst(request) {
  return caches.match(request)
    .then(function(cached) {
      if (cached) {
        // Actualizar cache en segundo plano (stale-while-revalidate)
        fetch(request).then(function(response) {
          if (response && response.ok) {
            caches.open(CACHE_NAME).then(function(cache) {
              cache.put(request, response.clone());
            });
          }
        }).catch(function() {});
        return cached;
      }
      return fetch(request).then(function(response) {
        if (response && response.ok) {
          var responseClone = response.clone();
          caches.open(CACHE_NAME).then(function(cache) {
            cache.put(request, responseClone);
          });
        }
        return response;
      }).catch(function() {
        return new Response('Recurso no disponible offline', { status: 503 });
      });
    });
}

// ============================================
// NOTIFICACIONES PUSH
// ============================================

// Evento push - recibe la notificacion del servidor
self.addEventListener('push', function(event) {
  console.log('[SW] Push recibido:', event);

  var data = {};

  try {
    if (event.data) {
      data = event.data.json();
    }
  } catch(e) {
    data = {
      title: 'PIC Industrial',
      body: event.data ? event.data.text() : 'Novedades en nuestra tienda',
      icon: '/proyecto/img/pic.png',
      badge: '/proyecto/img/pic.png',
      url: '/proyecto/interfaz_usuario/pagina_modernizada.html'
    };
  }

  var options = {
    body: data.body || 'Nuevos productos disponibles',
    icon: data.icon || '/proyecto/img/pic.png',
    badge: data.badge || '/proyecto/img/pic.png',
    vibrate: [200, 100, 200],
    data: {
      url: data.url || '/proyecto/interfaz_usuario/pagina_modernizada.html',
      dateOfArrival: Date.now()
    },
    actions: [
      {
        action: 'ver',
        title: 'Ver ahora'
      },
      {
        action: 'cerrar',
        title: 'Cerrar'
      }
    ]
  };

  event.waitUntil(
    self.registration.showNotification(data.title || 'PIC Industrial', options)
  );
});

// Evento click en notificacion
self.addEventListener('notificationclick', function(event) {
  console.log('[SW] Click en notificacion:', event);

  event.notification.close();

  if (event.action === 'cerrar') {
    return;
  }

  var urlToOpen = (event.notification.data && event.notification.data.url) || '/proyecto/interfaz_usuario/pagina_modernizada.html';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true })
      .then(function(windowClients) {
        for (var i = 0; i < windowClients.length; i++) {
          var client = windowClients[i];
          if (client.url === urlToOpen && 'focus' in client) {
            return client.focus();
          }
        }
        if (clients.openWindow) {
          return clients.openWindow(urlToOpen);
        }
      })
  );
});

// Evento cuando se cierra la notificacion sin hacer click
self.addEventListener('notificationclose', function(event) {
  console.log('[SW] Notificacion cerrada:', event.notification);
});