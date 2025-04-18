const revision = String(new Date().getTime());
module.exports = {
  globDirectory: "public/",
  globPatterns: ["**/*.{woff,svg,webp,html,css,js,json}"],
  globIgnores: ["assets/pdf/**", "static/**"],
  swDest: "public/sw.js",
  ignoreURLParametersMatching: [/^utm_/, /^fbclid$/],
  skipWaiting: true,
  clientsClaim: true,
  additionalManifestEntries: [{ url: "/offline?v=3", revision }],
  runtimeCaching: [
    // Cache-first strategy for Google Fonts stylesheets
    {
      urlPattern: /^https:\/\/fonts\.googleapis\.com/,
      handler: "CacheFirst",
      options: {
        cacheName: "google-fonts-stylesheets",
        cacheableResponse: { statuses: [0, 200] },
      },
    },
    // Cache-first strategy for Google Fonts webfonts
    {
      urlPattern: /^https:\/\/fonts\.gstatic\.com/,
      handler: "CacheFirst",
      options: {
        cacheName: "google-fonts-webfonts",
        cacheableResponse: { statuses: [0, 200] },
      },
    },
    // Cache-first strategy for CDN resources (unpkg and jsdelivr)
    {
      urlPattern:
        /^https:\/\/(unpkg\.com|cdn\.jsdelivr\.net|cdnjs\.cloudflare\.com)/,
      handler: "CacheFirst",
      options: {
        cacheName: "cdn-libraries",
        cacheableResponse: { statuses: [0, 200] },
      },
    },
    // Network-only for API POST requests
    {
      urlPattern: ({ url, request }) => {
        return request.method === "POST";
      },
      handler: "NetworkOnly",
    },
    // NetworkFirst for login, register, and profile routes
    {
      urlPattern: ({ url }) => {
        return ["/statistiques"].includes(url.pathname);
      },
      handler: "NetworkFirst",
      options: {
        cacheName: "network-first",
      },
    },
    // Stale-while-revalidate for everything else
    {
      urlPattern: /.*/,
      handler: "StaleWhileRevalidate",
      options: {
        cacheName: "default-cache",
      },
    },
  ],
};
