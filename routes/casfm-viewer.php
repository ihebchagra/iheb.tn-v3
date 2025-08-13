<div style="display: none;" href="/casfm-viewer" id="metadata">CA-SFM Search - iheb.tn</div>

<button id="go-back" hx-get="/casfmsearch" hx-target="#content" hx-swap="outerHTML" hx-select="#content">Retour</button>
<div id="zoom-container">
  <div id='pages-container'>
    <?php
    // This PHP logic remains the same.
    global $pages;
    foreach (range(1, 172) as $page) {
      $width = ($page >= 41 && $page <= 125) || ($page >= 130 && $page <= 138) || ($page >= 159 && $page <= 172) ? 2339 : 1654;
      $height = ($page >= 41 && $page <= 125) || ($page >= 130 && $page <= 138) || ($page >= 159 && $page <= 172) ? 1654 : 2339;
      $style = "display: block; max-width: 100%; height: auto; margin-bottom: 0.25rem;";
      if ($page == 1) {
        $style .= " margin-top: 3rem;";
      }
      echo "<img id='{$page}' data-src='/assets/casfm-pages/page_{$page}.webp?v=2' alt='Page {$page}' width='{$width}' height='{$height}' style='{$style}'>";
    }
    ?>
  </div>
</div>

<a style="display: none;" id="ref-button">Références</a>


<!-- navigation -->
<script>
  (function() {
    if (window.guideNavigationInitialized) return;
    window.guideNavigationInitialized = true;

    function navigateToTargetImage() {
      if (window.location.pathname !== '/casfm-viewer') return;
      const controller = document.getElementById('guide-navigation-controller'); // This ID doesn't exist, but we can leave the check.
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

    function handlePageTransition(evt) {
      if (evt.detail.pathInfo && evt.detail.pathInfo.requestPath !== '/casfm-viewer') {
        window.guideNavigationInitialized = false;
      }
    }

    document.addEventListener('DOMContentLoaded', navigateToTargetImage);
    document.addEventListener('htmx:afterOnLoad', navigateToTargetImage);
    document.addEventListener('htmx:afterSettle', navigateToTargetImage);
    document.addEventListener('htmx:beforeSwap', handlePageTransition);
  })();
</script>

<!-- lazy loading -->
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


<!-- ZOOM CONTROLLER SCRIPT USING PANZOOM.JS -->
<script>
  (function() {
    // Prevent multiple initializations
    if (window.panzoomControllerInitialized) {
      return;
    }
    window.panzoomControllerInitialized = true;

    const elem = document.getElementById('zoom-container');
    if (!elem) return;

    // Initialize Panzoom with the proven options from your other project
    const panzoom = Panzoom(elem, {
      maxScale: 5,
      minScale: 1,
      canvas: true,
      contain: 'outside',
      roundPixels: true,
      panOnlyWhenZoomed: true,
      disableYAxis: true,
      touchAction: "pan-y",
    });

    // Enable mouse wheel zooming (for desktop users)
    elem.addEventListener('wheel', function(event) {
        // Require ctrlKey to zoom with wheel, to not interfere with scrolling
        if (!event.ctrlKey) return;
        event.preventDefault();
        panzoom.zoomWithWheel(event);
    });

    // Clean up when navigating away via HTMX
    document.addEventListener('htmx:beforeSwap', function(evt) {
      if (evt.detail.pathInfo && evt.detail.pathInfo.requestPath !== '/casfm-viewer') {
        panzoom.destroy();
        window.panzoomControllerInitialized = false;
      }
    });
  })();
</script>

<style>
  #pages-container img {
    display: block;
    width: 100%;
    background: var(--bg4);
    margin-left: auto;
    margin-right: auto;
  }

  #zoom-container {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    -webkit-overflow-scrolling: touch;
    will-change: transform;
    transform-origin: top center;
  }

  #zoom-container::-webkit-scrollbar {
    display: none;
  }

  #pages-container {
    margin: 0 auto;
    max-width: 50rem;
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
</style>
