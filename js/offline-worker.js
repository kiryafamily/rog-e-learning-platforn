// offline-worker.js
// Service worker for offline access to lessons

const CACHE_NAME = 'raysofgrace-v1';
const API_CACHE_NAME = 'raysofgrace-api-v1';

// Assets to cache immediately
const PRECACHE_ASSETS = [
    '/',
    '/css/style.css',
    '/js/main.js',
    '/offline.html',
    '/images/logo.png',
    '/images/offline.svg'
];

// Install event - precache core assets
self.addEventListener('install', event => {
    console.log('Service Worker installing...');
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Pre-caching core assets');
                return cache.addAll(PRECACHE_ASSETS);
            })
            .then(() => self.skipWaiting())
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    console.log('Service Worker activating...');
    
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME && cacheName !== API_CACHE_NAME) {
                        console.log('Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch event - serve from cache or network
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);
    
    // Handle API requests
    if (url.pathname.startsWith('/api/')) {
        event.respondWith(handleAPIRequest(event.request));
        return;
    }
    
    // Handle lesson content (videos, PDFs)
    if (url.pathname.includes('/uploads/')) {
        event.respondWith(handleContentRequest(event.request));
        return;
    }
    
    // Handle page requests
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                // Return cached response if found
                if (response) {
                    return response;
                }
                
                // Otherwise fetch from network
                return fetch(event.request)
                    .then(response => {
                        // Don't cache non-successful responses
                        if (!response || response.status !== 200) {
                            return response;
                        }
                        
                        // Cache the response for future
                        const responseToCache = response.clone();
                        caches.open(CACHE_NAME)
                            .then(cache => {
                                cache.put(event.request, responseToCache);
                            });
                        
                        return response;
                    })
                    .catch(() => {
                        // If offline and page not in cache, show offline page
                        if (event.request.mode === 'navigate') {
                            return caches.match('/offline.html');
                        }
                    });
            })
    );
});

// Handle API requests with stale-while-revalidate strategy
function handleAPIRequest(request) {
    return caches.open(API_CACHE_NAME).then(cache => {
        return cache.match(request).then(cachedResponse => {
            const fetchPromise = fetch(request)
                .then(networkResponse => {
                    // Update cache with new response
                    cache.put(request, networkResponse.clone());
                    return networkResponse;
                })
                .catch(() => {
                    // If network fails and we have cached response, return it
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    // Otherwise return offline JSON
                    return new Response(
                        JSON.stringify({ 
                            error: 'offline', 
                            message: 'You are offline. Please check your connection.' 
                        }),
                        {
                            status: 503,
                            headers: { 'Content-Type': 'application/json' }
                        }
                    );
                });
            
            // Return cached response immediately, then update in background
            return cachedResponse || fetchPromise;
        });
    });
}

// Handle content requests (videos, PDFs) with cache-first strategy
function handleContentRequest(request) {
    return caches.open(CACHE_NAME).then(cache => {
        return cache.match(request).then(cachedResponse => {
            if (cachedResponse) {
                // Serve from cache and update in background
                fetch(request).then(networkResponse => {
                    cache.put(request, networkResponse);
                });
                return cachedResponse;
            }
            
            // Not in cache, try network
            return fetch(request).then(networkResponse => {
                if (networkResponse.status === 200) {
                    cache.put(request, networkResponse.clone());
                }
                return networkResponse;
            }).catch(() => {
                // If both cache and network fail, return placeholder
                if (request.url.includes('.mp4')) {
                    return new Response(
                        'Video offline. Please connect to internet to stream.',
                        { status: 503, headers: { 'Content-Type': 'text/plain' } }
                    );
                }
                return new Response(
                    'Content offline',
                    { status: 503, headers: { 'Content-Type': 'text/plain' } }
                );
            });
        });
    });
}

// Handle background sync for offline actions
self.addEventListener('sync', event => {
    if (event.tag === 'sync-progress') {
        event.waitUntil(syncProgress());
    } else if (event.tag === 'sync-quiz') {
        event.waitUntil(syncQuizResults());
    }
});

// Sync progress data when back online
function syncProgress() {
    return getIndexedDB('progress').then(progressData => {
        const promises = progressData.map(item => {
            return fetch('/api/sync-progress.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(item)
            }).then(response => {
                if (response.ok) {
                    return removeFromIndexedDB('progress', item.id);
                }
            });
        });
        return Promise.all(promises);
    });
}

// Sync quiz results when back online
function syncQuizResults() {
    return getIndexedDB('quiz-results').then(quizData => {
        const promises = quizData.map(item => {
            return fetch('/api/sync-quiz.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(item)
            }).then(response => {
                if (response.ok) {
                    return removeFromIndexedDB('quiz-results', item.id);
                }
            });
        });
        return Promise.all(promises);
    });
}

// Helper: Get data from IndexedDB
function getIndexedDB(storeName) {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('raysofgrace-offline', 1);
        
        request.onerror = reject;
        request.onsuccess = event => {
            const db = event.target.result;
            const transaction = db.transaction(storeName, 'readonly');
            const store = transaction.objectStore(storeName);
            const getAll = store.getAll();
            
            getAll.onsuccess = () => resolve(getAll.result);
            getAll.onerror = reject;
        };
        
        request.onupgradeneeded = event => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains('progress')) {
                db.createObjectStore('progress', { keyPath: 'id' });
            }
            if (!db.objectStoreNames.contains('quiz-results')) {
                db.createObjectStore('quiz-results', { keyPath: 'id' });
            }
        };
    });
}

// Helper: Remove from IndexedDB
function removeFromIndexedDB(storeName, id) {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('raysofgrace-offline', 1);
        
        request.onsuccess = event => {
            const db = event.target.result;
            const transaction = db.transaction(storeName, 'readwrite');
            const store = transaction.objectStore(storeName);
            const deleteRequest = store.delete(id);
            
            deleteRequest.onsuccess = resolve;
            deleteRequest.onerror = reject;
        };
    });
}

// Listen for messages from the page
self.addEventListener('message', event => {
    if (event.data.type === 'CACHE_LESSON') {
        // Cache specific lesson for offline
        caches.open(CACHE_NAME).then(cache => {
            cache.addAll(event.data.urls);
        });
    }
    
    if (event.data.type === 'REMOVE_FROM_CACHE') {
        // Remove lesson from cache
        caches.open(CACHE_NAME).then(cache => {
            cache.delete(event.data.url);
        });
    }
});

// Push notification handling
self.addEventListener('push', event => {
    const data = event.data.json();
    
    const options = {
        body: data.body,
        icon: '/images/icon-192.png',
        badge: '/images/badge.png',
        vibrate: [200, 100, 200],
        data: {
            url: data.url
        },
        actions: [
            {
                action: 'view',
                title: 'View'
            },
            {
                action: 'close',
                title: 'Close'
            }
        ]
    };
    
    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// Notification click handling
self.addEventListener('notificationclick', event => {
    event.notification.close();
    
    if (event.action === 'view') {
        event.waitUntil(
            clients.openWindow(event.notification.data.url)
        );
    }
});

// Periodic background sync (if supported)
self.addEventListener('periodicsync', event => {
    if (event.tag === 'update-cache') {
        event.waitUntil(updateCache());
    }
});

// Update cache periodically
function updateCache() {
    return caches.open(CACHE_NAME).then(cache => {
        return fetch('/api/lessons.php').then(response => {
            return response.json().then(lessons => {
                const urls = lessons.map(lesson => lesson.url);
                return cache.addAll(urls);
            });
        });
    });
}

// Log service worker status
console.log('Service Worker loaded');