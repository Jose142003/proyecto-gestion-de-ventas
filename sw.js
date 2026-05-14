// sw.js - Service Worker para PIC Industrial con Push Notifications
const CACHE_NAME = 'pic-v2';
const OFFLINE_URL = '/proyecto/offline.html';

// Archivos a cachear
const urlsToCache = [
  '/proyecto/offline.html',
  '/proyecto/interfaz%20usuario/pagina_modernizada.html',
  'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css'
];

// Instalación
self.addEventListener('install', function(event) {
  console.log('[SW] Instalando...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(function(cache) {
        console.log('[SW] Cacheando archivos...');
        return cache.addAll(urlsToCache);
      })
      .then(function() {
        return self.skipWaiting();
      })
      .catch(function(error) {
        console.error('[SW] Error en cache:', error);
      })
  );
});

// Activación
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

// Fetch
self.addEventListener('fetch', function(event) {
  if (!event.request.url.startsWith('http')) return;
  
  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request)
        .then(function(response) {
          if (response.ok) {
            var responseClone = response.clone();
            caches.open(CACHE_NAME).then(function(cache) {
              cache.put(event.request, responseClone);
            });
          }
          return response;
        })
        .catch(function() {
          return caches.match(OFFLINE_URL);
        })
    );
    return;
  }
  
  event.respondWith(
    caches.match(event.request)
      .then(function(response) {
        return response || fetch(event.request);
      })
      .catch(function() {
        return new Response('Recurso no disponible offline', { status: 503 });
      })
  );
});

// ============================================
// NOTIFICACIONES PUSH
// ============================================

// Evento push - recibe la notificación del servidor
self.addEventListener('push', function(event) {
  console.log('[SW] Push recibido:', event);
  
  let data = {};
  
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
      url: '/proyecto/interfaz%20usuario/pagina_modernizada.html'
    };
  }
  
  const options = {
    body: data.body || 'Nuevos productos disponibles',
    icon: data.icon || '/proyecto/img/pic.png',
    badge: data.badge || '/proyecto/img/pic.png',
    vibrate: [200, 100, 200],
    data: {
      url: data.url || '/proyecto/interfaz%20usuario/pagina_modernizada.html',
      dateOfArrival: Date.now()
    },
    actions: [
      {
        action: 'ver',
        title: '🔍 Ver ahora'
      },
      {
        action: 'cerrar',
        title: '❌ Cerrar'
      }
    ]
  };
  
  event.waitUntil(
    self.registration.showNotification(data.title || 'PIC Industrial', options)
  );
});

// Evento click en notificación
self.addEventListener('notificationclick', function(event) {
  console.log('[SW] Click en notificación:', event);
  
  event.notification.close();
  
  if (event.action === 'cerrar') {
    return;
  }
  
  const urlToOpen = event.notification.data?.url || '/proyecto/interfaz%20usuario/pagina_modernizada.html';
  
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

// Evento cuando se cierra la notificación sin hacer click
self.addEventListener('notificationclose', function(event) {
  console.log('[SW] Notificación cerrada:', event.notification);
});