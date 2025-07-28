<div style="display: none;" href="/casfm-viewer" id="metadata">CA-SFM Search - iheb.tn</div>

<!-- go back button : if q is set, go to search page, set hx-get to ?q=, else go to viewer page -->
<button id="go-back" hx-get="/casfmsearch" hx-target="#content" hx-swap="outerHTML" hx-select="#content">Retour</button>
<div id="zoom-container">
  <div id="zoom-inner">
    <div id='pages-container'>
      <?php
      // width and height are hardcoded using htis logic:
      //width : (i >= 42 && i <= 132) || (i >= 137 && i <= 147) || (i >= 165 && i <= 177) ? 2339 : 1654,
      //height : (i >= 42 && i <= 132) || (i >= 137 && i <= 147) || (i >= 165 && i <= 177) ? 1654 : 2339,
      global $pages;
      foreach (range(1, 172) as $page) {
        $width = ($page >= 41 && $page <= 125) || ($page >= 130 && $page <= 138) || ($page >= 159 && $page <= 172) ? 2339 : 1654;
        $height = ($page >= 41 && $page <= 125) || ($page >= 130 && $page <= 138) || ($page >= 159 && $page <= 172) ? 1654 : 2339;
        // first image has 3 rem margin-top
        if ($page == 1) {
          echo "<img id='{$page}' data-src='/assets/casfm-pages/page_{$page}.webp?v=2' alt='' width='{$width}' height='{$height}' style='max-width: 100%; height: auto; margin-top: 3rem;'>";
        } else {
          echo "<img id='{$page}' data-src='/assets/casfm-pages/page_{$page}.webp?v=2' alt='' width='{$width}' height='{$height}' style='max-width: 100%; height: auto;'>";
        }
      ?>
      <?php
      }
      ?>
    </div>


    <div id='guide-navigation-controller'></div>
    <script>
      (function() {
        if (window.guideNavigationInitialized) return;
        window.guideNavigationInitialized = true;

        function navigateToTargetImage() {
          if (window.location.pathname !== '/casfm-viewer') return;
          const controller = document.getElementById('guide-navigation-controller');
          if (!controller) return;
          let searchParams = new URLSearchParams(window.hash || window.location.hash.replace('#', ''));
          let retourElement = document.getElementById('go-back');
          if (retourElement) {
            if (searchParams.get('q')) {
              retourElement.setAttribute('hx-get', '/casfmsearch#q=' + searchParams.get('q'));
            } else {
              retourElement.setAttribute('hx-get', '/casfmsearch');
            }
          }
          let targetId = searchParams.get('p');
          if (targetId) {
            const element = document.getElementById(targetId);
            if (element) {
              if (!element.hasAttribute('src')) {
                element.setAttribute('src', element.getAttribute('data-src'));
              }
              element.scrollIntoView({
                block: 'center',
                behavior: 'auto'
              });
            }
          }
        }

        function parseHash(hash) {
          if (!hash) return '';
          return hash.replace(/_\\d+$/, '')
        }

        function isOnGuideViewer() {
          return window.location.pathname === '/guide/viewer';
        }

        function handlePageTransition(evt) {
          if (isOnGuideViewer() && evt.detail.pathInfo && evt.detail.pathInfo.requestPath !== '/guide/viewer') {
            window.guideNavigationInitialized = false;
          }
        }

        document.addEventListener('DOMContentLoaded', navigateToTargetImage);
        document.addEventListener('htmx:afterOnLoad', navigateToTargetImage);
        document.addEventListener('htmx:afterSettle', navigateToTargetImage);
        document.addEventListener('htmx:beforeSwap', handlePageTransition);
      })();
    </script>
    <script>
      (function() {
        function lazyLoadImages() {
          const images = document.querySelectorAll('#pages-container img[data-src]');
          const observer = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
              if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.getAttribute('data-src');
                obs.unobserve(img);
              }
            });
          }, {
            rootMargin: '1000px'
          });
          images.forEach(img => observer.observe(img));
        }
        document.addEventListener('DOMContentLoaded', lazyLoadImages);
        document.addEventListener('htmx:afterOnLoad', lazyLoadImages);
        document.addEventListener('htmx:afterSettle', lazyLoadImages);
      })();
    </script>
  </div>
</div>
<a style="display: none;" id="ref-button">Références</a>

<script>
  (function() {
    // Prevent multiple initializations
    if (window.zoomControllerInitialized) {
      return;
    }
    window.zoomControllerInitialized = true;

    // Initialize zoom controller
    const zoomContainer = document.getElementById("zoom-container");
    const zoomInner = document.getElementById("zoom-inner");
    let zoomScale = 1;

    function clamp(value, min, max) {
      return Math.max(min, Math.min(max, value));
    }

    let isPinching = false;
    let pinchStartDist = 0;
    let pinchStartScale = 1;
    let pinchCenterX = 0;
    let pinchCenterY = 0;

    function distanceBetween(t1, t2) {
      const dx = t1.clientX - t2.clientX;
      const dy = t1.clientY - t2.clientY;
      return Math.sqrt(dx * dx + dy * dy);
    }

    // Initialize the transform to force hardware acceleration
    function initializeTransform() {
      // Apply initial transform to force GPU layer creation
      zoomInner.style.transformOrigin = "0 0";
      zoomInner.style.transform = `scale(${zoomScale}) translateZ(0)`;

      // Force a reflow to ensure the browser creates the composite layers
      void zoomInner.offsetWidth;
    }

    // Run initialization after a short delay
    setTimeout(initializeTransform, 100);

    // Define event handlers
    function handleTouchStart(e) {
      if (e.touches.length === 2) {
        e.preventDefault();
        isPinching = true;
        pinchStartScale = zoomScale;

        const [t1, t2] = e.touches;
        pinchStartDist = distanceBetween(t1, t2);

        const rect = zoomContainer.getBoundingClientRect();
        const midX = ((t1.clientX + t2.clientX) / 2) - rect.left;
        const midY = ((t1.clientY + t2.clientY) / 2) - rect.top;

        pinchCenterX = (midX + zoomContainer.scrollLeft) / zoomScale;
        pinchCenterY = (midY + zoomContainer.scrollTop) / zoomScale;
      }
    }

    function handleTouchMove(e) {
      if (isPinching && e.touches.length === 2) {
        e.preventDefault();
        const [t1, t2] = e.touches;
        const newDist = distanceBetween(t1, t2);

        zoomScale = clamp(
          pinchStartScale * (newDist / pinchStartDist),
          1,
          2.5
        );

        // Use requestAnimationFrame for smoother animations
        requestAnimationFrame(() => {
          zoomInner.style.transformOrigin = "0 0";
          zoomInner.style.transform = `scale(${zoomScale}) translateZ(0)`;

          const rect = zoomContainer.getBoundingClientRect();
          const currentMidX = ((t1.clientX + t2.clientX) / 2) - rect.left;
          const currentMidY = ((t1.clientY + t2.clientY) / 2) - rect.top;
          zoomContainer.scrollLeft = pinchCenterX * zoomScale - currentMidX;
          zoomContainer.scrollTop = pinchCenterY * zoomScale - currentMidY;
        });
      }
    }

    function handleTouchEnd() {
      if (isPinching) {
        isPinching = false;
      }
    }

    // Add event listeners
    zoomContainer.addEventListener("touchstart", handleTouchStart, {
      passive: false
    });
    zoomContainer.addEventListener("touchmove", handleTouchMove, {
      passive: false
    });
    zoomContainer.addEventListener("touchend", handleTouchEnd);

    // Clean up when navigating away
    document.addEventListener('htmx:beforeSwap', function(evt) {
      // Only clean up when we're leaving the page
      if (evt.detail.pathInfo && evt.detail.pathInfo.requestPath !== '/guide/viewer') {
        zoomContainer.removeEventListener("touchstart", handleTouchStart);
        zoomContainer.removeEventListener("touchmove", handleTouchMove);
        zoomContainer.removeEventListener("touchend", handleTouchEnd);
        window.zoomControllerInitialized = false;
      }
    });
  })();
</script>

<style>
  img {
    width: 100%;
    background: var(--bg4);
    margin-left: auto;
    margin-right: auto;
    align-content: center;
  }

  #zoom-inner {
    transform-origin: 0 0;
    will-change: transform;
    margin-left: auto;
    margin-right: auto;
  }

  #zoom-container {
    position: absolute;
    top: 0;
    left: 0;
    touch-action: pan-x pan-y;
    transform-origin: 0 0;
    transition: transform 0.1s ease;
    width: fit-content;
    overflow: auto;
    height: 100vh;
    width: 100%;
    -webkit-overflow-scrolling: touch;
  }

  #pages-container {
    margin-left: auto;
    margin-right: auto;
  }

  #zoom-container::-webkit-scrollbar {
    display: none;
  }

  @media screen and (min-width: 768px) {
    #pages-container {
      width: 100%;
      max-width: 50rem;
    }

  }

  #go-back {
    position: fixed;
    top: 3.5rem;
    left: 0.5rem;
    background-color: var(--bg1);
    border: 2px solid var(--bg2);
    color: var(--fg);
    border-radius: 0.375rem;
    padding: 0.625rem 0.625rem;
    cursor: pointer;
    font-family: inherit;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: 0.15s ease;
    text-decoration-line: none;
    z-index: 10;
    font-weight: 700;
    font-size: 1.1rem;
  }

  /* @media (min-width: 36rem) {
#zoom-container {
left: 50%;
transform: translateX(-50%);
}
} */
</style>
