<script>
(function () {
  if (window.progressBarInitialized) return;
  window.progressBarInitialized = true;

  const progressBar = {
    start: () => {
      document.documentElement.style.setProperty("--progress-opacity", "100");
      document.documentElement.style.setProperty("--progress-width", "30%");
    },

    progress: () => {
    },

    complete: () => {
      document.documentElement.style.setProperty("--progress-width", "100%");

      setTimeout(() => {
        document.documentElement.style.setProperty("--progress-opacity", "0");
        setTimeout(() => {
          document.documentElement.style.setProperty("--progress-width", "0%");
        }, 300);
      }, 300);
    },

    error: (event) => {
      document.documentElement.style.setProperty("--progress-opacity", "0");

      setTimeout(() => {
        document.documentElement.style.setProperty("--progress-width", "0%");
        const xhr = event?.detail?.xhr;
        if (xhr && xhr.status >= 300 && xhr.status < 400) {
          const redirectUrl = xhr.getResponseHeader("Location");
          if (redirectUrl) {
            htmx
              .ajax("GET", redirectUrl, {
                target: "#content",
                swap: "outerHTML scroll:top",
                select: "#content",
              })
              .then(() => {
                window.scrollTo({ top: 0 });
              });
            return;
          }
        }

        htmx
          .ajax("GET", "/offline?v=3", {
            target: "#content",
            swap: "outerHTML scroll:top",
            select: "#content",
          })
          .then(() => {
            window.scrollTo({ top: 0 });
          });
      }, 300);
    },

    abort: () => {
      document.documentElement.style.setProperty("--progress-opacity", "0");

      setTimeout(() => {
        document.documentElement.style.setProperty("--progress-width", "0%");
      }, 300);
    },
  };

  window.progressBarHandlers = {
    start: progressBar.start,
    progress: progressBar.progress,
    complete: progressBar.complete,
    error: progressBar.error,
    abort: progressBar.abort,
  };

  // Request lifecycle event handlers - Only add once
  window.addEventListener(
    "htmx:beforeRequest",
    window.progressBarHandlers.start
  );
  window.addEventListener(
    "htmx:xhr:progress",
    window.progressBarHandlers.progress
  );
  window.addEventListener(
    "htmx:afterOnLoad",
    window.progressBarHandlers.complete
  );
  window.addEventListener(
    "htmx:responseError",
    window.progressBarHandlers.error
  );
  window.addEventListener("htmx:sendError", window.progressBarHandlers.error);
  window.addEventListener("htmx:timeout", window.progressBarHandlers.error);
  window.addEventListener("htmx:swapError", window.progressBarHandlers.error);
  window.addEventListener("htmx:xhr:abort", window.progressBarHandlers.abort);
})();

// Add flag to track if the page was loaded directly
// Update page title and URL based on content metadata
function setMetaData() {
  window.scrollTo({
    top: 0,
  });

  const metadataElement = document.getElementById("metadata");
  if (!metadataElement) return;

  document.title = metadataElement.innerText;
  const currentPath = window.location.pathname;

  if (currentPath !== "/casfm-viewer" && window.location.hash) {
    history.replaceState(
      { url: window.location.pathname },
      document.title,
      window.location.href.split("#")[0]
    );
  }

  let href = metadataElement.getAttribute("href");
  href += window.location.hash;

  if (href !== currentPath) {
      history.pushState({ url: href }, document.title, href);
  }
  logPageView(href);

  const isIphone = /iPhone/i.test(navigator.userAgent);
  const isStandalone = window.matchMedia("(display-mode: standalone)").matches;

  if (isIphone && !isStandalone) {
    window.installType = "iphone";
    document.documentElement.style.setProperty("--show-install", "block");
    if (!JSON.parse(localStorage.getItem("never_show"))) {
      document.documentElement.style.setProperty(
        "--show-install-banner",
        "flex"
      );
    }
  } else if (window.installEvent !== undefined) {
    if (!isStandalone) {
      document.documentElement.style.setProperty("--show-install", "block");
      if (!JSON.parse(localStorage.getItem("never_show"))) {
        document.documentElement.style.setProperty(
          "--show-install-banner",
          "flex"
        );
      }
    }
  } else {
    window.addEventListener("beforeinstallprompt", (event) => {
      event.preventDefault();

      if (!isStandalone) {
        document.documentElement.style.setProperty("--show-install", "block");
        if (!JSON.parse(localStorage.getItem("never_show"))) {
          document.documentElement.style.setProperty(
            "--show-install-banner",
            "flex"
          );
        }
        window.installEvent = event;
        window.installType = "android";
      }
    });
  }
}

window.addEventListener("DOMContentLoaded", setMetaData);
window.addEventListener("htmx:afterOnLoad", setMetaData);

window.addEventListener("popstate", function (e) {
  const targetUrl =
    e.state && e.state.url ? e.state.url : window.location.pathname;
  htmx
    .ajax("GET", targetUrl, {
      target: "#content",
      swap: "outerHTML",
      select: "#content",
    })
});

function logPageView(currentUrl) {
  const endpoint = '/view';

  fetch(endpoint, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ url: currentUrl })
  })
  .then(response => {
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
  })
  .then(data => {
  })
  .catch(error => {
  });
}

function showIphoneInstallDialog() {
  document.documentElement.style.setProperty("--show-iphone-install", "block");
}

window.addEventListener("appinstalled", () => {
  document.documentElement.style.setProperty("--show-install", "none");
  document.documentElement.style.setProperty("--show-install-banner", "none");
});

const isIphone = /iPhone/i.test(navigator.userAgent);
const isStandalone = window.matchMedia("(display-mode: standalone)").matches;

if (isIphone && !isStandalone) {
  window.installType = "iphone";
  document.documentElement.style.setProperty("--show-install", "block");
} else if (window.installEvent !== undefined) {
  if (!isStandalone) {
    document.documentElement.style.setProperty("--show-install", "block");
  }
} else {
  window.addEventListener("beforeinstallprompt", (event) => {
    event.preventDefault();
    if (!isStandalone) {
      document.documentElement.style.setProperty("--show-install", "block");
      window.installEvent = event;
      window.installType = "android";
    }
  });
  if (window.installType !== 'android' && !isStandalone) {
      document.documentElement.style.setProperty("--show-install", "block");
      window.installType = "other";
  }
}

</script>
