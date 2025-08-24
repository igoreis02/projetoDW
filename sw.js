// MUDAMOS A VERSÃO DO CACHE PARA FORÇAR A ATUALIZAÇÃO
const CACHE_NAME = 'deltaway-cache-v2'; 

// Lista de arquivos com caminhos relativos (corrigidos)
const urlsToCache = [
  '.',
  './index.html',
  './menu.php',
  './css/style.css',
  './imagens/logo.png',
  './imagens/favicon.png'
  // Adicione outros arquivos essenciais aqui com './' no início
];

// Evento de Instalação: Salva os arquivos novos em cache
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Cache aberto');
        return cache.addAll(urlsToCache);
      })
  );
});

// Evento Activate: Limpa os caches antigos
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          // Se o nome do cache não for o atual, ele será deletado
          if (cacheName !== CACHE_NAME) {
            console.log('Limpando cache antigo:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

// Evento de Fetch: Serve os arquivos do cache
self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        return response || fetch(event.request);
      })
  );
});