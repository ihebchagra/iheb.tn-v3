<div style="display: none;" href='/prelevements' id="metadata">Guide de Prélèvements - iheb.tn</div>

<h1>🩸 Guide de Prélèvements</h1>

<!-- Search Component Container -->
<div id="prelevements-search" x-data="prelevementsSearchApp()" x-init="initLoader()">

  <!-- Loading Database Indicator -->
  <div x-show="isLoading" class="loading-section">
    <p x-text="loadingStatus" class="loading-status-text"></p>
    <!-- Progress bar for determinate loading states -->
    <progress
      x-show="loadingProgress > 0 && loadingProgress < 100"
      x-bind:value="loadingProgress"
      max="100"
      class="loading-progress-bar"
      aria-label="Progression du chargement de la base de données"
    ></progress>
    <!-- Unicode spinners for indeterminate states -->
    <span x-show="isLoading && loadingProgress >= 100" class="loading-spinner" aria-hidden="true">⚙️</span>
    <span x-show="isLoading && loadingProgress === 0" class="loading-spinner" aria-hidden="true">⏳</span>
  </div>

  <!-- Search UI (Shown after loading) -->
  <div x-show="dbLoaded" class="search-ui-section" x-transition>
    <div class="autocomplete-container">
      <input
        type="search"
        id="search-term-input"
        placeholder="Nom, Technique, Spécimen..."
        x-model="searchTerm"
        @keydown="handleAutocompleteKeydown($event)"
        @keydown.enter.prevent="performSearch()"
        @input="handleSearchInput()"
        @focus="fetchAutocompleteResults()"
        @click.away="closeAutocomplete()"
        class="search-input"
        aria-label="Terme de recherche"
      >

      <!-- Autocomplete Dropdown -->
      <div 
        x-show="showAutocomplete" 
        class="autocomplete-dropdown"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
      >
        <div x-show="autocompleteLoading" class="autocomplete-loading">
          Chargement des suggestions...
        </div>
        <ul x-show="!autocompleteLoading && autocompleteResults.length > 0">
          <template x-for="(item, index) in autocompleteResults" :key="index">
            <li 
              @click="selectAutocompleteItem(index)"
              @mouseenter="selectedAutocompleteIndex = index"
              :class="{ 'selected': selectedAutocompleteIndex === index }"
              x-html="item.html"
            ></li>
          </template>
        </ul>
      </div>
    </div>

    <button
      @click="performSearch()"
      class="search-button"
      :disabled="isLoading || !searchTerm.trim()"
      aria-label="Effectuer la recherche"
    >Recherche</button>
  </div>

  <!-- Search Results Section -->
  <div id="search-results-container" class="results-section" aria-live="polite">
    <!-- Loading Results Indicator -->
    <div x-show="searchPerformed && !searchError && !isLoading && searchInProgress" class="results-loading-indicator">
      <span class="rotating-icon" aria-hidden="true">🔍</span>
      <span>Recherche en cours...</span>
    </div>

    <!-- Error Message -->
    <p x-show="searchError" x-text="searchError" class="search-error-message"></p>

    <!-- No Results Message -->
    <p x-show="!isLoading && searchPerformed && searchResults.length === 0 && !searchError && !searchInProgress" class="no-results-message">
      Aucun résultat trouvé pour "<span x-text="lastSearchedTerm"></span>".
    </p>

    <!-- Results list section -->
    <template x-for="result in filteredResults" :key="result.rowid">
      <div class="result-item" x-data="{ open: false }">
        <!-- Result Header (Always Visible) -->
        <div @click="open = !open" class="result-header">
          <div class="result-title">
            <span class="prop-value prop-value-title" x-html="makeTitle(result.title, result.price)"></span>
          </div>
          <!-- Rotating Arrow Indicator -->
          <span class="arrow-indicator"
            :class="{ 'rotate-90': open }"
            aria-hidden="true">
            ❯
          </span>
        </div>

        <!-- Collapsible Content -->
        <div x-show="open" x-collapse class="result-details"
             x-init="$nextTick(() => updateLastVisibleSection($el))"
             @x-collapse:expanded="updateLastVisibleSection($el)">

          <!-- Laboratory Information -->
          <template x-if="result.laboratory">
            <div class="details-section field-laboratory"
                 :class="{'last-visible-section': isLastVisibleSection($el)}"
                 x-effect="if(open) updateVisibilityStatus($el, true)">
              <strong class="prop-label prop-label-laboratory">Laboratoire :</strong>
              <span class="prop-value prop-value-laboratory" x-text="result.laboratory"></span>
            </div>
          </template>

          <!-- Specimens Information -->
          <template x-if="result.specimens">
            <div class="details-section field-specimens"
                 :class="{'last-visible-section': isLastVisibleSection($el)}"
                 x-effect="if(open) updateVisibilityStatus($el, true)">
              <strong class="prop-label prop-label-specimens">Prélèvement(s) :</strong>
              <span class="prop-value prop-value-specimens" x-html="result.specimens.replace(' OU ', '<b> OU </b> ')"></span>
            </div>
          </template>

          <!-- Price & Delay Information -->
          <div class="details-section"
               x-show="result.price || result.delay"
               :class="{'last-visible-section': isLastVisibleSection($el)}"
               x-effect="if(open) updateVisibilityStatus($el, result.price || result.delay)">
            <template x-if="result.price">
              <div class="field-container field-price">
                <strong class="prop-label prop-label-price">Prix (Institut Pasteur de Tunis) :</strong>
                <span class="prop-value prop-value-price" x-text="result.price_formatted + ' TND'"></span>
              </div>
            </template>
          </div>

          <!-- Technique Information -->
          <template x-if="result.technique">
            <div class="details-section field-technique"
                 :class="{'last-visible-section': isLastVisibleSection($el)}"
                 x-effect="if(open) updateVisibilityStatus($el, true)">
              <strong class="prop-label prop-label-technique">Technique :</strong>
              <span class="prop-value prop-value-technique" x-text="result.technique"></span>
            </div>
          </template>

        </div>
        <!-- End Collapsible Content -->
      </div>
    </template>
  </div>
</div> <!-- End Search Component Container -->

<script>
function makeTitle(title, price) {
  return '<span class="title-main">' + title + '</span>';
}

function prelevementsSearchApp() {
  return {
    isLoading: true,
    loadingStatus: 'Initialisation...',
    loadingProgress: 0,
    dbLoaded: false,
    db: null,
    SQL: null,
    searchTerm: '',
    lastSearchedTerm: '',
    searchResults: [],
    searchError: null,
    resultCountText: '',
    searchPerformed: false,
    searchInProgress: false,
    activeFilter: 'all',
    dbPath: '/assets/db/prelevements.db',
    ftsTableName: 'prelevements',
    
    // Properties for autocomplete
    showAutocomplete: false,
    autocompleteResults: [],
    autocompleteLoading: false,
    selectedAutocompleteIndex: -1,
    autocompleteTimeout: null,

    isLastVisibleSection(el) {
      if (!el || !el.parentElement) return false;
      const sections = Array.from(el.parentElement.querySelectorAll('.details-section'));
      const visibleSections = sections.filter(section =>
        section.offsetParent !== null
      );
      return visibleSections.length > 0 && visibleSections[visibleSections.length - 1] === el;
    },

    updateVisibilityStatus(el, isVisible) {
      this.$nextTick(() => {
        if (el && el.parentElement) {
          this.updateLastVisibleSection(el.parentElement);
        }
      });
    },

    updateLastVisibleSection(container) {
      if (!container) return;
      setTimeout(() => {
        const sections = Array.from(container.querySelectorAll('.details-section'));
        sections.forEach(section => section.classList.remove('last-visible-section'));
        const visibleSections = sections.filter(section =>
          section.offsetParent !== null
        );
        if (visibleSections.length > 0) {
          visibleSections[visibleSections.length - 1].classList.add('last-visible-section');
        }
      }, 50);
    },

    get filteredResults() {
      let results = this.searchResults;

      if (this.activeFilter === 'hasPrice') {
        results = results.filter(result => result.price != null && result.price !== '');
      } else if (this.activeFilter === 'hasDelay') {
        results = results.filter(result => result.delay != null && result.delay !== '');
      }

      return results.slice(0, 100);
    },

    async initLoader() {
      this.isLoading = true;
      this.dbLoaded = false;
      this.searchError = null;
      this.loadingProgress = 0;

      try {
        this.updateStatus('Initialisation...', 5);
        this.SQL = await initSqlJs({
          locateFile: filename => `/${filename}`
        });
        this.updateStatus(`Demande du fichier de base de données...`, 15);

        const response = await fetch(this.dbPath);
        if (!response.ok) {
          throw new Error(`Échec de la récupération de la base de données: ${response.statusText} (Statut: ${response.status})`);
        }

        const contentLength = response.headers.get('Content-Length');
        const totalSize = contentLength ? parseInt(contentLength, 10) : null;

        if (!totalSize) {
          this.updateStatus('Taille de la base de données inconnue. Chargement...', 20);
          this.loadingProgress = 0;
        } else {
          this.updateStatus(`Téléchargement de la base de données (${(totalSize / 1024 / 1024).toFixed(2)} Mo)...`, 20);
        }

        if (!response.body) {
          throw new Error('ReadableStream non supporté ou la réponse fetch manque de corps.');
        }

        const reader = response.body.getReader();
        let bytesLoaded = 0;
        let chunks = [];

        while (true) {
          const { done, value } = await reader.read();
          if (done) break;

          chunks.push(value);
          bytesLoaded += value.length;

          if (totalSize) {
            const progress = (bytesLoaded / totalSize) * 100;
            this.loadingProgress = Math.min(95, 20 + (progress * 0.75));
            this.updateStatus(`Téléchargement de la base de données pour la fonctionnalité hors ligne... ${(bytesLoaded / 1024 / 1024).toFixed(2)} Mo / ${(totalSize / 1024 / 1024).toFixed(2)} Mo`);
          } else {
            this.updateStatus(`Chargement de la base de données... ${(bytesLoaded / 1024 / 1024).toFixed(2)} Mo chargés`);
          }
        }
        this.updateStatus('Téléchargement terminé. Traitement...', 95);

        const finalTotalSize = totalSize || bytesLoaded;
        let dbArray = new Uint8Array(finalTotalSize);
        let offset = 0;
        for (const chunk of chunks) {
          dbArray.set(chunk, offset);
          offset += chunk.length;
        }
        chunks = [];

        this.updateStatus('Chargement de la base de données en mémoire...', 98);
        this.db = new this.SQL.Database(dbArray);
        this.updateStatus('Base de données prête !', 100);
        this.dbLoaded = true;
        this.isLoading = false;

      } catch (err) {
        this.updateStatus(`Erreur : ${err.message}.`, 0);
        this.isLoading = false;
        this.searchError = `Échec du chargement de la base de données : ${err.message}`;
      }
    },

    updateStatus(message, progress) {
      this.loadingStatus = message;
      if (progress !== undefined) {
        this.loadingProgress = progress;
      }
    },

    formatSearchTerm(term) {
      return term.trim()
        .split(/\s+/)
        .filter(part => part.length > 0)
        .map(part => part.replace(/[^a-zA-Z0-9\u00C0-\u017F]/g, ' ').trim())
        .filter(part => part.length > 0)
        .map(part => part + '*')
        .join(' ');
    },

    async performSearch() {
      if (!this.dbLoaded || !this.db) {
        this.searchError = "Base de données non prête.";
        return;
      }
      if (!this.searchTerm.trim()) {
        this.searchResults = [];
        this.resultCountText = '';
        this.searchError = null;
        this.searchPerformed = false;
        return;
      }

      this.activeFilter = 'all';
      this.searchError = null;
      this.searchPerformed = true;
      this.lastSearchedTerm = this.searchTerm;
      this.searchInProgress = true;
      this.resultCountText = 'Recherche en cours...';
      this.closeAutocomplete(); // Close autocomplete when performing search

      const ftsQueryTerm = this.formatSearchTerm(this.searchTerm);

      if (!ftsQueryTerm) {
        this.searchError = "Terme de recherche invalide après formatage.";
        this.searchResults = [];
        this.resultCountText = '0 résultats';
        this.searchInProgress = false;
        return;
      }

      try {
        await new Promise(resolve => setTimeout(resolve, 10));

        const query = `
          SELECT
              rowid,
              title, price, delay, laboratory, specimens, technique, code_ipt
          FROM "${this.ftsTableName}"
          WHERE "${this.ftsTableName}" MATCH ?
          ORDER BY rank
        `;

        const stmt = this.db.prepare(query);
        stmt.bind([ftsQueryTerm]);

        const results = [];
        while (stmt.step()) {
          let row = stmt.getAsObject();
          // Format price if available
          row.price_formatted = row.price ? parseFloat(row.price).toFixed(3) : 'N/A';
          results.push(row);
        }
        stmt.free();

        this.searchResults = results;
        const count = results.length;
        this.resultCountText = `${count >= 100 ? 100 : count}${count >= 100 ? '+' : ''} résultat${count > 1 ? 's' : ''} trouvé${count > 1 ? 's' : ''}.`;

      } catch (err) {
        let errorMsg = err.message;
        if (errorMsg.includes('malformed MATCH expression') || errorMsg.includes('fts5 syntax error')) {
          errorMsg = 'Syntaxe de recherche invalide. Essayez d\'utiliser des mots simples ou des parties de mots.';
        }
        this.searchError = `Erreur de recherche : ${errorMsg}`;
        this.searchResults = [];
        this.resultCountText = '';
      } finally {
        this.searchInProgress = false;
      }
    },

    resetFilterIfNeeded() {
      if (!this.searchTerm.trim()) {
        this.searchResults = [];
        this.activeFilter = 'all';
        this.searchPerformed = false;
        this.resultCountText = '';
        this.searchError = null;
      }
    },
    
    // Autocomplete methods
    async fetchAutocompleteResults() {
      if (!this.dbLoaded || !this.db || !this.searchTerm.trim()) {
        this.autocompleteResults = [];
        this.showAutocomplete = false;
        return;
      }
      
      this.autocompleteLoading = true;
      const term = this.searchTerm.trim();
      
      try {
        const ftsQueryTerm = term
          .split(/\s+/)
          .filter(part => part.length > 0)
          .map(part => part.replace(/[^a-zA-Z0-9\u00C0-\u017F]/g, ' ').trim())
          .filter(part => part.length > 0)
          .map(part => part + '*')
          .join(' ');
          
        if (!ftsQueryTerm) {
          this.autocompleteResults = [];
          this.showAutocomplete = false;
          this.autocompleteLoading = false;
          return;
        }
        
        const query = `
          SELECT DISTINCT title, technique
          FROM "${this.ftsTableName}"
          WHERE "${this.ftsTableName}" MATCH ?
          ORDER BY rank
          LIMIT 10
        `;
        
        const stmt = this.db.prepare(query);
        stmt.bind([ftsQueryTerm]);
        
        const results = [];
        while (stmt.step()) {
          const row = stmt.getAsObject();
          const suggestion = {
            text: row.title + (row.technique ? ' (' + row.technique + ')' : ''),
            html: this.highlightMatch(row.title, term) + 
                 (row.technique ? ' (' + this.highlightMatch(row.technique, term) + ')' : '')
          };
          results.push(suggestion);
        }
        stmt.free();
        
        this.autocompleteResults = results;
        this.showAutocomplete = results.length > 0;
      } catch (err) {
        console.error("Autocomplete error:", err);
        this.autocompleteResults = [];
        this.showAutocomplete = false;
      } finally {
        this.autocompleteLoading = false;
      }
    },
    
    highlightMatch(text, term) {
      if (!text) return '';
      
      // Simple highlighting - case insensitive
      const regex = new RegExp('(' + term.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&') + ')', 'gi');
      return text.replace(regex, '<strong class="highlight">$1</strong>');
    },
    
    selectAutocompleteItem(index) {
      if (index >= 0 && index < this.autocompleteResults.length) {
        this.searchTerm = this.autocompleteResults[index].text;
        this.closeAutocomplete();
        this.performSearch();
      }
    },
    
    handleAutocompleteKeydown(event) {
      if (!this.showAutocomplete) return;
      
      // Down arrow
      if (event.key === 'ArrowDown') {
        event.preventDefault();
        this.selectedAutocompleteIndex = Math.min(
          this.selectedAutocompleteIndex + 1, 
          this.autocompleteResults.length - 1
        );
      }
      // Up arrow
      else if (event.key === 'ArrowUp') {
        event.preventDefault();
        this.selectedAutocompleteIndex = Math.max(this.selectedAutocompleteIndex - 1, -1);
      }
      // Enter key
      else if (event.key === 'Enter') {
        if (this.selectedAutocompleteIndex >= 0) {
          event.preventDefault();
          this.selectAutocompleteItem(this.selectedAutocompleteIndex);
        }
      }
      // Escape key
      else if (event.key === 'Escape') {
        event.preventDefault();
        this.closeAutocomplete();
      }
    },
    
    closeAutocomplete() {
      this.showAutocomplete = false;
      this.selectedAutocompleteIndex = -1;
    },
    
    handleSearchInput() {
      this.searchPerformed = false;
      this.searchResults = [];
      
      // Debounce autocomplete requests
      clearTimeout(this.autocompleteTimeout);
      this.autocompleteTimeout = setTimeout(() => {
        this.fetchAutocompleteResults();
      }, 200); // 200ms delay
    }
  };
}
</script>

<style>
/* Base Styles */
h1, h2 {
  text-align: center;
}

#medicament-search {
  width: 100%;
}

/* Loading Indicators */
.loading-section {
  text-align: center;
  padding: 20px 10px;
  background-color: var(--bg1);
  border-radius: 5px;
  margin-bottom: 1.5rem;
  color: var(--fg2);
}

.loading-status-text {
  font-weight: bold;
  color: var(--fg1);
  margin-bottom: 10px;
}

.loading-progress-bar {
  -webkit-appearance: none;
  appearance: none;
  width: 80%;
  max-width: 300px;
  height: 8px;
  border: 1px solid var(--bg4);
  background-color: var(--bg2);
  border-radius: 4px;
  overflow: hidden;
  display: block;
  margin: 10px auto 0 auto;
}

/* Progress bar styling */
.loading-progress-bar::-webkit-progress-bar {
  background-color: var(--bg2);
  border-radius: 4px;
}
.loading-progress-bar::-webkit-progress-value {
  background-color: var(--orange);
  border-radius: 4px;
  transition: width 0.2s ease;
}
.loading-progress-bar::-moz-progress-bar {
  background-color: var(--orange);
  border-radius: 4px;
  transition: width 0.2s ease;
}

.loading-spinner {
  display: inline-block;
  margin-top: 10px;
  font-size: 1.5em;
  color: var(--orange);
  animation: spin 1.5s linear infinite;
}

/* Search Results Loading Indicator */
.results-loading-indicator {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 15px;
  background-color: var(--bg1);
  border-radius: 5px;
  margin-bottom: 1rem;
  color: var(--fg1);
  font-style: italic;
}

.rotating-icon {
  display: inline-block;
  margin-right: 10px;
  font-size: 1.2em;
  animation: spin 1.5s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Search UI */
.search-ui-section {
  display: flex;
  gap: 0.5rem;
  align-items: center;
  padding: 15px 10px;
  background-color: var(--bg1);
  border-radius: 5px;
  margin-bottom: 0.5rem;
}

.search-input {
  flex-grow: 1;
  min-width: 200px;
  padding-left: 0.75rem;
  padding-right: 0.75rem;
  padding-top: 0.5rem;
  padding-bottom: 0.5rem;
  border: 1px solid var(--bg4);
  border-radius: 4px;
  background-color: var(--bg);
  color: var(--fg);
  font-size: 1em;
}
.search-input:focus {
  outline: 2px solid var(--blue);
  border-color: var(--blue);
}

.search-button {
  padding: 10px 15px;
  background-color: var(--blue);
  color: var(--bg);
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: bold;
  transition: background-color 0.15s ease;
}

.search-button:hover:not(:disabled) {
  background-color: var(--blue-dim);
}
.search-button:disabled {
  background-color: var(--bg4);
  color: var(--fg4);
  cursor: not-allowed;
}

.result-count-text {
  width: 100%;
  text-align: center;
  margin-top: 0.5rem;
  font-size: 0.9em;
  color: var(--fg3);
  font-style: italic;
}

/* Filters */
.filter-controls {
  display: flex;
  justify-content: center;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 1rem;
  padding: 5px 10px;
  text-align: center;
  background-color: var(--bg2);
  border-radius: 4px;
}

.filter-controls span:first-child {
  color: var(--fg1);
  font-weight: bold;
  margin-right: 5px;
}

.filter-count {
  font-size: 0.9em;
  color: var(--fg3);
  margin-left: 10px;
}

.filter-button {
  padding: 5px 10px;
  border: 1px solid var(--bg4);
  background-color: var(--bg);
  color: var(--fg);
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.9em;
  transition: background-color 0.15s ease, border-color 0.15s ease;
}

.filter-button:hover {
  background-color: var(--bg3);
  border-color: var(--fg4);
}

.filter-button.active-filter-button {
  background-color: var(--green);
  color: var(--bg);
  border-color: var(--green);
  font-weight: bold;
}

/* Results Section */
.results-section {
  margin-top: 1rem;
}

.result-item {
  background-color: var(--bg1);
  border: 1px solid var(--bg3);
  border-radius: 5px;
  padding: 15px;
  margin-bottom: 1rem;
}

.result-header {
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.result-title {
  flex: 1;
}

.result-details {
  margin-top: 10px;
}

/* Details section with dividers */
.details-section {
  margin-bottom: 8px;
  padding-bottom: 8px;
  border-bottom: 1px solid var(--bg3);
}

/* Special styling for the last visible section */
.details-section.last-visible-section {
  margin-bottom: 0 !important;
  padding-bottom: 0 !important;
  border-bottom: none !important;
}

.result-item .prop-value-nom-commercial {
  margin-top: 0;
  font-size: 1.1rem;
}

.result-item .title-main {
  color: var(--red);
  font-weight: 700;
}

.result-item .title-extra {
  font-weight: 400;
  color: var(--fg2);
}

.field-container {
  padding-bottom: 0.25rem;
  padding-top: 0.25rem;
}

.prop-label {
  color: var(--green);
  margin-right: 5px;
}

.prop-value-prix-public {
  font-weight: 700;
}

.result-item a {
  color: var(--purple);
  font-weight: bold;
}

.result-item a:hover {
  color: var(--blue-dim);
}

/* Arrow Indicator */
.arrow-indicator {
  display: inline-block;
  font-size: 0.8em;
  color: var(--fg3);
  transition: transform 0.2s ease-in-out;
  margin-left: 0.25rem;
}

.arrow-indicator.rotate-90 {
  transform: rotate(90deg);
}

/* Messages */
.search-error-message,
.no-results-message {
  text-align: center;
  color: var(--orange);
  font-style: italic;
  background-color: var(--bg2);
  padding: 10px;
  border-radius: 4px;
  border: 1px solid var(--orange-dim);
}

.no-results-message span {
  font-weight: bold;
  font-style: normal;
}

/* Hide elements managed by Alpine initially */
[x-cloak] {
  display: none !important;
}

h1{
  margin-bottom: 1rem;
}
/* Autocomplete Styles */
.autocomplete-container {
  position: relative;
  flex-grow: 1;
  display: flex;
}

.search-input {
  flex-grow: 1;
  width: 100%;
  min-width: 200px;
  padding-left: 0.75rem;
  padding-right: 0.75rem;
  padding-top: 0.5rem;
  padding-bottom: 0.5rem;
  border: 1px solid var(--bg4);
  border-radius: 4px;
  background-color: var(--bg);
  color: var(--fg);
  font-size: 1em;
}

.autocomplete-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  z-index: 10;
  max-height: 300px;
  overflow-y: auto;
  background-color: var(--bg);
  border: 1px solid var(--bg4);
  border-radius: 4px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  margin-top: 4px;
}

.autocomplete-dropdown ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.autocomplete-dropdown li {
  padding: 8px 12px;
  cursor: pointer;
  border-bottom: 1px solid var(--bg2);
  transition: background-color 0.15s ease;
}

.autocomplete-dropdown li:last-child {
  border-bottom: none;
}

.autocomplete-dropdown li:hover,
.autocomplete-dropdown li.selected {
  background-color: var(--bg2);
}

.autocomplete-dropdown li .highlight {
  color: var(--blue);
  font-weight: bold;
}

.autocomplete-loading {
  padding: 10px;
  text-align: center;
  color: var(--fg3);
  font-style: italic;
}

/* Animation transitions */
.transition {
  transition-property: opacity, transform;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}
.duration-200 {
  transition-duration: 200ms;
}
.opacity-0 {
  opacity: 0;
}
.opacity-100 {
  opacity: 1;
}
.translate-y-0 {
  transform: translateY(0);
}
.translate-y-1 {
  transform: translateY(0.25rem);
}
</style>
