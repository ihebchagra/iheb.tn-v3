const revision = String(new Date().getTime());
module.exports = {
  globDirectory: "public/",
  globPatterns: ["**/*.{woff,svg,webp,html,css,js}"],
  globIgnores: [
    "assets/pdf/**",
    "static/**",
  ],
  swDest: "public/sw.js",
  ignoreURLParametersMatching: [/^utm_/, /^fbclid$/],
  skipWaiting: true,
  clientsClaim: true,
  additionalManifestEntries: [{ url: "/offline?v=2", revision }],
  runtimeCaching: [
    // Cache-first strategy for Google Fonts stylesheets
    {
      urlPattern: /^https:\/\/fonts\.googleapis\.com/,
      handler: "CacheFirst",
      options: {
        cacheName: "google-fonts-stylesheets",
        expiration: {
          maxAgeSeconds: 60 * 60 * 24 * 30, // 30 days
        },
        cacheableResponse: { statuses: [0, 200] },
      },
    },
    // Cache-first strategy for Google Fonts webfonts
    {
      urlPattern: /^https:\/\/fonts\.gstatic\.com/,
      handler: "CacheFirst",
      options: {
        cacheName: "google-fonts-webfonts",
        expiration: {
          maxAgeSeconds: 60 * 60 * 24 * 30, // 1 year
        },
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
        expiration: {
          maxAgeSeconds: 60 * 60 * 24 * 7, // 7 days
        },
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
        return [
          "/", // sadly this is needed so when we first login the user gets the home page. otherwise there is a risk the user gets redirected
          "/statistiques",
        ].includes(url.pathname);
      },
      handler: "NetworkFirst",
      options: {
        cacheName: "network-first",
        expiration: {
          maxAgeSeconds: 60 * 60 * 24, // 1 day
        },
      },
    },
    // Stale-while-revalidate for everything else
    {
      urlPattern: /.*/,
      handler: "StaleWhileRevalidate",
      options: {
        cacheName: "default-cache",
        expiration: {
          maxAgeSeconds: 60 * 60 * 24 * 30, // 30 days instead of 1 day
        },
      },
    },
  ],
};
