<script>
// PROGRESS BAR AND HTMX IMPLEMENTAITON
(function () {
  // Only create if it doesn't already exist in the window object
  if (window.progressBarInitialized) return;
  window.progressBarInitialized = true;

  // Create progressBar as a private variable with simplified 3-stage approach
  const progressBar = {
    // Initialize the progress bar - Stage 1: Starting request
    start: () => {
      document.documentElement.style.setProperty("--progress-opacity", "100");
      document.documentElement.style.setProperty("--progress-width", "30%");
    },

    // Skip incremental progress updates - we're using just 3 stages
    progress: () => {
      // No incremental updates needed in 3-stage model
    },

    // Handle successful completion - Stage 3: Completed with sequenced transitions
    complete: () => {
      // First set to 100% width
      document.documentElement.style.setProperty("--progress-width", "100%");

      // After 300ms, hide the bar
      setTimeout(() => {
        document.documentElement.style.setProperty("--progress-opacity", "0");

        // After another 300ms, reset width to 0
        setTimeout(() => {
          document.documentElement.style.setProperty("--progress-width", "0%");
        }, 300);
      }, 300);
    },

    error: (event) => {
      // First hide the bar
      document.documentElement.style.setProperty("--progress-opacity", "0");

      // After 300ms, reset width to 0
      setTimeout(() => {
        document.documentElement.style.setProperty("--progress-width", "0%");

        // Check if the error is actually a redirect
        const xhr = event?.detail?.xhr;
        if (xhr && xhr.status >= 300 && xhr.status < 400) {
          // This is a redirect, get the Location header
          const redirectUrl = xhr.getResponseHeader("Location");
          if (redirectUrl) {
            // Handle the redirect properly
            htmx
              .ajax("GET", redirectUrl, {
                target: "#content",
                swap: "outerHTML scroll:top",
                select: "#content",
              })
              .then(() => {
                window.scrollTo({ top: 0 });
              });
            return; // Don't go to offline page
          }
        }

        // For genuine errors, go to offline page
        htmx
          .ajax("GET", "/offline?v=2", {
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
      // First hide the bar
      document.documentElement.style.setProperty("--progress-opacity", "0");

      // After 300ms, reset width to 0
      setTimeout(() => {
        document.documentElement.style.setProperty("--progress-width", "0%");
      }, 300);
    },
  };

  // Expose progressBar methods for event handlers
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
isDirectPageLoad = true;

// Update page title and URL based on content metadata
function setMetaData() {
  window.scrollTo({
    top: 0,
  });

  const metadataElement = document.getElementById("metadata");
  if (!metadataElement) return;

  document.title = metadataElement.innerText;
  const currentPath = window.location.pathname;

  if (currentPath !== "/guide/viewer" && window.location.hash) {
    history.replaceState(
      { url: window.location.pathname },
      document.title,
      window.location.href.split("#")[0]
    );
  }

  let href = metadataElement.getAttribute("href");
  if (currentPath === "/guide/viewer") {
    href += window.location.hash;
    if (window.location.hash) {
      localStorage.setItem("currentGuideId", window.location.hash.substring(1));
    }
  } else {
    href = href.split("#")[0];
  }

  // Only push new state if the URL is actually changing
  if (href !== currentPath) {
    // Use replaceState for /guide/viewer with a hash
    if (currentPath === "/guide/viewer" && window.location.hash) {
      history.replaceState({ url: href }, document.title, href);
    } else {
      history.pushState({ url: href }, document.title, href);
    }
  }

  if (isDirectPageLoad) {
    localStorage.removeItem("previousUrl");
    isDirectPageLoad = false;
  } else if (currentPath !== "/guide/viewer" && currentPath !== href) {
    localStorage.setItem("previousUrl", currentPath);
  }

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

// Add global popstate listener for fake history navigation via AJAX
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


// init typewriter for home page 
function typewriter() {
    return {
        jobs: [
            "Médecin résident en microbiologie",
            "Programmeur du dimanche",
            "Responsable web/app @ AMENA",
        ],
        displayText: '',
        currentJob: 0,
        charIndex: 0,
        isDeleting: false,
        typeDelay: 100,
        deleteDelay: 50,
        pauseDelay: 1500,

        init() {
            this.typeNextChar();
        },

        typeNextChar() {
            const currentText = this.jobs[this.currentJob];

            if (!this.isDeleting) {
                // Typing
                this.displayText = currentText.substring(0, this.charIndex + 1);
                this.charIndex++;

                // If completed typing
                if (this.charIndex >= currentText.length) {
                    this.isDeleting = false;
                    // Wait before starting to delete
                    setTimeout(() => {
                        this.isDeleting = true;
                        this.typeNextChar();
                    }, this.pauseDelay);
                    return;
                }
            } else {
                // Deleting
                this.displayText = currentText.substring(0, this.charIndex - 1);
                this.charIndex--;

                // If completed deleting
                if (this.charIndex <= 0) {
                    this.isDeleting = false;
                    this.currentJob = (this.currentJob + 1) % this.jobs.length;
                }
            }
            const delay = this.isDeleting ? this.deleteDelay : this.typeDelay;
            setTimeout(() => this.typeNextChar(), delay);
        }
    }
}
</script>
