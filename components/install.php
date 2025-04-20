<div class="overlay iphone"></div>
<div class="overlay other"></div>
<div class="iphone-dialog">
  <div class="install-text">
    <ol>
      <li>
        Tapez sur
        <svg
          xmlns="http://www.w3.org/2000/svg"
          height="24"
          viewBox="0 0 24 24"
          width="24"
          fill="currentColor"
        >
          <path d="M0 0h24v24H0V0z" fill="none" />
          <path
            d="M16 5l-1.42 1.42-1.59-1.59V16h-1.98V4.83L9.42 6.42 8 5l4-4 4 4zm4 5v11c0 1.1-.9 2-2 2H6c-1.11 0-2-.9-2-2V10c0-1.11.89-2 2-2h3v2H6v11h12V10h-3V8h3c1.1 0 2 .89 2 2z"
          />
        </svg>
      </li>
      <li>Faire défiler verticalement</li>
      <li>
        Tapez : <b>Sur l'Écran d'Accueil</b>
        <svg
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 448 512"
          fill="currentColor"
          width="24"
          fill="currentColor"
          height="24"
        >
          <path
            d="M64 80c-8.8 0-16 7.2-16 16l0 320c0 8.8 7.2 16 16 16l320 0c8.8 0 16-7.2 16-16l0-320c0-8.8-7.2-16-16-16L64 80zM0 96C0 60.7 28.7 32 64 32l320 0c35.3 0 64 28.7 64 64l0 320c0 35.3-28.7 64-64 64L64 480c-35.3 0-64-28.7-64-64L0 96zM200 344l0-64-64 0c-13.3 0-24-10.7-24-24s10.7-24 24-24l64 0 0-64c0-13.3 10.7-24 24-24s24 10.7 24 24l0 64 64 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-64 0 0 64c0 13.3-10.7 24-24 24s-24-10.7-24-24z"
          />
        </svg>
      </li>
    </ol>
    <button
      x-init
      class="install-button"
      @click="
             document.documentElement.style.setProperty('--show-install-iphone', 'none');
           "
    >
      OK
    </button>
  </div>
</div>
<div class="other-dialog" x-data="{ 
  isAppleDevice: /iPad|iPhone|iPod|Mac/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1),
  getBrowser() { 
    return this.isAppleDevice ? 'Safari' : 'Chrome'; 
  },
  getDeviceType() {
    if (/iPhone|iPad|iPod/.test(navigator.userAgent)) return 'iPhone';
    else if (this.isAppleDevice) return 'Mac';
    else return 'Android';
  }
}">
  <div class="install-text">
    <div class="other-text">
      Ce site est installable hors ligne. Ouvrir <b><u>iheb.tn</u></b> sur <b x-text="getBrowser()"></b> pour <b x-text="getDeviceType()"></b>. Puis Tapez <b>Installer 📥</b>.
    </div>
    <button
      x-init
      class="install-button"
      @click="
             document.documentElement.style.setProperty('--show-install-other', 'none');
           "
    >
      OK
    </button>
  </div>
</div>

<style>
.iphone-dialog, .other-dialog {
  height: auto; /* Allow height to adjust */
  min-height: 10rem;
  width: 17rem;
  background-color: var(--bg, white);
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%); /* Better centering */
  z-index: 9999;
  border-radius: 0.5rem;
  padding: 1rem;
  box-sizing: border-box;
    color: var(--fg);
}

.iphone-dialog{
  display: var(--show-install-iphone);
}

.other-text{
  font-size: 1.125rem;
  line-height: 1.75rem;
}

.other-dialog{
  display: var(--show-install-other);
}
.install-text {
  display: flex;
  flex-direction: column;
  align-items: center; /* Centers flex items (like the button) horizontally */
}

.install-text ol {
  padding-left: 20px; /* Adjust as needed */
  margin: 0 0 1em 0; /* Reset default margins, add bottom margin */
  width: 100%; /* Take full width within the flex container */
  text-align: left; /* Ensure list text is left-aligned */
  box-sizing: border-box;
}

.install-text li {
  margin-bottom: 0.5em; /* Spacing between list items */
}
.install-text li:last-child {
    margin-bottom: 0;
}

.install-text svg {
  vertical-align: middle;
  margin: 0 0.2em;
}

.overlay {
  position: fixed;
  top: 0px;
  left: 0;
  height: 100vh;
  width: 100vw;
  background-color: rgba(0, 0, 0, 0.9);
  z-index: 9998;
}
.overlay.iphone {
  display: var(--show-install-iphone);
}
.overlay.other {
  display: var(--show-install-other);
}

.install-button {
  /* Removed align-content as it's not used for this */
  padding: 0.5em 1.2em;
  border-radius: 4px;
  border: none;
  font-weight: bold;
  font-size: 1em;
  cursor: pointer;
  background: var(--blue);
  color: var(--bg);
  transition: background 0.15s;
  margin-top: 1em; /* Add space above the button */
}
</style>
